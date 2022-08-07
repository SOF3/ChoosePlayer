<?php

declare(strict_types=1);

namespace SOFe\ChoosePlayer;

use dktapps\pmforms\MenuForm;
use dktapps\pmforms\MenuOption;
use Generator;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\AssumptionFailedError;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;
use SOFe\AwaitGenerator\Await;
use SOFe\AwaitGenerator\Traverser;
use function array_keys;
use function array_map;
use function count;
use function crc32;
use function is_int;
use function strtolower;
use function time;
use function uasort;

final class Main extends PluginBase {
    private static ?self $instance = null;

    private DataConnector $db;
    private int $batchSize = 20;

    /**
     * @internal This is not part of the public API.
     */
    public static function getInstance() : ?self {
        return self::$instance;
    }

    /**
     * @internal This is not part of the public API.
     * @var array<string, Suggester>
     */
    public array $suggesters = [];

    protected function onEnable() : void {
        self::$instance = $this;

        $this->saveDefaultConfig();
        $this->db = libasynql::create($this, [
            "type" => "sqlite",
            "sqlite" => [
                "file" => "data.sqlite",
            ],
        ], [
            "sqlite" => "queries.sql",
        ]);

        ChoosePlayer::suggest(new OnlinePlayerSuggester($this->getServer()));
    }

    /**
     * @internal This is not part of the public API.
     * @return Generator<mixed, mixed, mixed, ?ChoosePlayerResult>
     */
    public function chooseImpl(Player $who, string $text = "") : Generator {
        while (true) {
            $suggesters = yield from $this->sortSuggestersFor($who->getUniqueId()->toString());

            $selection = yield from $this->asyncMenuForm($who, "Choose player by...", $text, array_map(function(Suggester $suggester) {
                return new MenuOption($suggester->getDisplayName());
            }, $suggesters));

            if ($selection === null) {
                return null; // cancelled
            }

            /** @var Suggester $suggester */
            $suggester = $suggesters[$selection];

            $usageId = yield from $this->recordUsage($who, $suggester);

            $gen = $suggester->suggest($who);
            if ($gen === null) {
                continue; // retry
            }

            $traverser = new Traverser($gen);
            while (true) {
                /** @var DisplayPlayerOptionsResult $displayResult */
                $displayResult = yield from $this->displayPlayerOptions($who, $traverser, $text);

                if ($displayResult->type === DisplayPlayerOptionsResult::CANCELLED) {
                    continue 2; // choose suggester again
                }

                if ($displayResult->type === DisplayPlayerOptionsResult::NEXT_PAGE) {
                    continue;
                }

                $result = $displayResult->result;
                if ($result === null) {
                    throw new AssumptionFailedError("type === SELECTED implies result !== null");
                }

                // make sure `finally` blocks are called
                try {
                    $traverser->interrupt(new TerminateSuggestionsException);
                } catch (TerminateSuggestionsException $_) {
                }

                yield from $this->recordSelectedUsage($usageId);
                return $result;
            }
        }
    }

    private function displayPlayerOptions(Player $player, Traverser $traverser, string $text) : Generator {
        $options = [];
        $suggestions = [];
        /** @var Suggestion $suggestion */
        for ($i = 0; $i < $this->batchSize && $traverser->next($suggestion); $i++) {
            $options[] = new MenuOption($suggestion->display);
            $suggestions[] = $suggestion;
        }

        $options[] = new MenuOption("More options");

        $selected = yield from $this->asyncMenuForm($player, "Choose player", $text, $options);
        if ($selected === null) {
            return new DisplayPlayerOptionsResult(DisplayPlayerOptionsResult::CANCELLED, null);
        }

        if (!isset($suggestions[$selected])) {
            return new DisplayPlayerOptionsResult(DisplayPlayerOptionsResult::NEXT_PAGE, null);
        }

        $suggestion = $suggestions[$selected];
        return new DisplayPlayerOptionsResult(
            DisplayPlayerOptionsResult::SELECTED,
            new ChoosePlayerResult($suggestion->name, $suggestion->uuid),
        );
    }

    /**
     * @return Generator<mixed, mixed, mixed, int|null
     */
    private function asyncMenuForm(Player $player, string $title, string $text, array $options) : Generator {
        $ret = yield from Await::promise(function($resolve) use ($player, $title,$text,$options) {
            $form = new MenuForm($title, $text, $options, $resolve, fn() => $resolve(null));
            $player->sendForm($form);
        });
        if (!is_int($ret) || $ret < 0 || $ret >= count($options)) {
            return null;
        }
        return $ret;
    }

    /**
     * @return Genreator<mixed, mixed, mixed, int>
     */
    private function recordUsage(Player $who, Suggester $suggester) : Generator {
        [$insertId, $_] = yield from $this->db->asyncInsert("record-usage", [
            "player" => strtolower($who->getName()),
            "now" => time(),
            "suggester" => $suggester->getId(),
        ]);
        return $insertId;
    }

    /**
     * @return Genreator<mixed, mixed, mixed, void>
     */
    private function recordSelectedUsage(int $pk) : Generator {
        yield from $this->db->asyncChange("record-selected-usage", [
            "pk" => $pk,
        ]);
    }

    /**
     * @return Generator<mixed, mixed, mixed, list<Suggester>>
     */
    private function sortSuggestersFor(string $playerUuid) : Generator {
        /** @var array<string, Score> $scores */
        $scores = [];
        $gens = [];

        foreach ($this->suggesters as $suggesterName => $_) {
            $gens[] = (function() use ($suggesterName, $playerUuid) {
                $score = yield from $this->scoreSuggesterFor($playerUuid, $suggesterName);
                $scores[$suggesterName] = $score;
            })();
        }

        yield from Await::all($gens);

        uasort($scores, fn(Score $a, Score $b) => ($a->class !== $b->class ? ($a->class <=> $b->class) : ($a->index <=> $b->index)));

        $ret = [];
        foreach ($scores as $suggesterName => $_) {
            $ret[] = $this->suggesters[$suggesterName];
        }
        return array_keys($scores);
    }

    /**
     * @return Generator<mixed, mixed, mixed, Score>
     */
    private function scoreSuggesterFor(string $playerUuid, string $suggesterName) : Generator {
        $lastAcceptTime = yield from $this->db->asyncSelect("time-since-last-personal-use", [
            "player" => $playerUuid,
            "now" => time(),
            "suggester" => $suggesterName,
            "accepted" => 1,
        ]);
        if ($lastAcceptTime !== null) {
            return new Score(Score::CLASS_PERSONAL_LAST_ACCEPT, $lastAcceptTime);
        }

        $lastUseTime = yield from $this->db->asyncSelect("time-since-last-personal-use", [
            "player" => $playerUuid,
            "now" => time(),
            "suggester" => $suggesterName,
            "accepted" => 0,
        ]);
        if ($lastUseTime !== null) {
            return new Score(Score::CLASS_PERSONAL_LAST_USE, $lastUseTime);
        }

        $acceptCount = yield from $this->db->asyncSelect("count-unique-usage-rate", [
            "suggester" => $suggesterName,
            "accepted" => 1,
        ]);
        if ($acceptCount !== null && $acceptCount > 0) {
            return new Score(Score::CLASS_PUBLIC_FREQUENT_ACCEPT, $acceptCount);
        }

        $useCount = yield from $this->db->asyncSelect("count-unique-usage-rate", [
            "suggester" => $suggesterName,
            "accepted" => 0,
        ]);
        if ($useCount !== null && $useCount > 0) {
            return new Score(Score::CLASS_PUBLIC_FREQUENT_USE, $useCount);
        }

        return new Score(Score::CLASS_UNUSED, crc32($suggesterName));
    }
}

final class Score {
    public const CLASS_PERSONAL_LAST_ACCEPT = 0;
    public const CLASS_PERSONAL_LAST_USE = 1;
    public const CLASS_PUBLIC_FREQUENT_ACCEPT = 2;
    public const CLASS_PUBLIC_FREQUENT_USE = 3;
    public const CLASS_UNUSED = 4;

    public function __construct(
        public int $class,
        public int $index,
    ) {
    }
}

final class DisplayPlayerOptionsResult {
    public const SELECTED = 0;
    public const CANCELLED = 1;
    public const NEXT_PAGE = 2;

    public function __construct(
        public int $type,
        public ?ChoosePlayerResult $result,
    ) {
    }
}

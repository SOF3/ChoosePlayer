<?php

declare(strict_types=1);

namespace SOFe\ChoosePlayer;

use SOFe\ChoosePlayer\libs\pmforms\dktapps\pmforms\MenuForm;
use SOFe\ChoosePlayer\libs\pmforms\dktapps\pmforms\MenuOption;
use Generator;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\AssumptionFailedError;
use SOFe\ChoosePlayer\libs\libasynql\poggit\libasynql\DataConnector;
use SOFe\ChoosePlayer\libs\libasynql\poggit\libasynql\libasynql;
use SOFe\ChoosePlayer\libs\await_generator\SOFe\AwaitGenerator\Await;
use SOFe\ChoosePlayer\libs\await_generator\SOFe\AwaitGenerator\Traverser;
use function array_map;
use function count;
use function crc32;
use function is_int;
use function time;
use function uasort;

final class Main extends PluginBase {
    private static ?self $instance = null;

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

    private DataConnector $db;
    private Queries $queries;
    private int $batchSize = 20;

    protected function onEnable() : void {
        self::$instance = $this;

        $this->saveDefaultConfig();
        $this->db = libasynql::create($this, [
            "type" => "sqlite",
            "sqlite" => [
                "file" => "data.sqlite",
            ],
        ], [
            "sqlite" => ["history.sql"],
        ]);
        $this->queries = new Queries($this->db);
        Await::g2c($this->queries->init());
        $this->db->waitAll();

        ChoosePlayer::suggest(new OnlinePlayerSuggester($this->getServer()));
    }

    protected function onDisable() : void {
        $this->db->close();
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

            $usageId = yield from $this->queries->recordUsage($who->getUniqueId()->toString(), time(), $suggester->getId());

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
                yield from $traverser->interrupt(new TerminateSuggestionsException);

                yield from $this->queries->recordSelectedUsage($usageId);
                return $result;
            }
        }
    }

    private function displayPlayerOptions(Player $player, Traverser $traverser, string $text) : Generator {
        $options = [];
        $suggestions = [];
        for ($i = 0; $i < $this->batchSize && yield from $traverser->next($suggestion); $i++) {
            /** @var Suggestion $suggestion */
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
     * @param list<MenuOption> $options
     * @return Generator<mixed, mixed, mixed, int|null>
     */
    private function asyncMenuForm(Player $player, string $title, string $text, array $options) : Generator {
        $ret = yield from Await::promise(function($resolve) use ($player, $title, $text, $options) {
            $form = new MenuForm($title, $text, $options, $resolve, fn() => $resolve(null));
            $player->sendForm($form);
        });
        if (!is_int($ret) || $ret < 0 || $ret >= count($options)) {
            return null;
        }
        return $ret;
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
        return $ret;
    }

    /**
     * @return Generator<mixed, mixed, mixed, Score>
     */
    private function scoreSuggesterFor(string $playerUuid, string $suggesterName) : Generator {
        $lastAcceptTime = (yield from $this->queries->timeSinceLastPersonalUse($playerUuid, time(), $suggesterName, 1))[0]["elapsed"];
        if ($lastAcceptTime !== null) {
            return new Score(Score::CLASS_PERSONAL_LAST_ACCEPT, $lastAcceptTime);
        }

        $lastUseTime = (yield from $this->queries->timeSinceLastPersonalUse($playerUuid, time(), $suggesterName, 0))[0]["elapsed"];
        if ($lastUseTime !== null) {
            return new Score(Score::CLASS_PERSONAL_LAST_USE, $lastUseTime);
        }

        $acceptCount = (yield from $this->queries->countUniqueUsageRate($suggesterName, 1))[0]["cnt"];
        if ($acceptCount !== null && $acceptCount > 0) {
            return new Score(Score::CLASS_PUBLIC_FREQUENT_ACCEPT, $acceptCount);
        }

        $useCount = (yield from $this->queries->countUniqueUsageRate($suggesterName, 0))[0]["cnt"];
        if ($useCount !== null && $useCount > 0) {
            return new Score(Score::CLASS_PUBLIC_FREQUENT_USE, $useCount);
        }

        return new Score(Score::CLASS_UNUSED, crc32($suggesterName));
    }
}
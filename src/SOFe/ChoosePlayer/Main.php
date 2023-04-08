<?php

declare(strict_types=1);

namespace SOFe\ChoosePlayer;

use Closure;
use SOFe\ChoosePlayer\libs\_25a4cd1bd990efb0\dktapps\pmforms\MenuOption;
use Generator;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\TextFormat;
use SOFe\ChoosePlayer\libs\_25a4cd1bd990efb0\poggit\libasynql\DataConnector;
use SOFe\ChoosePlayer\libs\_25a4cd1bd990efb0\poggit\libasynql\libasynql;
use SOFe\ChoosePlayer\libs\_25a4cd1bd990efb0\SOFe\AwaitGenerator\Await;
use SOFe\ChoosePlayer\libs\_25a4cd1bd990efb0\SOFe\AwaitGenerator\Traverser;
use function array_map;
use function crc32;
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
        Await::g2c(Await::all([
            $this->queries->historyInit(),
            $this->queries->playerLogInit(),
        ]));
        $this->db->waitAll();

        $this->batchSize = $this->getConfig()->get("pageSize", 20);

        if ($this->getConfig()->get("trackJoinLog", true)) {
            $this->getServer()->getPluginManager()->registerEvents(new JoinTracker($this->queries), $this);
        }

        ChoosePlayer::suggest(new OnlinePlayerSuggester($this->getServer()));
    }

    protected function onDisable() : void {
        $this->db->close();
    }

    /**
     * @internal This is not part of the public API.
     * @param ?Closure(Suggestion): bool $filter
     * @return Generator<mixed, mixed, mixed, ?ChoosePlayerResult>
     */
    public function chooseImpl(Player $who, string $text, ?Closure $filter) : Generator {
        while (true) {
            $suggesters = yield from $this->sortSuggestersFor($who->getUniqueId()->toString());

            $selection = yield from Util::asyncMenuForm($who, "Choose player by...", $text, array_map(function(Suggester $suggester) {
                return new MenuOption($suggester->getDisplayName());
            }, $suggesters));

            if ($selection === null) {
                return null; // cancelled
            }

            /** @var Suggester $suggester */
            $suggester = $suggesters[$selection];

            $usageId = yield from $this->queries->historyRecordUsage($who->getUniqueId()->toString(), time(), $suggester->getId());

            $gen = $suggester->suggest($who, new SuggesterOptions($this->batchSize));
            if ($gen === null) {
                continue; // retry
            }

            $traverser = new Traverser(self::wrapFilter($gen, $filter));
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

                yield from $this->queries->historyRecordSelectedUsage($usageId);
                return $result;
            }
        }
    }

    /**
     * @return Generator<mixed, mixed, mixed, DisplayPlayerOptionsResult>
     */
    private function displayPlayerOptions(Player $player, Traverser $traverser, string $text) : Generator {
        $options = [];
        $suggestions = [];
        for ($i = 0; $i < $this->batchSize && yield from $traverser->next($suggestion); $i++) {
            /** @var Suggestion $suggestion */
            $options[] = new MenuOption($suggestion->display . " " . TextFormat::ITALIC . TextFormat::GRAY . $suggestion->subtitle);
            $suggestions[] = $suggestion;
        }

        $options[] = new MenuOption("More options");

        $selected = yield from Util::asyncMenuForm($player, "Choose player", $text, $options);
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
        $lastAcceptTime = (yield from $this->queries->historyTimeSinceLastPersonalUse($playerUuid, time(), $suggesterName, 1))[0]["elapsed"];
        if ($lastAcceptTime !== null) {
            return new Score(Score::CLASS_PERSONAL_LAST_ACCEPT, $lastAcceptTime);
        }

        $lastUseTime = (yield from $this->queries->historyTimeSinceLastPersonalUse($playerUuid, time(), $suggesterName, 0))[0]["elapsed"];
        if ($lastUseTime !== null) {
            return new Score(Score::CLASS_PERSONAL_LAST_USE, $lastUseTime);
        }

        $acceptCount = (yield from $this->queries->historyCountUniqueUsageRate($suggesterName, 1))[0]["cnt"];
        if ($acceptCount !== null && $acceptCount > 0) {
            return new Score(Score::CLASS_PUBLIC_FREQUENT_ACCEPT, $acceptCount);
        }

        $useCount = (yield from $this->queries->historyCountUniqueUsageRate($suggesterName, 0))[0]["cnt"];
        if ($useCount !== null && $useCount > 0) {
            return new Score(Score::CLASS_PUBLIC_FREQUENT_USE, $useCount);
        }

        return new Score(Score::CLASS_UNUSED, crc32($suggesterName));
    }

    /**
     * @param Generator<Suggestion|mixed, mixed, mixed, void> $generator
     * @param ?Closure(Suggestion): bool $filter
     * @return Generator<Suggestion|mixed, mixed, mixed, void>
     */
    private static function wrapFilter(Generator $generator, ?Closure $filter) : Generator {
        $traverser = new Traverser($generator);
        while (yield from $traverser->next($suggestion)) {
            /** @var Suggestion $suggestion */
            if ($filter === null || $filter($suggestion)) {
                yield $suggestion => Traverser::VALUE;
            }
        }
    }
}
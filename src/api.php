<?php

declare(strict_types=1);

namespace SOFe\ChoosePlayer;

use Closure;
use Exception;
use Generator;
use pocketmine\player\Player;
use pocketmine\Server;
use RuntimeException;
use SOFe\AwaitGenerator\Await;
use SOFe\AwaitGenerator\Traverser;

final class ChoosePlayer {
    /**
     * Lets `$who` choose a player.
     * Returns a generator compatible with await-generator v2/v3.
     *
     * @param ?Closure(Suggestion): bool $filter filter player suggestions.
     * @param string $text The subtitle displayed in the MenuForm.
     * @return Generator<mixed, mixed, mixed, ?ChoosePlayerResult>
     */
    public static function choose(Player $who, ?Closure $filter = null, string $text = "") : Generator {
        $self = Main::getInstance();
        if ($self === null) {
            throw new RuntimeException("Cannot choose player when ChoosePlayer plugin is disabled");
        }

        return yield from $self->chooseImpl($who, $text, $filter);
    }

    /**
     * Lets `$who` choose a player.
     *
     * @param Closure(ChoosePlayerResult): void $then
     * @param Closure(): void $else
     */
    public static function chooseCallback(Player $who, Closure $then, Closure $else, ?Closure $filter = null, string $text = "") : void {
        Await::f2c(function() use ($who, $then, $else, $filter, $text) : Generator {
            $result = yield from self::choose($who, $filter, $text);
            if ($result !== null) {
                $then($result);
            } else {
                $else();
            }
        });
    }

    /**
     * Provides a suggester.
     */
    public static function suggest(Suggester $suggester) : void {
        $self = Main::getInstance();
        if ($self === null) {
            throw new RuntimeException("Declare ChoosePlayer as a depend/softdepend in plugin.yml");
        }

        $self->suggesters[$suggester->getId()] = $suggester;
    }
}

final class Filters {
    /**
     * Returns a filter that only suggest online players.
     *
     * @return Closure(Suggestion): bool
     */
    public static function onlinePlayer(Server $server) : Closure {
        return fn(Suggestion $suggestion) => $server->getPlayerByRawUUID($suggestion->uuid)?->isOnline() ?? false;
    }

    /**
     * Returns a filter that excludes a specific player.
     *
     * @return Closure(Suggestion): bool
     */
    public static function isnt(Player $whom) : Closure {
        $uuid = $whom->getUniqueId()->toString();
        return fn(Suggestion $suggestion) => $suggestion->uuid !== $uuid;
    }

    /**
     * Returns a filter that inverts another filter.
     *
     * @param Closure(Suggestion): bool $filter
     * @return Closure(Suggestion): bool
     */
    public static function not(Closure $filter) : Closure {
        return fn(Suggestion $suggestion) => !$filter($suggestion);
    }

    /**
     * Returns a filter that only accepts suggestions satisfying all given filters.
     *
     * @param Closure(Suggestion): bool ...$filters
     * @return Closure(Suggestion): bool
     */
    public static function all(Closure ...$filters) : Closure {
        return function(Suggestion $suggestion) use ($filters) : bool {
            foreach ($filters as $filter) {
                if (!$filter($suggestion)) {
                    return false;
                }
            }
            return true;
        };
    }

    /**
     * Returns a filter that accepts suggestions satisfying any of the given filters.
     *
     * @param Closure(Suggestion): bool ...$filters
     * @return Closure(Suggestion): bool
     */
    public static function any(Closure ...$filters) : Closure {
        return function(Suggestion $suggestion) use ($filters) : bool {
            foreach ($filters as $filter) {
                if ($filter($suggestion)) {
                    return true;
                }
            }
            return false;
        };
    }
}

interface Suggester {
    /**
     * @return string The identifier for the suggester, used for recording usage history.
     */
    public function getId() : string;

    public function getDisplayName() : string;

    public function testPermission(Player $who) : bool;

    /**
     * Returns an async iterator of suggestions.
     * Returns null if the operation is cancelled.
     *
     * @return ?Generator<Suggestion|mixed, mixed, mixed, void>
     * @see TerminateSuggestionsException
     */
    public function suggest(Player $who, SuggesterOptions $options) : ?Generator;
}

final class SuggesterOptions {
    /**
     * @internal The constructor is not part of the public API.
     */
    public function __construct(
        /** @var int $batchSize Number of entries displayed per page. */
        public int $batchSize,
    ) {
    }
}

final class Suggestion {
    /** The displayed name in dialog */
    public string $display;
    /** Additional description of the suggestion */
    public string $subtitle = "";

    public function __construct(
        /** The last known player name */
        public string $name,
        /** The last known player UUID */
        public string $uuid,
    ) {
        $this->display = $name;
    }

    public static function new(string $name, string $uuid) : self {
        return new self($name, $uuid);
    }

    public function setDisplay(string $display) : self {
        $this->display = $display;
        return $this;
    }

    public function setSubtitle(string $subtitle) : self {
        $this->subtitle = $subtitle;
        return $this;
    }
}

/**
 * Thrown to a `Suggester::choose` traverser when no more suggestions are required.
 */
final class TerminateSuggestionsException extends Exception {
    public function __construct() {
        parent::__construct("This exception should be caught by ChoosePlayer");
    }
}

final class ChoosePlayerResult {
    public function __construct(
        public string $name,
        public string $uuid,
    ) {
    }
}

/**
 * You can use await-generator static methods and constants from this alias
 * if you don't want to install the virion.
 *
 * @extends Await<mixed>
 */
class AwaitAlias extends Await {
    public const VALUE = Traverser::VALUE;
}

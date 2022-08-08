<?php

declare(strict_types=1);

namespace SOFe\ChoosePlayer;

use Closure;
use Exception;
use Generator;
use pocketmine\player\Player;
use RuntimeException;
use SOFe\AwaitGenerator\Await;

final class ChoosePlayer {
    /**
     * Lets `$who` choose a player.
     * Returns a generator compatible with await-generator v2/v3.
     *
     * @return Generator<mixed, mixed, mixed, ?ChoosePlayerResult>
     */
    public static function choose(Player $who) : Generator {
        $self = Main::getInstance();
        if ($self === null) {
            throw new RuntimeException("Cannot choose player when ChoosePlayer plugin is disabled");
        }

        return yield from $self->chooseImpl($who);
    }

    /**
     * Lets `$who` choose a player.
     *
     * @param Closure(ChoosePlayerResult): void $then
     * @param Closure(): void $else
     */
    public static function chooseCallback(Player $who, Closure $then, Closure $else) : void {
        Await::f2c(function() use ($who, $then, $else) : Generator {
            $result = yield from self::choose($who);
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

interface Suggester {
    /**
     * @return string The identifier for the suggester, used for recording usage history.
     */
    public function getId() : string;

    public function getDisplayName() : string;

    /**
     * Returns an async iterator of suggestions.
     * Returns null if the operation is cancelled.
     *
     * @return ?Generator<Suggestion|mixed, mixed, mixed, void>
     * @see TerminateSuggestionsException
     */
    public function suggest(Player $who) : ?Generator;
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
}

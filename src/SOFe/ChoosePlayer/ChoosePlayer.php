<?php

declare(strict_types=1);

namespace SOFe\ChoosePlayer;

use Closure;
use Exception;
use Generator;
use pocketmine\player\Player;
use RuntimeException;
use SOFe\ChoosePlayer\libs\await_generator\SOFe\AwaitGenerator\Await;

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
<?php

declare(strict_types=1);

namespace SOFe\ChoosePlayer;

use Closure;
use Exception;
use Generator;
use pocketmine\player\Player;
use pocketmine\Server;
use RuntimeException;
use SOFe\ChoosePlayer\libs\_8da02277f176ee7c\SOFe\AwaitGenerator\Await;
use SOFe\ChoosePlayer\libs\_8da02277f176ee7c\SOFe\AwaitGenerator\Traverser;

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
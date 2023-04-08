<?php

declare(strict_types=1);

namespace SOFe\ChoosePlayer;

use Closure;
use Exception;
use Generator;
use pocketmine\player\Player;
use pocketmine\Server;
use RuntimeException;
use SOFe\ChoosePlayer\libs\_922de0f6858a3004\SOFe\AwaitGenerator\Await;
use SOFe\ChoosePlayer\libs\_922de0f6858a3004\SOFe\AwaitGenerator\Traverser;

















































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
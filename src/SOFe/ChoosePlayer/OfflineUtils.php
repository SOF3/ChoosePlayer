<?php

declare(strict_types=1);

namespace SOFe\ChoosePlayer;

use SOFe\ChoosePlayer\libs\_8da02277f176ee7c\dktapps\pmforms\element;
use Generator;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\form\FormValidationException;
use pocketmine\player\Player;
use pocketmine\Server;
use SOFe\ChoosePlayer\libs\_8da02277f176ee7c\SOFe\AwaitGenerator\Await;
use SOFe\ChoosePlayer\libs\_8da02277f176ee7c\SOFe\AwaitGenerator\Traverser;

use function floor;
use function sprintf;
use function time;














































































































/**
 * @internal This is not part of the public API.
 */
final class OfflineUtils {
    /**
     * @param list<array<string, mixed>> $results
     * @return Generator<mixed|Suggestion, mixed, mixed, int>
     */
    public static function yieldResults(Server $server, array $results) : Generator {
        $count = 0;

        foreach ($results as $result) {
            $uuid = $result["uuid"];
            $name = $result["name"];

            $player = $server->getPlayerByRawUUID($uuid);
            if ($player !== null && $player->isOnline()) {
                // skip online players
                continue;
            }

            $duration = OfflineUtils::formatDuration(time() - $result["time"]);
            yield Suggestion::new($name, $uuid)->setSubtitle("Last seen $duration ago") => Traverser::VALUE;
            $count += 1;
        }

        return $count;
    }

    private const DURATION_UNITS = [
        ["w", 86400 * 7],
        ["d", 86400],
        ["h", 3600],
        ["m", 60],
    ];
    public static function formatDuration(float $duration) : string {
        foreach (self::DURATION_UNITS as [$name, $quantity]) {
            if ($duration >= $quantity * 2) {
                return sprintf("%d%s", (int) floor($duration / $quantity), $name);
            }
        }

        return sprintf("%ss", (int) floor($duration));
    }
}
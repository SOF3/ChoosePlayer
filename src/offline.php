<?php

declare(strict_types=1);

namespace SOFe\ChoosePlayer;

use dktapps\pmforms\element;
use Generator;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\form\FormValidationException;
use pocketmine\player\Player;
use pocketmine\Server;
use SOFe\AwaitGenerator\Await;
use SOFe\AwaitGenerator\Traverser;

use function floor;
use function sprintf;
use function time;

final class JoinTracker implements Listener {
    public function __construct(
        private Queries $queries,
    ) {
    }

    /**
     * @priority MONITOR
     */
    public function e_login(PlayerLoginEvent $event) : void {
        $player = $event->getPlayer();
        Await::g2c($this->queries->playerLogStore($player->getUniqueId()->toString(), $player->getName(), time()));
    }

    public function e_quit(PlayerQuitEvent $event) : void {
        $player = $event->getPlayer();
        Await::g2c($this->queries->playerLogStore($player->getUniqueId()->toString(), $player->getName(), time()));
    }
}

final class OfflinePlayerSuggester implements Suggester {
    public function __construct(
        private Server $server,
        private Queries $queries,
    ) {
    }

    public function getId() : string {
        return "ChoosePlayer/offlineByName";
    }

    public function getDisplayName() : string {
        return "Search offline player by name";
    }

    public function testPermission(Player $who) : bool {
        return $who->hasPermission(Permissions::CHOOSE_PLAYER_OFFLINE_SELECTOR);
    }

    public function suggest(Player $who, SuggesterOptions $options) : Generator {
        while (true) {
            try {
                $resp = yield from Util::asyncCustomForm($who, "Search offline player", [
                    new element\Input("name", "Player Name", "You only need to type part of the name"),
                ]);
            } catch (FormValidationException $e) {
                Server::getInstance()->getLogger()->error("Form validation error: " . $who->getName() . ": " . $e->getMessage());
                // invalid input!
                return;
            }

            if ($resp === null) {
                return;
            }

            $name = $resp->getString("name");

            if ($name !== "") {
                break;
            }
        }

        $page = 0;
        while (true) {
            $results = yield from $this->queries->playerLogSearch($name, $options->batchSize, $page);
            $page++;

            $count = yield from OfflineUtils::yieldResults($this->server, $results);
            if ($count === 0) {
                return;
            }
        }
    }
}

final class RecentPlayerSuggester implements Suggester {
    public function __construct(
        private Server $server,
        private Queries $queries,
    ) {
    }

    public function getId() : string {
        return "ChoosePlayer/recentOffline";
    }

    public function getDisplayName() : string {
        return "Recent offline players";
    }

    public function testPermission(Player $who) : bool {
        return $who->hasPermission(Permissions::CHOOSE_PLAYER_OFFLINE_SELECTOR);
    }

    public function suggest(Player $who, SuggesterOptions $options) : Generator {
        $page = 0;

        while (true) {
            $results = yield from $this->queries->playerLogRecent($options->batchSize, $page);
            $page += 1;

            $count = yield from OfflineUtils::yieldResults($this->server, $results);
            if ($count === 0) {
                return;
            }
        }
    }
}

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

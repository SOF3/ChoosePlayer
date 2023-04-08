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
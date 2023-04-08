<?php

declare(strict_types=1);

namespace SOFe\ChoosePlayer;

use Generator;
use pocketmine\player\Player;
use pocketmine\Server;
use SOFe\ChoosePlayer\libs\_782176bb0d2d1f37\SOFe\AwaitGenerator\Traverser;
use function strtolower;
use function usort;

final class OnlinePlayerSuggester implements Suggester {
    public function __construct(
        private Server $server,
    ) {
    }

    public function getId() : string {
        return "ChoosePlayer/online";
    }

    public function getDisplayName() : string {
        return "Online players";
    }

    public function testPermission(Player $who) : bool {
        return $who->hasPermission(Permissions::CHOOSE_PLAYER_ONLINE_SELECTOR);
    }

    public function suggest(Player $who, SuggesterOptions $options) : Generator {
        $players = $this->server->getOnlinePlayers();

        usort($players, fn(Player $p1, Player $p2) => strtolower($p1->getName()) <=> strtolower($p2->getName()));

        foreach ($players as $player) {
            if ($player === $who || !$player->isConnected()) {
                continue; // player already left the server, don't display them
            }

            yield new Suggestion($who->getName(), $who->getUniqueId()->toString()) => Traverser::VALUE;
        }
    }
}
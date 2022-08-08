<?php

declare(strict_types=1);

namespace SOFe\ChoosePlayer;

use Generator;
use pocketmine\player\Player;
use pocketmine\Server;
use SOFe\ChoosePlayer\libs\await_generator\SOFe\AwaitGenerator\Traverser;

final class OnlinePlayerSuggester implements Suggester {
    public function __construct(
        private Server $server,
    ) {
    }

    public function getId() : string {
        return "ChoosePlayer/online";
    }

    public function getDisplayName() : string {
        return "Select from online players";
    }

    public function suggest(Player $who) : Generator {
        $players = $this->server->getOnlinePlayers();

        foreach ($players as $player) {
            if ($player === $who || !$player->isConnected()) {
                continue; // player already left the server, don't display them
            }

            yield new Suggestion($who->getName(), $who->getUniqueId()->toString()) => Traverser::VALUE;
        }
    }
}
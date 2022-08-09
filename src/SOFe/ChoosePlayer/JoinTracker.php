<?php

declare(strict_types=1);

namespace SOFe\ChoosePlayer;

use SOFe\ChoosePlayer\libs\pmforms\dktapps\pmforms\element;
use Generator;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\form\FormValidationException;
use pocketmine\player\Player;
use pocketmine\Server;
use SOFe\ChoosePlayer\libs\await_generator\SOFe\AwaitGenerator\Await;
use SOFe\ChoosePlayer\libs\await_generator\SOFe\AwaitGenerator\Traverser;

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
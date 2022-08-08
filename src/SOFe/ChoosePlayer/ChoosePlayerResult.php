<?php

declare(strict_types=1);

namespace SOFe\ChoosePlayer;

use Closure;
use Exception;
use Generator;
use pocketmine\player\Player;
use RuntimeException;
use SOFe\ChoosePlayer\libs\await_generator\SOFe\AwaitGenerator\Await;




































































































final class ChoosePlayerResult {
    public function __construct(
        public string $name,
        public string $uuid,
    ) {
    }
}
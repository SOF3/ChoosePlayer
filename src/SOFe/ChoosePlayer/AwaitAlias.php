<?php

declare(strict_types=1);

namespace SOFe\ChoosePlayer;

use Closure;
use Exception;
use Generator;
use pocketmine\player\Player;
use pocketmine\Server;
use RuntimeException;
use SOFe\ChoosePlayer\libs\_25a4cd1bd990efb0\SOFe\AwaitGenerator\Await;
use SOFe\ChoosePlayer\libs\_25a4cd1bd990efb0\SOFe\AwaitGenerator\Traverser;
































































































































































































/**
 * You can use await-generator static methods and constants from this alias
 * if you don't want to install the virion.
 *
 * @extends Await<mixed>
 */
class AwaitAlias extends Await {
    public const VALUE = Traverser::VALUE;
}
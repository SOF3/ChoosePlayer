<?php

declare(strict_types=1);

namespace SOFe\ChoosePlayer;

use Closure;
use Exception;
use Generator;
use pocketmine\player\Player;
use pocketmine\Server;
use RuntimeException;
use SOFe\ChoosePlayer\libs\_2f01549ac286c22b\SOFe\AwaitGenerator\Await;
use SOFe\ChoosePlayer\libs\_2f01549ac286c22b\SOFe\AwaitGenerator\Traverser;















































































































































































/**
 * Thrown to a `Suggester::choose` traverser when no more suggestions are required.
 */
final class TerminateSuggestionsException extends Exception {
    public function __construct() {
        parent::__construct("This exception should be caught by ChoosePlayer");
    }
}
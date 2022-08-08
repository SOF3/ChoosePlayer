<?php

declare(strict_types=1);

namespace SOFe\ChoosePlayer;

use Closure;
use Exception;
use Generator;
use pocketmine\player\Player;
use RuntimeException;
use SOFe\ChoosePlayer\libs\await_generator\SOFe\AwaitGenerator\Await;



























































































/**
 * Thrown to a `Suggester::choose` traverser when no more suggestions are required.
 */
final class TerminateSuggestionsException extends Exception {
    public function __construct() {
        parent::__construct("This exception should be caught by ChoosePlayer");
    }
}
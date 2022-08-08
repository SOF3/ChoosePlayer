<?php

declare(strict_types=1);

namespace SOFe\ChoosePlayer;

use Closure;
use Exception;
use Generator;
use pocketmine\player\Player;
use RuntimeException;
use SOFe\ChoosePlayer\libs\await_generator\SOFe\AwaitGenerator\Await;















































interface Suggester {
    /**
     * @return string The identifier for the suggester, used for recording usage history.
     */
    public function getId() : string;

    public function getDisplayName() : string;

    /**
     * Returns an async iterator of suggestions.
     * Returns null if the operation is cancelled.
     *
     * @return ?Generator<Suggestion|mixed, mixed, mixed, void>
     * @see TerminateSuggestionsException
     */
    public function suggest(Player $who) : ?Generator;
}
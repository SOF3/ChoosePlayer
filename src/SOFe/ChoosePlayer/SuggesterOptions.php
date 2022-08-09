<?php

declare(strict_types=1);

namespace SOFe\ChoosePlayer;

use Closure;
use Exception;
use Generator;
use pocketmine\player\Player;
use pocketmine\Server;
use RuntimeException;
use SOFe\ChoosePlayer\libs\await_generator\SOFe\AwaitGenerator\Await;
use SOFe\ChoosePlayer\libs\await_generator\SOFe\AwaitGenerator\Traverser;






































































































































final class SuggesterOptions {
    /**
     * @internal The constructor is not part of the public API.
     */
    public function __construct(
        /** @var int $batchSize Number of entries displayed per page. */
        public int $batchSize,
    ) {
    }
}
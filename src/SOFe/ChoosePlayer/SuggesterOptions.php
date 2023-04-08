<?php

declare(strict_types=1);

namespace SOFe\ChoosePlayer;

use Closure;
use Exception;
use Generator;
use pocketmine\player\Player;
use pocketmine\Server;
use RuntimeException;
use SOFe\ChoosePlayer\libs\_524de3f98c72a58e\SOFe\AwaitGenerator\Await;
use SOFe\ChoosePlayer\libs\_524de3f98c72a58e\SOFe\AwaitGenerator\Traverser;






































































































































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
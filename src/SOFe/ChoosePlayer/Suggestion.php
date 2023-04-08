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

















































































































































final class Suggestion {
    /** The displayed name in dialog */
    public string $display;
    /** Additional description of the suggestion */
    public string $subtitle = "";

    public function __construct(
        /** The last known player name */
        public string $name,
        /** The last known player UUID */
        public string $uuid,
    ) {
        $this->display = $name;
    }

    public static function new(string $name, string $uuid) : self {
        return new self($name, $uuid);
    }

    public function setDisplay(string $display) : self {
        $this->display = $display;
        return $this;
    }

    public function setSubtitle(string $subtitle) : self {
        $this->subtitle = $subtitle;
        return $this;
    }
}
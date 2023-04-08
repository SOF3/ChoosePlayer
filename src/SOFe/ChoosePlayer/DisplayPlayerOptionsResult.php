<?php

declare(strict_types=1);

namespace SOFe\ChoosePlayer;

use Closure;
use SOFe\ChoosePlayer\libs\_782176bb0d2d1f37\dktapps\pmforms\MenuOption;
use Generator;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\TextFormat;
use SOFe\ChoosePlayer\libs\_782176bb0d2d1f37\poggit\libasynql\DataConnector;
use SOFe\ChoosePlayer\libs\_782176bb0d2d1f37\poggit\libasynql\libasynql;
use SOFe\ChoosePlayer\libs\_782176bb0d2d1f37\SOFe\AwaitGenerator\Await;
use SOFe\ChoosePlayer\libs\_782176bb0d2d1f37\SOFe\AwaitGenerator\Traverser;
use function array_map;
use function crc32;
use function time;
use function uasort;




























































































































































































































final class DisplayPlayerOptionsResult {
    public const SELECTED = 0;
    public const CANCELLED = 1;
    public const NEXT_PAGE = 2;

    public function __construct(
        public int $type,
        public ?ChoosePlayerResult $result,
    ) {
    }
}
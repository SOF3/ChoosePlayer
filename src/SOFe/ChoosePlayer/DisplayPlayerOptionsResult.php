<?php

declare(strict_types=1);

namespace SOFe\ChoosePlayer;

use SOFe\ChoosePlayer\libs\pmforms\dktapps\pmforms\MenuForm;
use SOFe\ChoosePlayer\libs\pmforms\dktapps\pmforms\MenuOption;
use Generator;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\AssumptionFailedError;
use SOFe\ChoosePlayer\libs\libasynql\poggit\libasynql\DataConnector;
use SOFe\ChoosePlayer\libs\libasynql\poggit\libasynql\libasynql;
use SOFe\ChoosePlayer\libs\await_generator\SOFe\AwaitGenerator\Await;
use SOFe\ChoosePlayer\libs\await_generator\SOFe\AwaitGenerator\Traverser;
use function array_map;
use function count;
use function crc32;
use function is_int;
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
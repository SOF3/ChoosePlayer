<?php

declare(strict_types=1);

namespace SOFe\ChoosePlayer;

use SOFe\ChoosePlayer\libs\_524de3f98c72a58e\dktapps\pmforms\element;
use Generator;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\form\FormValidationException;
use pocketmine\player\Player;
use pocketmine\Server;
use SOFe\ChoosePlayer\libs\_524de3f98c72a58e\SOFe\AwaitGenerator\Await;
use SOFe\ChoosePlayer\libs\_524de3f98c72a58e\SOFe\AwaitGenerator\Traverser;

use function floor;
use function sprintf;
use function time;












































































final class RecentPlayerSuggester implements Suggester {
    public function __construct(
        private Server $server,
        private Queries $queries,
    ) {
    }

    public function getId() : string {
        return "ChoosePlayer/recentOffline";
    }

    public function getDisplayName() : string {
        return "Recent offline players";
    }

    public function testPermission(Player $who) : bool {
        return $who->hasPermission(Permissions::CHOOSE_PLAYER_OFFLINE_SELECTOR);
    }

    public function suggest(Player $who, SuggesterOptions $options) : Generator {
        $page = 0;

        while (true) {
            $results = yield from $this->queries->playerLogRecent($options->batchSize, $page);
            $page += 1;

            $count = yield from OfflineUtils::yieldResults($this->server, $results);
            if ($count === 0) {
                return;
            }
        }
    }
}
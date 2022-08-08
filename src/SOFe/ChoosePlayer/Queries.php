<?php

/*
 * Auto-generated by libasynql-fx
 * Created from config.yml, history.sql
 */

declare(strict_types=1);

namespace SOFe\ChoosePlayer;

use Generator;
use SOFe\ChoosePlayer\libs\libasynql\poggit\libasynql\DataConnector;
use SOFe\ChoosePlayer\libs\await_generator\SOFe\AwaitGenerator\Await;

final class Queries{
	public function __construct(private DataConnector $conn) {}

	/**
	 * <h4>Declared in:</h4>
	 * - resources/history.sql:41
	 * @param string $suggester
	 * @param int $accepted
	 * @return Generator<mixed, 'all'|'once'|'race'|'reject'|'resolve'|array{'resolve'}|Generator<mixed, mixed, mixed, mixed>|null, mixed, list<array<string, mixed>>>
	 */
	public function countUniqueUsageRate(string $suggester, int $accepted, ) : Generator {
		$this->conn->executeSelect("count-unique-usage-rate", ["suggester" => $suggester, "accepted" => $accepted, ], yield Await::RESOLVE, yield Await::REJECT);
		return yield Await::ONCE;
	}

	/**
	 * <h4>Declared in:</h4>
	 * - resources/history.sql:16
	 * @return Generator<mixed, 'all'|'once'|'race'|'reject'|'resolve'|array{'resolve'}|Generator<mixed, mixed, mixed, mixed>|null, mixed, int>
	 */
	public function init() : Generator {
		$this->conn->executeChange("init", [], yield Await::RESOLVE, yield Await::REJECT);
		return yield Await::ONCE;
	}

	/**
	 * <h4>Declared in:</h4>
	 * - resources/history.sql:27
	 * @param int $pk
	 * @return Generator<mixed, 'all'|'once'|'race'|'reject'|'resolve'|array{'resolve'}|Generator<mixed, mixed, mixed, mixed>|null, mixed, int>
	 */
	public function recordSelectedUsage(int $pk, ) : Generator {
		$this->conn->executeChange("record-selected-usage", ["pk" => $pk, ], yield Await::RESOLVE, yield Await::REJECT);
		return yield Await::ONCE;
	}

	/**
	 * <h4>Declared in:</h4>
	 * - resources/history.sql:23
	 * @param string $player
	 * @param int $now
	 * @param string $suggester
	 * @return Generator<mixed, 'all'|'once'|'race'|'reject'|'resolve'|array{'resolve'}|Generator<mixed, mixed, mixed, mixed>|null, mixed, int>
	 */
	public function recordUsage(string $player, int $now, string $suggester, ) : Generator {
		$this->conn->executeInsert("record-usage", ["player" => $player, "now" => $now, "suggester" => $suggester, ], yield Await::RESOLVE, yield Await::REJECT);
		return yield Await::ONCE;
	}

	/**
	 * <h4>Declared in:</h4>
	 * - resources/history.sql:35
	 * @param string $player
	 * @param int $now
	 * @param string $suggester
	 * @param int $accepted
	 * @return Generator<mixed, 'all'|'once'|'race'|'reject'|'resolve'|array{'resolve'}|Generator<mixed, mixed, mixed, mixed>|null, mixed, list<array<string, mixed>>>
	 */
	public function timeSinceLastPersonalUse(string $player, int $now, string $suggester, int $accepted, ) : Generator {
		$this->conn->executeSelect("time-since-last-personal-use", ["player" => $player, "now" => $now, "suggester" => $suggester, "accepted" => $accepted, ], yield Await::RESOLVE, yield Await::REJECT);
		return yield Await::ONCE;
	}
}
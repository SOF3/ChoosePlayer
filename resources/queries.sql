-- #!sqlite
-- #{ init
CREATE TABLE IF NOT EXISTS history (
	pk INT PRIMARY KEY AUTOINCREMENT,
	player TEXT,
	time INT,
	suggester TEXT,
	accepted INT
);
-- #&
CREATE INDEX IF NOT EXISTS history_by_player_time
ON history (player, time, accepted);
-- #&
CREATE INDEX IF NOT EXISTS history_by_suggester
ON history (suggester, player);
-- #}
-- #{ record-usage
-- #  :player string
-- #  :now int
-- #  :suggester string
INSERT INTO history (player, time, suggester, accepted)
VALUES (:player, :now, :suggester, 0);
-- #}
-- #{ record-selected-usage
-- #  :pk int
UPDATE history SET accepted = 1 WHERE pk = :pk;
-- #}
-- #{ time-since-last-personal-use
-- #  :player string
-- #  :now int
-- #  :suggester string
-- #  :accepted int
SELECT MIN(:now - time) AS elapsed FROM history
WHERE player = :player AND suggester = :suggester AND accepted = :accepted;
-- #}
-- #{ count-unique-usage-rate
-- #  :suggester string
-- #  :accepted iint
SELECT COUNT(DISTINCT player) FROM history
WHERE suggester = :suggester AND accepted = :accepted;
-- #}

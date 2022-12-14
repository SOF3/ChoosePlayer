-- #!sqlite
-- #{ history
-- #    { init
CREATE TABLE IF NOT EXISTS history (
    pk INT PRIMARY KEY AUTOINCREMENT,
    player TEXT,
    time INT,
    suggester TEXT,
    accepted INT
);
-- #&
CREATE INDEX IF NOT EXISTS history_for_last_personal_use
ON history (player, suggester, accepted, time);
-- #&
CREATE INDEX IF NOT EXISTS history_for_unique_usage_count
ON history (suggester, accepted, player);
-- #    }
-- #    { record-usage
-- #        :player string
-- #        :now int
-- #        :suggester string
INSERT INTO history (player, time, suggester, accepted)
VALUES (:player, :now, :suggester, 0);
-- #    }
-- #    { record-selected-usage
-- #        :pk int
UPDATE history SET accepted = 1 WHERE pk = :pk;
-- #    }
-- #    { time-since-last-personal-use
-- #        :player string
-- #        :now int
-- #        :suggester string
-- #        :accepted int
SELECT MIN(:now - time) AS elapsed FROM history
WHERE player = :player AND suggester = :suggester AND accepted = :accepted;
-- #    }
-- #    { count-unique-usage-rate
-- #        :suggester string
-- #        :accepted int
SELECT COUNT(DISTINCT player) AS cnt FROM history
WHERE suggester = :suggester AND accepted = :accepted;
-- #    }
-- #}

-- #!sqlite
-- #{ player_log
-- #    { init
CREATE TABLE IF NOT EXISTS player_log (
    uuid TEXT PRIMARY KEY,
    name TEXT COLLATE NOCASE,
    time INT
);
-- #&
CREATE INDEX IF NOT EXISTS player_log_by_name
ON player_log (name);
-- #    }
-- #    { store
-- #        :uuid string
-- #        :name string
-- #        :now int
INSERT OR REPLACE INTO player_log (uuid, name, time)
VALUES (:uuid, :name, :now);
-- #    }
-- #    { recent
-- #        :pageSize int
-- #        :page int
SELECT uuid, name, time FROM player_log
ORDER BY time DESC
LIMIT :pageSize OFFSET :page * :pageSize;
-- #    }
-- #    { search
-- #        :substring string
-- #        :pageSize int
-- #        :page int
SELECT uuid, name, time FROM player_log
WHERE INSTR(name, :substring)
ORDER BY INSTR(name, :substring) ASC, time DESC
LIMIT :pageSize OFFSET :page * :pageSize;
-- #    }
-- #}

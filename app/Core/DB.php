<?php namespace Core;

class DB {
    private static ?\PDO $pdo = null;

    public static function get(): \PDO {
        if (self::$pdo) return self::$pdo;
        $dir = dirname(DB_FILE);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        self::$pdo = new \PDO('sqlite:' . DB_FILE);
        self::$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        self::$pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        self::$pdo->exec('PRAGMA journal_mode=WAL; PRAGMA foreign_keys=ON;');
        self::migrate();
        return self::$pdo;
    }

    private static function migrate(): void {
        self::$pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                username   TEXT    NOT NULL UNIQUE,
                name       TEXT    NOT NULL,
                password   TEXT    NOT NULL DEFAULT '',
                role       TEXT    NOT NULL DEFAULT 'user',
                virtual    INTEGER NOT NULL DEFAULT 0,
                active     INTEGER NOT NULL DEFAULT 1,
                created_at TEXT    NOT NULL DEFAULT (datetime('now'))
            );

            CREATE TABLE IF NOT EXISTS teams (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                name       TEXT    NOT NULL,
                owner_id   INTEGER NOT NULL,
                created_at TEXT    NOT NULL DEFAULT (datetime('now')),
                FOREIGN KEY(owner_id) REFERENCES users(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS team_members (
                id        INTEGER PRIMARY KEY AUTOINCREMENT,
                team_id   INTEGER NOT NULL,
                user_id   INTEGER NOT NULL,
                UNIQUE(team_id, user_id),
                FOREIGN KEY(team_id) REFERENCES teams(id) ON DELETE CASCADE,
                FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS days (
                id      INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                date    TEXT    NOT NULL,
                type    TEXT    NOT NULL,
                UNIQUE(user_id, date),
                FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS notes (
                id      INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                date    TEXT    NOT NULL,
                content TEXT    NOT NULL,
                UNIQUE(user_id, date),
                FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS user_config (
                id      INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                key     TEXT    NOT NULL,
                value   TEXT    NOT NULL,
                UNIQUE(user_id, key),
                FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS delegations (
                id        INTEGER PRIMARY KEY AUTOINCREMENT,
                owner_id  INTEGER NOT NULL,
                viewer_id INTEGER NOT NULL,
                UNIQUE(owner_id, viewer_id),
                FOREIGN KEY(owner_id)  REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY(viewer_id) REFERENCES users(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS archives (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                year       INTEGER NOT NULL,
                user_id    INTEGER,
                team_id    INTEGER,
                label      TEXT    NOT NULL,
                filename   TEXT    NOT NULL,
                created_at TEXT    NOT NULL DEFAULT (datetime('now')),
                created_by INTEGER NOT NULL,
                FOREIGN KEY(user_id)    REFERENCES users(id) ON DELETE SET NULL,
                FOREIGN KEY(team_id)    REFERENCES teams(id) ON DELETE SET NULL,
                FOREIGN KEY(created_by) REFERENCES users(id)
            );
        ");

        // Migrations incrémentales (BDD existante)
        $cols = ['virtual INTEGER NOT NULL DEFAULT 0'];
        foreach ($cols as $col) {
            try { self::$pdo->exec("ALTER TABLE users ADD COLUMN $col"); } catch (\Throwable) {}
        }
        try { self::$pdo->exec("ALTER TABLE archives ADD COLUMN team_id INTEGER"); } catch (\Throwable) {}

        // Admin par défaut
        $count = self::$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        if ($count == 0) {
            $hash = password_hash('admin123', PASSWORD_DEFAULT);
            self::$pdo->prepare("INSERT INTO users (username,name,password,role) VALUES ('admin','Administrateur',?,'admin')")->execute([$hash]);
        }
    }

    public static function query(string $sql, array $params = []): \PDOStatement {
        $stmt = self::get()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function fetchAll(string $sql, array $p = []): array { return self::query($sql,$p)->fetchAll(); }
    public static function fetchOne(string $sql, array $p = []): ?array { return self::query($sql,$p)->fetch() ?: null; }
    public static function lastId(): string { return self::get()->lastInsertId(); }
}

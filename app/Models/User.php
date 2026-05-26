<?php namespace Models;
use Core\DB;

class User {

    public static function all(bool $includeVirtual = true): array {
        $sql = "SELECT id,username,name,role,virtual,active,created_at FROM users";
        if (!$includeVirtual) $sql .= " WHERE virtual=0";
        return DB::fetchAll($sql . " ORDER BY name");
    }

    /** Tous les comptes virtuels avec leur équipe */
    public static function allVirtual(): array {
        return DB::fetchAll("
            SELECT u.id, u.name, u.virtual, u.created_at,
                   t.name as team_name, t.id as team_id, ow.name as owner_name
            FROM users u
            LEFT JOIN team_members tm ON tm.user_id = u.id
            LEFT JOIN teams t ON t.id = tm.team_id
            LEFT JOIN users ow ON ow.id = t.owner_id
            WHERE u.virtual = 1
            ORDER BY t.name, u.name
        ");
    }

    public static function find(int $id): ?array {
        return DB::fetchOne("SELECT * FROM users WHERE id=?", [$id]);
    }

    public static function findByUsername(string $u): ?array {
        return DB::fetchOne("SELECT * FROM users WHERE username=? AND virtual=0", [$u]);
    }

    /** Crée un utilisateur réel */
    public static function create(string $username, string $name, string $password, string $role='user'): int {
        DB::query(
            "INSERT INTO users (username,name,password,role,virtual) VALUES (?,?,?,?,0)",
            [$username, $name, password_hash($password, PASSWORD_DEFAULT), $role]
        );
        return (int)DB::lastId();
    }

    /** Crée un compte virtuel (pas de login) — username auto-généré */
    public static function createVirtual(string $name): int {
        $slug = 'virtual_' . strtolower(preg_replace('/\s+/','_', $name)) . '_' . uniqid();
        DB::query(
            "INSERT INTO users (username,name,password,role,virtual) VALUES (?,?,'',?,1)",
            [$slug, $name, 'user']
        );
        return (int)DB::lastId();
    }

    /** Colonnes autorisées pour UPDATE — empêche l'injection via clé */
    private static array $ALLOWED_COLS = ['name','role','active','password','username'];

    public static function update(int $id, array $fields): void {
        $safe = array_filter($fields, fn($k) => in_array($k, self::$ALLOWED_COLS, true), ARRAY_FILTER_USE_KEY);
        if (empty($safe)) return;
        $sets = implode(',', array_map(fn($k) => "$k=?", array_keys($safe)));
        DB::query("UPDATE users SET $sets WHERE id=?", [...array_values($safe), $id]);
    }

    public static function delete(int $id): void {
        DB::query("DELETE FROM users WHERE id=?", [$id]);
    }

    public static function verify(string $username, string $password): ?array {
        $user = DB::fetchOne("SELECT * FROM users WHERE username=? AND virtual=0 AND active=1", [$username]);
        if ($user && password_verify($password, $user['password'])) return $user;
        return null;
    }

    /** Utilisateurs que $viewerId peut voir via délégations */
    public static function accessibleBy(int $viewerId): array {
        return DB::fetchAll("
            SELECT u.id, u.name, u.username, u.virtual
            FROM users u
            JOIN delegations d ON d.owner_id = u.id
            WHERE d.viewer_id = ?
            ORDER BY u.name
        ", [$viewerId]);
    }

    public static function canView(int $viewerId, int $ownerId): bool {
        if ($viewerId === $ownerId) return true;
        return DB::fetchOne(
            "SELECT id FROM delegations WHERE owner_id=? AND viewer_id=?",
            [$ownerId, $viewerId]
        ) !== null;
    }
}

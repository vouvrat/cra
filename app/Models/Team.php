<?php namespace Models;
use Core\DB;

class Team {

    public static function all(): array {
        return DB::fetchAll("
            SELECT t.*, u.name as owner_name, u.username as owner_username,
                   COUNT(tm.id) as member_count
            FROM teams t
            JOIN users u ON u.id = t.owner_id
            LEFT JOIN team_members tm ON tm.team_id = t.id
            GROUP BY t.id
            ORDER BY t.name
        ");
    }

    public static function find(int $id): ?array {
        return DB::fetchOne("
            SELECT t.*, u.name as owner_name
            FROM teams t JOIN users u ON u.id = t.owner_id
            WHERE t.id = ?
        ", [$id]);
    }

    /** Équipes dont l'utilisateur est responsable */
    public static function ownedBy(int $ownerId): array {
        return DB::fetchAll("
            SELECT t.*, COUNT(tm.id) as member_count
            FROM teams t
            LEFT JOIN team_members tm ON tm.team_id = t.id
            WHERE t.owner_id = ?
            GROUP BY t.id
            ORDER BY t.name
        ", [$ownerId]);
    }

    public static function members(int $teamId): array {
        return DB::fetchAll("
            SELECT u.id, u.name, u.username, u.virtual, u.active
            FROM team_members tm
            JOIN users u ON u.id = tm.user_id
            WHERE tm.team_id = ?
            ORDER BY u.name
        ", [$teamId]);
    }

    public static function create(string $name, int $ownerId): int {
        DB::query("INSERT INTO teams (name, owner_id) VALUES (?,?)", [$name, $ownerId]);
        return (int)DB::lastId();
    }

    public static function update(int $id, string $name, int $ownerId): void {
        DB::query("UPDATE teams SET name=?, owner_id=? WHERE id=?", [$name, $ownerId, $id]);
    }

    public static function delete(int $id): void {
        DB::query("DELETE FROM teams WHERE id=?", [$id]);
    }

    public static function addMember(int $teamId, int $userId): void {
        DB::query("INSERT OR IGNORE INTO team_members (team_id,user_id) VALUES (?,?)", [$teamId, $userId]);
    }

    public static function removeMember(int $teamId, int $userId): void {
        DB::query("DELETE FROM team_members WHERE team_id=? AND user_id=?", [$teamId, $userId]);
    }

    /** Est-ce que $userId est responsable d'une équipe contenant $memberId ? */
    public static function isManagerOf(int $userId, int $memberId): bool {
        $row = DB::fetchOne("
            SELECT tm.id FROM team_members tm
            JOIN teams t ON t.id = tm.team_id
            WHERE t.owner_id = ? AND tm.user_id = ?
        ", [$userId, $memberId]);
        return $row !== null;
    }

    /** Tous les membres (dont virtuels) accessibles par un responsable */
    public static function managedUsers(int $ownerId): array {
        return DB::fetchAll("
            SELECT DISTINCT u.id, u.name, u.username, u.virtual, t.name as team_name, t.id as team_id
            FROM team_members tm
            JOIN users u ON u.id = tm.user_id
            JOIN teams t ON t.id = tm.team_id
            WHERE t.owner_id = ?
            ORDER BY t.name, u.name
        ", [$ownerId]);
    }
}

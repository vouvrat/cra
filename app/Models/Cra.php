<?php namespace Models;
use Core\DB;

class Cra {

    // ── DAYS ─────────────────────────────────────────────────────────────────
    public static function getDays(int $userId, int $year): array {
        $rows = DB::fetchAll(
            "SELECT date, type FROM days WHERE user_id=? AND date LIKE ?",
            [$userId, "$year-%"]
        );
        return array_column($rows, 'type', 'date');
    }

    public static function setDay(int $userId, string $date, ?string $type): void {
        if ($type) {
            DB::query(
                "INSERT OR REPLACE INTO days (user_id,date,type) VALUES (?,?,?)",
                [$userId, $date, $type]
            );
        } else {
            DB::query("DELETE FROM days WHERE user_id=? AND date=?", [$userId, $date]);
        }
    }

    // ── NOTES ────────────────────────────────────────────────────────────────
    public static function getNotes(int $userId, int $year): array {
        $rows = DB::fetchAll(
            "SELECT date, content FROM notes WHERE user_id=? AND date LIKE ?",
            [$userId, "$year-%"]
        );
        return array_column($rows, 'content', 'date');
    }

    public static function setNote(int $userId, string $date, string $content): void {
        if (trim($content)) {
            DB::query(
                "INSERT OR REPLACE INTO notes (user_id,date,content) VALUES (?,?,?)",
                [$userId, $date, trim($content)]
            );
        } else {
            DB::query("DELETE FROM notes WHERE user_id=? AND date=?", [$userId, $date]);
        }
    }

    // ── CONFIG ───────────────────────────────────────────────────────────────
    public static function getConfig(int $userId): array {
        $rows = DB::fetchAll("SELECT key,value FROM user_config WHERE user_id=?", [$userId]);
        $cfg  = ['km'=>'40','duree'=>'60','indem'=>'0'];
        foreach ($rows as $r) $cfg[$r['key']] = $r['value'];
        return $cfg;
    }

    public static function setConfig(int $userId, string $key, string $value): void {
        DB::query(
            "INSERT OR REPLACE INTO user_config (user_id,key,value) VALUES (?,?,?)",
            [$userId, $key, $value]
        );
    }

    // ── STATS ────────────────────────────────────────────────────────────────
    public static function statsYear(int $userId, int $year): array {
        $rows = DB::fetchAll(
            "SELECT type, COUNT(*) as n FROM days WHERE user_id=? AND date LIKE ? GROUP BY type",
            [$userId, "$year-%"]
        );
        $s = ['p'=>0,'t'=>0,'r'=>0,'c'=>0,'f'=>0,'s'=>0];
        foreach ($rows as $r) $s[$r['type']] = (int)$r['n'];
        return $s;
    }

    public static function statsMonth(int $userId, int $year, int $month): array {
        $m   = str_pad($month, 2, '0', STR_PAD_LEFT);
        $rows = DB::fetchAll(
            "SELECT type, COUNT(*) as n FROM days WHERE user_id=? AND date LIKE ? GROUP BY type",
            [$userId, "$year-$m-%"]
        );
        $s = ['p'=>0,'t'=>0,'r'=>0,'c'=>0,'f'=>0,'s'=>0];
        foreach ($rows as $r) $s[$r['type']] = (int)$r['n'];
        return $s;
    }

    // ── ARCHIVES ─────────────────────────────────────────────────────────────
    public static function createArchive(int $year, ?int $userId, string $label, int $createdBy): int {
        if (!is_dir(ARCHIVES_DIR)) mkdir(ARCHIVES_DIR, 0755, true);

        // Collect data
        if ($userId) {
            $data = [
                'days'   => self::getDays($userId, $year),
                'notes'  => self::getNotes($userId, $year),
                'config' => self::getConfig($userId),
                'user'   => User::find($userId),
                'year'   => $year,
            ];
        } else {
            // Archive globale (tous les users)
            $users = User::all();
            $data  = ['year' => $year, 'users' => []];
            foreach ($users as $u) {
                $data['users'][] = [
                    'user'   => $u,
                    'days'   => self::getDays($u['id'], $year),
                    'notes'  => self::getNotes($u['id'], $year),
                    'config' => self::getConfig($u['id']),
                ];
            }
        }

        $filename = 'archive_' . $year . ($userId ? '_user'.$userId : '_all') . '_' . date('Ymd_His') . '.json';
        file_put_contents(ARCHIVES_DIR . '/' . $filename, json_encode($data, JSON_PRETTY_PRINT));

        DB::query(
            "INSERT INTO archives (year,user_id,label,filename,created_by) VALUES (?,?,?,?,?)",
            [$year, $userId, $label, $filename, $createdBy]
        );
        return (int)DB::lastId();
    }

    public static function allArchives(): array {
        return DB::fetchAll("
            SELECT a.*, u.name as user_name, cb.name as creator_name
            FROM archives a
            LEFT JOIN users u  ON u.id  = a.user_id
            JOIN users cb      ON cb.id = a.created_by
            ORDER BY a.created_at DESC
        ");
    }

    public static function findArchive(int $id): ?array {
        return DB::fetchOne("SELECT * FROM archives WHERE id=?", [$id]);
    }

    public static function deleteArchive(int $id): void {
        $a = self::findArchive($id);
        if ($a && file_exists(ARCHIVES_DIR.'/'.$a['filename'])) unlink(ARCHIVES_DIR.'/'.$a['filename']);
        DB::query("DELETE FROM archives WHERE id=?", [$id]);
    }

    // ── DELEGATIONS ──────────────────────────────────────────────────────────
    public static function allDelegations(): array {
        return DB::fetchAll("
            SELECT d.id, o.name as owner, v.name as viewer,
                   o.id as owner_id, v.id as viewer_id
            FROM delegations d
            JOIN users o ON o.id = d.owner_id
            JOIN users v ON v.id = d.viewer_id
            ORDER BY o.name, v.name
        ");
    }

    public static function addDelegation(int $ownerId, int $viewerId): void {
        DB::query(
            "INSERT OR IGNORE INTO delegations (owner_id,viewer_id) VALUES (?,?)",
            [$ownerId, $viewerId]
        );
    }

    public static function deleteDelegation(int $id): void {
        DB::query("DELETE FROM delegations WHERE id=?", [$id]);
    }

    // ── FÉRIÉS FR ────────────────────────────────────────────────────────────
    public static function feries(int $year): array {
        // Calcul Pâques (algorithme de Meeus/Jones/Butcher)
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day   = (($h + $l - 7 * $m + 114) % 31) + 1;
        $paques = new \DateTime("$year-$month-$day");

        $list = [];
        $fixed = [
            ['01','01'], ['05','01'], ['05','08'],
            ['07','14'], ['08','15'], ['11','01'],
            ['11','11'], ['12','25'],
        ];
        foreach ($fixed as [$m, $d]) $list[] = "$year-$m-$d";

        $offsets = [1, 39, 50]; // Lundi Pâques, Ascension, Pentecôte
        foreach ($offsets as $o) {
            $d = clone $paques;
            $d->modify("+$o days");
            $list[] = $d->format('Y-m-d');
        }
        sort($list);
        return $list;
    }
}

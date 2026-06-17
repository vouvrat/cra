<?php namespace Models;
use Core\DB;

class Cra {

    // ── DAYS ─────────────────────────────────────────────────────────────────

    /**
     * Retourne les jours sous la forme :
     *   'YYYY-MM-DD' => ['type'=>'p', 'am'=>null, 'pm'=>null]
     * type = journée complète, am/pm = demi-journées (null si non saisi)
     */
    public static function getDays(int $userId, int $year): array {
        $rows = DB::fetchAll(
            "SELECT date, type, type_am, type_pm FROM days WHERE user_id=? AND date LIKE ?",
            [$userId, "$year-%"]
        );
        $result = [];
        foreach ($rows as $r) {
            $result[$r['date']] = [
                'type' => $r['type'],
                'am'   => $r['type_am'],
                'pm'   => $r['type_pm'],
            ];
        }
        return $result;
    }

    /** Saisie journée complète */
    public static function setDay(int $userId, string $date, ?string $type): void {
        if ($type) {
            DB::query(
                "INSERT INTO days (user_id,date,type,type_am,type_pm) VALUES (?,?,?,NULL,NULL)
                 ON CONFLICT(user_id,date) DO UPDATE SET type=excluded.type, type_am=NULL, type_pm=NULL",
                [$userId, $date, $type]
            );
        } else {
            DB::query("DELETE FROM days WHERE user_id=? AND date=?", [$userId, $date]);
        }
    }

    /** Saisie demi-journée (am = matin, pm = après-midi) */
    public static function setHalfDay(int $userId, string $date, string $half, ?string $type): void {
        // Récupérer l'état actuel
        $existing = DB::fetchOne(
            "SELECT type, type_am, type_pm FROM days WHERE user_id=? AND date=?",
            [$userId, $date]
        );

        $col    = $half === 'am' ? 'type_am' : 'type_pm';
        $other  = $half === 'am' ? 'type_pm' : 'type_am';
        $curOther = $existing[$other] ?? null;

        if (!$type && !$curOther) {
            // Plus rien sur cette journée → supprimer
            DB::query("DELETE FROM days WHERE user_id=? AND date=?", [$userId, $date]);
            return;
        }

        // Déterminer le type "pleine journée" à stocker (pour compatibilité)
        // Si les deux demi-journées sont saisies, type = le plus représentatif (am)
        $fullType = $type ?? $curOther ?? ($existing['type'] ?? 'p');

        DB::query(
            "INSERT INTO days (user_id,date,type,type_am,type_pm) VALUES (?,?,?,?,?)
             ON CONFLICT(user_id,date) DO UPDATE SET
               type=excluded.type,
               $col=excluded.$col",
            [$userId, $date, $fullType,
             $half === 'am' ? $type : ($existing['type_am'] ?? null),
             $half === 'pm' ? $type : ($existing['type_pm'] ?? null)]
        );
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

    // ── CONFIG (ancienne API — retourne config active aujourd'hui) ───────────
    public static function getConfig(int $userId): array {
        return self::getConfigForDate($userId, date('Y-m-d'));
    }

    /** Retourne la config active pour une date donnée (YYYY-MM-DD) */
    public static function getConfigForDate(int $userId, string $date): array {
        $row = DB::fetchOne(
            "SELECT km, duree, indem FROM config_periods
             WHERE user_id = ?
               AND valid_from <= ?
               AND (valid_to IS NULL OR valid_to >= ?)
             ORDER BY valid_from DESC
             LIMIT 1",
            [$userId, $date, $date]
        );
        // Fallback sur l'ancienne table si aucune période trouvée
        if (!$row) {
            $rows = DB::fetchAll("SELECT key,value FROM user_config WHERE user_id=?", [$userId]);
            $cfg  = ['km'=>40,'duree'=>60,'indem'=>0];
            foreach ($rows as $r) $cfg[$r['key']] = (float)$r['value'];
            return $cfg;
        }
        return [
            'km'    => (float)$row['km'],
            'duree' => (float)$row['duree'],
            'indem' => (float)$row['indem'],
        ];
    }

    /** Retourne un tableau [mois => config] pour une année entière */
    public static function getConfigByMonth(int $userId, int $year): array {
        $result = [];
        for ($m = 1; $m <= 12; $m++) {
            $date = sprintf('%04d-%02d-15', $year, $m); // milieu du mois
            $result[$m] = self::getConfigForDate($userId, $date);
        }
        return $result;
    }

    /** Toutes les périodes de config d'un utilisateur */
    public static function getConfigPeriods(int $userId): array {
        return DB::fetchAll(
            "SELECT * FROM config_periods WHERE user_id=? ORDER BY valid_from DESC",
            [$userId]
        );
    }

    /**
     * Ajoute une nouvelle période de config.
     * Si $validTo est null, cette période devient la période "en cours" :
     * toute autre période encore ouverte (valid_to NULL) démarrée avant elle
     * est automatiquement clôturée à $validFrom - 1 jour.
     * Si $validTo est fourni, la période est insérée telle quelle, sans
     * toucher aux autres périodes — les bornes sont gérées manuellement.
     */
    public static function addConfigPeriod(
        int $userId, float $km, float $duree, float $indem,
        string $validFrom, ?string $validTo, string $label
    ): void {
        if ($validTo === null) {
            self::closeOpenPeriodsBefore($userId, $validFrom, null);
        }
        DB::query(
            "INSERT INTO config_periods (user_id,km,duree,indem,valid_from,valid_to,label) VALUES (?,?,?,?,?,?,?)",
            [$userId, $km, $duree, $indem, $validFrom, $validTo, $label]
        );
    }

    public static function deleteConfigPeriod(int $id, int $userId): void {
        // Vérifier que la période appartient bien à cet utilisateur
        $period = DB::fetchOne("SELECT * FROM config_periods WHERE id=? AND user_id=?", [$id, $userId]);
        if (!$period) return;

        // Si c'est la seule période, on ne supprime pas
        $count = DB::fetchOne("SELECT COUNT(*) as n FROM config_periods WHERE user_id=?", [$userId]);
        if ((int)$count['n'] <= 1) return;

        DB::query("DELETE FROM config_periods WHERE id=?", [$id]);

        // Si la période supprimée était la période "en cours" (sans date de fin),
        // rouvrir la plus récente des périodes restantes pour garder une
        // configuration active aujourd'hui. Les périodes aux bornes fixées
        // manuellement par l'utilisateur ne sont jamais touchées.
        if ($period['valid_to'] === null) {
            $prev = DB::fetchOne(
                "SELECT id FROM config_periods WHERE user_id=? ORDER BY valid_from DESC LIMIT 1",
                [$userId]
            );
            if ($prev) {
                DB::query("UPDATE config_periods SET valid_to=NULL WHERE id=?", [$prev['id']]);
            }
        }
    }

    /**
     * Met à jour une période existante avec des bornes explicites.
     * Si $validTo est null, cette période redevient la période "en cours" :
     * toute autre période encore ouverte démarrée avant elle est clôturée.
     */
    public static function updateConfigPeriod(int $id, int $userId, float $km, float $duree, float $indem, string $validFrom, ?string $validTo, string $label): void {
        DB::query(
            "UPDATE config_periods SET km=?,duree=?,indem=?,valid_from=?,valid_to=?,label=? WHERE id=? AND user_id=?",
            [$km, $duree, $indem, $validFrom, $validTo, $label, $id, $userId]
        );
        if ($validTo === null) {
            self::closeOpenPeriodsBefore($userId, $validFrom, $id);
        }
    }

    /**
     * Clôture toute période encore "en cours" (valid_to NULL) démarrée avant
     * $validFrom, pour qu'il n'y ait jamais deux périodes ouvertes en même
     * temps. $excludeId permet d'ignorer la période en cours d'insertion/édition.
     */
    private static function closeOpenPeriodsBefore(int $userId, string $validFrom, ?int $excludeId): void {
        $sql = "UPDATE config_periods
                SET valid_to = DATE(?, '-1 day')
                WHERE user_id = ? AND valid_to IS NULL AND valid_from < ?";
        $params = [$validFrom, $userId, $validFrom];
        if ($excludeId !== null) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        DB::query($sql, $params);
    }

    /** Ancienne API conservée pour compatibilité */
    public static function setConfig(int $userId, string $key, string $value): void {
        DB::query(
            "INSERT OR REPLACE INTO user_config (user_id,key,value) VALUES (?,?,?)",
            [$userId, $key, $value]
        );
    }

    // ── STATS ────────────────────────────────────────────────────────────────
    /** Calcule les stats en comptant les demi-journées comme 0.5 */
    private static function calcStats(array $rows): array {
        $s = ['p'=>0.0,'t'=>0.0,'r'=>0.0,'c'=>0.0,'f'=>0.0,'s'=>0.0];
        foreach ($rows as $r) {
            // Journée complète sans demi-journées
            if (!$r['type_am'] && !$r['type_pm']) {
                if (isset($s[$r['type']])) $s[$r['type']] += 1.0;
            } else {
                // Au moins une demi-journée saisie
                if ($r['type_am'] && isset($s[$r['type_am']])) $s[$r['type_am']] += 0.5;
                if ($r['type_pm'] && isset($s[$r['type_pm']])) $s[$r['type_pm']] += 0.5;
                // Si seulement am ou pm → l'autre moitié = journée complète
                if ($r['type_am'] && !$r['type_pm'] && isset($s[$r['type']])) $s[$r['type']] += 0.5;
                if ($r['type_pm'] && !$r['type_am'] && isset($s[$r['type']])) $s[$r['type']] += 0.5;
            }
        }
        return $s;
    }

    public static function statsYear(int $userId, int $year): array {
        $rows = DB::fetchAll(
            "SELECT type, type_am, type_pm FROM days WHERE user_id=? AND date LIKE ?",
            [$userId, "$year-%"]
        );
        return self::calcStats($rows);
    }

    public static function statsMonth(int $userId, int $year, int $month): array {
        $m = str_pad($month, 2, '0', STR_PAD_LEFT);
        $rows = DB::fetchAll(
            "SELECT type, type_am, type_pm FROM days WHERE user_id=? AND date LIKE ?",
            [$userId, "$year-$m-%"]
        );
        return self::calcStats($rows);
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

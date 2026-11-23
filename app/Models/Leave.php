<?php namespace Models;
use Core\DB;

/**
 * Soldes RTT / congés payés.
 *
 * Exercice = du 1er juin de l'année Y au 31 mai de l'année Y+1. On l'identifie par Y
 * (exercice 2026 = 01/06/2026 → 31/05/2027).
 *
 * Ce qui est SAISI  : droits acquis + report (table leave_balances).
 * Ce qui est CALCULÉ : jours consommés, à partir des jours déjà saisis dans le CRA
 *                      (type 'r' ou 'c', demi-journée = 0,5). Pas de double saisie.
 */
class Leave {
    public const TYPES = ['r' => 'RTT', 'c' => 'Congés payés'];

    /** Exercice qui contient une date (YYYY-MM-DD). */
    public static function periodOf(string $date): int {
        $y = (int)substr($date, 0, 4);
        $m = (int)substr($date, 5, 2);
        return $m >= 6 ? $y : $y - 1;
    }

    /** [début, fin] de l'exercice Y. */
    public static function bounds(int $y): array {
        return [sprintf('%04d-06-01', $y), sprintf('%04d-05-31', $y + 1)];
    }

    public static function label(int $y): string {
        return $y . '-' . ($y + 1);
    }

    /** Nombre de jours de type $type dans une ligne de la table days (même règle que Cra::calcStats). */
    private static function units(array $r, string $type): float {
        $n = 0.0;
        if (!$r['type_am'] && !$r['type_pm']) {
            return $r['type'] === $type ? 1.0 : 0.0;
        }
        if ($r['type_am'] === $type) $n += 0.5;
        if ($r['type_pm'] === $type) $n += 0.5;
        if ($r['type_am'] && !$r['type_pm'] && $r['type'] === $type) $n += 0.5;
        if ($r['type_pm'] && !$r['type_am'] && $r['type'] === $type) $n += 0.5;
        return $n;
    }

    /** Jours pris (date <= aujourd'hui) et posés (date > aujourd'hui) sur l'exercice. */
    public static function consumed(int $userId, string $type, int $y, ?string $today = null): array {
        $today ??= date('Y-m-d');
        [$from, $to] = self::bounds($y);
        $rows = DB::fetchAll(
            "SELECT date, type, type_am, type_pm FROM days WHERE user_id=? AND date BETWEEN ? AND ?",
            [$userId, $from, $to]
        );
        $taken = 0.0; $planned = 0.0;
        foreach ($rows as $r) {
            $u = self::units($r, $type);
            if ($u <= 0) continue;
            if ($r['date'] <= $today) $taken += $u; else $planned += $u;
        }
        return ['taken' => $taken, 'planned' => $planned];
    }

    public static function getRow(int $userId, string $type, int $y): ?array {
        return DB::fetchOne(
            "SELECT acquis, report FROM leave_balances WHERE user_id=? AND type=? AND period=?",
            [$userId, $type, $y]
        );
    }

    /** Solde de fin d'exercice, utilisé pour proposer le report vers l'exercice suivant. */
    public static function closingBalance(int $userId, string $type, int $y): float {
        $row = self::getRow($userId, $type, $y);
        if (!$row) return 0.0;
        $c = self::consumed($userId, $type, $y);
        return (float)$row['acquis'] + (float)$row['report'] - $c['taken'] - $c['planned'];
    }

    /** Tout ce qu'il faut pour afficher un type sur un exercice. */
    public static function summary(int $userId, string $type, int $y): array {
        $row  = self::getRow($userId, $type, $y);
        $c    = self::consumed($userId, $type, $y);
        $acq  = (float)($row['acquis'] ?? 0);
        $rep  = (float)($row['report'] ?? 0);
        $prev = self::getRow($userId, $type, $y - 1) ? self::closingBalance($userId, $type, $y - 1) : null;
        return [
            'type'       => $type,
            'label'      => self::TYPES[$type],
            'period'     => $y,
            'configured' => $row !== null,
            'acquis'     => $acq,
            'report'     => $rep,
            'suggested'  => $prev,                       // solde restant de l'exercice précédent (null si inconnu)
            'taken'      => $c['taken'],
            'planned'    => $c['planned'],
            'now'        => $acq + $rep - $c['taken'],   // solde à ce jour
            'final'      => $acq + $rep - $c['taken'] - $c['planned'], // solde si tous les jours posés sont pris
        ];
    }

    public static function save(int $userId, string $type, int $y, float $acquis, float $report): void {
        DB::query(
            "INSERT INTO leave_balances (user_id,type,period,acquis,report) VALUES (?,?,?,?,?)
             ON CONFLICT(user_id,type,period) DO UPDATE SET acquis=excluded.acquis, report=excluded.report",
            [$userId, $type, $y, $acquis, $report]
        );
    }

    /** Exercices ayant des droits saisis, du plus récent au plus ancien. */
    public static function periodsWithData(int $userId): array {
        $rows = DB::fetchAll("SELECT DISTINCT period FROM leave_balances WHERE user_id=? ORDER BY period DESC", [$userId]);
        return array_map('intval', array_column($rows, 'period'));
    }
}

<?php namespace Controllers;
use Core\Controller;
use Models\{Cra, User, Leave};

class CraController extends Controller {

    // ── HELPERS ──────────────────────────────────────────────────────────────
    private function resolveUser(int $targetId): ?array {
        $me = $this->requireAuth();
        if ($me['role'] === 'admin') return User::find($targetId);
        if ($me['id'] === $targetId)  return User::find($targetId);
        if (User::canView($me['id'], $targetId)) return User::find($targetId);
        if (\Models\Team::isManagerOf($me['id'], $targetId)) return User::find($targetId);
        return null;
    }

    // ── VUE MEMBRE (appelé depuis TeamController) ─────────────────────────────
    public function renderMemberYear(array $me, array $member, int $year): void {
        // Membres virtuels : saisie par le responsable (readonly=false)
        // Membres réels via délégation : lecture seule (readonly=true)
        $isVirtual = !empty($member['virtual']);
        $readonly  = !$isVirtual;
        $this->renderYear($member['id'], $year, $me, $readonly, $member);
    }

    public function renderMemberMonth(array $me, array $member, int $year, int $month): void {
        $isVirtual = !empty($member['virtual']);
        $readonly  = !$isVirtual;
        $this->renderMonth($member['id'], $year, $month, $me, $readonly, $member);
    }

    private function currentYear(): int {
        return (int)date('Y');
    }

    // ── VUE PROPRE ───────────────────────────────────────────────────────────
    public function year(string $year = ''): void {
        $me   = $this->requireAuth();
        $year = $year ? (int)$year : $this->currentYear();
        $this->renderYear($me['id'], $year, $me, false);
    }

    public function month(string $year, string $month): void {
        $me = $this->requireAuth();
        $this->renderMonth($me['id'], (int)$year, (int)$month, $me, false);
    }

    // ── VUE DÉLÉGUÉE ─────────────────────────────────────────────────────────
    public function viewYear(string $uid, string $year): void {
        $me     = $this->requireAuth();
        $target = $this->resolveUser((int)$uid);
        if (!$target) { $this->flash('error', 'Accès non autorisé.'); $this->redirect('cra'); }
        $this->renderYear($target['id'], (int)$year, $me, true, $target);
    }

    public function viewMonth(string $uid, string $year, string $month): void {
        $me     = $this->requireAuth();
        $target = $this->resolveUser((int)$uid);
        if (!$target) { $this->flash('error', 'Accès non autorisé.'); $this->redirect('cra'); }
        $this->renderMonth($target['id'], (int)$year, (int)$month, $me, true, $target);
    }

    // ── RENDER YEAR ──────────────────────────────────────────────────────────
    private function renderYear(int $userId, int $year, array $me, bool $readonly, ?array $target = null): void {
        $stats   = [];
        $feries  = Cra::feries($year);
        for ($m = 1; $m <= 12; $m++) {
            $s       = Cra::statsMonth($userId, $year, $m);
            $s['nr'] = $this->ouvrables($year, $m, $feries)
                       - $s['p'] - $s['t'] - $s['r'] - $s['c'] - $s['f'] - $s['s'];
            $stats[$m] = $s;
        }
        $yearStats      = Cra::statsYear($userId, $year);
        $config         = Cra::getConfig($userId);         // config actuelle (pour le formulaire)
        $configByMonth  = Cra::getConfigByMonth($userId, $year); // config historique par mois
        $configPeriods  = Cra::getConfigPeriods($userId);  // toutes les périodes
        $accessible     = $me['role'] === 'admin' ? User::all() : User::accessibleBy($me['id']);
        // Soldes RTT/CP : exercice en cours si on regarde l'année courante, sinon celui qui démarre en juin de l'année affichée
        $leavePeriod = ($year === (int)date('Y')) ? Leave::periodOf(date('Y-m-d')) : $year;
        $leave = [
            'r' => Leave::summary($userId, 'r', $leavePeriod),
            'c' => Leave::summary($userId, 'c', $leavePeriod),
        ];

        $this->view('shared.layout', [
            'me'            => $me,
            'target'        => $target,
            'readonly'      => $readonly,
            'year'          => $year,
            'leave'         => $leave,
            'leavePeriod'   => $leavePeriod,
            'stats'         => $stats,
            'yearStats'     => $yearStats,
            'config'        => $config,
            'configByMonth' => $configByMonth,
            'configPeriods' => $configPeriods,
            'feries'        => $feries,
            'accessible'    => $accessible,
            'view'          => 'year',
            'flash'         => $this->getFlash(),
        ]);
    }

    // ── RENDER MONTH ─────────────────────────────────────────────────────────
    private function renderMonth(int $userId, int $year, int $month, array $me, bool $readonly, ?array $target = null): void {
        $feries = Cra::feries($year);
        $days   = Cra::getDays($userId, $year);
        $notes  = Cra::getNotes($userId, $year);
        $stats  = Cra::statsMonth($userId, $year, $month);
        // Config pour ce mois précis (milieu du mois)
        $config = Cra::getConfigForDate($userId, sprintf('%04d-%02d-15', $year, $month));
        $accessible = $me['role'] === 'admin' ? User::all() : User::accessibleBy($me['id']);

        $this->view('shared.layout', [
            'me'        => $me,
            'target'    => $target,
            'readonly'  => $readonly,
            'year'      => $year,
            'month'     => $month,
            'days'      => $days,
            'notes'     => $notes,
            'stats'     => $stats,
            'config'    => $config,
            'feries'    => $feries,
            'accessible'=> $accessible,
            'view'      => 'month',
            'flash'     => $this->getFlash(),
        ]);
    }

    // ── ACTIONS (AJAX / POST) ────────────────────────────────────────────────
    private const VALID_TYPES = ['p','t','r','c','f','s'];
    private const VALID_HALVES = ['am','pm'];

    public function saveDay(): void {
        $me       = $this->requireAuth();
        $this->verifyCsrf();
        $date     = $this->post('date');
        $rawType  = $this->post('type') ?: null;
        $type     = ($rawType && in_array($rawType, self::VALID_TYPES, true)) ? $rawType : null;
        $targetId = $this->post('target_id') ? (int)$this->post('target_id') : null;

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $this->json(['error'=>'date invalide'],400);

        if ($targetId && $targetId !== $me['id']) {
            // Vérifier accès (manager d'équipe ou admin)
            if ($me['role'] !== 'admin' && !\Models\Team::isManagerOf($me['id'], $targetId)) {
                $this->json(['error'=>'Accès refusé'], 403);
            }
            Cra::setDay($targetId, $date, $type);
        } else {
            Cra::setDay($me['id'], $date, $type);
        }
        $this->json(['ok' => true]);
    }

    public function saveHalfDay(): void {
        $me       = $this->requireAuth();
        $this->verifyCsrf();
        $date     = $this->post('date');
        $half     = $this->post('half'); // 'am' ou 'pm'
        $rawType  = $this->post('type') ?: null;
        $type     = ($rawType && in_array($rawType, self::VALID_TYPES, true)) ? $rawType : null;
        $targetId = $this->post('target_id') ? (int)$this->post('target_id') : null;

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date))
            $this->json(['error'=>'date invalide'], 400);
        if (!in_array($half, self::VALID_HALVES, true))
            $this->json(['error'=>'half invalide'], 400);

        $userId = $me['id'];
        if ($targetId && $targetId !== $me['id']) {
            if ($me['role'] !== 'admin' && !\Models\Team::isManagerOf($me['id'], $targetId))
                $this->json(['error'=>'Accès refusé'], 403);
            $userId = $targetId;
        }

        Cra::setHalfDay($userId, $date, $half, $type);
        $this->json(['ok' => true]);
    }

    public function saveNote(): void {
        $me       = $this->requireAuth();
        $this->verifyCsrf();
        $date     = $this->post('date');
        $content  = $this->post('content','');
        $targetId = $this->post('target_id') ? (int)$this->post('target_id') : null;

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $this->json(['error'=>'date invalide'],400);

        if ($targetId && $targetId !== $me['id']) {
            if ($me['role'] !== 'admin' && !\Models\Team::isManagerOf($me['id'], $targetId)) {
                $this->json(['error'=>'Accès refusé'], 403);
            }
            Cra::setNote($targetId, $date, $content);
        } else {
            Cra::setNote($me['id'], $date, $content);
        }
        $this->json(['ok' => true]);
    }

    public function saveConfig(): void {
        $me = $this->requireAuth();
        $this->verifyCsrf();
        foreach (['km','duree','indem'] as $k) {
            if (isset($_POST[$k])) {
                $val = filter_var($this->post($k), FILTER_VALIDATE_FLOAT);
                if ($val !== false && $val >= 0 && $val <= 9999) {
                    Cra::setConfig($me['id'], $k, (string)$val);
                }
            }
        }
        $this->json(['ok' => true]);
    }

    public function saveConfigPeriod(): void {
        $me    = $this->requireAuth();
        $this->verifyCsrf();

        // Support membres virtuels gérés par responsable
        $targetId = $this->post('target_id') ? (int)$this->post('target_id') : $me['id'];
        if ($targetId !== $me['id']) {
            if ($me['role'] !== 'admin' && !\Models\Team::isManagerOf($me['id'], $targetId)) {
                $this->redirect('cra');
            }
        }

        $km        = max(0, (float)$this->post('km', 0));
        $duree     = max(0, (float)$this->post('duree', 0));
        $indem     = max(0, (float)$this->post('indem', 0));
        $validFrom = $this->post('valid_from');
        $label     = trim($this->post('label', '')) ?: 'Configuration du '.$validFrom;

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $validFrom)) {
            $this->flash('error', 'Date de début invalide.');
            $this->redirect('cra');
        }

        // Date de fin optionnelle — vide = période en cours (valeur actuelle)
        $rawValidTo = trim((string)$this->post('valid_to', ''));
        $validTo    = null;
        if ($rawValidTo !== '') {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawValidTo)) {
                $this->flash('error', 'Date de fin invalide.');
                $this->redirect('cra');
            }
            if ($rawValidTo < $validFrom) {
                $this->flash('error', 'La date de fin doit être postérieure à la date de début.');
                $this->redirect('cra');
            }
            $validTo = $rawValidTo;
        }

        $periodId = (int)$this->post('period_id', 0);
        if ($periodId > 0) {
            Cra::updateConfigPeriod($periodId, $targetId, $km, $duree, $indem, $validFrom, $validTo, $label);
            $this->flash('success', 'Configuration mise à jour.');
        } else {
            Cra::addConfigPeriod($targetId, $km, $duree, $indem, $validFrom, $validTo, $label);
            $this->flash('success', 'Nouvelle période de configuration ajoutée.');
        }

        $year = $this->post('year', date('Y'));
        if ($targetId !== $me['id']) {
            $this->redirect("teams/member/$targetId/year/$year");
        } else {
            $this->redirect("cra/year/$year");
        }
    }

    public function deleteConfigPeriod(): void {
        $me = $this->requireAuth();
        $this->verifyCsrf();

        $targetId = $this->post('target_id') ? (int)$this->post('target_id') : $me['id'];
        if ($targetId !== $me['id']) {
            if ($me['role'] !== 'admin' && !\Models\Team::isManagerOf($me['id'], $targetId)) {
                $this->redirect('cra');
            }
        }

        $periodId = (int)$this->post('period_id');
        Cra::deleteConfigPeriod($periodId, $targetId);
        $this->flash('success', 'Période supprimée.');

        $year = $this->post('year', date('Y'));
        if ($targetId !== $me['id']) {
            $this->redirect("teams/member/$targetId/year/$year");
        } else {
            $this->redirect("cra/year/$year");
        }
    }

    // ── SOLDES RTT / CONGÉS ──────────────────────────────────────────────────
    /** L'utilisateur $me peut-il modifier les droits de $target ? (soi-même, ou compte virtuel dont on est responsable) */
    private function canEditLeave(array $me, array $target): bool {
        if ($me['id'] === $target['id']) return true;
        if (empty($target['virtual'])) return false;
        return $me['role'] === 'admin' || \Models\Team::isManagerOf($me['id'], $target['id']);
    }

    public function soldes(string $period = ''): void {
        $me       = $this->requireAuth();
        $targetId = isset($_GET['u']) ? (int)$_GET['u'] : $me['id'];
        $target   = $this->resolveUser($targetId);
        if (!$target) { $this->flash('error', 'Accès non autorisé.'); $this->redirect('cra'); }

        $period = ($period !== '' && ctype_digit($period)) ? (int)$period : Leave::periodOf(date('Y-m-d'));
        if ($period < 2000 || $period > 2100) $period = Leave::periodOf(date('Y-m-d'));

        $isSelf   = $target['id'] === $me['id'];
        $readonly = !$this->canEditLeave($me, $target);
        $periods  = Leave::periodsWithData($target['id']);
        $history  = [];
        foreach ($periods as $p) {
            $history[$p] = ['r' => Leave::summary($target['id'], 'r', $p), 'c' => Leave::summary($target['id'], 'c', $p)];
        }
        $accessible = $me['role'] === 'admin' ? User::all() : User::accessibleBy($me['id']);

        $this->view('shared.layout', [
            'me'         => $me,
            'target'     => $isSelf ? null : $target,
            'readonly'   => $readonly,
            'year'       => (int)date('Y'),
            'period'     => $period,
            'leave'      => ['r' => Leave::summary($target['id'], 'r', $period), 'c' => Leave::summary($target['id'], 'c', $period)],
            'history'    => $history,
            'accessible' => $accessible,
            'view'       => 'soldes',
            'flash'      => $this->getFlash(),
        ]);
    }

    public function saveSoldes(): void {
        $me = $this->requireAuth();
        $this->verifyCsrf();

        $targetId = $this->post('target_id') ? (int)$this->post('target_id') : $me['id'];
        $target   = User::find($targetId);
        if (!$target || !$this->canEditLeave($me, $target)) {
            $this->flash('error', 'Accès non autorisé.');
            $this->redirect('cra');
        }

        $type   = (string)$this->post('type');
        $period = (int)$this->post('period');
        if (!isset(Leave::TYPES[$type]) || $period < 2000 || $period > 2100) {
            $this->flash('error', 'Données invalides.');
            $this->redirect('cra/soldes');
        }

        // Accepte "12,5" comme "12.5"
        $parse = fn(string $k) => filter_var(str_replace(',', '.', trim((string)$this->post($k, '0'))), FILTER_VALIDATE_FLOAT);
        $acquis = $parse('acquis');
        $report = $parse('report');
        if ($acquis === false || $report === false || $acquis < 0 || $acquis > 365 || $report < 0 || $report > 365) {
            $this->flash('error', 'Valeurs invalides (nombre de jours entre 0 et 365).');
        } elseif (fmod($acquis * 2, 1.0) != 0.0 || fmod($report * 2, 1.0) != 0.0) {
            // Le CRA compte en demi-journées : un solde en 0,3 j ne pourrait jamais être consommé exactement.
            $this->flash('error', 'Saisissez des jours entiers ou des demi-journées (ex : 12 ou 12,5).');
        } else {
            Leave::save($targetId, $type, $period, (float)$acquis, (float)$report);
            $this->flash('success', Leave::TYPES[$type] . ' ' . Leave::label($period) . ' enregistré.');
        }
        $this->redirect("cra/soldes/$period" . ($targetId !== $me['id'] ? "?u=$targetId" : ''));
    }

    public function export(string $year): void {
        $me   = $this->requireAuth();
        $this->doExport($me['id'], (int)$year);
    }

    public function viewExport(string $uid, string $year): void {
        $me     = $this->requireAuth();
        $target = $this->resolveUser((int)$uid);
        if (!$target) $this->json(['error'=>'Accès refusé'],403);
        $this->doExport($target['id'], (int)$year);
    }

    private function doExport(int $userId, int $year): void {
        $user          = User::find($userId);
        $days          = Cra::getDays($userId, $year);
        $notes         = Cra::getNotes($userId, $year);
        $configByMonth = Cra::getConfigByMonth($userId, $year);
        $feries        = Cra::feries($year);

        // CSV avec km/temps/indem par mois selon config historique
        $csv  = "Date,Jour,Type,Note,Km A/R,Temps trajet (min),Indemnite (EUR)\n";
        $months = ['','Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
        $dayNames = ['Dim','Lun','Mar','Mer','Jeu','Ven','Sam'];
        $labels = ['p'=>'Présentiel','t'=>'Télétravail','r'=>'RTT','c'=>'Congé payé','f'=>'Férié','s'=>'Sans solde'];

        for ($m = 1; $m <= 12; $m++) {
            $total = cal_days_in_month(CAL_GREGORIAN, $m, $year);
            for ($d = 1; $d <= $total; $d++) {
                $date  = sprintf('%04d-%02d-%02d', $year, $m, $d);
                $dow   = (int)date('w', strtotime($date));
                $isWe  = in_array($dow, [0,6]);
                $isFer = in_array($date, $feries) && !$isWe;
                $dayData = $days[$date] ?? null;
                $typeAm  = $dayData['am']   ?? null;
                $typePm  = $dayData['pm']   ?? null;
                $type    = $dayData ? $dayData['type'] : ($isFer ? 'f' : ($isWe ? 'we' : null));

                if ($typeAm || $typePm) {
                    $label = ($labels[$typeAm ?? ''] ?? 'Non saisi').' AM / '.($labels[$typePm ?? ''] ?? 'Non saisi').' PM';
                } else {
                    $label = $labels[$type ?? ''] ?? ($type === 'we' ? 'Week-end' : 'Non saisi');
                }
                $note   = '"' . str_replace('"','""', $notes[$date] ?? '') . '"';
                $cfg    = $configByMonth[$m];
                // Trajet complet (1x) dès qu'il y a du présentiel sur la journée,
                // même en demi-journée — le trajet domicile-travail ne dépend
                // pas de la durée de présence sur place.
                $pTrip  = (!$typeAm && !$typePm)
                        ? ($type === 'p' ? 1.0 : 0.0)
                        : (($typeAm === 'p' || $typePm === 'p') ? 1.0 : 0.0);
                $kmVal  = $pTrip * $cfg['km'];
                $durVal = $pTrip * $cfg['duree'];
                $indVal = $pTrip * $cfg['indem'];

                $dayName = $dayNames[$dow];
                $csv .= "$date,$dayName,\"$label\",$note,"
                      . number_format($kmVal, 1, '.', '') . ','
                      . number_format($durVal, 0, '.', '') . ','
                      . number_format($indVal, 2, '.', '') . "\n";
            }
        }

        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"CRA_{$year}_{$user['username']}.csv\"");
        header('Content-Length: ' . strlen($csv));
        echo $csv;
        exit;
    }

    // ── UTILS ────────────────────────────────────────────────────────────────
    private function ouvrables(int $year, int $month, array $feries): int {
        $total = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $n = 0;
        for ($d = 1; $d <= $total; $d++) {
            $date = sprintf('%04d-%02d-%02d', $year, $month, $d);
            $dow  = (int)date('w', strtotime($date));
            if (!in_array($dow,[0,6]) && !in_array($date,$feries)) $n++;
        }
        return $n;
    }
}

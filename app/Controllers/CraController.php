<?php namespace Controllers;
use Core\Controller;
use Models\{Cra, User};

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
        $yearStats = Cra::statsYear($userId, $year);
        $config    = Cra::getConfig($userId);
        $accessible = $me['role'] === 'admin' ? User::all() : User::accessibleBy($me['id']);

        $this->view('shared.layout', [
            'me'        => $me,
            'target'    => $target,
            'readonly'  => $readonly,
            'year'      => $year,
            'stats'     => $stats,
            'yearStats' => $yearStats,
            'config'    => $config,
            'feries'    => $feries,
            'accessible'=> $accessible,
            'view'      => 'year',
            'flash'     => $this->getFlash(),
        ]);
    }

    // ── RENDER MONTH ─────────────────────────────────────────────────────────
    private function renderMonth(int $userId, int $year, int $month, array $me, bool $readonly, ?array $target = null): void {
        $feries = Cra::feries($year);
        $days   = Cra::getDays($userId, $year);
        $notes  = Cra::getNotes($userId, $year);
        $stats  = Cra::statsMonth($userId, $year, $month);
        $config = Cra::getConfig($userId);
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
        $user   = User::find($userId);
        $days   = Cra::getDays($userId, $year);
        $notes  = Cra::getNotes($userId, $year);
        $config = Cra::getConfig($userId);
        $feries = Cra::feries($year);

        // CSV
        $csv  = "Date,Jour,Type,Note\n";
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
                $type  = $days[$date] ?? ($isFer ? 'f' : ($isWe ? 'we' : ''));
                $label = $labels[$type] ?? ($type === 'we' ? 'Week-end' : 'Non saisi');
                $note  = '"' . str_replace('"','""', $notes[$date] ?? '') . '"';
                $csv  .= "$date,{$dayNames[$dow]},$label,$note\n";
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

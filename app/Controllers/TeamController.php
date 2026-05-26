<?php namespace Controllers;
use Core\Controller;
use Models\{Team, User, Cra};

class TeamController extends Controller {

    /** Vérifie que l'utilisateur courant est bien responsable de l'équipe (ou admin) */
    private function requireTeamOwner(int $teamId): array {
        $me   = $this->requireAuth();
        $team = Team::find($teamId);
        if (!$team) {
            $this->flash('error','Équipe introuvable.');
            $this->redirect('teams');
        }
        // L'admin voit tout ; le owner aussi ; les membres de l'équipe peuvent voir (lecture)
        if ($me['role'] !== 'admin' && (int)$team['owner_id'] !== (int)$me['id']) {
            $this->flash('error','Accès non autorisé.');
            $this->redirect('teams');
        }
        return [$me, $team];
    }

    /** Vérifie que l'utilisateur peut gérer un membre (via équipe ou admin) */
    private function requireMemberAccess(int $memberId): array {
        $me = $this->requireAuth();
        $member = User::find($memberId);
        if (!$member) { $this->flash('error','Membre introuvable.'); $this->redirect('teams'); }
        if ($me['role'] !== 'admin' && !Team::isManagerOf($me['id'], $memberId)) {
            $this->flash('error','Accès non autorisé.'); $this->redirect('teams');
        }
        return [$me, $member];
    }

    // ── LISTE DES ÉQUIPES ────────────────────────────────────────────────────
    public function index(): void {
        $me    = $this->requireAuth();
        $teams = $me['role'] === 'admin'
            ? Team::all()
            : Team::ownedBy($me['id']);

        $this->view('shared.layout', [
            'me'         => $me,
            'teams'      => $teams,
            'view'       => 'teams_index',
            'year'       => (int)date('Y'),
            'readonly'   => false,
            'target'     => null,
            'accessible' => [],
            'flash'      => $this->getFlash(),
        ]);
    }

    // ── DÉTAIL D'UNE ÉQUIPE ──────────────────────────────────────────────────
    public function show(string $id): void {
        [$me, $team] = $this->requireTeamOwner((int)$id);
        $members = Team::members((int)$id);
        $year    = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
        $stats   = [];
        foreach ($members as $m) {
            $s = Cra::statsYear($m['id'], $year);
            $stats[$m['id']] = $s;
        }
        $allUsers = User::all(false); // Utilisateurs réels pour ajout

        $this->view('shared.layout', [
            'me'         => $me,
            'team'       => $team,
            'members'    => $members,
            'stats'      => $stats,
            'allUsers'   => $allUsers,
            'year'       => $year,
            'view'       => 'teams_show',
            'readonly'   => false,
            'target'     => null,
            'accessible' => [],
            'flash'      => $this->getFlash(),
        ]);
    }

    // ── CRÉER UNE ÉQUIPE ─────────────────────────────────────────────────────
    public function create(): void {
        $me   = $this->requireAuth();
        $name = trim($this->post('name'));
        if (!$name) { $this->flash('error','Nom requis.'); $this->redirect('teams'); }

        // Par défaut l'utilisateur courant est responsable.
        // L'admin peut choisir un autre responsable.
        $ownerId = ($me['role'] === 'admin' && $this->post('owner_id'))
            ? (int)$this->post('owner_id')
            : (int)$me['id'];

        $tid = Team::create($name, $ownerId);
        $this->flash('success', "Équipe « $name » créée.");
        $this->redirect("teams/$tid");
    }

    // ── MODIFIER UNE ÉQUIPE ──────────────────────────────────────────────────
    public function edit(string $id): void {
        [$me, $team] = $this->requireTeamOwner((int)$id);
        $name    = trim($this->post('name'));
        $ownerId = $me['role'] === 'admin' && $this->post('owner_id')
            ? (int)$this->post('owner_id')
            : $team['owner_id'];

        if (!$name) { $this->flash('error','Nom requis.'); $this->redirect("teams/$id"); }
        Team::update((int)$id, $name, $ownerId);
        $this->flash('success','Équipe mise à jour.');
        $this->redirect("teams/$id");
    }

    // ── SUPPRIMER UNE ÉQUIPE (responsable ou admin) ──────────────────────────
    public function delete(string $id): void {
        [$me, $team] = $this->requireTeamOwner((int)$id);
        $members = Team::members((int)$id);
        Team::delete((int)$id);
        // Nettoyer les comptes virtuels orphelins
        foreach ($members as $m) {
            if ($m['virtual']) {
                $remaining = \Core\DB::fetchOne("SELECT id FROM team_members WHERE user_id=?", [$m['id']]);
                if (!$remaining) \Models\User::delete($m['id']);
            }
        }
        $this->flash('success',"Équipe « {$team['name']} » supprimée.");
        $this->redirect('teams');
    }

    // ── AJOUTER UN MEMBRE VIRTUEL ────────────────────────────────────────────
    public function addVirtual(string $id): void {
        [$me, $team] = $this->requireTeamOwner((int)$id);
        $name = trim($this->post('name'));
        if (!$name) { $this->flash('error','Nom requis.'); $this->redirect("teams/$id"); }

        $uid = User::createVirtual($name);
        Team::addMember((int)$id, $uid);
        $this->flash('success',"Membre virtuel « $name » ajouté.");
        $this->redirect("teams/$id");
    }

    // ── AJOUTER UN MEMBRE RÉEL ───────────────────────────────────────────────
    public function addMember(string $id): void {
        [$me, $team] = $this->requireTeamOwner((int)$id);
        $uid = (int)$this->post('user_id');
        if (!$uid) { $this->flash('error','Utilisateur requis.'); $this->redirect("teams/$id"); }
        Team::addMember((int)$id, $uid);
        $this->flash('success','Membre ajouté.');
        $this->redirect("teams/$id");
    }

    // ── RETIRER UN MEMBRE ────────────────────────────────────────────────────
    public function removeMember(string $id): void {
        [$me, $team] = $this->requireTeamOwner((int)$id);
        $uid     = (int)$this->post('user_id');
        $member  = User::find($uid);
        Team::removeMember((int)$id, $uid);

        // Supprimer le compte si virtuel et plus dans aucune équipe
        if ($member && $member['virtual']) {
            $remaining = \Core\DB::fetchOne(
                "SELECT id FROM team_members WHERE user_id=?", [$uid]
            );
            if (!$remaining) User::delete($uid);
        }
        $this->flash('success','Membre retiré.');
        $this->redirect("teams/$id");
    }

    // ── RENOMMER UN MEMBRE VIRTUEL ───────────────────────────────────────────
    public function renameVirtual(string $id): void {
        [$me, $member] = $this->requireMemberAccess((int)$id);
        if (!$member['virtual']) { $this->redirect('teams'); }
        $name = trim($this->post('name'));
        if ($name) User::update((int)$id, ['name' => $name]);
        $ref = $this->post('team_id');
        $this->flash('success','Nom mis à jour.');
        $this->redirect($ref ? "teams/$ref" : 'teams');
    }

    // ── CRA D'UN MEMBRE (saisie par responsable) ─────────────────────────────
    public function memberYear(string $uid, string $year): void {
        [$me, $member] = $this->requireMemberAccess((int)$uid);
        $craCtrl = new CraController();
        $craCtrl->renderMemberYear($me, $member, (int)$year);
    }

    public function memberMonth(string $uid, string $year, string $month): void {
        [$me, $member] = $this->requireMemberAccess((int)$uid);
        $craCtrl = new CraController();
        $craCtrl->renderMemberMonth($me, $member, (int)$year, (int)$month);
    }

    // ── EXPORT ÉQUIPE ────────────────────────────────────────────────────────
    public function exportTeam(string $id, string $year): void {
        [$me, $team] = $this->requireTeamOwner((int)$id);
        $members = Team::members((int)$id);
        $yr      = (int)$year;

        $labels  = ['p'=>'Présentiel','t'=>'Télétravail','r'=>'RTT','c'=>'Congé payé','f'=>'Férié','s'=>'Sans solde'];
        $dayNames= ['Dim','Lun','Mar','Mer','Jeu','Ven','Sam'];
        $feries  = Cra::feries($yr);

        $csv = "Membre,Date,Jour,Type,Note\n";
        foreach ($members as $m) {
            $days  = Cra::getDays($m['id'], $yr);
            $notes = Cra::getNotes($m['id'], $yr);
            for ($mo=1; $mo<=12; $mo++) {
                $total = cal_days_in_month(CAL_GREGORIAN, $mo, $yr);
                for ($d=1; $d<=$total; $d++) {
                    $date  = sprintf('%04d-%02d-%02d', $yr, $mo, $d);
                    $dow   = (int)date('w', strtotime($date));
                    $isWe  = in_array($dow,[0,6]);
                    $isFer = in_array($date,$feries) && !$isWe;
                    $type  = $days[$date] ?? ($isFer?'f':($isWe?'we':''));
                    $label = $labels[$type] ?? ($type==='we'?'Week-end':'Non saisi');
                    $note  = '"'.str_replace('"','""',$notes[$date]??'').'"';
                    $name  = '"'.str_replace('"','""',$m['name']).'"';
                    $csv  .= "$name,$date,{$dayNames[$dow]},$label,$note\n";
                }
            }
        }

        $filename = "CRA_equipe_{$team['name']}_{$yr}.csv";
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"$filename\"");
        echo $csv;
        exit;
    }

    // ── ARCHIVE ÉQUIPE ───────────────────────────────────────────────────────
    public function archiveTeam(string $id): void {
        [$me, $team] = $this->requireTeamOwner((int)$id);
        $year  = (int)$this->post('year', date('Y'));
        $label = trim($this->post('label')) ?: "Archive équipe {$team['name']} $year";

        if (!is_dir(ARCHIVES_DIR)) mkdir(ARCHIVES_DIR, 0755, true);
        $members = Team::members((int)$id);
        $data = ['year'=>$year,'team'=>$team,'members'=>[]];
        foreach ($members as $m) {
            $data['members'][] = [
                'user'   => $m,
                'days'   => Cra::getDays($m['id'], $year),
                'notes'  => Cra::getNotes($m['id'], $year),
                'config' => Cra::getConfig($m['id']),
            ];
        }
        $filename = 'archive_team'.$id.'_'.$year.'_'.date('Ymd_His').'.json';
        file_put_contents(ARCHIVES_DIR.'/'.$filename, json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));

        \Core\DB::query(
            "INSERT INTO archives (year,team_id,label,filename,created_by) VALUES (?,?,?,?,?)",
            [$year, (int)$id, $label, $filename, $me['id']]
        );
        $this->flash('success',"Archive créée.");
        $this->redirect("teams/$id");
    }
}

<?php namespace Controllers;
use Core\{Controller, DB};
use Models\{User, Cra, Team};

class AdminController extends Controller {

    public function dashboard(): void {
        $admin = $this->requireAdmin();
        $users = User::all();
        $year  = (int)date('Y');
        $stats = [];
        foreach ($users as $u) {
            $stats[$u['id']] = Cra::statsYear($u['id'], $year);
        }
        $this->view('shared.layout', [
            'me'=>$admin,'users'=>$users,'stats'=>$stats,'year'=>$year,
            'flash'=>$this->getFlash(),'view'=>'admin_dashboard',
            'readonly'=>false,'target'=>null,'accessible'=>[]
        ]);
    }

    // ── UTILISATEURS ─────────────────────────────────────────────────────────
    public function users(): void {
        $admin = $this->requireAdmin();
        $this->view('shared.layout', [
            'me'      => $admin,
            'users'   => User::all(false),   // Uniquement comptes réels
            'virtuals'=> User::allVirtual(),  // Comptes virtuels séparés
            'flash'   => $this->getFlash(),
            'view'    => 'admin_users',
            'readonly'=> false,'target'=>null,'accessible'=>[],'year'=>(int)date('Y')
        ]);
    }

    public function createUser(): void {
        $this->requireAdmin();
        $username = trim($this->post('username'));
        $name     = trim($this->post('name'));
        $password = $this->post('password');
        $role     = in_array($this->post('role'), ['user','admin'], true) ? $this->post('role') : 'user';

        if (!$username || !$name || !$password) {
            $this->flash('error','Tous les champs sont requis.');
            $this->redirect('admin/users');
        }
        if (User::findByUsername($username)) {
            $this->flash('error',"Le nom d'utilisateur « $username » existe déjà.");
            $this->redirect('admin/users');
        }
        User::create($username, $name, $password, $role);
        $this->flash('success',"Utilisateur « $name » créé.");
        $this->redirect('admin/users');
    }

    public function editUser(string $id): void {
        $admin = $this->requireAdmin();
        $this->verifyCsrf();
        $id    = (int)$id;
        $user  = User::find($id);
        if (!$user) { $this->flash('error','Utilisateur introuvable.'); $this->redirect('admin/users'); }

        $fields = [
            'name'   => trim($this->post('name')),
            'role'   => in_array($this->post('role',['user','admin']), ['user','admin'], true) ? $this->post('role') : 'user',
            'active' => $this->post('active') === '0' ? '0' : '1',
        ];
        $password = $this->post('password');
        if ($password) $fields['password'] = password_hash($password, PASSWORD_DEFAULT);

        if ($id === $admin['id'] && $fields['role'] !== 'admin') {
            $this->flash('error','Impossible de retirer vos propres droits admin.');
            $this->redirect('admin/users');
        }
        User::update($id, $fields);
        $this->flash('success','Utilisateur mis à jour.');
        $this->redirect('admin/users');
    }

    public function deleteUser(string $id): void {
        $admin = $this->requireAdmin();
        $this->verifyCsrf();
        $id    = (int)$id;
        if ($id === $admin['id']) {
            $this->flash('error','Impossible de supprimer votre propre compte.');
            $this->redirect('admin/users');
        }
        $user = User::find($id);
        if (!$user) { $this->flash('error','Utilisateur introuvable.'); $this->redirect('admin/users'); }
        // Suppression en cascade (jours, notes, config, délégations via FK)
        User::delete($id);
        $this->flash('success',"Compte « {$user['name']} » supprimé avec toutes ses données.");
        $this->redirect('admin/users');
    }

    public function deleteVirtual(string $id): void {
        $this->requireAdmin();
        $id   = (int)$id;
        $user = User::find($id);
        if (!$user || !$user['virtual']) {
            $this->flash('error','Compte virtuel introuvable.');
            $this->redirect('admin/users');
        }
        User::delete($id); // Cascade : team_members, days, notes supprimés
        $this->flash('success',"Compte virtuel « {$user['name']} » supprimé.");
        $this->redirect('admin/users');
    }

    public function viewUser(string $id): void {
        $this->requireAdmin();
        $this->redirect('view/' . $id . '/year/' . date('Y'));
    }

    // ── ÉQUIPES (admin) ───────────────────────────────────────────────────────
    public function deleteTeam(string $id): void {
        $this->requireAdmin();
        $id   = (int)$id;
        $team = Team::find($id);
        if (!$team) { $this->flash('error','Équipe introuvable.'); $this->redirect('admin'); }

        // Supprimer les comptes virtuels orphelins de cette équipe
        $members = Team::members($id);
        Team::delete($id); // Supprime aussi team_members en cascade
        foreach ($members as $m) {
            if ($m['virtual']) {
                // S'il n'est plus dans aucune équipe, on le supprime
                $remaining = DB::fetchOne("SELECT id FROM team_members WHERE user_id=?", [$m['id']]);
                if (!$remaining) User::delete($m['id']);
            }
        }
        $this->flash('success',"Équipe « {$team['name']} » et ses membres virtuels supprimés.");
        $this->redirect('teams');
    }

    // ── DÉLÉGATIONS ───────────────────────────────────────────────────────────
    public function delegations(): void {
        $admin = $this->requireAdmin();
        $this->view('shared.layout', [
            'me'=>$admin,'delegations'=>Cra::allDelegations(),'users'=>User::all(),
            'flash'=>$this->getFlash(),'view'=>'admin_deleg',
            'readonly'=>false,'target'=>null,'accessible'=>[],'year'=>(int)date('Y')
        ]);
    }

    public function saveDelegation(): void {
        $this->requireAdmin();
        $ownerId  = (int)$this->post('owner_id');
        $viewerId = (int)$this->post('viewer_id');
        if ($ownerId === $viewerId) { $this->flash('error','Un utilisateur ne peut pas se déléguer à lui-même.'); $this->redirect('admin/delegations'); }
        Cra::addDelegation($ownerId, $viewerId);
        $this->flash('success','Délégation ajoutée.');
        $this->redirect('admin/delegations');
    }

    public function deleteDelegation(): void {
        $this->requireAdmin();
        Cra::deleteDelegation((int)$this->post('id'));
        $this->flash('success','Délégation supprimée.');
        $this->redirect('admin/delegations');
    }

    // ── ARCHIVES ──────────────────────────────────────────────────────────────
    public function archives(): void {
        $admin = $this->requireAdmin();
        $this->view('shared.layout', [
            'me'=>$admin,'archives'=>Cra::allArchives(),'users'=>User::all(),
            'flash'=>$this->getFlash(),'view'=>'admin_archives',
            'readonly'=>false,'target'=>null,'accessible'=>[],'year'=>(int)date('Y')
        ]);
    }

    public function createArchive(): void {
        $admin  = $this->requireAdmin();
        $year   = (int)$this->post('year', date('Y'));
        $userId = $this->post('user_id') !== '' ? (int)$this->post('user_id') : null;
        $label  = trim($this->post('label')) ?: "Archive $year" . ($userId ? ' (user '.$userId.')' : ' (tous)');
        Cra::createArchive($year, $userId, $label, $admin['id']);
        $this->flash('success',"Archive « $label » créée.");
        $this->redirect('admin/archives');
    }

    public function downloadArchive(string $id): void {
        $this->requireAdmin();
        $archive = Cra::findArchive((int)$id);
        if (!$archive) { $this->flash('error','Archive introuvable.'); $this->redirect('admin/archives'); }
        $path = ARCHIVES_DIR . '/' . $archive['filename'];
        if (!file_exists($path)) { $this->flash('error','Fichier manquant.'); $this->redirect('admin/archives'); }
        // Sanitiser le nom de fichier pour éviter l'injection de header
        $safeFilename = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($archive['filename']));
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $safeFilename . '"');
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: no-store');
        readfile($path);
        exit;
    }

    public function deleteArchive(string $id): void {
        $this->requireAdmin();
        Cra::deleteArchive((int)$id);
        $this->flash('success','Archive supprimée.');
        $this->redirect('admin/archives');
    }

    // ── REMISE À ZÉRO ────────────────────────────────────────────────────────
    public function resetPage(): void {
        $admin = $this->requireAdmin();
        $this->view('shared.layout', [
            'me'      => $admin,
            'view'    => 'admin_reset',
            'year'    => (int)date('Y'),
            'flash'   => $this->getFlash(),
            'readonly'=> false,'target'=>null,'accessible'=>[]
        ]);
    }

    public function resetConfirm(): void {
        $admin      = $this->requireAdmin();
        $confirm    = trim($this->post('confirm'));
        $keepAdmin  = $this->post('keep_admin', '1') === '1';

        // Double vérification : l'admin doit taper "RESET" pour confirmer
        if ($confirm !== 'RESET') {
            $this->flash('error','Confirmation incorrecte. Tapez exactement RESET.');
            $this->redirect('admin/reset');
        }

        // Sauvegarder les infos admin si nécessaire
        $adminUser = User::find($admin['id']);

        // Supprimer les fichiers d'archives
        if (is_dir(ARCHIVES_DIR)) {
            foreach (glob(ARCHIVES_DIR . '/*.json') as $f) unlink($f);
        }

        // Vider toutes les tables de données
        DB::query("DELETE FROM days");
        DB::query("DELETE FROM notes");
        DB::query("DELETE FROM user_config");
        DB::query("DELETE FROM delegations");
        DB::query("DELETE FROM archives");
        DB::query("DELETE FROM team_members");
        DB::query("DELETE FROM teams");
        // Supprimer tous les users sauf l'admin courant
        DB::query("DELETE FROM users WHERE id != ?", [$admin['id']]);
        // Réinitialiser les séquences autoincrement (table peut ne pas exister)
        try { DB::query("DELETE FROM sqlite_sequence WHERE name != 'users'"); } catch (\Throwable) {}

        $this->flash('success','Base de données remise à zéro. Seul votre compte admin a été conservé.');
        $this->redirect('admin');
    }
}

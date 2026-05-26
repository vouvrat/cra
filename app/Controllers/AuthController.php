<?php namespace Controllers;
use Core\Controller;
use Models\User;

class AuthController extends Controller {

    public function login(): void {
        if (!empty($_SESSION['user'])) $this->redirect('cra');
        $this->view('auth.login', ['flash' => $this->getFlash()]);
    }

    public function doLogin(): void {
        // Pas de CSRF sur login (pas encore de session établie)
        $username = trim($this->post('username'));
        $password = $this->post('password');

        if (!$username || !$password) {
            $this->flash('error', 'Identifiants requis.');
            $this->redirect('login');
        }

        $user = User::verify($username, $password);
        if (!$user) {
            // Délai anti-brute-force
            sleep(1);
            $this->flash('error', 'Identifiants incorrects ou compte désactivé.');
            $this->redirect('login');
        }

        // Régénérer l'ID de session après authentification (fixation de session)
        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id'       => $user['id'],
            'username' => $user['username'],
            'name'     => $user['name'],
            'role'     => $user['role'],
        ];
        $_SESSION['last_activity'] = time();

        $this->redirect($user['role'] === 'admin' ? 'admin' : 'cra');
    }

    public function logout(): void {
        $_SESSION = [];
        session_destroy();
        $this->redirect('login');
    }
}

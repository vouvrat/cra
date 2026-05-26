<?php namespace Core;

class Controller {

    // ── CSRF ─────────────────────────────────────────────────────────────────
    protected function csrfToken(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    protected function verifyCsrf(): void {
        $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            http_response_code(403);
            die('Invalid CSRF token.');
        }
    }

    // ── VIEW ──────────────────────────────────────────────────────────────────
    protected function view(string $tpl, array $data = []): void {
        // Injecter le token CSRF dans toutes les vues
        $data['_csrf'] = $this->csrfToken();
        extract($data);
        $file = APP . '/Views/' . str_replace('.', '/', $tpl) . '.php';
        if (!file_exists($file)) {
            http_response_code(500);
            die('Vue introuvable : ' . htmlspecialchars($tpl));
        }
        require $file;
    }

    protected function redirect(string $path): void {
        header('Location: ' . BASE_URL . ltrim($path, '/'));
        exit;
    }

    protected function json(mixed $data, int $code = 200): void {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }

    protected function requireAuth(): array {
        if (empty($_SESSION['user'])) {
            $this->redirect('login');
        }
        // Timeout de session : 8h d'inactivité
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > 28800) {
            session_destroy();
            $this->redirect('login');
        }
        $_SESSION['last_activity'] = time();
        return $_SESSION['user'];
    }

    protected function requireAdmin(): array {
        $user = $this->requireAuth();
        if ($user['role'] !== 'admin') {
            http_response_code(403);
            $this->redirect('cra');
        }
        return $user;
    }

    protected function post(string $key, mixed $default = ''): mixed {
        return $_POST[$key] ?? $default;
    }

    protected function flash(string $type, string $msg): void {
        $_SESSION['flash'] = compact('type', 'msg');
    }

    protected function getFlash(): ?array {
        $f = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $f;
    }
}

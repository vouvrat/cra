<?php
define('ROOT', dirname(__DIR__));
define('APP',  ROOT . '/app');
define('DATA', ROOT . '/data');

// ── SESSION SÉCURISÉE ─────────────────────────────────────────────────────────
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Strict',
]);
session_start();

// ── HEADERS DE SÉCURITÉ ───────────────────────────────────────────────────────
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src https://fonts.gstatic.com; img-src 'self' data:;");

// ── AUTOLOADER ────────────────────────────────────────────────────────────────
spl_autoload_register(function ($class) {
    $map = [
        'Core\\'        => APP . '/Core/',
        'Models\\'      => APP . '/Models/',
        'Controllers\\' => APP . '/Controllers/',
    ];
    foreach ($map as $prefix => $base) {
        if (str_starts_with($class, $prefix)) {
            $file = $base . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
            if (file_exists($file)) require $file;
        }
    }
});

require ROOT . '/config/config.php';

$router = new Core\Router();

// ── AUTH ──────────────────────────────────────────────────────────────────────
$router->get('',                 'AuthController@login');
$router->get('login',            'AuthController@login');
$router->post('login',           'AuthController@doLogin');
$router->get('logout',           'AuthController@logout');

// ── CRA ───────────────────────────────────────────────────────────────────────
$router->get('cra',                      'CraController@year');
$router->get('cra/year/{year}',          'CraController@year');
$router->get('cra/month/{year}/{month}', 'CraController@month');
$router->post('cra/day',                 'CraController@saveDay');
$router->post('cra/note',                'CraController@saveNote');
$router->post('cra/config',              'CraController@saveConfig');
$router->get('cra/export/{year}',        'CraController@export');

// ── DÉLÉGATION ────────────────────────────────────────────────────────────────
$router->get('view/{uid}/year/{year}',          'CraController@viewYear');
$router->get('view/{uid}/month/{year}/{month}', 'CraController@viewMonth');
$router->get('view/{uid}/export/{year}',        'CraController@viewExport');

// ── ADMIN — fixes avant paramétrées ──────────────────────────────────────────
$router->get('admin',                    'AdminController@dashboard');
$router->get('admin/users',              'AdminController@users');
$router->post('admin/users/create',      'AdminController@createUser');
$router->get('admin/delegations',        'AdminController@delegations');
$router->post('admin/delegations/save',  'AdminController@saveDelegation');
$router->post('admin/delegations/delete','AdminController@deleteDelegation');
$router->get('admin/archives',           'AdminController@archives');
$router->post('admin/archives/create',   'AdminController@createArchive');
$router->get('admin/reset',              'AdminController@resetPage');
$router->post('admin/reset/confirm',     'AdminController@resetConfirm');
$router->get('admin/archives/download/{id}',     'AdminController@downloadArchive');
$router->post('admin/archives/delete/{id}',      'AdminController@deleteArchive');
$router->get('admin/users/{id}/view',            'AdminController@viewUser');
$router->post('admin/users/{id}/edit',           'AdminController@editUser');
$router->post('admin/users/{id}/delete',         'AdminController@deleteUser');
$router->post('admin/users/{id}/delete-virtual', 'AdminController@deleteVirtual');
$router->post('admin/teams/{id}/delete',         'AdminController@deleteTeam');

// ── TEAMS — fixes avant paramétrées ──────────────────────────────────────────
$router->get('teams',                    'TeamController@index');
$router->post('teams/create',            'TeamController@create');
$router->post('teams/{id}/add-virtual',  'TeamController@addVirtual');
$router->post('teams/{id}/add-member',   'TeamController@addMember');
$router->post('teams/{id}/remove-member','TeamController@removeMember');
$router->post('teams/{id}/edit',         'TeamController@edit');
$router->post('teams/{id}/delete',       'TeamController@delete');
$router->post('teams/{id}/archive',      'TeamController@archiveTeam');
$router->post('teams/member/{id}/rename',                'TeamController@renameVirtual');
$router->get('teams/member/{uid}/year/{year}',           'TeamController@memberYear');
$router->get('teams/member/{uid}/month/{year}/{month}',  'TeamController@memberMonth');
$router->get('teams/{id}/export/{year}', 'TeamController@exportTeam');
$router->get('teams/{id}',               'TeamController@show');

$router->dispatch();

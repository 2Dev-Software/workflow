<?php
declare(strict_types=1);

require_once __DIR__ . '/../db/db.php';

if (!function_exists('redirect_to_login')) {
    function redirect_to_login(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();

        $base_path = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/\\');
        $login_path = $base_path === '' ? '/index.php' : $base_path . '/index.php';

        header('Location: ' . $login_path, true, 302);
        exit();
    }
}

if (!function_exists('require_login')) {
    function require_login(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['pID'])) {
            redirect_to_login();
        }

        $connection = db_connection();
        $teacher_pid = (string) $_SESSION['pID'];

        $role_row = db_fetch_one('SELECT roleID FROM teacher WHERE pID = ? AND status = 1 LIMIT 1', 's', $teacher_pid);
        if (!$role_row) {
            redirect_to_login();
        }

        $teacher_role_id = (int) ($role_row['roleID'] ?? 0);
        $status_row = db_fetch_one('SELECT dh_status FROM thesystem ORDER BY ID DESC LIMIT 1');
        $dh_status = $status_row ? (int) ($status_row['dh_status'] ?? 1) : 1;

        if ($dh_status !== 1 && $teacher_role_id !== 1) {
            redirect_to_login();
        }
    }
}

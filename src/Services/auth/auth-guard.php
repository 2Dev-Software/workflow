<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../app/modules/audit/logger.php';

$redirect_to_login = static function (): void {
    if (function_exists('audit_log')) {
        audit_log('auth', 'ACCESS_DENIED', 'DENY', null, null, 'unauthorized');
    }

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

    $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/\\');
    $loginPath = $basePath === '' ? '/index.php' : $basePath . '/index.php';

    header('Location: ' . $loginPath, true, 302);
    exit();
};

if (empty($_SESSION['pID'])) {
    $redirect_to_login();
}

require_once __DIR__ . '/../../../config/connection.php';

$teacher_pid = (string) $_SESSION['pID'];
$teacher_role_id = 0;

$role_sql = 'SELECT roleID FROM teacher WHERE pID = ? AND status = 1 LIMIT 1';
$role_stmt = mysqli_prepare($connection, $role_sql);

if ($role_stmt === false) {
    error_log('Database Error: ' . mysqli_error($connection));
    $redirect_to_login();
}

mysqli_stmt_bind_param($role_stmt, 's', $teacher_pid);
mysqli_stmt_execute($role_stmt);
$role_result = mysqli_stmt_get_result($role_stmt);
$role_row = $role_result ? mysqli_fetch_assoc($role_result) : null;
mysqli_stmt_close($role_stmt);

if (!$role_row) {
    $redirect_to_login();
}

$teacher_role_id = (int) ($role_row['roleID'] ?? 0);
$dh_status = 1;

$status_sql = 'SELECT dh_status FROM thesystem ORDER BY ID DESC LIMIT 1';
$status_stmt = mysqli_prepare($connection, $status_sql);

if ($status_stmt === false) {
    error_log('Database Error: ' . mysqli_error($connection));
} else {
    mysqli_stmt_execute($status_stmt);
    $status_result = mysqli_stmt_get_result($status_stmt);

    if ($status_row = mysqli_fetch_assoc($status_result)) {
        $dh_status = (int) $status_row['dh_status'];
    }
    mysqli_stmt_close($status_stmt);
}

if ($dh_status !== 1 && $teacher_role_id !== 1) {
    $redirect_to_login();
}

if (function_exists('audit_log')) {
    $method = (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET');
    $action = strtoupper($method) === 'GET' ? 'VIEW' : 'ACTION';
    audit_log('request', $action, 'SUCCESS', null, null, null, [
        'path' => (string) ($_SERVER['REQUEST_URI'] ?? ''),
        'method' => $method,
    ]);
}

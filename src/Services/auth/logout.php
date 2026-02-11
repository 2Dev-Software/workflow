<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$scriptName = basename($_SERVER['SCRIPT_NAME'] ?? '');
$logoutRequested = $scriptName === 'logout.php'
    || (isset($_GET['logout']) && $_GET['logout'] === '1')
    || (isset($_POST['logout']) && $_POST['logout'] === '1');

if ($logoutRequested) {
    require_once __DIR__ . '/../../../app/modules/audit/logger.php';
    $actor_pid = $_SESSION['pID'] ?? null;
    if ($actor_pid) {
        audit_log('auth', 'LOGOUT', 'SUCCESS', 'teacher', $actor_pid, null);
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
}
?>

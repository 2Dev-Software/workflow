<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../security/session.php';
require_once __DIR__ . '/../security/csrf.php';
require_once __DIR__ . '/../services/auth-service.php';
require_once __DIR__ . '/../views/view.php';
require_once __DIR__ . '/../modules/audit/logger.php';

if (!function_exists('auth_show_login')) {
    function auth_show_login(): void
    {
        app_session_start();
        if (!empty($_SESSION['pID'])) {
            redirect_to('/dashboard');
        }

        $alert = flash_get('login_alert');
        view_render('auth/login', [
            'alert' => $alert,
        ]);
    }
}

if (!function_exists('auth_handle_login')) {
    function auth_handle_login(): void
    {
        app_session_start();

        if (!csrf_validate($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            if (request_wants_json()) {
                json_error('ไม่สามารถยืนยันความปลอดภัย กรุณาลองใหม่อีกครั้ง', [], 403);
            }
            flash_set('login_alert', ['type' => 'danger', 'message' => 'ไม่สามารถยืนยันความปลอดภัย กรุณาลองใหม่อีกครั้ง']);
            audit_log('auth', 'LOGIN', 'FAIL', 'teacher', null, 'CSRF invalid');
            redirect_to('/login');
        }

        $pid = trim((string) ($_POST['pid'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

        [$allowed, $lock_message] = auth_check_lockout($pid, $ip);
        if (!$allowed) {
            http_response_code(429);
            if (request_wants_json()) {
                json_error($lock_message ?? 'บัญชีถูกล็อกชั่วคราว', [], 429);
            }
            flash_set('login_alert', ['type' => 'warning', 'message' => $lock_message]);
            audit_log('auth', 'LOGIN', 'DENY', 'teacher', null, 'Locked out', ['pID' => $pid]);
            redirect_to('/login');
        }

        [$ok, $user, $error] = auth_validate_credentials($pid, $password);
        if (!$ok || !$user) {
            auth_record_login_failure($pid, $ip);
            http_response_code(401);
            if (request_wants_json()) {
                json_error($error ?? 'เข้าสู่ระบบไม่สำเร็จ', [], 401);
            }
            flash_set('login_alert', ['type' => 'danger', 'message' => $error ?? 'เข้าสู่ระบบไม่สำเร็จ']);
            audit_log('auth', 'LOGIN', 'FAIL', 'teacher', null, 'Invalid credentials', ['pID' => $pid]);
            redirect_to('/login');
        }

        $status_row = db_fetch_one('SELECT dh_status FROM thesystem ORDER BY ID DESC LIMIT 1');
        $dh_status = $status_row ? (int) ($status_row['dh_status'] ?? 1) : 1;
        $role_id = (int) ($user['roleID'] ?? 0);

        if ($dh_status !== 1 && $role_id !== 1) {
            http_response_code(403);
            if (request_wants_json()) {
                json_error('ระบบยังไม่เปิดให้ใช้งานในขณะนี้', [], 403);
            }
            flash_set('login_alert', ['type' => 'warning', 'message' => 'ระบบยังไม่เปิดให้ใช้งานในขณะนี้']);
            audit_log('auth', 'LOGIN', 'DENY', 'teacher', null, 'System closed', ['pID' => $pid]);
            redirect_to('/login');
        }

        auth_clear_login_failure($pid, $ip);
        session_regenerate_id(true);
        $_SESSION['pID'] = $user['pID'];
        $_SESSION['user_name'] = trim(($user['fname'] ?? '') . ' ' . ($user['lname'] ?? ''));

        audit_log('auth', 'LOGIN', 'SUCCESS', 'teacher', $user['pID'], null, ['request_id' => app_request_id()]);
        if (request_wants_json()) {
            json_success('เข้าสู่ระบบสำเร็จ', ['redirect' => app_url('/dashboard')]);
        }
        redirect_to('/dashboard');
    }
}

if (!function_exists('auth_logout')) {
    function auth_logout(): void
    {
        app_session_start();
        $pid = (string) ($_SESSION['pID'] ?? '');
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        if ($pid !== '') {
            audit_log('auth', 'LOGOUT', 'SUCCESS', 'teacher', $pid, null, ['request_id' => app_request_id()]);
        }
        redirect_to('/login');
    }
}

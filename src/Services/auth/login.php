<?php

$login_alert = $login_alert ?? null;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $set_alert = static function (string $type, string $title, string $message = '', array $extra = []) use (&$login_alert): void {
        $login_alert = array_merge([
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'button_label' => 'ยืนยัน',
            'link' => 'index.php',
        ], $extra);
    };

    require_once __DIR__ . '/../../../app/auth/csrf.php';
    require_once __DIR__ . '/../../../app/services/auth-service.php';
    require_once __DIR__ . '/../../../app/repositories/user-repository.php';
    require_once __DIR__ . '/../../../app/modules/audit/logger.php';

    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        $set_alert('danger', 'ไม่สามารถยืนยันความปลอดภัย', 'กรุณาลองใหม่อีกครั้ง');
        audit_log('auth', 'LOGIN', 'FAIL', 'teacher', null, 'CSRF invalid');

        return;
    }

    $pID = trim($_POST['pID'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($pID === '' || $password === '') {
        http_response_code(400);
        $set_alert('danger', 'เข้าสู่ระบบไม่สำเร็จ', 'กรุณากรอกเลขบัตรประชาชนและรหัสผ่านให้ครบถ้วน');

        return;
    }

    $ip_address = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    [$allowed, $lock_message] = auth_check_lockout($pID, $ip_address);

    if (!$allowed) {
        http_response_code(429);
        $set_alert('warning', 'บัญชีถูกล็อกชั่วคราว', (string) ($lock_message ?? 'กรุณาลองใหม่อีกครั้งในภายหลัง'));
        audit_log('auth', 'LOGIN', 'DENY', 'teacher', null, 'Locked out', ['pID' => $pID]);

        return;
    }

    [$valid, $user, $auth_error] = auth_validate_credentials($pID, $password);

    if (!$valid || !$user) {
        auth_record_login_failure($pID, $ip_address);
        http_response_code(401);
        $set_alert('danger', 'เข้าสู่ระบบไม่สำเร็จ', (string) ($auth_error ?? 'กรุณาตรวจสอบเลขบัตรประชาชนหรือรหัสผ่านอีกครั้ง'));
        audit_log('auth', 'LOGIN', 'FAIL', 'teacher', null, 'Invalid credentials', ['pID' => $pID]);

        return;
    }

    $status_row = db_fetch_one('SELECT dh_status FROM thesystem ORDER BY ID DESC LIMIT 1');
    $dh_status = $status_row ? (int) ($status_row['dh_status'] ?? 1) : 1;
    $role_id = (int) ($user['roleID'] ?? 0);

    if ($dh_status !== 1 && $role_id !== 1) {
        http_response_code(403);

        $status_titles = [
            2 => ['ระบบปิดปรับปรุง', 'ขณะนี้ระบบอยู่ระหว่างปรับปรุง กรุณาลองใหม่ภายหลัง'],
            3 => ['ระบบปิดชั่วคราว', 'ขณะนี้ระบบปิดชั่วคราว กรุณาติดต่อผู้ดูแลระบบ'],
        ];
        $status_alert = $status_titles[$dh_status] ?? ['ระบบไม่พร้อมใช้งาน', 'ขณะนี้ระบบไม่พร้อมใช้งาน'];
        $set_alert('warning', $status_alert[0], $status_alert[1]);
        audit_log('auth', 'LOGIN', 'DENY', 'teacher', null, 'System closed', ['pID' => $pID, 'dh_status' => $dh_status]);

        return;
    }

    auth_clear_login_failure($pID, $ip_address);
    session_regenerate_id(true);
    $_SESSION['pID'] = (string) $user['pID'];
    $_SESSION['user_name'] = trim((string) ($user['fname'] ?? '') . ' ' . (string) ($user['lname'] ?? ''));
    user_touch_last_login((string) $user['pID']);

    audit_log('auth', 'LOGIN', 'SUCCESS', 'teacher', (string) $user['pID'], null);

    $set_alert(
        'success',
        'เข้าสู่ระบบสำเร็จ',
        'กำลังนำท่านไปยังหน้าหลัก...',
        [
            'auto' => true,
            'hide_button' => true,
            'redirect' => 'dashboard.php',
            'delay_ms' => 1000,
        ]
    );

    return;
}

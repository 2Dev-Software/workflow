<?php

$login_alert = $login_alert ?? null;

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

    if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        $set_alert('danger', 'ไม่สามารถยืนยันความปลอดภัย', 'กรุณาลองใหม่อีกครั้ง');

        return;
    }

    require_once __DIR__ . '/../../../config/connection.php';
    require_once __DIR__ . '/../../../app/auth/password.php';
    require_once __DIR__ . '/../../../app/modules/audit/logger.php';

    $pID = trim($_POST['pID'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($pID === '' || $password === '') {
        http_response_code(400);
        $set_alert('danger', 'เข้าสู่ระบบไม่สำเร็จ', 'กรุณากรอกเลขบัตรประชาชนและรหัสผ่านให้ครบถ้วน');

        return;
    }

    $auth_password_column = auth_password_column($connection);
    $sql = "SELECT pID, roleID FROM teacher WHERE pID = ? AND {$auth_password_column} = ? AND status = 1 LIMIT 1";
    $stmt = mysqli_prepare($connection, $sql);

    if ($stmt === false) {
        error_log('Database Error: ' . mysqli_error($connection));
        http_response_code(500);
        $set_alert('danger', 'ระบบขัดข้อง', 'ไม่สามารถเข้าสู่ระบบได้ในขณะนี้');

        return;
    }

    mysqli_stmt_bind_param($stmt, 'ss', $pID, $password);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    if (!$row) {
        http_response_code(401);
        $set_alert('danger', 'เข้าสู่ระบบไม่สำเร็จ', 'กรุณาตรวจสอบเลขบัตรประชาชนหรือรหัสผ่านอีกครั้ง');
        audit_log('auth', 'LOGIN', 'FAIL', 'teacher', null, 'Invalid credentials', ['pID' => $pID]);

        return;
    }

    $role_id = (int) ($row['roleID'] ?? 0);
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

    if ($dh_status !== 1 && $role_id !== 1) {
        http_response_code(403);

        $status_titles = [
            2 => ['ระบบปิดปรับปรุง', 'ขณะนี้ระบบอยู่ระหว่างปรับปรุง กรุณาลองใหม่ภายหลัง'],
            3 => ['ระบบปิดชั่วคราว', 'ขณะนี้ระบบปิดชั่วคราว กรุณาติดต่อผู้ดูแลระบบ'],
        ];
        $status_alert = $status_titles[$dh_status] ?? ['ระบบไม่พร้อมใช้งาน', 'ขณะนี้ระบบไม่พร้อมใช้งาน'];
        $set_alert('warning', $status_alert[0], $status_alert[1]);

        return;
    }

    session_regenerate_id(true);
    $_SESSION['pID'] = $row['pID'];

    audit_log('auth', 'LOGIN', 'SUCCESS', 'teacher', $row['pID'], null);

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

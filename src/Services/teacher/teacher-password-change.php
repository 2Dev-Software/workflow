<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['change_password'])) {
    return;
}

require_once __DIR__ . '/../../../config/connection.php';
require_once __DIR__ . '/../../../app/auth/password.php';
require_once __DIR__ . '/../../../app/modules/audit/logger.php';

$redirect_url = 'profile.php?tab=password';

$set_profile_alert = static function (string $type, string $title, string $message = '') use ($redirect_url): void {
    $_SESSION['profile_alert'] = [
        'type' => $type,
        'title' => $title,
        'message' => $message,
        'link' => $redirect_url,
    ];
};

$teacher_pid = (string) ($_SESSION['pID'] ?? '');

if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    if (function_exists('audit_log')) {
        audit_log('security', 'CSRF_FAIL', 'DENY', 'teacher', $teacher_pid, 'password_change');
    }
    $set_profile_alert('danger', 'ไม่สามารถยืนยันความปลอดภัย', 'กรุณาลองใหม่อีกครั้ง');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if ($current_password === '' || $new_password === '' || $confirm_password === '') {
    $set_profile_alert('danger', 'บันทึกไม่สำเร็จ', 'กรุณากรอกข้อมูลให้ครบถ้วน');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

if ($new_password !== $confirm_password) {
    $set_profile_alert('danger', 'บันทึกไม่สำเร็จ', 'รหัสผ่านใหม่ไม่ตรงกัน');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

if ($teacher_pid === '') {
    header('Location: index.php', true, 302);
    exit();
}

$auth_password_column = auth_password_column($connection);
$select_sql = 'SELECT ' . $auth_password_column . ' AS password_value FROM teacher WHERE pID = ? AND status = 1 LIMIT 1';
$select_stmt = mysqli_prepare($connection, $select_sql);

if ($select_stmt === false) {
    error_log('Database Error: ' . mysqli_error($connection));
    if (function_exists('audit_log')) {
        audit_log('profile', 'PASSWORD_CHANGE', 'FAIL', 'teacher', $teacher_pid, 'select_failed');
    }
    $set_profile_alert('danger', 'ระบบขัดข้อง', 'ไม่สามารถเปลี่ยนรหัสผ่านได้ในขณะนี้');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

mysqli_stmt_bind_param($select_stmt, 's', $teacher_pid);
mysqli_stmt_execute($select_stmt);
$select_result = mysqli_stmt_get_result($select_stmt);
$teacher_row = $select_result ? mysqli_fetch_assoc($select_result) : null;
mysqli_stmt_close($select_stmt);

if (!$teacher_row) {
    $set_profile_alert('danger', 'ไม่พบข้อมูลผู้ใช้', 'กรุณาลองใหม่อีกครั้ง');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

if (!hash_equals((string) ($teacher_row['password_value'] ?? ''), (string) $current_password)) {
    if (function_exists('audit_log')) {
        audit_log('profile', 'PASSWORD_CHANGE', 'FAIL', 'teacher', $teacher_pid, 'invalid_current_password');
    }
    $set_profile_alert('danger', 'บันทึกไม่สำเร็จ', 'รหัสผ่านเดิมไม่ถูกต้อง');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

if (hash_equals((string) ($teacher_row['password_value'] ?? ''), (string) $new_password)) {
    $set_profile_alert('warning', 'ไม่มีการเปลี่ยนแปลง', 'รหัสผ่านใหม่ต้องไม่ซ้ำกับรหัสผ่านเดิม');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

$update_sql = 'UPDATE teacher SET ' . $auth_password_column . ' = ? WHERE pID = ? AND status = 1';
$update_stmt = mysqli_prepare($connection, $update_sql);

if ($update_stmt === false) {
    error_log('Database Error: ' . mysqli_error($connection));
    if (function_exists('audit_log')) {
        audit_log('profile', 'PASSWORD_CHANGE', 'FAIL', 'teacher', $teacher_pid, 'prepare_failed');
    }
    $set_profile_alert('danger', 'ระบบขัดข้อง', 'ไม่สามารถเปลี่ยนรหัสผ่านได้ในขณะนี้');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

mysqli_stmt_bind_param($update_stmt, 'ss', $new_password, $teacher_pid);
if (mysqli_stmt_execute($update_stmt) === false) {
    mysqli_stmt_close($update_stmt);
    if (function_exists('audit_log')) {
        audit_log('profile', 'PASSWORD_CHANGE', 'FAIL', 'teacher', $teacher_pid, 'execute_failed');
    }
    $set_profile_alert('danger', 'ระบบขัดข้อง', 'ไม่สามารถเปลี่ยนรหัสผ่านได้ในขณะนี้');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}
mysqli_stmt_close($update_stmt);

if (function_exists('audit_log')) {
    audit_log('profile', 'PASSWORD_CHANGE', 'SUCCESS', 'teacher', $teacher_pid);
}

$set_profile_alert('success', 'บันทึกสำเร็จ', 'เปลี่ยนรหัสผ่านเรียบร้อยแล้ว');
header('Location: ' . $redirect_url, true, 303);
exit();

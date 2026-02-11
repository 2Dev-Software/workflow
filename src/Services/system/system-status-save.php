<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['dh_status_save'])) {
    return;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../app/db/connection.php';
require_once __DIR__ . '/../../../app/modules/audit/logger.php';

$connection = db_connection();
if (!($connection instanceof mysqli)) {
    error_log('Database Error: invalid connection');
    $_SESSION['setting_alert'] = [
        'type' => 'danger',
        'title' => 'ระบบขัดข้อง',
        'message' => 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้ในขณะนี้',
    ];
    header('Location: setting.php?tab=settingSystem', true, 303);
    exit();
}

$redirect_url = 'setting.php?tab=settingSystem';

$set_setting_alert = static function (string $type, string $title, string $message = '') use ($redirect_url): void {
    $_SESSION['setting_alert'] = [
        'type' => $type,
        'title' => $title,
        'message' => $message,
    ];
};

if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $set_setting_alert('danger', 'ไม่สามารถยืนยันความปลอดภัย', 'กรุณาลองใหม่อีกครั้ง');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

$dh_status_input = filter_input(INPUT_POST, 'dh_status', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1, 'max_range' => 3],
]);

if ($dh_status_input === false || $dh_status_input === null) {
    $set_setting_alert('danger', 'ข้อมูลไม่ถูกต้อง', 'กรุณาเลือกสถานะระบบใหม่');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

$select_sql = 'SELECT ID, dh_status FROM thesystem ORDER BY ID DESC LIMIT 1';
$select_result = mysqli_query($connection, $select_sql);

if ($select_result === false) {
    error_log('Database Error: ' . mysqli_error($connection));
    if (function_exists('audit_log')) {
        audit_log('system', 'UPDATE_STATUS', 'FAIL', 'thesystem', null, 'select_failed');
    }
    $set_setting_alert('danger', 'ระบบขัดข้อง', 'ไม่สามารถบันทึกสถานะระบบได้ในขณะนี้');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

$system_row = mysqli_fetch_assoc($select_result);
mysqli_free_result($select_result);

if (!$system_row) {
    $set_setting_alert('danger', 'ไม่พบข้อมูลระบบ', 'กรุณาลองใหม่อีกครั้ง');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

$system_id = (int) $system_row['ID'];
$current_status = (int) ($system_row['dh_status'] ?? 0);

if ($current_status === $dh_status_input) {
    $set_setting_alert('warning', 'ไม่มีการเปลี่ยนแปลง', 'สถานะระบบยังเป็นค่าเดิม');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

$update_sql = 'UPDATE thesystem SET dh_status = ? WHERE ID = ?';
$update_stmt = mysqli_prepare($connection, $update_sql);

if ($update_stmt === false) {
    error_log('Database Error: ' . mysqli_error($connection));
    if (function_exists('audit_log')) {
        audit_log('system', 'UPDATE_STATUS', 'FAIL', 'thesystem', $system_id, 'prepare_failed');
    }
    $set_setting_alert('danger', 'ระบบขัดข้อง', 'ไม่สามารถบันทึกสถานะระบบได้ในขณะนี้');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

mysqli_stmt_bind_param($update_stmt, 'ii', $dh_status_input, $system_id);
mysqli_stmt_execute($update_stmt);
mysqli_stmt_close($update_stmt);

if (function_exists('audit_log')) {
    audit_log('system', 'UPDATE_STATUS', 'SUCCESS', 'thesystem', $system_id, null, [
        'dh_status' => $dh_status_input,
    ]);
}

$set_setting_alert('success', 'บันทึกสำเร็จ', 'อัปเดตสถานะระบบเรียบร้อยแล้ว');
header('Location: ' . $redirect_url, true, 303);
exit();

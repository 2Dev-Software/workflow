<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['dh_year_save'])) {
    return;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../app/db/connection.php';

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

$dh_year_input = filter_input(INPUT_POST, 'dh_year', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);

if ($dh_year_input === false || $dh_year_input === null) {
    $set_setting_alert('danger', 'ข้อมูลไม่ถูกต้อง', 'กรุณาเลือกปีสารบรรณใหม่');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

$currentThaiYear = (int) date('Y') + 544;
$startThaiYear = 2568;
if ($dh_year_input < $startThaiYear || $dh_year_input > $currentThaiYear) {
    $set_setting_alert('danger', 'ปีสารบรรณไม่ถูกต้อง', 'กรุณาเลือกปีสารบรรณที่ถูกต้อง');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

$select_sql = 'SELECT ID, dh_year FROM thesystem ORDER BY ID DESC LIMIT 1';
$select_result = mysqli_query($connection, $select_sql);

if ($select_result === false) {
    error_log('Database Error: ' . mysqli_error($connection));
    $set_setting_alert('danger', 'ระบบขัดข้อง', 'ไม่สามารถบันทึกปีสารบรรณได้ในขณะนี้');
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
$current_year = (int) ($system_row['dh_year'] ?? 0);

if ($current_year === $dh_year_input) {
    $set_setting_alert('warning', 'ไม่มีการเปลี่ยนแปลง', 'ปีสารบรรณยังเป็นค่าเดิม');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

$update_sql = 'UPDATE thesystem SET dh_year = ? WHERE ID = ?';
$update_stmt = mysqli_prepare($connection, $update_sql);

if ($update_stmt === false) {
    error_log('Database Error: ' . mysqli_error($connection));
    $set_setting_alert('danger', 'ระบบขัดข้อง', 'ไม่สามารถบันทึกปีสารบรรณได้ในขณะนี้');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

mysqli_stmt_bind_param($update_stmt, 'ii', $dh_year_input, $system_id);
mysqli_stmt_execute($update_stmt);
mysqli_stmt_close($update_stmt);

$set_setting_alert('success', 'บันทึกสำเร็จ', 'อัปเดตปีสารบรรณเรียบร้อยแล้ว');
header('Location: ' . $redirect_url, true, 303);
exit();

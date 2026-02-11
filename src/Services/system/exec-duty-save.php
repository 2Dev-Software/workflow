<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['exec_duty_save'])) {
    return;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../config/connection.php';
require_once __DIR__ . '/../../../app/modules/audit/logger.php';
require_once __DIR__ . '/../../../app/modules/system/system.php';

$redirect_url = 'setting.php?tab=settingDuty';

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

$exec_duty_pid = $_POST['exec_duty_pid'] ?? '';
if (is_array($exec_duty_pid)) {
    $exec_duty_pid = reset($exec_duty_pid) ?: '';
}
$exec_duty_pid = trim((string) $exec_duty_pid);

if ($exec_duty_pid === '' || !preg_match('/^\d{1,13}$/', $exec_duty_pid)) {
    $set_setting_alert('danger', 'ข้อมูลไม่ถูกต้อง', 'กรุณาเลือกผู้บริหารก่อนบันทึก');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

$teacher_sql = 'SELECT positionID FROM teacher WHERE pID = ? AND status = 1 AND dID = 12 LIMIT 1';
$teacher_stmt = mysqli_prepare($connection, $teacher_sql);

if ($teacher_stmt === false) {
    error_log('Database Error: ' . mysqli_error($connection));
    $set_setting_alert('danger', 'ระบบขัดข้อง', 'ไม่สามารถบันทึกข้อมูลผู้บริหารได้ในขณะนี้');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

mysqli_stmt_bind_param($teacher_stmt, 's', $exec_duty_pid);
mysqli_stmt_execute($teacher_stmt);
$teacher_result = mysqli_stmt_get_result($teacher_stmt);
$teacher_row = $teacher_result ? mysqli_fetch_assoc($teacher_result) : null;
mysqli_stmt_close($teacher_stmt);

if (!$teacher_row) {
    $set_setting_alert('danger', 'ไม่พบข้อมูลผู้บริหาร', 'กรุณาเลือกผู้บริหารใหม่อีกครั้ง');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

$director_pid = system_get_director_pid();
$duty_status = ($director_pid !== null && $director_pid !== '' && $exec_duty_pid === $director_pid) ? 1 : 2;

$teacher_position_id = (int) ($teacher_row['positionID'] ?? 0);
$allowed_positions = array_merge([1], system_position_deputy_ids($connection));
$allowed_positions = array_values(array_unique(array_filter($allowed_positions)));
if (!in_array($teacher_position_id, $allowed_positions, true)) {
    $set_setting_alert('danger', 'ข้อมูลไม่ถูกต้อง', 'กรุณาเลือกผู้บริหารก่อนบันทึก');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

if (mysqli_begin_transaction($connection) === false) {
    error_log('Database Error: ' . mysqli_error($connection));
    $set_setting_alert('danger', 'ระบบขัดข้อง', 'ไม่สามารถบันทึกข้อมูลผู้บริหารได้ในขณะนี้');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

try {
    $current_sql = 'SELECT dutyLogID, pID, dutyStatus FROM dh_exec_duty_logs WHERE dutyStatus IN (1, 2) ORDER BY dutyLogID DESC LIMIT 1 FOR UPDATE';
    $current_result = mysqli_query($connection, $current_sql);
    if ($current_result === false) {
        throw new RuntimeException('Failed to fetch current duty log.');
    }

    $current_row = mysqli_fetch_assoc($current_result);
    mysqli_free_result($current_result);

    if ($current_row) {
        $current_pid = (string) $current_row['pID'];
        $current_status = (int) $current_row['dutyStatus'];

        if ($current_pid === $exec_duty_pid && $current_status === $duty_status) {
            mysqli_commit($connection);
            $set_setting_alert('warning', 'ไม่มีการเปลี่ยนแปลง', 'ข้อมูลการปฏิบัติราชการยังเป็นรายการเดิม');
            header('Location: ' . $redirect_url, true, 303);
            exit();
        }
    }

    $event_time = date('Y-m-d H:i:s');

    $reset_sql = 'UPDATE dh_exec_duty_logs SET dutyStatus = 0, end_at = ? WHERE dutyStatus IN (1, 2)';
    $reset_stmt = mysqli_prepare($connection, $reset_sql);
    if ($reset_stmt === false) {
        throw new RuntimeException('Failed to prepare duty reset.');
    }

    mysqli_stmt_bind_param($reset_stmt, 's', $event_time);
    if (mysqli_stmt_execute($reset_stmt) === false) {
        mysqli_stmt_close($reset_stmt);
        throw new RuntimeException('Failed to reset duty logs.');
    }
    mysqli_stmt_close($reset_stmt);

    $insert_sql = 'INSERT INTO dh_exec_duty_logs (pID, dutyStatus, created_at) VALUES (?, ?, ?)';
    $insert_stmt = mysqli_prepare($connection, $insert_sql);
    if ($insert_stmt === false) {
        throw new RuntimeException('Failed to prepare duty insert.');
    }

    mysqli_stmt_bind_param($insert_stmt, 'sis', $exec_duty_pid, $duty_status, $event_time);
    if (mysqli_stmt_execute($insert_stmt) === false) {
        mysqli_stmt_close($insert_stmt);
        throw new RuntimeException('Failed to insert duty log.');
    }

    mysqli_stmt_close($insert_stmt);
    mysqli_commit($connection);
} catch (Throwable $e) {
    mysqli_rollback($connection);
    error_log('Database Exception: ' . $e->getMessage());
    $set_setting_alert('danger', 'ระบบขัดข้อง', 'ไม่สามารถบันทึกข้อมูลผู้บริหารได้ในขณะนี้');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

$set_setting_alert('success', 'บันทึกสำเร็จ', 'อัปเดตการปฏิบัติราชการของผู้บริหารเรียบร้อยแล้ว');
audit_log('system', 'ACTING_DIRECTOR_ASSIGN', 'SUCCESS', 'dh_exec_duty_logs', null, null, [
    'pID' => $exec_duty_pid,
    'status' => $duty_status,
]);
header('Location: ' . $redirect_url, true, 303);
exit();

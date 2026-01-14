<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['exec_duty_save'])) {
    return;
}

require_once __DIR__ . '/../../../config/connection.php';

if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    http_response_code(403);
    exit('403 Forbidden: Invalid Security Token');
}

$exec_duty_pid = $_POST['exec_duty_pid'] ?? '';
if (is_array($exec_duty_pid)) {
    $exec_duty_pid = reset($exec_duty_pid) ?: '';
}
$exec_duty_pid = trim((string) $exec_duty_pid);

if ($exec_duty_pid === '' || !preg_match('/^\d{1,13}$/', $exec_duty_pid)) {
    http_response_code(400);
    exit('400 Bad Request: Invalid Executive ID');
}

$teacher_sql = 'SELECT positionID FROM teacher WHERE pID = ? AND status = 1 AND dID = 12 LIMIT 1';
$teacher_stmt = mysqli_prepare($connection, $teacher_sql);

if ($teacher_stmt === false) {
    error_log('Database Error: ' . mysqli_error($connection));
    http_response_code(500);
    exit('500 Internal Server Error');
}

mysqli_stmt_bind_param($teacher_stmt, 's', $exec_duty_pid);
mysqli_stmt_execute($teacher_stmt);
$teacher_result = mysqli_stmt_get_result($teacher_stmt);
$teacher_row = $teacher_result ? mysqli_fetch_assoc($teacher_result) : null;
mysqli_stmt_close($teacher_stmt);

if (!$teacher_row) {
    http_response_code(404);
    exit('404 Not Found');
}

$position_id = (int) $teacher_row['positionID'];
$duty_status = $position_id === 1 ? 1 : 2;

mysqli_begin_transaction($connection);

$redirect_url = 'setting.php?tab=settingDuty';

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
    http_response_code(500);
    exit('500 Internal Server Error');
}

header('Location: ' . $redirect_url, true, 303);
exit();

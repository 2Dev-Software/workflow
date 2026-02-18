<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    return;
}

require_once __DIR__ . '/../../../config/connection.php';
require_once __DIR__ . '/room-booking-utils.php';
require_once __DIR__ . '/../../../app/modules/audit/logger.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$redirect_url = 'room-booking-approval.php';
$return_url = trim((string) ($_POST['return_url'] ?? ''));

if ($return_url !== '' && strpos($return_url, $redirect_url) === 0) {
    $redirect_url = $return_url;
}

$set_room_booking_approval_alert = static function (string $type, string $title, string $message = '') use ($redirect_url): void {
    $_SESSION['room_booking_approval_alert'] = [
        'type' => $type,
        'title' => $title,
        'message' => $message,
        'button_label' => 'ยืนยัน',
        'redirect' => '',
        'delay_ms' => 0,
    ];
    header('Location: ' . $redirect_url, true, 303);
    exit();
};

$raw_action = trim((string) ($_POST['approval_action'] ?? ''));
$raw_booking_id = (int) ($_POST['room_booking_id'] ?? 0);

$connection = $connection ?? ($GLOBALS['connection'] ?? null);

if (!($connection instanceof mysqli)) {
    if (function_exists('audit_log')) {
        audit_log('room', 'APPROVAL', 'FAIL', 'dh_room_bookings', $raw_booking_id > 0 ? $raw_booking_id : null, 'db_connection_missing');
    }
    $set_room_booking_approval_alert('danger', 'ระบบขัดข้อง', 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้');
}

if (
    empty($_POST['csrf_token']) ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals((string) $_SESSION['csrf_token'], (string) $_POST['csrf_token'])
) {
    if (function_exists('audit_log')) {
        audit_log('security', 'CSRF_FAIL', 'DENY', 'dh_room_bookings', $raw_booking_id > 0 ? $raw_booking_id : null, 'room_booking_approval_actions', [
            'action' => $raw_action,
        ]);
    }
    $set_room_booking_approval_alert('danger', 'ไม่สามารถยืนยันความปลอดภัย', 'กรุณาลองใหม่อีกครั้ง');
}

$action = $raw_action;

if (!in_array($action, ['approve', 'reject'], true)) {
    if (function_exists('audit_log')) {
        audit_log('room', 'APPROVAL', 'FAIL', 'dh_room_bookings', $raw_booking_id > 0 ? $raw_booking_id : null, 'invalid_action', [
            'action' => $action,
        ]);
    }
    $set_room_booking_approval_alert('danger', 'คำสั่งไม่ถูกต้อง', 'กรุณาลองใหม่อีกครั้ง');
}

$booking_id = $raw_booking_id;

if ($booking_id <= 0) {
    if (function_exists('audit_log')) {
        audit_log('room', 'APPROVAL', 'FAIL', 'dh_room_bookings', null, 'invalid_booking_id');
    }
    $set_room_booking_approval_alert('danger', 'ข้อมูลไม่ถูกต้อง', 'ไม่พบรายการจองที่ต้องการ');
}

$approver_pid = trim((string) ($_SESSION['pID'] ?? ''));

if ($approver_pid === '') {
    if (function_exists('audit_log')) {
        audit_log('security', 'AUTH_REQUIRED', 'DENY', null, null, 'room_booking_approval_actions');
    }
    header('Location: index.php', true, 302);
    exit();
}

$current_status = null;

try {
    $check_sql = 'SELECT status FROM dh_room_bookings WHERE roomBookingID = ? AND deletedAt IS NULL LIMIT 1';
    $check_stmt = mysqli_prepare($connection, $check_sql);

    mysqli_stmt_bind_param($check_stmt, 'i', $booking_id);
    mysqli_stmt_execute($check_stmt);

    $check_result = mysqli_stmt_get_result($check_stmt);
    $check_row = $check_result ? mysqli_fetch_assoc($check_result) : null;

    mysqli_stmt_close($check_stmt);

    if (!$check_row) {
        if (function_exists('audit_log')) {
            audit_log('room', 'APPROVAL', 'FAIL', 'dh_room_bookings', $booking_id, 'booking_not_found');
        }
        $set_room_booking_approval_alert('warning', 'ไม่พบรายการ', 'รายการจองนี้อาจถูกลบไปแล้ว');
    }

    $current_status = room_booking_status_to_int($connection, $check_row['status'] ?? 0);
} catch (mysqli_sql_exception $exception) {
    error_log('Database Exception: ' . $exception->getMessage());

    if (function_exists('audit_log')) {
        audit_log('room', 'APPROVAL', 'FAIL', 'dh_room_bookings', $booking_id, 'status_check_failed');
    }
    $set_room_booking_approval_alert('danger', 'ระบบขัดข้อง', 'ไม่สามารถตรวจสอบรายการจองได้ในขณะนี้');
}

$next_status = $action === 'approve' ? 1 : 2;
$status_param = room_booking_status_to_db($connection, $next_status);

$approved_at = date('Y-m-d H:i:s');

$update_sql = 'UPDATE dh_room_bookings
    SET status = ?, approvedByPID = ?, approvedAt = ?, updatedAt = CURRENT_TIMESTAMP
    WHERE roomBookingID = ? AND deletedAt IS NULL';

try {
    $update_stmt = mysqli_prepare($connection, $update_sql);

    $bind_values = [
        $status_param['value'],
        $approver_pid,
        $approved_at,
        $booking_id,
    ];

    $types = $status_param['type'] . 'ssi';

    $bind_params = [];
    $bind_params[] = $update_stmt;
    $bind_params[] = $types;

    foreach ($bind_values as $i => $v) {
        $bind_params[] = &$bind_values[$i];
    }

    call_user_func_array('mysqli_stmt_bind_param', $bind_params);

    mysqli_stmt_execute($update_stmt);

    $affected = mysqli_stmt_affected_rows($update_stmt);
    mysqli_stmt_close($update_stmt);

    if ($affected <= 0) {
        if (function_exists('audit_log')) {
            audit_log('room', $action === 'approve' ? 'APPROVE' : 'REJECT', 'FAIL', 'dh_room_bookings', $booking_id, 'no_rows_affected', [
                'fromStatus' => $current_status,
                'toStatus' => $next_status,
            ]);
        }
        $set_room_booking_approval_alert('warning', 'ไม่มีการเปลี่ยนแปลง', 'ข้อมูลรายการยังเหมือนเดิม');
    }

    if ($action === 'approve') {
        if (function_exists('audit_log')) {
            audit_log('room', 'APPROVE', 'SUCCESS', 'dh_room_bookings', $booking_id);
        }
        $set_room_booking_approval_alert('success', 'อนุมัติสำเร็จ', 'บันทึกผลการอนุมัติเรียบร้อยแล้ว');
    }

    if (function_exists('audit_log')) {
        audit_log('room', 'REJECT', 'SUCCESS', 'dh_room_bookings', $booking_id);
    }
    $set_room_booking_approval_alert('success', 'บันทึกสำเร็จ', 'บันทึกการไม่อนุมัติเรียบร้อยแล้ว');
} catch (mysqli_sql_exception $exception) {
    error_log('Database Exception: ' . $exception->getMessage());

    if (function_exists('audit_log')) {
        audit_log('room', $action === 'approve' ? 'APPROVE' : 'REJECT', 'FAIL', 'dh_room_bookings', $booking_id, $exception->getMessage());
    }
    $set_room_booking_approval_alert('danger', 'ระบบขัดข้อง', 'ไม่สามารถบันทึกผลการพิจารณาได้ในขณะนี้');
}

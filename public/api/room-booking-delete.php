<?php

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../app/modules/audit/logger.php';

$render_alert_response = static function (
    string $type,
    string $title,
    string $message,
    int $status_code,
    array $extra = []
): void {
    $alert = [
        'type' => $type,
        'title' => $title,
        'message' => $message,
        'button_label' => 'ยืนยัน',
        'redirect' => '',
        'delay_ms' => 0,
    ];

    ob_start();
    require __DIR__ . '/../components/x-alert.php';
    $alert_html = ob_get_clean();

    http_response_code($status_code);
    echo json_encode(array_merge([
        'message' => $message,
        'html' => $alert_html,
    ], $extra), JSON_UNESCAPED_UNICODE);
    exit();
};

$store_success_alert = static function (string $title, string $message): void {
    $_SESSION['room_booking_alert'] = [
        'type' => 'success',
        'title' => $title,
        'message' => $message,
        'button_label' => 'ยืนยัน',
        'redirect' => '',
        'delay_ms' => 0,
    ];
};

if (empty($_SESSION['pID'])) {
    if (function_exists('audit_log')) {
        audit_log('security', 'AUTH_REQUIRED', 'DENY', null, null, 'room_booking_delete_api');
    }
    $render_alert_response('danger', 'กรุณาเข้าสู่ระบบอีกครั้ง', 'ไม่พบข้อมูลผู้ใช้งาน', 401, [
        'error' => 'unauthorized',
    ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $render_alert_response('danger', 'ไม่รองรับคำขอนี้', 'โปรดลองใหม่อีกครั้ง', 405, [
        'error' => 'method_not_allowed',
    ]);
}

$raw_input = file_get_contents('php://input');
$payload = json_decode($raw_input, true);

if (!is_array($payload)) {
    $payload = $_POST;
}

$csrf_token = (string) ($payload['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));

if ($csrf_token === '' || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
    if (function_exists('audit_log')) {
        audit_log('security', 'CSRF_FAIL', 'DENY', 'dh_room_bookings', null, 'room_booking_delete_api', [
            'booking_id' => $payload['booking_id'] ?? null,
        ]);
    }
    $render_alert_response('danger', 'ไม่สามารถยืนยันความปลอดภัย', 'กรุณาลองใหม่อีกครั้ง', 403, [
        'error' => 'invalid_csrf',
    ]);
}

$booking_id = filter_var($payload['booking_id'] ?? null, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);

if (!$booking_id) {
    if (function_exists('audit_log')) {
        audit_log('room', 'DELETE', 'FAIL', 'dh_room_bookings', null, 'invalid_payload', [
            'booking_id' => $payload['booking_id'] ?? null,
        ]);
    }
    $render_alert_response('danger', 'ข้อมูลไม่ถูกต้อง', 'กรุณาลองใหม่อีกครั้ง', 400, [
        'error' => 'invalid_payload',
    ]);
}

require_once __DIR__ . '/../../config/connection.php';

$select_sql = 'SELECT roomBookingID, requesterPID, status FROM dh_room_bookings WHERE roomBookingID = ? AND deletedAt IS NULL LIMIT 1';
$select_stmt = mysqli_prepare($connection, $select_sql);

if ($select_stmt === false) {
    error_log('Database Error: ' . mysqli_error($connection));

    if (function_exists('audit_log')) {
        audit_log('room', 'DELETE', 'FAIL', 'dh_room_bookings', $booking_id, 'db_error', [
            'step' => 'select_prepare_failed',
        ]);
    }
    $render_alert_response('danger', 'ระบบขัดข้อง', 'กรุณาลองใหม่อีกครั้ง', 500, [
        'error' => 'db_error',
    ]);
}

mysqli_stmt_bind_param($select_stmt, 'i', $booking_id);
mysqli_stmt_execute($select_stmt);
$select_result = mysqli_stmt_get_result($select_stmt);
$booking_row = $select_result ? mysqli_fetch_assoc($select_result) : null;
mysqli_stmt_close($select_stmt);

if (!$booking_row) {
    if (function_exists('audit_log')) {
        audit_log('room', 'DELETE', 'FAIL', 'dh_room_bookings', $booking_id, 'booking_not_found');
    }
    $render_alert_response('danger', 'ไม่พบรายการจอง', 'รายการนี้อาจถูกลบไปแล้ว', 404, [
        'error' => 'not_found',
    ]);
}

$requester_pid = (string) ($_SESSION['pID'] ?? '');

if ((string) ($booking_row['requesterPID'] ?? '') !== $requester_pid) {
    if (function_exists('audit_log')) {
        audit_log('room', 'DELETE', 'DENY', 'dh_room_bookings', $booking_id, 'not_owner');
    }
    $render_alert_response('danger', 'ไม่มีสิทธิ์ลบรายการนี้', 'โปรดลองใหม่อีกครั้ง', 403, [
        'error' => 'forbidden',
    ]);
}

$update_sql = 'UPDATE dh_room_bookings SET deletedAt = NOW(), updatedAt = NOW() WHERE roomBookingID = ?';
$update_stmt = mysqli_prepare($connection, $update_sql);

if ($update_stmt === false) {
    error_log('Database Error: ' . mysqli_error($connection));

    if (function_exists('audit_log')) {
        audit_log('room', 'DELETE', 'FAIL', 'dh_room_bookings', $booking_id, 'db_error', [
            'step' => 'update_prepare_failed',
        ]);
    }
    $render_alert_response('danger', 'ระบบขัดข้อง', 'กรุณาลองใหม่อีกครั้ง', 500, [
        'error' => 'db_error',
    ]);
}

mysqli_stmt_bind_param($update_stmt, 'i', $booking_id);

if (mysqli_stmt_execute($update_stmt) === false) {
    mysqli_stmt_close($update_stmt);

    if (function_exists('audit_log')) {
        audit_log('room', 'DELETE', 'FAIL', 'dh_room_bookings', $booking_id, 'db_error', [
            'step' => 'update_execute_failed',
        ]);
    }
    $render_alert_response('danger', 'ไม่สามารถลบรายการได้', 'กรุณาลองใหม่อีกครั้ง', 500, [
        'error' => 'db_error',
    ]);
}

mysqli_stmt_close($update_stmt);

if (function_exists('audit_log')) {
    audit_log('room', 'DELETE', 'SUCCESS', 'dh_room_bookings', $booking_id, null, [
        'previousStatus' => $booking_row['status'] ?? null,
    ]);
}

$store_success_alert('ลบรายการเรียบร้อยแล้ว', 'รายการจองถูกลบออกจากระบบแล้ว');
echo json_encode([
    'success' => true,
    'reload' => true,
], JSON_UNESCAPED_UNICODE);
exit();

<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'ok' => false,
        'html' => '',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../../src/Services/room/room-booking-utils.php';
require_once __DIR__ . '/../../app/modules/audit/logger.php';

$audit_payload_base = [
    'roomID' => trim((string) ($_POST['roomID'] ?? '')),
    'dh_year' => (int) ($_POST['dh_year'] ?? 0),
    'startDate' => trim((string) ($_POST['startDate'] ?? '')),
    'endDate' => trim((string) ($_POST['endDate'] ?? '')),
    'startTime' => trim((string) ($_POST['startTime'] ?? '')),
    'endTime' => trim((string) ($_POST['endTime'] ?? '')),
    'attendeeCount' => trim((string) ($_POST['attendeeCount'] ?? '')),
];

$render_alert = static function (string $type, string $title, string $message = ''): void {
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

    echo json_encode([
        'ok' => $type === 'success',
        'html' => $alert_html,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
};

if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    if (function_exists('audit_log')) {
        audit_log('security', 'CSRF_FAIL', 'DENY', 'dh_room_bookings', null, 'room_booking_check_api', $audit_payload_base);
    }
    $render_alert('danger', 'ไม่สามารถยืนยันความปลอดภัย', 'กรุณาลองใหม่อีกครั้ง');
}

$requester_pid = (string) ($_SESSION['pID'] ?? '');

if ($requester_pid === '') {
    if (function_exists('audit_log')) {
        audit_log('security', 'AUTH_REQUIRED', 'DENY', null, null, 'room_booking_check_api', $audit_payload_base);
    }
    $render_alert('danger', 'ไม่พบข้อมูลผู้ใช้งาน', 'กรุณาเข้าสู่ระบบใหม่');
}

$room_booking_year = (int) ($_POST['dh_year'] ?? 0);

if ($room_booking_year <= 0) {
    $room_booking_year = (int) date('Y') + 543;
}

$room_map = room_booking_get_room_map($connection);
$room_detail_map = room_booking_get_room_detail_map($connection);
$room_id = trim((string) ($_POST['roomID'] ?? ''));

if ($room_id === '' || !isset($room_map[$room_id])) {
    if (function_exists('audit_log')) {
        audit_log('room', 'CHECK_AVAILABILITY', 'FAIL', 'dh_room_bookings', null, 'invalid_room', $audit_payload_base);
    }
    $render_alert('danger', 'ข้อมูลไม่ถูกต้อง', 'กรุณาเลือกห้องหรือสถานที่');
}

$room_detail = $room_detail_map[$room_id] ?? null;
$room_status = $room_detail ? (string) ($room_detail['roomStatus'] ?? '') : '';
$room_note = $room_detail ? trim((string) ($room_detail['roomNote'] ?? '')) : '';

if ($room_detail === null) {
    if (function_exists('audit_log')) {
        audit_log('room', 'CHECK_AVAILABILITY', 'FAIL', 'dh_room_bookings', null, 'room_not_found', $audit_payload_base);
    }
    $render_alert('danger', 'ข้อมูลไม่ถูกต้อง', 'ไม่พบข้อมูลห้องที่เลือก');
}

if (!room_booking_is_room_available($room_status)) {
    if (function_exists('audit_log')) {
        audit_log('room', 'CHECK_AVAILABILITY', 'FAIL', 'dh_room_bookings', null, 'room_unavailable', array_merge($audit_payload_base, [
            'roomStatus' => $room_status !== '' ? $room_status : null,
            'roomNote' => $room_note !== '' ? $room_note : null,
        ]));
    }
    $status_label = $room_status !== '' ? $room_status : 'ไม่พร้อมใช้งาน';
    $message = 'สถานะห้อง: ' . $status_label;

    if ($room_note !== '') {
        $message .= ' • ' . $room_note;
    }
    $render_alert('warning', 'ห้องนี้ไม่พร้อมใช้งาน', $message);
}

$start_date_raw = trim((string) ($_POST['startDate'] ?? ''));
$end_date_raw = trim((string) ($_POST['endDate'] ?? ''));

$start_date_obj = DateTime::createFromFormat('Y-m-d', $start_date_raw);

if ($start_date_obj === false) {
    if (function_exists('audit_log')) {
        audit_log('room', 'CHECK_AVAILABILITY', 'FAIL', 'dh_room_bookings', null, 'invalid_start_date', $audit_payload_base);
    }
    $render_alert('danger', 'วันที่ไม่ถูกต้อง', 'กรุณาเลือกวันที่เริ่มใช้');
}

$end_date_obj = $end_date_raw !== ''
    ? DateTime::createFromFormat('Y-m-d', $end_date_raw)
    : clone $start_date_obj;

if ($end_date_obj === false) {
    if (function_exists('audit_log')) {
        audit_log('room', 'CHECK_AVAILABILITY', 'FAIL', 'dh_room_bookings', null, 'invalid_end_date', $audit_payload_base);
    }
    $render_alert('danger', 'วันที่ไม่ถูกต้อง', 'กรุณาเลือกวันที่สิ้นสุดให้ถูกต้อง');
}

if ($end_date_obj < $start_date_obj) {
    if (function_exists('audit_log')) {
        audit_log('room', 'CHECK_AVAILABILITY', 'FAIL', 'dh_room_bookings', null, 'invalid_date_range', $audit_payload_base);
    }
    $render_alert('danger', 'วันที่ไม่ถูกต้อง', 'วันที่สิ้นสุดต้องไม่น้อยกว่าวันที่เริ่มใช้');
}

$start_date = $start_date_obj->format('Y-m-d');
$end_date = $end_date_obj->format('Y-m-d');

$start_time_raw = trim((string) ($_POST['startTime'] ?? ''));
$end_time_raw = trim((string) ($_POST['endTime'] ?? ''));

$start_time_obj = DateTime::createFromFormat('H:i', $start_time_raw);
$end_time_obj = DateTime::createFromFormat('H:i', $end_time_raw);

if ($start_time_obj === false || $end_time_obj === false) {
    if (function_exists('audit_log')) {
        audit_log('room', 'CHECK_AVAILABILITY', 'FAIL', 'dh_room_bookings', null, 'invalid_time', $audit_payload_base);
    }
    $render_alert('danger', 'เวลาไม่ถูกต้อง', 'กรุณาเลือกช่วงเวลาให้ครบถ้วน');
}

if ($end_time_obj <= $start_time_obj) {
    if (function_exists('audit_log')) {
        audit_log('room', 'CHECK_AVAILABILITY', 'FAIL', 'dh_room_bookings', null, 'invalid_time_range', $audit_payload_base);
    }
    $render_alert('danger', 'เวลาไม่ถูกต้อง', 'เวลาเลิกใช้ต้องมากกว่าเวลาเริ่มใช้');
}

$start_time = $start_time_obj->format('H:i:s');
$end_time = $end_time_obj->format('H:i:s');

$attendee_raw = trim((string) ($_POST['attendeeCount'] ?? ''));

if ($attendee_raw !== '') {
    $attendee_count = filter_var($attendee_raw, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1, 'max_range' => 9999],
    ]);

    if ($attendee_count === false) {
        if (function_exists('audit_log')) {
            audit_log('room', 'CHECK_AVAILABILITY', 'FAIL', 'dh_room_bookings', null, 'invalid_attendee_count', $audit_payload_base);
        }
        $render_alert('danger', 'ข้อมูลไม่ถูกต้อง', 'กรุณาระบุจำนวนผู้เข้าร่วมให้ถูกต้อง');
    }
}

$active_status = room_booking_get_active_status_values($connection);
$conflict_sql = 'SELECT roomBookingID FROM dh_room_bookings
    WHERE roomID = ? AND deletedAt IS NULL AND status IN (?, ?)
    AND startDate <= ? AND COALESCE(endDate, startDate) >= ?
    AND NOT (endTime <= ? OR startTime >= ?)
    LIMIT 1';
$conflict_stmt = mysqli_prepare($connection, $conflict_sql);

if ($conflict_stmt === false) {
    if (function_exists('audit_log')) {
        audit_log('room', 'CHECK_AVAILABILITY', 'FAIL', 'dh_room_bookings', null, 'conflict_prepare_failed', $audit_payload_base);
    }
    $render_alert('danger', 'ระบบขัดข้อง', 'ไม่สามารถตรวจสอบเวลาว่างได้ในขณะนี้');
}

mysqli_stmt_bind_param(
    $conflict_stmt,
    's' . $active_status['types'] . 'ssss',
    $room_id,
    $active_status['values'][0],
    $active_status['values'][1],
    $end_date,
    $start_date,
    $start_time,
    $end_time
);
mysqli_stmt_execute($conflict_stmt);
$conflict_result = mysqli_stmt_get_result($conflict_stmt);
$conflict_row = $conflict_result ? mysqli_fetch_assoc($conflict_result) : null;
mysqli_stmt_close($conflict_stmt);

if ($conflict_row) {
    if (function_exists('audit_log')) {
        audit_log('room', 'CHECK_AVAILABILITY', 'FAIL', 'dh_room_bookings', null, 'conflict', array_merge($audit_payload_base, [
            'conflictBookingID' => $conflict_row['roomBookingID'] ?? null,
        ]));
    }
    $render_alert('warning', 'ช่วงเวลานี้ถูกจองแล้ว', 'กรุณาเลือกวันหรือเวลาที่ว่าง');
}

if (function_exists('audit_log')) {
    audit_log('room', 'CHECK_AVAILABILITY', 'SUCCESS', 'dh_room_bookings', null, null, [
        'roomID' => $room_id,
        'startDate' => $start_date,
        'endDate' => $end_date,
        'startTime' => $start_time,
        'endTime' => $end_time,
        'attendeeCount' => isset($attendee_count) ? $attendee_count : null,
    ]);
}
$render_alert('success', 'ช่วงเวลาว่าง', 'สามารถจองช่วงเวลานี้ได้');

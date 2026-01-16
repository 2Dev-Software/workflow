<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

require_once __DIR__ . '/../../../config/connection.php';
require_once __DIR__ . '/room-booking-utils.php';

$is_check = !empty($_POST['room_booking_check']);
$is_save = !empty($_POST['room_booking_save']) && !$is_check;

if (!$is_check && !$is_save) {
    return;
}

$redirect_url = 'room-booking.php';

$set_room_booking_alert = static function (
    string $type,
    string $title,
    string $message = '',
    string $button_label = 'ยืนยัน'
) use ($redirect_url): void {
    $_SESSION['room_booking_alert'] = [
        'type' => $type,
        'title' => $title,
        'message' => $message,
        'button_label' => $button_label,
        'redirect' => '',
        'delay_ms' => 0,
    ];
};

if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $set_room_booking_alert('danger', 'ไม่สามารถยืนยันความปลอดภัย', 'กรุณาลองใหม่อีกครั้ง');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

$requester_pid = (string) ($_SESSION['pID'] ?? '');
if ($requester_pid === '') {
    header('Location: index.php', true, 302);
    exit();
}

$room_booking_year = isset($dh_year_value) ? (int) $dh_year_value : ((int) date('Y') + 543);
$room_map = room_booking_get_room_map($connection);
$room_detail_map = room_booking_get_room_detail_map($connection);

$room_id = trim((string) ($_POST['roomID'] ?? ''));
if ($room_id === '' || !isset($room_map[$room_id])) {
    $set_room_booking_alert('danger', 'ข้อมูลไม่ถูกต้อง', 'กรุณาเลือกห้องหรือสถานที่');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

$room_detail = $room_detail_map[$room_id] ?? null;
$room_status = $room_detail ? (string) ($room_detail['roomStatus'] ?? '') : '';
$room_note = $room_detail ? trim((string) ($room_detail['roomNote'] ?? '')) : '';
if ($room_detail === null) {
    $set_room_booking_alert('danger', 'ข้อมูลไม่ถูกต้อง', 'ไม่พบข้อมูลห้องที่เลือก');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}
if (!room_booking_is_room_available($room_status)) {
    $status_label = $room_status !== '' ? $room_status : 'ไม่พร้อมใช้งาน';
    $message = 'สถานะห้อง: ' . $status_label;
    if ($room_note !== '') {
        $message .= ' • ' . $room_note;
    }
    $set_room_booking_alert('warning', 'ห้องนี้ไม่พร้อมใช้งาน', $message);
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

$start_date_raw = trim((string) ($_POST['startDate'] ?? ''));
$end_date_raw = trim((string) ($_POST['endDate'] ?? ''));

$start_date_obj = DateTime::createFromFormat('Y-m-d', $start_date_raw);
if ($start_date_obj === false) {
    $set_room_booking_alert('danger', 'วันที่ไม่ถูกต้อง', 'กรุณาเลือกวันที่เริ่มใช้');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

$end_date_obj = $end_date_raw !== ''
    ? DateTime::createFromFormat('Y-m-d', $end_date_raw)
    : clone $start_date_obj;

if ($end_date_obj === false) {
    $set_room_booking_alert('danger', 'วันที่ไม่ถูกต้อง', 'กรุณาเลือกวันที่สิ้นสุดให้ถูกต้อง');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

if ($end_date_obj < $start_date_obj) {
    $set_room_booking_alert('danger', 'วันที่ไม่ถูกต้อง', 'วันที่สิ้นสุดต้องไม่น้อยกว่าวันที่เริ่มใช้');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

$start_date = $start_date_obj->format('Y-m-d');
$end_date = $end_date_obj->format('Y-m-d');

$start_time_raw = trim((string) ($_POST['startTime'] ?? ''));
$end_time_raw = trim((string) ($_POST['endTime'] ?? ''));

$start_time_obj = DateTime::createFromFormat('H:i', $start_time_raw);
$end_time_obj = DateTime::createFromFormat('H:i', $end_time_raw);

if ($start_time_obj === false || $end_time_obj === false) {
    $set_room_booking_alert('danger', 'เวลาไม่ถูกต้อง', 'กรุณาเลือกช่วงเวลาให้ครบถ้วน');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

if ($end_time_obj <= $start_time_obj) {
    $set_room_booking_alert('danger', 'เวลาไม่ถูกต้อง', 'เวลาเลิกใช้ต้องมากกว่าเวลาเริ่มใช้');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

$start_time = $start_time_obj->format('H:i:s');
$end_time = $end_time_obj->format('H:i:s');

$attendee_count = filter_input(INPUT_POST, 'attendeeCount', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1, 'max_range' => 9999],
]);
if ($attendee_count === false || $attendee_count === null) {
    $set_room_booking_alert('danger', 'ข้อมูลไม่ถูกต้อง', 'กรุณาระบุจำนวนผู้เข้าร่วม');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

$booking_topic = trim((string) ($_POST['bookingTopic'] ?? ''));
$booking_detail = trim((string) ($_POST['bookingDetail'] ?? ''));
$equipment_detail = trim((string) ($_POST['equipmentDetail'] ?? ''));
$requester_display_name = trim((string) ($teacher_name ?? ''));
if ($requester_display_name === '') {
    $requester_display_name = 'ผู้ใช้งานระบบ';
}

if ($booking_topic === '') {
    $booking_topic = 'รายการจองห้อง';
}

$clip_text = static function (string $value, int $limit): string {
    if ($limit <= 0) {
        return $value;
    }
    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, $limit);
    }
    return substr($value, 0, $limit);
};

$booking_columns = room_booking_get_table_columns($connection, 'dh_room_bookings');

$handle_db_exception = static function (mysqli_sql_exception $exception, string $title, string $message) use ($set_room_booking_alert, $redirect_url): void {
    error_log('Database Error: ' . $exception->getMessage());
    $set_room_booking_alert('danger', $title, $message);
    header('Location: ' . $redirect_url, true, 303);
    exit();
};

$active_status = room_booking_get_active_status_values($connection);
$conflict_sql = 'SELECT roomBookingID FROM dh_room_bookings
    WHERE roomID = ? AND deletedAt IS NULL AND status IN (?, ?)
    AND startDate <= ? AND COALESCE(endDate, startDate) >= ?
    AND NOT (endTime <= ? OR startTime >= ?)
    LIMIT 1';
try {
    $conflict_stmt = mysqli_prepare($connection, $conflict_sql);
} catch (mysqli_sql_exception $exception) {
    $handle_db_exception($exception, 'ระบบขัดข้อง', 'ไม่สามารถตรวจสอบเวลาว่างได้ในขณะนี้');
}

if ($conflict_stmt === false) {
    error_log('Database Error: ' . mysqli_error($connection));
    $set_room_booking_alert('danger', 'ระบบขัดข้อง', 'ไม่สามารถตรวจสอบเวลาว่างได้ในขณะนี้');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

try {
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
} catch (mysqli_sql_exception $exception) {
    $handle_db_exception($exception, 'ระบบขัดข้อง', 'ไม่สามารถตรวจสอบเวลาว่างได้ในขณะนี้');
}

if ($conflict_row) {
    $set_room_booking_alert('warning', 'ช่วงเวลานี้ถูกจองแล้ว', 'กรุณาเลือกวันหรือเวลาที่ว่าง');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

$set_room_booking_success = static function (string $title, string $message) use ($set_room_booking_alert, $redirect_url): void {
    $set_room_booking_alert('success', $title, $message);
    header('Location: ' . $redirect_url, true, 303);
    exit();
};

if ($is_check) {
    $set_room_booking_success('ช่วงเวลาว่าง', 'สามารถจองช่วงเวลานี้ได้');
}

$columns = [];
$placeholders = [];
$values = [];
$types = '';

$add_param = static function (string $type, $value) use (&$types, &$values, &$placeholders): void {
    $types .= $type;
    $values[] = $value;
    $placeholders[] = '?';
};

$columns[] = 'dh_year';
$add_param('i', $room_booking_year);

$columns[] = 'requesterPID';
$add_param('s', $requester_pid);

$columns[] = 'roomID';
$add_param('s', $room_id);

$columns[] = 'startDate';
$add_param('s', $start_date);

$columns[] = 'endDate';
$add_param('s', $end_date);

$columns[] = 'startTime';
$add_param('s', $start_time);

$columns[] = 'endTime';
$add_param('s', $end_time);

$columns[] = 'attendeeCount';
$add_param('i', $attendee_count);

$columns[] = 'status';
$status_param = room_booking_status_to_db($connection, 0);
$add_param($status_param['type'], $status_param['value']);

$columns[] = 'statusReason';
$add_param('s', '');

if (room_booking_has_column($booking_columns, 'bookingTopic')) {
    $columns[] = 'bookingTopic';
    $add_param('s', $clip_text($booking_topic, 255));
}

if (room_booking_has_column($booking_columns, 'bookingDetail')) {
    $columns[] = 'bookingDetail';
    $add_param('s', $clip_text($booking_detail, 2000));
}

if (room_booking_has_column($booking_columns, 'equipmentDetail')) {
    $columns[] = 'equipmentDetail';
    $add_param('s', $clip_text($equipment_detail, 500));
}

if (room_booking_has_column($booking_columns, 'requesterDisplayName')) {
    $columns[] = 'requesterDisplayName';
    $add_param('s', $clip_text($requester_display_name, 255));
}

$columns[] = 'createdAt';
$columns[] = 'updatedAt';
$placeholders[] = 'NOW()';
$placeholders[] = 'NOW()';

$insert_sql = 'INSERT INTO dh_room_bookings (' . implode(', ', $columns) . ')
    VALUES (' . implode(', ', $placeholders) . ')';

try {
    $insert_stmt = mysqli_prepare($connection, $insert_sql);
} catch (mysqli_sql_exception $exception) {
    $handle_db_exception($exception, 'ระบบขัดข้อง', 'ไม่สามารถบันทึกรายการจองได้ในขณะนี้');
}
if ($insert_stmt === false) {
    error_log('Database Error: ' . mysqli_error($connection));
    $set_room_booking_alert('danger', 'ระบบขัดข้อง', 'ไม่สามารถบันทึกรายการจองได้ในขณะนี้');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

$bind_params = array_merge([$insert_stmt, $types], $values);
$bind_refs = [];
foreach ($bind_params as $index => $value) {
    $bind_refs[$index] = &$bind_params[$index];
}
try {
    call_user_func_array('mysqli_stmt_bind_param', $bind_refs);

    if (mysqli_stmt_execute($insert_stmt) === false) {
        mysqli_stmt_close($insert_stmt);
        $set_room_booking_alert('danger', 'บันทึกไม่สำเร็จ', 'กรุณาลองใหม่อีกครั้ง');
        header('Location: ' . $redirect_url, true, 303);
        exit();
    }

    mysqli_stmt_close($insert_stmt);
} catch (mysqli_sql_exception $exception) {
    if ($insert_stmt instanceof mysqli_stmt) {
        mysqli_stmt_close($insert_stmt);
    }
    $handle_db_exception($exception, 'บันทึกไม่สำเร็จ', 'กรุณาตรวจสอบข้อมูลและลองใหม่อีกครั้ง');
}

$set_room_booking_alert('success', 'ส่งคำขอจองเรียบร้อยแล้ว', 'ระบบจะส่งคำขอให้ผู้ดูแลพิจารณา');
header('Location: ' . $redirect_url, true, 303);
exit();

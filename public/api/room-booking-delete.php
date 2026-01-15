<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['pID'])) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized', 'message' => 'กรุณาเข้าสู่ระบบอีกครั้ง'], JSON_UNESCAPED_UNICODE);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'method_not_allowed', 'message' => 'ไม่รองรับคำขอนี้'], JSON_UNESCAPED_UNICODE);
    exit();
}

$raw_input = file_get_contents('php://input');
$payload = json_decode($raw_input, true);
if (!is_array($payload)) {
    $payload = $_POST;
}

$csrf_token = (string) ($payload['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
if ($csrf_token === '' || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
    http_response_code(403);
    echo json_encode(['error' => 'invalid_csrf', 'message' => 'ไม่สามารถยืนยันความปลอดภัยได้'], JSON_UNESCAPED_UNICODE);
    exit();
}

$booking_id = filter_var($payload['booking_id'] ?? null, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);
if (!$booking_id) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid_payload', 'message' => 'ข้อมูลไม่ถูกต้อง'], JSON_UNESCAPED_UNICODE);
    exit();
}

require_once __DIR__ . '/../../config/connection.php';

$select_sql = 'SELECT roomBookingID, requesterPID, status FROM dh_room_bookings WHERE roomBookingID = ? AND deletedAt IS NULL LIMIT 1';
$select_stmt = mysqli_prepare($connection, $select_sql);

if ($select_stmt === false) {
    error_log('Database Error: ' . mysqli_error($connection));
    http_response_code(500);
    echo json_encode(['error' => 'db_error', 'message' => 'ระบบขัดข้อง'], JSON_UNESCAPED_UNICODE);
    exit();
}

mysqli_stmt_bind_param($select_stmt, 'i', $booking_id);
mysqli_stmt_execute($select_stmt);
$select_result = mysqli_stmt_get_result($select_stmt);
$booking_row = $select_result ? mysqli_fetch_assoc($select_result) : null;
mysqli_stmt_close($select_stmt);

if (!$booking_row) {
    http_response_code(404);
    echo json_encode(['error' => 'not_found', 'message' => 'ไม่พบรายการจอง'], JSON_UNESCAPED_UNICODE);
    exit();
}

$requester_pid = (string) ($_SESSION['pID'] ?? '');
if ((string) ($booking_row['requesterPID'] ?? '') !== $requester_pid) {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden', 'message' => 'ไม่มีสิทธิ์ลบรายการนี้'], JSON_UNESCAPED_UNICODE);
    exit();
}

$update_sql = 'UPDATE dh_room_bookings SET deletedAt = NOW(), updatedAt = NOW() WHERE roomBookingID = ?';
$update_stmt = mysqli_prepare($connection, $update_sql);

if ($update_stmt === false) {
    error_log('Database Error: ' . mysqli_error($connection));
    http_response_code(500);
    echo json_encode(['error' => 'db_error', 'message' => 'ระบบขัดข้อง'], JSON_UNESCAPED_UNICODE);
    exit();
}

mysqli_stmt_bind_param($update_stmt, 'i', $booking_id);

if (mysqli_stmt_execute($update_stmt) === false) {
    mysqli_stmt_close($update_stmt);
    http_response_code(500);
    echo json_encode(['error' => 'db_error', 'message' => 'ไม่สามารถลบรายการได้'], JSON_UNESCAPED_UNICODE);
    exit();
}

mysqli_stmt_close($update_stmt);

echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);

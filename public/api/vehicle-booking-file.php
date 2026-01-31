<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['pID'])) {
    http_response_code(401);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit();
}

$booking_id = filter_input(INPUT_GET, 'booking_id', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);
$file_id = filter_input(INPUT_GET, 'file_id', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);

if (!$booking_id || !$file_id) {
    http_response_code(400);
    exit();
}

require_once __DIR__ . '/../../config/connection.php';

$booking_sql = 'SELECT bookingID, requesterPID FROM dh_vehicle_bookings WHERE bookingID = ? AND deletedAt IS NULL LIMIT 1';
$booking_stmt = mysqli_prepare($connection, $booking_sql);
if ($booking_stmt === false) {
    error_log('Database Error: ' . mysqli_error($connection));
    http_response_code(500);
    exit();
}

mysqli_stmt_bind_param($booking_stmt, 'i', $booking_id);
mysqli_stmt_execute($booking_stmt);
$booking_result = mysqli_stmt_get_result($booking_stmt);
$booking_row = $booking_result ? mysqli_fetch_assoc($booking_result) : null;
mysqli_stmt_close($booking_stmt);

if (!$booking_row) {
    http_response_code(404);
    exit();
}

$requester_pid = (string) ($_SESSION['pID'] ?? '');
if ((string) ($booking_row['requesterPID'] ?? '') !== $requester_pid) {
    http_response_code(403);
    exit();
}

$file_sql = 'SELECT f.fileID, f.fileName, f.filePath, f.mimeType, f.fileSize
    FROM dh_file_refs AS r
    INNER JOIN dh_files AS f ON r.fileID = f.fileID
    WHERE r.moduleName = ? AND r.entityName = ? AND r.entityID = ? AND r.fileID = ? AND f.deletedAt IS NULL
    LIMIT 1';
$file_stmt = mysqli_prepare($connection, $file_sql);
if ($file_stmt === false) {
    error_log('Database Error: ' . mysqli_error($connection));
    http_response_code(500);
    exit();
}

$module_name = 'vehicle';
$entity_name = 'dh_vehicle_bookings';
$entity_id = (string) $booking_id;
mysqli_stmt_bind_param($file_stmt, 'sssi', $module_name, $entity_name, $entity_id, $file_id);
mysqli_stmt_execute($file_stmt);
$file_result = mysqli_stmt_get_result($file_stmt);
$file_row = $file_result ? mysqli_fetch_assoc($file_result) : null;
mysqli_stmt_close($file_stmt);

if (!$file_row) {
    http_response_code(404);
    exit();
}

$file_path = (string) ($file_row['filePath'] ?? '');
if ($file_path === '') {
    http_response_code(404);
    exit();
}

$base_dir = realpath(__DIR__ . '/../../assets/uploads/vehicle-bookings');
$target_path = realpath(__DIR__ . '/../../' . $file_path);

if ($base_dir === false || $target_path === false || strpos($target_path, $base_dir) !== 0) {
    http_response_code(404);
    exit();
}

if (!is_file($target_path)) {
    http_response_code(404);
    exit();
}

$file_name = (string) ($file_row['fileName'] ?? 'attachment');
$mime_type = (string) ($file_row['mimeType'] ?? 'application/octet-stream');
$download = isset($_GET['download']) && $_GET['download'] === '1';

header('Content-Type: ' . $mime_type);
header('Content-Length: ' . (string) filesize($target_path));
header('X-Content-Type-Options: nosniff');

$disposition = $download ? 'attachment' : 'inline';
$safe_name = str_replace(["\r", "\n"], '', $file_name);
header('Content-Disposition: ' . $disposition . '; filename="' . $safe_name . '"');

readfile($target_path);
exit();

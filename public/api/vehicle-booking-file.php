<?php

declare(strict_types=1);

// Production-grade file responses must never be corrupted by PHP notices/warnings.
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('html_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

set_error_handler(static function (int $severity, string $message, string $file = '', int $line = 0): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    error_log('PHP error [' . $severity . '] ' . $message . ' in ' . $file . ':' . $line);

    return true; // Prevent default handler from outputting to the response.
});

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../app/modules/audit/logger.php';

$actor_pid = (string) ($_SESSION['pID'] ?? '');

if ($actor_pid === '') {
    if (function_exists('audit_log')) {
        audit_log('security', 'AUTH_REQUIRED', 'DENY', null, null, 'vehicle_booking_file');
    }
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
    if (function_exists('audit_log')) {
        audit_log('vehicle', 'ATTACHMENT_VIEW', 'FAIL', 'dh_vehicle_bookings', null, 'invalid_params', [
            'bookingID' => $booking_id ?: null,
            'fileID' => $file_id ?: null,
        ]);
    }
    http_response_code(400);
    exit();
}

require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../../app/db/db.php';
require_once __DIR__ . '/../../app/rbac/roles.php';
require_once __DIR__ . '/../../app/modules/system/system.php';

$download = isset($_GET['download']) && $_GET['download'] === '1';
$audit_action = $download ? 'ATTACHMENT_DOWNLOAD' : 'ATTACHMENT_VIEW';

$abort = static function (int $status, string $audit_status, string $message, array $payload = []) use ($audit_action, $booking_id, $file_id): void {
    if (function_exists('audit_log')) {
        audit_log('vehicle', $audit_action, $audit_status, 'dh_vehicle_bookings', $booking_id, $message, array_merge([
            'fileID' => $file_id,
        ], $payload), 'GET', $status);
    }
    http_response_code($status);
    exit();
};

$booking_sql = 'SELECT bookingID, requesterPID FROM dh_vehicle_bookings WHERE bookingID = ? AND deletedAt IS NULL LIMIT 1';
$booking_stmt = mysqli_prepare($connection, $booking_sql);

if ($booking_stmt === false) {
    error_log('Database Error: ' . mysqli_error($connection));
    $abort(500, 'FAIL', 'booking_prepare_failed');
}

mysqli_stmt_bind_param($booking_stmt, 'i', $booking_id);
mysqli_stmt_execute($booking_stmt);
$booking_result = mysqli_stmt_get_result($booking_stmt);
$booking_row = $booking_result ? mysqli_fetch_assoc($booking_result) : null;
mysqli_stmt_close($booking_stmt);

if (!$booking_row) {
    $abort(404, 'FAIL', 'booking_not_found');
}

$authorized = (string) ($booking_row['requesterPID'] ?? '') === $actor_pid;

if (!$authorized) {
    $is_director = system_get_current_director_pid() === $actor_pid;
    $is_vehicle_officer = rbac_user_has_role($connection, $actor_pid, ROLE_VEHICLE)
        || rbac_user_has_role($connection, $actor_pid, ROLE_ADMIN);

    if (!$is_vehicle_officer) {
        $legacy_role = db_fetch_one('SELECT roleID FROM teacher WHERE pID = ? AND status = 1 LIMIT 1', 's', $actor_pid);
        $legacy_role_id = (int) ($legacy_role['roleID'] ?? 0);

        if (in_array($legacy_role_id, [1, 3], true)) {
            $is_vehicle_officer = true;
        }
    }

    if ($is_director || $is_vehicle_officer) {
        $authorized = true;
    }
}

if (!$authorized) {
    $abort(403, 'DENY', 'not_authorized');
}

$file_sql = 'SELECT f.fileID, f.fileName, f.filePath, f.mimeType, f.fileSize
    FROM dh_file_refs AS r
    INNER JOIN dh_files AS f ON r.fileID = f.fileID
    WHERE r.moduleName = ? AND r.entityName = ? AND r.entityID = ? AND r.fileID = ? AND f.deletedAt IS NULL
    LIMIT 1';
$file_stmt = mysqli_prepare($connection, $file_sql);

if ($file_stmt === false) {
    error_log('Database Error: ' . mysqli_error($connection));
    $abort(500, 'FAIL', 'file_prepare_failed');
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
    $abort(404, 'FAIL', 'file_not_found', [
        'refFileID' => $file_id,
    ]);
}

$file_path = (string) ($file_row['filePath'] ?? '');

if ($file_path === '') {
    $abort(404, 'FAIL', 'file_path_missing');
}

$base_dir = realpath(__DIR__ . '/../../assets/uploads/vehicle-bookings');
$target_path = realpath(__DIR__ . '/../../' . $file_path);

if ($base_dir === false || $target_path === false || strpos($target_path, $base_dir) !== 0) {
    $abort(404, 'DENY', 'invalid_file_path');
}

if (!is_file($target_path)) {
    $abort(404, 'FAIL', 'file_missing_on_disk');
}

$file_name = (string) ($file_row['fileName'] ?? 'attachment');
$mime_type = (string) ($file_row['mimeType'] ?? 'application/octet-stream');

if (function_exists('audit_log')) {
    audit_log('vehicle', $audit_action, 'SUCCESS', 'dh_vehicle_bookings', $booking_id, null, [
        'fileID' => (int) ($file_row['fileID'] ?? $file_id),
        'mimeType' => $mime_type,
        'fileSize' => (int) ($file_row['fileSize'] ?? 0),
    ], 'GET', 200);
}

header('Content-Type: ' . $mime_type);
header('Content-Length: ' . (string) filesize($target_path));
header('X-Content-Type-Options: nosniff');

$disposition = $download ? 'attachment' : 'inline';
$safe_name = str_replace(["\r", "\n"], '', $file_name);
header('Content-Disposition: ' . $disposition . '; filename="' . $safe_name . '"');

readfile($target_path);
exit();

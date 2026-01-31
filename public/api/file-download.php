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

$module = trim((string) ($_GET['module'] ?? ''));
$entity_id = trim((string) ($_GET['entity_id'] ?? ''));
$file_id = filter_input(INPUT_GET, 'file_id', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);

if ($module === '' || $entity_id === '' || !$file_id) {
    http_response_code(400);
    exit();
}

require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../../app/config/constants.php';
require_once __DIR__ . '/../../app/rbac/roles.php';

$allowed_modules = ['circulars', 'orders', 'outgoing', 'memos', 'repairs'];
if (!in_array($module, $allowed_modules, true)) {
    http_response_code(400);
    exit();
}

$file_sql = 'SELECT f.fileID, f.fileName, f.filePath, f.mimeType, f.fileSize, r.moduleName, r.entityName, r.entityID
    FROM dh_file_refs AS r
    INNER JOIN dh_files AS f ON r.fileID = f.fileID
    WHERE r.moduleName = ? AND r.entityID = ? AND r.fileID = ? AND f.deletedAt IS NULL
    LIMIT 1';
$stmt = mysqli_prepare($connection, $file_sql);
if ($stmt === false) {
    error_log('Database Error: ' . mysqli_error($connection));
    http_response_code(500);
    exit();
}

mysqli_stmt_bind_param($stmt, 'ssi', $module, $entity_id, $file_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$file_row = $result ? mysqli_fetch_assoc($result) : null;
mysqli_stmt_close($stmt);

if (!$file_row) {
    http_response_code(404);
    exit();
}

$current_pid = (string) $_SESSION['pID'];
$authorized = false;

if ($module === 'circulars') {
    $check = mysqli_prepare($connection, 'SELECT 1 FROM dh_circulars WHERE circularID = ? AND createdByPID = ? LIMIT 1');
    if ($check) {
        mysqli_stmt_bind_param($check, 'is', $entity_id, $current_pid);
        mysqli_stmt_execute($check);
        $res = mysqli_stmt_get_result($check);
        $authorized = $res && mysqli_fetch_assoc($res);
        mysqli_stmt_close($check);
    }
    if (!$authorized) {
        $check = mysqli_prepare($connection, 'SELECT 1 FROM dh_circular_inboxes WHERE circularID = ? AND pID = ? LIMIT 1');
        if ($check) {
            mysqli_stmt_bind_param($check, 'is', $entity_id, $current_pid);
            mysqli_stmt_execute($check);
            $res = mysqli_stmt_get_result($check);
            $authorized = $res && mysqli_fetch_assoc($res);
            mysqli_stmt_close($check);
        }
    }
} elseif ($module === 'orders') {
    $check = mysqli_prepare($connection, 'SELECT 1 FROM dh_orders WHERE orderID = ? AND createdByPID = ? LIMIT 1');
    if ($check) {
        mysqli_stmt_bind_param($check, 'is', $entity_id, $current_pid);
        mysqli_stmt_execute($check);
        $res = mysqli_stmt_get_result($check);
        $authorized = $res && mysqli_fetch_assoc($res);
        mysqli_stmt_close($check);
    }
    if (!$authorized) {
        $check = mysqli_prepare($connection, 'SELECT 1 FROM dh_order_inboxes WHERE orderID = ? AND pID = ? LIMIT 1');
        if ($check) {
            mysqli_stmt_bind_param($check, 'is', $entity_id, $current_pid);
            mysqli_stmt_execute($check);
            $res = mysqli_stmt_get_result($check);
            $authorized = $res && mysqli_fetch_assoc($res);
            mysqli_stmt_close($check);
        }
    }
} elseif ($module === 'outgoing') {
    $role_ids = rbac_resolve_role_ids($connection, ROLE_REGISTRY);
    if (empty($role_ids)) {
        $role_ids = [2];
    }
    if (!empty($role_ids)) {
        $placeholders = implode(', ', array_fill(0, count($role_ids), '?'));
        $types = str_repeat('i', count($role_ids));
        $sql = 'SELECT 1 FROM teacher WHERE pID = ? AND roleID IN (' . $placeholders . ') LIMIT 1';
        $stmt = mysqli_prepare($connection, $sql);
        if ($stmt) {
            $params = array_merge([$stmt, 's' . $types, $current_pid], $role_ids);
            $refs = [];
            foreach ($params as $i => $val) {
                $refs[$i] = &$params[$i];
            }
            call_user_func_array('mysqli_stmt_bind_param', $refs);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $authorized = $res && mysqli_fetch_assoc($res);
            mysqli_stmt_close($stmt);
        }
    }
} elseif ($module === 'memos') {
    $check = mysqli_prepare($connection, 'SELECT 1 FROM dh_memos WHERE memoID = ? AND createdByPID = ? LIMIT 1');
    if ($check) {
        mysqli_stmt_bind_param($check, 'is', $entity_id, $current_pid);
        mysqli_stmt_execute($check);
        $res = mysqli_stmt_get_result($check);
        $authorized = $res && mysqli_fetch_assoc($res);
        mysqli_stmt_close($check);
    }
} elseif ($module === 'repairs') {
    $check = mysqli_prepare($connection, 'SELECT 1 FROM dh_repair_requests WHERE repairID = ? AND requesterPID = ? LIMIT 1');
    if ($check) {
        mysqli_stmt_bind_param($check, 'is', $entity_id, $current_pid);
        mysqli_stmt_execute($check);
        $res = mysqli_stmt_get_result($check);
        $authorized = $res && mysqli_fetch_assoc($res);
        mysqli_stmt_close($check);
    }
}

if (!$authorized) {
    http_response_code(403);
    exit();
}

$file_path = (string) ($file_row['filePath'] ?? '');
if ($file_path === '') {
    http_response_code(404);
    exit();
}

$base_storage = realpath(__DIR__ . '/../../storage/uploads');
$base_assets = realpath(__DIR__ . '/../../assets/uploads');
$target_path = realpath(__DIR__ . '/../../' . $file_path);

$valid = false;
if ($target_path && $base_storage && strpos($target_path, $base_storage) === 0) {
    $valid = true;
}
if ($target_path && $base_assets && strpos($target_path, $base_assets) === 0) {
    $valid = true;
}

if (!$valid || !is_file($target_path)) {
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

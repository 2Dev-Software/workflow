<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../app/modules/audit/logger.php';

$module = trim((string) ($_GET['module'] ?? ''));
$entity_id = trim((string) ($_GET['entity_id'] ?? ''));
$file_id = filter_input(INPUT_GET, 'file_id', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);
$download = isset($_GET['download']) && $_GET['download'] === '1';
$is_outgoing_module = $module === 'outgoing';
$outgoing_entity_id = ctype_digit($entity_id) ? (int) $entity_id : null;
$outgoing_audit_action = $download ? 'ATTACHMENT_DOWNLOAD' : 'ATTACHMENT_VIEW';
$outgoing_abort = static function (int $http_status, string $audit_status, string $message, array $payload = [], ?string $http_method = null) use ($is_outgoing_module, $outgoing_audit_action, $outgoing_entity_id, $file_id): void {
    if ($is_outgoing_module && function_exists('audit_log')) {
        audit_log('outgoing', $outgoing_audit_action, $audit_status, 'dh_outgoing_letters', $outgoing_entity_id, $message, array_filter(array_merge([
            'fileID' => $file_id ?: null,
        ], $payload), static function ($value): bool {
            return $value !== null && $value !== '' && $value !== [];
        }), $http_method ?? (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'), $http_status);
    }
    http_response_code($http_status);
    exit();
};

if (empty($_SESSION['pID'])) {
    if ($is_outgoing_module && function_exists('audit_log')) {
        audit_log('security', 'AUTH_REQUIRED', 'DENY', 'dh_outgoing_letters', $outgoing_entity_id, 'outgoing_file_download', array_filter([
            'fileID' => $file_id ?: null,
            'download' => $download,
        ], static function ($value): bool {
            return $value !== null && $value !== '' && $value !== [];
        }), 'GET', 401);
    }
    http_response_code(401);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    $outgoing_abort(405, 'FAIL', 'invalid_method');
}

if ($module === '' || $entity_id === '' || !$file_id) {
    $outgoing_abort(400, 'FAIL', 'invalid_params', [
        'module' => $module !== '' ? $module : null,
        'rawEntityID' => $entity_id !== '' ? $entity_id : null,
    ], 'GET');
}

require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../../app/config/constants.php';
require_once __DIR__ . '/../../app/rbac/roles.php';

$allowed_modules = ['circulars', 'orders', 'outgoing', 'memos', 'repairs'];

if (!in_array($module, $allowed_modules, true)) {
    $outgoing_abort(400, 'FAIL', 'invalid_module', [
        'module' => $module,
    ], 'GET');
}

$file_sql = 'SELECT f.fileID, f.fileName, f.filePath, f.mimeType, f.fileSize, r.moduleName, r.entityName, r.entityID
    FROM dh_file_refs AS r
    INNER JOIN dh_files AS f ON r.fileID = f.fileID
    WHERE r.moduleName = ? AND r.entityID = ? AND r.fileID = ? AND f.deletedAt IS NULL
    LIMIT 1';
$stmt = mysqli_prepare($connection, $file_sql);

if ($stmt === false) {
    error_log('Database Error: ' . mysqli_error($connection));
    $outgoing_abort(500, 'FAIL', 'file_lookup_prepare_failed', [], 'GET');
}

mysqli_stmt_bind_param($stmt, 'ssi', $module, $entity_id, $file_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$file_row = $result ? mysqli_fetch_assoc($result) : null;
mysqli_stmt_close($stmt);

if (!$file_row) {
    $outgoing_abort(404, 'FAIL', 'file_reference_not_found', [], 'GET');
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
    $authorized = rbac_user_has_role($connection, $current_pid, ROLE_ADMIN)
        || rbac_user_has_role($connection, $current_pid, ROLE_REGISTRY);

    if (!$authorized) {
        $check = mysqli_prepare($connection, 'SELECT 1 FROM teacher WHERE pID = ? AND roleID IN (1, 2) LIMIT 1');

        if ($check) {
            mysqli_stmt_bind_param($check, 's', $current_pid);
            mysqli_stmt_execute($check);
            $res = mysqli_stmt_get_result($check);
            $authorized = $res && mysqli_fetch_assoc($res);
            mysqli_stmt_close($check);
        }
    }
} elseif ($module === 'memos') {
    // Authorized: creator OR current approver (toPID) OR decision actor (approvedByPID) OR admin.
    $fields = ['createdByPID', 'approvedByPID'];

    if (function_exists('db_column_exists') && db_column_exists($connection, 'dh_memos', 'status')) {
        $fields[] = 'status';
    }

    if (function_exists('db_column_exists') && db_column_exists($connection, 'dh_memos', 'submittedAt')) {
        $fields[] = 'submittedAt';
    }

    if (function_exists('db_column_exists') && db_column_exists($connection, 'dh_memos', 'toPID')) {
        $fields[] = 'toPID';
    }
    $check_sql = 'SELECT ' . implode(', ', $fields) . ' FROM dh_memos WHERE memoID = ? LIMIT 1';
    $check = mysqli_prepare($connection, $check_sql);

    if ($check) {
        mysqli_stmt_bind_param($check, 'i', $entity_id);
        mysqli_stmt_execute($check);
        $res = mysqli_stmt_get_result($check);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($check);

        if ($row) {
            $memo_status = strtoupper(trim((string) ($row['status'] ?? '')));
            $is_submitted_or_legacy = !empty($row['submittedAt']) || in_array($memo_status, [
                'SUBMITTED',
                'IN_REVIEW',
                'RETURNED',
                'APPROVED_UNSIGNED',
                'SIGNED',
                'REJECTED',
            ], true);
            $authorized = ((string) ($row['createdByPID'] ?? '') === $current_pid);

            // Draft and "cancelled-before-submit" are creator-only even if an approver was preselected.
            if (!$authorized && $memo_status !== 'DRAFT' && $is_submitted_or_legacy && !empty($row['toPID'])) {
                $authorized = ((string) $row['toPID'] === $current_pid);
            }

            if (!$authorized && !empty($row['approvedByPID'])) {
                $authorized = ((string) $row['approvedByPID'] === $current_pid);
            }
        }
    }

    if (!$authorized && function_exists('rbac_user_has_role')) {
        $authorized = rbac_user_has_role($connection, $current_pid, ROLE_ADMIN);
    }
} elseif ($module === 'repairs') {
    $check = mysqli_prepare($connection, 'SELECT requesterPID FROM dh_repair_requests WHERE repairID = ? LIMIT 1');

    if ($check) {
        mysqli_stmt_bind_param($check, 'i', $entity_id);
        mysqli_stmt_execute($check);
        $res = mysqli_stmt_get_result($check);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        $authorized = ((string) ($row['requesterPID'] ?? '') === $current_pid);
        mysqli_stmt_close($check);
    }

    if (!$authorized && function_exists('rbac_user_has_any_role')) {
        $authorized = rbac_user_has_any_role($connection, $current_pid, [ROLE_ADMIN, ROLE_FACILITY]);
    }

    if (!$authorized) {
        $check = mysqli_prepare($connection, 'SELECT 1 FROM teacher WHERE pID = ? AND roleID IN (1, 5) LIMIT 1');

        if ($check) {
            mysqli_stmt_bind_param($check, 's', $current_pid);
            mysqli_stmt_execute($check);
            $res = mysqli_stmt_get_result($check);
            $authorized = $res && mysqli_fetch_assoc($res);
            mysqli_stmt_close($check);
        }
    }
}

if (!$authorized) {
    $outgoing_abort(403, 'DENY', 'not_authorized', [], 'GET');
}

$file_path = (string) ($file_row['filePath'] ?? '');

if ($file_path === '') {
    $outgoing_abort(404, 'FAIL', 'file_path_missing', [], 'GET');
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
    $outgoing_abort(404, !$valid ? 'DENY' : 'FAIL', !$valid ? 'invalid_file_path' : 'file_missing_on_disk', [], 'GET');
}

$file_name = (string) ($file_row['fileName'] ?? 'attachment');
$mime_type = (string) ($file_row['mimeType'] ?? 'application/octet-stream');

if ($is_outgoing_module && function_exists('audit_log')) {
    audit_log('outgoing', $outgoing_audit_action, 'SUCCESS', 'dh_outgoing_letters', $outgoing_entity_id, null, [
        'fileID' => (int) ($file_row['fileID'] ?? $file_id),
        'mimeType' => $mime_type,
        'fileSize' => (int) ($file_row['fileSize'] ?? 0),
        'download' => $download,
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

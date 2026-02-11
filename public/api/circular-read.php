<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    exit();
}

require_once __DIR__ . '/../../app/helpers.php';
require_once __DIR__ . '/../../app/auth/csrf.php';
require_once __DIR__ . '/../../app/modules/circulars/repository.php';

$current_pid = (string) ($_SESSION['pID'] ?? '');
if ($current_pid === '') {
    json_error('unauthorized', [], 401);
}

if (!csrf_validate($_POST['csrf_token'] ?? null)) {
    json_error('invalid_csrf', [], 403);
}

$inbox_id = filter_input(INPUT_POST, 'inbox_id', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);

if (!$inbox_id) {
    json_error('invalid_inbox', [], 400);
}

try {
    circular_mark_read((int) $inbox_id, $current_pid);
    json_success('ok', ['inbox_id' => (int) $inbox_id]);
} catch (Throwable $e) {
    error_log('Circular mark read failed: ' . $e->getMessage());
    json_error('server_error', [], 500);
}

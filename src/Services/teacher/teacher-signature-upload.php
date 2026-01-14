<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['signature_upload'])) {
    return;
}

require_once __DIR__ . '/../../../config/connection.php';

if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    http_response_code(403);
    exit('403 Forbidden: Invalid Security Token');
}

$teacher_pid = $_SESSION['pID'] ?? '';
if ($teacher_pid === '') {
    http_response_code(401);
    exit('401 Unauthorized');
}

if (empty($_FILES['signature_file'])) {
    http_response_code(400);
    exit('400 Bad Request: Missing Signature File');
}

$signature_file = $_FILES['signature_file'];
if ($signature_file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    exit('400 Bad Request: Upload Failed');
}

$max_signature_size = 2 * 1024 * 1024;
if ((int) $signature_file['size'] > $max_signature_size) {
    http_response_code(413);
    exit('413 Payload Too Large');
}

$signature_mime = '';
if (class_exists('finfo')) {
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    if ($finfo) {
        $signature_mime = (string) $finfo->file($signature_file['tmp_name']);
    }
}
if ($signature_mime === '') {
    $signature_mime = (string) ($signature_file['type'] ?? '');
}

$allowed_mime = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
];
if (!isset($allowed_mime[$signature_mime])) {
    http_response_code(415);
    exit('415 Unsupported Media Type');
}

$safe_pid = preg_replace('/\D+/', '', (string) $teacher_pid);
if ($safe_pid === '') {
    http_response_code(400);
    exit('400 Bad Request: Invalid Identifier');
}

$signature_dir = __DIR__ . '/../../../assets/img/signature/' . $safe_pid;
if (!is_dir($signature_dir) && !mkdir($signature_dir, 0755, true)) {
    error_log('Signature directory create failed: ' . $signature_dir);
    http_response_code(500);
    exit('500 Internal Server Error');
}

$signature_filename = 'signature_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $allowed_mime[$signature_mime];
$signature_target = $signature_dir . '/' . $signature_filename;

if (!move_uploaded_file($signature_file['tmp_name'], $signature_target)) {
    http_response_code(500);
    exit('500 Internal Server Error');
}

@chmod($signature_target, 0644);

$signature_path = 'assets/img/signature/' . $safe_pid . '/' . $signature_filename;

$update_sql = 'UPDATE teacher SET signature = ? WHERE pID = ? AND status = 1';
$update_stmt = mysqli_prepare($connection, $update_sql);

if ($update_stmt === false) {
    error_log('Database Error: ' . mysqli_error($connection));
    http_response_code(500);
    exit('500 Internal Server Error');
}

mysqli_stmt_bind_param($update_stmt, 'ss', $signature_path, $teacher_pid);
mysqli_stmt_execute($update_stmt);
mysqli_stmt_close($update_stmt);

header('Location: profile.php?tab=signature', true, 303);
exit();

<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['change_password'])) {
    return;
}

require_once __DIR__ . '/../../../config/connection.php';

if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    http_response_code(403);
    exit('403 Forbidden: Invalid Security Token');
}

$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if ($current_password === '' || $new_password === '' || $confirm_password === '') {
    http_response_code(400);
    exit('400 Bad Request: Missing Password Fields');
}

if ($new_password !== $confirm_password) {
    http_response_code(400);
    exit('400 Bad Request: Passwords Do Not Match');
}

$teacher_pid = $_SESSION['pID'] ?? '';
if ($teacher_pid === '') {
    http_response_code(401);
    exit('401 Unauthorized');
}

$select_sql = 'SELECT password FROM teacher WHERE pID = ? AND status = 1 LIMIT 1';
$select_stmt = mysqli_prepare($connection, $select_sql);

if ($select_stmt === false) {
    error_log('Database Error: ' . mysqli_error($connection));
    http_response_code(500);
    exit('500 Internal Server Error');
}

mysqli_stmt_bind_param($select_stmt, 's', $teacher_pid);
mysqli_stmt_execute($select_stmt);
$select_result = mysqli_stmt_get_result($select_stmt);
$teacher_row = $select_result ? mysqli_fetch_assoc($select_result) : null;
mysqli_stmt_close($select_stmt);

if (!$teacher_row) {
    http_response_code(404);
    exit('404 Not Found');
}

if (!hash_equals((string) $teacher_row['password'], (string) $current_password)) {
    http_response_code(401);
    exit('401 Unauthorized: Invalid Current Password');
}

if (hash_equals((string) $teacher_row['password'], (string) $new_password)) {
    http_response_code(400);
    exit('400 Bad Request: Password Not Changed');
}

$update_sql = 'UPDATE teacher SET password = ? WHERE pID = ? AND status = 1';
$update_stmt = mysqli_prepare($connection, $update_sql);

if ($update_stmt === false) {
    error_log('Database Error: ' . mysqli_error($connection));
    http_response_code(500);
    exit('500 Internal Server Error');
}

mysqli_stmt_bind_param($update_stmt, 'ss', $new_password, $teacher_pid);
mysqli_stmt_execute($update_stmt);
mysqli_stmt_close($update_stmt);

header('Location: profile.php?tab=password', true, 303);
exit();

<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['dh_status_save'])) {
    return;
}

require_once __DIR__ . '/../../../config/connection.php';

if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    http_response_code(403);
    exit('403 Forbidden: Invalid Security Token');
}

$dh_status_input = filter_input(INPUT_POST, 'dh_status', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1, 'max_range' => 3],
]);

if ($dh_status_input === false || $dh_status_input === null) {
    http_response_code(400);
    exit('400 Bad Request: Invalid Status');
}

$select_sql = 'SELECT ID FROM thesystem ORDER BY ID DESC LIMIT 1';
$select_result = mysqli_query($connection, $select_sql);

if ($select_result === false) {
    error_log('Database Error: ' . mysqli_error($connection));
    http_response_code(500);
    exit('500 Internal Server Error');
}

$system_row = mysqli_fetch_assoc($select_result);
mysqli_free_result($select_result);

if (!$system_row) {
    http_response_code(404);
    exit('404 Not Found');
}

$system_id = (int) $system_row['ID'];

$update_sql = 'UPDATE thesystem SET dh_status = ? WHERE ID = ?';
$update_stmt = mysqli_prepare($connection, $update_sql);

if ($update_stmt === false) {
    error_log('Database Error: ' . mysqli_error($connection));
    http_response_code(500);
    exit('500 Internal Server Error');
}

mysqli_stmt_bind_param($update_stmt, 'ii', $dh_status_input, $system_id);
mysqli_stmt_execute($update_stmt);
mysqli_stmt_close($update_stmt);

header('Location: setting.php', true, 303);
exit();

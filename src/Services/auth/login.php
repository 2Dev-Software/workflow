<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {

    if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        exit('403 Forbidden: Invalid Security Token');
    }

    $pID = trim($_POST['pID'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($pID === '' || $password === '') {
        http_response_code(400);
        exit('400 Bad Request: Missing Credentials');
    }

    require_once __DIR__ . '/../../config/connection.php';
}
?>
<?php

date_default_timezone_set('Asia/Bangkok');

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

try {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
} catch (Exception $e) {
    error_log('Dotenv Load Failed: ' . $e->getMessage());
    die('Server Configuration Error.');
}

$db_host = $_ENV['DB_HOST'];
$db_name = $_ENV['DB_NAME'];
$db_user = $_ENV['DB_USER'];
$db_pass = $_ENV['DB_PASS'];
$db_charset = $_ENV['DB_CHARSET'];
$app_env = $_ENV['APP_ENV'];

if ($app_env === 'local') {

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
} else {

    mysqli_report(MYSQLI_REPORT_OFF);
}

$connection = @mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$connection) {
    error_log('MySQL Connection Failed: ' . mysqli_connect_error());

    if ($app_env === 'local') {
        die('<h3>Local Connection Error:</h3>' . mysqli_connect_error());
    }

    http_response_code(500);
    die('ขออภัย ระบบไม่สามารถเชื่อมต่อฐานข้อมูลได้ในขณะนี้ (กรุณาติดต่อผู้ดูแลระบบ)');
}

mysqli_set_charset($connection, $db_charset);

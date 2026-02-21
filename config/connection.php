<?php

date_default_timezone_set('Asia/Bangkok');

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

try {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->safeLoad();
} catch (Exception $e) {
    error_log('Dotenv Load Failed: ' . $e->getMessage());
}

$env = static function (string $key, ?string $default = null): ?string {
    if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
        return (string) $_ENV[$key];
    }

    if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
        return (string) $_SERVER[$key];
    }

    $value = getenv($key);

    if ($value !== false && $value !== '') {
        return (string) $value;
    }

    return $default;
};

$db_host = (string) $env('DB_HOST', '127.0.0.1');
$db_name = (string) $env('DB_NAME', 'deebuk_platform');
$db_user = (string) $env('DB_USER', 'root');
$db_pass = (string) $env('DB_PASS', '');
$db_charset = (string) $env('DB_CHARSET', 'utf8mb4');
$db_port = (int) $env('DB_PORT', '3306');
$app_env = strtolower((string) $env('APP_ENV', 'production'));

if (in_array($app_env, ['local', 'development', 'dev', 'staging'], true)) {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
} else {
    mysqli_report(MYSQLI_REPORT_OFF);
}

$required = [
    'DB_HOST' => $db_host,
    'DB_NAME' => $db_name,
    'DB_USER' => $db_user,
    'DB_CHARSET' => $db_charset,
];

$missing_keys = [];

foreach ($required as $key => $value) {
    if (trim((string) $value) === '') {
        $missing_keys[] = $key;
    }
}

if ($missing_keys !== []) {
    $message = 'Missing environment keys: ' . implode(', ', $missing_keys);
    error_log('Configuration Error: ' . $message);

    if (in_array($app_env, ['local', 'development', 'dev', 'staging'], true)) {
        die('<h3>Local Configuration Error:</h3>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
    }

    http_response_code(500);
    die('ขออภัย ระบบไม่สามารถเชื่อมต่อฐานข้อมูลได้ในขณะนี้ (กรุณาติดต่อผู้ดูแลระบบ)');
}

$connection = @mysqli_connect($db_host, $db_user, $db_pass, $db_name, $db_port);

if (!$connection) {
    error_log('MySQL Connection Failed: ' . mysqli_connect_error());

    if (in_array($app_env, ['local', 'development', 'dev', 'staging'], true)) {
        die('<h3>Local Connection Error:</h3>' . mysqli_connect_error());
    }

    http_response_code(500);
    die('ขออภัย ระบบไม่สามารถเชื่อมต่อฐานข้อมูลได้ในขณะนี้ (กรุณาติดต่อผู้ดูแลระบบ)');
}

if (!@mysqli_set_charset($connection, $db_charset)) {
    error_log('MySQL Charset Error: ' . mysqli_error($connection));
    @mysqli_set_charset($connection, 'utf8mb4');
}

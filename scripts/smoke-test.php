<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/db/db.php';

app_bootstrap();

$results = [];

try {
    $connection = db_connection();
    $results[] = ['DB connection', $connection instanceof mysqli];
} catch (Throwable $e) {
    $results[] = ['DB connection', false];
}

$upload_root = rtrim((string) app_env('UPLOAD_ROOT', __DIR__ . '/../storage/uploads'), '/');
$results[] = ['Upload dir writable', is_writable($upload_root)];

$required_extensions = ['mysqli', 'openssl', 'mbstring', 'json', 'fileinfo'];

foreach ($required_extensions as $ext) {
    $results[] = ["PHP extension {$ext}", extension_loaded($ext)];
}

foreach ($results as [$label, $ok]) {
    echo sprintf("[%s] %s\n", $ok ? 'OK' : 'FAIL', $label);
}

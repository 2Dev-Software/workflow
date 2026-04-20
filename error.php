<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

app_bootstrap();

$code = (int) ($_GET['code'] ?? 404);

if ($code === 403) {
    require __DIR__ . '/app/views/errors/403.php';
    exit;
}

if ($code === 500) {
    require __DIR__ . '/app/views/errors/500.php';
    exit;
}

require __DIR__ . '/app/views/errors/404.php';

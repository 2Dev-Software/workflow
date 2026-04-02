<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only\n");
    exit(1);
}

$_SERVER['REQUEST_METHOD'] = 'CLI';
$_SERVER['REQUEST_URI'] = 'cli://scripts/backfill-outgoing-priority.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['HTTP_USER_AGENT'] = 'Codex Outgoing Priority Backfill';

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/modules/outgoing/service.php';

try {
    $summary = outgoing_backfill_priority_metadata();
    fwrite(STDOUT, json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL);
} catch (Throwable $e) {
    fwrite(STDERR, 'Outgoing priority backfill failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

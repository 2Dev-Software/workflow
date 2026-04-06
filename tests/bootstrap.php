<?php

declare(strict_types=1);

$_SERVER['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
$_SERVER['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/db/db.php';
require_once __DIR__ . '/../app/modules/system/system.php';
require_once __DIR__ . '/../app/modules/outgoing/repository.php';
require_once __DIR__ . '/../app/modules/circulars/repository.php';
require_once __DIR__ . '/../app/modules/certificates/repository.php';
require_once __DIR__ . '/../app/modules/certificates/service.php';
require_once __DIR__ . '/../app/modules/memos/repository.php';
require_once __DIR__ . '/../app/modules/memos/service.php';
require_once __DIR__ . '/../app/modules/orders/repository.php';
require_once __DIR__ . '/../app/modules/orders/service.php';
require_once __DIR__ . '/../app/modules/repairs/service.php';
require_once __DIR__ . '/../app/modules/vehicle/calendar.php';
require_once __DIR__ . '/../src/Services/room/room-booking-utils.php';

$GLOBALS['connection'] = $GLOBALS['connection'] ?? ($connection ?? null);

app_bootstrap();

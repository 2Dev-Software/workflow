<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Services/auth/auth-guard.php';

if (!function_exists('orders_send_index')) {
    function orders_send_index(): void
    {
        header('Location: orders-create.php', true, 302);
        exit();
    }
}

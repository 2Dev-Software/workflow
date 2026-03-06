<?php

declare(strict_types=1);

require_once __DIR__ . '/circular-notice-controller.php';

if (!function_exists('outgoing_notice_index')) {
    function outgoing_notice_index(): void
    {
        circular_notice_index();
    }
}

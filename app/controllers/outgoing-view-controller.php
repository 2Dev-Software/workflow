<?php

declare(strict_types=1);

require_once __DIR__ . '/circular-view-controller.php';

if (!function_exists('outgoing_view_index')) {
    function outgoing_view_index(): void
    {
        circular_view_index();
    }
}

<?php
declare(strict_types=1);

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/db/db.php';
require_once __DIR__ . '/helpers.php';

if (defined('APP_TIMEZONE')) {
    date_default_timezone_set(APP_TIMEZONE);
}

if (!function_exists('app_bootstrap')) {
    function app_bootstrap(): void
    {
        $request_id = app_request_id();

        if (app_is_debug()) {
            ini_set('display_errors', '1');
            ini_set('display_startup_errors', '1');
            error_reporting(E_ALL);
        } else {
            ini_set('display_errors', '0');
            ini_set('display_startup_errors', '0');
            error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
        }

        // Correlation ID for logs and audit trails
        header('X-Request-Id: ' . $request_id);
    }
}

<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/connection.php';

if (!function_exists('db_connection')) {
    function db_connection(): mysqli
    {
        global $connection;

        return $connection;
    }
}

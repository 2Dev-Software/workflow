<?php
declare(strict_types=1);

require_once __DIR__ . '/../db/db.php';

if (!function_exists('auth_password_column')) {
    function auth_password_column(mysqli $connection): string
    {
        static $column;

        if ($column !== null) {
            return $column;
        }

        if (db_column_exists($connection, 'teacher', 'passWord')) {
            $column = 'passWord';
            return $column;
        }

        $column = 'password';
        return $column;
    }
}

<?php
declare(strict_types=1);

require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/../auth/password.php';

if (!function_exists('user_find_active_by_pid')) {
    function user_find_active_by_pid(string $pid): ?array
    {
        $pid = trim($pid);
        if ($pid === '') {
            return null;
        }

        $connection = db_connection();
        $password_column = auth_password_column($connection);
        $sql = "SELECT pID, roleID, positionID, {$password_column} AS passwordValue, fname, lname FROM teacher WHERE pID = ? AND status = 1 LIMIT 1";
        return db_fetch_one($sql, 's', $pid);
    }
}

if (!function_exists('user_touch_last_login')) {
    function user_touch_last_login(string $pid): void
    {
        $pid = trim($pid);
        if ($pid === '') {
            return;
        }

        if (!db_table_exists(db_connection(), 'teacher')) {
            return;
        }

        if (!db_column_exists(db_connection(), 'teacher', 'lastLoginAt')) {
            return;
        }

        db_execute('UPDATE teacher SET lastLoginAt = NOW() WHERE pID = ?', 's', $pid);
    }
}

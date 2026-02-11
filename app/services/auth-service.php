<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/../repositories/user-repository.php';
require_once __DIR__ . '/../modules/audit/logger.php';

if (!function_exists('auth_validate_credentials')) {
    function auth_validate_credentials(string $pid, string $password): array
    {
        $pid = trim($pid);
        $password = (string) $password;
        if ($pid === '' || $password === '') {
            return [false, null, 'กรุณากรอกเลขบัตรประชาชนและรหัสผ่านให้ครบถ้วน'];
        }

        if (!ctype_digit($pid) || strlen($pid) !== 13) {
            return [false, null, 'เลขบัตรประชาชนไม่ถูกต้อง'];
        }

        $user = user_find_active_by_pid($pid);
        if (!$user) {
            return [false, null, 'เลขบัตรประชาชนหรือรหัสผ่านไม่ถูกต้อง'];
        }

        $stored = (string) ($user['passwordValue'] ?? '');
        if (!hash_equals($stored, $password)) {
            return [false, null, 'เลขบัตรประชาชนหรือรหัสผ่านไม่ถูกต้อง'];
        }

        return [true, $user, null];
    }
}

if (!function_exists('auth_check_lockout')) {
    function auth_check_lockout(string $pid, string $ip): array
    {
        $connection = db_connection();
        if (!db_table_exists($connection, 'dh_login_attempts')) {
            return [true, null];
        }

        $row = db_fetch_one('SELECT attemptCount, lockedUntil FROM dh_login_attempts WHERE pID = ? AND ipAddress = ? LIMIT 1', 'ss', $pid, $ip);
        if (!$row) {
            return [true, null];
        }

        $locked_until = $row['lockedUntil'] ?? null;
        if ($locked_until && strtotime((string) $locked_until) > time()) {
            return [false, 'บัญชีถูกล็อกชั่วคราว กรุณาลองใหม่อีกครั้งในภายหลัง'];
        }

        return [true, null];
    }
}

if (!function_exists('auth_record_login_failure')) {
    function auth_record_login_failure(string $pid, string $ip): void
    {
        $connection = db_connection();
        if (!db_table_exists($connection, 'dh_login_attempts')) {
            return;
        }

        $max_attempts = (int) app_env('AUTH_MAX_ATTEMPTS', 5);
        $lock_minutes = (int) app_env('AUTH_LOCK_MINUTES', 15);

        $sql = 'INSERT INTO dh_login_attempts (pID, ipAddress, attemptCount, lastAttemptAt, lockedUntil)
                VALUES (?, ?, 1, NOW(), NULL)
                ON DUPLICATE KEY UPDATE
                    attemptCount = attemptCount + 1,
                    lastAttemptAt = NOW(),
                    lockedUntil = IF(attemptCount + 1 >= ?, DATE_ADD(NOW(), INTERVAL ? MINUTE), lockedUntil)';

        db_execute($sql, 'ssii', $pid, $ip, $max_attempts, $lock_minutes);
    }
}

if (!function_exists('auth_clear_login_failure')) {
    function auth_clear_login_failure(string $pid, string $ip): void
    {
        $connection = db_connection();
        if (!db_table_exists($connection, 'dh_login_attempts')) {
            return;
        }

        db_execute('DELETE FROM dh_login_attempts WHERE pID = ? AND ipAddress = ?', 'ss', $pid, $ip);
    }
}

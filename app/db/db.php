<?php
declare(strict_types=1);

require_once __DIR__ . '/connection.php';

if (!function_exists('db_prepare')) {
    function db_prepare(string $sql): mysqli_stmt
    {
        $connection = db_connection();
        $stmt = mysqli_prepare($connection, $sql);
        if ($stmt === false) {
            error_log('Database Error (prepare): ' . mysqli_error($connection));
            throw new RuntimeException('Database prepare failed');
        }

        return $stmt;
    }
}

if (!function_exists('db_bind_params')) {
    function db_bind_params(mysqli_stmt $stmt, string $types, array $params): void
    {
        if ($types === '') {
            return;
        }

        $bind_params = array_merge([$stmt, $types], $params);
        $refs = [];
        foreach ($bind_params as $index => $value) {
            $refs[$index] = &$bind_params[$index];
        }

        if (call_user_func_array('mysqli_stmt_bind_param', $refs) === false) {
            throw new RuntimeException('Database bind failed');
        }
    }
}

if (!function_exists('db_query')) {
    function db_query(string $sql, string $types = '', ...$params): mysqli_stmt
    {
        $stmt = db_prepare($sql);
        db_bind_params($stmt, $types, $params);

        if (mysqli_stmt_execute($stmt) === false) {
            $connection = db_connection();
            error_log('Database Error (execute): ' . mysqli_error($connection));
            mysqli_stmt_close($stmt);
            throw new RuntimeException('Database execute failed');
        }

        return $stmt;
    }
}

if (!function_exists('db_fetch_one')) {
    function db_fetch_one(string $sql, string $types = '', ...$params): ?array
    {
        $stmt = db_query($sql, $types, ...$params);
        $result = mysqli_stmt_get_result($stmt);
        $row = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);

        return $row ?: null;
    }
}

if (!function_exists('db_fetch_all')) {
    function db_fetch_all(string $sql, string $types = '', ...$params): array
    {
        $stmt = db_query($sql, $types, ...$params);
        $result = mysqli_stmt_get_result($stmt);
        $rows = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $rows[] = $row;
            }
        }
        mysqli_stmt_close($stmt);

        return $rows;
    }
}

if (!function_exists('db_execute')) {
    function db_execute(string $sql, string $types = '', ...$params): int
    {
        $stmt = db_query($sql, $types, ...$params);
        $affected = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);

        return $affected;
    }
}

if (!function_exists('db_last_insert_id')) {
    function db_last_insert_id(): int
    {
        $connection = db_connection();
        return (int) mysqli_insert_id($connection);
    }
}

if (!function_exists('db_begin')) {
    function db_begin(): bool
    {
        $connection = db_connection();
        return mysqli_begin_transaction($connection);
    }
}

if (!function_exists('db_commit')) {
    function db_commit(): bool
    {
        $connection = db_connection();
        return mysqli_commit($connection);
    }
}

if (!function_exists('db_rollback')) {
    function db_rollback(): bool
    {
        $connection = db_connection();
        return mysqli_rollback($connection);
    }
}

if (!function_exists('db_table_exists')) {
    function db_table_exists(mysqli $connection, string $table): bool
    {
        $table = trim($table);
        if ($table === '') {
            return false;
        }
        $stmt = mysqli_prepare(
            $connection,
            'SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1'
        );
        if ($stmt === false) {
            error_log('Database Error (table exists): ' . mysqli_error($connection));
            return false;
        }

        mysqli_stmt_bind_param($stmt, 's', $table);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);

        return $row !== null;
    }
}

if (!function_exists('db_column_exists')) {
    function db_column_exists(mysqli $connection, string $table, string $column): bool
    {
        $table = trim($table);
        $column = trim($column);
        if ($table === '' || $column === '') {
            return false;
        }
        $stmt = mysqli_prepare(
            $connection,
            'SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? LIMIT 1'
        );
        if ($stmt === false) {
            error_log('Database Error (column exists): ' . mysqli_error($connection));
            return false;
        }

        mysqli_stmt_bind_param($stmt, 'ss', $table, $column);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);

        return $row !== null;
    }
}

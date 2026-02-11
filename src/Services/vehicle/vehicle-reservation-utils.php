<?php
declare(strict_types=1);

if (!function_exists('vehicle_reservation_get_table_columns')) {
    function vehicle_reservation_get_table_columns(mysqli $connection, string $table = 'dh_vehicle_bookings'): array
    {
        static $cached = [];
        $table = trim($table);

        if ($table === '' || !preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            return [];
        }

        if (isset($cached[$table])) {
            return $cached[$table];
        }

        $cached[$table] = [];
        $result = mysqli_query($connection, 'SHOW COLUMNS FROM `' . $table . '`');
        if ($result === false) {
            error_log('Database Error: ' . mysqli_error($connection));
            return $cached[$table];
        }

        while ($row = mysqli_fetch_assoc($result)) {
            if (!empty($row['Field'])) {
                $cached[$table][] = $row['Field'];
            }
        }

        mysqli_free_result($result);
        return $cached[$table];
    }
}

if (!function_exists('vehicle_reservation_has_column')) {
    function vehicle_reservation_has_column(array $columns, string $column): bool
    {
        return in_array($column, $columns, true);
    }
}

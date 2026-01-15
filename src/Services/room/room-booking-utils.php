<?php
declare(strict_types=1);

if (!function_exists('room_booking_get_room_map')) {
    function room_booking_get_room_map(mysqli $connection): array
    {
        static $cached = null;
        if (is_array($cached)) {
            return $cached;
        }

        $room_map = [];
        $result = mysqli_query(
            $connection,
            'SELECT roomID, roomName FROM dh_rooms ORDER BY roomName'
        );

        if ($result === false) {
            error_log('Database Error: ' . mysqli_error($connection));
            $cached = [];
            return $cached;
        }

        while ($row = mysqli_fetch_assoc($result)) {
            $room_id = trim((string) ($row['roomID'] ?? ''));
            if ($room_id === '') {
                continue;
            }
            $room_name = trim((string) ($row['roomName'] ?? ''));
            if ($room_name === '') {
                $room_name = $room_id;
            }
            $room_map[$room_id] = $room_name;
        }

        mysqli_free_result($result);
        $cached = $room_map;
        return $cached;
    }
}

if (!function_exists('room_booking_get_table_columns')) {
    function room_booking_get_table_columns(mysqli $connection, string $table = 'dh_room_bookings'): array
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

if (!function_exists('room_booking_has_column')) {
    function room_booking_has_column(array $columns, string $column): bool
    {
        return in_array($column, $columns, true);
    }
}

if (!function_exists('room_booking_normalize_time')) {
    function room_booking_normalize_time(?string $time): string
    {
        if ($time === null) {
            return '';
        }
        $time = trim($time);
        if ($time === '') {
            return '';
        }
        if (preg_match('/^\d{2}:\d{2}$/', $time)) {
            return $time;
        }
        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $time)) {
            return substr($time, 0, 5);
        }
        $date = DateTime::createFromFormat('H:i:s', $time);
        if ($date !== false) {
            return $date->format('H:i');
        }
        $date = DateTime::createFromFormat('H:i', $time);
        if ($date !== false) {
            return $date->format('H:i');
        }
        return $time;
    }
}

if (!function_exists('room_booking_build_events')) {
    function room_booking_build_events(array $bookings, array $room_map): array
    {
        $events = [];

        foreach ($bookings as $booking) {
            $status = (int) ($booking['status'] ?? 0);
            if ($status === 2) {
                continue;
            }

            $start_date_raw = (string) ($booking['startDate'] ?? '');
            if ($start_date_raw === '') {
                continue;
            }

            $end_date_raw = (string) ($booking['endDate'] ?? $start_date_raw);
            $start_date = DateTime::createFromFormat('Y-m-d', $start_date_raw);
            $end_date = DateTime::createFromFormat('Y-m-d', $end_date_raw);
            if ($start_date === false) {
                continue;
            }
            if ($end_date === false) {
                $end_date = clone $start_date;
            }

            $room_id = (string) ($booking['roomID'] ?? '');
            $room_name = $room_map[$room_id] ?? ((string) ($booking['roomName'] ?? $room_id));
            $time_range = room_booking_normalize_time((string) ($booking['startTime'] ?? ''))
                . '-' . room_booking_normalize_time((string) ($booking['endTime'] ?? ''));
            $detail = trim((string) ($booking['bookingTopic'] ?? ''));
            if ($detail === '') {
                $detail = 'รายการจองห้อง';
            }

            $event = [
                'bookingId' => (string) ($booking['roomBookingID'] ?? ''),
                'type' => 'room',
                'title' => $room_name,
                'time' => $time_range,
                'detail' => $detail,
                'count' => (string) ($booking['attendeeCount'] ?? '-'),
                'owner' => (string) ($booking['requesterName'] ?? '-'),
                'status' => $status,
            ];

            $cursor = clone $start_date;
            $end_date->setTime(0, 0, 0);
            while ($cursor <= $end_date) {
                $key = $cursor->format('Y-n-j');
                if (!isset($events[$key])) {
                    $events[$key] = [];
                }
                $events[$key][] = $event;
                $cursor->modify('+1 day');
            }
        }

        return $events;
    }
}

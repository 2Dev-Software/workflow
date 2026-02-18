<?php

declare(strict_types=1);

if (!function_exists('room_booking_get_rooms')) {
    function room_booking_get_rooms(mysqli $connection): array
    {
        static $cached = null;

        if (is_array($cached)) {
            return $cached;
        }

        $rooms = [];
        $result = mysqli_query(
            $connection,
            'SELECT roomID, roomName, roomStatus, roomNote FROM dh_rooms WHERE deletedAt IS NULL ORDER BY roomName'
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
            $rooms[] = [
                'roomID' => $room_id,
                'roomName' => $room_name,
                'roomStatus' => trim((string) ($row['roomStatus'] ?? '')),
                'roomNote' => trim((string) ($row['roomNote'] ?? '')),
            ];
        }

        mysqli_free_result($result);
        $cached = $rooms;

        return $cached;
    }
}

if (!function_exists('room_booking_get_available_rooms')) {
    function room_booking_get_available_rooms(mysqli $connection): array
    {
        return array_values(array_filter(
            room_booking_get_rooms($connection),
            static fn (array $room): bool => room_booking_is_room_available((string) ($room['roomStatus'] ?? ''))
        ));
    }
}

if (!function_exists('room_booking_get_room_detail_map')) {
    function room_booking_get_room_detail_map(mysqli $connection): array
    {
        static $cached = null;

        if (is_array($cached)) {
            return $cached;
        }

        $map = [];

        foreach (room_booking_get_rooms($connection) as $room) {
            $room_id = (string) ($room['roomID'] ?? '');

            if ($room_id === '') {
                continue;
            }
            $map[$room_id] = $room;
        }

        $cached = $map;

        return $cached;
    }
}

if (!function_exists('room_booking_get_room_map')) {
    function room_booking_get_room_map(mysqli $connection): array
    {
        static $cached = null;

        if (is_array($cached)) {
            return $cached;
        }

        $room_map = [];

        foreach (room_booking_get_room_detail_map($connection) as $room_id => $room) {
            $room_name = trim((string) ($room['roomName'] ?? ''));
            $room_map[$room_id] = $room_name !== '' ? $room_name : $room_id;
        }

        $cached = $room_map;

        return $cached;
    }
}

if (!function_exists('room_booking_is_room_available')) {
    function room_booking_is_room_available(string $status): bool
    {
        $status = trim($status);

        if ($status === '') {
            return true;
        }

        return $status === 'พร้อมใช้งาน';
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

if (!function_exists('room_booking_get_status_schema')) {
    function room_booking_get_status_schema(mysqli $connection): array
    {
        static $cached = null;

        if (is_array($cached)) {
            return $cached;
        }

        $schema = [
            'mode' => 'int',
            'values' => [],
        ];

        $result = mysqli_query($connection, "SHOW COLUMNS FROM `dh_room_bookings` LIKE 'status'");

        if ($result === false) {
            error_log('Database Error: ' . mysqli_error($connection));
            $cached = $schema;

            return $cached;
        }

        $row = mysqli_fetch_assoc($result);
        mysqli_free_result($result);

        if (!$row || empty($row['Type'])) {
            $cached = $schema;

            return $cached;
        }

        $type = strtolower((string) $row['Type']);

        if (strpos($type, 'enum(') === 0 || strpos($type, 'set(') === 0) {
            $schema['mode'] = 'enum';

            if (preg_match_all("/'((?:\\\\'|[^'])*)'/", $type, $matches)) {
                $schema['values'] = array_map(
                    static fn (string $value): string => str_replace("\\'", "'", $value),
                    $matches[1]
                );
            }
        } elseif (strpos($type, 'char') === 0 || strpos($type, 'varchar') === 0 || strpos($type, 'text') !== false) {
            $schema['mode'] = 'string';
        } else {
            $schema['mode'] = 'int';
        }

        $cached = $schema;

        return $cached;
    }
}

if (!function_exists('room_booking_status_to_db')) {
    function room_booking_status_to_db(mysqli $connection, int $status): array
    {
        $schema = room_booking_get_status_schema($connection);

        if ($schema['mode'] === 'int') {
            return ['type' => 'i', 'value' => $status];
        }

        $values = $schema['values'] ?? [];
        $value = (string) $status;

        if (!empty($values)) {
            $value = $values[$status] ?? $values[0] ?? (string) $status;

            if ($status === 0) {
                foreach ($values as $candidate) {
                    $lower = strtolower($candidate);

                    if ($candidate === '0' || strpos($lower, 'pend') !== false || strpos($lower, 'wait') !== false) {
                        $value = $candidate;
                        break;
                    }
                }
            } elseif ($status === 1) {
                foreach ($values as $candidate) {
                    $lower = strtolower($candidate);

                    if ($candidate === '1' || strpos($lower, 'approv') !== false || strpos($lower, 'allow') !== false) {
                        $value = $candidate;
                        break;
                    }
                }
            } elseif ($status === 2) {
                foreach ($values as $candidate) {
                    $lower = strtolower($candidate);

                    if ($candidate === '2' || strpos($lower, 'reject') !== false || strpos($lower, 'deny') !== false || strpos($lower, 'cancel') !== false) {
                        $value = $candidate;
                        break;
                    }
                }
            }
        }

        return ['type' => 's', 'value' => $value];
    }
}

if (!function_exists('room_booking_status_to_int')) {
    function room_booking_status_to_int(mysqli $connection, $value): int
    {
        $schema = room_booking_get_status_schema($connection);

        if ($schema['mode'] === 'int') {
            return (int) $value;
        }

        $raw = trim((string) $value);

        if ($raw === '') {
            return 0;
        }

        $lower = strtolower($raw);

        if (strpos($lower, 'pend') !== false || strpos($lower, 'wait') !== false) {
            return 0;
        }

        if (strpos($lower, 'draft') !== false) {
            return 0;
        }

        if (strpos($lower, 'approv') !== false || strpos($lower, 'allow') !== false) {
            return 1;
        }

        if (strpos($lower, 'complet') !== false) {
            return 1;
        }

        if (strpos($lower, 'reject') !== false || strpos($lower, 'deny') !== false || strpos($lower, 'cancel') !== false) {
            return 2;
        }

        if (!empty($schema['values'])) {
            $index = array_search($raw, $schema['values'], true);

            if ($index !== false) {
                return (int) $index;
            }
        }

        if (ctype_digit($raw)) {
            return (int) $raw;
        }

        return 0;
    }
}

if (!function_exists('room_booking_get_active_status_values')) {
    function room_booking_get_active_status_values(mysqli $connection): array
    {
        $pending = room_booking_status_to_db($connection, 0);
        $approved = room_booking_status_to_db($connection, 1);

        return [
            'types' => $pending['type'] . $approved['type'],
            'values' => [$pending['value'], $approved['value']],
        ];
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

            if ($status !== 1) {
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

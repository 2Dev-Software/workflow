<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../config/connection.php';
require_once __DIR__ . '/room-booking-utils.php';

$connection = $connection ?? ($GLOBALS['connection'] ?? null);

if (!($connection instanceof mysqli)) {
    return;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$room_booking_approval_year = isset($dh_year_value) ? (int) $dh_year_value : 0;

if ($room_booking_approval_year <= 0) {
    $room_booking_approval_year = (int) date('Y') + 543;
}

$room_booking_room_list = room_booking_get_rooms($connection);
$room_booking_rooms = room_booking_get_room_detail_map($connection);
$room_booking_columns = room_booking_get_table_columns($connection, 'dh_room_bookings');

$room_booking_approval_query = trim((string) ($_GET['q'] ?? ''));
$room_booking_approval_status = trim((string) ($_GET['status'] ?? 'all'));
$room_booking_approval_room = trim((string) ($_GET['room'] ?? 'all'));

$status_filter_map = [
    'pending' => 0,
    'approved' => 1,
    'rejected' => 2,
];

if (!isset($status_filter_map[$room_booking_approval_status])) {
    $room_booking_approval_status = 'all';
}

$room_booking_approval_requests = [];
$room_booking_approval_total = 0;
$room_booking_approval_pending_total = 0;
$room_booking_approval_approved_total = 0;
$room_booking_approval_rejected_total = 0;

$optional_columns = [
    'bookingTopic',
    'bookingDetail',
    'equipmentDetail',
    'requesterDisplayName',
];

$select_fields = [
    'b.roomBookingID',
    'b.dh_year',
    'b.requesterPID',
    'b.roomID',
    'b.startDate',
    'b.endDate',
    'b.startTime',
    'b.endTime',
    'b.attendeeCount',
    'b.status',
    'b.approvedByPID',
    'b.approvedAt',
    'b.createdAt',
    'b.updatedAt',
];

foreach ($optional_columns as $column) {
    if (room_booking_has_column($room_booking_columns, $column)) {
        $select_fields[] = 'b.' . $column;
    }
}

$select_fields[] = 'r.roomName AS room_name';
$select_fields[] = 'req.fName AS requester_name';
$select_fields[] = 'req.telephone AS requester_phone';
$select_fields[] = 'd.dName AS department_name';
$select_fields[] = 'app.fName AS approver_name';

$where = [
    'b.deletedAt IS NULL',
    'b.dh_year = ?',
];
$types = 'i';
$params = [$room_booking_approval_year];

if ($room_booking_approval_room !== 'all' && isset($room_booking_rooms[$room_booking_approval_room])) {
    $where[] = 'b.roomID = ?';
    $types .= 'i';
    $params[] = (int) $room_booking_approval_room;
}

if ($room_booking_approval_status !== 'all') {
    $status_value = $status_filter_map[$room_booking_approval_status];
    $status_param = room_booking_status_to_db($connection, $status_value);
    $where[] = 'b.status = ?';
    $types .= $status_param['type'];
    $params[] = $status_param['value'];
}

if ($room_booking_approval_query !== '') {
    $search_like = '%' . $room_booking_approval_query . '%';
    $search_parts = [];
    $search_types = '';
    $search_params = [];

    if (room_booking_has_column($room_booking_columns, 'requesterDisplayName')) {
        $search_parts[] = 'b.requesterDisplayName LIKE ?';
        $search_types .= 's';
        $search_params[] = $search_like;
    }

    if (room_booking_has_column($room_booking_columns, 'bookingTopic')) {
        $search_parts[] = 'b.bookingTopic LIKE ?';
        $search_types .= 's';
        $search_params[] = $search_like;
    }

    $search_parts[] = 'req.fName LIKE ?';
    $search_types .= 's';
    $search_params[] = $search_like;

    $search_parts[] = 'r.roomName LIKE ?';
    $search_types .= 's';
    $search_params[] = $search_like;

    $search_parts[] = 'd.dName LIKE ?';
    $search_types .= 's';
    $search_params[] = $search_like;

    if ($search_parts !== []) {
        $where[] = '(' . implode(' OR ', $search_parts) . ')';
        $types .= $search_types;

        foreach ($search_params as $v) {
            $params[] = $v;
        }
    }
}

$sql = 'SELECT ' . implode(', ', $select_fields) . ' FROM dh_room_bookings AS b
    LEFT JOIN dh_rooms AS r ON b.roomID = r.roomID
    LEFT JOIN teacher AS req ON b.requesterPID = req.pID
    LEFT JOIN department AS d ON req.dID = d.dID
    LEFT JOIN teacher AS app ON b.approvedByPID = app.pID
    WHERE ' . implode(' AND ', $where) . '
    ORDER BY b.createdAt DESC, b.roomBookingID DESC';

try {
    $stmt = mysqli_prepare($connection, $sql);

    $bind_params = [];
    $bind_params[] = $stmt;
    $bind_params[] = $types;

    foreach ($params as $i => $v) {
        $bind_params[] = &$params[$i];
    }
    call_user_func_array('mysqli_stmt_bind_param', $bind_params);

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $status_value = room_booking_status_to_int($connection, $row['status'] ?? 0);

            if (!in_array($status_value, [0, 1, 2], true)) {
                $status_value = $status_value === 1 ? 1 : 2;
            }

            $room_id = (string) ($row['roomID'] ?? '');
            $room_name = trim((string) ($row['room_name'] ?? ''));

            if ($room_name === '') {
                $room_name = $room_booking_rooms[$room_id]['roomName'] ?? $room_id;
            }

            $requester_name = trim((string) ($row['requester_name'] ?? ''));

            if ($requester_name === '') {
                $requester_name = trim((string) ($row['requesterDisplayName'] ?? ''));
            }
            $requester_name = $requester_name !== '' ? $requester_name : '-';

            $department_name = trim((string) ($row['department_name'] ?? ''));
            $contact_phone = trim((string) ($row['requester_phone'] ?? ''));

            $row['status'] = $status_value;
            $row['roomName'] = $room_name !== '' ? $room_name : $room_id;
            $row['requesterName'] = $requester_name;
            $row['departmentName'] = $department_name !== '' ? $department_name : '-';
            $row['contactPhone'] = $contact_phone !== '' ? $contact_phone : '-';
            $row['approvedByName'] = trim((string) ($row['approver_name'] ?? ''));
            $row['startTime'] = room_booking_normalize_time($row['startTime'] ?? '');
            $row['endTime'] = room_booking_normalize_time($row['endTime'] ?? '');
            $row['endDate'] = ($row['endDate'] ?? '') !== '' ? $row['endDate'] : ($row['startDate'] ?? '');

            $room_booking_approval_requests[] = $row;
        }
    }

    mysqli_stmt_close($stmt);
} catch (mysqli_sql_exception $exception) {
    error_log('Database Exception: ' . $exception->getMessage());
}

$status_sort_rank = static function (int $status): int {
    if ($status === 0) {
        return 0; // pending
    }

    if ($status === 1) {
        return 1; // approved
    }

    return 2; // rejected/others
};

$timestamp_or_zero = static function ($value): int {
    $value = trim((string) $value);

    if ($value === '' || $value === '0000-00-00 00:00:00') {
        return 0;
    }
    $ts = strtotime($value);

    return $ts === false ? 0 : $ts;
};

// Default sort: pending -> approved -> rejected, and within each status show latest activity first.
usort(
    $room_booking_approval_requests,
    static function (array $a, array $b) use ($status_sort_rank, $timestamp_or_zero): int {
        $status_a = (int) ($a['status'] ?? 0);
        $status_b = (int) ($b['status'] ?? 0);

        $rank_a = $status_sort_rank($status_a);
        $rank_b = $status_sort_rank($status_b);

        if ($rank_a !== $rank_b) {
            return $rank_a <=> $rank_b;
        }

        $ts_a = max(
            $timestamp_or_zero($a['updatedAt'] ?? ''),
            $timestamp_or_zero($a['approvedAt'] ?? ''),
            $timestamp_or_zero($a['createdAt'] ?? '')
        );
        $ts_b = max(
            $timestamp_or_zero($b['updatedAt'] ?? ''),
            $timestamp_or_zero($b['approvedAt'] ?? ''),
            $timestamp_or_zero($b['createdAt'] ?? '')
        );

        if ($ts_a !== $ts_b) {
            return $ts_b <=> $ts_a;
        }

        $id_a = (int) ($a['roomBookingID'] ?? 0);
        $id_b = (int) ($b['roomBookingID'] ?? 0);

        return $id_b <=> $id_a;
    }
);

$room_booking_approval_total = count($room_booking_approval_requests);

foreach ($room_booking_approval_requests as $request_item) {
    $status_value = (int) ($request_item['status'] ?? 0);

    if ($status_value === 1) {
        $room_booking_approval_approved_total += 1;
    } elseif ($status_value === 2) {
        $room_booking_approval_rejected_total += 1;
    } else {
        $room_booking_approval_pending_total += 1;
    }
}

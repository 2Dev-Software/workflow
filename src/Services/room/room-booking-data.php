<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../config/connection.php';
require_once __DIR__ . '/room-booking-utils.php';

$room_booking_year = isset($room_booking_year) ? (int) $room_booking_year : 0;
if ($room_booking_year <= 0) {
    $room_booking_year = (int) date('Y') + 543;
}

$room_booking_rooms = room_booking_get_room_map($connection);
$room_booking_columns = room_booking_get_table_columns($connection, 'dh_room_bookings');

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
    'b.statusReason',
    'b.approvedByPID',
    'b.approvedAt',
    'b.deletedAt',
    'b.createdAt',
    'b.updatedAt',
];

foreach ($optional_columns as $column) {
    if (room_booking_has_column($room_booking_columns, $column)) {
        $select_fields[] = 'b.' . $column;
    }
}

$select_fields[] = 'req.fName AS requester_name';
$select_fields[] = 'app.fName AS approver_name';

$sql = 'SELECT ' . implode(', ', $select_fields) . ' FROM dh_room_bookings AS b
    LEFT JOIN teacher AS req ON b.requesterPID = req.pID
    LEFT JOIN teacher AS app ON b.approvedByPID = app.pID
    WHERE b.deletedAt IS NULL AND b.dh_year = ?
    ORDER BY b.createdAt DESC, b.roomBookingID DESC';

$room_bookings = [];
$stmt = mysqli_prepare($connection, $sql);

if ($stmt === false) {
    error_log('Database Error: ' . mysqli_error($connection));
} else {
    mysqli_stmt_bind_param($stmt, 'i', $room_booking_year);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $room_id = (string) ($row['roomID'] ?? '');
            $row['roomName'] = $room_booking_rooms[$room_id] ?? $room_id;

            $requester_name = trim((string) ($row['requester_name'] ?? ''));
            if ($requester_name === '') {
                $requester_name = trim((string) ($row['requesterDisplayName'] ?? ''));
            }
            $row['requesterName'] = $requester_name !== '' ? $requester_name : '-';

            $approver_name = trim((string) ($row['approver_name'] ?? ''));
            $row['approvedByName'] = $approver_name;

            $row['startTime'] = room_booking_normalize_time($row['startTime'] ?? '');
            $row['endTime'] = room_booking_normalize_time($row['endTime'] ?? '');

            if (empty($row['endDate'])) {
                $row['endDate'] = $row['startDate'];
            }

            $room_bookings[] = $row;
        }
    }

    mysqli_stmt_close($stmt);
}

$room_booking_total = count($room_bookings);
$room_booking_approved_total = count(array_filter(
    $room_bookings,
    static fn(array $item): bool => (int) ($item['status'] ?? 0) === 1
));
$room_booking_pending_total = count(array_filter(
    $room_bookings,
    static fn(array $item): bool => (int) ($item['status'] ?? 0) === 0
));

$room_booking_pid = (string) ($_SESSION['pID'] ?? '');
$my_bookings = $room_booking_pid === ''
    ? []
    : array_values(array_filter(
        $room_bookings,
        static fn(array $item): bool => (string) ($item['requesterPID'] ?? '') === $room_booking_pid
    ));

$sort_bookings_latest = static function (array $left, array $right): int {
    $left_time = strtotime((string) ($left['createdAt'] ?? '')) ?: 0;
    $right_time = strtotime((string) ($right['createdAt'] ?? '')) ?: 0;

    return $right_time <=> $left_time;
};

$my_bookings_sorted = $my_bookings;
usort($my_bookings_sorted, $sort_bookings_latest);

$my_booking_limit = 5;
$my_booking_total = count($my_bookings_sorted);
$my_booking_display = min($my_booking_limit, $my_booking_total);
$my_bookings_latest = array_slice($my_bookings_sorted, 0, $my_booking_limit);
$my_booking_subtitle = $my_booking_total > 0
    ? "แสดงล่าสุด {$my_booking_display} จากทั้งหมด {$my_booking_total} รายการ"
    : 'ยังไม่มีรายการจองของคุณ';

$room_booking_events = room_booking_build_events($room_bookings, $room_booking_rooms);

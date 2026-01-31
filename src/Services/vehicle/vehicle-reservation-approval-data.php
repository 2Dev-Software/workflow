<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../config/connection.php';
require_once __DIR__ . '/../teacher/teacher-profile.php';
require_once __DIR__ . '/../system/exec-duty-current.php';
require_once __DIR__ . '/vehicle-reservation-utils.php';
require_once __DIR__ . '/vehicle-reservation-data.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$actor_pid = (string) ($_SESSION['pID'] ?? '');
$role_id = (int) ($teacher['roleID'] ?? 0);
$position_id = (int) ($teacher['positionID'] ?? 0);
$acting_pid = '';
if (($exec_duty_current_status ?? 0) === 2 && !empty($exec_duty_current_pid)) {
    $acting_pid = (string) $exec_duty_current_pid;
}

$vehicle_approval_is_director = $position_id === 1 || ($acting_pid !== '' && $acting_pid === $actor_pid);
$vehicle_approval_is_vehicle_officer = in_array($role_id, [1, 3], true);
$vehicle_approval_can_assign = $vehicle_approval_is_vehicle_officer;
$vehicle_approval_can_finalize = $vehicle_approval_is_director;
$vehicle_approval_mode = 'officer';
if ($vehicle_approval_can_finalize && !$vehicle_approval_can_assign) {
    $vehicle_approval_mode = 'director';
} elseif ($vehicle_approval_can_finalize && $vehicle_approval_can_assign) {
    $vehicle_approval_mode = 'both';
}

$vehicle_approval_year = isset($dh_year_value) ? (int) $dh_year_value : 0;
if ($vehicle_approval_year <= 0) {
    $vehicle_approval_year = (int) date('Y') + 543;
}

$vehicle_approval_query = trim((string) ($_GET['q'] ?? ''));
$vehicle_approval_status = trim((string) ($_GET['status'] ?? 'all'));
$vehicle_approval_vehicle = trim((string) ($_GET['vehicle'] ?? 'all'));
$vehicle_approval_date_from = trim((string) ($_GET['date_from'] ?? ''));
$vehicle_approval_date_to = trim((string) ($_GET['date_to'] ?? ''));

$pending_statuses = ['PENDING'];
if ($vehicle_approval_mode === 'director') {
    $pending_statuses = ['ASSIGNED'];
} elseif ($vehicle_approval_mode === 'both') {
    $pending_statuses = ['PENDING', 'ASSIGNED'];
}

$status_filter_map = [
    'pending' => $pending_statuses,
    'approved' => ['APPROVED', 'COMPLETED'],
    'rejected' => ['REJECTED', 'CANCELLED'],
];

if ($vehicle_approval_mode === 'officer') {
    $status_filter_map['approved'][] = 'ASSIGNED';
}

if (!isset($status_filter_map[$vehicle_approval_status])) {
    $vehicle_approval_status = 'all';
}

$vehicle_list = [];
try {
    $vehicle_stmt = mysqli_prepare($connection, 'SELECT vehicleID, vehiclePlate, vehicleType, vehicleBrand, vehicleModel FROM dh_vehicles ORDER BY vehiclePlate ASC');
    if ($vehicle_stmt) {
        mysqli_stmt_execute($vehicle_stmt);
        $vehicle_result = mysqli_stmt_get_result($vehicle_stmt);
        while ($vehicle_result && ($row = mysqli_fetch_assoc($vehicle_result))) {
            $vehicle_list[] = $row;
        }
        mysqli_stmt_close($vehicle_stmt);
    }
} catch (mysqli_sql_exception $exception) {
    error_log('Database Exception: ' . $exception->getMessage());
}

$vehicle_driver_list = [];
try {
    $driver_stmt = mysqli_prepare($connection, 'SELECT pID, fName, telephone FROM teacher WHERE status = 1 ORDER BY fName ASC');
    if ($driver_stmt) {
        mysqli_stmt_execute($driver_stmt);
        $driver_result = mysqli_stmt_get_result($driver_stmt);
        while ($driver_result && ($row = mysqli_fetch_assoc($driver_result))) {
            $vehicle_driver_list[] = [
                'pID' => (string) ($row['pID'] ?? ''),
                'name' => (string) ($row['fName'] ?? ''),
                'telephone' => (string) ($row['telephone'] ?? ''),
            ];
        }
        mysqli_stmt_close($driver_stmt);
    }
} catch (mysqli_sql_exception $exception) {
    error_log('Database Exception: ' . $exception->getMessage());
}

$vehicle_columns = vehicle_reservation_get_table_columns($connection, 'dh_vehicle_bookings');
$select_fields = [
    'b.bookingID',
    'b.dh_year',
    'b.requesterPID',
    'b.vehicleID',
    'b.driverPID',
    'b.driverName',
    'b.driverTel',
    'b.startAt',
    'b.endAt',
    'b.status',
    'b.statusReason',
    'b.approvedByPID',
    'b.approvedAt',
    'b.deletedAt',
    'b.createdAt',
    'b.updatedAt',
];

$optional_columns = [
    'department',
    'purpose',
    'location',
    'passengerCount',
    'fuelSource',
    'writeDate',
    'companionCount',
    'companionIds',
    'requesterDisplayName',
    'attachmentFileID',
];

foreach ($optional_columns as $column) {
    if (vehicle_reservation_has_column($vehicle_columns, $column)) {
        $select_fields[] = 'b.' . $column;
    }
}

$select_fields[] = 'req.fName AS requester_name';
$select_fields[] = 'req.telephone AS requester_phone';
$select_fields[] = 'dep.dName AS department_name';
$select_fields[] = 'app.fName AS approver_name';
$select_fields[] = 'v.vehiclePlate';
$select_fields[] = 'v.vehicleType';
$select_fields[] = 'v.vehicleBrand';
$select_fields[] = 'v.vehicleModel';

$where = [
    'b.deletedAt IS NULL',
    'b.dh_year = ?',
];
$types = 'i';
$params = [$vehicle_approval_year];

if ($vehicle_approval_vehicle !== 'all' && $vehicle_approval_vehicle !== '') {
    $where[] = 'b.vehicleID = ?';
    $types .= 'i';
    $params[] = (int) $vehicle_approval_vehicle;
}

if ($vehicle_approval_status !== 'all') {
    $status_values = $status_filter_map[$vehicle_approval_status];
    $placeholders = implode(', ', array_fill(0, count($status_values), '?'));
    $where[] = 'b.status IN (' . $placeholders . ')';
    $types .= str_repeat('s', count($status_values));
    foreach ($status_values as $status_value) {
        $params[] = $status_value;
    }
}

if ($vehicle_approval_query !== '') {
    $search_like = '%' . $vehicle_approval_query . '%';
    $search_parts = [
        'req.fName LIKE ?',
        'b.driverName LIKE ?',
        'v.vehiclePlate LIKE ?',
        'v.vehicleType LIKE ?',
    ];
    $search_types = 'ssss';
    $search_params = [$search_like, $search_like, $search_like, $search_like];

    if (vehicle_reservation_has_column($vehicle_columns, 'purpose')) {
        $search_parts[] = 'b.purpose LIKE ?';
        $search_types .= 's';
        $search_params[] = $search_like;
    }
    if (vehicle_reservation_has_column($vehicle_columns, 'location')) {
        $search_parts[] = 'b.location LIKE ?';
        $search_types .= 's';
        $search_params[] = $search_like;
    }

    $search_parts[] = 'b.bookingID LIKE ?';
    $search_types .= 's';
    $search_params[] = $search_like;

    if ($search_parts !== []) {
        $where[] = '(' . implode(' OR ', $search_parts) . ')';
        $types .= $search_types;
        foreach ($search_params as $value) {
            $params[] = $value;
        }
    }
}

if ($vehicle_approval_date_from !== '') {
    $date_from_obj = DateTime::createFromFormat('Y-m-d', $vehicle_approval_date_from);
    if ($date_from_obj !== false) {
        $where[] = 'b.startAt >= ?';
        $types .= 's';
        $params[] = $date_from_obj->format('Y-m-d') . ' 00:00:00';
    }
}

if ($vehicle_approval_date_to !== '') {
    $date_to_obj = DateTime::createFromFormat('Y-m-d', $vehicle_approval_date_to);
    if ($date_to_obj !== false) {
        $where[] = 'b.endAt <= ?';
        $types .= 's';
        $params[] = $date_to_obj->format('Y-m-d') . ' 23:59:59';
    }
}

$sql = 'SELECT ' . implode(', ', $select_fields) . ' FROM dh_vehicle_bookings AS b
    LEFT JOIN teacher AS req ON b.requesterPID = req.pID
    LEFT JOIN department AS dep ON req.dID = dep.dID
    LEFT JOIN teacher AS app ON b.approvedByPID = app.pID
    LEFT JOIN dh_vehicles AS v ON b.vehicleID = v.vehicleID
    WHERE ' . implode(' AND ', $where) . '
    ORDER BY b.createdAt DESC, b.bookingID DESC';

$vehicle_booking_requests = [];
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
            $vehicle_booking_requests[] = $row;
        }
    }
    mysqli_stmt_close($stmt);
} catch (mysqli_sql_exception $exception) {
    error_log('Database Exception: ' . $exception->getMessage());
}

$vehicle_booking_total = count($vehicle_booking_requests);
$vehicle_booking_pending_total = 0;
$vehicle_booking_approved_total = 0;
$vehicle_booking_rejected_total = 0;

foreach ($vehicle_booking_requests as $item) {
    $status_key = strtoupper(trim((string) ($item['status'] ?? 'PENDING')));
    $group = 'pending';

    if (in_array($status_key, ['APPROVED', 'COMPLETED'], true)) {
        $group = 'approved';
    } elseif (in_array($status_key, ['REJECTED', 'CANCELLED'], true)) {
        $group = 'rejected';
    } elseif ($status_key === 'ASSIGNED') {
        if ($vehicle_approval_mode === 'officer') {
            $group = 'approved';
        } else {
            $group = 'pending';
        }
    }

    if ($group === 'approved') {
        $vehicle_booking_approved_total += 1;
    } elseif ($group === 'rejected') {
        $vehicle_booking_rejected_total += 1;
    } else {
        $vehicle_booking_pending_total += 1;
    }
}

$booking_ids = array_values(array_filter(array_map(
    static fn(array $booking): int => (int) ($booking['bookingID'] ?? 0),
    $vehicle_booking_requests
)));
$vehicle_booking_attachments = vehicle_reservation_get_booking_attachments($connection, $booking_ids);

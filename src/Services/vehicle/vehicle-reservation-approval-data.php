<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../config/connection.php';
require_once __DIR__ . '/../teacher/teacher-profile.php';
require_once __DIR__ . '/../../../app/modules/system/system.php';
require_once __DIR__ . '/vehicle-reservation-utils.php';
require_once __DIR__ . '/vehicle-reservation-data.php';

$connection = $connection ?? ($GLOBALS['connection'] ?? null);
if (!($connection instanceof mysqli)) {
    return;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$actor_pid = (string) ($_SESSION['pID'] ?? '');
$role_id = (int) ($teacher['roleID'] ?? 0);
$position_id = (int) ($teacher['positionID'] ?? 0);
$current_director_pid = system_get_current_director_pid();
$vehicle_approval_is_director = $current_director_pid !== null && $current_director_pid !== '' && $current_director_pid === $actor_pid;
// Executive (director / acting director) scope for filtering to prevent mixing decisions across executives.
$acting_director_pid = system_get_acting_director_pid();
$vehicle_approval_exec_pid = null;
if ($position_id === 1) {
    $vehicle_approval_exec_pid = $actor_pid;
} elseif ($acting_director_pid !== null && $acting_director_pid !== '' && $acting_director_pid === $actor_pid) {
    $vehicle_approval_exec_pid = $actor_pid;
}
// roleID mapping (legacy): 1=ADMIN, 3=VEHICLE
$vehicle_approval_is_admin = $role_id === 1;
$vehicle_approval_is_vehicle_officer = $role_id === 3;
// Admin is view-only for vehicle approval flows.
$vehicle_approval_can_assign = $vehicle_approval_is_vehicle_officer && !$vehicle_approval_is_admin;
$vehicle_approval_can_finalize = $vehicle_approval_is_director;
$vehicle_approval_mode = 'viewer';
if ($vehicle_approval_can_assign && $vehicle_approval_can_finalize) {
    $vehicle_approval_mode = 'both';
} elseif ($vehicle_approval_can_finalize) {
    $vehicle_approval_mode = 'director';
} elseif ($vehicle_approval_can_assign) {
    $vehicle_approval_mode = 'officer';
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
$vehicle_approval_page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$vehicle_approval_per_page_param = (string) ($_GET['per_page'] ?? '10');
$vehicle_approval_per_page = 10;
if ($vehicle_approval_per_page_param === 'all') {
    $vehicle_approval_per_page = 'all';
} else {
    $vehicle_approval_per_page = (int) $vehicle_approval_per_page_param;
}
$vehicle_approval_allowed_per_page = [10, 20, 50, 'all'];
if (!in_array($vehicle_approval_per_page, $vehicle_approval_allowed_per_page, true)) {
    $vehicle_approval_per_page = 10;
}

$pending_statuses = ['PENDING', 'ASSIGNED'];
if ($vehicle_approval_mode === 'director') {
    // Directors act after officers assign.
    $pending_statuses = ['ASSIGNED'];
} elseif ($vehicle_approval_mode === 'officer') {
    // Officers start at submitted requests.
    $pending_statuses = ['PENDING', 'ASSIGNED'];
} elseif ($vehicle_approval_mode === 'both') {
    $pending_statuses = ['PENDING', 'ASSIGNED'];
}

// For director/acting-director view, only show stages relevant to executives.
// Requirement: executives should see "กำลังดำเนินการ" (ASSIGNED), "อนุมัติการจองสำเร็จ" (APPROVED),
// and "ไม่อนุมัติ" (REJECTED).
$vehicle_approval_visible_statuses = null;
if ($vehicle_approval_mode === 'director') {
    $vehicle_approval_visible_statuses = ['ASSIGNED', 'APPROVED', 'REJECTED'];
} elseif ($vehicle_approval_exec_pid !== null && !$vehicle_approval_can_finalize && !$vehicle_approval_can_assign) {
    // Director (positionID=1) can still view their own decisions when an acting director is appointed.
    $vehicle_approval_visible_statuses = ['APPROVED', 'REJECTED'];
}

$status_filter_map = [
    'pending' => $pending_statuses,
    'approved' => ['APPROVED', 'COMPLETED'],
    'rejected' => ['REJECTED', 'CANCELLED'],
];

if ($vehicle_approval_mode === 'director') {
    // Executives focus on final decision outcomes. Completed/cancelled are out of scope for this screen.
    $status_filter_map['approved'] = ['APPROVED'];
    $status_filter_map['rejected'] = ['REJECTED'];
}

if (!isset($status_filter_map[$vehicle_approval_status])) {
    $vehicle_approval_status = 'all';
}

$vehicle_list = [];
try {
    $vehicle_table_columns = vehicle_reservation_get_table_columns($connection, 'dh_vehicles');
    $vehicle_sql = 'SELECT vehicleID, vehiclePlate, vehicleType, vehicleBrand, vehicleModel FROM dh_vehicles';
    $vehicle_conditions = [];
    if (vehicle_reservation_has_column($vehicle_table_columns, 'deletedAt')) {
        $vehicle_conditions[] = 'deletedAt IS NULL';
    }
    if (vehicle_reservation_has_column($vehicle_table_columns, 'vehicleStatus')) {
        // Only show vehicles that are ready to use in assignment/filter dropdowns.
        $vehicle_conditions[] = "vehicleStatus = 'พร้อมใช้งาน'";
    }
    if (!empty($vehicle_conditions)) {
        $vehicle_sql .= ' WHERE ' . implode(' AND ', $vehicle_conditions);
    }
    $vehicle_sql .= ' ORDER BY vehiclePlate ASC';

    $vehicle_stmt = mysqli_prepare($connection, $vehicle_sql);
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
    vehicle_reservation_has_column($vehicle_columns, 'driverTel')
        ? "COALESCE(NULLIF(drv.telephone, ''), b.driverTel) AS driverTel"
        : 'drv.telephone AS driverTel',
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
    'assignedByPID',
    'assignedAt',
    'assignedNote',
    'approvalNote',
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
$assigned_join = '';
if (vehicle_reservation_has_column($vehicle_columns, 'assignedByPID')) {
    $select_fields[] = 'asg.fName AS assigned_name';
    $assigned_join = 'LEFT JOIN teacher AS asg ON b.assignedByPID = asg.pID';
}
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

// Prevent executives from seeing other executives' final decisions:
// - ASSIGNED/PENDING are operational stages (handled by current workflow roles)
// - APPROVED/REJECTED are final decisions and should be scoped to the executive (director/acting) who recorded them.
if ($vehicle_approval_exec_pid !== null) {
    $where[] = "(b.status NOT IN ('APPROVED', 'REJECTED') OR b.approvedByPID = ?)";
    $types .= 's';
    $params[] = $vehicle_approval_exec_pid;
}

if (is_array($vehicle_approval_visible_statuses) && $vehicle_approval_visible_statuses !== []) {
    $placeholders = implode(', ', array_fill(0, count($vehicle_approval_visible_statuses), '?'));
    $where[] = 'b.status IN (' . $placeholders . ')';
    $types .= str_repeat('s', count($vehicle_approval_visible_statuses));
    foreach ($vehicle_approval_visible_statuses as $status_value) {
        $params[] = $status_value;
    }
}

if ($vehicle_approval_vehicle !== 'all' && $vehicle_approval_vehicle !== '') {
    $where[] = 'b.vehicleID = ?';
    $types .= 'i';
    $params[] = (int) $vehicle_approval_vehicle;
}

if ($vehicle_approval_status !== 'all') {
    $status_values = $status_filter_map[$vehicle_approval_status];
    if ($status_values === []) {
        $where[] = '0=1';
    } else {
        $placeholders = implode(', ', array_fill(0, count($status_values), '?'));
        $where[] = 'b.status IN (' . $placeholders . ')';
        $types .= str_repeat('s', count($status_values));
        foreach ($status_values as $status_value) {
            $params[] = $status_value;
        }
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
    LEFT JOIN teacher AS drv ON b.driverPID = drv.pID
    LEFT JOIN department AS dep ON req.dID = dep.dID
    LEFT JOIN teacher AS app ON b.approvedByPID = app.pID
    ' . $assigned_join . '
    LEFT JOIN dh_vehicles AS v ON b.vehicleID = v.vehicleID
    WHERE ' . implode(' AND ', $where) . '
    ORDER BY
        CASE
            WHEN b.status = \'PENDING\' THEN 1
            WHEN b.status = \'ASSIGNED\' THEN 2
            WHEN b.status = \'APPROVED\' THEN 3
            WHEN b.status = \'COMPLETED\' THEN 4
            WHEN b.status = \'REJECTED\' THEN 5
            WHEN b.status = \'CANCELLED\' THEN 6
            ELSE 99
        END ASC,
        COALESCE(
            NULLIF(b.updatedAt, \'0000-00-00 00:00:00\'),
            NULLIF(b.createdAt, \'0000-00-00 00:00:00\')
        ) DESC,
        b.bookingID DESC';

$vehicle_approval_total = 0;
$vehicle_approval_total_pages = 0;
$vehicle_approval_limit = null;
$vehicle_approval_offset = null;

try {
    $count_sql = 'SELECT COUNT(*) AS total FROM dh_vehicle_bookings AS b
        LEFT JOIN teacher AS req ON b.requesterPID = req.pID
        LEFT JOIN teacher AS drv ON b.driverPID = drv.pID
        LEFT JOIN department AS dep ON req.dID = dep.dID
        LEFT JOIN teacher AS app ON b.approvedByPID = app.pID
        ' . $assigned_join . '
        LEFT JOIN dh_vehicles AS v ON b.vehicleID = v.vehicleID
        WHERE ' . implode(' AND ', $where);

    $count_stmt = mysqli_prepare($connection, $count_sql);
    $bind_params = [];
    $bind_params[] = $count_stmt;
    $bind_params[] = $types;
    foreach ($params as $i => $v) {
        $bind_params[] = &$params[$i];
    }
    call_user_func_array('mysqli_stmt_bind_param', $bind_params);
    mysqli_stmt_execute($count_stmt);
    $count_result = mysqli_stmt_get_result($count_stmt);
    if ($count_result && ($row = mysqli_fetch_assoc($count_result))) {
        $vehicle_approval_total = (int) ($row['total'] ?? 0);
    }
    mysqli_stmt_close($count_stmt);
} catch (mysqli_sql_exception $exception) {
    error_log('Database Exception: ' . $exception->getMessage());
}

if ($vehicle_approval_per_page === 'all') {
    $vehicle_approval_total_pages = $vehicle_approval_total > 0 ? 1 : 0;
} else {
    $vehicle_approval_per_page = max((int) $vehicle_approval_per_page, 1);
    $vehicle_approval_total_pages = (int) ceil($vehicle_approval_total / $vehicle_approval_per_page);
}

if ($vehicle_approval_total_pages > 0 && $vehicle_approval_page > $vehicle_approval_total_pages) {
    $vehicle_approval_page = $vehicle_approval_total_pages;
}

if ($vehicle_approval_per_page !== 'all') {
    $vehicle_approval_limit = (int) $vehicle_approval_per_page;
    $vehicle_approval_offset = ($vehicle_approval_page - 1) * $vehicle_approval_limit;
    $sql .= ' LIMIT ? OFFSET ?';
    $types .= 'ii';
    $params[] = $vehicle_approval_limit;
    $params[] = $vehicle_approval_offset;
}

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

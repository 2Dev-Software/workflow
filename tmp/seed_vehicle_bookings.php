<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/connection.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$seed_year = 2568;
$seed_tag = 'SEED:vehicle_booking_pagination';
$target_rows = 20;

$director_pid = '3810500334835'; // positionID=1 in this DB
$vehicle_officer_pid = '3180600191510'; // roleID=3 in this DB

$driver_name = '';
$driver_tel = '';
try {
    $stmt = mysqli_prepare($connection, 'SELECT fName, telephone FROM teacher WHERE pID = ? AND status = 1 LIMIT 1');
    mysqli_stmt_bind_param($stmt, 's', $vehicle_officer_pid);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);
    if ($row) {
        $driver_name = trim((string) ($row['fName'] ?? ''));
        $driver_tel = trim((string) ($row['telephone'] ?? ''));
    }
} catch (mysqli_sql_exception $e) {
    // ignore
}

if ($driver_name === '') {
    $driver_name = 'เจ้าหน้าที่งานยานพาหนะ';
}

$requester_pids = [];
try {
    $res = mysqli_query(
        $connection,
        "SELECT pID FROM teacher WHERE status = 1 AND positionID <> 1 ORDER BY pID ASC LIMIT 8"
    );
    while ($res && ($row = mysqli_fetch_assoc($res))) {
        $pid = trim((string) ($row['pID'] ?? ''));
        if ($pid !== '') {
            $requester_pids[] = $pid;
        }
    }
} catch (mysqli_sql_exception $e) {
    // ignore
}

if ($requester_pids === []) {
    fwrite(STDERR, "No requester PID candidates found.\n");
    exit(1);
}

$existing = 0;
try {
    $stmt = mysqli_prepare(
        $connection,
        'SELECT COUNT(*) AS c FROM dh_vehicle_bookings WHERE dh_year = ? AND deletedAt IS NULL AND purpose LIKE CONCAT(?, \'%\')'
    );
    mysqli_stmt_bind_param($stmt, 'is', $seed_year, $seed_tag);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);
    $existing = (int) ($row['c'] ?? 0);
} catch (mysqli_sql_exception $e) {
    // ignore
}

$to_insert = max(0, $target_rows - $existing);
if ($to_insert <= 0) {
    echo "Seed rows already present: {$existing}\n";
    exit(0);
}

$insert_sql = 'INSERT INTO dh_vehicle_bookings (
    dh_year, requesterPID, department, purpose, location, passengerCount, fuelSource, writeDate,
    companionCount, companionIds, requesterDisplayName, attachmentFileID,
    vehicleID, driverPID, driverName, driverTel, assignedByPID, assignedAt, assignedNote,
    startAt, endAt, status, statusReason, approvalNote, approvedByPID, approvedAt
) VALUES (
    ?, ?, ?, ?, ?, ?, ?, ?,
    ?, ?, ?, ?,
    ?, ?, ?, ?, ?, ?, ?,
    ?, ?, ?, ?, ?, ?, ?
)';

$insert_stmt = mysqli_prepare($connection, $insert_sql);
$types = str_repeat('s', 26);

$base_start = new DateTimeImmutable('2026-02-10 08:00:00');
$vehicle_ids = ['2', '1'];

$inserted = 0;
for ($i = 0; $i < $to_insert; $i++) {
    $pos = $existing + $i; // 0..19

    $status = 'PENDING';
    if ($pos < 5) {
        $status = 'PENDING';
    } elseif ($pos < 11) {
        $status = 'ASSIGNED';
    } elseif ($pos < 16) {
        $status = 'APPROVED';
    } else {
        $status = 'REJECTED';
    }

    $start = $base_start->modify('+' . $pos . ' day');
    $end = $start->modify('+2 hours 45 minutes');

    $write_date = $start->format('Y-m-d');
    $start_at = $start->format('Y-m-d H:i:s');
    $end_at = $end->format('Y-m-d H:i:s');

    $requester_pid = $requester_pids[$pos % count($requester_pids)];

    $purpose = $seed_tag
        . " #{$pos} เดินทางเพื่อทดสอบระบบ pagination และการเลือกจำนวนต่อหน้า (per-page) ให้ครบถ้วนตามมาตรฐาน";
    $location = "สถานที่ทดสอบ #{$pos} อำเภอเมือง จังหวัดพังงา";

    $passenger_count = (string) (2 + ($pos % 6)); // 2..7
    $companion_count = (string) max(0, ((int) $passenger_count) - 1);

    $department = null;
    $fuel_source = 'central';
    $companion_ids = null;

    $vehicle_id = null;
    $driver_pid = null;
    $driver_name_value = null;
    $driver_tel_value = null;
    $assigned_by = null;
    $assigned_at = null;
    $assigned_note = null;

    $status_reason = null;
    $approval_note = null;
    $approved_by = null;
    $approved_at = null;

    if ($status !== 'PENDING') {
        $vehicle_id = $vehicle_ids[$pos % 2];
        $driver_pid = $vehicle_officer_pid;
        $driver_name_value = $driver_name;
        $driver_tel_value = $driver_tel !== '' ? $driver_tel : null;
        $assigned_by = $vehicle_officer_pid;
        $assigned_at = $start->modify('-3 hours')->format('Y-m-d H:i:s');
        $assigned_note = 'ทดสอบการมอบหมายรถและคนขับ';
    }

    if ($status === 'ASSIGNED') {
        $approved_by = $vehicle_officer_pid;
        $approved_at = $assigned_at;
    } elseif ($status === 'APPROVED') {
        $approval_note = 'เห็นควรอนุมัติ (ทดสอบ)';
        $approved_by = $director_pid;
        $approved_at = $start->modify('-30 minutes')->format('Y-m-d H:i:s');
    } elseif ($status === 'REJECTED') {
        $approval_note = 'ไม่อนุมัติ (ทดสอบ)';
        $status_reason = $approval_note;
        $approved_by = $director_pid;
        $approved_at = $start->modify('-20 minutes')->format('Y-m-d H:i:s');
    }

    $values = [
        (string) $seed_year,
        $requester_pid,
        $department,
        $purpose,
        $location,
        $passenger_count,
        $fuel_source,
        $write_date,
        $companion_count,
        $companion_ids,
        null, // requesterDisplayName
        null, // attachmentFileID
        $vehicle_id,
        $driver_pid,
        $driver_name_value,
        null, // driverTel (derive from teacher)
        $assigned_by,
        $assigned_at,
        $assigned_note,
        $start_at,
        $end_at,
        $status,
        $status_reason,
        $approval_note,
        $approved_by,
        $approved_at,
    ];

    $bind_params = [];
    $bind_params[] = $insert_stmt;
    $bind_params[] = $types;
    foreach ($values as $k => $v) {
        $bind_params[] = &$values[$k];
    }
    call_user_func_array('mysqli_stmt_bind_param', $bind_params);
    mysqli_stmt_execute($insert_stmt);
    $inserted++;
}

mysqli_stmt_close($insert_stmt);

echo "Inserted {$inserted} vehicle booking rows.\n";


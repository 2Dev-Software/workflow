<?php
require_once __DIR__ . '/../../../config/connection.php';
require_once __DIR__ . '/../../../app/modules/system/positions.php';

$connection = $connection ?? ($GLOBALS['connection'] ?? null);
if (!($connection instanceof mysqli)) {
    return;
}

$room_management_alert = $room_management_alert ?? null;
$room_staff_members = [];
$room_candidate_members = [];
$room_staff_role_id = 5;
$admin_role_id = 1;
$default_role_id = 6;
$unassigned_role_id = 0;

$map_member = static function (array $row): array {
    $name = trim((string) ($row['fName'] ?? ''));
    if ($name === '') {
        $name = 'ไม่ระบุชื่อ';
    }

    return [
        'pID' => (string) ($row['pID'] ?? ''),
        'name' => $name,
        'position_name' => trim((string) ($row['position_name'] ?? '')),
        'role_name' => trim((string) ($row['role_name'] ?? '')),
        'department_name' => trim((string) ($row['department_name'] ?? '')),
        'telephone' => trim((string) ($row['telephone'] ?? '')),
    ];
};

$position = system_position_join($connection, 't', 'p');

$staff_sql = 'SELECT t.pID, t.fName, t.positionID, t.roleID, t.telephone,
    ' . $position['name'] . ' AS position_name,
    r.roleName AS role_name,
    d.dName AS department_name
    FROM teacher AS t
    ' . $position['join'] . '
    LEFT JOIN dh_roles AS r ON t.roleID = r.roleID
    LEFT JOIN department AS d ON t.dID = d.dID
    WHERE t.status = 1 AND t.roleID = ?
    ORDER BY t.fName';

try {
    $stmt = mysqli_prepare($connection, $staff_sql);
    if ($stmt === false) {
        error_log('Database Error: ' . mysqli_error($connection));
        $room_management_alert = [
            'type' => 'danger',
            'title' => 'ระบบขัดข้อง',
            'message' => 'ไม่สามารถโหลดข้อมูลเจ้าหน้าที่สถานที่ได้ในขณะนี้',
            'button_label' => 'ยืนยัน',
        ];
    } else {
        mysqli_stmt_bind_param($stmt, 'i', $room_staff_role_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result !== false) {
            while ($row = mysqli_fetch_assoc($result)) {
                $room_staff_members[] = $map_member($row);
            }
        }

        mysqli_stmt_close($stmt);
    }
} catch (mysqli_sql_exception $exception) {
    error_log('Database Exception: ' . $exception->getMessage());
    $room_management_alert = [
        'type' => 'danger',
        'title' => 'ระบบขัดข้อง',
        'message' => 'ไม่สามารถโหลดข้อมูลเจ้าหน้าที่สถานที่ได้ในขณะนี้',
        'button_label' => 'ยืนยัน',
    ];
}

$candidate_sql = 'SELECT t.pID, t.fName, t.positionID, t.roleID, t.telephone,
    ' . $position['name'] . ' AS position_name,
    r.roleName AS role_name,
    d.dName AS department_name
    FROM teacher AS t
    ' . $position['join'] . '
    LEFT JOIN dh_roles AS r ON t.roleID = r.roleID
    LEFT JOIN department AS d ON t.dID = d.dID
    WHERE t.status = 1 AND t.roleID IN (?, ?)
    ORDER BY t.fName';

try {
    $stmt = mysqli_prepare($connection, $candidate_sql);
    if ($stmt === false) {
        error_log('Database Error: ' . mysqli_error($connection));
        $room_management_alert = $room_management_alert ?? [
            'type' => 'danger',
            'title' => 'ระบบขัดข้อง',
            'message' => 'ไม่สามารถโหลดรายชื่อบุคลากรได้ในขณะนี้',
            'button_label' => 'ยืนยัน',
        ];
    } else {
        mysqli_stmt_bind_param($stmt, 'ii', $default_role_id, $unassigned_role_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result !== false) {
            while ($row = mysqli_fetch_assoc($result)) {
                $room_candidate_members[] = $map_member($row);
            }
        }

        mysqli_stmt_close($stmt);
    }
} catch (mysqli_sql_exception $exception) {
    error_log('Database Exception: ' . $exception->getMessage());
    if ($room_management_alert === null) {
        $room_management_alert = [
            'type' => 'danger',
            'title' => 'ระบบขัดข้อง',
            'message' => 'ไม่สามารถโหลดรายชื่อบุคลากรได้ในขณะนี้',
            'button_label' => 'ยืนยัน',
        ];
    }
}

$room_staff_count = count($room_staff_members);
$room_candidate_count = count($room_candidate_members);

$room_management_rooms = [];
$room_sql = 'SELECT roomID, roomName, roomStatus, roomNote, createdAt, updatedAt
    FROM dh_rooms
    WHERE deletedAt IS NULL
    ORDER BY roomName';

try {
    $result = mysqli_query($connection, $room_sql);
    if ($result === false) {
        error_log('Database Error: ' . mysqli_error($connection));
        $room_management_alert = $room_management_alert ?? [
            'type' => 'danger',
            'title' => 'ระบบขัดข้อง',
            'message' => 'ไม่สามารถโหลดข้อมูลห้องได้ในขณะนี้',
            'button_label' => 'ยืนยัน',
        ];
    } else {
        while ($row = mysqli_fetch_assoc($result)) {
            $room_management_rooms[] = [
                'roomID' => (int) ($row['roomID'] ?? 0),
                'roomName' => trim((string) ($row['roomName'] ?? '')),
                'roomStatus' => trim((string) ($row['roomStatus'] ?? '')),
                'roomNote' => trim((string) ($row['roomNote'] ?? '')),
                'createdAt' => (string) ($row['createdAt'] ?? ''),
                'updatedAt' => (string) ($row['updatedAt'] ?? ''),
            ];
        }
        mysqli_free_result($result);
    }
} catch (mysqli_sql_exception $exception) {
    error_log('Database Exception: ' . $exception->getMessage());
    $room_management_alert = $room_management_alert ?? [
        'type' => 'danger',
        'title' => 'ระบบขัดข้อง',
        'message' => 'ไม่สามารถโหลดข้อมูลห้องได้ในขณะนี้',
        'button_label' => 'ยืนยัน',
    ];
}

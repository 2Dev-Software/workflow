<?php

require_once __DIR__ . '/../../../config/connection.php';
require_once __DIR__ . '/../../../app/modules/system/positions.php';

$exec_duty_announcement = 'วันนี้ยังไม่มีข้อมูลการปฏิบัติราชการ';
$exec_duty_name = '';
$exec_duty_position = '';
$exec_duty_status_label = '';

try {
    $position = system_position_join($connection, 't', 'p');
    $sql = 'SELECT l.dutyStatus, t.fName, ' . $position['name'] . ' AS positionName
        FROM dh_exec_duty_logs AS l
        INNER JOIN teacher AS t ON l.pID = t.pID
        ' . $position['join'] . '
        WHERE l.dutyStatus IN (1, 2) AND t.status = 1
        ORDER BY l.dutyLogID DESC
        LIMIT 1';
    $stmt = mysqli_prepare($connection, $sql);

    if ($stmt === false) {
        error_log('Database Error: ' . mysqli_error($connection));
    } else {
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            $exec_duty_name = trim((string) ($row['fName'] ?? ''));
            $exec_duty_position = trim((string) ($row['positionName'] ?? ''));

            $duty_status = (int) ($row['dutyStatus'] ?? 0);

            if ($duty_status === 1) {
                $exec_duty_status_label = 'ปฏิบัติราชการ';
            } elseif ($duty_status === 2) {
                $exec_duty_status_label = 'รักษาราชการแทน';
            }

            $school_name = 'โรงเรียนดีบุกพังงาวิทยายน';
            $school_suffix = preg_replace('/^โรงเรียน/u', '', $school_name);

            if ($duty_status === 2) {
                $exec_duty_position = 'รองผู้อำนวยการ' . $school_name;
            } elseif ($exec_duty_position !== '' && strpos($exec_duty_position, $school_name) === false) {
                if (strpos($exec_duty_position, 'โรงเรียน') !== false) {
                    $exec_duty_position .= $school_suffix;
                } else {
                    $exec_duty_position .= $school_name;
                }
            }

            $parts = array_filter(['วันนี้', $exec_duty_name, $exec_duty_position, $exec_duty_status_label]);

            if (count($parts) > 1) {
                $exec_duty_announcement = implode(' ', $parts);
            }
        }

        mysqli_stmt_close($stmt);
    }
} catch (mysqli_sql_exception $e) {
    error_log('Database Exception: ' . $e->getMessage());
}

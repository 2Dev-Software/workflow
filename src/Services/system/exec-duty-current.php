<?php
require_once __DIR__ . '/../../../config/connection.php';

$exec_duty_current_pid = '';
$exec_duty_current_status = 0;

try {
    $sql = 'SELECT pID, dutyStatus FROM dh_exec_duty_logs WHERE dutyStatus IN (1, 2) ORDER BY dutyLogID DESC LIMIT 1';
    $stmt = mysqli_prepare($connection, $sql);

    if ($stmt === false) {
        error_log('Database Error: ' . mysqli_error($connection));
    } else {
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            $exec_duty_current_pid = (string) $row['pID'];
            $exec_duty_current_status = (int) $row['dutyStatus'];
        }

        mysqli_stmt_close($stmt);
    }
} catch (mysqli_sql_exception $e) {
    error_log('Database Exception: ' . $e->getMessage());
}
?>

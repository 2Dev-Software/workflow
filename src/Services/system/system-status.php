<?php
require_once __DIR__ . '/../../../config/connection.php';

$dh_status = 1;

try {
    $sql = 'SELECT dh_status FROM thesystem ORDER BY ID DESC LIMIT 1';
    $stmt = mysqli_prepare($connection, $sql);

    if ($stmt === false) {
        error_log('Database Error: ' . mysqli_error($connection));
    } else {
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            $dh_status = (int) $row['dh_status'];
        }

        mysqli_stmt_close($stmt);
    }
} catch (mysqli_sql_exception $e) {
    error_log('Database Exception: ' . $e->getMessage());
}
?>

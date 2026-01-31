<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($connection) || !($connection instanceof mysqli)) {
    require_once __DIR__ . '/../../../config/connection.php';
}
require_once __DIR__ . '/../../../app/modules/system/positions.php';

if (!isset($connection) || !($connection instanceof mysqli)) {
    $connection = $GLOBALS['connection'] ?? null;
}

$teacher = [];
$teacher_pid = $_SESSION['pID'] ?? '';

if (!($connection instanceof mysqli)) {
    return;
}

if ($teacher_pid !== '') {
    try {
        $position = system_position_join($connection, 't', 'p');
        $sql = 'SELECT t.pID, t.fName, t.fID, t.dID, t.lID, t.oID, t.positionID, t.roleID, t.telephone, t.picture, t.signature, t.status,
            f.fName AS faction_name,
            d.dName AS department_name,
            l.lName AS level_name,
            ' . $position['name'] . ' AS position_name,
            r.roleName AS role_name
            FROM teacher AS t
            LEFT JOIN faction AS f ON t.fID = f.fID
            LEFT JOIN department AS d ON t.dID = d.dID
            LEFT JOIN level AS l ON t.lID = l.lID
            ' . $position['join'] . '
            LEFT JOIN dh_roles AS r ON t.roleID = r.roleID
            WHERE t.pID = ? AND t.status = 1
            LIMIT 1';
        $stmt = mysqli_prepare($connection, $sql);

        if ($stmt === false) {
            error_log('Database Error: ' . mysqli_error($connection));
        } else {
            mysqli_stmt_bind_param($stmt, 's', $teacher_pid);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($row = mysqli_fetch_assoc($result)) {
                $teacher = $row;
            }

            mysqli_stmt_close($stmt);
        }
    } catch (mysqli_sql_exception $e) {
        error_log('Database Exception: ' . $e->getMessage());
    }
}
?>

<?php
if (!isset($connection) || !($connection instanceof mysqli)) {
    require_once __DIR__ . '/../../../config/connection.php';
}

if (!isset($connection) || !($connection instanceof mysqli)) {
    $connection = $GLOBALS['connection'] ?? null;
}

$dh_year = '';

if (!($connection instanceof mysqli)) {
    return;
}

try {
    $sql = 'SELECT dh_year FROM thesystem ORDER BY ID DESC LIMIT 1';
    $stmt = mysqli_prepare($connection, $sql);

    if ($stmt === false) {
        error_log('Database Error: ' . mysqli_error($connection));
    } else {
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            $dh_year = (string) $row['dh_year'];
        }

        mysqli_stmt_close($stmt);
    }
} catch (mysqli_sql_exception $e) {
    error_log('Database Exception: ' . $e->getMessage());
}

// Make the value available across include scopes (some views are rendered inside functions).
$GLOBALS['dh_year'] = $dh_year;
?>

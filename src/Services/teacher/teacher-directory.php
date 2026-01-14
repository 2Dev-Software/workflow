<?php
require_once __DIR__ . '/../../../config/connection.php';

$teacher_directory = [];
$teacher_directory_total = 0;
$teacher_directory_total_pages = 0;

$teacher_directory_page = isset($teacher_directory_page) ? (int) $teacher_directory_page : 1;
$teacher_directory_page = max($teacher_directory_page, 1);

$teacher_directory_per_page = $teacher_directory_per_page ?? 10;
$teacher_directory_query = trim((string) ($teacher_directory_query ?? ''));
$teacher_directory_filter_did = isset($teacher_directory_filter_did) ? (int) $teacher_directory_filter_did : null;
$teacher_directory_order_by = $teacher_directory_order_by ?? 'fName';
$teacher_directory_order_by = (string) $teacher_directory_order_by;

$teacher_directory_order_map = [
    'fName' => 't.fName ASC',
    'positionID' => 't.positionID ASC, t.fName ASC',
];
$teacher_directory_order_sql = $teacher_directory_order_map[$teacher_directory_order_by] ?? $teacher_directory_order_map['fName'];

try {
    $has_search = $teacher_directory_query !== '';
    $search_like = '%' . $teacher_directory_query . '%';

    $count_sql = 'SELECT COUNT(*) AS total
        FROM teacher AS t
        LEFT JOIN department AS d ON t.dID = d.dID
        WHERE t.status = 1';
    if ($teacher_directory_filter_did !== null) {
        $count_sql .= ' AND t.dID = ?';
    }
    if ($has_search) {
        $count_sql .= ' AND (t.fName LIKE ? OR t.telephone LIKE ? OR d.dName LIKE ?)';
    }

    $count_stmt = mysqli_prepare($connection, $count_sql);

    if ($count_stmt === false) {
        error_log('Database Error: ' . mysqli_error($connection));
    } else {
        if ($teacher_directory_filter_did !== null && $has_search) {
            mysqli_stmt_bind_param($count_stmt, 'isss', $teacher_directory_filter_did, $search_like, $search_like, $search_like);
        } elseif ($teacher_directory_filter_did !== null) {
            mysqli_stmt_bind_param($count_stmt, 'i', $teacher_directory_filter_did);
        } elseif ($has_search) {
            mysqli_stmt_bind_param($count_stmt, 'sss', $search_like, $search_like, $search_like);
        }

        mysqli_stmt_execute($count_stmt);
        $count_result = mysqli_stmt_get_result($count_stmt);

        if ($row = mysqli_fetch_assoc($count_result)) {
            $teacher_directory_total = (int) $row['total'];
        }

        mysqli_stmt_close($count_stmt);
    }

    if ($teacher_directory_per_page === 'all') {
        $teacher_directory_total_pages = $teacher_directory_total > 0 ? 1 : 0;
    } else {
        $teacher_directory_per_page = max((int) $teacher_directory_per_page, 1);
        $teacher_directory_total_pages = (int) ceil($teacher_directory_total / $teacher_directory_per_page);
    }

    if ($teacher_directory_total_pages > 0 && $teacher_directory_page > $teacher_directory_total_pages) {
        $teacher_directory_page = $teacher_directory_total_pages;
    }

    $sql = 'SELECT t.pID, t.fName, t.telephone, t.positionID, d.dName AS department_name, p.positionName AS position_name
        FROM teacher AS t
        LEFT JOIN department AS d ON t.dID = d.dID
        LEFT JOIN dh_positions AS p ON t.positionID = p.positionID
        WHERE t.status = 1';
    if ($teacher_directory_filter_did !== null) {
        $sql .= ' AND t.dID = ?';
    }
    if ($has_search) {
        $sql .= ' AND (t.fName LIKE ? OR t.telephone LIKE ? OR d.dName LIKE ?)';
    }
    
    $sql .= ' ORDER BY ' . $teacher_directory_order_sql;

    $limit = null;
    $offset = null;

    if ($teacher_directory_per_page !== 'all') {
        $limit = (int) $teacher_directory_per_page;
        $offset = ($teacher_directory_page - 1) * $limit;
        $sql .= ' LIMIT ? OFFSET ?';
    }

    $stmt = mysqli_prepare($connection, $sql);

    if ($stmt === false) {
        error_log('Database Error: ' . mysqli_error($connection));
    } else {
        if ($teacher_directory_filter_did !== null && $has_search && $teacher_directory_per_page !== 'all') {
            mysqli_stmt_bind_param($stmt, 'isssii', $teacher_directory_filter_did, $search_like, $search_like, $search_like, $limit, $offset);
        } elseif ($teacher_directory_filter_did !== null && $has_search) {
            mysqli_stmt_bind_param($stmt, 'isss', $teacher_directory_filter_did, $search_like, $search_like, $search_like);
        } elseif ($teacher_directory_filter_did !== null && $teacher_directory_per_page !== 'all') {
            mysqli_stmt_bind_param($stmt, 'iii', $teacher_directory_filter_did, $limit, $offset);
        } elseif ($teacher_directory_filter_did !== null) {
            mysqli_stmt_bind_param($stmt, 'i', $teacher_directory_filter_did);
        } elseif ($has_search && $teacher_directory_per_page !== 'all') {
            mysqli_stmt_bind_param($stmt, 'sssii', $search_like, $search_like, $search_like, $limit, $offset);
        } elseif ($has_search) {
            mysqli_stmt_bind_param($stmt, 'sss', $search_like, $search_like, $search_like);
        } elseif ($teacher_directory_per_page !== 'all') {
            mysqli_stmt_bind_param($stmt, 'ii', $limit, $offset);
        }

        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result !== false) {
            while ($row = mysqli_fetch_assoc($result)) {
                $teacher_directory[] = $row;
            }
        }

        mysqli_stmt_close($stmt);
    }
} catch (mysqli_sql_exception $e) {
    error_log('Database Exception: ' . $e->getMessage());
}
?>

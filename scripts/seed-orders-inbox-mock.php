<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/db/db.php';
require_once __DIR__ . '/../app/modules/system/system.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "This script is CLI only.\n";
    exit(1);
}

$connection = db_connection();

$required_tables = [
    'teacher',
    'dh_orders',
    'dh_order_recipients',
    'dh_order_inboxes',
    'dh_order_routes',
];

foreach ($required_tables as $table_name) {
    if (!db_table_exists($connection, $table_name)) {
        fwrite(STDERR, 'Missing table: ' . $table_name . "\n");
        exit(1);
    }
}

$dh_year = system_get_dh_year();
$active_teachers = db_fetch_all(
    'SELECT pID, fName FROM teacher WHERE status = 1 ORDER BY pID ASC'
);

if (count($active_teachers) < 2) {
    fwrite(STDERR, "Need at least 2 active teachers to seed mock inbox data.\n");
    exit(1);
}

$sender_rows = array_slice($active_teachers, 0, 2);
$sender_pids = array_values(array_filter(array_map(static function (array $row): string {
    return trim((string) ($row['pID'] ?? ''));
}, $sender_rows)));

if (count($sender_pids) < 2) {
    fwrite(STDERR, "Cannot resolve sender PIDs.\n");
    exit(1);
}

db_begin();

try {
    $seq_row = db_fetch_one(
        'SELECT orderSeq FROM dh_orders WHERE dh_year = ? ORDER BY orderSeq DESC LIMIT 1 FOR UPDATE',
        'i',
        $dh_year
    );
    $current_seq = (int) ($seq_row['orderSeq'] ?? 0);
    $timestamp = date('Y-m-d H:i:s');
    $created_orders = [];

    foreach ($sender_pids as $sender_index => $sender_pid) {
        $current_seq++;
        $order_no = $current_seq . '/' . $dh_year;
        $subject = 'Mock คำสั่งราชการ จากผู้ส่ง #' . ($sender_index + 1) . ' - ' . $timestamp;
        $detail = "Mock data for orders inbox testing\nGenerated at: " . $timestamp;

        $create_stmt = db_query(
            'INSERT INTO dh_orders (dh_year, orderNo, orderSeq, subject, detail, status, createdByPID, updatedByPID)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            'isisssss',
            $dh_year,
            $order_no,
            $current_seq,
            $subject,
            $detail,
            'SENT',
            $sender_pid,
            $sender_pid
        );
        mysqli_stmt_close($create_stmt);

        $order_id = db_last_insert_id();

        $route_create_stmt = db_query(
            'INSERT INTO dh_order_routes (orderID, action, fromPID, toPID, note) VALUES (?, ?, ?, ?, ?)',
            'issss',
            $order_id,
            'CREATE',
            $sender_pid,
            null,
            'Mock seed create'
        );
        mysqli_stmt_close($route_create_stmt);

        $route_send_stmt = db_query(
            'INSERT INTO dh_order_routes (orderID, action, fromPID, toPID, note) VALUES (?, ?, ?, ?, ?)',
            'issss',
            $order_id,
            'SEND',
            $sender_pid,
            null,
            'Mock seed send'
        );
        mysqli_stmt_close($route_send_stmt);

        $recipient_count = 0;

        foreach ($active_teachers as $teacher_row) {
            $receiver_pid = trim((string) ($teacher_row['pID'] ?? ''));

            if ($receiver_pid === '' || $receiver_pid === $sender_pid) {
                continue;
            }

            $recipient_stmt = db_query(
                'INSERT INTO dh_order_recipients (orderID, targetType, fID, roleID, pID, isCc)
                 VALUES (?, ?, ?, ?, ?, ?)',
                'isiisi',
                $order_id,
                'PERSON',
                null,
                null,
                $receiver_pid,
                0
            );
            mysqli_stmt_close($recipient_stmt);

            $inbox_stmt = db_query(
                'INSERT INTO dh_order_inboxes (orderID, pID, deliveredByPID)
                 VALUES (?, ?, ?)',
                'iss',
                $order_id,
                $receiver_pid,
                $sender_pid
            );
            mysqli_stmt_close($inbox_stmt);
            $recipient_count++;
        }

        $created_orders[] = [
            'order_id' => $order_id,
            'order_no' => $order_no,
            'sender_pid' => $sender_pid,
            'recipient_count' => $recipient_count,
        ];
    }

    db_commit();

    echo "Seed complete.\n";
    echo 'dh_year: ' . $dh_year . "\n";
    foreach ($created_orders as $order) {
        echo '- orderID=' . $order['order_id']
            . ' orderNo=' . $order['order_no']
            . ' sender=' . $order['sender_pid']
            . ' recipients=' . $order['recipient_count']
            . "\n";
    }
} catch (Throwable $e) {
    db_rollback();
    fwrite(STDERR, 'Seed failed: ' . $e->getMessage() . "\n");
    exit(1);
}

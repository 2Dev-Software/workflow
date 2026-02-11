<?php
if (PHP_SAPI !== 'cli') {
    echo "CLI only\n";
    exit(1);
}

require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../app/auth/password.php';

$pid = $argv[1] ?? '';
$pid = trim((string) $pid);
if ($pid === '' || !preg_match('/^\d{1,13}$/', $pid)) {
    echo "Usage: php scripts/bootstrap-admin.php <pID>\n";
    exit(1);
}

$select = mysqli_prepare($connection, 'SELECT pID, roleID FROM teacher WHERE pID = ? LIMIT 1');
if ($select === false) {
    echo "DB error\n";
    exit(1);
}
mysqli_stmt_bind_param($select, 's', $pid);
mysqli_stmt_execute($select);
$result = mysqli_stmt_get_result($select);
$row = $result ? mysqli_fetch_assoc($result) : null;
mysqli_stmt_close($select);

if (!$row) {
    echo "ไม่พบผู้ใช้ในระบบ: {$pid}\n";
    exit(1);
}

$update = mysqli_prepare($connection, 'UPDATE teacher SET roleID = 1 WHERE pID = ?');
if ($update === false) {
    echo "DB error\n";
    exit(1);
}
mysqli_stmt_bind_param($update, 's', $pid);
mysqli_stmt_execute($update);
mysqli_stmt_close($update);

echo "ตั้งค่าเป็น Admin เรียบร้อย: {$pid}\n";

<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

if (empty($_POST['room_action'])) {
    return;
}

require_once __DIR__ . '/../../../config/connection.php';
require_once __DIR__ . '/../../../app/modules/audit/logger.php';

$raw_action = (string) ($_POST['room_action'] ?? '');
$audit_action_map = [
    'add' => 'CREATE',
    'edit' => 'UPDATE',
    'delete' => 'DELETE',
];
$audit_action = $audit_action_map[$raw_action] ?? 'MANAGE_ROOM';
$audit_payload_base = [
    'room_action' => $raw_action,
    'room_id' => $_POST['room_id'] ?? null,
    'room_name' => $_POST['room_name'] ?? null,
    'room_status' => $_POST['room_status'] ?? null,
];
$audit_fail = static function (string $reason, ?int $room_id = null, array $payload = []) use ($audit_action, $audit_payload_base): void {
    if (!function_exists('audit_log')) {
        return;
    }
    audit_log('room', $audit_action, 'FAIL', 'dh_rooms', $room_id, $reason, array_merge($audit_payload_base, $payload));
};

$redirect_url = 'room-management.php';

$set_room_alert = static function (string $type, string $title, string $message = '') use ($redirect_url): void {
    $_SESSION['room_management_alert'] = [
        'type' => $type,
        'title' => $title,
        'message' => $message,
        'button_label' => 'ยืนยัน',
        'redirect' => '',
        'delay_ms' => 0,
    ];
};

$connection = $connection ?? ($GLOBALS['connection'] ?? null);
if (!($connection instanceof mysqli)) {
    $audit_fail('db_connection_missing');
    $set_room_alert('danger', 'ระบบขัดข้อง', 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string) $_POST['csrf_token'])) {
    if (function_exists('audit_log')) {
        audit_log('security', 'CSRF_FAIL', 'DENY', 'dh_rooms', null, 'room_management_room_actions', $audit_payload_base);
    }
    $set_room_alert('danger', 'ไม่สามารถยืนยันความปลอดภัย', 'กรุณาลองใหม่อีกครั้ง');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

$allowed_statuses = ['พร้อมใช้งาน', 'ระงับชั่วคราว', 'กำลังซ่อม', 'ไม่พร้อมใช้งาน'];
$action = (string) ($_POST['room_action'] ?? '');

$normalize_text = static function (string $value, int $limit = 0): string {
    $value = trim($value);
    if ($limit <= 0) {
        return $value;
    }
    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, $limit);
    }
    return substr($value, 0, $limit);
};

try {
    if ($action === 'add' || $action === 'edit') {
        $room_name = $normalize_text((string) ($_POST['room_name'] ?? ''), 150);
        $room_status = $normalize_text((string) ($_POST['room_status'] ?? ''), 50);
        $room_note = $normalize_text((string) ($_POST['room_note'] ?? ''), 2000);

        if ($room_name === '') {
            $audit_fail('missing_room_name');
            $set_room_alert('danger', 'ข้อมูลไม่ครบถ้วน', 'กรุณาระบุชื่อห้องหรือสถานที่');
            header('Location: ' . $redirect_url, true, 303);
            exit();
        }

        if (!in_array($room_status, $allowed_statuses, true)) {
            $audit_fail('invalid_room_status');
            $set_room_alert('danger', 'ข้อมูลไม่ถูกต้อง', 'กรุณาเลือกสถานะห้องให้ถูกต้อง');
            header('Location: ' . $redirect_url, true, 303);
            exit();
        }

        if ($action === 'add') {
            $check_sql = 'SELECT roomID FROM dh_rooms WHERE roomName = ? AND deletedAt IS NULL LIMIT 1';
            $check_stmt = mysqli_prepare($connection, $check_sql);
            if ($check_stmt) {
                mysqli_stmt_bind_param($check_stmt, 's', $room_name);
                mysqli_stmt_execute($check_stmt);
                $check_result = mysqli_stmt_get_result($check_stmt);
                $exists = $check_result && mysqli_fetch_assoc($check_result);
                mysqli_stmt_close($check_stmt);
                if ($exists) {
                    $audit_fail('duplicate_room_name', null, [
                        'roomName' => $room_name,
                    ]);
                    $set_room_alert('warning', 'ชื่อซ้ำ', 'มีชื่อห้องนี้อยู่แล้วในระบบ');
                    header('Location: ' . $redirect_url, true, 303);
                    exit();
                }
            }

            $sql = 'INSERT INTO dh_rooms (roomName, roomStatus, roomNote, createdAt, updatedAt)
                VALUES (?, ?, ?, NOW(), NOW())';
            $stmt = mysqli_prepare($connection, $sql);
            if ($stmt === false) {
                error_log('Database Error: ' . mysqli_error($connection));
                $audit_fail('insert_prepare_failed', null, [
                    'error' => mysqli_error($connection),
                ]);
                $set_room_alert('danger', 'ระบบขัดข้อง', 'ไม่สามารถเพิ่มห้องได้ในขณะนี้');
            } else {
                mysqli_stmt_bind_param($stmt, 'sss', $room_name, $room_status, $room_note);
                mysqli_stmt_execute($stmt);
                $affected = mysqli_stmt_affected_rows($stmt);
                mysqli_stmt_close($stmt);
                if ($affected > 0) {
                    if (function_exists('audit_log')) {
                        $room_id = (int) mysqli_insert_id($connection);
                        audit_log('room', 'CREATE', 'SUCCESS', 'dh_rooms', $room_id, null, [
                            'roomName' => $room_name,
                        ]);
                    }
                    $set_room_alert('success', 'เพิ่มห้องสำเร็จ', 'บันทึกห้องใหม่เรียบร้อยแล้ว');
                } else {
                    $audit_fail('no_rows_affected');
                    $set_room_alert('warning', 'ไม่สามารถเพิ่มห้อง', 'กรุณาลองใหม่อีกครั้ง');
                }
            }
        } else {
            $room_id = filter_input(INPUT_POST, 'room_id', FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1],
            ]);
            if ($room_id === null || $room_id === false) {
                $audit_fail('invalid_room_id');
                $set_room_alert('danger', 'ข้อมูลไม่ถูกต้อง', 'ไม่พบรหัสห้องที่ต้องการแก้ไข');
                header('Location: ' . $redirect_url, true, 303);
                exit();
            }

            $check_sql = 'SELECT roomID FROM dh_rooms WHERE roomName = ? AND roomID <> ? AND deletedAt IS NULL LIMIT 1';
            $check_stmt = mysqli_prepare($connection, $check_sql);
            if ($check_stmt) {
                mysqli_stmt_bind_param($check_stmt, 'si', $room_name, $room_id);
                mysqli_stmt_execute($check_stmt);
                $check_result = mysqli_stmt_get_result($check_stmt);
                $exists = $check_result && mysqli_fetch_assoc($check_result);
                mysqli_stmt_close($check_stmt);
                if ($exists) {
                    $audit_fail('duplicate_room_name', $room_id, [
                        'roomName' => $room_name,
                    ]);
                    $set_room_alert('warning', 'ชื่อซ้ำ', 'มีชื่อห้องนี้อยู่แล้วในระบบ');
                    header('Location: ' . $redirect_url, true, 303);
                    exit();
                }
            }

            $sql = 'UPDATE dh_rooms
                SET roomName = ?, roomStatus = ?, roomNote = ?, updatedAt = NOW()
                WHERE roomID = ? AND deletedAt IS NULL';
            $stmt = mysqli_prepare($connection, $sql);
            if ($stmt === false) {
                error_log('Database Error: ' . mysqli_error($connection));
                $audit_fail('update_prepare_failed', $room_id, [
                    'error' => mysqli_error($connection),
                ]);
                $set_room_alert('danger', 'ระบบขัดข้อง', 'ไม่สามารถแก้ไขห้องได้ในขณะนี้');
            } else {
                mysqli_stmt_bind_param($stmt, 'sssi', $room_name, $room_status, $room_note, $room_id);
                mysqli_stmt_execute($stmt);
                $affected = mysqli_stmt_affected_rows($stmt);
                mysqli_stmt_close($stmt);
                if ($affected > 0) {
                    if (function_exists('audit_log')) {
                        audit_log('room', 'UPDATE', 'SUCCESS', 'dh_rooms', $room_id, null, [
                            'roomName' => $room_name,
                        ]);
                    }
                    $set_room_alert('success', 'บันทึกสำเร็จ', 'อัปเดตข้อมูลห้องเรียบร้อยแล้ว');
                } else {
                    $audit_fail('no_rows_affected', $room_id);
                    $set_room_alert('warning', 'ไม่มีการเปลี่ยนแปลง', 'ข้อมูลห้องยังคงเดิมหรือถูกลบไปแล้ว');
                }
            }
        }
    } elseif ($action === 'delete') {
        $room_id = filter_input(INPUT_POST, 'room_id', FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        if ($room_id === null || $room_id === false) {
            $audit_fail('invalid_room_id');
            $set_room_alert('danger', 'ข้อมูลไม่ถูกต้อง', 'ไม่พบรหัสห้องที่ต้องการลบ');
            header('Location: ' . $redirect_url, true, 303);
            exit();
        }

        $check_sql = 'SELECT COUNT(*) AS total FROM dh_room_bookings WHERE roomID = ? AND deletedAt IS NULL';
        $check_stmt = mysqli_prepare($connection, $check_sql);
        if ($check_stmt) {
            mysqli_stmt_bind_param($check_stmt, 'i', $room_id);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);
            $check_row = $check_result ? mysqli_fetch_assoc($check_result) : null;
            mysqli_stmt_close($check_stmt);
            if ($check_row && (int) ($check_row['total'] ?? 0) > 0) {
                $audit_fail('delete_has_bookings', $room_id, [
                    'bookingTotal' => (int) ($check_row['total'] ?? 0),
                ]);
                $set_room_alert('warning', 'ไม่สามารถลบได้', 'ห้องนี้มีรายการจองอยู่ในระบบ');
                header('Location: ' . $redirect_url, true, 303);
                exit();
            }
        }

        $sql = 'UPDATE dh_rooms
            SET deletedAt = NOW(), updatedAt = NOW()
            WHERE roomID = ? AND deletedAt IS NULL';
        $stmt = mysqli_prepare($connection, $sql);
        if ($stmt === false) {
            error_log('Database Error: ' . mysqli_error($connection));
            $audit_fail('delete_prepare_failed', $room_id, [
                'error' => mysqli_error($connection),
            ]);
            $set_room_alert('danger', 'ระบบขัดข้อง', 'ไม่สามารถลบห้องได้ในขณะนี้');
        } else {
            mysqli_stmt_bind_param($stmt, 'i', $room_id);
            mysqli_stmt_execute($stmt);
            $affected = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            if ($affected > 0) {
                if (function_exists('audit_log')) {
                    audit_log('room', 'DELETE', 'SUCCESS', 'dh_rooms', $room_id);
                }
                $set_room_alert('success', 'ลบสำเร็จ', 'ลบห้องออกจากระบบแล้ว');
            } else {
                $audit_fail('no_rows_affected', $room_id);
                $set_room_alert('warning', 'ไม่สามารถลบได้', 'ไม่พบข้อมูลห้องที่ต้องการหรือถูกลบไปแล้ว');
            }
        }
    } else {
        $audit_fail('invalid_action');
        $set_room_alert('danger', 'ข้อมูลไม่ถูกต้อง', 'ไม่พบคำสั่งที่ต้องการ');
    }
} catch (mysqli_sql_exception $exception) {
    error_log('Database Exception: ' . $exception->getMessage());
    $audit_fail('db_exception', null, [
        'error' => $exception->getMessage(),
    ]);
    $set_room_alert('danger', 'ระบบขัดข้อง', 'ไม่สามารถดำเนินการได้ในขณะนี้');
}

header('Location: ' . $redirect_url, true, 303);
exit();

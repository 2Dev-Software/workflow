<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

if (empty($_POST['member_action'])) {
    return;
}

require_once __DIR__ . '/../../../config/connection.php';

$is_ajax = false;
if (!empty($_POST['ajax'])) {
    $is_ajax = true;
} elseif (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    $is_ajax = true;
} elseif (!empty($_SERVER['HTTP_ACCEPT']) && strpos((string) $_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
    $is_ajax = true;
}

$redirect_url = 'room-management.php';

$set_room_management_alert = static function (string $type, string $title, string $message = '') use ($redirect_url): void {
    $_SESSION['room_management_alert'] = [
        'type' => $type,
        'title' => $title,
        'message' => $message,
        'button_label' => 'ยืนยัน',
        'redirect' => '',
        'delay_ms' => 0,
    ];
};

$send_json = static function (bool $ok, string $type, string $title, string $message = ''): void {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok' => $ok,
        'type' => $type,
        'title' => $title,
        'message' => $message,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
};

$connection = $connection ?? ($GLOBALS['connection'] ?? null);
if (!($connection instanceof mysqli)) {
    if ($is_ajax) {
        $send_json(false, 'danger', 'ระบบขัดข้อง', 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้');
    }
    $set_room_management_alert('danger', 'ระบบขัดข้อง', 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string) $_POST['csrf_token'])) {
    if ($is_ajax) {
        $send_json(false, 'danger', 'ไม่สามารถยืนยันความปลอดภัย', 'กรุณาลองใหม่อีกครั้ง');
    }
    $set_room_management_alert('danger', 'ไม่สามารถยืนยันความปลอดภัย', 'กรุณาลองใหม่อีกครั้ง');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

$member_pid = trim((string) ($_POST['member_pid'] ?? ''));
if ($member_pid === '' || !preg_match('/^\d{13}$/', $member_pid)) {
    if ($is_ajax) {
        $send_json(false, 'danger', 'ข้อมูลไม่ถูกต้อง', 'ไม่พบรหัสบุคลากรที่ต้องการ');
    }
    $set_room_management_alert('danger', 'ข้อมูลไม่ถูกต้อง', 'ไม่พบรหัสบุคลากรที่ต้องการ');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

$action = (string) ($_POST['member_action'] ?? '');
$staff_role_id = 5;
$admin_role_id = 1;

try {
    if ($action === 'add') {
        if (!$is_ajax) {
            $_SESSION['room_management_open_modal'] = 'roomMemberModal';
        }
        $sql = 'UPDATE teacher SET roleID = ?
            WHERE pID = ? AND status = 1 AND roleID NOT IN (?, ?)';
        $stmt = mysqli_prepare($connection, $sql);

        if ($stmt === false) {
            error_log('Database Error: ' . mysqli_error($connection));
            if ($is_ajax) {
                $send_json(false, 'danger', 'ระบบขัดข้อง', 'ไม่สามารถเพิ่มสมาชิกได้ในขณะนี้');
            }
            $set_room_management_alert('danger', 'ระบบขัดข้อง', 'ไม่สามารถเพิ่มสมาชิกได้ในขณะนี้');
        } else {
            mysqli_stmt_bind_param($stmt, 'isii', $staff_role_id, $member_pid, $admin_role_id, $staff_role_id);
            mysqli_stmt_execute($stmt);
            $affected = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);

            if ($affected > 0) {
                if ($is_ajax) {
                    $send_json(true, 'success', 'เพิ่มสมาชิกสำเร็จ', 'อัปเดตสิทธิ์เป็นเจ้าหน้าที่สถานที่แล้ว');
                }
                $set_room_management_alert('success', 'เพิ่มสมาชิกสำเร็จ', 'อัปเดตสิทธิ์เป็นเจ้าหน้าที่สถานที่แล้ว');
            } else {
                if ($is_ajax) {
                    $send_json(false, 'warning', 'ไม่สามารถเพิ่มสมาชิก', 'บุคลากรนี้อาจถูกเพิ่มแล้วหรือไม่อยู่ในระบบ');
                }
                $set_room_management_alert('warning', 'ไม่สามารถเพิ่มสมาชิก', 'บุคลากรนี้อาจถูกเพิ่มแล้วหรือไม่อยู่ในระบบ');
            }
        }
    } elseif ($action === 'remove') {
        $default_role_id = 6;
        $sql = 'UPDATE teacher SET roleID = ?
            WHERE pID = ? AND status = 1 AND roleID = ?';
        $stmt = mysqli_prepare($connection, $sql);

        if ($stmt === false) {
            error_log('Database Error: ' . mysqli_error($connection));
            if ($is_ajax) {
                $send_json(false, 'danger', 'ระบบขัดข้อง', 'ไม่สามารถลบสมาชิกได้ในขณะนี้');
            }
            $set_room_management_alert('danger', 'ระบบขัดข้อง', 'ไม่สามารถลบสมาชิกได้ในขณะนี้');
        } else {
            mysqli_stmt_bind_param($stmt, 'isi', $default_role_id, $member_pid, $staff_role_id);
            mysqli_stmt_execute($stmt);
            $affected = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);

            if ($affected > 0) {
                if ($is_ajax) {
                    $send_json(true, 'success', 'ลบสมาชิกสำเร็จ', 'สิทธิ์ถูกปรับกลับเป็นผู้ใช้งานทั่วไปแล้ว');
                }
                $set_room_management_alert('success', 'ลบสมาชิกสำเร็จ', 'สิทธิ์ถูกปรับกลับเป็นผู้ใช้งานทั่วไปแล้ว');
            } else {
                if ($is_ajax) {
                    $send_json(false, 'warning', 'ไม่สามารถลบสมาชิก', 'ไม่พบสมาชิกในบทบาทเจ้าหน้าที่สถานที่');
                }
                $set_room_management_alert('warning', 'ไม่สามารถลบสมาชิก', 'ไม่พบสมาชิกในบทบาทเจ้าหน้าที่สถานที่');
            }
        }
    } else {
        if ($is_ajax) {
            $send_json(false, 'danger', 'ข้อมูลไม่ถูกต้อง', 'ไม่พบคำสั่งที่ต้องการ');
        }
        $set_room_management_alert('danger', 'ข้อมูลไม่ถูกต้อง', 'ไม่พบคำสั่งที่ต้องการ');
    }
} catch (mysqli_sql_exception $exception) {
    error_log('Database Exception: ' . $exception->getMessage());
    if ($is_ajax) {
        $send_json(false, 'danger', 'ระบบขัดข้อง', 'ไม่สามารถดำเนินการได้ในขณะนี้');
    }
    $set_room_management_alert('danger', 'ระบบขัดข้อง', 'ไม่สามารถดำเนินการได้ในขณะนี้');
}

header('Location: ' . $redirect_url, true, 303);
exit();

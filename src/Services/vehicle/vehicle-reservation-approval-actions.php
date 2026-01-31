<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !isset($_POST['approval_action'])) {
    return;
}

require_once __DIR__ . '/../../../config/connection.php';
require_once __DIR__ . '/../teacher/teacher-profile.php';
require_once __DIR__ . '/../system/exec-duty-current.php';
require_once __DIR__ . '/../../../app/modules/audit/logger.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$redirect_url = 'vehicle-reservation-approval.php';
$return_url = trim((string) ($_POST['return_url'] ?? ''));
if ($return_url !== '' && strpos($return_url, $redirect_url) === 0) {
    $redirect_url = $return_url;
}

$set_vehicle_approval_alert = static function (string $type, string $title, string $message = '') use ($redirect_url): void {
    $_SESSION['vehicle_approval_alert'] = [
        'type' => $type,
        'title' => $title,
        'message' => $message,
        'button_label' => 'ยืนยัน',
        'redirect' => '',
        'delay_ms' => 0,
    ];
    header('Location: ' . $redirect_url, true, 303);
    exit();
};

if (
    empty($_POST['csrf_token']) ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals((string) $_SESSION['csrf_token'], (string) $_POST['csrf_token'])
) {
    $set_vehicle_approval_alert('danger', 'ไม่สามารถยืนยันความปลอดภัย', 'กรุณาลองใหม่อีกครั้ง');
}

$action = trim((string) ($_POST['approval_action'] ?? ''));
if (!in_array($action, ['approve', 'reject'], true)) {
    $set_vehicle_approval_alert('danger', 'คำสั่งไม่ถูกต้อง', 'กรุณาลองใหม่อีกครั้ง');
}

$booking_id = (int) ($_POST['vehicle_booking_id'] ?? 0);
if ($booking_id <= 0) {
    $set_vehicle_approval_alert('danger', 'ข้อมูลไม่ถูกต้อง', 'ไม่พบรายการจองที่ต้องการ');
}

$actor_pid = trim((string) ($_SESSION['pID'] ?? ''));
if ($actor_pid === '') {
    header('Location: index.php', true, 302);
    exit();
}

$role_id = (int) ($teacher['roleID'] ?? 0);
$position_id = (int) ($teacher['positionID'] ?? 0);
$acting_pid = '';
if (($exec_duty_current_status ?? 0) === 2 && !empty($exec_duty_current_pid)) {
    $acting_pid = (string) $exec_duty_current_pid;
}

$is_director = $position_id === 1 || ($acting_pid !== '' && $acting_pid === $actor_pid);
$is_vehicle_officer = in_array($role_id, [1, 3], true);

$reason = trim((string) ($_POST['statusReason'] ?? ''));
if ($action === 'reject' && $reason === '') {
    $set_vehicle_approval_alert('warning', 'กรุณาระบุเหตุผล', 'โปรดระบุเหตุผลเมื่อไม่อนุมัติ');
}

$current_status = 'PENDING';

try {
    $check_sql = 'SELECT bookingID, status FROM dh_vehicle_bookings WHERE bookingID = ? AND deletedAt IS NULL LIMIT 1';
    $check_stmt = mysqli_prepare($connection, $check_sql);

    mysqli_stmt_bind_param($check_stmt, 'i', $booking_id);
    mysqli_stmt_execute($check_stmt);

    $check_result = mysqli_stmt_get_result($check_stmt);
    $check_row = $check_result ? mysqli_fetch_assoc($check_result) : null;
    mysqli_stmt_close($check_stmt);

    if (!$check_row) {
        $set_vehicle_approval_alert('warning', 'ไม่พบรายการ', 'รายการจองนี้อาจถูกลบไปแล้ว');
    }

    $current_status = strtoupper(trim((string) ($check_row['status'] ?? 'PENDING')));
} catch (mysqli_sql_exception $exception) {
    error_log('Database Exception: ' . $exception->getMessage());
    $set_vehicle_approval_alert('danger', 'ระบบขัดข้อง', 'ไม่สามารถตรวจสอบรายการจองได้ในขณะนี้');
}

$approved_at = date('Y-m-d H:i:s');

if ($action === 'approve') {
    if ($current_status === 'PENDING') {
        if (!$is_vehicle_officer) {
            $set_vehicle_approval_alert('danger', 'ไม่มีสิทธิ์ดำเนินการ', 'เฉพาะเจ้าหน้าที่งานยานพาหนะเท่านั้น');
        }

        $assign_vehicle_id = (int) ($_POST['assign_vehicle_id'] ?? 0);
        if ($assign_vehicle_id <= 0) {
            $set_vehicle_approval_alert('warning', 'กรุณาเลือกยานพาหนะ', 'โปรดเลือกยานพาหนะก่อนส่งต่อผู้บริหาร');
        }

        $vehicle_exists = false;
        try {
            $vehicle_stmt = mysqli_prepare($connection, 'SELECT vehicleID FROM dh_vehicles WHERE vehicleID = ? LIMIT 1');
            mysqli_stmt_bind_param($vehicle_stmt, 'i', $assign_vehicle_id);
            mysqli_stmt_execute($vehicle_stmt);
            $vehicle_result = mysqli_stmt_get_result($vehicle_stmt);
            $vehicle_exists = $vehicle_result && mysqli_fetch_assoc($vehicle_result);
            mysqli_stmt_close($vehicle_stmt);
        } catch (mysqli_sql_exception $exception) {
            error_log('Database Exception: ' . $exception->getMessage());
        }

        if (!$vehicle_exists) {
            $set_vehicle_approval_alert('warning', 'ไม่พบยานพาหนะ', 'กรุณาเลือกยานพาหนะที่ถูกต้อง');
        }

        $assign_driver_pid = trim((string) ($_POST['assign_driver_pid'] ?? ''));
        if ($assign_driver_pid !== '' && !ctype_digit($assign_driver_pid)) {
            $assign_driver_pid = preg_replace('/\D+/', '', $assign_driver_pid);
        }
        $assign_driver_name = trim((string) ($_POST['assign_driver_name'] ?? ''));
        $assign_driver_tel = trim((string) ($_POST['assign_driver_tel'] ?? ''));

        if ($assign_driver_pid !== '') {
            try {
                $driver_stmt = mysqli_prepare($connection, 'SELECT pID, fName, telephone FROM teacher WHERE pID = ? AND status = 1 LIMIT 1');
                mysqli_stmt_bind_param($driver_stmt, 's', $assign_driver_pid);
                mysqli_stmt_execute($driver_stmt);
                $driver_result = mysqli_stmt_get_result($driver_stmt);
                $driver_row = $driver_result ? mysqli_fetch_assoc($driver_result) : null;
                mysqli_stmt_close($driver_stmt);

                if ($driver_row) {
                    if ($assign_driver_name === '') {
                        $assign_driver_name = trim((string) ($driver_row['fName'] ?? ''));
                    }
                    if ($assign_driver_tel === '') {
                        $assign_driver_tel = trim((string) ($driver_row['telephone'] ?? ''));
                    }
                }
            } catch (mysqli_sql_exception $exception) {
                error_log('Database Exception: ' . $exception->getMessage());
            }
        }

        if ($assign_driver_name === '') {
            $set_vehicle_approval_alert('warning', 'กรุณาระบุผู้ขับรถ', 'โปรดเลือกหรือกรอกชื่อผู้ขับรถ');
        }

        $driver_pid_param = $assign_driver_pid !== '' ? $assign_driver_pid : null;
        $driver_tel_param = $assign_driver_tel !== '' ? $assign_driver_tel : null;

        $update_sql = 'UPDATE dh_vehicle_bookings
            SET status = ?, statusReason = ?, vehicleID = ?, driverPID = ?, driverName = ?, driverTel = ?, approvedByPID = ?, approvedAt = ?, updatedAt = CURRENT_TIMESTAMP
            WHERE bookingID = ? AND deletedAt IS NULL';

        try {
            $update_stmt = mysqli_prepare($connection, $update_sql);
            $status_value = 'ASSIGNED';
            $status_reason = null;
            $bind_values = [
                $status_value,
                $status_reason,
                $assign_vehicle_id,
                $driver_pid_param,
                $assign_driver_name,
                $driver_tel_param,
                $actor_pid,
                $approved_at,
                $booking_id,
            ];
            $types = 'ssisssssi';

            $bind_params = [];
            $bind_params[] = $update_stmt;
            $bind_params[] = $types;
            foreach ($bind_values as $i => $v) {
                $bind_params[] = &$bind_values[$i];
            }
            call_user_func_array('mysqli_stmt_bind_param', $bind_params);
            mysqli_stmt_execute($update_stmt);
            mysqli_stmt_close($update_stmt);

            audit_log('vehicle', 'ASSIGN', 'SUCCESS', 'dh_vehicle_bookings', $booking_id, null, [
                'vehicleID' => $assign_vehicle_id,
                'driverPID' => $driver_pid_param,
                'driverName' => $assign_driver_name,
            ]);

            $set_vehicle_approval_alert('success', 'ส่งต่อผู้บริหารแล้ว', 'มอบหมายรถและคนขับเรียบร้อยแล้ว');
        } catch (mysqli_sql_exception $exception) {
            error_log('Database Exception: ' . $exception->getMessage());
            audit_log('vehicle', 'ASSIGN', 'FAIL', 'dh_vehicle_bookings', $booking_id, $exception->getMessage());
            $set_vehicle_approval_alert('danger', 'ระบบขัดข้อง', 'ไม่สามารถบันทึกการมอบหมายได้ในขณะนี้');
        }
    }

    if ($current_status === 'ASSIGNED') {
        if (!$is_director) {
            $set_vehicle_approval_alert('danger', 'ไม่มีสิทธิ์ดำเนินการ', 'เฉพาะผู้บริหารเท่านั้น');
        }

        $update_sql = 'UPDATE dh_vehicle_bookings
            SET status = ?, statusReason = ?, approvedByPID = ?, approvedAt = ?, updatedAt = CURRENT_TIMESTAMP
            WHERE bookingID = ? AND deletedAt IS NULL';

        try {
            $update_stmt = mysqli_prepare($connection, $update_sql);
            $status_value = 'APPROVED';
            $status_reason = null;
            $bind_values = [
                $status_value,
                $status_reason,
                $actor_pid,
                $approved_at,
                $booking_id,
            ];
            $types = 'ssssi';

            $bind_params = [];
            $bind_params[] = $update_stmt;
            $bind_params[] = $types;
            foreach ($bind_values as $i => $v) {
                $bind_params[] = &$bind_values[$i];
            }
            call_user_func_array('mysqli_stmt_bind_param', $bind_params);
            mysqli_stmt_execute($update_stmt);
            mysqli_stmt_close($update_stmt);

            audit_log('vehicle', 'FINAL_APPROVE', 'SUCCESS', 'dh_vehicle_bookings', $booking_id);

            $set_vehicle_approval_alert('success', 'อนุมัติสำเร็จ', 'บันทึกผลการอนุมัติเรียบร้อยแล้ว');
        } catch (mysqli_sql_exception $exception) {
            error_log('Database Exception: ' . $exception->getMessage());
            audit_log('vehicle', 'FINAL_APPROVE', 'FAIL', 'dh_vehicle_bookings', $booking_id, $exception->getMessage());
            $set_vehicle_approval_alert('danger', 'ระบบขัดข้อง', 'ไม่สามารถบันทึกผลการพิจารณาได้ในขณะนี้');
        }
    }

    $set_vehicle_approval_alert('warning', 'ไม่สามารถดำเนินการได้', 'สถานะรายการไม่รองรับการอนุมัติ');
}

if ($action === 'reject') {
    $can_reject = false;
    if ($current_status === 'PENDING' && $is_vehicle_officer) {
        $can_reject = true;
    }
    if ($current_status === 'ASSIGNED' && $is_director) {
        $can_reject = true;
    }

    if (!$can_reject) {
        $set_vehicle_approval_alert('danger', 'ไม่มีสิทธิ์ดำเนินการ', 'ไม่สามารถปฏิเสธรายการนี้ได้');
    }

    $update_sql = 'UPDATE dh_vehicle_bookings
        SET status = ?, statusReason = ?, approvedByPID = ?, approvedAt = ?, updatedAt = CURRENT_TIMESTAMP
        WHERE bookingID = ? AND deletedAt IS NULL';

    try {
        $update_stmt = mysqli_prepare($connection, $update_sql);
        $status_value = 'REJECTED';
        $status_reason = $reason;
        $bind_values = [
            $status_value,
            $status_reason,
            $actor_pid,
            $approved_at,
            $booking_id,
        ];
        $types = 'ssssi';

        $bind_params = [];
        $bind_params[] = $update_stmt;
        $bind_params[] = $types;
        foreach ($bind_values as $i => $v) {
            $bind_params[] = &$bind_values[$i];
        }
        call_user_func_array('mysqli_stmt_bind_param', $bind_params);
        mysqli_stmt_execute($update_stmt);
        mysqli_stmt_close($update_stmt);

        audit_log('vehicle', 'REJECT', 'SUCCESS', 'dh_vehicle_bookings', $booking_id, null, [
            'status' => $current_status,
        ]);

        $set_vehicle_approval_alert('success', 'บันทึกสำเร็จ', 'บันทึกการไม่อนุมัติเรียบร้อยแล้ว');
    } catch (mysqli_sql_exception $exception) {
        error_log('Database Exception: ' . $exception->getMessage());
        audit_log('vehicle', 'REJECT', 'FAIL', 'dh_vehicle_bookings', $booking_id, $exception->getMessage());
        $set_vehicle_approval_alert('danger', 'ระบบขัดข้อง', 'ไม่สามารถบันทึกผลการพิจารณาได้ในขณะนี้');
    }
}

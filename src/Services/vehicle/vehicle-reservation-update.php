<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['vehicle_reservation_update'])) {
    return;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../config/connection.php';
require_once __DIR__ . '/vehicle-reservation-utils.php';
require_once __DIR__ . '/vehicle-reservation-data.php';

$redirect_url = 'vehicle-reservation.php';

$set_vehicle_alert = static function (
    string $type,
    string $title,
    string $message = '',
    string $button_label = 'ยืนยัน'
) use ($redirect_url): void {
    $_SESSION['vehicle_reservation_alert'] = [
        'type' => $type,
        'title' => $title,
        'message' => $message,
        'button_label' => $button_label,
        'redirect' => '',
        'delay_ms' => 0,
    ];
};

if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $set_vehicle_alert('danger', 'ไม่สามารถยืนยันความปลอดภัย', 'กรุณาลองใหม่อีกครั้ง');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

$requester_pid = (string) ($_SESSION['pID'] ?? '');
if ($requester_pid === '') {
    header('Location: index.php', true, 302);
    exit();
}

$booking_id = filter_input(INPUT_POST, 'vehicle_booking_id', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);
if (!$booking_id) {
    $set_vehicle_alert('danger', 'ข้อมูลไม่ถูกต้อง', 'ไม่พบรายการที่ต้องการแก้ไข');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

$select_sql = 'SELECT bookingID, requesterPID, status FROM dh_vehicle_bookings WHERE bookingID = ? AND deletedAt IS NULL LIMIT 1';
$select_stmt = mysqli_prepare($connection, $select_sql);
if ($select_stmt === false) {
    error_log('Database Error: ' . mysqli_error($connection));
    $set_vehicle_alert('danger', 'ระบบขัดข้อง', 'ไม่สามารถตรวจสอบรายการได้ในขณะนี้');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

mysqli_stmt_bind_param($select_stmt, 'i', $booking_id);
mysqli_stmt_execute($select_stmt);
$select_result = mysqli_stmt_get_result($select_stmt);
$booking_row = $select_result ? mysqli_fetch_assoc($select_result) : null;
mysqli_stmt_close($select_stmt);

if (!$booking_row) {
    $set_vehicle_alert('danger', 'ไม่พบรายการ', 'รายการนี้อาจถูกลบไปแล้ว');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

if ((string) ($booking_row['requesterPID'] ?? '') !== $requester_pid) {
    $set_vehicle_alert('danger', 'ไม่มีสิทธิ์แก้ไขรายการนี้', 'กรุณาลองใหม่อีกครั้ง');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

$current_status = strtoupper(trim((string) ($booking_row['status'] ?? 'PENDING')));
if ($current_status !== 'PENDING') {
    $set_vehicle_alert('warning', 'ไม่สามารถแก้ไขได้', 'แก้ไขได้เฉพาะรายการที่รออนุมัติ');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

$vehicle_year = (int) ($_POST['dh_year'] ?? 0);
if ($vehicle_year <= 0) {
    $vehicle_year = (int) date('Y') + 543;
}

$department = trim((string) ($_POST['department'] ?? ''));
if ($department === '') {
    $set_vehicle_alert('danger', 'ข้อมูลไม่ถูกต้อง', 'กรุณาเลือกส่วนราชการ');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

$department_pool = [];
foreach (vehicle_reservation_get_departments($connection) as $dept) {
    if (!empty($dept['name'])) {
        $department_pool[$dept['name']] = true;
    }
}
foreach (vehicle_reservation_get_factions($connection) as $faction) {
    if (!empty($faction['name'])) {
        $department_pool[$faction['name']] = true;
    }
}
if (!isset($department_pool[$department])) {
    $set_vehicle_alert('danger', 'ข้อมูลไม่ถูกต้อง', 'กรุณาเลือกส่วนราชการจากรายการที่กำหนด');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

$write_date_raw = trim((string) ($_POST['writeDate'] ?? ''));
$write_date = '';
if ($write_date_raw !== '') {
    $write_date_obj = DateTime::createFromFormat('Y-m-d', $write_date_raw);
    if ($write_date_obj === false) {
        $set_vehicle_alert('danger', 'วันที่ไม่ถูกต้อง', 'กรุณาเลือกวันที่เขียนให้ถูกต้อง');
        header('Location: ' . $redirect_url, true, 303);
        exit();
    }
    $write_date = $write_date_obj->format('Y-m-d');
}

$purpose = trim((string) ($_POST['purpose'] ?? ''));
if ($purpose === '') {
    $set_vehicle_alert('danger', 'ข้อมูลไม่ครบถ้วน', 'กรุณาระบุวัตถุประสงค์การใช้รถ');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

$location = trim((string) ($_POST['location'] ?? ''));
if ($location === '') {
    $set_vehicle_alert('danger', 'ข้อมูลไม่ครบถ้วน', 'กรุณาระบุสถานที่ปลายทาง');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

$start_date_raw = trim((string) ($_POST['startDate'] ?? ''));
$end_date_raw = trim((string) ($_POST['endDate'] ?? ''));

$start_date_obj = DateTime::createFromFormat('Y-m-d', $start_date_raw);
if ($start_date_obj === false) {
    $set_vehicle_alert('danger', 'วันที่ไม่ถูกต้อง', 'กรุณาเลือกวันที่เริ่มเดินทาง');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

$end_date_obj = $end_date_raw !== ''
    ? DateTime::createFromFormat('Y-m-d', $end_date_raw)
    : clone $start_date_obj;

if ($end_date_obj === false) {
    $set_vehicle_alert('danger', 'วันที่ไม่ถูกต้อง', 'กรุณาเลือกวันที่สิ้นสุดให้ถูกต้อง');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

if ($end_date_obj < $start_date_obj) {
    $set_vehicle_alert('danger', 'วันที่ไม่ถูกต้อง', 'วันที่สิ้นสุดต้องไม่น้อยกว่าวันที่เริ่มเดินทาง');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

$start_time_raw = trim((string) ($_POST['startTime'] ?? ''));
$end_time_raw = trim((string) ($_POST['endTime'] ?? ''));

$start_time_obj = DateTime::createFromFormat('H:i', $start_time_raw);
$end_time_obj = DateTime::createFromFormat('H:i', $end_time_raw);

if ($start_time_obj === false || $end_time_obj === false) {
    $set_vehicle_alert('danger', 'เวลาไม่ถูกต้อง', 'กรุณาเลือกช่วงเวลาให้ครบถ้วน');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

$start_at_obj = DateTime::createFromFormat('Y-m-d H:i', $start_date_obj->format('Y-m-d') . ' ' . $start_time_raw);
$end_at_obj = DateTime::createFromFormat('Y-m-d H:i', $end_date_obj->format('Y-m-d') . ' ' . $end_time_raw);

if ($start_at_obj === false || $end_at_obj === false) {
    $set_vehicle_alert('danger', 'เวลาไม่ถูกต้อง', 'กรุณาเลือกช่วงเวลาให้ถูกต้อง');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

if ($end_at_obj <= $start_at_obj) {
    $set_vehicle_alert('danger', 'เวลาไม่ถูกต้อง', 'เวลาเดินทางสิ้นสุดต้องมากกว่าเวลาเริ่มต้น');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

$start_at = $start_at_obj->format('Y-m-d H:i:s');
$end_at = $end_at_obj->format('Y-m-d H:i:s');

$fuel_source = trim((string) ($_POST['fuelSource'] ?? ''));
$allowed_fuel_sources = ['central', 'project', 'user'];
if (!in_array($fuel_source, $allowed_fuel_sources, true)) {
    $set_vehicle_alert('danger', 'ข้อมูลไม่ถูกต้อง', 'กรุณาเลือกแหล่งน้ำมันเชื้อเพลิง');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

$companion_ids = $_POST['companionIds'] ?? [];
if (!is_array($companion_ids)) {
    $companion_ids = [];
}
$companion_ids = array_values(array_unique(array_filter(array_map(
    static fn($id): string => trim((string) $id),
    $companion_ids
))));

$teacher_ids = [];
foreach (vehicle_reservation_get_teachers($connection) as $teacher) {
    $teacher_id = trim((string) ($teacher['id'] ?? ''));
    if ($teacher_id !== '') {
        $teacher_ids[$teacher_id] = true;
    }
}

$companion_ids = array_values(array_filter(
    $companion_ids,
    static fn(string $id): bool => isset($teacher_ids[$id])
));

$companion_count = count($companion_ids);
$passenger_count = max(1, $companion_count + 1);

$companion_ids_json = null;
if ($companion_count > 0) {
    $encoded = json_encode($companion_ids, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($encoded !== false) {
        $companion_ids_json = $encoded;
    }
}

$retain_attachment_ids = $_POST['retainAttachmentIds'] ?? [];
if (!is_array($retain_attachment_ids)) {
    $retain_attachment_ids = [];
}
$retain_attachment_ids = array_values(array_unique(array_filter(array_map(
    static fn($id): int => (int) $id,
    $retain_attachment_ids
))));

$uploaded_files = [];
$attachments = $_FILES['attachments'] ?? null;
$max_attachments = 5;
$max_file_size = 10 * 1024 * 1024;
$allowed_mime = [
    'application/pdf' => 'pdf',
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
];
$cleanup_uploads = static function (array $files): void {
    foreach ($files as $file) {
        $path = __DIR__ . '/../../../' . ($file['filePath'] ?? '');
        if ($path !== '' && is_file($path)) {
            @unlink($path);
        }
    }
};

if (is_array($attachments) && isset($attachments['name']) && is_array($attachments['name'])) {
    $total_files = count($attachments['name']);
    $valid_files = 0;
    for ($i = 0; $i < $total_files; $i++) {
        if (($attachments['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $valid_files++;
    }

    if ($valid_files > $max_attachments) {
        $set_vehicle_alert('warning', 'แนบไฟล์เกินจำนวนที่กำหนด', 'แนบไฟล์ได้สูงสุด 5 ไฟล์');
        header('Location: ' . $redirect_url, true, 303);
        exit();
    }

    $finfo = null;
    if (class_exists('finfo')) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
    }

    $upload_dir = __DIR__ . '/../../../assets/uploads/vehicle-bookings';
    if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true)) {
        error_log('Upload directory create failed: ' . $upload_dir);
        $set_vehicle_alert('danger', 'ระบบขัดข้อง', 'ไม่สามารถแนบไฟล์ได้ในขณะนี้');
        header('Location: ' . $redirect_url, true, 303);
        exit();
    }

    for ($i = 0; $i < $total_files; $i++) {
        $error = $attachments['error'][$i] ?? UPLOAD_ERR_NO_FILE;
        if ($error === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if ($error !== UPLOAD_ERR_OK) {
            $cleanup_uploads($uploaded_files);
            $set_vehicle_alert('danger', 'แนบไฟล์ไม่สำเร็จ', 'กรุณาลองใหม่อีกครั้ง');
            header('Location: ' . $redirect_url, true, 303);
            exit();
        }

        $size = (int) ($attachments['size'][$i] ?? 0);
        if ($size > $max_file_size) {
            $cleanup_uploads($uploaded_files);
            $set_vehicle_alert('warning', 'ไฟล์มีขนาดใหญ่เกินไป', 'รองรับไฟล์ขนาดไม่เกิน 10MB ต่อไฟล์');
            header('Location: ' . $redirect_url, true, 303);
            exit();
        }

        $tmp_name = $attachments['tmp_name'][$i] ?? '';
        $file_mime = '';
        if ($finfo && $tmp_name !== '') {
            $file_mime = (string) $finfo->file($tmp_name);
        }
        if ($file_mime === '') {
            $file_mime = (string) ($attachments['type'][$i] ?? '');
        }

        if (!isset($allowed_mime[$file_mime])) {
            $cleanup_uploads($uploaded_files);
            $set_vehicle_alert('warning', 'รูปแบบไฟล์ไม่ถูกต้อง', 'รองรับเฉพาะไฟล์ .pdf, .jpg, .png');
            header('Location: ' . $redirect_url, true, 303);
            exit();
        }

        $extension = $allowed_mime[$file_mime];
        $filename = 'vehicle_booking_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $extension;
        $target_path = $upload_dir . '/' . $filename;

        if (!move_uploaded_file($tmp_name, $target_path)) {
            $cleanup_uploads($uploaded_files);
            $set_vehicle_alert('danger', 'ระบบขัดข้อง', 'ไม่สามารถแนบไฟล์ได้ในขณะนี้');
            header('Location: ' . $redirect_url, true, 303);
            exit();
        }

        @chmod($target_path, 0644);

        $uploaded_files[] = [
            'fileName' => $attachments['name'][$i] ?? $filename,
            'filePath' => 'assets/uploads/vehicle-bookings/' . $filename,
            'mimeType' => $file_mime,
            'fileSize' => $size,
            'checksumSHA256' => hash_file('sha256', $target_path) ?: null,
        ];
    }
}

$vehicle_columns = vehicle_reservation_get_table_columns($connection, 'dh_vehicle_bookings');
$set_fields = ['dh_year = ?', 'startAt = ?', 'endAt = ?'];
$values = [$vehicle_year, $start_at, $end_at];
$types = 'iss';

$add_update = static function (string $column, string $type, $value) use (
    &$set_fields,
    &$values,
    &$types,
    $vehicle_columns
): void {
    if (!vehicle_reservation_has_column($vehicle_columns, $column)) {
        return;
    }
    $set_fields[] = $column . ' = ?';
    $values[] = $value;
    $types .= $type;
};

$add_update('department', 's', $department);
$add_update('purpose', 's', $purpose);
$add_update('location', 's', $location);
$add_update('passengerCount', 'i', $passenger_count);
$add_update('fuelSource', 's', $fuel_source);
if ($write_date !== '') {
    $add_update('writeDate', 's', $write_date);
}
$add_update('companionCount', 'i', $companion_count);
if ($companion_ids_json !== null) {
    $add_update('companionIds', 's', $companion_ids_json);
}

$requester_display_name = trim((string) ($teacher_name ?? ''));
if ($requester_display_name !== '') {
    $add_update('requesterDisplayName', 's', $requester_display_name);
}

$update_sql = 'UPDATE dh_vehicle_bookings SET ' . implode(', ', $set_fields) . ' WHERE bookingID = ?';
$values[] = $booking_id;
$types .= 'i';

if (mysqli_begin_transaction($connection) === false) {
    error_log('Database Error: ' . mysqli_error($connection));
    $set_vehicle_alert('danger', 'ระบบขัดข้อง', 'ไม่สามารถบันทึกการแก้ไขได้ในขณะนี้');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

try {
    $update_stmt = mysqli_prepare($connection, $update_sql);
    if ($update_stmt === false) {
        throw new RuntimeException('Failed to prepare booking update.');
    }

    $bind_params = array_merge([$update_stmt, $types], $values);
    $bind_refs = [];
    foreach ($bind_params as $index => $value) {
        $bind_refs[$index] = &$bind_params[$index];
    }

    call_user_func_array('mysqli_stmt_bind_param', $bind_refs);
    if (mysqli_stmt_execute($update_stmt) === false) {
        mysqli_stmt_close($update_stmt);
        throw new RuntimeException('Failed to update booking.');
    }
    mysqli_stmt_close($update_stmt);

    $existing_sql = 'SELECT r.fileID FROM dh_file_refs AS r
        INNER JOIN dh_files AS f ON r.fileID = f.fileID
        WHERE r.moduleName = ? AND r.entityName = ? AND r.entityID = ? AND f.deletedAt IS NULL';
    $existing_stmt = mysqli_prepare($connection, $existing_sql);
    if ($existing_stmt === false) {
        throw new RuntimeException('Failed to prepare attachment lookup.');
    }

    $module_name = 'vehicle';
    $entity_name = 'dh_vehicle_bookings';
    $entity_id = (string) $booking_id;
    mysqli_stmt_bind_param($existing_stmt, 'sss', $module_name, $entity_name, $entity_id);
    mysqli_stmt_execute($existing_stmt);
    $existing_result = mysqli_stmt_get_result($existing_stmt);
    $existing_ids = [];
    if ($existing_result) {
        while ($row = mysqli_fetch_assoc($existing_result)) {
            $existing_ids[] = (int) ($row['fileID'] ?? 0);
        }
    }
    mysqli_stmt_close($existing_stmt);

    $retain_ids = array_values(array_intersect($existing_ids, $retain_attachment_ids));
    $delete_ids = array_values(array_diff($existing_ids, $retain_ids));

    if (!empty($delete_ids)) {
        $placeholders = implode(', ', array_fill(0, count($delete_ids), '?'));
        $delete_sql = 'UPDATE dh_files SET deletedAt = NOW() WHERE fileID IN (' . $placeholders . ')';
        $delete_stmt = mysqli_prepare($connection, $delete_sql);
        if ($delete_stmt === false) {
            throw new RuntimeException('Failed to prepare attachment delete.');
        }

        $delete_types = str_repeat('i', count($delete_ids));
        $delete_params = array_merge([$delete_stmt, $delete_types], $delete_ids);
        $delete_refs = [];
        foreach ($delete_params as $index => $value) {
            $delete_refs[$index] = &$delete_params[$index];
        }
        call_user_func_array('mysqli_stmt_bind_param', $delete_refs);
        if (mysqli_stmt_execute($delete_stmt) === false) {
            mysqli_stmt_close($delete_stmt);
            throw new RuntimeException('Failed to delete attachments.');
        }
        mysqli_stmt_close($delete_stmt);
    }

    $attachment_file_ids = $retain_ids;
    foreach ($uploaded_files as $uploaded_file) {
        $file_sql = 'INSERT INTO dh_files (fileName, filePath, mimeType, fileSize, checksumSHA256, storageProvider, version, uploadedByPID)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
        $file_stmt = mysqli_prepare($connection, $file_sql);
        if ($file_stmt === false) {
            throw new RuntimeException('Failed to prepare file insert.');
        }

        $storage_provider = 'local';
        $version = 1;
        mysqli_stmt_bind_param(
            $file_stmt,
            'sssissis',
            $uploaded_file['fileName'],
            $uploaded_file['filePath'],
            $uploaded_file['mimeType'],
            $uploaded_file['fileSize'],
            $uploaded_file['checksumSHA256'],
            $storage_provider,
            $version,
            $requester_pid
        );

        if (mysqli_stmt_execute($file_stmt) === false) {
            mysqli_stmt_close($file_stmt);
            throw new RuntimeException('Failed to insert file.');
        }

        $attachment_file_id = (int) mysqli_insert_id($connection);
        mysqli_stmt_close($file_stmt);
        $attachment_file_ids[] = $attachment_file_id;

        $ref_sql = 'INSERT INTO dh_file_refs (fileID, moduleName, entityName, entityID, note, attachedByPID)
            VALUES (?, ?, ?, ?, ?, ?)';
        $ref_stmt = mysqli_prepare($connection, $ref_sql);
        if ($ref_stmt === false) {
            throw new RuntimeException('Failed to prepare file reference insert.');
        }

        $note = 'vehicle_reservation_attachment';
        mysqli_stmt_bind_param($ref_stmt, 'isssss', $attachment_file_id, $module_name, $entity_name, $entity_id, $note, $requester_pid);
        if (mysqli_stmt_execute($ref_stmt) === false) {
            mysqli_stmt_close($ref_stmt);
            throw new RuntimeException('Failed to insert file reference.');
        }
        mysqli_stmt_close($ref_stmt);
    }

    if (vehicle_reservation_has_column($vehicle_columns, 'attachmentFileID')) {
        $attachment_value = empty($attachment_file_ids) ? null : (int) $attachment_file_ids[0];
        $attachment_sql = 'UPDATE dh_vehicle_bookings SET attachmentFileID = ? WHERE bookingID = ?';
        $attachment_stmt = mysqli_prepare($connection, $attachment_sql);
        if ($attachment_stmt === false) {
            throw new RuntimeException('Failed to prepare attachment update.');
        }

        if ($attachment_value === null) {
            mysqli_stmt_bind_param($attachment_stmt, 'ii', $attachment_value, $booking_id);
        } else {
            mysqli_stmt_bind_param($attachment_stmt, 'ii', $attachment_value, $booking_id);
        }

        if (mysqli_stmt_execute($attachment_stmt) === false) {
            mysqli_stmt_close($attachment_stmt);
            throw new RuntimeException('Failed to update attachment reference.');
        }
        mysqli_stmt_close($attachment_stmt);
    }

    $total_attachments = count($attachment_file_ids);
    if ($total_attachments > $max_attachments) {
        throw new RuntimeException('Attachment limit exceeded.');
    }

    mysqli_commit($connection);
} catch (Throwable $e) {
    mysqli_rollback($connection);
    error_log('Vehicle Booking Update Error: ' . $e->getMessage());
    $cleanup_uploads($uploaded_files);
    $set_vehicle_alert('danger', 'บันทึกไม่สำเร็จ', 'กรุณาลองใหม่อีกครั้ง');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

$set_vehicle_alert('success', 'บันทึกการแก้ไขสำเร็จ', 'อัปเดตรายการจองเรียบร้อยแล้ว');
header('Location: ' . $redirect_url, true, 303);
exit();

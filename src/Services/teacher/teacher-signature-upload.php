<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['signature_upload'])) {
    return;
}

require_once __DIR__ . '/../../../config/connection.php';
require_once __DIR__ . '/../../../app/modules/audit/logger.php';

$redirect_url = 'profile.php?tab=signature';

$set_profile_alert = static function (string $type, string $title, string $message = '') use ($redirect_url): void {
    $_SESSION['profile_alert'] = [
        'type' => $type,
        'title' => $title,
        'message' => $message,
        'link' => $redirect_url,
    ];
};

$teacher_pid = (string) ($_SESSION['pID'] ?? '');

if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    if (function_exists('audit_log')) {
        audit_log('security', 'CSRF_FAIL', 'DENY', 'teacher', $teacher_pid, 'signature_upload');
    }
    $set_profile_alert('danger', 'ไม่สามารถยืนยันความปลอดภัย', 'กรุณาลองใหม่อีกครั้ง');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

if ($teacher_pid === '') {
    header('Location: index.php', true, 302);
    exit();
}

if (empty($_FILES['signature_file'])) {
    $set_profile_alert('danger', 'อัปโหลดไม่สำเร็จ', 'กรุณาแนบไฟล์ลายเซ็นก่อนบันทึก');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

$signature_file = $_FILES['signature_file'];

if ($signature_file['error'] !== UPLOAD_ERR_OK) {
    $set_profile_alert('danger', 'อัปโหลดไม่สำเร็จ', 'กรุณาลองใหม่อีกครั้ง');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

$max_signature_size = 2 * 1024 * 1024;

if ((int) $signature_file['size'] > $max_signature_size) {
    $set_profile_alert('warning', 'ไฟล์มีขนาดใหญ่เกินไป', 'รองรับไฟล์ขนาดไม่เกิน 2MB');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

$signature_mime = '';

if (class_exists('finfo')) {
    $finfo = new finfo(FILEINFO_MIME_TYPE);

    if ($finfo) {
        $signature_mime = (string) $finfo->file($signature_file['tmp_name']);
    }
}

if ($signature_mime === '') {
    $signature_mime = (string) ($signature_file['type'] ?? '');
}

$allowed_mime = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
];

if (!isset($allowed_mime[$signature_mime])) {
    $set_profile_alert('warning', 'รูปแบบไฟล์ไม่ถูกต้อง', 'รองรับเฉพาะไฟล์ .jpg และ .png');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

$safe_pid = preg_replace('/\D+/', '', (string) $teacher_pid);

if ($safe_pid === '') {
    $set_profile_alert('danger', 'อัปโหลดไม่สำเร็จ', 'กรุณาลองใหม่อีกครั้ง');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

$signature_dir = __DIR__ . '/../../../assets/img/signature/' . $safe_pid;

if (!is_dir($signature_dir) && !mkdir($signature_dir, 0755, true)) {
    error_log('Signature directory create failed: ' . $signature_dir);
    $set_profile_alert('danger', 'ระบบขัดข้อง', 'ไม่สามารถบันทึกไฟล์ลายเซ็นได้ในขณะนี้');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

$signature_filename = 'signature_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $allowed_mime[$signature_mime];
$signature_target = $signature_dir . '/' . $signature_filename;

if (!move_uploaded_file($signature_file['tmp_name'], $signature_target)) {
    if (function_exists('audit_log')) {
        audit_log('profile', 'SIGNATURE_UPDATE', 'FAIL', 'teacher', $teacher_pid, 'move_failed');
    }
    $set_profile_alert('danger', 'ระบบขัดข้อง', 'ไม่สามารถบันทึกไฟล์ลายเซ็นได้ในขณะนี้');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

@chmod($signature_target, 0644);

$signature_path = 'assets/img/signature/' . $safe_pid . '/' . $signature_filename;

$update_sql = 'UPDATE teacher SET signature = ? WHERE pID = ? AND status = 1';
$update_stmt = mysqli_prepare($connection, $update_sql);

if ($update_stmt === false) {
    error_log('Database Error: ' . mysqli_error($connection));

    if (function_exists('audit_log')) {
        audit_log('profile', 'SIGNATURE_UPDATE', 'FAIL', 'teacher', $teacher_pid, 'prepare_failed');
    }
    $set_profile_alert('danger', 'ระบบขัดข้อง', 'ไม่สามารถบันทึกลายเซ็นได้ในขณะนี้');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

mysqli_stmt_bind_param($update_stmt, 'ss', $signature_path, $teacher_pid);

if (mysqli_stmt_execute($update_stmt) === false) {
    mysqli_stmt_close($update_stmt);

    if (function_exists('audit_log')) {
        audit_log('profile', 'SIGNATURE_UPDATE', 'FAIL', 'teacher', $teacher_pid, 'execute_failed');
    }
    $set_profile_alert('danger', 'ระบบขัดข้อง', 'ไม่สามารถบันทึกลายเซ็นได้ในขณะนี้');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}
mysqli_stmt_close($update_stmt);

if (function_exists('audit_log')) {
    audit_log('profile', 'SIGNATURE_UPDATE', 'SUCCESS', 'teacher', $teacher_pid, null, [
        'path' => $signature_path,
    ]);
}

$set_profile_alert('success', 'บันทึกสำเร็จ', 'บันทึกลายเซ็นเรียบร้อยแล้ว');
header('Location: ' . $redirect_url, true, 303);
exit();

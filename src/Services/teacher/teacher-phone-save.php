<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['phone_save'])) {
    return;
}

require_once __DIR__ . '/../../../config/connection.php';

$redirect_url = 'profile.php?tab=personal';

$set_profile_alert = static function (string $type, string $title, string $message = '', string $button_label = 'ยืนยัน') use ($redirect_url): void {
    $_SESSION['profile_alert'] = [
        'type' => $type,
        'title' => $title,
        'message' => $message,
        'button_label' => $button_label,
        'link' => $redirect_url,
    ];
};

if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $set_profile_alert('danger', 'ไม่สามารถยืนยันความปลอดภัย', 'กรุณาลองใหม่อีกครั้ง');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

$teacher_pid = $_SESSION['pID'] ?? '';
if ($teacher_pid === '') {
    header('Location: index.php', true, 302);
    exit();
}

$telephone_raw = (string) ($_POST['telephone'] ?? '');
$telephone = preg_replace('/\D+/', '', $telephone_raw);

if ($telephone === '') {
    $set_profile_alert('danger', 'บันทึกไม่สำเร็จ', 'กรุณากรอกเบอร์โทรศัพท์');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

if (strlen($telephone) !== 10) {
    $set_profile_alert('danger', 'บันทึกไม่สำเร็จ', 'กรุณากรอกเบอร์โทรศัพท์ให้ครบ 10 หลัก');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

$select_sql = 'SELECT telephone FROM teacher WHERE pID = ? AND status = 1 LIMIT 1';
$select_stmt = mysqli_prepare($connection, $select_sql);

if ($select_stmt === false) {
    error_log('Database Error: ' . mysqli_error($connection));
    $set_profile_alert('danger', 'ระบบขัดข้อง', 'ไม่สามารถบันทึกเบอร์โทรศัพท์ได้ในขณะนี้');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

mysqli_stmt_bind_param($select_stmt, 's', $teacher_pid);
mysqli_stmt_execute($select_stmt);
$select_result = mysqli_stmt_get_result($select_stmt);
$teacher_row = $select_result ? mysqli_fetch_assoc($select_result) : null;
mysqli_stmt_close($select_stmt);

if (!$teacher_row) {
    $set_profile_alert('danger', 'ไม่พบข้อมูลผู้ใช้', 'กรุณาลองใหม่อีกครั้ง');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

$current_phone = preg_replace('/\D+/', '', (string) ($teacher_row['telephone'] ?? ''));
if ($current_phone === $telephone) {
    $set_profile_alert('warning', 'ไม่มีการเปลี่ยนแปลง', 'เบอร์โทรศัพท์นี้ถูกบันทึกไว้แล้ว');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

$update_sql = 'UPDATE teacher SET telephone = ? WHERE pID = ? AND status = 1';
$update_stmt = mysqli_prepare($connection, $update_sql);

if ($update_stmt === false) {
    error_log('Database Error: ' . mysqli_error($connection));
    $set_profile_alert('danger', 'ระบบขัดข้อง', 'ไม่สามารถบันทึกเบอร์โทรศัพท์ได้ในขณะนี้');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

mysqli_stmt_bind_param($update_stmt, 'ss', $telephone, $teacher_pid);

if (mysqli_stmt_execute($update_stmt) === false) {
    mysqli_stmt_close($update_stmt);
    $set_profile_alert('danger', 'บันทึกไม่สำเร็จ', 'กรุณาลองใหม่อีกครั้ง');
    header('Location: ' . $redirect_url, true, 303);
    exit();
}

mysqli_stmt_close($update_stmt);

$set_profile_alert('success', 'บันทึกสำเร็จ', 'บันทึกเบอร์โทรศัพท์เรียบร้อยแล้ว', 'ตกลง');
header('Location: ' . $redirect_url, true, 303);
exit();

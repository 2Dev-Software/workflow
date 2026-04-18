<?php
require_once __DIR__ . '/../../../src/Services/system/system-year.php';
require_once __DIR__ . '/../../../src/Services/teacher/teacher-profile.php';
require_once __DIR__ . '/../../../src/Services/system/exec-duty-announcement.php';

$teacher = $teacher ?? ($GLOBALS['teacher'] ?? []);
$dh_year_value = (string) ($dh_year ?? ($GLOBALS['dh_year'] ?? ''));
$exec_duty_name_value = trim((string) ($exec_duty_name ?? ($GLOBALS['exec_duty_name'] ?? '')));
$exec_duty_status_value = trim((string) ($exec_duty_status_label ?? ($GLOBALS['exec_duty_status_label'] ?? '')));
$exec_duty_announcement_value = trim((string) ($exec_duty_announcement ?? ($GLOBALS['exec_duty_announcement'] ?? '')));

if ($dh_year_value === '' || !is_numeric($dh_year_value)) {
    $dh_year_value = (string) ((int) date('Y') + 543);
}
?>
<nav class="navigation-bar">
    <div class="nav-user-info">
        <?php if ($exec_duty_name_value !== '' && $exec_duty_status_value !== '') : ?>
            <p>วันนี้ <b><?= htmlspecialchars($exec_duty_name_value, ENT_QUOTES, 'UTF-8') ?></b> <?= htmlspecialchars($exec_duty_status_value, ENT_QUOTES, 'UTF-8') ?></p>
        <?php else : ?>
            <p><?= htmlspecialchars($exec_duty_announcement_value !== '' ? $exec_duty_announcement_value : 'วันนี้ยังไม่มีข้อมูลการปฏิบัติราชการ', ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <p>ปีสารบรรณ <?= htmlspecialchars((string) $dh_year_value, ENT_QUOTES, 'UTF-8') ?></p>
    </div>
</nav>

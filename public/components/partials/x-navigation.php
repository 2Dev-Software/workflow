<?php
require_once __DIR__ . '/../../../src/Services/system/system-year.php';
require_once __DIR__ . '/../../../src/Services/teacher/teacher-profile.php';

$teacher = $teacher ?? ($GLOBALS['teacher'] ?? []);
$dh_year_value = (string) ($dh_year ?? ($GLOBALS['dh_year'] ?? ''));

if ($dh_year_value === '' || !is_numeric($dh_year_value)) {
    $dh_year_value = (string) ((int) date('Y') + 543);
}
?>
<nav class="navigation-bar">
    <div class="nav-user-info">
        <p>สวัสดี, <?= htmlspecialchars($teacher['fName'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
        <p>ปีสารบรรณ <?= htmlspecialchars((string) $dh_year_value, ENT_QUOTES, 'UTF-8') ?></p>
    </div>
</nav>

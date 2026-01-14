<?php
require_once __DIR__ . '/../../../src/Services/system/system-year.php';
require_once __DIR__ . '/../../../src/Services/teacher/teacher-profile.php';
?>
<nav class="navigation-bar">
    <div class="nav-user-info">
        <p>สวัสดี, <?= htmlspecialchars($teacher['fName'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
        <p>ปีสารบรรณ <?= htmlspecialchars($dh_year, ENT_QUOTES, 'UTF-8') ?></p>
    </div>
</nav>

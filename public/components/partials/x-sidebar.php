<?php require_once __DIR__ . '/../../../src/Services/teacher/teacher-profile.php'; ?>
<aside class="sidebar close">
    <header class="logo-details">
        <a href="#">
            <img src="assets/img/db-hub-banner.svg" alt="db-logo">
        </a>
        <i class="fa-solid fa-angle-left" id="btn-toggle"></i>
    </header>

    <main class="profile-section">
        <?php
        $profile_picture_raw = trim((string) ($teacher['picture'] ?? ''));
        $profile_picture = '';
        if ($profile_picture_raw !== '' && strtoupper($profile_picture_raw) !== 'EMPTY') {
            $profile_picture = $profile_picture_raw;
        }
        ?>
        <div class="profile-image">
            <?php if ($profile_picture !== '') : ?>
                <img src="<?= htmlspecialchars($profile_picture, ENT_QUOTES, 'UTF-8') ?>" alt="Profile image">
            <?php else : ?>
                <i class="fa-solid fa-user"></i>
            <?php endif; ?>
        </div>
        <div class="proflie-text">
            <p>ชื่อ : <?= htmlspecialchars($teacher['fName'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
            <p>ตำแหน่ง : <?= htmlspecialchars($teacher['position_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
            <p>หน้าที่ : <?= htmlspecialchars($teacher['role_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    </main>

    <div class="navigation-links">
        <li>
            <a href="dashboard.php">
                <i class="fa-solid fa-house-chimney"></i>
                <p class="link-name">หน้าหลัก</p>
            </a>
        </li>
        <li class="navigation-links-has-sub">
            <div class="icon-link">
                <a href="#">
                    <i class="fa-solid fa-book"></i>
                    <p class="link-name">หนังสือเวียน</p>
                </a>
                <i class="fa-solid fa-caret-down"></i>
            </div>
            <ul class="navigation-links-sub-menu">
                <li><a href="#">หนังสือเวียน</a></li>
                <li><a href="#">หนังสือเวียนที่จัดเก็บ</a></li>
                <li><a href="#">ส่งหนังสือเวียน</a></li>
            </ul>
        </li>
        <li class="navigation-links-has-sub">
            <div class="icon-link">
                <a href="#">
                    <i class="fa-solid fa-file"></i>
                    <p class="link-name">คำสั่งราชการ</p>
                </a>
                <i class="fa-solid fa-caret-down"></i>
            </div>
            <ul class="navigation-links-sub-menu">
                <li><a href="#">หนังสือเวียน</a></li>
                <li><a href="#">หนังสือเวียนที่จัดเก็บ</a></li>
                <li><a href="#">ส่งหนังสือเวียน</a></li>
            </ul>
        </li>
        <li>
            <a href="#">
                <i class="fa-solid fa-pen-to-square"></i>
                <p class="link-name">บันทึกข้อความ</p>
            </a>
        </li>
        <li>
            <a href="room-booking.php">
                <i class="fa-solid fa-building"></i>
                <p class="link-name">จองสถานที่/ห้อง</p>
            </a>
        </li>
        <li>
            <a href="#">
                <i class="fa-solid fa-car"></i>
                <p class="link-name">จองยานพาหนะ</p>
            </a>
        </li>
        <li>
            <a href="#">
                <i class="fa-solid fa-wrench"></i>
                <p class="link-name">แจ้งเหตุซ่อมแซม</p>
            </a>
        </li>
        <li>
            <a href="teacher-phone-directory.php">
                <i class="fa-solid fa-phone"></i>
                <p class="link-name">สมุดโทรศัพท์</p>
            </a>
        </li>
        <li>
            <a href="profile.php">
                <i class="fa-solid fa-user-gear"></i>
                <p class="link-name">โปรไฟล์</p>
            </a>
        </li>
        <?php if ((int) ($teacher['roleID'] ?? 0) === 1) : ?>
            <li>
                <a href="setting.php">
                    <i class="fa-solid fa-gear"></i>
                    <p class="link-name">การตั้งค่า</p>
                </a>
            </li>
        <?php endif; ?>
    </div>

    <div class="logout-section">
        <a href="logout.php" class="logout-btn">
            <i class="fa-solid fa-right-from-bracket"></i>
            <p>ออกจากระบบ</p>
        </a>
    </div>
</aside>

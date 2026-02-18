<?php
require_once __DIR__ . '/../../../app/db/db.php';
require_once __DIR__ . '/../../../app/rbac/roles.php';
require_once __DIR__ . '/../../../src/Services/teacher/teacher-profile.php';
require_once __DIR__ . '/../../../src/Services/system/exec-duty-current.php';

$role_id = (int) ($teacher['roleID'] ?? 0);
$position_id = (int) ($teacher['positionID'] ?? 0);
$actor_pid = (string) ($_SESSION['pID'] ?? '');

// Exec duty: dutyStatus 2 means "acting director" (รองรักษาการแทน).
$acting_pid = '';

if (($exec_duty_current_status ?? 0) === 2 && !empty($exec_duty_current_pid)) {
    $acting_pid = (string) $exec_duty_current_pid;
}
$is_director_or_acting = $position_id === 1 || ($acting_pid !== '' && $acting_pid === $actor_pid);

$sidebar_connection = db_connection();
$is_admin_user = false;
$is_registry_user = false;
$can_manage_external_circular = false;

if ($actor_pid !== '') {
    $is_admin_user = rbac_user_has_role($sidebar_connection, $actor_pid, ROLE_ADMIN) || $role_id === 1;
    $is_registry_user = rbac_user_has_role($sidebar_connection, $actor_pid, ROLE_REGISTRY) || $role_id === 2;
    $can_manage_external_circular = $is_admin_user || $is_registry_user;
}
?>
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
                <li><a href="circular-compose.php">ส่งหนังสือเวียน</a></li>
                <li><a href="circular-notice.php">หนังสือเวียน</a></li>
                <li><a href="circular-archive.php">หนังสือเวียนที่จัดเก็บ</a></li>
                <li><a href="circular-sent.php">หนังสือเวียนของฉัน</a></li>
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
                <li><a href="orders-inbox.php">คำสั่งราชการ (inbox)</a></li>
                <li><a href="orders-archive.php">คำสั่งที่จัดเก็บ</a></li>
                <li><a href="orders-create.php">ออกคำสั่งราชการ</a></li>
                <li><a href="orders-manage.php">คำสั่งของฉัน</a></li>
            </ul>
        </li>

        <li class="navigation-links-has-sub">
            <div class="icon-link">
                <a href="#">
                    <i class="fa-solid fa-pen-to-square"></i>
                    <p class="link-name">บันทึกข้อความ</p>
                </a>
                <i class="fa-solid fa-caret-down"></i>
            </div>
            <ul class="navigation-links-sub-menu">
                <li><a href="memo.php">บันทึกข้อความ</a></li>
                <li><a href="memo-inbox.php">Inbox บันทึกข้อความ</a></li>
                <li><a href="memo-archive.php">บันทึกข้อความที่จัดเก็บ</a></li>
            </ul>
        </li>

        <?php if ($can_manage_external_circular) : ?>
            <li class="navigation-links-has-sub">
                <div class="icon-link">
                    <a href="#">
                        <i class="fa-solid fa-paper-plane"></i>
                        <p class="link-name">หนังสือเวียนภายนอก</p>
                    </a>
                    <i class="fa-solid fa-caret-down"></i>
                </div>
                <ul class="navigation-links-sub-menu">
                    <li><a href="outgoing-receive.php">ลงทะเบียนรับหนังสือเวียน</a></li>
                    <?php if ($is_registry_user || $is_admin_user) : ?>
                        <li><a href="circular-notice.php?box=clerk&type=external&read=all&sort=newest&view=table1">กล่องกำลังเสนอ</a></li>
                        <li><a href="circular-notice.php?box=clerk_return&type=external&read=all&sort=newest&view=table1">กล่องพิจารณาแล้ว</a></li>
                    <?php endif; ?>
                    <li><a href="outgoing.php">ทะเบียนหนังสือออก</a></li>
                    <li><a href="outgoing-create.php">ออกเลขหนังสือภายนอก</a></li>
                </ul>
            </li>
        <?php endif; ?>

        <?php if ($is_director_or_acting) : ?>
            <li>
                <a href="circular-notice.php?box=director&type=external&read=all&sort=newest&view=table1">
                    <i class="fa-solid fa-envelope-open-text"></i>
                    <p class="link-name">พิจารณาหนังสือเวียนภายนอก</p>
                </a>
            </li>
        <?php endif; ?>

        <?php if (in_array((int) ($teacher['roleID'] ?? 0), [1, 5], true)) : ?>
            <li class="navigation-links-has-sub">
                <div class="icon-link">
                    <a href="#">
                        <i class="fa-solid fa-building"></i>
                        <p class="link-name">การจองสถานที่/ห้อง</p>
                    </a>
                    <i class="fa-solid fa-caret-down"></i>
                </div>
                <ul class="navigation-links-sub-menu">
                    <li><a href="room-booking.php">จองสถานที่/ห้อง</a></li>
                    <li><a href="room-booking-approval.php">อนุมัติการจองสถานที่/ห้อง</a></li>
                    <li><a href="room-management.php">จัดการสถานที่/ห้อง</a></li>
                </ul>
            </li>
        <?php else : ?>
            <li>
                <a href="room-booking.php">
                    <i class="fa-solid fa-building"></i>
                    <p class="link-name">จองสถานที่/ห้อง</p>
                </a>
            </li>
        <?php endif; ?>

        <?php if ($is_director_or_acting) : ?>
            <li>
                <a href="vehicle-reservation-approval.php">
                    <i class="fa-solid fa-car"></i>
                    <p class="link-name">อนุมัติการจองยานพาหนะ</p>
                </a>
            </li>
        <?php elseif (in_array($role_id, [1, 3], true)) : ?>
            <li class="navigation-links-has-sub">
                <div class="icon-link">
                    <a href="#">
                        <i class="fa-solid fa-car"></i>
                        <p class="link-name">การจองยานพาหนะ</p>
                    </a>
                    <i class="fa-solid fa-caret-down"></i>
                </div>
                <ul class="navigation-links-sub-menu">
                    <li><a href="vehicle-reservation.php">จองยานพาหนะ</a></li>
                    <li><a href="vehicle-reservation-approval.php">อนุมัติการจองยานพาหนะ</a></li>
                    <li><a href="vehicle-management.php">จัดการยานพาหนะ</a></li>
                </ul>
            </li>
        <?php else : ?>
            <li>
                <a href="vehicle-reservation.php">
                    <i class="fa-solid fa-car"></i>
                    <p class="link-name">จองยานพาหนะ</p>
                </a>
            </li>
        <?php endif; ?>
        <!--
        <li>
            <a href="repairs.php">
                <i class="fa-solid fa-wrench"></i>
                <p class="link-name">แจ้งเหตุซ่อมแซม</p>
            </a>
        </li>
        -->
        <li>
            <a href="teacher-phone-directory.php">
                <i class="fa-solid fa-phone"></i>
                <p class="link-name">สมุดโทรศัพท์</p>
            </a>
        </li>
        <!-- <li class="navigation-links-has-sub">
            <div class="icon-link">
                <a href="#">
                    <i class="fa-solid fa-briefcase"></i>
                    <p class="link-name">งานอนาคต</p>
                </a>
                <i class="fa-solid fa-caret-down"></i>
            </div>
            <ul class="navigation-links-sub-menu">
                <li><a href="travel.php">ขออนุญาตไปราชการ</a></li>
                <li><a href="leave.php">การลา</a></li>
                <li><a href="certificates.php">ทะเบียนเกียรติบัตร</a></li>
            </ul>
        </li> -->

        <li>
            <a href="profile.php">
                <i class="fa-solid fa-user-gear"></i>
                <p class="link-name">โปรไฟล์</p>
            </a>
        </li>
        <?php if (in_array((int) ($teacher['roleID'] ?? 0), [1, 2], true)) : ?>
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

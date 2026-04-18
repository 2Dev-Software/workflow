<?php
require_once __DIR__ . '/../../../app/db/db.php';
require_once __DIR__ . '/../../../app/rbac/roles.php';
require_once __DIR__ . '/../../../src/Services/teacher/teacher-profile.php';
require_once __DIR__ . '/../../../src/Services/system/exec-duty-current.php';

$role_ids = rbac_parse_role_ids($teacher['roleID'] ?? '');
$position_id = (int) ($teacher['positionID'] ?? 0);
$repair_staff_role_id = 7;
$actor_pid = (string) ($_SESSION['pID'] ?? '');

// Exec duty: dutyStatus 2 means "acting director" (รองรักษาการแทน).
$acting_pid = '';

if (($exec_duty_current_status ?? 0) === 2 && !empty($exec_duty_current_pid)) {
    $acting_pid = (string) $exec_duty_current_pid;
}

$sidebar_connection = db_connection();
$is_admin_user = in_array(1, $role_ids, true);
$is_registry_user = in_array(2, $role_ids, true);
$is_vehicle_user = in_array(3, $role_ids, true);
$is_facility_user = in_array(5, $role_ids, true);
$is_repair_staff_user = in_array($repair_staff_role_id, $role_ids, true);

if ($actor_pid !== '') {
    $is_admin_user = rbac_user_has_role($sidebar_connection, $actor_pid, ROLE_ADMIN) || $is_admin_user;
    $is_registry_user = rbac_user_has_role($sidebar_connection, $actor_pid, ROLE_REGISTRY) || $is_registry_user;
    $is_vehicle_user = rbac_user_has_role($sidebar_connection, $actor_pid, ROLE_VEHICLE) || $is_vehicle_user;
    $is_facility_user = rbac_user_has_role($sidebar_connection, $actor_pid, ROLE_FACILITY) || $is_facility_user;
    $is_repair_staff_user = rbac_user_has_role($sidebar_connection, $actor_pid, ROLE_REPAIR) || $is_repair_staff_user;
}

$is_director_or_acting = $position_id === 1 || ($acting_pid !== '' && $acting_pid === $actor_pid);
$can_manage_external_circular = $is_admin_user || $is_registry_user;
$can_access_external_circular_menu = $can_manage_external_circular || $is_director_or_acting;
$can_manage_room_module = $is_admin_user || $is_facility_user;
$can_manage_vehicle_module = $is_admin_user || $is_vehicle_user;
$can_access_settings = $is_admin_user || $is_registry_user;
$can_manage_repair_module = $is_admin_user || $is_facility_user || $is_repair_staff_user;
?>
<aside class="sidebar close">
    <header class="logo-details">
        <a href="#">
            <img src="assets/img/DBsarabun_banner.png" alt="DB Sarabun">
        </a>
        <i class="fa-solid fa-angle-left" id="btn-toggle"></i>
    </header>
    <hr>
    <div class="navigation-links">
        <li>
            <a href="dashboard.php">
                <span class="red-dot-alert pulse-shadow"></span>
                <!-- <i class="fa-solid fa-house-chimney"></i> -->
                <img src="/public/assets/img/icon/home.png" alt="">
                <p class="link-name">หน้าหลัก</p>
            </a>
        </li>

        <li>
            <a href="#news-paper">
                <span class="red-dot-alert pulse-shadow"></span>
                <!-- <i class="fa-solid fa-house-chimney"></i> -->
                <img src="/public/assets/img/icon/news-paper.png" alt="">
                <p class="link-name">ข่าวประชาสัมพันธ์ </p>
            </a>
        </li>

        <li class="navigation-links-has-sub">
            <div class="icon-link">
                <a href="#">
                    <span class="red-dot-alert pulse-shadow"></span>
                    <!-- <i class="fa-solid fa-book"></i> -->
                    <img src="/public/assets/img/icon/envelope.png" alt="">
                    <p class="link-name">หนังสือเวียน</p>
                </a>
                <i class="fa-solid fa-caret-down"></i>
            </div>
            <ul class="navigation-links-sub-menu">
                <li><a href="circular-compose.php">ส่งหนังสือเวียน</a></li>
                <li><a href="circular-notice.php">หนังสือเวียน</a></li>
                <li><a href="circular-archive.php">หนังสือเวียนที่จัดเก็บ</a></li>
            </ul>
        </li>

        <?php if ($can_access_external_circular_menu): ?>
            <li class="navigation-links-has-sub">
                <div class="icon-link">
                    <a href="#">
                        <span class="red-dot-alert pulse-shadow"></span>
                        <img src="/public/assets/img/icon/files.png" alt="">
                        <p class="link-name">หนังสือเวียน</p>
                    </a>
                    <i class="fa-solid fa-caret-down"></i>
                </div>
                <ul class="navigation-links-sub-menu">
                    <?php if ($can_manage_external_circular): ?>
                        <li><a href="outgoing-receive.php">ลงทะเบียนรับหนังสือเวียน</a></li>
                        <li><a href="outgoing-notice.php?box=clerk&type=external&read=all&sort=newest&view=table1">กล่องกำลังเสนอ</a></li>
                        <li><a href="outgoing-notice.php?box=clerk_return&type=external&read=all&sort=newest&view=table1">กล่องพิจารณาแล้ว</a></li>
                    <?php endif; ?>
                    <?php if ($is_director_or_acting): ?>
                        <li><a href="outgoing-notice.php?box=director&type=external&read=all&sort=newest&view=table1">กล่องรอพิจารณา</a></li>
                    <?php endif; ?>
                </ul>
            </li>
        <?php endif; ?>

        <li class="navigation-links-has-sub">
            <div class="icon-link">
                <a href="#">
                    <!-- <i class="fa-solid fa-book"></i> -->
                    <img src="/public/assets/img/icon/envelope.png" alt="">
                    <p class="link-name">หนังสือเวียน (ภายใน)</p>
                </a>
                <i class="fa-solid fa-caret-down"></i>
            </div>
            <ul class="navigation-links-sub-menu">
                <li><a href="circular-compose.php">ส่งหนังสือเวียน</a></li>
                <li><a href="circular-notice.php">หนังสือเวียน</a></li>
                <li><a href="circular-archive.php">หนังสือเวียนที่จัดเก็บ</a></li>
            </ul>
        </li>

        <?php if ($can_manage_external_circular): ?>
            <li>
                <a href="outgoing.php">
                    <img src="/public/assets/img/icon/clipboard.png" alt="">
                    <p class="link-name">ออกเลขทะเบียนส่ง</p>
                </a>
            </li>
        <?php endif; ?>

        <li class="navigation-links-has-sub">
            <div class="icon-link">
                <a href="#">
                    <span class="red-dot-alert pulse-shadow"></span>
                    <!-- <i class="fa-solid fa-pen-to-square"></i> -->
                    <img src="/public/assets/img/icon/memo.png" alt="">
                    <p class="link-name">บันทึกข้อความ</p>
                </a>
                <i class="fa-solid fa-caret-down"></i>
            </div>
            <ul class="navigation-links-sub-menu">
                <li><a href="memo.php">บันทึกข้อความ</a></li>
                <li><a href="memo-inbox.php">กล่องบันทึกข้อความ</a></li>
                <li><a href="memo-archive.php">บันทึกข้อความที่จัดเก็บ</a></li>
            </ul>
        </li>

        <li class="navigation-links-has-sub">
            <div class="icon-link">
                <a href="#">
                    <span class="red-dot-alert pulse-shadow"></span>
                    <!-- <i class="fa-solid fa-file"></i> -->
                     <img src="/public/assets/img/icon/files.png" alt="">
                    <p class="link-name">คำสั่งราชการ</p>
                </a>
                <i class="fa-solid fa-caret-down"></i>
            </div>
            <ul class="navigation-links-sub-menu">
                <li><a href="orders-create.php">ออกเลขคำสั่งราชการ</a></li>
                <li><a href="orders-inbox.php">กล่องคำสั่งราชการ</a></li>
                <li><a href="orders-archive.php">คำสั่งราชการที่จัดเก็บ</a></li>
            </ul>
        </li>

        <?php if ($can_manage_room_module): ?>
            <li class="navigation-links-has-sub">
                <div class="icon-link">
                    <a href="#">
                        <span class="red-dot-alert pulse-shadow"></span>
                        <!-- <i class="fa-solid fa-building"></i> -->
                         <img src="/public/assets/img/icon/building.png" alt="">
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
        <?php else: ?>
            <li>
                <a href="room-booking.php">
                    <span class="red-dot-alert pulse-shadow"></span>
                    <i class="fa-solid fa-building"></i>
                    <p class="link-name">จองสถานที่/ห้อง</p>
                </a>
            </li>
        <?php endif; ?>

        <?php if ($is_director_or_acting): ?>
            <li>
                <a href="vehicle-reservation-approval.php">
                    <span class="red-dot-alert pulse-shadow"></span>
                    <i class="fa-solid fa-car"></i>
                    <p class="link-name">อนุมัติการจองยานพาหนะ</p>
                </a>
            </li>
        <?php elseif ($can_manage_vehicle_module): ?>
            <li class="navigation-links-has-sub">
                <div class="icon-link">
                    <a href="#">
                        <span class="red-dot-alert pulse-shadow"></span>
                        <!-- <i class="fa-solid fa-car"></i> -->
                        <img src="/public/assets/img/icon/car.png" alt="">
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
        <?php elseif ($is_director_or_acting): ?>
            <li>
                <a href="vehicle-reservation-approval.php">
                    <i class="fa-solid fa-car"></i>
                    <p class="link-name">อนุมัติการจองยานพาหนะ</p>
                </a>
            </li>
        <?php else: ?>
            <li>
                <a href="vehicle-reservation.php">
                    <span class="red-dot-alert pulse-shadow"></span>
                    <i class="fa-solid fa-car"></i>
                    <p class="link-name">จองยานพาหนะ</p>
                </a>
            </li>
        <?php endif; ?>
        <li class="navigation-links-has-sub">
            <div class="icon-link">
                <a href="#">
                    <span class="red-dot-alert pulse-shadow"></span>
                    <!-- <i class="fa-solid fa-wrench"></i> -->
                    <img src="/public/assets/img/icon/repair.png" alt="">
                    <p class="link-name">แจ้งเหตุซ่อมแซม</p>
                </a>
                <i class="fa-solid fa-caret-down"></i>
            </div>
            <ul class="navigation-links-sub-menu">
                <li><a href="repairs.php">แจ้งเหตุซ่อมแซม</a></li>
                <?php if ($can_manage_repair_module): ?>
                    <li><a href="repairs-approval.php">อนุมัติการซ่อมแซม</a></li>
                <?php endif; ?>
                <?php if ($is_admin_user): ?>
                    <li><a href="repairs-management.php">จัดการงานซ่อม</a></li>
                <?php endif; ?>
            </ul>
        </li>
        <li>
            <a href="teacher-phone-directory.php">
                <!-- <i class="fa-solid fa-phone"></i> -->
                <img src="/public/assets/img/icon/phone.png" alt="">
                <p class="link-name">สมุดโทรศัพท์</p>
            </a>
        </li>

        <li>
            <a href="profile.php">
                <!-- <i class="fa-solid fa-user-gear"></i> -->
                 <img src="/public/assets/img/icon/user.png" alt="">
                <p class="link-name">โปรไฟล์</p>
            </a>
        </li>
        <?php if ($can_access_settings): ?>
            <li>
                <a href="setting.php">
                    <!-- <i class="fa-solid fa-gear"></i> -->
                    <img src="/public/assets/img/icon/setting.png" alt="">
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

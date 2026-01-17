<?php
require_once __DIR__ . '/src/Services/auth/auth-guard.php';
require_once __DIR__ . '/src/Services/security/security-service.php';
require_once __DIR__ . '/src/Services/room/room-management-member-actions.php';
require_once __DIR__ . '/src/Services/room/room-management-room-actions.php';
require_once __DIR__ . '/src/Services/system/system-year.php';
require_once __DIR__ . '/src/Services/teacher/teacher-profile.php';
require_once __DIR__ . '/src/Services/room/room-management-data.php';

$allowed_room_roles = [1, 5];
$current_role_id = (int) ($teacher['roleID'] ?? ($teacher_role_id ?? 0));
if (!in_array($current_role_id, $allowed_room_roles, true)) {
    header('Location: dashboard.php', true, 302);
    exit();
}

$room_status_classes = [
    'พร้อมใช้งาน' => 'available',
    'ระงับชั่วคราว' => 'paused',
    'กำลังซ่อม' => 'maintenance',
    'ไม่พร้อมใช้งาน' => 'unavailable',
];
$room_status_options = array_keys($room_status_classes);
$room_management_rooms = $room_management_rooms ?? [];

$thai_months = [
    1 => 'มกราคม',
    2 => 'กุมภาพันธ์',
    3 => 'มีนาคม',
    4 => 'เมษายน',
    5 => 'พฤษภาคม',
    6 => 'มิถุนายน',
    7 => 'กรกฎาคม',
    8 => 'สิงหาคม',
    9 => 'กันยายน',
    10 => 'ตุลาคม',
    11 => 'พฤศจิกายน',
    12 => 'ธันวาคม',
];

$format_thai_datetime = static function (string $datetime) use ($thai_months): string {
    if ($datetime === '' || strpos($datetime, '0000-00-00') === 0) {
        return '-';
    }

    $date_obj = DateTime::createFromFormat('Y-m-d H:i:s', $datetime);
    if ($date_obj === false) {
        $date_obj = DateTime::createFromFormat('Y-m-d H:i', $datetime);
    }
    if ($date_obj === false) {
        return $datetime;
    }

    $day = (int) $date_obj->format('j');
    $month = (int) $date_obj->format('n');
    $year = (int) $date_obj->format('Y') + 543;
    $month_label = $thai_months[$month] ?? '';

    return trim($day . ' ' . $month_label . ' ' . $year . ' เวลา ' . $date_obj->format('H:i'));
};

$room_staff_members = $room_staff_members ?? [];
$room_candidate_members = $room_candidate_members ?? [];
$room_staff_count = $room_staff_count ?? count($room_staff_members);
$room_candidate_count = $room_candidate_count ?? count($room_candidate_members);
$room_management_alert = $room_management_alert ?? null;
$room_management_open_modal = '';
if (isset($_SESSION['room_management_alert'])) {
    $room_management_alert = $_SESSION['room_management_alert'];
    unset($_SESSION['room_management_alert']);
}
if (isset($_SESSION['room_management_open_modal'])) {
    $room_management_open_modal = (string) $_SESSION['room_management_open_modal'];
    unset($_SESSION['room_management_open_modal']);
}
?>
<!DOCTYPE html>
<html lang="th">
<?php require_once __DIR__ . '/public/components/x-head.php'; ?>

<body>

    <?php require_once __DIR__ . '/public/components/layout/preloader.php'; ?>
    <?php if (!empty($room_management_alert)) : ?>
        <?php $alert = $room_management_alert; ?>
        <?php require __DIR__ . '/public/components/x-alert.php'; ?>
    <?php endif; ?>

    <?php require_once __DIR__ . '/public/components/partials/x-sidebar.php'; ?>

    <section class="main-section">

        <?php require_once __DIR__ . '/public/components/partials/x-navigation.php'; ?>

        <main class="content-wrapper">

            <div class="content-header">
                <h1>ยินดีต้อนรับ</h1>
                <p>จัดการสถานที่/ห้อง</p>
            </div>

            <div class="content-area room-admin-page">
                <section class="booking-card booking-list-card room-admin-card">
                    <div class="booking-card-header">
                        <div class="booking-card-title-group">
                            <h2 class="booking-card-title">รายการสถานที่/ห้อง</h2>
                            <p class="booking-card-subtitle">ปรับชื่อห้อง เปลี่ยนสถานะ และกำหนดผู้รับผิดชอบตามหน่วยงาน</p>
                        </div>
                        <div class="room-admin-actions" data-room-filter>
                            <div class="room-admin-search">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <input class="form-input" type="search" placeholder="ค้นหาชื่อห้องหรือสถานที่" autocomplete="off" data-room-search-input>
                            </div>
                            <div class="room-admin-filter">
                                <select class="form-input" data-room-status-filter>
                                    <option value="all">ทุกสถานะ</option>
                                    <option value="available">พร้อมใช้งาน</option>
                                    <option value="paused">ระงับชั่วคราว</option>
                                    <option value="maintenance">กำลังซ่อม</option>
                                    <option value="unavailable">ไม่พร้อมใช้งาน</option>
                                </select>
                            </div>
                            <button type="button" class="btn-confirm" data-room-modal-open="roomAddModal">เพิ่มห้องใหม่</button>
                        </div>
                    </div>

                    <div class="room-admin-legend">
                        <span class="room-status-pill available">พร้อมใช้งาน</span>
                        <span class="room-status-pill paused">ระงับชั่วคราว</span>
                        <span class="room-status-pill maintenance">กำลังซ่อม</span>
                        <span class="room-status-pill unavailable">ไม่พร้อมใช้งาน</span>
                    </div>

                    <div class="table-responsive">
                        <table class="custom-table booking-table room-admin-table">
                            <thead>
                                <tr>
                                    <th>ห้อง/สถานที่</th>
                                    <th>สถานะ</th>
                                    <th>หมายเหตุ</th>
                                    <th>อัปเดตล่าสุด</th>
                                    <th>จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($room_management_rooms)) : ?>
                                    <?php foreach ($room_management_rooms as $room) : ?>
                                        <?php
                                        $room_id = (int) ($room['roomID'] ?? 0);
                                        $room_name = trim((string) ($room['roomName'] ?? ''));
                                        $room_status = trim((string) ($room['roomStatus'] ?? ''));
                                        $room_note = trim((string) ($room['roomNote'] ?? ''));
                                        $created_at = (string) ($room['createdAt'] ?? '');
                                        $updated_at = (string) ($room['updatedAt'] ?? '');
                                        $display_updated = $updated_at !== '' && strpos($updated_at, '0000-00-00') !== 0
                                            ? $updated_at
                                            : $created_at;
                                        $status_label = $room_status !== '' ? $room_status : 'ไม่พร้อมใช้งาน';
                                        $status_class = $room_status_classes[$status_label] ?? 'paused';
                                        $updated_label = $format_thai_datetime($display_updated);
                                        $row_search = trim(implode(' ', [
                                            $room_name,
                                            $room_note,
                                            $updated_label,
                                            $status_label,
                                        ]));
                                        ?>
                                        <tr data-room-row data-room-id="<?= htmlspecialchars((string) $room_id, ENT_QUOTES, 'UTF-8') ?>" data-room-status="<?= htmlspecialchars($status_class, ENT_QUOTES, 'UTF-8') ?>" data-room-name="<?= htmlspecialchars($room_name, ENT_QUOTES, 'UTF-8') ?>" data-room-status-label="<?= htmlspecialchars($status_label, ENT_QUOTES, 'UTF-8') ?>" data-room-note="<?= htmlspecialchars($room_note, ENT_QUOTES, 'UTF-8') ?>" data-room-search="<?= htmlspecialchars($row_search, ENT_QUOTES, 'UTF-8') ?>">
                                            <td>
                                                <div class="room-admin-room-name"><?= htmlspecialchars($room_name !== '' ? $room_name : 'ไม่ระบุชื่อห้อง', ENT_QUOTES, 'UTF-8') ?></div>
                                                <div class="room-admin-room-meta">ใช้สำหรับการประชุมและกิจกรรมภายในโรงเรียน</div>
                                            </td>
                                            <td>
                                                <span class="room-status-pill <?= htmlspecialchars($status_class, ENT_QUOTES, 'UTF-8') ?>">
                                                    <?= htmlspecialchars($status_label, ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($room_note !== '' ? $room_note : '-', ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($updated_label, ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="room-admin-actions-cell">
                                                <div class="booking-action-group">
                                                    <button type="button" class="booking-action-btn secondary" data-room-edit="true">แก้ไข</button>
                                                    <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') ?>" data-room-delete-form>
                                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                                                        <input type="hidden" name="room_action" value="delete">
                                                        <input type="hidden" name="room_id" value="<?= htmlspecialchars((string) $room_id, ENT_QUOTES, 'UTF-8') ?>">
                                                        <button type="button" class="booking-action-btn danger" data-room-delete-btn>ลบ</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr class="booking-empty">
                                        <td colspan="5">ยังไม่มีข้อมูลห้องในระบบ</td>
                                    </tr>
                                <?php endif; ?>
                                <tr class="booking-empty hidden" data-room-empty>
                                    <td colspan="5">ไม่พบรายการที่ค้นหา</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="booking-card booking-list-card room-admin-card room-admin-members-card">
                    <div class="booking-card-header">
                        <div class="booking-card-title-group">
                            <h2 class="booking-card-title">เกี่ยวกับเจ้าหน้าที่สถานที่</h2>
                            <p class="booking-card-subtitle">รายชื่อทีมงานที่ดูแลห้องประชุมในระบบ</p>
                        </div>
                        <div class="room-admin-member-actions">
                            <span class="room-admin-room-chip">จัดการข้อมูลเจ้าหน้าที่สถานที่</span>
                            <button type="button" class="btn-confirm" data-room-modal-open="roomMemberModal">เพิ่มสมาชิก</button>
                        </div>
                    </div>
                    <div class="room-admin-member-summary">
                        <div class="room-admin-member-summary-header">
                            <p class="room-admin-member-count">ทั้งหมด <?= htmlspecialchars((string) $room_staff_count, ENT_QUOTES, 'UTF-8') ?> คน</p>
                            <p class="room-admin-member-note">ผู้ที่มีตำแหน่งเจ้าหน้าที่สถานที่จะถูกกำหนดเป็นผู้รับผิดชอบอัตโนมัติ</p>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="custom-table booking-table room-admin-member-table">
                            <thead>
                                <tr>
                                    <th>ชื่อ-นามสกุล</th>
                                    <th>ตำแหน่ง/หน่วยงาน</th>
                                    <th>หน้าที่</th>
                                    <th>สถานะ</th>
                                    <th>จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($room_staff_members)) : ?>
                                    <?php foreach ($room_staff_members as $member) : ?>
                                        <?php
                                        $member_name = trim((string) ($member['name'] ?? ''));
                                        $position_label = trim((string) ($member['position_name'] ?? ''));
                                        $department_label = trim((string) ($member['department_name'] ?? ''));
                                        $role_label = trim((string) ($member['role_name'] ?? ''));
                                        if ($member_name === '') {
                                            $member_name = 'ไม่ระบุชื่อ';
                                        }
                                        if ($position_label === '') {
                                            $position_label = 'ไม่ระบุตำแหน่ง';
                                        }
                                        ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($member_name, ENT_QUOTES, 'UTF-8') ?></strong>
                                            </td>
                                            <td>
                                                <div class="room-admin-member-position"><?= htmlspecialchars($position_label, ENT_QUOTES, 'UTF-8') ?></div>
                                                <?php if ($department_label !== '') : ?>
                                                    <div class="room-admin-member-subtext"><?= htmlspecialchars($department_label, ENT_QUOTES, 'UTF-8') ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="room-admin-member-tag">
                                                    <?= htmlspecialchars($role_label !== '' ? $role_label : 'ไม่ระบุหน้าที่', ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                            </td>
                                            <td><span class="member-status-pill">อยู่ในทีมแล้ว</span></td>
                                            <td>
                                                <div class="booking-action-group">
                                                    <form class="booking-action-form" data-member-remove-form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') ?>">
                                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                                                        <input type="hidden" name="member_action" value="remove">
                                                        <input type="hidden" name="member_pid" value="<?= htmlspecialchars((string) ($member['pID'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                                        <button type="submit" class="booking-action-btn danger">ลบ</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr class="booking-empty">
                                        <td colspan="5">ยังไม่มีข้อมูลเจ้าหน้าที่สถานที่</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

        </main>

        <?php require_once __DIR__ . '/public/components/partials/x-footer.php'; ?>

    </section>

    <div id="roomAddModal" class="modal-overlay hidden">
        <div class="modal-content room-admin-modal">
            <header class="modal-header">
                <div class="modal-title">
                    <i class="fa-solid fa-plus"></i>
                    <span>เพิ่มห้อง/สถานที่ใหม่</span>
                </div>
                <div class="close-modal-btn" data-room-modal-close="roomAddModal">
                    <i class="fa-solid fa-xmark"></i>
                </div>
            </header>
            <div class="modal-body room-admin-modal-body">
                <form class="room-admin-form" method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="room_action" value="add">
                    <div class="form-group full">
                        <label class="form-label">ชื่อห้อง/สถานที่</label>
                        <input class="form-input" type="text" name="room_name" maxlength="150" placeholder="เช่น ห้องประชุมเพชรประดู่" required>
                    </div>
                    <div class="form-group full">
                        <label class="form-label">สถานะ</label>
                        <select class="form-input" name="room_status" required>
                            <?php foreach ($room_status_options as $status_option) : ?>
                                <option value="<?= htmlspecialchars($status_option, ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($status_option, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="form-hint">สถานะนี้จะกำหนดว่าครูสามารถจองห้องได้หรือไม่</p>
                    </div>
                    <div class="room-admin-note">
                        <i class="fa-solid fa-circle-info"></i>
                        <div>
                            <p class="room-admin-note-title">ผู้รับผิดชอบ</p>
                            <p class="room-admin-note-text">ระบบจะกำหนดผู้ที่มีตำแหน่งเจ้าหน้าที่สถานที่เป็นผู้ดูแลอัตโนมัติ สามารถเพิ่มสมาชิกได้ในส่วนผู้รับผิดชอบห้อง</p>
                        </div>
                    </div>
                    <div class="form-group full">
                        <label class="form-label">หมายเหตุเพิ่มเติม</label>
                        <textarea class="form-input booking-textarea" rows="3" name="room_note" maxlength="2000" placeholder="ระบุรายละเอียดเพิ่มเติม"></textarea>
                    </div>
                    <div class="room-admin-modal-actions">
                        <button type="button" class="btn-outline" data-room-modal-close="roomAddModal">ยกเลิก</button>
                        <button type="submit" class="btn-confirm">บันทึกข้อมูล</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="roomEditModal" class="modal-overlay hidden">
        <div class="modal-content room-admin-modal">
            <header class="modal-header">
                <div class="modal-title">
                    <i class="fa-solid fa-pen"></i>
                    <span>แก้ไขข้อมูลห้อง</span>
                </div>
                <div class="close-modal-btn" data-room-modal-close="roomEditModal">
                    <i class="fa-solid fa-xmark"></i>
                </div>
            </header>
            <div class="modal-body room-admin-modal-body">
                <form class="room-admin-form" method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="room_action" value="edit">
                    <input type="hidden" name="room_id" value="" data-room-edit-id>
                    <div class="form-group full">
                        <label class="form-label">ชื่อห้อง/สถานที่</label>
                        <input class="form-input" type="text" name="room_name" maxlength="150" value="" required data-room-edit-name>
                        <p class="form-hint">ชื่อห้องจะถูกใช้แสดงในการจองและปฏิทินของผู้ใช้</p>
                    </div>
                    <div class="form-group full">
                        <label class="form-label">สถานะ</label>
                        <select class="form-input" name="room_status" required data-room-edit-status>
                            <?php foreach ($room_status_options as $status_option) : ?>
                                <option value="<?= htmlspecialchars($status_option, ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($status_option, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="form-hint">ปรับสถานะเพื่อควบคุมการจองและการแสดงผล</p>
                    </div>
                    <div class="room-admin-note">
                        <i class="fa-solid fa-circle-info"></i>
                        <div>
                            <p class="room-admin-note-title">ผู้รับผิดชอบ</p>
                            <p class="room-admin-note-text">ผู้ที่มีตำแหน่งเจ้าหน้าที่สถานที่จะถูกระบุเป็นผู้ดูแลอัตโนมัติ หากต้องการเพิ่มคนใหม่ให้ใช้ส่วนผู้รับผิดชอบห้อง</p>
                        </div>
                    </div>
                    <div class="form-group full">
                        <label class="form-label">หมายเหตุเพิ่มเติม</label>
                        <textarea class="form-input booking-textarea" rows="3" name="room_note" maxlength="2000" data-room-edit-note></textarea>
                    </div>
                    <div class="room-admin-modal-actions">
                        <button type="button" class="btn-outline" data-room-modal-close="roomEditModal">ยกเลิก</button>
                        <button type="submit" class="btn-confirm">บันทึกการเปลี่ยนแปลง</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="roomMemberModal" class="modal-overlay hidden">
        <div class="modal-content room-admin-modal">
            <header class="modal-header">
                <div class="modal-title">
                    <i class="fa-solid fa-user-group"></i>
                    <span>เพิ่มสมาชิกผู้รับผิดชอบ</span>
                </div>
                <div class="close-modal-btn" data-room-modal-close="roomMemberModal">
                    <i class="fa-solid fa-xmark"></i>
                </div>
            </header>
            <div class="modal-body room-admin-modal-body">
                <div class="room-admin-member-toolbar" style="margin-top: 12px;">
                    <form class="room-admin-member-search" role="search" data-member-search-form>
                        <div class="search-box">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" placeholder="ค้นหาด้วย ชื่อจริง-นามสกุล หรือ กลุ่มสาระ หรือ เบอร์โทรศัพท์" autocomplete="off" data-member-search>
                        </div>
                    </form>
                </div>
                <div class="room-admin-member-panel">
                    <div class="room-admin-member-panel-header">
                        <div>
                            <p class="room-admin-member-panel-title">รายชื่อครูและบุคลากรทั้งหมด</p>
                            <span class="room-admin-member-panel-subtitle">เลือกเพื่อแต่งตั้งเป็นผู้รับผิดชอบ</span>
                        </div>
                        <span class="room-admin-member-panel-count" data-member-count>ทั้งหมด <?= htmlspecialchars((string) $room_candidate_count, ENT_QUOTES, 'UTF-8') ?> คน</span>
                    </div>
                    <div class="room-admin-member-list">
                        <?php if (!empty($room_candidate_members)) : ?>
                            <?php foreach ($room_candidate_members as $member) : ?>
                            <?php
                            $member_name = trim((string) ($member['name'] ?? ''));
                            $position_label = trim((string) ($member['position_name'] ?? ''));
                            $department_label = trim((string) ($member['department_name'] ?? ''));
                            $role_label = trim((string) ($member['role_name'] ?? ''));
                            $telephone = trim((string) ($member['telephone'] ?? ''));
                            if ($member_name === '') {
                                $member_name = 'ไม่ระบุชื่อ';
                            }
                            if ($position_label === '') {
                                $position_label = 'ไม่ระบุตำแหน่ง';
                            }
                            $member_search = trim(implode(' ', [
                                $member_name,
                                $position_label,
                                $department_label,
                                $role_label,
                                $telephone,
                            ]));
                            ?>
                            <div
                                class="room-admin-member-card"
                                data-member-card
                                data-member-search="<?= htmlspecialchars($member_search, ENT_QUOTES, 'UTF-8') ?>"
                                data-member-name="<?= htmlspecialchars($member_name, ENT_QUOTES, 'UTF-8') ?>"
                                data-member-position="<?= htmlspecialchars($position_label, ENT_QUOTES, 'UTF-8') ?>"
                                data-member-department="<?= htmlspecialchars($department_label, ENT_QUOTES, 'UTF-8') ?>"
                                data-member-role="<?= htmlspecialchars($role_label, ENT_QUOTES, 'UTF-8') ?>"
                                data-member-pid="<?= htmlspecialchars((string) ($member['pID'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                <div class="room-admin-member-info">
                                    <p class="room-admin-member-name"><?= htmlspecialchars($member_name, ENT_QUOTES, 'UTF-8') ?></p>
                                    <div class="room-admin-member-meta">
                                        <span class="room-admin-member-role"><?= htmlspecialchars($position_label, ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php if ($department_label !== '') : ?>
                                            <span class="room-admin-member-tag"><?= htmlspecialchars($department_label, ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <form class="room-admin-member-action" method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="member_action" value="add">
                                    <input type="hidden" name="member_pid" value="<?= htmlspecialchars((string) ($member['pID'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    <button type="submit" class="btn-confirm">เพิ่มเป็นสมาชิก</button>
                                </form>
                            </div>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <div class="room-admin-member-empty" data-member-empty>ไม่พบรายชื่อที่ค้นหา</div>
                        <?php endif; ?>
                        <?php if (!empty($room_candidate_members)) : ?>
                            <div class="room-admin-member-empty hidden" data-member-empty>ไม่พบรายชื่อที่ค้นหา</div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="room-admin-modal-actions">
                    <button type="button" class="btn-outline" data-room-modal-close="roomMemberModal">ปิดหน้าต่าง</button>
                </div>
            </div>
        </div>
    </div>

    <div id="roomMemberConfirmModal" class="alert-overlay hidden">
        <div class="alert-box warning room-member-confirm-alert">
            <div class="alert-header">
                <div class="icon-circle"><i class="fa-solid fa-user-plus"></i></div>
            </div>
            <div class="alert-body">
                <h1>ยืนยันการเพิ่มผู้รับผิดชอบ</h1>
                <p data-room-member-confirm-message>โปรดยืนยันการแต่งตั้งบุคลากรเป็นผู้รับผิดชอบห้องนี้</p>
                <div class="alert-actions">
                    <button type="button" class="btn-close-alert" data-room-member-confirm="true">ยืนยันเพิ่ม</button>
                    <button type="button" class="btn-close-alert btn-cancel-alert" data-room-member-cancel="true">ยกเลิก</button>
                </div>
            </div>
        </div>
    </div>

    <div id="roomMemberRemoveConfirmModal" class="alert-overlay hidden">
        <div class="alert-box danger room-member-confirm-alert">
            <div class="alert-header">
                <div class="icon-circle"><i class="fa-solid fa-user-minus"></i></div>
            </div>
            <div class="alert-body">
                <h1>ยืนยันการลบผู้รับผิดชอบ</h1>
                <p data-room-member-remove-message>โปรดยืนยันการยกเลิกการแต่งตั้งผู้รับผิดชอบห้องนี้</p>
                <div class="alert-actions">
                    <button type="button" class="btn-close-alert" data-room-member-remove-confirm="true">ยืนยันลบ</button>
                    <button type="button" class="btn-close-alert btn-cancel-alert" data-room-member-remove-cancel="true">ยกเลิก</button>
                </div>
            </div>
        </div>
    </div>

    <div id="roomDeleteConfirmModal" class="alert-overlay hidden">
        <div class="alert-box danger room-member-confirm-alert">
            <div class="alert-header">
                <div class="icon-circle"><i class="fa-solid fa-trash-can"></i></div>
            </div>
            <div class="alert-body">
                <h1>ยืนยันการลบห้อง</h1>
                <p data-room-delete-message>โปรดยืนยันการลบห้อง/สถานที่ออกจากระบบ</p>
                <div class="alert-actions">
                    <button type="button" class="btn-close-alert" data-room-delete-confirm="true">ยืนยันลบ</button>
                    <button type="button" class="btn-close-alert btn-cancel-alert" data-room-delete-cancel="true">ยกเลิก</button>
                </div>
            </div>
        </div>
    </div>

    <?php require_once __DIR__ . '/public/components/x-scripts.php'; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const openButtons = document.querySelectorAll('[data-room-modal-open]');
            const closeButtons = document.querySelectorAll('[data-room-modal-close]');
            const memberModal = document.getElementById('roomMemberModal');
            const editModal = document.getElementById('roomEditModal');
            const memberConfirmModal = document.getElementById('roomMemberConfirmModal');
            const memberConfirmMessage = memberConfirmModal ? memberConfirmModal.querySelector('[data-room-member-confirm-message]') : null;
            const memberConfirmButton = memberConfirmModal ? memberConfirmModal.querySelector('[data-room-member-confirm="true"]') : null;
            const memberCancelButton = memberConfirmModal ? memberConfirmModal.querySelector('[data-room-member-cancel="true"]') : null;
            const memberRemoveConfirmModal = document.getElementById('roomMemberRemoveConfirmModal');
            const memberRemoveMessage = memberRemoveConfirmModal ? memberRemoveConfirmModal.querySelector('[data-room-member-remove-message]') : null;
            const memberRemoveConfirmButton = memberRemoveConfirmModal ? memberRemoveConfirmModal.querySelector('[data-room-member-remove-confirm="true"]') : null;
            const memberRemoveCancelButton = memberRemoveConfirmModal ? memberRemoveConfirmModal.querySelector('[data-room-member-remove-cancel="true"]') : null;
            const roomDeleteConfirmModal = document.getElementById('roomDeleteConfirmModal');
            const roomDeleteMessage = roomDeleteConfirmModal ? roomDeleteConfirmModal.querySelector('[data-room-delete-message]') : null;
            const roomDeleteConfirmButton = roomDeleteConfirmModal ? roomDeleteConfirmModal.querySelector('[data-room-delete-confirm="true"]') : null;
            const roomDeleteCancelButton = roomDeleteConfirmModal ? roomDeleteConfirmModal.querySelector('[data-room-delete-cancel="true"]') : null;
            const memberSearchForm = memberModal ? memberModal.querySelector('[data-member-search-form]') : null;
            const memberSearchInput = memberModal ? memberModal.querySelector('[data-member-search]') : null;
            let memberCards = memberModal ? Array.from(memberModal.querySelectorAll('[data-member-card]')) : [];
            const memberEmptyState = memberModal ? memberModal.querySelector('[data-member-empty]') : null;
            const memberCount = memberModal ? memberModal.querySelector('[data-member-count]') : null;
            const staffCountEl = document.querySelector('.room-admin-member-count');
            const editButtons = Array.from(document.querySelectorAll('[data-room-edit]'));
            const editIdInput = editModal ? editModal.querySelector('[data-room-edit-id]') : null;
            const editNameInput = editModal ? editModal.querySelector('[data-room-edit-name]') : null;
            const editStatusSelect = editModal ? editModal.querySelector('[data-room-edit-status]') : null;
            const editNoteInput = editModal ? editModal.querySelector('[data-room-edit-note]') : null;
            let pendingMemberForm = null;
            let pendingRemoveForm = null;
            let pendingMemberCard = null;
            let pendingRoomDeleteForm = null;

            const openMemberConfirm = function (form) {
                pendingMemberForm = form;
                const card = form.closest('.room-admin-member-card');
                pendingMemberCard = card;
                const nameEl = card ? card.querySelector('.room-admin-member-name') : null;
                const name = nameEl && nameEl.textContent.trim() !== '' ? nameEl.textContent.trim() : 'บุคลากรคนนี้';
                if (memberConfirmMessage) {
                    memberConfirmMessage.textContent = 'โปรดยืนยันการแต่งตั้ง ' + name + ' เป็นผู้รับผิดชอบห้องนี้';
                }
                if (memberConfirmModal) {
                    memberConfirmModal.classList.remove('hidden');
                }
            };

            const openMemberRemoveConfirm = function (form) {
                pendingRemoveForm = form;
                const row = form.closest('tr');
                const nameEl = row ? row.querySelector('strong') : null;
                const name = nameEl && nameEl.textContent.trim() !== '' ? nameEl.textContent.trim() : 'บุคลากรคนนี้';
                if (memberRemoveMessage) {
                    memberRemoveMessage.textContent = 'โปรดยืนยันการยกเลิกการแต่งตั้ง ' + name + ' จากผู้รับผิดชอบห้องนี้';
                }
                if (memberRemoveConfirmModal) {
                    memberRemoveConfirmModal.classList.remove('hidden');
                }
            };

            const openRoomDeleteConfirm = function (form) {
                pendingRoomDeleteForm = form;
                const row = form.closest('[data-room-row]');
                const roomName = row ? row.dataset.roomName || '' : '';
                const label = roomName.trim() !== '' ? roomName.trim() : 'ห้อง/สถานที่นี้';
                if (roomDeleteMessage) {
                    roomDeleteMessage.textContent = 'โปรดยืนยันการลบ ' + label + ' ออกจากระบบ';
                }
                if (roomDeleteConfirmModal) {
                    roomDeleteConfirmModal.classList.remove('hidden');
                }
            };

            const buildAlertHtml = function (type, title, message) {
                const iconMap = {
                    success: 'fa-check',
                    warning: 'fa-triangle-exclamation',
                    danger: 'fa-xmark',
                };
                const alertType = iconMap[type] ? type : 'danger';
                const icon = iconMap[alertType] || 'fa-xmark';
                return (
                    '<div class="alert-overlay" data-alert-redirect="" data-alert-delay="0">' +
                    '<div class="alert-box ' + alertType + '">' +
                    '<div class="alert-header"><div class="icon-circle"><i class="fa-solid ' + icon + '"></i></div></div>' +
                    '<div class="alert-body">' +
                    '<h1>' + title + '</h1>' +
                    (message ? '<p>' + message + '</p>' : '') +
                    '<button type="button" class="btn-close-alert" data-alert-close="true">ยืนยัน</button>' +
                    '</div></div></div>'
                );
            };

            const showRoomAlert = function (type, title, message) {
                const temp = document.createElement('div');
                temp.innerHTML = buildAlertHtml(type, title, message);
                const overlay = temp.querySelector('.alert-overlay');
                if (!overlay) return;
                document.querySelectorAll('.alert-overlay').forEach(function (existing) {
                    if (
                        existing.id !== 'roomMemberConfirmModal' &&
                        existing.id !== 'roomMemberRemoveConfirmModal' &&
                        existing.id !== 'roomDeleteConfirmModal'
                    ) {
                        existing.remove();
                    }
                });
                document.body.appendChild(overlay);
                overlay.querySelectorAll('[data-alert-close="true"]').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        overlay.remove();
                    });
                });
            };

            const updateStaffCount = function (delta) {
                if (!staffCountEl) return;
                const match = staffCountEl.textContent.match(/\d+/);
                const current = match ? parseInt(match[0], 10) : 0;
                const next = Math.max(0, current + delta);
                staffCountEl.textContent = 'ทั้งหมด ' + next + ' คน';
            };

            const appendStaffRow = function (card) {
                const tableBody = document.querySelector('.room-admin-member-table tbody');
                if (!tableBody || !card) return;
                const name = card.dataset.memberName || (card.querySelector('.room-admin-member-name') || {}).textContent?.trim() || 'ไม่ระบุชื่อ';
                const positionLabel = card.dataset.memberPosition || (card.querySelector('.room-admin-member-role') || {}).textContent?.trim() || 'ไม่ระบุตำแหน่ง';
                const departmentLabel = card.dataset.memberDepartment || (card.querySelector('.room-admin-member-tag') || {}).textContent?.trim() || '';
                const roleLabel = card.dataset.memberRole || '';
                const pid = card.dataset.memberPid || '';
                const csrf = <?= json_encode($_SESSION['csrf_token'] ?? '', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

                const row = document.createElement('tr');
                row.innerHTML =
                    '<td><strong>' + name + '</strong></td>' +
                    '<td><div class="room-admin-member-position">' + positionLabel + '</div>' +
                    (departmentLabel ? '<div class="room-admin-member-subtext">' + departmentLabel + '</div>' : '') + '</td>' +
                    '<td><span class="room-admin-member-tag">' + (roleLabel || 'ไม่ระบุหน้าที่') + '</span></td>' +
                    '<td><span class="member-status-pill">อยู่ในทีมแล้ว</span></td>' +
                    '<td><div class="booking-action-group">' +
                    '<form class="booking-action-form" data-member-remove-form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') ?>">' +
                    '<input type="hidden" name="csrf_token" value="' + csrf + '">' +
                    '<input type="hidden" name="member_action" value="remove">' +
                    '<input type="hidden" name="member_pid" value="' + pid + '">' +
                    '<button type="submit" class="booking-action-btn danger">ลบ</button>' +
                    '</form>' +
                    '</div></td>';

                tableBody.appendChild(row);
            };

            const submitMemberAjax = function (form) {
                const formData = new FormData(form);
                formData.append('ajax', '1');
                return fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                }).then(function (response) {
                    if (!response.ok) {
                        throw new Error('NETWORK_ERROR');
                    }
                    return response.json();
                });
            };

            const updateMemberSearch = function () {
                if (!memberModal || !memberSearchInput) return;
                const query = memberSearchInput.value.trim().toLowerCase();
                let visibleCount = 0;

                memberCards.forEach(function (card) {
                    const haystack = (card.dataset.memberSearch || '').toLowerCase();
                    const isMatch = query === '' || haystack.includes(query);
                    card.style.display = isMatch ? '' : 'none';
                    if (isMatch) visibleCount += 1;
                });

                if (memberCount) {
                    memberCount.textContent = query === '' ? 'ทั้งหมด ' + visibleCount + ' คน' : 'พบ ' + visibleCount + ' คน';
                }

                if (memberEmptyState) {
                    memberEmptyState.classList.toggle('hidden', visibleCount !== 0);
                }
            };

            openButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    const targetId = button.getAttribute('data-room-modal-open');
                    if (!targetId) return;
                    const targetModal = document.getElementById(targetId);
                    if (targetModal) {
                        targetModal.classList.remove('hidden');
                        if (targetId === 'roomMemberModal' && memberSearchInput) {
                            memberSearchInput.value = '';
                            updateMemberSearch();
                            memberSearchInput.focus();
                        }
                    }
                });
            });

            editButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    const row = button.closest('[data-room-row]');
                    if (!row || !editModal) return;
                    const roomId = row.dataset.roomId || '';
                    const roomName = row.dataset.roomName || '';
                    const roomStatus = row.dataset.roomStatusLabel || '';
                    const roomNote = row.dataset.roomNote || '';

                    if (editIdInput) editIdInput.value = roomId;
                    if (editNameInput) editNameInput.value = roomName;
                    if (editStatusSelect) editStatusSelect.value = roomStatus;
                    if (editNoteInput) editNoteInput.value = roomNote;

                    editModal.classList.remove('hidden');
                });
            });

            const initialModal = <?= json_encode($room_management_open_modal, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
            if (initialModal) {
                const targetModal = document.getElementById(initialModal);
                if (targetModal) {
                    targetModal.classList.remove('hidden');
                    if (initialModal === 'roomMemberModal' && memberSearchInput) {
                        updateMemberSearch();
                        memberSearchInput.focus();
                    }
                }
            }

            closeButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    const targetId = button.getAttribute('data-room-modal-close');
                    if (!targetId) return;
                    const targetModal = document.getElementById(targetId);
                    if (targetModal) {
                        targetModal.classList.add('hidden');
                    }
                });
            });

            document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
                overlay.addEventListener('click', function (event) {
                    if (event.target === overlay) {
                        overlay.classList.add('hidden');
                    }
                });
            });

            if (memberConfirmModal) {
                memberConfirmModal.addEventListener('click', function (event) {
                    if (event.target === memberConfirmModal) {
                        memberConfirmModal.classList.add('hidden');
                        pendingMemberForm = null;
                        pendingMemberCard = null;
                    }
                });
            }

            if (memberRemoveConfirmModal) {
                memberRemoveConfirmModal.addEventListener('click', function (event) {
                    if (event.target === memberRemoveConfirmModal) {
                        memberRemoveConfirmModal.classList.add('hidden');
                        pendingRemoveForm = null;
                    }
                });
            }

            if (roomDeleteConfirmModal) {
                roomDeleteConfirmModal.addEventListener('click', function (event) {
                    if (event.target === roomDeleteConfirmModal) {
                        roomDeleteConfirmModal.classList.add('hidden');
                        pendingRoomDeleteForm = null;
                    }
                });
            }

            document.addEventListener('submit', function (event) {
                const form = event.target;
                if (!(form instanceof HTMLFormElement)) return;
                if (form.classList.contains('room-admin-member-action')) {
                    event.preventDefault();
                    openMemberConfirm(form);
                }
                if (form.matches('[data-member-remove-form]')) {
                    event.preventDefault();
                    openMemberRemoveConfirm(form);
                }
                if (form.matches('[data-room-delete-form]')) {
                    event.preventDefault();
                    openRoomDeleteConfirm(form);
                }
            });

            document.addEventListener('click', function (event) {
                const deleteButton = event.target.closest('[data-room-delete-btn]');
                if (!deleteButton) return;
                event.preventDefault();
                const form = deleteButton.closest('[data-room-delete-form]');
                if (form) {
                    openRoomDeleteConfirm(form);
                }
            });

            if (memberConfirmButton) {
                memberConfirmButton.addEventListener('click', function () {
                    if (!pendingMemberForm) return;
                    memberConfirmButton.disabled = true;
                    submitMemberAjax(pendingMemberForm)
                        .then(function (data) {
                            const type = data && data.type ? data.type : 'danger';
                            const title = data && data.title ? data.title : 'ระบบขัดข้อง';
                            const message = data && data.message ? data.message : 'ไม่สามารถเพิ่มสมาชิกได้ในขณะนี้';
                            showRoomAlert(type, title, message);

                            if (data && data.ok && pendingMemberCard) {
                                appendStaffRow(pendingMemberCard);
                                pendingMemberCard.remove();
                                memberCards = memberCards.filter(function (card) {
                                    return card !== pendingMemberCard;
                                });
                                updateStaffCount(1);
                                updateMemberSearch();
                            }
                        })
                        .catch(function () {
                            showRoomAlert('danger', 'ระบบขัดข้อง', 'ไม่สามารถเพิ่มสมาชิกได้ในขณะนี้');
                        })
                        .finally(function () {
                            if (memberConfirmModal) {
                                memberConfirmModal.classList.add('hidden');
                            }
                            pendingMemberForm = null;
                            pendingMemberCard = null;
                            memberConfirmButton.disabled = false;
                        });
                });
            }

            if (memberCancelButton) {
                memberCancelButton.addEventListener('click', function () {
                    if (memberConfirmModal) {
                        memberConfirmModal.classList.add('hidden');
                    }
                    pendingMemberForm = null;
                    pendingMemberCard = null;
                });
            }

            if (memberRemoveConfirmButton) {
                memberRemoveConfirmButton.addEventListener('click', function () {
                    if (pendingRemoveForm) {
                        pendingRemoveForm.submit();
                    }
                });
            }

            if (memberRemoveCancelButton) {
                memberRemoveCancelButton.addEventListener('click', function () {
                    if (memberRemoveConfirmModal) {
                        memberRemoveConfirmModal.classList.add('hidden');
                    }
                    pendingRemoveForm = null;
                });
            }

            if (roomDeleteConfirmButton) {
                roomDeleteConfirmButton.addEventListener('click', function () {
                    if (pendingRoomDeleteForm) {
                        pendingRoomDeleteForm.submit();
                    }
                });
            }

            if (roomDeleteCancelButton) {
                roomDeleteCancelButton.addEventListener('click', function () {
                    if (roomDeleteConfirmModal) {
                        roomDeleteConfirmModal.classList.add('hidden');
                    }
                    pendingRoomDeleteForm = null;
                });
            }

            if (memberSearchForm && memberSearchInput) {
                memberSearchForm.addEventListener('submit', function (event) {
                    event.preventDefault();
                    updateMemberSearch();
                });

                memberSearchInput.addEventListener('input', updateMemberSearch);
            }

            updateMemberSearch();

            const roomSearchInput = document.querySelector('[data-room-search-input]');
            const roomStatusFilter = document.querySelector('[data-room-status-filter]');
            const roomRows = Array.from(document.querySelectorAll('[data-room-row]'));
            const roomEmpty = document.querySelector('[data-room-empty]');

            const applyRoomFilters = function () {
                if (!roomRows.length) return;
                const query = roomSearchInput ? roomSearchInput.value.trim().toLowerCase() : '';
                const status = roomStatusFilter ? roomStatusFilter.value : 'all';
                let visibleCount = 0;

                roomRows.forEach(function (row) {
                    const haystack = (row.dataset.roomSearch || '').toLowerCase();
                    const rowStatus = row.dataset.roomStatus || '';
                    const matchQuery = query === '' || haystack.includes(query);
                    const matchStatus = status === 'all' || rowStatus === status;
                    const isVisible = matchQuery && matchStatus;
                    row.style.display = isVisible ? '' : 'none';
                    if (isVisible) visibleCount += 1;
                });

                if (roomEmpty) {
                    roomEmpty.classList.toggle('hidden', visibleCount !== 0);
                }
            };

            if (roomSearchInput) {
                roomSearchInput.addEventListener('input', applyRoomFilters);
                roomSearchInput.addEventListener('keydown', function (event) {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        applyRoomFilters();
                    }
                });
            }

            if (roomStatusFilter) {
                roomStatusFilter.addEventListener('change', applyRoomFilters);
            }

            applyRoomFilters();
        });
    </script>
</body>

</html>

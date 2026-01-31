<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../auth/csrf.php';

$room_status_classes = (array) ($room_status_classes ?? []);
$room_status_options = (array) ($room_status_options ?? []);
$room_management_rooms = (array) ($room_management_rooms ?? []);
$room_staff_members = (array) ($room_staff_members ?? []);
$room_candidate_members = (array) ($room_candidate_members ?? []);
$room_staff_count = (int) ($room_staff_count ?? count($room_staff_members));
$room_candidate_count = (int) ($room_candidate_count ?? count($room_candidate_members));
$room_management_alert = $room_management_alert ?? null;
$room_management_open_modal = (string) ($room_management_open_modal ?? '');

$alert = $room_management_alert;

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

ob_start();
?>
<div class="content-header">
    <h1>ยินดีต้อนรับ</h1>
    <p>จัดการสถานที่/ห้อง</p>
</div>

<div class="content-area room-admin-page" data-room-management data-room-open-modal="<?= h($room_management_open_modal) ?>">
    <section class="booking-card booking-list-card room-admin-card">
        <div class="booking-card-header">
            <div class="booking-card-title-group">
                <h2 class="booking-card-title">รายการสถานที่/ห้อง</h2>
                <p class="booking-card-subtitle">ปรับชื่อห้อง เปลี่ยนสถานะ และกำหนดผู้รับผิดชอบตามหน่วยงาน</p>
            </div>
            <div class="room-admin-actions" data-room-filter>
                <div class="room-admin-search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input class="form-input" type="search" placeholder="ค้นหาชื่อห้องหรือสถานที่" autocomplete="off"
                        data-room-search-input>
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
                            <tr data-room-row data-room-id="<?= h((string) $room_id) ?>" data-room-status="<?= h($status_class) ?>"
                                data-room-name="<?= h($room_name) ?>" data-room-status-label="<?= h($status_label) ?>"
                                data-room-note="<?= h($room_note) ?>" data-room-search="<?= h($row_search) ?>">
                                <td>
                                    <div class="room-admin-room-name">
                                        <?= h($room_name !== '' ? $room_name : 'ไม่ระบุชื่อห้อง') ?>
                                    </div>
                                    <div class="room-admin-room-meta">ใช้สำหรับการประชุมและกิจกรรมภายในโรงเรียน</div>
                                </td>
                                <td>
                                    <span class="room-status-pill <?= h($status_class) ?>">
                                        <?= h($status_label) ?>
                                    </span>
                                </td>
                                <td><?= h($room_note !== '' ? $room_note : '-') ?></td>
                                <td><?= h($updated_label) ?></td>
                                <td class="room-admin-actions-cell">
                                    <div class="booking-action-group">
                                        <button type="button" class="booking-action-btn secondary" data-room-edit="true">แก้ไข</button>
                                        <form method="POST" action="<?= h($_SERVER['PHP_SELF'] ?? 'room-management.php') ?>"
                                            data-room-delete-form>
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="room_action" value="delete">
                                            <input type="hidden" name="room_id" value="<?= h((string) $room_id) ?>">
                                            <button type="button" class="booking-action-btn danger" data-room-delete-btn>ลบ</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <tr class="booking-empty hidden" data-room-empty>
                        <td colspan="5">ไม่พบรายการที่ค้นหา</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <section class="booking-card booking-list-card room-admin-card">
        <div class="booking-card-header">
            <div class="booking-card-title-group">
                <h2 class="booking-card-title">ทีมผู้ดูแลสถานที่</h2>
                <p class="booking-card-subtitle">รายชื่อผู้รับผิดชอบการจองและอนุมัติห้องในระบบ</p>
            </div>
            <div class="room-admin-member-count">ทั้งหมด <?= h((string) $room_staff_count) ?> คน</div>
            <button type="button" class="btn-outline" data-room-modal-open="roomMemberModal">เพิ่มสมาชิก</button>
        </div>

        <div class="table-responsive">
            <table class="custom-table booking-table room-admin-member-table">
                <thead>
                    <tr>
                        <th>ชื่อ-สกุล</th>
                        <th>ตำแหน่ง</th>
                        <th>บทบาท</th>
                        <th>สถานะ</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($room_staff_members)) : ?>
                        <tr>
                            <td colspan="5" class="booking-empty">ยังไม่มีผู้รับผิดชอบห้อง</td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($room_staff_members as $member) : ?>
                            <?php
                            $member_pid = (string) ($member['pID'] ?? '');
                            $member_name = trim((string) ($member['name'] ?? ''));
                            $member_position = trim((string) ($member['position_name'] ?? ''));
                            $member_role = trim((string) ($member['role_name'] ?? ''));
                            $member_department = trim((string) ($member['department_name'] ?? ''));
                            ?>
                            <tr>
                                <td><strong><?= h($member_name !== '' ? $member_name : 'ไม่ระบุชื่อ') ?></strong></td>
                                <td>
                                    <div class="room-admin-member-position"><?= h($member_position !== '' ? $member_position : '-') ?></div>
                                    <?php if ($member_department !== '') : ?>
                                        <div class="room-admin-member-subtext"><?= h($member_department) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><span class="room-admin-member-tag"><?= h($member_role !== '' ? $member_role : '-') ?></span></td>
                                <td><span class="member-status-pill">อยู่ในทีมแล้ว</span></td>
                                <td>
                                    <div class="booking-action-group">
                                        <form class="booking-action-form" data-member-remove-form method="POST"
                                            action="<?= h($_SERVER['PHP_SELF'] ?? 'room-management.php') ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="member_action" value="remove">
                                            <input type="hidden" name="member_pid" value="<?= h($member_pid) ?>">
                                            <button type="submit" class="booking-action-btn danger">ลบ</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<div id="roomAddModal" class="modal-overlay hidden">
    <div class="modal-content room-admin-modal">
        <header class="modal-header">
            <div class="modal-title">
                <i class="fa-solid fa-plus"></i>
                <span>เพิ่มห้องใหม่</span>
            </div>
            <div class="close-modal-btn" data-room-modal-close="roomAddModal">
                <i class="fa-solid fa-xmark"></i>
            </div>
        </header>
        <div class="modal-body room-admin-modal-body">
            <form class="room-admin-form" method="POST" action="<?= h($_SERVER['PHP_SELF'] ?? 'room-management.php') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="room_action" value="add">
                <div class="form-group full">
                    <label class="form-label">ชื่อห้อง/สถานที่</label>
                    <input class="form-input" type="text" name="room_name" maxlength="150" required
                        placeholder="เช่น ห้องประชุมใหญ่">
                </div>
                <div class="form-group full">
                    <label class="form-label">สถานะ</label>
                    <select class="form-input" name="room_status" required>
                        <?php foreach ($room_status_options as $status_option) : ?>
                            <option value="<?= h((string) $status_option) ?>"><?= h((string) $status_option) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group full">
                    <label class="form-label">หมายเหตุ</label>
                    <textarea class="form-input" name="room_note" rows="4" placeholder="ระบุรายละเอียดเพิ่มเติม"></textarea>
                </div>
                <div class="room-admin-modal-actions">
                    <button type="button" class="btn-outline" data-room-modal-close="roomAddModal">ยกเลิก</button>
                    <button type="submit" class="btn-confirm">บันทึกห้องใหม่</button>
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
            <form class="room-admin-form" method="POST" action="<?= h($_SERVER['PHP_SELF'] ?? 'room-management.php') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="room_action" value="edit">
                <input type="hidden" name="room_id" data-room-edit-id>
                <div class="form-group full">
                    <label class="form-label">ชื่อห้อง/สถานที่</label>
                    <input class="form-input" type="text" name="room_name" maxlength="150" required
                        data-room-edit-name>
                </div>
                <div class="form-group full">
                    <label class="form-label">สถานะ</label>
                    <select class="form-input" name="room_status" required data-room-edit-status>
                        <?php foreach ($room_status_options as $status_option) : ?>
                            <option value="<?= h((string) $status_option) ?>"><?= h((string) $status_option) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group full">
                    <label class="form-label">หมายเหตุ</label>
                    <textarea class="form-input" name="room_note" rows="4" data-room-edit-note></textarea>
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
    <div class="modal-content room-admin-modal room-admin-member-modal">
        <header class="modal-header">
            <div class="modal-title">
                <i class="fa-solid fa-user-plus"></i>
                <span>เพิ่มสมาชิกทีมผู้ดูแล</span>
            </div>
            <div class="close-modal-btn" data-room-modal-close="roomMemberModal">
                <i class="fa-solid fa-xmark"></i>
            </div>
        </header>
        <div class="modal-body room-admin-modal-body">
            <form class="room-admin-search-form" data-member-search-form>
                <div class="room-admin-search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input class="form-input" type="search" placeholder="ค้นหาบุคลากร" autocomplete="off" data-member-search>
                </div>
                <span class="room-admin-member-count" data-member-count>ทั้งหมด <?= h((string) $room_candidate_count) ?> คน</span>
            </form>

            <div class="room-admin-member-grid">
                <?php if (empty($room_candidate_members)) : ?>
                    <div class="booking-empty" data-member-empty>ไม่พบบุคลากร</div>
                <?php else : ?>
                    <?php foreach ($room_candidate_members as $member) : ?>
                        <?php
                        $candidate_pid = (string) ($member['pID'] ?? '');
                        $candidate_name = trim((string) ($member['name'] ?? ''));
                        $candidate_position = trim((string) ($member['position_name'] ?? ''));
                        $candidate_role = trim((string) ($member['role_name'] ?? ''));
                        $candidate_department = trim((string) ($member['department_name'] ?? ''));
                        $candidate_phone = trim((string) ($member['telephone'] ?? ''));
                        $search_blob = trim(implode(' ', [
                            $candidate_name,
                            $candidate_position,
                            $candidate_role,
                            $candidate_department,
                            $candidate_phone,
                        ]));
                        ?>
                        <div class="room-admin-member-card" data-member-card
                            data-member-pid="<?= h($candidate_pid) ?>" data-member-name="<?= h($candidate_name) ?>"
                            data-member-position="<?= h($candidate_position) ?>" data-member-role="<?= h($candidate_role) ?>"
                            data-member-department="<?= h($candidate_department) ?>" data-member-search="<?= h($search_blob) ?>">
                            <div class="room-admin-member-info">
                                <div class="room-admin-member-name"><?= h($candidate_name !== '' ? $candidate_name : 'ไม่ระบุชื่อ') ?></div>
                                <div class="room-admin-member-role"><?= h($candidate_position !== '' ? $candidate_position : '-') ?></div>
                                <div class="room-admin-member-tag"><?= h($candidate_department !== '' ? $candidate_department : '-') ?></div>
                            </div>
                            <div class="room-admin-member-actions">
                                <form method="POST" action="<?= h($_SERVER['PHP_SELF'] ?? 'room-management.php') ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="member_action" value="add">
                                    <input type="hidden" name="member_pid" value="<?= h($candidate_pid) ?>">
                                    <button type="button" class="btn-outline" data-member-add-btn>เพิ่ม</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div class="booking-empty hidden" data-member-empty>ไม่พบบุคลากร</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div id="roomMemberConfirmModal" class="alert-overlay hidden">
    <div class="alert-box">
        <div class="alert-header">
            <div class="icon-circle"><i class="fa-solid fa-user-plus"></i></div>
        </div>
        <div class="alert-body">
            <h1>ยืนยันการเพิ่มสมาชิก</h1>
            <p data-room-member-confirm-message>โปรดยืนยันการเพิ่มสมาชิกใหม่</p>
            <div class="alert-actions">
                <button type="button" class="btn-close-alert btn-cancel-alert" data-room-member-cancel="true">ยกเลิก</button>
                <button type="button" class="btn-close-alert" data-room-member-confirm="true">ยืนยัน</button>
            </div>
        </div>
    </div>
</div>

<div id="roomMemberRemoveConfirmModal" class="alert-overlay hidden">
    <div class="alert-box danger">
        <div class="alert-header">
            <div class="icon-circle"><i class="fa-solid fa-user-minus"></i></div>
        </div>
        <div class="alert-body">
            <h1>ยืนยันการลบสมาชิก</h1>
            <p data-room-member-remove-message>โปรดยืนยันการลบสมาชิกออกจากทีม</p>
            <div class="alert-actions">
                <button type="button" class="btn-close-alert btn-cancel-alert" data-room-member-remove-cancel="true">ยกเลิก</button>
                <button type="button" class="btn-close-alert" data-room-member-remove-confirm="true">ยืนยัน</button>
            </div>
        </div>
    </div>
</div>

<div id="roomDeleteConfirmModal" class="alert-overlay hidden">
    <div class="alert-box danger">
        <div class="alert-header">
            <div class="icon-circle"><i class="fa-solid fa-trash"></i></div>
        </div>
        <div class="alert-body">
            <h1>ยืนยันการลบห้อง</h1>
            <p data-room-delete-message>โปรดยืนยันการลบห้องนี้ออกจากระบบ</p>
            <div class="alert-actions">
                <button type="button" class="btn-close-alert btn-cancel-alert" data-room-delete-cancel="true">ยกเลิก</button>
                <button type="button" class="btn-close-alert" data-room-delete-confirm="true">ยืนยัน</button>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';

<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../auth/csrf.php';

$vehicles = (array) ($vehicles ?? []);
$status_classes = (array) ($status_classes ?? []);
$status_options = (array) ($status_options ?? []);
$open_modal = (string) ($open_modal ?? '');
$form_values = (array) ($form_values ?? []);
$edit_values = (array) ($edit_values ?? []);
$vehicle_staff_members = (array) ($vehicle_staff_members ?? []);
$vehicle_candidate_members = (array) ($vehicle_candidate_members ?? []);
$vehicle_staff_count = (int) ($vehicle_staff_count ?? count($vehicle_staff_members));
$vehicle_candidate_count = (int) ($vehicle_candidate_count ?? count($vehicle_candidate_members));

$form_values = array_merge([
    'vehicleType' => '',
    'vehicleBrand' => '',
    'vehicleModel' => '',
    'vehiclePlate' => '',
    'vehicleColor' => '',
    'vehicleCapacity' => 4,
    'vehicleStatus' => $status_options[0] ?? 'พร้อมใช้งาน',
], $form_values);

$edit_values = array_merge([
    'vehicleID' => 0,
    'vehicleType' => '',
    'vehicleBrand' => '',
    'vehicleModel' => '',
    'vehiclePlate' => '',
    'vehicleColor' => '',
    'vehicleCapacity' => 4,
    'vehicleStatus' => $status_options[0] ?? 'พร้อมใช้งาน',
], $edit_values);

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

$format_thai_date = static function (string $date) use ($thai_months): string {
    $date = trim($date);
    if ($date === '' || strpos($date, '0000-00-00') === 0) {
        return '-';
    }

    $date_obj = DateTime::createFromFormat('Y-m-d', $date);
    if ($date_obj === false) {
        return $date;
    }

    $day = (int) $date_obj->format('j');
    $month = (int) $date_obj->format('n');
    $year = (int) $date_obj->format('Y') + 543;
    $month_label = $thai_months[$month] ?? '';

    return trim($day . ' ' . $month_label . ' ' . $year);
};

ob_start();
?>
<div class="content-header">
    <h1>ยินดีต้อนรับ</h1>
    <p>จัดการยานพาหนะ</p>
</div>

<div class="content-area room-admin-page" data-vehicle-management data-vehicle-open-modal="<?= h($open_modal) ?>">
    <section class="booking-card booking-list-card room-admin-card">
        <div class="booking-card-header">
            <div class="booking-card-title-group">
                <h2 class="booking-card-title">รายการยานพาหนะ</h2>
            </div>
            <div class="room-admin-actions" data-vehicle-filter>
                <div class="room-admin-search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input class="form-input" type="search" placeholder="ค้นหาทะเบียนรถ ประเภทรถ หรือยี่ห้อ"
                        autocomplete="off" data-vehicle-search-input>
                </div>
                <div class="room-admin-filter">
                    <select class="form-input" data-vehicle-status-filter>
                        <option value="all">ทุกสถานะ</option>
                        <?php foreach ($status_options as $status_option) : ?>
                            <option value="<?= h((string) ($status_classes[$status_option] ?? '')) ?>">
                                <?= h((string) $status_option) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="button" class="btn-confirm" data-vehicle-modal-open="vehicleAddModal">เพิ่มยานพาหนะใหม่</button>
            </div>
        </div>

        <div class="room-admin-legend">
            <?php foreach ($status_options as $status_option) : ?>
                <?php $status_class = $status_classes[$status_option] ?? 'available'; ?>
                <span class="room-status-pill <?= h($status_class) ?>"><?= h($status_option) ?></span>
            <?php endforeach; ?>
        </div>

        <div class="table-responsive">
            <table class="custom-table booking-table room-admin-table vehicle-admin-table">
                <thead>
                    <tr>
                        <th>ทะเบียนรถ</th>
                        <th>ประเภทยานพาหนะ</th>
                        <th>สถานะ</th>
                        <th>ความจุ</th>
                        <th>อัปเดตล่าสุด</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($vehicles)) : ?>
                        <tr class="booking-empty">
                            <td colspan="6">ยังไม่มีข้อมูลยานพาหนะ</td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($vehicles as $vehicle) : ?>
                            <?php
                            $vehicle_id = (int) ($vehicle['vehicleID'] ?? 0);
                            $vehicle_type = trim((string) ($vehicle['vehicleType'] ?? ''));
                            $vehicle_plate = trim((string) ($vehicle['vehiclePlate'] ?? ''));
                            $vehicle_brand = trim((string) ($vehicle['vehicleBrand'] ?? ''));
                            $vehicle_model = trim((string) ($vehicle['vehicleModel'] ?? ''));
                            $vehicle_color = trim((string) ($vehicle['vehicleColor'] ?? ''));
                            $vehicle_capacity = (int) ($vehicle['vehicleCapacity'] ?? 0);
                            $vehicle_status = trim((string) ($vehicle['vehicleStatus'] ?? ''));
                            if ($vehicle_status === '') {
                                $vehicle_status = $status_options[0] ?? 'พร้อมใช้งาน';
                            }
                            $status_class = $status_classes[$vehicle_status] ?? 'available';
                            $created_at = (string) ($vehicle['createdAt'] ?? '');
                            $updated_at = (string) ($vehicle['updatedAt'] ?? '');
                            $display_updated = $updated_at !== '' && strpos($updated_at, '0000-00-00') !== 0
                                ? $updated_at
                                : $created_at;
                            $updated_date = $display_updated !== '' ? substr($display_updated, 0, 10) : '';
                            $updated_time = $display_updated !== '' && strlen($display_updated) >= 16 ? substr($display_updated, 11, 5) : '';
                            $updated_date_label = $updated_date !== '' ? $format_thai_date($updated_date) : '-';
                            $updated_time_label = $updated_time !== '' ? $updated_time : '-';
                            $vehicle_meta = trim(implode(' ', array_filter([$vehicle_brand, $vehicle_model, $vehicle_color])));
                            $search_text = trim(implode(' ', [
                                $vehicle_plate,
                                $vehicle_type,
                                $vehicle_brand,
                                $vehicle_model,
                                $vehicle_color,
                                $vehicle_status,
                                (string) $vehicle_capacity,
                            ]));
                            ?>
                            <tr data-vehicle-row
                                data-vehicle-id="<?= h((string) $vehicle_id) ?>"
                                data-vehicle-status="<?= h($status_class) ?>"
                                data-vehicle-plate="<?= h($vehicle_plate) ?>"
                                data-vehicle-type="<?= h($vehicle_type) ?>"
                                data-vehicle-brand="<?= h($vehicle_brand) ?>"
                                data-vehicle-model="<?= h($vehicle_model) ?>"
                                data-vehicle-color="<?= h($vehicle_color) ?>"
                                data-vehicle-capacity="<?= h((string) $vehicle_capacity) ?>"
                                data-vehicle-status-label="<?= h($vehicle_status) ?>"
                                data-vehicle-search="<?= h($search_text) ?>">
                                <td>
                                    <div class="room-admin-room-name">
                                        <?= h($vehicle_plate !== '' ? $vehicle_plate : 'ไม่ระบุทะเบียน') ?>
                                    </div>
                                    <span class="detail-subtext">
                                        <?= h($vehicle_meta !== '' ? $vehicle_meta : 'ไม่ระบุรายละเอียด') ?>
                                    </span>
                                </td>
                                <td><?= h($vehicle_type !== '' ? $vehicle_type : '-') ?></td>
                                <td>
                                    <span class="room-status-pill <?= h($status_class) ?>">
                                        <?= h($vehicle_status) ?>
                                    </span>
                                </td>
                                <td><?= h($vehicle_capacity > 0 ? (string) $vehicle_capacity . ' ที่นั่ง' : '-') ?></td>
                                <td>
                                    <?= h($updated_date_label) ?><br>
                                    <span class="detail-subtext"><?= h($updated_time_label) ?></span>
                                </td>
                                <td class="room-admin-actions-cell">
                                    <div class="booking-action-group">
                                        <button type="button" class="booking-action-btn secondary" data-vehicle-edit="true">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                            <span class="tooltip">แก้ไขข้อมูล</span>
                                        </button>
                                        <form method="POST" action="<?= h($_SERVER['PHP_SELF'] ?? 'vehicle-management.php') ?>" data-vehicle-delete-form>
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="vehicle_action" value="delete">
                                            <input type="hidden" name="vehicle_id" value="<?= h((string) $vehicle_id) ?>">
                                            <button type="button" class="booking-action-btn danger" data-vehicle-delete-btn>
                                                <i class="fa-solid fa-trash"></i>
                                                <span class="tooltip danger">ลบข้อมูลการจอง</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <tr class="booking-empty hidden" data-vehicle-empty>
                        <td colspan="6">ไม่พบรายการที่ค้นหา</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <section class="booking-card booking-list-card room-admin-card">
        <div class="booking-card-header">
            <div class="booking-card-title-group">
                <h2 class="booking-card-title">ทีมผู้ดูแลยานพาหนะ</h2>
            </div>
            <div>
                <div class="room-admin-member-count">ทั้งหมด <?= h((string) $vehicle_staff_count) ?> คน</div>
                <button type="button" class="btn-outline" data-vehicle-modal-open="vehicleMemberModal">เพิ่มสมาชิก</button>
            </div>
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
                    <?php if (empty($vehicle_staff_members)) : ?>
                        <tr>
                            <td colspan="5" class="booking-empty">ยังไม่มีเจ้าหน้าที่ยานพาหนะ</td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($vehicle_staff_members as $member) : ?>
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
                                            action="<?= h($_SERVER['PHP_SELF'] ?? 'vehicle-management.php') ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="member_action" value="remove">
                                            <input type="hidden" name="member_pid" value="<?= h($member_pid) ?>">
                                            <button type="submit" class="booking-action-btn danger">
                                                <i class="fa-solid fa-trash"></i>
                                                <span class="tooltip danger">ลบข้อมูลการจอง</span>
                                            </button>
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

<div id="vehicleAddModal" class="modal-overlay hidden">
    <div class="modal-content room-admin-modal">
        <header class="modal-header">
            <div class="modal-title">
                <i class="fa-solid fa-plus"></i>
                <span>เพิ่มยานพาหนะใหม่</span>
            </div>
            <div class="close-modal-btn" data-vehicle-modal-close="vehicleAddModal">
                <i class="fa-solid fa-xmark"></i>
            </div>
        </header>
        <div class="modal-body room-admin-modal-body">
            <form class="room-admin-form" method="POST" action="<?= h($_SERVER['PHP_SELF'] ?? 'vehicle-management.php') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="vehicle_action" value="add">
                <div class="form-group full">
                    <label class="form-label">ประเภทยานพาหนะ</label>
                    <input class="form-input" type="text" name="vehicle_type" maxlength="50" required
                        placeholder="เช่น รถตู้, รถกระบะ" value="<?= h((string) $form_values['vehicleType']) ?>">
                </div>
                <div class="form-group full">
                    <label class="form-label">ทะเบียนรถ</label>
                    <input class="form-input" type="text" name="vehicle_plate" maxlength="50" required
                        placeholder="เช่น กข 1234" value="<?= h((string) $form_values['vehiclePlate']) ?>">
                </div>
                <div class="form-group full">
                    <label class="form-label">ยี่ห้อรถ</label>
                    <input class="form-input" type="text" name="vehicle_brand" maxlength="100"
                        placeholder="เช่น Toyota" value="<?= h((string) $form_values['vehicleBrand']) ?>">
                </div>
                <div class="form-group full">
                    <label class="form-label">รุ่นรถ</label>
                    <input class="form-input" type="text" name="vehicle_model" maxlength="100"
                        placeholder="เช่น Commuter" value="<?= h((string) $form_values['vehicleModel']) ?>">
                </div>
                <div class="form-group full">
                    <label class="form-label">สีรถ</label>
                    <input class="form-input" type="text" name="vehicle_color" maxlength="50"
                        placeholder="เช่น ขาว" value="<?= h((string) $form_values['vehicleColor']) ?>">
                </div>
                <div class="form-group full">
                    <label class="form-label">ความจุที่นั่ง</label>
                    <input class="form-input" type="number" name="vehicle_capacity" min="1" max="99"
                        value="<?= h((string) ($form_values['vehicleCapacity'] ?? 4)) ?>">
                </div>
                <div class="form-group full">
                    <label class="form-label">สถานะ</label>
                    <div class="custom-select-wrapper">
                        <div class="custom-select-trigger">
                            <p id="select-value">พร้อมใช้งาน</p>
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>

                        <div class="custom-options">
                            <div class="custom-option" data-value="">อยู่ระหว่างใช้งาน</div>
                            <div class="custom-option" data-value="">ส่งซ่อม</div>
                            <div class="custom-option" data-value="">ไม่พร้อมใช้งาน</div>
                        </div>

                        <select class="form-input" name="status">
                            <option value="all">ทุกสถานะ</option>
                            <option value="pending" <?= $vehicle_approval_status === 'pending' ? 'selected' : '' ?>>รออนุมัติ</option>
                            <option value="approved" <?= $vehicle_approval_status === 'approved' ? 'selected' : '' ?>>อนุมัติแล้ว</option>
                            <option value="rejected" <?= $vehicle_approval_status === 'rejected' ? 'selected' : '' ?>>ไม่อนุมัติ/ยกเลิก</option>
                        </select>
                    </div>
                    <!-- <select class="form-input" name="vehicle_status" required>
                        <?php //foreach ($status_options as $status_option) : 
                        ?>
                            <option value="<?php //h((string) $status_option) 
                                            ?>" <?php //$status_option === $form_values['vehicleStatus'] ? 'selected' : '' 
                                                ?>>
                                <?php //h((string) $status_option) 
                                ?>
                            </option>
                        <?php //endforeach; 
                        ?>
                    </select> -->
                </div>
                <div class="room-admin-modal-actions">
                    <button type="button" class="btn-outline" data-vehicle-modal-close="vehicleAddModal">ยกเลิก</button>
                    <button type="submit" class="btn-confirm">บันทึกข้อมูล</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="vehicleEditModal" class="modal-overlay hidden">
    <div class="modal-content room-admin-modal">
        <header class="modal-header">
            <div class="modal-title">
                <i class="fa-solid fa-pen"></i>
                <span>แก้ไขข้อมูลยานพาหนะ</span>
            </div>
            <div class="close-modal-btn" data-vehicle-modal-close="vehicleEditModal">
                <i class="fa-solid fa-xmark"></i>
            </div>
        </header>
        <div class="modal-body room-admin-modal-body">
            <form class="room-admin-form" method="POST" action="<?= h($_SERVER['PHP_SELF'] ?? 'vehicle-management.php') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="vehicle_action" value="edit">
                <input type="hidden" name="vehicle_id" value="<?= h((string) ($edit_values['vehicleID'] ?? 0)) ?>" data-vehicle-edit-id>
                <div class="form-group full">
                    <label class="form-label">ประเภทยานพาหนะ</label>
                    <input class="form-input" type="text" name="vehicle_type" maxlength="50" required
                        data-vehicle-edit-type value="<?= h((string) $edit_values['vehicleType']) ?>">
                </div>
                <div class="form-group full">
                    <label class="form-label">ทะเบียนรถ</label>
                    <input class="form-input" type="text" name="vehicle_plate" maxlength="50" required
                        data-vehicle-edit-plate value="<?= h((string) $edit_values['vehiclePlate']) ?>">
                </div>
                <div class="form-group full">
                    <label class="form-label">ยี่ห้อรถ</label>
                    <input class="form-input" type="text" name="vehicle_brand" maxlength="100"
                        data-vehicle-edit-brand value="<?= h((string) $edit_values['vehicleBrand']) ?>">
                </div>
                <div class="form-group full">
                    <label class="form-label">รุ่นรถ</label>
                    <input class="form-input" type="text" name="vehicle_model" maxlength="100"
                        data-vehicle-edit-model value="<?= h((string) $edit_values['vehicleModel']) ?>">
                </div>
                <div class="form-group full">
                    <label class="form-label">สีรถ</label>
                    <input class="form-input" type="text" name="vehicle_color" maxlength="50"
                        data-vehicle-edit-color value="<?= h((string) $edit_values['vehicleColor']) ?>">
                </div>
                <div class="form-group full">
                    <label class="form-label">ความจุที่นั่ง</label>
                    <input class="form-input" type="number" name="vehicle_capacity" min="1" max="99"
                        data-vehicle-edit-capacity value="<?= h((string) ($edit_values['vehicleCapacity'] ?? 4)) ?>">
                </div>
                <div class="form-group full">
                    <label class="form-label">สถานะ</label>
                    <select class="form-input" name="vehicle_status" required data-vehicle-edit-status>
                        <?php foreach ($status_options as $status_option) : ?>
                            <option value="<?= h((string) $status_option) ?>" <?= $status_option === $edit_values['vehicleStatus'] ? 'selected' : '' ?>>
                                <?= h((string) $status_option) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="room-admin-modal-actions">
                    <button type="button" class="btn-outline" data-vehicle-modal-close="vehicleEditModal">ยกเลิก</button>
                    <button type="submit" class="btn-confirm">บันทึกการเปลี่ยนแปลง</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="vehicleDeleteConfirmModal" class="alert-overlay hidden">
    <div class="alert-box danger room-member-confirm-alert">
        <div class="alert-header">
            <div class="icon-circle"><i class="fa-solid fa-trash-can"></i></div>
        </div>
        <div class="alert-body">
            <h1>ยืนยันการลบยานพาหนะ</h1>
            <p data-vehicle-delete-message>โปรดยืนยันการลบยานพาหนะออกจากระบบ</p>
            <div class="alert-actions">
                <button type="button" class="btn-close-alert" data-vehicle-delete-confirm="true">ยืนยันลบ</button>
                <button type="button" class="btn-close-alert btn-cancel-alert" data-vehicle-delete-cancel="true">ยกเลิก</button>
            </div>
        </div>
    </div>
</div>

<div id="vehicleMemberModal" class="modal-overlay hidden">
    <div class="modal-content room-admin-modal room-admin-member-modal">
        <header class="modal-header">
            <div class="modal-title">
                <i class="fa-solid fa-user-plus"></i>
                <span>เพิ่มสมาชิกทีมผู้ดูแล</span>
            </div>
            <div class="close-modal-btn" data-vehicle-modal-close="vehicleMemberModal">
                <i class="fa-solid fa-xmark"></i>
            </div>
        </header>
        <div class="modal-body room-admin-modal-body">
            <form class="room-admin-search-form" data-member-search-form>
                <div class="room-admin-search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input class="form-input" type="search" placeholder="ค้นหาบุคลากร" autocomplete="off" data-member-search>
                </div>
            </form>

            <div class="room-admin-member-count" data-member-count>
                ทั้งหมด <?= h((string) $vehicle_candidate_count) ?> คน
            </div>

            <div class="table-responsive">
                <table class="custom-table booking-table room-admin-member-table">
                    <thead>
                        <tr>
                            <th>ชื่อ-สกุล</th>
                            <th>กลุ่มสาระฯ</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $candidate_seen = [];
                        foreach ($vehicle_candidate_members as $candidate) :
                            $candidate_pid = trim((string) ($candidate['pID'] ?? ''));
                            if ($candidate_pid === '' || isset($candidate_seen[$candidate_pid])) {
                                continue;
                            }
                            $candidate_seen[$candidate_pid] = true;

                            $candidate_name = trim((string) ($candidate['name'] ?? ''));
                            $candidate_name = $candidate_name !== '' ? $candidate_name : 'ไม่ระบุชื่อ';
                            $candidate_department = trim((string) ($candidate['department_name'] ?? ''));
                            $candidate_department = $candidate_department !== '' ? $candidate_department : '-';
                            $candidate_position = trim((string) ($candidate['position_name'] ?? ''));
                            $candidate_tel = trim((string) ($candidate['telephone'] ?? ''));

                            $member_search = trim(implode(' ', array_filter([
                                $candidate_pid,
                                $candidate_name,
                                $candidate_department,
                                $candidate_position,
                                $candidate_tel,
                            ])));
                        ?>
                            <tr data-member-row data-member-search="<?= h($member_search) ?>">
                                <td>
                                    <strong><?= h($candidate_name) ?></strong>
                                    <?php if ($candidate_position !== '') : ?>
                                        <div class="detail-subtext"><?= h($candidate_position) ?></div>
                                    <?php endif; ?>
                                    <?php if ($candidate_tel !== '') : ?>
                                        <div class="detail-subtext">โทร <?= h($candidate_tel) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><span><?= h($candidate_department) ?></span></td>
                                <td>
                                    <div class="booking-action-group">
                                        <form class="booking-action-form" method="POST"
                                            action="<?= h($_SERVER['PHP_SELF'] ?? 'vehicle-management.php') ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="member_action" value="add">
                                            <input type="hidden" name="member_pid" value="<?= h($candidate_pid) ?>">
                                            <button type="submit" class="booking-action-btn add" data-member-add-btn>เพิ่ม</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <tr class="booking-empty <?= empty($vehicle_candidate_members) ? '' : 'hidden' ?>" data-member-empty>
                            <td colspan="3">ไม่พบบุคลากรที่สามารถเพิ่มได้</td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>

<div id="vehicleMemberConfirmModal" class="alert-overlay hidden">
    <div class="alert-box warning">
        <div class="alert-header">
            <div class="icon-circle"><i class="fa-solid fa-user-plus"></i></div>
        </div>
        <div class="alert-body">
            <h1>ยืนยันการเพิ่มสมาชิก</h1>
            <p data-vehicle-member-confirm-message>โปรดยืนยันการเพิ่มสมาชิกใหม่</p>
            <div class="alert-actions">
                <button type="button" class="btn-close-alert btn-cancel-alert" data-vehicle-member-cancel="true">ยกเลิก</button>
                <button type="button" class="btn-close-alert" data-vehicle-member-confirm="true">ยืนยัน</button>
            </div>
        </div>
    </div>
</div>

<div id="vehicleMemberRemoveConfirmModal" class="alert-overlay hidden">
    <div class="alert-box danger">
        <div class="alert-header">
            <div class="icon-circle"><i class="fa-solid fa-user-minus"></i></div>
        </div>
        <div class="alert-body">
            <h1>ยืนยันการลบสมาชิก</h1>
            <p data-vehicle-member-remove-message>โปรดยืนยันการลบสมาชิกออกจากทีม</p>
            <div class="alert-actions">
                <button type="button" class="btn-close-alert btn-cancel-alert" data-vehicle-member-remove-cancel="true">ยกเลิก</button>
                <button type="button" class="btn-close-alert" data-vehicle-member-remove-confirm="true">ยืนยัน</button>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';

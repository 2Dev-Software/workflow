<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../auth/csrf.php';

$vehicles = (array) ($vehicles ?? []);
$status_classes = (array) ($status_classes ?? []);
$status_options = (array) ($status_options ?? []);
$open_modal = (string) ($open_modal ?? '');
$form_values = (array) ($form_values ?? []);
$edit_values = (array) ($edit_values ?? []);

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
                <p class="booking-card-subtitle">จัดการทะเบียนรถ สถานะ และรายละเอียดของยานพาหนะในระบบ</p>
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
            <table class="custom-table booking-table room-admin-table">
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
                            $updated_label = $format_thai_datetime($display_updated);
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
                                    <div class="room-admin-room-meta">
                                        <?= h($vehicle_meta !== '' ? $vehicle_meta : 'ไม่ระบุรายละเอียด') ?>
                                    </div>
                                </td>
                                <td><?= h($vehicle_type !== '' ? $vehicle_type : '-') ?></td>
                                <td>
                                    <span class="room-status-pill <?= h($status_class) ?>">
                                        <?= h($vehicle_status) ?>
                                    </span>
                                </td>
                                <td><?= h($vehicle_capacity > 0 ? (string) $vehicle_capacity . ' ที่นั่ง' : '-') ?></td>
                                <td><?= h($updated_label) ?></td>
                                <td class="room-admin-actions-cell">
                                    <div class="booking-action-group">
                                        <button type="button" class="booking-action-btn secondary" data-vehicle-edit="true">แก้ไข</button>
                                        <form method="POST" action="<?= h($_SERVER['PHP_SELF'] ?? 'vehicle-management.php') ?>" data-vehicle-delete-form>
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="vehicle_action" value="delete">
                                            <input type="hidden" name="vehicle_id" value="<?= h((string) $vehicle_id) ?>">
                                            <button type="button" class="booking-action-btn danger" data-vehicle-delete-btn>ลบ</button>
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
                    <select class="form-input" name="vehicle_status" required>
                        <?php foreach ($status_options as $status_option) : ?>
                            <option value="<?= h((string) $status_option) ?>" <?= $status_option === $form_values['vehicleStatus'] ? 'selected' : '' ?>>
                                <?= h((string) $status_option) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
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

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';

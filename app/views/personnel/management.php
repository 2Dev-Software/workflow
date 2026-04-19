<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../auth/csrf.php';

$personnel_rows = (array) ($personnel_rows ?? []);
$open_modal = (string) ($open_modal ?? '');
$form_values = array_merge([
    'original_pid' => '',
    'pID' => '',
    'fName' => '',
    'fID' => 0,
    'dID' => 0,
    'lID' => 0,
    'oID' => 0,
    'positionID' => 0,
    'roleIDs' => [6],
    'telephone' => '',
    'picture' => '',
    'signature' => '',
    'passWord' => '',
    'LineID' => '',
    'status' => 1,
], (array) ($form_values ?? []));
$edit_values = array_merge([
    'original_pid' => '',
    'pID' => '',
    'fName' => '',
    'fID' => 0,
    'dID' => 0,
    'lID' => 0,
    'oID' => 0,
    'positionID' => 0,
    'roleIDs' => [6],
    'telephone' => '',
    'picture' => '',
    'signature' => '',
    'passWord' => '',
    'LineID' => '',
    'status' => 1,
], (array) ($edit_values ?? []));
$faction_options = (array) ($faction_options ?? [0 => 'ไม่กำหนด']);
$department_options = (array) ($department_options ?? [0 => 'ไม่กำหนด']);
$level_options = (array) ($level_options ?? [0 => 'ไม่กำหนด']);
$legacy_position_options = (array) ($legacy_position_options ?? [0 => 'ไม่กำหนด']);
$position_options = (array) ($position_options ?? [0 => 'ไม่กำหนด']);
$role_rows = (array) ($role_rows ?? []);
$active_count = (int) ($active_count ?? 0);
$inactive_count = (int) ($inactive_count ?? 0);

$format_role_ids = static function ($value): array {
    $ids = [];

    foreach ((array) $value as $item) {
        foreach (preg_split('/\s*,\s*/', trim((string) $item)) ?: [] as $part) {
            $part = trim($part);

            if ($part === '' || !ctype_digit($part)) {
                continue;
            }

            $role_id = (int) $part;

            if ($role_id > 0) {
                $ids[] = $role_id;
            }
        }
    }

    return array_values(array_unique($ids));
};

$form_role_ids = $format_role_ids($form_values['roleIDs'] ?? []);
$edit_role_ids = $format_role_ids($edit_values['roleIDs'] ?? []);

ob_start();
?>
<style>
    .content-area.personnel-admin-page .room-admin-table td {
        vertical-align: top;
    }

    .content-area.personnel-admin-page .personnel-summary-line {
        color: var(--color-neutral-dark);
        font-size: var(--font-size-desc-2);
        line-height: 1.5;
    }

    .content-area.personnel-admin-page .personnel-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px 20px;
    }

    .content-area.personnel-admin-page .personnel-form-grid .form-group.full {
        grid-column: 1 / -1;
    }

    .content-area.personnel-admin-page .personnel-modal-scroll {
        max-height: calc(100vh - 210px);
        overflow-y: auto;
    }

    .content-area.personnel-admin-page .personnel-role-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }

    .content-area.personnel-admin-page .personnel-role-option {
        display: flex;
        align-items: center;
        gap: 10px;
        min-height: 48px;
        padding: 10px 12px;
        border-radius: 14px;
        border: 1px solid rgba(var(--rgb-primary-dark), 0.18);
        background: rgba(var(--rgb-neutral-lightest), 0.98);
        cursor: pointer;
    }

    .content-area.personnel-admin-page .personnel-role-option input {
        width: 18px;
        height: 18px;
        margin: 0;
    }

    .content-area.personnel-admin-page .personnel-form-note {
        color: var(--color-neutral-dark);
        font-size: var(--font-size-desc-2);
        line-height: 1.35;
    }

    @media (max-width: 900px) {
        .content-area.personnel-admin-page .personnel-form-grid,
        .content-area.personnel-admin-page .personnel-role-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="content-header">
    <h1>ยินดีต้อนรับ</h1>
    <p>จัดการบุคลากร</p>
</div>

<div class="content-area room-admin-page personnel-admin-page" data-personnel-management data-personnel-open-modal="<?= h($open_modal) ?>">
    <section class="booking-card booking-list-card room-admin-card">
        <div class="booking-card-header">
            <div class="booking-card-title-group">
                <h2 class="booking-card-title">รายการบุคลากร</h2>
                <div class="room-admin-member-count personnel-summary-line">
                    ทั้งหมด <?= h((string) count($personnel_rows)) ?> คน
                    | กำลังใช้งาน <?= h((string) $active_count) ?> คน
                    | ปิดใช้งาน <?= h((string) $inactive_count) ?> คน
                </div>
            </div>
            <div class="room-admin-actions" data-personnel-filter>
                <div class="room-admin-search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input class="form-input" type="search" placeholder="ค้นหาชื่อ รหัสประชาชน กลุ่ม หน่วยงาน หรือบทบาท"
                        autocomplete="off" data-personnel-search-input>
                </div>
                <div class="room-admin-filter">
                    <div class="custom-select-wrapper">
                        <div class="custom-select-trigger">
                            <p class="select-value">ทุกสถานะ</p>
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>

                        <div class="custom-options">
                            <div class="custom-option selected" data-value="all">ทุกสถานะ</div>
                            <div class="custom-option" data-value="1">กำลังใช้งาน</div>
                            <div class="custom-option" data-value="0">ปิดใช้งาน</div>
                        </div>

                        <select class="form-input" data-personnel-status-filter>
                            <option value="all" selected>ทุกสถานะ</option>
                            <option value="1">กำลังใช้งาน</option>
                            <option value="0">ปิดใช้งาน</option>
                        </select>
                    </div>
                </div>
                <button type="button" class="btn-confirm" data-personnel-modal-open="personnelAddModal">เพิ่มบุคลากรใหม่</button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="custom-table booking-table room-admin-table personnel-admin-table">
                <thead>
                    <tr>
                        <th>ชื่อ-นามสกุล</th>
                        <th>กลุ่ม/หน่วยงาน</th>
                        <th>ตำแหน่ง</th>
                        <th>บทบาท</th>
                        <th>สถานะ</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($personnel_rows)) : ?>
                        <tr>
                            <td colspan="6" class="booking-empty">ไม่พบข้อมูลบุคลากร</td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($personnel_rows as $row) : ?>
                            <?php
                            $pid = trim((string) ($row['pID'] ?? ''));
                            $name = trim((string) ($row['fName'] ?? ''));
                            $faction_name = trim((string) ($row['factionName'] ?? ''));
                            $department_name = trim((string) ($row['departmentName'] ?? ''));
                            $level_name = trim((string) ($row['levelName'] ?? ''));
                            $legacy_position_name = trim((string) ($row['legacyPositionName'] ?? ''));
                            $system_position_name = trim((string) ($row['systemPositionName'] ?? ''));
                            $role_name = trim((string) ($row['roleName'] ?? ''));
                            $telephone = trim((string) ($row['telephone'] ?? ''));
                            $picture = trim((string) ($row['picture'] ?? ''));
                            $signature = trim((string) ($row['signature'] ?? ''));
                            $line_id = trim((string) ($row['LineID'] ?? ''));
                            $status = (int) ($row['status'] ?? 0);
                            $role_ids_csv = trim((string) ($row['roleID'] ?? ''));
                            $search_text = trim(implode(' ', [
                                $pid,
                                $name,
                                $faction_name,
                                $department_name,
                                $level_name,
                                $legacy_position_name,
                                $system_position_name,
                                $role_name,
                                $telephone,
                            ]));
                            $position_display = $system_position_name !== '' ? $system_position_name : ($legacy_position_name !== '' ? $legacy_position_name : '-');
                            ?>
                            <tr
                                data-personnel-row
                                data-personnel-search="<?= h($search_text) ?>"
                                data-personnel-status="<?= h((string) $status) ?>"
                                data-pid="<?= h($pid) ?>"
                                data-name="<?= h($name) ?>"
                                data-fid="<?= h((string) ((int) ($row['fID'] ?? 0))) ?>"
                                data-did="<?= h((string) ((int) ($row['dID'] ?? 0))) ?>"
                                data-lid="<?= h((string) ((int) ($row['lID'] ?? 0))) ?>"
                                data-oid="<?= h((string) ((int) ($row['oID'] ?? 0))) ?>"
                                data-position-id="<?= h((string) ((int) ($row['positionID'] ?? 0))) ?>"
                                data-role-ids="<?= h($role_ids_csv) ?>"
                                data-telephone="<?= h($telephone) ?>"
                                data-picture="<?= h($picture) ?>"
                                data-signature="<?= h($signature) ?>"
                                data-line-id="<?= h($line_id) ?>"
                                data-status-value="<?= h((string) $status) ?>">
                                <td>
                                    <div class="room-admin-room-name"><?= h($name !== '' ? $name : '-') ?></div>
                                    <span class="detail-subtext">รหัสประชาชน <?= h($pid !== '' ? $pid : '-') ?></span>
                                    <span class="detail-subtext">โทร <?= h($telephone !== '' ? $telephone : '-') ?></span>
                                </td>
                                <td>
                                    <div class="room-admin-room-name"><?= h($faction_name !== '' ? $faction_name : 'ไม่กำหนด') ?></div>
                                    <span class="detail-subtext"><?= h($department_name !== '' ? $department_name : 'ไม่กำหนดหน่วยงาน') ?></span>
                                    <span class="detail-subtext">วิทยฐานะ <?= h($level_name !== '' ? $level_name : 'ไม่กำหนด') ?></span>
                                </td>
                                <td>
                                    <div class="room-admin-room-name"><?= h($position_display) ?></div>
                                    <span class="detail-subtext">ตำแหน่งเดิม <?= h($legacy_position_name !== '' ? $legacy_position_name : 'ไม่กำหนด') ?></span>
                                </td>
                                <td><?= h($role_name !== '' ? $role_name : '-') ?></td>
                                <td>
                                    <span class="room-status-pill <?= $status === 1 ? 'available' : 'unavailable' ?>">
                                        <?= h($status === 1 ? 'กำลังใช้งาน' : 'ปิดใช้งาน') ?>
                                    </span>
                                </td>
                                <td class="room-admin-actions-cell">
                                    <div class="booking-action-group">
                                        <button type="button" class="booking-action-btn secondary" data-personnel-edit="true">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                            <span class="tooltip">แก้ไขข้อมูลบุคลากร</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <tr class="hidden" data-personnel-empty>
                        <td colspan="6" class="booking-empty">ไม่พบข้อมูลตามเงื่อนไขที่ค้นหา</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <div id="personnelAddModal" class="modal-overlay hidden">
        <div class="modal-content room-admin-modal">
            <header class="modal-header">
                <div class="modal-title">
                    <span>เพิ่มบุคลากรใหม่</span>
                </div>
                <div class="close-modal-btn" data-personnel-modal-close="personnelAddModal">
                    <i class="fa-solid fa-xmark"></i>
                </div>
            </header>

            <div class="modal-body room-admin-modal-body personnel-modal-scroll">
                <form class="room-admin-form" method="POST" action="<?= h($_SERVER['PHP_SELF'] ?? 'personnel-management.php') ?>" id="personnelAddForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="personnel_action" value="create">

                    <div class="personnel-form-grid">
                        <div class="form-group">
                            <label class="form-label" for="personnelAddPid">รหัสบัตรประชาชน</label>
                            <input class="form-input" type="text" id="personnelAddPid" name="pID" value="<?= h((string) ($form_values['pID'] ?? '')) ?>" inputmode="numeric" maxlength="13" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="personnelAddName">ชื่อ-นามสกุล</label>
                            <input class="form-input" type="text" id="personnelAddName" name="fName" value="<?= h((string) ($form_values['fName'] ?? '')) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="personnelAddFaction">กลุ่ม/ฝ่าย</label>
                            <select class="form-input" id="personnelAddFaction" name="fID">
                                <?php foreach ($faction_options as $id => $label) : ?>
                                    <option value="<?= h((string) $id) ?>" <?= (int) ($form_values['fID'] ?? 0) === (int) $id ? 'selected' : '' ?>><?= h((string) $label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="personnelAddDepartment">หน่วยงาน</label>
                            <select class="form-input" id="personnelAddDepartment" name="dID">
                                <?php foreach ($department_options as $id => $label) : ?>
                                    <option value="<?= h((string) $id) ?>" <?= (int) ($form_values['dID'] ?? 0) === (int) $id ? 'selected' : '' ?>><?= h((string) $label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="personnelAddLevel">วิทยฐานะ</label>
                            <select class="form-input" id="personnelAddLevel" name="lID">
                                <?php foreach ($level_options as $id => $label) : ?>
                                    <option value="<?= h((string) $id) ?>" <?= (int) ($form_values['lID'] ?? 0) === (int) $id ? 'selected' : '' ?>><?= h((string) $label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="personnelAddLegacyPosition">ตำแหน่งเดิม</label>
                            <select class="form-input" id="personnelAddLegacyPosition" name="oID">
                                <?php foreach ($legacy_position_options as $id => $label) : ?>
                                    <option value="<?= h((string) $id) ?>" <?= (int) ($form_values['oID'] ?? 0) === (int) $id ? 'selected' : '' ?>><?= h((string) $label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="personnelAddPosition">ตำแหน่งใช้งานระบบ</label>
                            <select class="form-input" id="personnelAddPosition" name="positionID">
                                <?php foreach ($position_options as $id => $label) : ?>
                                    <option value="<?= h((string) $id) ?>" <?= (int) ($form_values['positionID'] ?? 0) === (int) $id ? 'selected' : '' ?>><?= h((string) $label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="personnelAddTelephone">เบอร์โทรศัพท์</label>
                            <input class="form-input" type="text" id="personnelAddTelephone" name="telephone" value="<?= h((string) ($form_values['telephone'] ?? '')) ?>" inputmode="numeric" maxlength="10">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="personnelAddLineId">Line ID</label>
                            <input class="form-input" type="text" id="personnelAddLineId" name="LineID" value="<?= h((string) ($form_values['LineID'] ?? '')) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="personnelAddPicture">พาธรูปโปรไฟล์</label>
                            <input class="form-input" type="text" id="personnelAddPicture" name="picture" value="<?= h((string) ($form_values['picture'] ?? '')) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="personnelAddSignature">พาธลายเซ็น</label>
                            <input class="form-input" type="text" id="personnelAddSignature" name="signature" value="<?= h((string) ($form_values['signature'] ?? '')) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="personnelAddPassword">รหัสผ่าน</label>
                            <input class="form-input" type="text" id="personnelAddPassword" name="passWord" value="<?= h((string) ($form_values['passWord'] ?? '')) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="personnelAddStatus">สถานะใช้งาน</label>
                            <select class="form-input" id="personnelAddStatus" name="status">
                                <option value="1" <?= (int) ($form_values['status'] ?? 1) === 1 ? 'selected' : '' ?>>กำลังใช้งาน</option>
                                <option value="0" <?= (int) ($form_values['status'] ?? 1) === 0 ? 'selected' : '' ?>>ปิดใช้งาน</option>
                            </select>
                        </div>
                        <div class="form-group full">
                            <label class="form-label">บทบาทระบบ</label>
                            <div class="personnel-role-grid">
                                <?php foreach ($role_rows as $role_row) : ?>
                                    <?php
                                    $role_id = (int) ($role_row['id'] ?? 0);
                                    $role_name = trim((string) ($role_row['name'] ?? ''));
                                    if ($role_id <= 0 || $role_name === '') {
                                        continue;
                                    }
                                    ?>
                                    <label class="personnel-role-option">
                                        <input type="checkbox" name="role_ids[]" value="<?= h((string) $role_id) ?>" <?= in_array($role_id, $form_role_ids, true) ? 'checked' : '' ?>>
                                        <span><?= h($role_name) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <div class="personnel-form-note">ถ้าไม่เลือก ระบบจะกำหนดเป็นบุคลากรทั่วไปให้อัตโนมัติ</div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="footer-modal">
                <form method="POST" action="" class="orders-send-form">
                    <button type="submit" form="personnelAddForm">
                        <p>บันทึกข้อมูล</p>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div id="personnelEditModal" class="modal-overlay hidden">
        <div class="modal-content room-admin-modal">
            <header class="modal-header">
                <div class="modal-title">
                    <span>แก้ไขข้อมูลบุคลากร</span>
                </div>
                <div class="close-modal-btn" data-personnel-modal-close="personnelEditModal">
                    <i class="fa-solid fa-xmark"></i>
                </div>
            </header>

            <div class="modal-body room-admin-modal-body personnel-modal-scroll">
                <form class="room-admin-form" method="POST" action="<?= h($_SERVER['PHP_SELF'] ?? 'personnel-management.php') ?>" id="personnelEditForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="personnel_action" value="update">
                    <input type="hidden" name="original_pid" id="personnelEditOriginalPid" value="<?= h((string) ($edit_values['original_pid'] ?? '')) ?>">

                    <div class="personnel-form-grid">
                        <div class="form-group">
                            <label class="form-label" for="personnelEditPid">รหัสบัตรประชาชน</label>
                            <input class="form-input" type="text" id="personnelEditPid" name="pID" value="<?= h((string) ($edit_values['pID'] ?? '')) ?>" readonly required>
                            <div class="personnel-form-note">รหัสบุคลากรเป็นข้อมูลอ้างอิงหลักของระบบ จึงแก้ไขไม่ได้จากหน้านี้</div>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="personnelEditName">ชื่อ-นามสกุล</label>
                            <input class="form-input" type="text" id="personnelEditName" name="fName" value="<?= h((string) ($edit_values['fName'] ?? '')) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="personnelEditFaction">กลุ่ม/ฝ่าย</label>
                            <select class="form-input" id="personnelEditFaction" name="fID">
                                <?php foreach ($faction_options as $id => $label) : ?>
                                    <option value="<?= h((string) $id) ?>" <?= (int) ($edit_values['fID'] ?? 0) === (int) $id ? 'selected' : '' ?>><?= h((string) $label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="personnelEditDepartment">หน่วยงาน</label>
                            <select class="form-input" id="personnelEditDepartment" name="dID">
                                <?php foreach ($department_options as $id => $label) : ?>
                                    <option value="<?= h((string) $id) ?>" <?= (int) ($edit_values['dID'] ?? 0) === (int) $id ? 'selected' : '' ?>><?= h((string) $label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="personnelEditLevel">วิทยฐานะ</label>
                            <select class="form-input" id="personnelEditLevel" name="lID">
                                <?php foreach ($level_options as $id => $label) : ?>
                                    <option value="<?= h((string) $id) ?>" <?= (int) ($edit_values['lID'] ?? 0) === (int) $id ? 'selected' : '' ?>><?= h((string) $label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="personnelEditLegacyPosition">ตำแหน่งเดิม</label>
                            <select class="form-input" id="personnelEditLegacyPosition" name="oID">
                                <?php foreach ($legacy_position_options as $id => $label) : ?>
                                    <option value="<?= h((string) $id) ?>" <?= (int) ($edit_values['oID'] ?? 0) === (int) $id ? 'selected' : '' ?>><?= h((string) $label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="personnelEditPosition">ตำแหน่งใช้งานระบบ</label>
                            <select class="form-input" id="personnelEditPosition" name="positionID">
                                <?php foreach ($position_options as $id => $label) : ?>
                                    <option value="<?= h((string) $id) ?>" <?= (int) ($edit_values['positionID'] ?? 0) === (int) $id ? 'selected' : '' ?>><?= h((string) $label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="personnelEditTelephone">เบอร์โทรศัพท์</label>
                            <input class="form-input" type="text" id="personnelEditTelephone" name="telephone" value="<?= h((string) ($edit_values['telephone'] ?? '')) ?>" inputmode="numeric" maxlength="10">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="personnelEditLineId">Line ID</label>
                            <input class="form-input" type="text" id="personnelEditLineId" name="LineID" value="<?= h((string) ($edit_values['LineID'] ?? '')) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="personnelEditPicture">พาธรูปโปรไฟล์</label>
                            <input class="form-input" type="text" id="personnelEditPicture" name="picture" value="<?= h((string) ($edit_values['picture'] ?? '')) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="personnelEditSignature">พาธลายเซ็น</label>
                            <input class="form-input" type="text" id="personnelEditSignature" name="signature" value="<?= h((string) ($edit_values['signature'] ?? '')) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="personnelEditPassword">รหัสผ่านใหม่</label>
                            <input class="form-input" type="text" id="personnelEditPassword" name="passWord" value="">
                            <div class="personnel-form-note">เว้นว่างไว้หากไม่ต้องการเปลี่ยนรหัสผ่าน</div>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="personnelEditStatus">สถานะใช้งาน</label>
                            <select class="form-input" id="personnelEditStatus" name="status">
                                <option value="1" <?= (int) ($edit_values['status'] ?? 1) === 1 ? 'selected' : '' ?>>กำลังใช้งาน</option>
                                <option value="0" <?= (int) ($edit_values['status'] ?? 1) === 0 ? 'selected' : '' ?>>ปิดใช้งาน</option>
                            </select>
                        </div>
                        <div class="form-group full">
                            <label class="form-label">บทบาทระบบ</label>
                            <div class="personnel-role-grid">
                                <?php foreach ($role_rows as $role_row) : ?>
                                    <?php
                                    $role_id = (int) ($role_row['id'] ?? 0);
                                    $role_name = trim((string) ($role_row['name'] ?? ''));
                                    if ($role_id <= 0 || $role_name === '') {
                                        continue;
                                    }
                                    ?>
                                    <label class="personnel-role-option">
                                        <input type="checkbox" name="role_ids[]" value="<?= h((string) $role_id) ?>" <?= in_array($role_id, $edit_role_ids, true) ? 'checked' : '' ?>>
                                        <span><?= h($role_name) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <div class="personnel-form-note">ถ้าไม่เลือก ระบบจะกำหนดเป็นบุคลากรทั่วไปให้อัตโนมัติ</div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="footer-modal">
                <form method="POST" action="" class="orders-send-form">
                    <button type="submit" form="personnelEditForm">
                        <p>บันทึกข้อมูล</p>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    (() => {
        const root = document.querySelector('[data-personnel-management]');
        if (!root) return;

        const openModalKey = String(root.dataset.personnelOpenModal || '').trim();
        const searchInput = root.querySelector('[data-personnel-search-input]');
        const statusFilter = root.querySelector('[data-personnel-status-filter]');
        const rows = Array.from(root.querySelectorAll('[data-personnel-row]'));
        const emptyRow = root.querySelector('[data-personnel-empty]');
        const addModal = document.getElementById('personnelAddModal');
        const editModal = document.getElementById('personnelEditModal');
        const editForm = document.getElementById('personnelEditForm');

        const modalMap = {
            personnelAddModal: addModal,
            personnelEditModal: editModal,
        };

        const closeModal = (modal) => {
            if (!modal) return;
            modal.classList.add('hidden');
        };

        const openModal = (modal) => {
            if (!modal) return;
            modal.classList.remove('hidden');
        };

        root.querySelectorAll('[data-personnel-modal-open]').forEach((button) => {
            button.addEventListener('click', () => {
                const key = String(button.getAttribute('data-personnel-modal-open') || '');
                openModal(modalMap[key] || null);
            });
        });

        root.querySelectorAll('[data-personnel-modal-close]').forEach((button) => {
            button.addEventListener('click', () => {
                const key = String(button.getAttribute('data-personnel-modal-close') || '');
                closeModal(modalMap[key] || null);
            });
        });

        Object.values(modalMap).forEach((modal) => {
            modal?.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal(modal);
                }
            });
        });

        const setRoleSelections = (form, roleCsv) => {
            const roleIds = String(roleCsv || '')
                .split(',')
                .map((value) => value.trim())
                .filter((value) => value !== '');

            form.querySelectorAll('input[name="role_ids[]"]').forEach((checkbox) => {
                checkbox.checked = roleIds.includes(String(checkbox.value || '').trim());
            });
        };

        root.querySelectorAll('[data-personnel-edit]').forEach((button) => {
            button.addEventListener('click', () => {
                const row = button.closest('[data-personnel-row]');
                if (!row || !editForm) return;

                const setValue = (selector, value) => {
                    const field = editForm.querySelector(selector);
                    if (!field) return;
                    field.value = String(value || '');
                };

                setValue('#personnelEditOriginalPid', row.getAttribute('data-pid') || '');
                setValue('#personnelEditPid', row.getAttribute('data-pid') || '');
                setValue('#personnelEditName', row.getAttribute('data-name') || '');
                setValue('#personnelEditFaction', row.getAttribute('data-fid') || '0');
                setValue('#personnelEditDepartment', row.getAttribute('data-did') || '0');
                setValue('#personnelEditLevel', row.getAttribute('data-lid') || '0');
                setValue('#personnelEditLegacyPosition', row.getAttribute('data-oid') || '0');
                setValue('#personnelEditPosition', row.getAttribute('data-position-id') || '0');
                setValue('#personnelEditTelephone', row.getAttribute('data-telephone') || '');
                setValue('#personnelEditLineId', row.getAttribute('data-line-id') || '');
                setValue('#personnelEditPicture', row.getAttribute('data-picture') || '');
                setValue('#personnelEditSignature', row.getAttribute('data-signature') || '');
                setValue('#personnelEditStatus', row.getAttribute('data-status-value') || '1');
                setValue('#personnelEditPassword', '');
                setRoleSelections(editForm, row.getAttribute('data-role-ids') || '');

                openModal(editModal);
            });
        });

        const applyFilters = () => {
            const query = String(searchInput?.value || '').trim().toLowerCase();
            const statusValue = String(statusFilter?.value || 'all');
            let visibleCount = 0;

            rows.forEach((row) => {
                const rowSearch = String(row.getAttribute('data-personnel-search') || '').toLowerCase();
                const rowStatus = String(row.getAttribute('data-personnel-status') || '');
                const matchedQuery = query === '' || rowSearch.includes(query);
                const matchedStatus = statusValue === 'all' || rowStatus === statusValue;
                const isVisible = matchedQuery && matchedStatus;
                row.style.display = isVisible ? '' : 'none';

                if (isVisible) {
                    visibleCount += 1;
                }
            });

            if (emptyRow) {
                emptyRow.style.display = visibleCount === 0 ? '' : 'none';
            }
        };

        searchInput?.addEventListener('input', applyFilters);
        statusFilter?.addEventListener('change', applyFilters);
        applyFilters();

        if (openModalKey !== '' && modalMap[openModalKey]) {
            openModal(modalMap[openModalKey]);
        }
    })();
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';

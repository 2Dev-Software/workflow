<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../auth/csrf.php';
require_once __DIR__ . '/../view.php';
require_once __DIR__ . '/../../rbac/current_user.php';

$values = $values ?? [];
$factions = $factions ?? [];
$teachers = $teachers ?? [];
$is_edit_mode = (bool) ($is_edit_mode ?? false);
$edit_circular_id = (int) ($edit_circular_id ?? 0);
$existing_attachments = (array) ($existing_attachments ?? []);

$current_user = current_user() ?? [];
$sender_name = trim((string) ($current_user['fName'] ?? ''));
if ($sender_name === '') {
    $sender_name = (string) ($current_user['pID'] ?? '');
}
$faction_name_map = [];
foreach ($factions as $faction) {
    $fid = (int) ($faction['fID'] ?? 0);
    if ($fid <= 0) {
        continue;
    }
    $faction_name_map[$fid] = trim((string) ($faction['fName'] ?? ''));
}
$sender_from_fid = (int) ($current_user['fID'] ?? 0);
$sender_faction_display = '';
if ($sender_from_fid > 0 && isset($faction_name_map[$sender_from_fid])) {
    $sender_faction_display = (string) $faction_name_map[$sender_from_fid];
} else {
    $sender_faction_display = trim((string) ($current_user['faction_name'] ?? ''));
}
if ($sender_faction_display === '') {
    $position_name = trim((string) ($current_user['position_name'] ?? ''));
    if ($position_name !== '') {
        $sender_faction_display = 'ตำแหน่ง ' . $position_name . ' (' . $sender_name . ')';
    } else {
        $sender_faction_display = 'ผู้ส่ง ' . $sender_name;
    }
}

$selected_factions = array_map('strval', (array) ($values['faction_ids'] ?? []));
$selected_people = array_map('strval', (array) ($values['person_ids'] ?? []));

$is_selected = static function (string $value, array $selected): bool {
    return in_array($value, $selected, true);
};

$faction_members = [];
$department_groups = [];
$executive_members = [];
$subject_head_members = [];
foreach ($teachers as $teacher) {
    $fid = (int) ($teacher['fID'] ?? 0);
    $did = (int) ($teacher['dID'] ?? 0);
    $position_id = (int) ($teacher['positionID'] ?? 0);
    $pid = trim((string) ($teacher['pID'] ?? ''));
    $name = trim((string) ($teacher['fName'] ?? ''));
    $department_name = trim((string) ($teacher['departmentName'] ?? ''));
    if ($pid === '' || $name === '') {
        continue;
    }
    if ($fid > 0) {
        if (!isset($faction_members[$fid])) {
            $faction_members[$fid] = [];
        }
        $faction_members[$fid][] = [
            'pID' => $pid,
            'name' => $name,
        ];
    }

    if (in_array($position_id, [1, 2, 3, 4], true)) {
        $executive_members[$pid] = [
            'pID' => $pid,
            'name' => $name,
        ];
    }

    if ($position_id === 5) {
        $subject_head_members[$pid] = [
            'pID' => $pid,
            'name' => $name,
        ];
    }

    $normalized_department_name = preg_replace('/\s+/u', '', $department_name);
    if (
        $did > 0 &&
        $department_name !== '' &&
        strpos((string) $normalized_department_name, 'ผู้บริหาร') === false &&
        strpos((string) $normalized_department_name, 'ฝ่ายบริหาร') === false
    ) {
        if (!isset($department_groups[$did])) {
            $department_groups[$did] = [
                'dID' => $did,
                'name' => $department_name,
                'members' => [],
            ];
        }
        $department_groups[$did]['members'][] = [
            'pID' => $pid,
            'name' => $name,
        ];
    }
}

if (!empty($department_groups)) {
    uasort($department_groups, static function (array $a, array $b): int {
        return strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
    });
}

$executive_members = array_values($executive_members);
$subject_head_members = array_values($subject_head_members);
usort($executive_members, static function (array $a, array $b): int {
    return strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
});
usort($subject_head_members, static function (array $a, array $b): int {
    return strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
});

$special_groups = [];
if (!empty($executive_members)) {
    $special_groups[] = [
        'key' => 'special-executive',
        'name' => 'คณะผู้บริหารสถานศึกษา',
        'members' => $executive_members,
    ];
}
if (!empty($subject_head_members)) {
    $special_groups[] = [
        'key' => 'special-subject-head',
        'name' => 'หัวหน้ากลุ่มสาระ',
        'members' => $subject_head_members,
    ];
}

ob_start();
?>
<div class="content-header">
    <h1>ยินดีต้อนรับ</h1>
    <p>หนังสือเวียน / <?= h($is_edit_mode ? 'แก้ไขและส่งใหม่' : 'ส่งหนังสือเวียน') ?></p>
</div>

<style>
    .container-circular-notice-sending .sender-row {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }

    .container-circular-notice-sending .sender-row .form-group {
        margin-bottom: 0;
    }

    .container-circular-notice-sending .sender-row .form-group label {
        display: block;
        margin: 0 0 8px;
        line-height: 1.2;
    }

    .container-circular-notice-sending .sender-row input[disabled] {
        background-color: #eef3ff;
        color: var(--color-primary-dark);
        font-weight: 600;
        cursor: not-allowed;
    }

    @media (max-width: 900px) {
        .container-circular-notice-sending .sender-row {
            grid-template-columns: 1fr;
        }
    }

    .container-circular-notice-sending .form-group.receive .dropdown-container {
        max-width: 520px;
    }

    .container-circular-notice-sending .form-group.receive .search-input-wrapper {
        min-height: 44px;
    }

    .container-circular-notice-sending .form-group.receive .search-input-wrapper .search-input {
        font-size: 18px;
    }

    .container-circular-notice-sending .dropdown-content {
        width: 100% !important;
        right: 0 !important;
        border-radius: 8px;
    }

    .container-circular-notice-sending .dropdown-content .dropdown-header {
        padding: 8px 10px;
    }

    .container-circular-notice-sending .dropdown-content .select-all-box {
        font-size: 18px;
        font-weight: 700;
        line-height: 1.3;
    }

    .container-circular-notice-sending .dropdown-content .dropdown-list {
        max-height: 360px;
    }

    .container-circular-notice-sending .dropdown-content .category-title {
        padding: 8px 10px;
        border-bottom: 1px solid rgba(var(--rgb-primary-dark), 0.12);
    }

    .container-circular-notice-sending .dropdown-content .category-title span {
        display: inline-flex;
        align-items: center;
        font-size: 14px;
        font-weight: 600;
        line-height: 1.35;
        color: var(--color-secondary);
    }

    .container-circular-notice-sending .dropdown-content .category-items {
        padding: 0 !important;
    }

    .container-circular-notice-sending .dropdown-content .item.item-group {
        display: block !important;
        width: 100%;
        padding: 10px 14px !important;
        box-sizing: border-box;
        cursor: default;
        border-top: 1px solid rgba(var(--rgb-primary-dark), 0.12);
    }

    .container-circular-notice-sending .dropdown-content .item.item-group:hover {
        background: transparent;
    }

    .container-circular-notice-sending .dropdown-content .item-group .group-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 10px;
    }

    .container-circular-notice-sending .dropdown-content .item-group .item-main {
        display: grid;
        grid-template-columns: 20px minmax(0, 1fr);
        align-items: start;
        column-gap: 10px;
        row-gap: 3px;
        width: 100%;
        padding: 4px 0 !important;
        box-sizing: border-box;
        margin: 0;
        cursor: pointer;
    }

    .container-circular-notice-sending .dropdown-content .item-group .group-toggle {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
        border: 1px solid rgba(var(--rgb-primary-dark), 0.35);
        border-radius: 8px;
        background: var(--color-neutral-lightest);
        color: var(--color-secondary);
        cursor: pointer;
        flex-shrink: 0;
        margin-top: 2px;
        transition: background-color 0.2s ease, border-color 0.2s ease, transform 0.2s ease;
    }

    .container-circular-notice-sending .dropdown-content .item-group .group-toggle:hover {
        background: rgba(var(--rgb-primary-dark), 0.06);
    }

    .container-circular-notice-sending .dropdown-content .item-group .group-toggle i {
        transition: transform 0.2s ease;
    }

    .container-circular-notice-sending .dropdown-content .item-group.is-collapsed .group-toggle i {
        transform: rotate(-90deg);
    }

    .container-circular-notice-sending .dropdown-content .item-group .item-main input[type="checkbox"] {
        margin: 2px 0 0 0;
    }

    .container-circular-notice-sending .dropdown-content .item-group .item-title {
        display: block;
        min-width: 0;
        font-weight: 700;
        font-size: 16px;
        line-height: 1.4;
        white-space: normal;
        word-break: normal;
        overflow-wrap: break-word;
        color: var(--color-secondary);
    }

    .container-circular-notice-sending .dropdown-content .item-group .item-subtext {
        display: block;
        grid-column: 2;
        min-width: 0;
        margin-top: 0;
        font-size: 13px;
        line-height: 1.35;
        white-space: normal;
        word-break: normal;
        overflow-wrap: break-word;
        color: rgba(var(--rgb-primary-dark), 0.8);
    }

    .container-circular-notice-sending .dropdown-content .item-group .member-sublist {
        margin: 8px 0 4px 0;
        padding: 0 0 0 30px;
        list-style: none;
    }

    .container-circular-notice-sending .dropdown-content .item-group.is-collapsed .member-sublist {
        display: none;
    }

    .container-circular-notice-sending .dropdown-content .item-group .member-sublist li {
        margin: 0 0 6px 0;
    }

    .container-circular-notice-sending .dropdown-content .item.member-item {
        display: grid;
        grid-template-columns: 20px minmax(0, 1fr);
        align-items: start;
        gap: 10px;
        width: 100%;
        padding: 6px 0 !important;
        box-sizing: border-box;
        margin: 0;
        cursor: pointer;
        border-radius: 6px;
    }

    .container-circular-notice-sending .dropdown-content .item.member-item input[type="checkbox"] {
        margin: 2px 0 0 0;
    }

    .container-circular-notice-sending .dropdown-content .item.member-item .member-name {
        min-width: 0;
        line-height: 1.55;
        font-size: 15px;
        font-weight: 500;
        white-space: normal;
        word-break: normal;
        overflow-wrap: break-word;
        color: var(--color-primary-dark);
    }

    .container-circular-notice-sending .dropdown-content .item.member-item:hover {
        background: rgba(var(--rgb-primary-dark), 0.06);
    }

    .container-circular-notice-sending .dropdown-content .category-items label.item-main,
    .container-circular-notice-sending .dropdown-content .category-items label.member-item {
        width: 100%;
        padding: 0 !important;
        overflow: visible !important;
        text-overflow: clip !important;
        white-space: normal !important;
    }

    .container-circular-notice-sending .enterprise-checkbox-row {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 6px 0;
        cursor: pointer;
    }
</style>

<form method="POST" enctype="multipart/form-data" data-validate class="container-circular-notice-sending" id="circularComposeForm">
    <?= csrf_field() ?>
    <?php if ($is_edit_mode && $edit_circular_id > 0) : ?>
        <input type="hidden" name="edit_circular_id" value="<?= h((string) $edit_circular_id) ?>">
    <?php endif; ?>

    <?php if ($is_edit_mode) : ?>
        <div class="enterprise-panel">
            <p><strong>โหมดแก้ไข:</strong> เอกสารถูกดึงกลับแล้ว คุณสามารถแก้ไขเนื้อหาและส่งใหม่ได้</p>
        </div>
    <?php endif; ?>

    <div class="form-group">
        <label for="subject">หัวเรื่อง</label>
        <input type="text" name="subject" id="subject" placeholder="กรุณากรอกหัวเรื่อง" value="<?= h((string) ($values['subject'] ?? '')) ?>" required>
    </div>

    <div class="form-group">
        <label for="detail">รายละเอียด</label>
        <textarea name="detail" id="detail" rows="4" placeholder="กรุณากรอกรายละเอียด"><?= h((string) ($values['detail'] ?? '')) ?></textarea>
    </div>

    <div class="form-group">
        <label>อัปโหลดไฟล์เอกสาร</label>
        <section class="upload-layout">
            <input type="file" id="fileInput" name="attachments[]" multiple accept="application/pdf,image/png,image/jpeg" style="display: none;" />

            <div class="upload-box" id="dropzone">
                <i class="fa-solid fa-upload"></i>
                <p>ลากไฟล์มาวางที่นี่</p>
            </div>

            <div class="file-list" id="fileListContainer"></div>
        </section>
    </div>

    <?php if ($is_edit_mode && !empty($existing_attachments)) : ?>
        <div class="form-group">
            <label>ไฟล์แนบเดิม (เลือกเพื่อลบก่อนส่งใหม่)</label>
            <div class="enterprise-panel">
                <?php foreach ($existing_attachments as $attachment) : ?>
                    <?php
                    $attachment_file_id = (int) ($attachment['fileID'] ?? 0);
                    $attachment_name = (string) ($attachment['fileName'] ?? '');
                    ?>
                    <label class="enterprise-checkbox-row">
                        <input type="checkbox" name="remove_file_ids[]" value="<?= h((string) $attachment_file_id) ?>">
                        <span><?= h($attachment_name !== '' ? $attachment_name : ('ไฟล์ #' . $attachment_file_id)) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="row form-group">
        <button class="btn btn-upload-small" type="button" id="btnAddFiles">
            <p>เพิ่มไฟล์</p>
        </button>
        <div class="file-hint">
            <p>* แนบไฟล์ได้สูงสุด 5 ไฟล์ (รวม PNG และ PDF) *</p>
        </div>
    </div>

    <div id="imagePreviewModal" class="modal-overlay-preview">
        <span class="close-preview" id="closePreviewBtn">&times;</span>
        <img class="preview-content" id="previewImage" alt="">
        <div id="previewCaption"></div>
    </div>

    <div class="form-group">
        <label for="linkURL">แนบลิ้งก์</label>
        <input type="text" id="linkURL" name="linkURL" placeholder="กรุณาแนบลิ้งก์ที่เกี่ยวข้อง" value="<?= h((string) ($values['linkURL'] ?? '')) ?>" />
    </div>

    <div class="sender-row">
        <div class="form-group sender-field">
            <label for="senderDisplay">ผู้ส่ง</label>
            <input id="senderDisplay" type="text" value="<?= h($sender_name) ?>" disabled>
        </div>
        <div class="form-group">
            <label for="fromFIDDisplay">ในนามของ</label>
            <input id="fromFIDDisplay" type="text" value="<?= h($sender_faction_display) ?>" disabled>
            <input type="hidden" name="fromFID" value="<?= h($sender_from_fid > 0 ? (string) $sender_from_fid : '') ?>">
        </div>
    </div>

    <div class="form-group receive" data-recipients-section>
        <label>ส่งถึง :</label>
        <div class="dropdown-container">
            <div class="search-input-wrapper" id="recipientToggle">
                <input type="text" id="mainInput" class="search-input"
                    placeholder="ค้นหา หรือ เลือกข้อมูล..." autocomplete="off">
                <i class="fa-solid fa-chevron-down"></i>
            </div>

            <div class="dropdown-content" id="dropdownContent">
                <div class="dropdown-header">
                    <label class="select-all-box">
                        <input type="checkbox" id="selectAll">เลือกทั้งหมด
                    </label>
                </div>

                <div class="dropdown-list">
                    <?php if (!empty($factions)) : ?>
                        <div class="category-group">
                            <div class="category-title">
                                <span>หน่วยงาน</span>
                            </div>
                            <div class="category-items">
                                <?php foreach ($factions as $faction) : ?>
                                    <?php
                                    $fid = (int) ($faction['fID'] ?? 0);
                                    if ($fid <= 0) {
                                        continue;
                                    }
                                    $fid_value = (string) $fid;
                                    $faction_name = trim((string) ($faction['fName'] ?? ''));
                                    if ($faction_name === '' || strpos($faction_name, 'ฝ่ายบริหาร') !== false) {
                                        continue;
                                    }
                                    $members = $faction_members[$fid] ?? [];
                                    $member_payload = [];
                                    foreach ($members as $member) {
                                        $member_payload[] = [
                                            'pID' => (string) ($member['pID'] ?? ''),
                                            'name' => (string) ($member['name'] ?? ''),
                                            'faction' => $faction_name,
                                        ];
                                    }
                                    $member_payload_json = json_encode($member_payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                                    if ($member_payload_json === false) {
                                        $member_payload_json = '[]';
                                    }
                                    $member_total = count($members);
                                    $has_selected_member = false;
                                    foreach ($members as $member) {
                                        $member_pid = (string) ($member['pID'] ?? '');
                                        if ($member_pid !== '' && $is_selected($member_pid, $selected_people)) {
                                            $has_selected_member = true;
                                            break;
                                        }
                                    }
                                    $expanded_by_default = $is_selected($fid_value, $selected_factions) || $has_selected_member;
                                    ?>
                                    <div class="item item-group<?= $expanded_by_default ? '' : ' is-collapsed' ?>" data-faction-id="<?= h($fid_value) ?>">
                                        <div class="group-header">
                                            <label class="item-main">
                                                <input type="checkbox" class="item-checkbox group-item-checkbox faction-item-checkbox" data-group="faction"
                                                    data-group-key="faction-<?= h($fid_value) ?>"
                                                    data-group-label="<?= h($faction_name) ?>"
                                                    data-members="<?= h($member_payload_json) ?>"
                                                    name="faction_ids[]" value="<?= h($fid_value) ?>" <?= h($is_selected($fid_value, $selected_factions) ? 'checked' : '') ?>>
                                                <span class="item-title"><?= h($faction_name) ?></span>
                                                <small class="item-subtext">สมาชิกทั้งหมด <?= h((string) $member_total) ?> คน</small>
                                            </label>
                                            <button type="button" class="group-toggle" aria-expanded="<?= $expanded_by_default ? 'true' : 'false' ?>" title="แสดง/ซ่อนรายชื่อสมาชิก">
                                                <i class="fa-solid fa-chevron-down"></i>
                                            </button>
                                        </div>

                                        <ol class="member-sublist">
                                            <?php if ($member_total === 0) : ?>
                                                <li>
                                                    <span class="item-subtext">ไม่มีสมาชิกในฝ่ายนี้</span>
                                                </li>
                                            <?php else : ?>
                                                <?php foreach ($members as $member) : ?>
                                                    <?php
                                                    $member_pid = (string) ($member['pID'] ?? '');
                                                    $member_name = (string) ($member['name'] ?? '');
                                                    if ($member_pid === '' || $member_name === '') {
                                                        continue;
                                                    }
                                                    ?>
                                                    <li>
                                                        <label class="item member-item">
                                                            <input type="checkbox" class="member-checkbox"
                                                                data-member-group-key="faction-<?= h($fid_value) ?>"
                                                                data-member-name="<?= h($member_name) ?>"
                                                                data-group-label="<?= h($faction_name) ?>"
                                                                name="person_ids[]" value="<?= h($member_pid) ?>" <?= h($is_selected($member_pid, $selected_people) ? 'checked' : '') ?>>
                                                            <span class="member-name"><?= h($member_name) ?></span>
                                                        </label>
                                                    </li>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </ol>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($department_groups)) : ?>
                        <div class="category-group">
                            <div class="category-title">
                                <span>กลุ่มสาระ</span>
                            </div>
                            <div class="category-items">
                                <?php foreach ($department_groups as $department_group) : ?>
                                    <?php
                                    $did = (int) ($department_group['dID'] ?? 0);
                                    $department_name = trim((string) ($department_group['name'] ?? ''));
                                    $members = (array) ($department_group['members'] ?? []);
                                    if ($did <= 0 || $department_name === '' || empty($members)) {
                                        continue;
                                    }

                                    $member_payload = [];
                                    $has_selected_member = false;
                                    foreach ($members as $member) {
                                        $member_pid = (string) ($member['pID'] ?? '');
                                        $member_name = (string) ($member['name'] ?? '');
                                        if ($member_pid === '' || $member_name === '') {
                                            continue;
                                        }
                                        if ($is_selected($member_pid, $selected_people)) {
                                            $has_selected_member = true;
                                        }
                                        $member_payload[] = [
                                            'pID' => $member_pid,
                                            'name' => $member_name,
                                            'faction' => $department_name,
                                        ];
                                    }
                                    if (empty($member_payload)) {
                                        continue;
                                    }
                                    $member_payload_json = json_encode($member_payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                                    if ($member_payload_json === false) {
                                        $member_payload_json = '[]';
                                    }
                                    $member_total = count($member_payload);
                                    $group_key = 'department-' . $did;
                                    ?>
                                    <div class="item item-group<?= $has_selected_member ? '' : ' is-collapsed' ?>">
                                        <div class="group-header">
                                            <label class="item-main">
                                                <input type="checkbox" class="item-checkbox group-item-checkbox department-item-checkbox" data-group="department"
                                                    data-group-key="<?= h($group_key) ?>"
                                                    data-group-label="<?= h($department_name) ?>"
                                                    data-members="<?= h($member_payload_json) ?>"
                                                    value="<?= h($group_key) ?>">
                                                <span class="item-title"><?= h($department_name) ?></span>
                                                <small class="item-subtext">สมาชิกทั้งหมด <?= h((string) $member_total) ?> คน</small>
                                            </label>
                                            <button type="button" class="group-toggle" aria-expanded="<?= $has_selected_member ? 'true' : 'false' ?>" title="แสดง/ซ่อนรายชื่อสมาชิก">
                                                <i class="fa-solid fa-chevron-down"></i>
                                            </button>
                                        </div>

                                        <ol class="member-sublist">
                                            <?php foreach ($member_payload as $member) : ?>
                                                <li>
                                                    <label class="item member-item">
                                                        <input type="checkbox" class="member-checkbox"
                                                            data-member-group-key="<?= h($group_key) ?>"
                                                            data-member-name="<?= h((string) ($member['name'] ?? '')) ?>"
                                                            data-group-label="<?= h($department_name) ?>"
                                                            name="person_ids[]" value="<?= h((string) ($member['pID'] ?? '')) ?>" <?= h($is_selected((string) ($member['pID'] ?? ''), $selected_people) ? 'checked' : '') ?>>
                                                        <span class="member-name"><?= h((string) ($member['name'] ?? '')) ?></span>
                                                    </label>
                                                </li>
                                            <?php endforeach; ?>
                                        </ol>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($special_groups)) : ?>
                        <div class="category-group">
                            <div class="category-title">
                                <span>อื่นๆ</span>
                            </div>
                            <div class="category-items">
                                <?php foreach ($special_groups as $special_group) : ?>
                                    <?php
                                    $group_key = trim((string) ($special_group['key'] ?? ''));
                                    $group_name = trim((string) ($special_group['name'] ?? ''));
                                    $members = (array) ($special_group['members'] ?? []);
                                    if ($group_key === '' || $group_name === '' || empty($members)) {
                                        continue;
                                    }

                                    $member_payload = [];
                                    $has_selected_member = false;
                                    foreach ($members as $member) {
                                        $member_pid = (string) ($member['pID'] ?? '');
                                        $member_name = (string) ($member['name'] ?? '');
                                        if ($member_pid === '' || $member_name === '') {
                                            continue;
                                        }
                                        if ($is_selected($member_pid, $selected_people)) {
                                            $has_selected_member = true;
                                        }
                                        $member_payload[] = [
                                            'pID' => $member_pid,
                                            'name' => $member_name,
                                            'faction' => $group_name,
                                        ];
                                    }

                                    if (empty($member_payload)) {
                                        continue;
                                    }

                                    $member_payload_json = json_encode($member_payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                                    if ($member_payload_json === false) {
                                        $member_payload_json = '[]';
                                    }
                                    $member_total = count($member_payload);
                                    ?>
                                    <div class="item item-group<?= $has_selected_member ? '' : ' is-collapsed' ?>">
                                        <div class="group-header">
                                            <label class="item-main">
                                                <input type="checkbox" class="item-checkbox group-item-checkbox" data-group="special"
                                                    data-group-key="<?= h($group_key) ?>"
                                                    data-group-label="<?= h($group_name) ?>"
                                                    data-members="<?= h($member_payload_json) ?>"
                                                    value="<?= h($group_key) ?>">
                                                <span class="item-title"><?= h($group_name) ?></span>
                                                <small class="item-subtext">สมาชิกทั้งหมด <?= h((string) $member_total) ?> คน</small>
                                            </label>
                                            <button type="button" class="group-toggle" aria-expanded="<?= $has_selected_member ? 'true' : 'false' ?>" title="แสดง/ซ่อนรายชื่อสมาชิก">
                                                <i class="fa-solid fa-chevron-down"></i>
                                            </button>
                                        </div>

                                        <ol class="member-sublist">
                                            <?php foreach ($member_payload as $member) : ?>
                                                <li>
                                                    <label class="item member-item">
                                                        <input type="checkbox" class="member-checkbox"
                                                            data-member-group-key="<?= h($group_key) ?>"
                                                            data-member-name="<?= h((string) ($member['name'] ?? '')) ?>"
                                                            data-group-label="<?= h($group_name) ?>"
                                                            name="person_ids[]" value="<?= h((string) ($member['pID'] ?? '')) ?>" <?= h($is_selected((string) ($member['pID'] ?? ''), $selected_people) ? 'checked' : '') ?>>
                                                        <span class="member-name"><?= h((string) ($member['name'] ?? '')) ?></span>
                                                    </label>
                                                </li>
                                            <?php endforeach; ?>
                                        </ol>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="sent-notice-selected">
            <button id="btnShowRecipients" type="button">
                <p>แสดงผู้รับทั้งหมด</p>
            </button>
        </div>
    </div>

    <button id="btnSendNotice" class="sent-notice-btn" type="submit">
        <p><?= h($is_edit_mode ? 'บันทึกแก้ไขและส่งใหม่' : 'ส่งหนังสือเวียน') ?></p>
    </button>

    <div id="confirmModal" class="modal-overlay-confirm">
        <div class="confirm-box">
            <div class="confirm-header">
                <div class="icon-circle">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
            </div>
            <div class="confirm-body">
                <h3><?= h($is_edit_mode ? 'ยืนยันการแก้ไขและส่งใหม่' : 'ยืนยันการส่งหนังสือเวียน') ?></h3>
                <div class="confirm-actions">
                    <button id="btnConfirmYes" class="btn-yes" type="button">ยืนยัน</button>
                    <button id="btnConfirmNo" class="btn-no" type="button">ยกเลิก</button>
                </div>
            </div>
        </div>
    </div>

    <div id="recipientModal" class="modal-overlay-recipient">
        <div class="modal-container">
            <div class="modal-header">
                <div class="modal-title">
                    <i class="fa-solid fa-users"></i>
                    <span>รายชื่อผู้รับหนังสือเวียน</span>
                </div>
                <button class="modal-close" id="closeModalBtn" type="button">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="modal-body">
                <table class="recipient-table">
                    <thead>
                        <tr>
                            <th>ลำดับ</th>
                            <th>ชื่อจริง-นามสกุล</th>
                            <th>กลุ่ม/ฝ่าย</th>
                        </tr>
                    </thead>
                    <tbody id="recipientTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('circularComposeForm');
        const fileInput = document.getElementById('fileInput');
        const fileList = document.getElementById('fileListContainer');
        const dropzone = document.getElementById('dropzone');
        const addFilesBtn = document.getElementById('btnAddFiles');
        const previewModal = document.getElementById('imagePreviewModal');
        const previewImage = document.getElementById('previewImage');
        const previewCaption = document.getElementById('previewCaption');
        const closePreviewBtn = document.getElementById('closePreviewBtn');

        const maxFiles = 5;
        const allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
        let selectedFiles = [];

        const renderFiles = () => {
            if (!fileList) return;
            fileList.innerHTML = '';
            if (selectedFiles.length === 0) return;

            selectedFiles.forEach((file, index) => {
                const wrapper = document.createElement('div');
                wrapper.className = 'file-item-wrapper';

                const deleteBtn = document.createElement('button');
                deleteBtn.type = 'button';
                deleteBtn.className = 'delete-btn';
                deleteBtn.innerHTML = '<i class="fa-solid fa-trash"></i>';
                deleteBtn.addEventListener('click', () => {
                    selectedFiles = selectedFiles.filter((_, i) => i !== index);
                    syncFiles();
                    renderFiles();
                });

                const banner = document.createElement('div');
                banner.className = 'file-banner';

                const info = document.createElement('div');
                info.className = 'file-info';

                const icon = document.createElement('div');
                icon.className = 'file-icon';
                icon.innerHTML = file.type === 'application/pdf' ?
                    '<i class="fa-solid fa-file-pdf"></i>' :
                    '<i class="fa-solid fa-image"></i>';

                const text = document.createElement('div');
                text.className = 'file-text';

                const name = document.createElement('div');
                name.className = 'file-name';
                name.textContent = file.name;

                const type = document.createElement('div');
                type.className = 'file-type';
                type.textContent = file.type || 'ไฟล์แนบ';

                text.appendChild(name);
                text.appendChild(type);

                info.appendChild(icon);
                info.appendChild(text);

                const actions = document.createElement('div');
                actions.className = 'file-actions';

                const view = document.createElement('a');
                view.href = '#';
                view.innerHTML = '<i class="fa-solid fa-eye"></i>';
                view.addEventListener('click', (e) => {
                    e.preventDefault();
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = () => {
                            if (previewImage) previewImage.src = reader.result;
                            if (previewCaption) previewCaption.textContent = file.name;
                            previewModal?.classList.add('active');
                        };
                        reader.readAsDataURL(file);
                    } else {
                        const url = URL.createObjectURL(file);
                        window.open(url, '_blank', 'noopener');
                        setTimeout(() => URL.revokeObjectURL(url), 1000);
                    }
                });

                actions.appendChild(view);
                banner.appendChild(info);
                banner.appendChild(actions);

                wrapper.appendChild(deleteBtn);
                wrapper.appendChild(banner);
                fileList.appendChild(wrapper);
            });
        };

        const syncFiles = () => {
            if (!fileInput) return;
            const dt = new DataTransfer();
            selectedFiles.forEach((file) => dt.items.add(file));
            fileInput.files = dt.files;
        };

        const addFiles = (files) => {
            if (!files) return;
            const existing = new Set(selectedFiles.map((file) => `${file.name}-${file.size}-${file.lastModified}`));
            Array.from(files).forEach((file) => {
                const key = `${file.name}-${file.size}-${file.lastModified}`;
                if (existing.has(key)) return;
                if (!allowedTypes.includes(file.type)) return;
                if (selectedFiles.length >= maxFiles) return;
                selectedFiles.push(file);
                existing.add(key);
            });
            syncFiles();
            renderFiles();
        };

        if (fileInput) {
            fileInput.addEventListener('change', (e) => {
                addFiles(e.target.files);
            });
        }

        if (dropzone) {
            dropzone.addEventListener('click', () => fileInput?.click());
            dropzone.addEventListener('dragover', (e) => {
                e.preventDefault();
                dropzone.classList.add('active');
            });
            dropzone.addEventListener('dragleave', () => dropzone.classList.remove('active'));
            dropzone.addEventListener('drop', (e) => {
                e.preventDefault();
                dropzone.classList.remove('active');
                addFiles(e.dataTransfer?.files || []);
            });
        }

        addFilesBtn?.addEventListener('click', () => fileInput?.click());

        closePreviewBtn?.addEventListener('click', () => previewModal?.classList.remove('active'));
        previewModal?.addEventListener('click', (e) => {
            if (e.target === previewModal) previewModal.classList.remove('active');
        });

        const recipientSection = document.querySelector('[data-recipients-section]');
        if (recipientSection) {
            recipientSection.classList.remove('u-hidden');
        }

        const dropdown = document.getElementById('dropdownContent');
        const toggle = document.getElementById('recipientToggle');
        const searchInput = document.getElementById('mainInput');
        const selectAll = document.getElementById('selectAll');
        const groupChecks = Array.from(document.querySelectorAll('.group-item-checkbox'));
        const memberChecks = Array.from(document.querySelectorAll('.member-checkbox'));
        const groupItems = Array.from(document.querySelectorAll('.dropdown-list .item-group'));

        const getMemberChecksByGroupKey = (groupKey) => memberChecks.filter((el) => (el.dataset.memberGroupKey || '') === String(groupKey));
        const syncMemberByPid = (pid, checked, source) => {
            const normalizedPid = String(pid || '').trim();
            if (normalizedPid === '') return;
            memberChecks.forEach((memberCheck) => {
                if (memberCheck === source) return;
                if (String(memberCheck.value || '') !== normalizedPid) return;
                if (memberCheck.disabled) return;
                memberCheck.checked = checked;
            });
        };
        const setGroupCollapsed = (groupItem, collapsed) => {
            if (!groupItem) return;
            groupItem.classList.toggle('is-collapsed', collapsed);
            const toggleBtn = groupItem.querySelector('.group-toggle');
            if (toggleBtn) {
                toggleBtn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            }
        };

        const setDropdownVisible = (visible) => {
            if (!dropdown) return;
            dropdown.classList.toggle('show', visible);
        };

        toggle?.addEventListener('click', (e) => {
            e.stopPropagation();
            setDropdownVisible(!dropdown?.classList.contains('show'));
        });

        document.addEventListener('click', (e) => {
            if (!dropdown) return;
            if (!dropdown.contains(e.target) && !toggle?.contains(e.target)) {
                setDropdownVisible(false);
            }
        });

        groupItems.forEach((groupItem) => {
            const toggleBtn = groupItem.querySelector('.group-toggle');
            toggleBtn?.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                const isCollapsed = groupItem.classList.contains('is-collapsed');
                setGroupCollapsed(groupItem, !isCollapsed);
            });
        });

        searchInput?.addEventListener('input', () => {
            const query = (searchInput.value || '').trim().toLowerCase();
            groupItems.forEach((groupItem) => {
                const titleEl = groupItem.querySelector('.item-title');
                const titleText = (titleEl?.textContent || '').trim().toLowerCase();
                const memberRows = Array.from(groupItem.querySelectorAll('.member-sublist li'));
                const isGroupMatch = query !== '' && titleText.includes(query);

                if (query === '') {
                    groupItem.style.display = '';
                    memberRows.forEach((row) => {
                        row.style.display = '';
                    });
                    return;
                }

                let hasMemberMatch = false;
                memberRows.forEach((row) => {
                    const rowText = (row.textContent || '').trim().toLowerCase();
                    const matched = isGroupMatch || rowText.includes(query);
                    row.style.display = matched ? '' : 'none';
                    if (matched) hasMemberMatch = true;
                });

                const isVisible = isGroupMatch || hasMemberMatch;
                groupItem.style.display = isVisible ? '' : 'none';
                if (isVisible) {
                    setGroupCollapsed(groupItem, false);
                }
            });
        });

        const updateSelectAllState = () => {
            if (!selectAll) return;
            const allChecks = [...groupChecks, ...memberChecks];
            const checked = allChecks.filter((el) => el.checked).length;
            selectAll.checked = allChecks.length > 0 && checked === allChecks.length;
            selectAll.indeterminate = checked > 0 && checked < allChecks.length;

            groupChecks.forEach((groupCheck) => {
                const groupKey = groupCheck.getAttribute('data-group-key') || '';
                const members = getMemberChecksByGroupKey(groupKey);
                if (members.length === 0) {
                    groupCheck.indeterminate = false;
                    return;
                }
                const memberChecked = members.filter((el) => el.checked).length;
                if (memberChecked === 0) {
                    groupCheck.checked = false;
                    groupCheck.indeterminate = false;
                    return;
                }
                if (memberChecked === members.length) {
                    groupCheck.checked = true;
                    groupCheck.indeterminate = false;
                    return;
                }
                groupCheck.checked = false;
                groupCheck.indeterminate = true;
            });
        };

        selectAll?.addEventListener('change', () => {
            const checked = selectAll.checked;
            [...groupChecks, ...memberChecks].forEach((el) => {
                if (!el.disabled) el.checked = checked;
            });
            updateSelectAllState();
        });

        groupChecks.forEach((item) => {
            item.addEventListener('change', () => {
                const groupKey = item.getAttribute('data-group-key') || '';
                const members = getMemberChecksByGroupKey(groupKey);
                members.forEach((member) => {
                    if (!member.disabled) {
                        member.checked = item.checked;
                        syncMemberByPid(member.value || '', item.checked, member);
                    }
                });
                const parentGroup = item.closest('.item-group');
                if (item.checked) {
                    setGroupCollapsed(parentGroup, false);
                }
                item.indeterminate = false;
                updateSelectAllState();
            });
        });
        memberChecks.forEach((item) => {
            item.addEventListener('change', () => {
                syncMemberByPid(item.value || '', item.checked, item);
                updateSelectAllState();
            });
        });

        // Keep pre-selected group behavior consistent: when a group is selected,
        // treat all members in that group as selected.
        groupChecks.forEach((item) => {
            if (!item.checked) return;
            const groupKey = item.getAttribute('data-group-key') || '';
            const members = getMemberChecksByGroupKey(groupKey);
            members.forEach((member) => {
                member.checked = true;
                syncMemberByPid(member.value || '', true, member);
            });
        });
        updateSelectAllState();

        const btnSend = document.getElementById('btnSendNotice');
        const confirmModal = document.getElementById('confirmModal');
        const confirmYes = document.getElementById('btnConfirmYes');
        const confirmNo = document.getElementById('btnConfirmNo');

        btnSend?.addEventListener('click', (e) => {
            e.preventDefault();
            confirmModal?.classList.add('active');
        });
        confirmNo?.addEventListener('click', () => confirmModal?.classList.remove('active'));
        confirmModal?.addEventListener('click', (e) => {
            if (e.target === confirmModal) confirmModal.classList.remove('active');
        });
        confirmYes?.addEventListener('click', () => form?.submit());

        const recipientModal = document.getElementById('recipientModal');
        const recipientTableBody = document.getElementById('recipientTableBody');
        const btnShowRecipients = document.getElementById('btnShowRecipients');
        const closeRecipients = document.getElementById('closeModalBtn');

        const renderRecipients = () => {
            if (!recipientTableBody) return;
            recipientTableBody.innerHTML = '';
            const checkedGroups = groupChecks.filter((item) => item.checked);
            const checkedMembers = memberChecks.filter((item) => item.checked);
            if (checkedGroups.length === 0 && checkedMembers.length === 0) {
                const row = document.createElement('tr');
                row.innerHTML = '<td colspan=\"3\" style=\"text-align:center; padding: 16px;\">ไม่มีผู้รับที่เลือก</td>';
                recipientTableBody.appendChild(row);
                return;
            }

            const recipientsMap = new Map();
            const addRecipient = (pid, name, faction) => {
                const key = String(pid || '').trim();
                if (key === '') return;
                if (recipientsMap.has(key)) return;
                recipientsMap.set(key, {
                    pid: key,
                    name: (name || '-').trim() || '-',
                    faction: (faction || '-').trim() || '-',
                });
            };

            checkedGroups.forEach((item) => {
                let members = [];
                try {
                    members = JSON.parse(item.getAttribute('data-members') || '[]');
                } catch (error) {
                    members = [];
                }
                if (!Array.isArray(members)) return;
                members.forEach((member) => {
                    addRecipient(member && member.pID ? String(member.pID) : '', member && member.name ? String(member.name) : '-', item.getAttribute('data-group-label') || '-');
                });
            });

            checkedMembers.forEach((item) => {
                addRecipient(item.value || '', item.getAttribute('data-member-name') || '-', item.getAttribute('data-group-label') || '-');
            });

            const uniqueRecipients = Array.from(recipientsMap.values());
            uniqueRecipients.sort((a, b) => {
                if (a.faction === b.faction) {
                    return a.name.localeCompare(b.name, 'th');
                }
                return a.faction.localeCompare(b.faction, 'th');
            });

            uniqueRecipients.forEach((recipient, index) => {
                const row = document.createElement('tr');
                row.innerHTML = `<td>${index + 1}</td><td>${recipient.name}</td><td>${recipient.faction}</td>`;
                recipientTableBody.appendChild(row);
            });
        };

        btnShowRecipients?.addEventListener('click', () => {
            renderRecipients();
            recipientModal?.classList.add('active');
        });
        closeRecipients?.addEventListener('click', () => recipientModal?.classList.remove('active'));
        recipientModal?.addEventListener('click', (e) => {
            if (e.target === recipientModal) recipientModal.classList.remove('active');
        });
    });
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';

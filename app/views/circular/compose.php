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
$sent_items = (array) ($sent_items ?? []);
$filter_query = (string) ($filter_query ?? '');
$filter_status = (string) ($filter_status ?? 'all');
$filter_sort = (string) ($filter_sort ?? 'newest');
$page = (int) ($page ?? 1);
$per_page = (int) ($per_page ?? 10);
$total_pages = (int) ($total_pages ?? 1);
$filtered_total = (int) ($filtered_total ?? count($sent_items));
$query_params = (array) ($query_params ?? []);
$active_tab = (string) ($active_tab ?? 'compose');
$is_track_active = $active_tab === 'track';
$read_stats_map = (array) ($read_stats_map ?? []);
$detail_map = (array) ($detail_map ?? []);
$receipt_circular_id = (int) ($receipt_circular_id ?? 0);
$receipt_subject = (string) ($receipt_subject ?? '');
$receipt_sender_faction = (string) ($receipt_sender_faction ?? '');
$receipt_stats = (array) ($receipt_stats ?? []);

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

$status_map = [
    INTERNAL_STATUS_DRAFT => ['label' => 'ร่าง', 'pill' => 'pending'],
    INTERNAL_STATUS_SENT => ['label' => 'ส่งแล้ว', 'pill' => 'approved'],
    INTERNAL_STATUS_RECALLED => ['label' => 'ดึงกลับ', 'pill' => 'rejected'],
    INTERNAL_STATUS_ARCHIVED => ['label' => 'จัดเก็บ', 'pill' => 'approved'],
    EXTERNAL_STATUS_SUBMITTED => ['label' => 'รับเข้าแล้ว', 'pill' => 'pending'],
    EXTERNAL_STATUS_PENDING_REVIEW => ['label' => 'รอพิจารณา', 'pill' => 'pending'],
    EXTERNAL_STATUS_REVIEWED => ['label' => 'พิจารณาแล้ว', 'pill' => 'approved'],
    EXTERNAL_STATUS_FORWARDED => ['label' => 'ส่งแล้ว', 'pill' => 'approved'],
];

$thai_months = [
    1 => 'ม.ค.',
    2 => 'ก.พ.',
    3 => 'มี.ค.',
    4 => 'เม.ย.',
    5 => 'พ.ค.',
    6 => 'มิ.ย.',
    7 => 'ก.ค.',
    8 => 'ส.ค.',
    9 => 'ก.ย.',
    10 => 'ต.ค.',
    11 => 'พ.ย.',
    12 => 'ธ.ค.',
];

$thai_months_full = [
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

$format_thai_datetime = static function (?string $date_value) use ($thai_months): string {
    if ($date_value === null || trim($date_value) === '') {
        return '-';
    }

    $timestamp = strtotime($date_value);

    if ($timestamp === false) {
        return $date_value;
    }

    $day = (int) date('j', $timestamp);
    $month = (int) date('n', $timestamp);
    $year = (int) date('Y', $timestamp) + 543;
    $month_label = $thai_months[$month] ?? '';

    return $day . ' ' . $month_label . ' ' . $year . ' ' . date('H:i', $timestamp) . ' น.';
};

$format_thai_date_long = static function (?string $date_value) use ($thai_months_full): string {
    if ($date_value === null || trim($date_value) === '') {
        return '-';
    }

    $timestamp = strtotime($date_value);

    if ($timestamp === false) {
        return $date_value;
    }

    $day = (int) date('j', $timestamp);
    $month = (int) date('n', $timestamp);
    $year = (int) date('Y', $timestamp) + 543;
    $month_label = $thai_months_full[$month] ?? '';

    if ($month_label === '') {
        return $date_value;
    }

    return $day . ' ' . $month_label . ' พ.ศ.' . $year;
};

$build_track_url = static function (array $override = []) use ($query_params): string {
    $params = array_merge($query_params, $override);

    foreach ($params as $key => $value) {
        if ($value === null || $value === '') {
            unset($params[$key]);
        }
    }
    $query = http_build_query($params);

    return 'circular-compose.php' . ($query !== '' ? ('?' . $query) : '');
};

$build_url = $build_track_url;
$receipt_total = count($receipt_stats);
$receipt_read = 0;

foreach ($receipt_stats as $stat) {
    if ((int) ($stat['isRead'] ?? 0) === 1) {
        $receipt_read++;
    }
}
$receipt_unread = max(0, $receipt_total - $receipt_read);

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

    /* -------------------------------------------------------- */
    .circular-my-summary-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
    }

    .circular-my-summary-card {
        border: 1px solid rgba(var(--rgb-secondary), 0.16);
        border-radius: 12px;
        padding: 12px 14px;
        background: linear-gradient(180deg, #fbfdff 0%, #f3f7ff 100%);
    }

    .circular-my-summary-card p {
        margin: 0;
        font-size: var(--font-size-desc-2);
        color: var(--color-neutral-medium);
        font-weight: 600;
    }

    .circular-my-summary-card h3 {
        margin: 4px 0 0;
        font-size: 24px;
        line-height: 1.1;
        color: var(--color-secondary);
    }

    .circular-my-filter-grid {
        display: flex;
        /* grid-template-columns: 1.5fr 0.7fr 0.8fr 0.8fr 0.6fr auto auto; */
        gap: 20px;
        align-items: center;
        flex-wrap: wrap;
        flex-direction: row;
        margin: 0 0 40px;
    }

    .circular-my-filter-field {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .circular-my-filter-field label {
        margin: 0;
        font-size: var(--font-size-desc-3);
        color: var(--color-neutral-medium);
        font-weight: 700;
    }

    .circular-my-table-wrap {
        margin-top: 8px;
    }

    .circular-my-table td {
        vertical-align: top;
    }

    .circular-my-table td:nth-child(n+2) {
        vertical-align: middle;
        text-align: center;
    }

    .circular-my-table th:nth-child(n+5) {
        text-align: center;
    }

    .circular-my-subject {
        min-width: 260px;
        max-width: 380px;
        font-weight: 700;
        color: var(--color-secondary);
        line-height: 1.45;
        word-break: break-word;
    }

    .circular-my-meta {
        color: var(--color-neutral-dark);
        font-size: var(--font-size-desc-2);
        margin-top: 2px;
    }

    .circular-my-actions {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 6px;
        min-width: 0px;
    }

    .circular-my-actions form {
        margin: 0;
    }

    .circular-my-actions .btn,
    .circular-my-actions .c-button {
        /* height: 34px; */
        /* padding: 0 12px; */
        /* min-width: auto; */
    }

    .circular-track-modal-host {
        width: 0;
        height: 0;
        padding: 0;
        margin: 0;
        border: 0;
        background: transparent;
        display: block;
        overflow: visible;
    }

    .circular-track-modal-host .modal-overlay-circular-notice-index.outside-person .modal-content {
        width: 95%;
        height: 90%;
    }

    .circular-track-modal-host .modal-overlay-circular-notice-index.outside-person .content-read-sec {
        /* margin: 30px 0 0; */
        padding-top: 30px;
        /* border-top: 1px solid var(--color-secondary); */
    }

    .circular-track-modal-host .modal-overlay-circular-notice-index.outside-person .content-read-sec .custom-table {
        /* margin-top: 10px; */
    }

    .circular-track-modal-host .modal-overlay-circular-notice-index.outside-person .content-read-sec th,
    .circular-track-modal-host .modal-overlay-circular-notice-index.outside-person .content-read-sec td {
        text-align: left;
        vertical-align: middle;
        font-size: var(--font-size-body-2)
    }

    .circular-track-modal-host .modal-overlay-circular-notice-index.outside-person .content-read-sec td:nth-child(2),
    .circular-track-modal-host .modal-overlay-circular-notice-index.outside-person .content-read-sec th:nth-child(2) {
        text-align: center;
    }


    @media (max-width: 1280px) {
        .circular-my-filter-grid {
            grid-template-columns: 1fr 1fr 1fr 1fr;
        }

        .circular-my-filter-grid .circular-my-filter-actions {
            grid-column: span 2;
        }
    }

    @media (max-width: 900px) {
        .circular-my-summary-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .circular-my-filter-grid {
            grid-template-columns: 1fr;
        }

        .circular-my-filter-grid .circular-my-filter-actions {
            grid-column: span 1;
        }
    }
</style>

<div class="tabs-container setting-page">
    <div class="button-container vehicle">
        <button class="tab-btn <?= $is_track_active ? '' : 'active' ?>"
            onclick="openTab('circularComposeForm', event)">ส่งหนังสือเวียน</button>
        <button class="tab-btn <?= $is_track_active ? 'active' : '' ?>" onclick="openTab('circularTrack', event)">ติดตามการส่ง</button>
    </div>
</div>

<form method="POST" enctype="multipart/form-data" data-validate class="tab-content container-circular-notice-sending <?= $is_track_active ? '' : 'active' ?>" id="circularComposeForm">
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

<section class="enterprise-card tab-content <?= $is_track_active ? 'active' : '' ?>" id="circularTrack">
    <div class="enterprise-card-header">
        <div class="enterprise-card-title-group">
            <h2 class="enterprise-card-title">ค้นหาและกรองรายการ</h2>
        </div>
    </div>

    <form method="GET" class="circular-my-filter-grid">
        <input type="hidden" name="tab" value="track">
        <div class="approval-filter-group">
            <div class="room-admin-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input class="form-input" type="search" name="q" value="<?= h($filter_query) ?>"
                    placeholder="ค้นหาชื่อผู้จอง/ห้อง/หัวข้อ" autocomplete="off">
            </div>
            <div class="room-admin-filter">
                <div class="custom-select-wrapper">
                    <div class="custom-select-trigger">
                        <p class="select-value">
                            <?php
                            $status_label = 'ทั้งหมด';

if ($filter_status === strtolower(INTERNAL_STATUS_SENT)) {
    $status_label = 'ส่งแล้ว';
} elseif ($filter_status === strtolower(INTERNAL_STATUS_RECALLED)) {
    $status_label = 'ดึงกลับ';
} elseif ($filter_status === strtolower(EXTERNAL_STATUS_PENDING_REVIEW)) {
    $status_label = 'รอพิจารณา';
} elseif ($filter_status === strtolower(EXTERNAL_STATUS_REVIEWED)) {
    $status_label = 'พิจารณาแล้ว';
}
echo h($status_label);
?>
                        </p>
                        <i class="fa-solid fa-chevron-down"></i>
                    </div>

                    <div class="custom-options">
                        <div class="custom-option" data-value="all">ทั้งหมด</div>
                        <div class="custom-option" data-value="<?= h(strtolower(INTERNAL_STATUS_SENT)) ?>">ส่งแล้ว</div>
                        <div class="custom-option" data-value="<?= h(strtolower(INTERNAL_STATUS_RECALLED)) ?>">ดึงกลับ</div>
                        <div class="custom-option" data-value="<?= h(strtolower(EXTERNAL_STATUS_PENDING_REVIEW)) ?>">รอพิจารณา</div>
                        <div class="custom-option" data-value="<?= h(strtolower(EXTERNAL_STATUS_REVIEWED)) ?>">พิจารณาแล้ว</div>
                    </div>

                    <select class="form-input" name="status">
                        <option value="all" <?= $filter_status === 'all' ? 'selected' : '' ?>>ทั้งหมด</option>
                        <option value="<?= h(strtolower(INTERNAL_STATUS_SENT)) ?>" <?= $filter_status === strtolower(INTERNAL_STATUS_SENT) ? 'selected' : '' ?>>ส่งแล้ว</option>
                        <option value="<?= h(strtolower(INTERNAL_STATUS_RECALLED)) ?>" <?= $filter_status === strtolower(INTERNAL_STATUS_RECALLED) ? 'selected' : '' ?>>ดึงกลับ</option>
                        <option value="<?= h(strtolower(EXTERNAL_STATUS_PENDING_REVIEW)) ?>" <?= $filter_status === strtolower(EXTERNAL_STATUS_PENDING_REVIEW) ? 'selected' : '' ?>>รอพิจารณา</option>
                        <option value="<?= h(strtolower(EXTERNAL_STATUS_REVIEWED)) ?>" <?= $filter_status === strtolower(EXTERNAL_STATUS_REVIEWED) ? 'selected' : '' ?>>พิจารณาแล้ว</option>
                    </select>
                </div>
            </div>
            <div class="room-admin-filter">
                <div class="custom-select-wrapper">
                    <div class="custom-select-trigger">
                        <p class="select-value"><?= h($filter_sort === 'oldest' ? 'เก่าไปใหม่' : 'ใหม่ไปเก่า') ?></p>
                        <i class="fa-solid fa-chevron-down"></i>
                    </div>

                    <div class="custom-options">
                        <div class="custom-option" data-value="newest">ใหม่ไปเก่า</div>
                        <div class="custom-option" data-value="oldest">เก่าไปใหม่</div>
                    </div>

                    <select class="form-input" name="sort">
                        <option value="newest" <?= $filter_sort === 'newest' ? 'selected' : '' ?>>ใหม่ไปเก่า</option>
                        <option value="oldest" <?= $filter_sort === 'oldest' ? 'selected' : '' ?>>เก่าไปใหม่</option>
                    </select>
                </div>
            </div>
        </div>
    </form>

    <div class="enterprise-card-header">
        <div class="enterprise-card-title-group">
            <h2 class="enterprise-card-title">รายการหนังสือเวียนของฉัน</h2>
        </div>
    </div>

    <div class="table-responsive circular-my-table-wrap">
        <table class="custom-table circular-my-table">
            <thead>
                <tr>
                    <th>เรื่อง</th>
                    <th>สถานะ</th>
                    <th>อ่านแล้ว/ทั้งหมด</th>
                    <th>วันที่ส่ง</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($sent_items)) : ?>
                    <tr>
                        <td colspan="5" class="enterprise-empty">ไม่มีรายการหนังสือเวียนตามเงื่อนไข</td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($sent_items as $item) : ?>
                        <?php
                        $circular_id = (int) ($item['circularID'] ?? 0);
                        $status_key = strtoupper(trim((string) ($item['status'] ?? '')));
                        $status_meta = $status_map[$status_key] ?? ['label' => ($status_key !== '' ? $status_key : '-'), 'pill' => 'pending'];
                        $item_type = strtoupper((string) ($item['circularType'] ?? ''));
                        $read_count = (int) ($item['readCount'] ?? 0);
                        $recipient_count = (int) ($item['recipientCount'] ?? 0);
                        $created_at = (string) ($item['createdAt'] ?? '');
                        $date_display = $format_thai_datetime($created_at);
                        $date_long_display = $format_thai_date_long($created_at);
                        $sender_faction_name = (string) ($item['senderFactionName'] ?? '');
                        $detail_row = (array) ($detail_map[$circular_id] ?? []);
                        $detail_text = trim((string) ($detail_row['detail'] ?? ''));
                        $detail_sender_name = trim((string) ($detail_row['senderName'] ?? ''));
                        $detail_sender_faction = trim((string) ($detail_row['senderFactionName'] ?? $sender_faction_name));
                        $attachments = (array) ($detail_row['files'] ?? []);
                        $files_json = json_encode($attachments, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                        if ($files_json === false) {
                            $files_json = '[]';
                        }
                        $consider_class = 'considering';

                        if (in_array($status_key, [INTERNAL_STATUS_RECALLED], true)) {
                            $consider_class = 'considered';
                        } elseif (in_array($status_key, [INTERNAL_STATUS_SENT, INTERNAL_STATUS_ARCHIVED, EXTERNAL_STATUS_REVIEWED, EXTERNAL_STATUS_FORWARDED], true)) {
                            $consider_class = 'success';
                        }
                        $stats_rows = [];
                        $has_any_read = $read_count > 0;

                        foreach ((array) ($read_stats_map[$circular_id] ?? []) as $stat) {
                            $is_read = (int) ($stat['isRead'] ?? 0) === 1;

                            if ($is_read) {
                                $has_any_read = true;
                            }
                            $stats_rows[] = [
                                'name' => (string) ($stat['fName'] ?? '-'),
                                'status' => $is_read ? 'อ่านแล้ว' : 'ยังไม่อ่าน',
                                'pill' => $is_read ? 'approved' : 'pending',
                                'readAt' => $is_read ? $format_thai_datetime((string) ($stat['readAt'] ?? '')) : '-',
                            ];
                        }
                        $stats_json = json_encode($stats_rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                        if ($stats_json === false) {
                            $stats_json = '[]';
                        }
                        ?>
                        <tr>
                            <td>
                                <div class="circular-my-subject"><?= h((string) ($item['subject'] ?? '-')) ?></div>
                                <?php if (!empty($item['senderFactionName'])) : ?>
                                    <div class="circular-my-meta">ในนาม <?= h((string) $item['senderFactionName']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-pill <?= h((string) ($status_meta['pill'] ?? 'pending')) ?>"><?= h((string) ($status_meta['label'] ?? '-')) ?></span>
                            </td>
                            <td><?= h((string) $read_count) ?>/<?= h((string) $recipient_count) ?></td>
                            <td><?= h($date_display) ?></td>
                            <td>
                                <div class="circular-my-actions">
                                    <?php if ($item_type === 'INTERNAL' && $status_key === INTERNAL_STATUS_SENT && !$has_any_read) : ?>
                                        <form method="POST">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="recall">
                                            <input type="hidden" name="circular_id" value="<?= h((string) $circular_id) ?>">
                                            <button type="submit" class="booking-action-btn secondary">
                                                <i class="fa-solid fa-rotate-left"></i>
                                                <span class="tooltip">ดึงกลับ</span>
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($item_type === 'EXTERNAL' && $status_key === EXTERNAL_STATUS_PENDING_REVIEW && !$has_any_read) : ?>
                                        <form method="POST">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="recall_external">
                                            <input type="hidden" name="circular_id" value="<?= h((string) $circular_id) ?>">
                                            <button type="submit" class="booking-action-btn secondary">
                                                <i class="fa-solid fa-rotate-left"></i>
                                                <span class="tooltip">ดึงกลับ</span>
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($item_type === 'INTERNAL' && $status_key === INTERNAL_STATUS_RECALLED) : ?>
                                        <form method="POST">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="resend">
                                            <input type="hidden" name="circular_id" value="<?= h((string) $circular_id) ?>">
                                            <button type="submit" class="booking-action-btn secondary">
                                                <i class="fa-solid fa-arrow-right-from-bracket"></i>
                                                <span class="tooltip">ส่งใหม่</span>
                                            </button>
                                        </form>
                                        <a class="c-button c-button--sm booking-action-btn secondary" href="circular-compose.php?edit=<?= h((string) $circular_id) ?>">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                            <span class="tooltip">แก้ไข</span>
                                        </a>
                                    <?php endif; ?>

                                    <button
                                        class="booking-action-btn secondary js-open-circular-modal"
                                        type="button"
                                        data-circular-id="<?= h((string) $circular_id) ?>"
                                        data-type="<?= h($item_type) ?>"
                                        data-subject="<?= h((string) ($item['subject'] ?? '-')) ?>"
                                        data-detail="<?= h($detail_text) ?>"
                                        data-sender-name="<?= h($detail_sender_name !== '' ? $detail_sender_name : $sender_name) ?>"
                                        data-sender-faction="<?= h($detail_sender_faction !== '' ? $detail_sender_faction : $sender_faction_display) ?>"
                                        data-bookno="<?= h('#' . (string) $circular_id) ?>"
                                        data-issued="<?= h($date_long_display) ?>"
                                        data-from="<?= h(($detail_sender_name !== '' ? $detail_sender_name : $sender_name) . (($detail_sender_faction !== '' ? $detail_sender_faction : $sender_faction_display) !== '' ? (' / ' . ($detail_sender_faction !== '' ? $detail_sender_faction : $sender_faction_display)) : '')) ?>"
                                        data-to="<?= h('ผู้รับทั้งหมด ' . (string) $recipient_count . ' คน') ?>"
                                        data-status="<?= h((string) ($status_meta['label'] ?? '-')) ?>"
                                        data-consider="<?= h($consider_class) ?>"
                                        data-received-time="<?= h($date_display) ?>"
                                        data-files="<?= h($files_json) ?>"
                                        data-read-stats="<?= h($stats_json) ?>">
                                        <i class="fa-solid fa-eye"></i>
                                        <span class="tooltip">ดูรายละเอียด</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php
    // $pagination_url = $build_url(['page' => null]);
    // component_render('pagination', [
    //     'page' => $page,
    //     'total_pages' => $total_pages,
    //     'base_url' => $pagination_url,
    //     'class' => 'u-mt-2',
    // ]);
?>
</section>

<div class="content-circular-notice-index circular-track-modal-host">
    <div class="modal-overlay-circular-notice-index outside-person" id="modalNoticeKeepOverlay">
        <div class="modal-content">
            <div class="header-modal">
                <div class="first-header">
                    <p>แสดงข้อความรายละเอียดหนังสือเวียน</p>
                </div>
                <div class="sec-header">
                    <i class="fa-solid fa-xmark" id="closeModalNoticeKeep"></i>
                </div>
            </div>

            <div class="content-modal">

                <div class="content-topic-sec">
                    <div class="more-details">
                        <p><strong>ลงวันที่</strong></p>
                        <input type="text" id="modalIssuedDate" placeholder="-" disabled>
                    </div>
                </div>

                <div class="content-topic-sec">
                    <div class="more-details">
                        <p><strong>จาก</strong></p>
                        <input type="text" id="modalFromText" placeholder="-" disabled>
                    </div>
                </div>

                <div class="content-details-sec">
                    <p><strong>หัวเรื่อง :</strong></p>
                    <p id="modalSubject">-</p>
                </div>
                <div class="content-details-sec">
                    <p><strong>รายละเอียดเพิ่มเติม</strong></p>
                    <p id="modalDetail">-</p>
                </div>

                <div class="content-file-sec">
                    <p><strong>ไฟล์เอกสารแนบจากระบบ</strong></p>
                    <div class="file-section" id="modalFileSection"></div>
                </div>

                <div class="content-read-sec">
                    <p><strong>สถานะการอ่านรายบุคคล</strong></p>
                    <div class="table-responsive">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>ชื่อผู้รับ</th>
                                    <th>สถานะ</th>
                                    <th>เวลาอ่านล่าสุด</th>
                                </tr>
                            </thead>
                            <tbody id="receiptStatusTableBody">
                                <tr>
                                    <td colspan="3" class="enterprise-empty">ไม่พบข้อมูลผู้รับ</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('circularComposeForm');
        const trackFilterForm = document.querySelector('#circularTrack form.circular-my-filter-grid');
        const trackSearchInput = trackFilterForm ? trackFilterForm.querySelector('input[name="q"]') : null;
        const trackStatusSelect = trackFilterForm ? trackFilterForm.querySelector('select[name="status"]') : null;
        const trackSortSelect = trackFilterForm ? trackFilterForm.querySelector('select[name="sort"]') : null;
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
        let trackSearchTimer = null;

        if (trackSearchInput && trackFilterForm) {
            trackSearchInput.addEventListener('input', () => {
                if (trackSearchTimer) {
                    clearTimeout(trackSearchTimer);
                }
                trackSearchTimer = window.setTimeout(() => {
                    trackFilterForm.submit();
                }, 300);
            });
        }
        trackStatusSelect?.addEventListener('change', () => trackFilterForm?.submit());
        trackSortSelect?.addEventListener('change', () => trackFilterForm?.submit());

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
        const categoryGroups = Array.from(document.querySelectorAll('.dropdown-list .category-group'));

        const normalizeSearchText = (value) => String(value || '')
            .toLowerCase()
            .replace(/\s+/g, '')
            .replace(/[^0-9a-z\u0E00-\u0E7F]/gi, '');

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
            const clickedInput = e.target instanceof HTMLElement && (
                e.target.matches('input.search-input') ||
                !!e.target.closest('input.search-input')
            );
            if (clickedInput) {
                setDropdownVisible(true);
                return;
            }
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

        const filterRecipientDropdown = (rawQuery, remoteMatchedPids = null) => {
            const query = normalizeSearchText(rawQuery);
            groupItems.forEach((groupItem) => {
                const titleEl = groupItem.querySelector('.item-title');
                const titleText = normalizeSearchText(titleEl?.textContent || '');
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
                    const memberCheckbox = row.querySelector('.member-checkbox');
                    const memberPid = String(memberCheckbox?.value || '').trim();
                    const isRemoteMatched = remoteMatchedPids instanceof Set ?
                        remoteMatchedPids.has(memberPid) :
                        null;
                    const rowText = normalizeSearchText(row.textContent || '');
                    const matchedByText = rowText.includes(query);
                    const matched = isGroupMatch || matchedByText || isRemoteMatched === true;
                    row.style.display = matched ? '' : 'none';
                    if (matched) hasMemberMatch = true;
                });

                const isVisible = isGroupMatch || hasMemberMatch;
                groupItem.style.display = isVisible ? '' : 'none';
                if (isVisible) {
                    setGroupCollapsed(groupItem, false);
                }
            });

            categoryGroups.forEach((category) => {
                const hasVisibleItem = Array.from(category.querySelectorAll('.category-items .item-group'))
                    .some((item) => item.style.display !== 'none');
                category.style.display = hasVisibleItem ? '' : 'none';
            });
        };

        let recipientSearchTimer = null;
        let recipientSearchRequestNo = 0;
        const recipientSearchEndpoint = 'public/api/circular-recipient-search.php';

        const requestRecipientSearch = (query) => {
            const requestNo = ++recipientSearchRequestNo;
            const url = `${recipientSearchEndpoint}?q=${encodeURIComponent(query)}`;
            return fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                })
                .then((response) => {
                    if (!response.ok) {
                        throw new Error('search_failed');
                    }
                    return response.json();
                })
                .then((payload) => {
                    if (requestNo !== recipientSearchRequestNo) {
                        return;
                    }
                    const pids = Array.isArray(payload?.pids) ? payload.pids : [];
                    filterRecipientDropdown(query, new Set(pids.map((pid) => String(pid))));
                })
                .catch(() => {
                    if (requestNo !== recipientSearchRequestNo) {
                        return;
                    }
                    filterRecipientDropdown(query);
                });
        };

        searchInput?.addEventListener('focus', () => {
            setDropdownVisible(true);
        });

        searchInput?.addEventListener('input', () => {
            setDropdownVisible(true);
            const query = String(searchInput.value || '').trim();
            if (recipientSearchTimer) {
                clearTimeout(recipientSearchTimer);
            }
            if (query === '') {
                recipientSearchRequestNo++;
                filterRecipientDropdown('');
                return;
            }
            recipientSearchTimer = window.setTimeout(() => {
                requestRecipientSearch(query);
            }, 180);
        });

        searchInput?.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                setDropdownVisible(false);
            }
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

        const detailModal = document.getElementById('modalNoticeKeepOverlay');
        const closeDetailModalBtn = document.getElementById('closeModalNoticeKeep');
        const openDetailBtns = document.querySelectorAll('.js-open-circular-modal');
        const modalUrgency = document.getElementById('modalUrgency');
        const modalBookNo = document.getElementById('modalBookNo');
        const modalIssuedDate = document.getElementById('modalIssuedDate');
        const modalFromText = document.getElementById('modalFromText');
        const modalToText = document.getElementById('modalToText');
        const modalSubject = document.getElementById('modalSubject');
        const modalDetail = document.getElementById('modalDetail');
        const modalFileSection = document.getElementById('modalFileSection');
        const modalReceivedTime = document.getElementById('modalReceivedTime');
        const modalStatus = document.getElementById('modalStatus');
        const modalConsiderStatus = document.getElementById('modalConsiderStatus');
        const receiptStatusTableBody = document.getElementById('receiptStatusTableBody');

        const buildModalFileItem = (file, entityId) => {
            const container = document.createElement('div');
            container.className = 'file-banner';

            const info = document.createElement('div');
            info.className = 'file-info';

            const iconWrap = document.createElement('div');
            iconWrap.className = 'file-icon';
            const icon = document.createElement('i');
            const mime = String(file?.mimeType || '').toLowerCase();
            if (mime.includes('pdf')) {
                icon.className = 'fa-solid fa-file-pdf';
            } else if (mime.includes('image')) {
                icon.className = 'fa-solid fa-file-image';
            } else {
                icon.className = 'fa-solid fa-file';
            }
            iconWrap.appendChild(icon);

            const text = document.createElement('div');
            text.className = 'file-text';
            const nameEl = document.createElement('span');
            nameEl.className = 'file-name';
            nameEl.textContent = String(file?.fileName || '-');
            const typeEl = document.createElement('span');
            typeEl.className = 'file-type';
            typeEl.textContent = String(file?.mimeType || '');
            text.appendChild(nameEl);
            text.appendChild(typeEl);

            info.appendChild(iconWrap);
            info.appendChild(text);

            const viewAction = document.createElement('div');
            viewAction.className = 'file-actions';
            const viewLink = document.createElement('a');
            viewLink.href = 'public/api/file-download.php?module=circulars&entity_id=' +
                encodeURIComponent(String(entityId || '')) +
                '&file_id=' + encodeURIComponent(String(file?.fileID || ''));
            viewLink.target = '_blank';
            viewLink.rel = 'noopener';
            viewLink.innerHTML = '<i class="fa-solid fa-eye"></i>';
            viewAction.appendChild(viewLink);

            const downloadAction = document.createElement('div');
            downloadAction.className = 'file-actions';
            const downloadLink = document.createElement('a');
            downloadLink.href = 'public/api/file-download.php?module=circulars&entity_id=' +
                encodeURIComponent(String(entityId || '')) +
                '&file_id=' + encodeURIComponent(String(file?.fileID || '')) +
                '&download=1';
            downloadLink.innerHTML = '<i class="fa-solid fa-download"></i>';
            downloadAction.appendChild(downloadLink);

            container.appendChild(info);
            container.appendChild(viewAction);
            container.appendChild(downloadAction);
            return container;
        };

        const renderModalFiles = (files, entityId) => {
            if (!modalFileSection) return;
            modalFileSection.innerHTML = '';

            if (!Array.isArray(files) || files.length === 0) {
                modalFileSection.innerHTML = '<div class="content-details-sec" style="margin: 0;"><p id="modalDetail">-</p></div>';
                return;
            }

            files.forEach((file) => {
                modalFileSection.appendChild(buildModalFileItem(file, entityId));
            });
        };

        const renderReceiptRows = (stats) => {
            if (!receiptStatusTableBody) return;
            receiptStatusTableBody.innerHTML = '';

            if (!Array.isArray(stats) || stats.length === 0) {
                const emptyRow = document.createElement('tr');
                emptyRow.innerHTML = '<td colspan="3" class="enterprise-empty">ไม่พบข้อมูลผู้รับ</td>';
                receiptStatusTableBody.appendChild(emptyRow);
                return;
            }

            stats.forEach((item) => {
                const name = String(item?.name || '-');
                const statusText = String(item?.status || 'ยังไม่อ่าน');
                const pill = String(item?.pill || 'pending');
                const readAt = String(item?.readAt || '-');
                const row = document.createElement('tr');
                const nameTd = document.createElement('td');
                nameTd.textContent = name;

                const statusTd = document.createElement('td');
                const statusSpan = document.createElement('span');
                statusSpan.className = `status-pill ${pill}`;
                statusSpan.textContent = statusText;
                statusTd.appendChild(statusSpan);

                const readAtTd = document.createElement('td');
                readAtTd.textContent = readAt;

                row.appendChild(nameTd);
                row.appendChild(statusTd);
                row.appendChild(readAtTd);
                receiptStatusTableBody.appendChild(row);
            });
        };

        openDetailBtns.forEach((btn) => {
            btn.addEventListener('click', (event) => {
                event.preventDefault();
                const circularType = String(btn.getAttribute('data-type') || 'INTERNAL').toUpperCase();
                const circularId = String(btn.getAttribute('data-circular-id') || '').trim();
                const detail = String(btn.getAttribute('data-detail') || '').trim();
                const subject = String(btn.getAttribute('data-subject') || '').trim();
                const bookNo = String(btn.getAttribute('data-bookno') || '-').trim();
                const issuedDate = String(btn.getAttribute('data-issued') || '-').trim();
                const fromText = String(btn.getAttribute('data-from') || '-').trim();
                const toText = String(btn.getAttribute('data-to') || '-').trim();
                const statusText = String(btn.getAttribute('data-status') || '-').trim();
                const considerClass = String(btn.getAttribute('data-consider') || 'considering').trim() || 'considering';
                const receivedTime = String(btn.getAttribute('data-received-time') || '-').trim();
                let stats = [];
                let files = [];
                try {
                    stats = JSON.parse(String(btn.getAttribute('data-read-stats') || '[]'));
                } catch (error) {
                    stats = [];
                }
                try {
                    files = JSON.parse(String(btn.getAttribute('data-files') || '[]'));
                } catch (error) {
                    files = [];
                }

                if (modalUrgency) {
                    modalUrgency.className = 'urgency-status normal';
                    const typeLabel = circularType === 'EXTERNAL' ? 'ภายนอก' : 'ภายใน';
                    const urgencyLabel = modalUrgency.querySelector('p');
                    if (urgencyLabel) {
                        urgencyLabel.textContent = typeLabel;
                    }
                }
                if (modalBookNo) modalBookNo.value = bookNo !== '' ? bookNo : '-';
                if (modalIssuedDate) modalIssuedDate.value = issuedDate !== '' ? issuedDate : '-';
                if (modalFromText) modalFromText.value = fromText !== '' ? fromText : '-';
                if (modalToText) modalToText.value = toText !== '' ? toText : '-';
                if (modalSubject) modalSubject.textContent = subject !== '' ? subject : '-';
                if (modalDetail) modalDetail.textContent = detail !== '' ? detail : '-';
                if (modalReceivedTime) modalReceivedTime.value = receivedTime !== '' ? receivedTime : '-';
                if (modalStatus) modalStatus.value = statusText !== '' ? statusText : '-';

                if (modalConsiderStatus) {
                    modalConsiderStatus.className = `consider-status ${considerClass}`;
                    modalConsiderStatus.textContent = statusText !== '' ? statusText : '-';
                }

                renderModalFiles(files, circularId);
                renderReceiptRows(stats);

                if (detailModal) {
                    detailModal.style.display = 'flex';
                }
            });
        });

        closeDetailModalBtn?.addEventListener('click', () => {
            if (detailModal) {
                detailModal.style.display = 'none';
            }
        });
        detailModal?.addEventListener('click', (event) => {
            if (event.target === detailModal) {
                detailModal.style.display = 'none';
            }
        });
    });
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';

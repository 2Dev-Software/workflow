<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../auth/csrf.php';

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


$values = (array) ($values ?? []);
$values = array_merge([
    'extPriority' => 'ปกติ',
    'extBookNo' => '',
    'extIssuedDate' => '',
    'subject' => '',
    'extFromText' => '',
    'extGroupFID' => '',
    'linkURL' => '',
    'detail' => '',
    'reviewerPID' => '',
], $values);
$factions = (array) ($factions ?? []);
$reviewers = (array) ($reviewers ?? []);
$is_edit_mode = (bool) ($is_edit_mode ?? false);
$edit_circular_id = (int) ($edit_circular_id ?? 0);
$existing_attachments = (array) ($existing_attachments ?? []);
$has_reviewer_options = !empty($reviewers);

$selected_group_name = 'เลือกกลุ่ม/ฝ่าย';

foreach ($factions as $faction_item) {
    if ((string) ($faction_item['fID'] ?? '') === (string) ($values['extGroupFID'] ?? '')) {
        $selected_group_name = (string) ($faction_item['fName'] ?? $selected_group_name);
        break;
    }
}

$selected_reviewer_name = 'เลือกผู้พิจารณา';

foreach ($reviewers as $reviewer_item) {
    if ((string) ($reviewer_item['pID'] ?? '') === (string) ($values['reviewerPID'] ?? '')) {
        $selected_reviewer_name = (string) ($reviewer_item['label'] ?? $selected_reviewer_name);
        break;
    }
}

ob_start();
?>
<style>
    .circular-my-filter-grid {
        display: flex;
        /* grid-template-columns: 1.5fr 0.7fr 0.8fr 0.8fr 0.6fr auto auto; */
        gap: 20px;
        align-items: center;
        flex-wrap: wrap;
        flex-direction: row;
        margin: 0 0 40px;
    }

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
<div class="content-header">
    <h1>ยินดีต้อนรับ</h1>
    <p>ลงทะเบียนรับหนังสือเวียนภายนอก</p>
</div>

<div class="tabs-container setting-page">
    <div class="button-container vehicle">
        <button class="tab-btn <?= $is_track_active ? '' : 'active' ?>"
            onclick="openTab('register', event)">ลงทะเบียนรับ</button>
        <button class="tab-btn <?= $is_track_active ? 'active' : '' ?>" onclick="openTab('trackBook', event)">ติดตามหนังสือ</button>
    </div>
</div>

<div class="content-outgoing tab-content active" id="register">
    <form action="" method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <?php if ($is_edit_mode && $edit_circular_id > 0) : ?>
            <input type="hidden" name="edit_circular_id" value="<?= h((string) $edit_circular_id) ?>">
        <?php endif; ?>

        <?php if ($is_edit_mode) : ?>
            <div class="enterprise-panel">
                <p><strong>โหมดแก้ไข:</strong> เอกสารถูกดึงกลับแล้ว สามารถแก้ไขและส่งใหม่ก่อนผู้บริหารพิจารณา</p>
            </div>
        <?php endif; ?>

        <div class="type-urgent">
            <p><strong>ประเภท: </strong></p>
            <div class="radio-group-urgent">
                <input type="radio" name="extPriority" value="ปกติ" <?= (string) $values['extPriority'] === 'ปกติ' ? 'checked' : '' ?>>
                <p class="urgency-status normal">ปกติ</p>
                <input type="radio" name="extPriority" value="ด่วน" <?= (string) $values['extPriority'] === 'ด่วน' ? 'checked' : '' ?>>
                <p class="urgency-status urgen">ด่วน</p>
                <input type="radio" name="extPriority" value="ด่วนมาก" <?= (string) $values['extPriority'] === 'ด่วนมาก' ? 'checked' : '' ?>>
                <p class="urgency-status very-urgen">ด่วนมาก</p>
                <input type="radio" name="extPriority" value="ด่วนที่สุด" <?= (string) $values['extPriority'] === 'ด่วนที่สุด' ? 'checked' : '' ?>>
                <p class="urgency-status extremly-urgen">ด่วนที่สุด</p>
            </div>
        </div>

        <div class="form-group-row">
            <div class="input-group">
                <p><strong>เลขที่หนังสือ</strong></p>
                <input type="text" name="extBookNo" value="<?= h((string) $values['extBookNo']) ?>" required>
            </div>
            <div class="input-group">
                <p><strong>ลงวันที่</strong></p>
                <input type="date" name="extIssuedDate" value="<?= h((string) $values['extIssuedDate']) ?>" required>
            </div>
        </div>

        <div class="form-group-row">
            <div class="input-group">
                <p><strong>เรื่อง</strong></p>
                <textarea name="subject" rows="3" required><?= h((string) $values['subject']) ?></textarea>
            </div>
        </div>

        <div class="form-group-row">
            <div class="input-group">
                <p><strong>จาก</strong></p>
                <input type="text" name="extFromText" value="<?= h((string) $values['extFromText']) ?>" required>
            </div>
            <div class="input-group">
                <p><strong>หนังสือของกลุ่ม</strong></p>
                <div class="custom-select-wrapper">
                    <div class="custom-select-trigger">
                        <p class="select-value"><?= h($selected_group_name) ?></p>
                        <i class="fa-solid fa-chevron-down"></i>
                    </div>

                    <div class="custom-options">
                        <div class="custom-option<?= (string) $values['extGroupFID'] === '' ? ' selected' : '' ?>" data-value="">เลือกกลุ่ม/ฝ่าย</div>
                        <?php foreach ($factions as $faction_item) : ?>
                            <?php $fid = (int) ($faction_item['fID'] ?? 0); ?>
                            <?php if ($fid <= 0) {
                                continue;
                            } ?>
                            <div class="custom-option<?= (string) $values['extGroupFID'] === (string) $fid ? ' selected' : '' ?>" data-value="<?= h((string) $fid) ?>"><?= h((string) ($faction_item['fName'] ?? '')) ?></div>
                        <?php endforeach; ?>
                    </div>

                    <select class="form-input" name="extGroupFID">
                        <option value="" <?= (string) $values['extGroupFID'] === '' ? 'selected' : '' ?>>เลือกกลุ่ม/ฝ่าย</option>
                        <?php foreach ($factions as $faction_item) : ?>
                            <?php $fid = (int) ($faction_item['fID'] ?? 0); ?>
                            <?php if ($fid <= 0) {
                                continue;
                            } ?>
                            <option value="<?= h((string) $fid) ?>" <?= (string) $values['extGroupFID'] === (string) $fid ? 'selected' : '' ?>><?= h((string) ($faction_item['fName'] ?? '')) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-group-row">
            <div class="input-group">
                <p><strong>เกษียณหนังสือ: </strong>เรียน ผู้อำนวยการโรงเรียนดีบุกพังงาวิทยายน</p>
                <textarea name="detail" id="memo_editor"><?= h((string) $values['detail']) ?></textarea>
            </div>
        </div>

        <div class="form-group-row">
            <div class="input-group">
                <p><strong>แนบลิงก์ (ถ้ามี)</strong></p>
                <input type="url" name="linkURL" value="<?= h((string) $values['linkURL']) ?>" placeholder="https://example.com">
            </div>
        </div>

        <div class="form-group-row">
            <div class="input-group">
                <p><strong>เสนอ</strong></p>
                <div class="custom-select-wrapper">
                    <div class="custom-select-trigger">
                        <p class="select-value"><?= h($selected_reviewer_name) ?></p>
                        <i class="fa-solid fa-chevron-down"></i>
                    </div>

                    <div class="custom-options">
                        <div class="custom-option<?= (string) $values['reviewerPID'] === '' ? ' selected' : '' ?>" data-value="">เลือกผู้พิจารณา</div>
                        <?php foreach ($reviewers as $reviewer_item) : ?>
                            <?php $reviewer_pid = trim((string) ($reviewer_item['pID'] ?? '')); ?>
                            <?php if ($reviewer_pid === '') {
                                continue;
                            } ?>
                            <div class="custom-option<?= (string) $values['reviewerPID'] === $reviewer_pid ? ' selected' : '' ?>" data-value="<?= h($reviewer_pid) ?>"><?= h((string) ($reviewer_item['label'] ?? '')) ?></div>
                        <?php endforeach; ?>
                    </div>

                    <select class="form-input" name="reviewerPID" required>
                        <option value="" <?= (string) $values['reviewerPID'] === '' ? 'selected' : '' ?>>เลือกผู้พิจารณา</option>
                        <?php foreach ($reviewers as $reviewer_item) : ?>
                            <?php $reviewer_pid = trim((string) ($reviewer_item['pID'] ?? '')); ?>
                            <?php if ($reviewer_pid === '') {
                                continue;
                            } ?>
                            <option value="<?= h($reviewer_pid) ?>" <?= (string) $values['reviewerPID'] === $reviewer_pid ? 'selected' : '' ?>><?= h((string) ($reviewer_item['label'] ?? '')) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if (!$has_reviewer_options) : ?>
                    <p class="form-error" style="display:block;">ไม่พบผู้พิจารณาในระบบ กรุณาติดต่อผู้ดูแลระบบ</p>
                <?php endif; ?>
            </div>
        </div>

        <hr>

        <div class="form-group-row">
            <div class="input-group">
                <p><strong>หนังสือนำ</strong></p>
                <div>
                    <button
                        type="button"
                        class="btn btn-upload-small"
                        onclick="document.getElementById('cover_attachment').click()">
                        <p>เพิ่มไฟล์</p>
                    </button>
                </div>
                <input
                    type="file"
                    id="cover_attachment"
                    name="cover_attachments[]"
                    class="file-input"
                    multiple
                    accept=".pdf,image/png,image/jpeg"
                    hidden>

                <div class="file-list" id="cover_attachmentList" aria-live="polite"></div>
            </div>
        </div>

        <hr>

        <div class="form-group-row">
            <div class="input-group">
                <p><strong>เอกสารแนบ</strong></p>
                <div>
                    <button
                        type="button"
                        class="btn btn-upload-small"
                        onclick="document.getElementById('attachment').click()">
                        <p>เพิ่มไฟล์</p>
                    </button>
                </div>
                <input
                    type="file"
                    id="attachment"
                    name="attachments[]"
                    class="file-input"
                    multiple
                    accept=".pdf,image/png,image/jpeg"
                    hidden>
                <p class="form-error hidden" id="attachmentError">แนบได้สูงสุด 4 ไฟล์</p>

                <div class="file-list" id="attachmentList" aria-live="polite"></div>
            </div>
        </div>

        <?php if ($is_edit_mode && !empty($existing_attachments)) : ?>
            <hr>
            <div class="form-group-row">
                <div class="input-group">
                    <p><strong>ไฟล์แนบเดิม (เลือกเพื่อลบก่อนส่งใหม่)</strong></p>
                    <div class="enterprise-panel">
                        <?php foreach ($existing_attachments as $attachment) : ?>
                            <?php
                            $attachment_file_id = (int) ($attachment['fileID'] ?? 0);
                            $attachment_name = (string) ($attachment['fileName'] ?? '');
                            ?>
                            <label style="display:flex;align-items:center;gap:8px;padding:6px 0;">
                                <input type="checkbox" name="remove_file_ids[]" value="<?= h((string) $attachment_file_id) ?>">
                                <span><?= h($attachment_name !== '' ? $attachment_name : ('ไฟล์ #' . $attachment_file_id)) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <hr>

        <div class="form-group-row">
            <div class="input-group">
                <button class="submit" type="submit" <?= $has_reviewer_options ? '' : 'disabled' ?>>
                    <p><?= $is_edit_mode ? 'บันทึกแก้ไขและส่งใหม่' : 'บันทึกเอกสาร' ?></p>
                </button>
            </div>
        </div>
    </form>
</div>

<section class="enterprise-card tab-content <?= $is_track_active ? 'active' : '' ?>" id="trackBook">
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
                    </div>

                    <select class="form-input" name="status">
                        <option value="all" <?= $filter_status === 'all' ? 'selected' : '' ?>>ทั้งหมด</option>
                        <option value="<?= h(strtolower(INTERNAL_STATUS_SENT)) ?>" <?= $filter_status === strtolower(INTERNAL_STATUS_SENT) ? 'selected' : '' ?>>ส่งแล้ว</option>
                        <option value="<?= h(strtolower(INTERNAL_STATUS_RECALLED)) ?>" <?= $filter_status === strtolower(INTERNAL_STATUS_RECALLED) ? 'selected' : '' ?>>ดึงกลับ</option>
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
            <!-- <tbody>
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
                        } elseif (in_array($status_key, [INTERNAL_STATUS_SENT, INTERNAL_STATUS_ARCHIVED], true)) {
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
            </tbody> -->

            <tr>
                <td>
                    <div class="circular-my-subject">ประชาสัมพันธ์หลักสูตรการอบรมเชิงปฏิบัติการด้านการจัดการเรียนรู้วิทยาศาสตร์</div>
                    <div class="circular-my-meta">ในนาม กลุ่มบริหารงานทั่วไป</div>
                </td>
                <td>
                    <span class="status-pill approved">ส่งแล้ว</span>
                </td>
                <td>1/2</td>
                <td>11 ก.พ. 2569 16:38 น.</td>
                <td>
                    <div class="circular-my-actions">


                        <button type="button" class="booking-action-btn secondary js-open-view-modal" data-vehicle-approval-action="detail" data-vehicle-booking-action="detail" data-vehicle-booking-id="4">

                            <i class="fa-solid fa-eye" aria-hidden="true"></i>
                            <span class="tooltip">ดูรายละเอียด</span>
                        </button>

                        <button type="button" class="booking-action-btn secondary js-open-edit-modal" data-vehicle-approval-action="detail" data-vehicle-booking-action="detail" data-vehicle-booking-id="4">

                            <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                            <span class="tooltip">ดู/แก้ไข</span>
                        </button>


                    </div>
                </td>
            </tr>

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

<div class="modal-overlay-outgoing details" id="modalViewOverlay" style="display: none;">
    <div class="modal-content">
        <div class="header-modal">
            <p id="modalTypeLabel">รายละเอียด</p>
            <i class="fa-solid fa-xmark" id="closeModalView" aria-hidden="true"></i>
        </div>

        <div class="content-modal">

            <div class="content-outgoing">
                <form action="" method="post" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <?php if ($is_edit_mode && $edit_circular_id > 0) : ?>
                        <input type="hidden" name="edit_circular_id" value="<?= h((string) $edit_circular_id) ?>">
                    <?php endif; ?>

                    <?php if ($is_edit_mode) : ?>
                        <div class="enterprise-panel">
                            <p><strong>โหมดแก้ไข:</strong> เอกสารถูกดึงกลับแล้ว สามารถแก้ไขและส่งใหม่ก่อนผู้บริหารพิจารณา</p>
                        </div>
                    <?php endif; ?>

                    <div class="type-urgent">
                        <p><strong>ประเภท: </strong></p>
                        <div class="radio-group-urgent">
                            <input type="radio" name="extPriority" value="ปกติ" <?= (string) $values['extPriority'] === 'ปกติ' ? 'checked' : '' ?>>
                            <p class="urgency-status normal">ปกติ</p>
                            <input type="radio" name="extPriority" disabled value="ด่วน" <?= (string) $values['extPriority'] === 'ด่วน' ? 'checked' : '' ?>>
                            <p class="urgency-status urgen">ด่วน</p>
                            <input type="radio" name="extPriority" disabled value="ด่วนมาก" <?= (string) $values['extPriority'] === 'ด่วนมาก' ? 'checked' : '' ?>>
                            <p class="urgency-status very-urgen">ด่วนมาก</p>
                            <input type="radio" name="extPriority" disabled value="ด่วนที่สุด" <?= (string) $values['extPriority'] === 'ด่วนที่สุด' ? 'checked' : '' ?>>
                            <p class="urgency-status extremly-urgen">ด่วนที่สุด</p>
                        </div>
                    </div>

                    <div class="form-group-row">
                        <div class="input-group">
                            <p><strong>เลขที่หนังสือ</strong></p>
                            <input type="text" name="extBookNo" disabled value="<?= h((string) $values['extBookNo']) ?>" required>
                        </div>
                        <div class="input-group">
                            <p><strong>ลงวันที่</strong></p>
                            <input type="date" name="extIssuedDate" disabled value="<?= h((string) $values['extIssuedDate']) ?>" required>
                        </div>
                    </div>

                    <div class="form-group-row">
                        <div class="input-group">
                            <p><strong>เรื่อง</strong></p>
                            <textarea name="subject" rows="3" disabled><?= h((string) $values['subject']) ?></textarea>
                        </div>
                    </div>

                    <div class="form-group-row">
                        <div class="input-group">
                            <p><strong>จาก</strong></p>
                            <input type="text" name="extFromText" disabled value="<?= h((string) $values['extFromText']) ?>" required>
                        </div>
                        <div class="input-group">
                            <p><strong>หนังสือของกลุ่ม</strong></p>
                            <input type="text" disabled>
                            <!-- <div class="custom-select-wrapper">
                                <div class="custom-select-trigger">
                                    <p class="select-value"><?= h($selected_group_name) ?></p>
                                    <i class="fa-solid fa-chevron-down"></i>
                                </div>

                                <div class="custom-options">
                                    <div class="custom-option<?= (string) $values['extGroupFID'] === '' ? ' selected' : '' ?>" data-value="">เลือกกลุ่ม/ฝ่าย</div>
                                    <?php foreach ($factions as $faction_item) : ?>
                                        <?php $fid = (int) ($faction_item['fID'] ?? 0); ?>
                                        <?php if ($fid <= 0) {
                                            continue;
                                        } ?>
                                        <div class="custom-option<?= (string) $values['extGroupFID'] === (string) $fid ? ' selected' : '' ?>" data-value="<?= h((string) $fid) ?>"><?= h((string) ($faction_item['fName'] ?? '')) ?></div>
                                    <?php endforeach; ?>
                                </div>

                                <select class="form-input" name="extGroupFID">
                                    <option value="" <?= (string) $values['extGroupFID'] === '' ? 'selected' : '' ?>>เลือกกลุ่ม/ฝ่าย</option>
                                    <?php foreach ($factions as $faction_item) : ?>
                                        <?php $fid = (int) ($faction_item['fID'] ?? 0); ?>
                                        <?php if ($fid <= 0) {
                                            continue;
                                        } ?>
                                        <option value="<?= h((string) $fid) ?>" <?= (string) $values['extGroupFID'] === (string) $fid ? 'selected' : '' ?>><?= h((string) ($faction_item['fName'] ?? '')) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div> -->
                        </div>
                    </div>

                    <div class="form-group-row">
                        <div class="input-group">
                            <p><strong>เกษียณหนังสือ: </strong>เรียน ผู้อำนวยการโรงเรียนดีบุกพังงาวิทยายน</p>
                            <textarea name="subject" rows="3" disabled></textarea>
                        </div>
                    </div>

                    <div class="form-group-row">
                        <div class="input-group">
                            <p><strong>แนบลิงก์ (ถ้ามี)</strong></p>
                            <input type="url" name="linkURL" disabled value="<?= h((string) $values['linkURL']) ?>" placeholder="https://example.com">
                        </div>
                    </div>

                    <div class="form-group-row">
                        <div class="input-group">
                            <p><strong>เสนอ</strong></p>
                            <input type="text" disabled>
                            <!-- <div class="custom-select-wrapper">
                                <div class="custom-select-trigger">
                                    <p class="select-value"><?= h($selected_reviewer_name) ?></p>
                                    <i class="fa-solid fa-chevron-down"></i>
                                </div>

                                <div class="custom-options">
                                    <div class="custom-option<?= (string) $values['reviewerPID'] === '' ? ' selected' : '' ?>" data-value="">เลือกผู้พิจารณา</div>
                                    <?php foreach ($reviewers as $reviewer_item) : ?>
                                        <?php $reviewer_pid = trim((string) ($reviewer_item['pID'] ?? '')); ?>
                                        <?php if ($reviewer_pid === '') {
                                            continue;
                                        } ?>
                                        <div class="custom-option<?= (string) $values['reviewerPID'] === $reviewer_pid ? ' selected' : '' ?>" data-value="<?= h($reviewer_pid) ?>"><?= h((string) ($reviewer_item['label'] ?? '')) ?></div>
                                    <?php endforeach; ?>
                                </div>

                                <select class="form-input" name="reviewerPID" required>
                                    <option value="" <?= (string) $values['reviewerPID'] === '' ? 'selected' : '' ?>>เลือกผู้พิจารณา</option>
                                    <?php foreach ($reviewers as $reviewer_item) : ?>
                                        <?php $reviewer_pid = trim((string) ($reviewer_item['pID'] ?? '')); ?>
                                        <?php if ($reviewer_pid === '') {
                                            continue;
                                        } ?>
                                        <option value="<?= h($reviewer_pid) ?>" <?= (string) $values['reviewerPID'] === $reviewer_pid ? 'selected' : '' ?>><?= h((string) ($reviewer_item['label'] ?? '')) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div> -->
                            <?php if (!$has_reviewer_options) : ?>
                                <p class="form-error" style="display:block;">ไม่พบผู้พิจารณาในระบบ กรุณาติดต่อผู้ดูแลระบบ</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <hr>

                    <div class="form-group-row">
                        <div class="input-group">
                            <p><strong>หนังสือนำ</strong></p>
                            <div>
                                <button
                                    type="button"
                                    class="btn btn-upload-small"
                                    onclick="document.getElementById('cover_attachment_modal').click()">
                                    <p>เพิ่มไฟล์</p>
                                </button>
                            </div>
                            <input
                                type="file"
                                id="cover_attachment_modal"
                                name="cover_attachments[]"
                                class="file-input"
                                multiple
                                accept=".pdf,image/png,image/jpeg"
                                hidden>

                            <div class="file-list" id="cover_attachmentList_modal" aria-live="polite"></div>
                        </div>
                    </div>

                    <hr>

                    <div class="form-group-row">
                        <div class="input-group">
                            <p><strong>เอกสารแนบ</strong></p>
                            <div>
                                <button
                                    type="button"
                                    class="btn btn-upload-small"
                                    onclick="document.getElementById('attachment_modal').click()">
                                    <p>เพิ่มไฟล์</p>
                                </button>
                            </div>
                            <input
                                type="file"
                                id="attachment_modal"
                                name="attachments[]"
                                class="file-input"
                                multiple
                                accept=".pdf,image/png,image/jpeg"
                                hidden>
                            <p class="form-error hidden" id="attachmentError_modal">แนบได้สูงสุด 4 ไฟล์</p>

                            <div class="file-list" id="attachmentList_modal" aria-live="polite"></div>
                        </div>
                    </div>

                    <?php if ($is_edit_mode && !empty($existing_attachments)) : ?>
                        <hr>
                        <div class="form-group-row">
                            <div class="input-group">
                                <p><strong>ไฟล์แนบเดิม (เลือกเพื่อลบก่อนส่งใหม่)</strong></p>
                                <div class="enterprise-panel">
                                    <?php foreach ($existing_attachments as $attachment) : ?>
                                        <?php
                                        $attachment_file_id = (int) ($attachment['fileID'] ?? 0);
                                        $attachment_name = (string) ($attachment['fileName'] ?? '');
                                        ?>
                                        <label style="display:flex;align-items:center;gap:8px;padding:6px 0;">
                                            <input type="checkbox" name="remove_file_ids[]" value="<?= h((string) $attachment_file_id) ?>">
                                            <span><?= h($attachment_name !== '' ? $attachment_name : ('ไฟล์ #' . $attachment_file_id)) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <hr>

                    <!-- <div class="form-group-row">
                        <div class="input-group">
                            <button class="submit" type="submit" <?= $has_reviewer_options ? '' : 'disabled' ?>>
                                <p><?= $is_edit_mode ? 'บันทึกแก้ไขและส่งใหม่' : 'บันทึกเอกสาร' ?></p>
                            </button>
                        </div>
                    </div> -->
                </form>
            </div>

        </div>

        <!-- <div class="footer-modal">
            <form method="POST" id="modalArchiveForm">
                <input type="hidden" name="csrf_token" value="3ece51cef25df8dcbb025b7f59af78f9d7fa9c90963b44be41d39e6d5152a6ac"> <input type="hidden" name="inbox_id" id="modalInboxId" value="10">
                <input type="hidden" name="action" value="archive">
                <button type="submit">
                    <p>บันทึกเอกสาร</p>
                </button>
            </form>
        </div> -->
    </div>


</div>

<div class="modal-overlay-outgoing" id="modalEditOverlay" style="display: none;">
    <div class="modal-content">
        <div class="header-modal">
            <p id="modalTypeLabel">รายละเอียด</p>
            <i class="fa-solid fa-xmark" id="closeModalEdit" aria-hidden="true"></i>
        </div>

        <div class="content-modal">
            <div class="content-outgoing">
                <form action="" method="post" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <?php if ($is_edit_mode && $edit_circular_id > 0) : ?>
                        <input type="hidden" name="edit_circular_id" value="<?= h((string) $edit_circular_id) ?>">
                    <?php endif; ?>

                    <?php if ($is_edit_mode) : ?>
                        <div class="enterprise-panel">
                            <p><strong>โหมดแก้ไข:</strong> เอกสารถูกดึงกลับแล้ว สามารถแก้ไขและส่งใหม่ก่อนผู้บริหารพิจารณา</p>
                        </div>
                    <?php endif; ?>

                    <div class="type-urgent">
                        <p><strong>ประเภท: </strong></p>
                        <div class="radio-group-urgent">
                            <input type="radio" name="extPriority" value="ปกติ" <?= (string) $values['extPriority'] === 'ปกติ' ? 'checked' : '' ?>>
                            <p class="urgency-status normal">ปกติ</p>
                            <input type="radio" name="extPriority" value="ด่วน" <?= (string) $values['extPriority'] === 'ด่วน' ? 'checked' : '' ?>>
                            <p class="urgency-status urgen">ด่วน</p>
                            <input type="radio" name="extPriority" value="ด่วนมาก" <?= (string) $values['extPriority'] === 'ด่วนมาก' ? 'checked' : '' ?>>
                            <p class="urgency-status very-urgen">ด่วนมาก</p>
                            <input type="radio" name="extPriority" value="ด่วนที่สุด" <?= (string) $values['extPriority'] === 'ด่วนที่สุด' ? 'checked' : '' ?>>
                            <p class="urgency-status extremly-urgen">ด่วนที่สุด</p>
                        </div>
                    </div>

                    <div class="form-group-row">
                        <div class="input-group">
                            <p><strong>เลขที่หนังสือ</strong></p>
                            <input type="text" name="extBookNo" value="<?= h((string) $values['extBookNo']) ?>" required>
                        </div>
                        <div class="input-group">
                            <p><strong>ลงวันที่</strong></p>
                            <input type="date" name="extIssuedDate" value="<?= h((string) $values['extIssuedDate']) ?>" required>
                        </div>
                    </div>

                    <div class="form-group-row">
                        <div class="input-group">
                            <p><strong>เรื่อง</strong></p>
                            <textarea name="subject" rows="3" required><?= h((string) $values['subject']) ?></textarea>
                        </div>
                    </div>

                    <div class="form-group-row">
                        <div class="input-group">
                            <p><strong>จาก</strong></p>
                            <input type="text" name="extFromText" value="<?= h((string) $values['extFromText']) ?>" required>
                        </div>
                        <div class="input-group">
                            <p><strong>หนังสือของกลุ่ม</strong></p>
                            <div class="custom-select-wrapper">
                                <div class="custom-select-trigger">
                                    <p class="select-value"><?= h($selected_group_name) ?></p>
                                    <i class="fa-solid fa-chevron-down"></i>
                                </div>

                                <div class="custom-options">
                                    <div class="custom-option<?= (string) $values['extGroupFID'] === '' ? ' selected' : '' ?>" data-value="">เลือกกลุ่ม/ฝ่าย</div>
                                    <?php foreach ($factions as $faction_item) : ?>
                                        <?php $fid = (int) ($faction_item['fID'] ?? 0); ?>
                                        <?php if ($fid <= 0) {
                                            continue;
                                        } ?>
                                        <div class="custom-option<?= (string) $values['extGroupFID'] === (string) $fid ? ' selected' : '' ?>" data-value="<?= h((string) $fid) ?>"><?= h((string) ($faction_item['fName'] ?? '')) ?></div>
                                    <?php endforeach; ?>
                                </div>

                                <select class="form-input" name="extGroupFID">
                                    <option value="" <?= (string) $values['extGroupFID'] === '' ? 'selected' : '' ?>>เลือกกลุ่ม/ฝ่าย</option>
                                    <?php foreach ($factions as $faction_item) : ?>
                                        <?php $fid = (int) ($faction_item['fID'] ?? 0); ?>
                                        <?php if ($fid <= 0) {
                                            continue;
                                        } ?>
                                        <option value="<?= h((string) $fid) ?>" <?= (string) $values['extGroupFID'] === (string) $fid ? 'selected' : '' ?>><?= h((string) ($faction_item['fName'] ?? '')) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group-row">
                        <div class="input-group">
                            <p><strong>เกษียณหนังสือ: </strong>เรียน ผู้อำนวยการโรงเรียนดีบุกพังงาวิทยายน</p>
                            <textarea name="detail" id="memo_editor"><?= h((string) $values['detail']) ?></textarea>
                        </div>
                    </div>

                    <div class="form-group-row">
                        <div class="input-group">
                            <p><strong>แนบลิงก์ (ถ้ามี)</strong></p>
                            <input type="url" name="linkURL" value="<?= h((string) $values['linkURL']) ?>" placeholder="https://example.com">
                        </div>
                    </div>

                    <div class="form-group-row">
                        <div class="input-group">
                            <p><strong>เสนอ</strong></p>
                            <div class="custom-select-wrapper">
                                <div class="custom-select-trigger">
                                    <p class="select-value"><?= h($selected_reviewer_name) ?></p>
                                    <i class="fa-solid fa-chevron-down"></i>
                                </div>

                                <div class="custom-options">
                                    <div class="custom-option<?= (string) $values['reviewerPID'] === '' ? ' selected' : '' ?>" data-value="">เลือกผู้พิจารณา</div>
                                    <?php foreach ($reviewers as $reviewer_item) : ?>
                                        <?php $reviewer_pid = trim((string) ($reviewer_item['pID'] ?? '')); ?>
                                        <?php if ($reviewer_pid === '') {
                                            continue;
                                        } ?>
                                        <div class="custom-option<?= (string) $values['reviewerPID'] === $reviewer_pid ? ' selected' : '' ?>" data-value="<?= h($reviewer_pid) ?>"><?= h((string) ($reviewer_item['label'] ?? '')) ?></div>
                                    <?php endforeach; ?>
                                </div>

                                <select class="form-input" name="reviewerPID" required>
                                    <option value="" <?= (string) $values['reviewerPID'] === '' ? 'selected' : '' ?>>เลือกผู้พิจารณา</option>
                                    <?php foreach ($reviewers as $reviewer_item) : ?>
                                        <?php $reviewer_pid = trim((string) ($reviewer_item['pID'] ?? '')); ?>
                                        <?php if ($reviewer_pid === '') {
                                            continue;
                                        } ?>
                                        <option value="<?= h($reviewer_pid) ?>" <?= (string) $values['reviewerPID'] === $reviewer_pid ? 'selected' : '' ?>><?= h((string) ($reviewer_item['label'] ?? '')) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php if (!$has_reviewer_options) : ?>
                                <p class="form-error" style="display:block;">ไม่พบผู้พิจารณาในระบบ กรุณาติดต่อผู้ดูแลระบบ</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <hr>

                    <div class="form-group-row">
                        <div class="input-group">
                            <p><strong>หนังสือนำ</strong></p>
                            <div>
                                <button
                                    type="button"
                                    class="btn btn-upload-small"
                                    onclick="document.getElementById('cover_attachment_edit').click()">
                                    <p>เพิ่มไฟล์</p>
                                </button>
                            </div>
                            <input
                                type="file"
                                id="cover_attachment_edit"
                                name="cover_attachments[]"
                                class="file-input"
                                multiple
                                accept=".pdf,image/png,image/jpeg"
                                hidden>

                            <div class="file-list" id="cover_attachmentList_edit" aria-live="polite"></div>
                        </div>
                    </div>

                    <hr>

                    <div class="form-group-row">
                        <div class="input-group">
                            <p><strong>เอกสารแนบ</strong></p>
                            <div>
                                <button
                                    type="button"
                                    class="btn btn-upload-small"
                                    onclick="document.getElementById('attachment_edit').click()">
                                    <p>เพิ่มไฟล์</p>
                                </button>
                            </div>
                            <input
                                type="file"
                                id="attachment_edit"
                                name="attachments[]"
                                class="file-input"
                                multiple
                                accept=".pdf,image/png,image/jpeg"
                                hidden>
                            <p class="form-error hidden" id="attachmentError_edit">แนบได้สูงสุด 4 ไฟล์</p>

                            <div class="file-list" id="attachmentList_edit" aria-live="polite"></div>
                        </div>
                    </div>

                    <?php if ($is_edit_mode && !empty($existing_attachments)) : ?>
                        <hr>
                        <div class="form-group-row">
                            <div class="input-group">
                                <p><strong>ไฟล์แนบเดิม (เลือกเพื่อลบก่อนส่งใหม่)</strong></p>
                                <div class="enterprise-panel">
                                    <?php foreach ($existing_attachments as $attachment) : ?>
                                        <?php
                                        $attachment_file_id = (int) ($attachment['fileID'] ?? 0);
                                        $attachment_name = (string) ($attachment['fileName'] ?? '');
                                        ?>
                                        <label style="display:flex;align-items:center;gap:8px;padding:6px 0;">
                                            <input type="checkbox" name="remove_file_ids[]" value="<?= h((string) $attachment_file_id) ?>">
                                            <span><?= h($attachment_name !== '' ? $attachment_name : ('ไฟล์ #' . $attachment_file_id)) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <hr>

                    <!-- <div class="form-group-row">
                        <div class="input-group">
                            <button class="submit" type="submit" <?= $has_reviewer_options ? '' : 'disabled' ?>>
                                <p><?= $is_edit_mode ? 'บันทึกแก้ไขและส่งใหม่' : 'บันทึกเอกสาร' ?></p>
                            </button>
                        </div>
                    </div> -->
                </form>
            </div>
        </div>

        <div class="footer-modal">
            <form method="POST" id="modalArchiveForm">
                <input type="hidden" name="csrf_token" value="3ece51cef25df8dcbb025b7f59af78f9d7fa9c90963b44be41d39e6d5152a6ac"> <input type="hidden" name="inbox_id" id="modalInboxId" value="10">
                <input type="hidden" name="action" value="archive">
                <button type="submit">
                    <p>บันทึกเอกสาร</p>
                </button>
            </form>
        </div>

    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js"></script>
<script>
    tinymce.init({
        selector: "#memo_editor",
        height: 500,
        menubar: false,
        language: "th_TH",
        plugins: "searchreplace autolink directionality visualblocks visualchars image link media codesample table charmap pagebreak nonbreaking anchor insertdatetime advlist lists wordcount help charmap emoticons",
        toolbar: "undo redo | fontfamily | fontsize | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | outdent indent |  numlist bullist | forecolor backcolor removeformat | pagebreak | charmap emoticons",
        font_family_formats: "TH Sarabun New=Sarabun, sans-serif;",
        font_size_formats: "8pt 9pt 10pt 12pt 14pt 16pt 18pt 20pt 22pt 24pt 26pt 36pt 48pt 72pt",
        content_style: `
      @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&display=swap');
      body {
        font-family: 'Sarabun', sans-serif;
        font-size: 16pt;
        line-height: 1.5;
        color: #000;
        background-color: #fff;
        padding: 0 20px;
        margin: 0 auto;
      }
      p {
        margin-bottom: 0px;
      }
    `,
        nonbreaking_force_tab: true,
        promotion: false,
        branding: false,
    });

    document.addEventListener("DOMContentLoaded", function() {
        function setupFileUpload(inputId, listId, maxFiles = 1) {
            const fileInput = document.getElementById(inputId);
            const fileList = document.getElementById(listId);
            const previewModal = document.getElementById("imagePreviewModal");
            const previewImage = document.getElementById("previewImage");
            const previewCaption = document.getElementById("previewCaption");
            const closePreviewBtn = document.getElementById("closePreviewBtn");
            const allowedTypes = ["application/pdf", "image/jpeg", "image/png"];
            let selectedFiles = [];

            if (!fileInput || !fileList) return;

            const renderFiles = () => {
                fileList.innerHTML = "";

                if (selectedFiles.length === 0) {
                    fileList.innerHTML = `
            <div style="
              background-color: #f0f4fa;
              border: 1px dashed #ced4da;
              border-radius: 6px;
              padding: 15px;
              text-align: center;
              color: #6c757d;
              font-size: 14px;
              margin-top: 10px;
            ">
              ยังไม่มีไฟล์แนบ
            </div>
          `;
                    return;
                }

                selectedFiles.forEach((file, index) => {
                    const wrapper = document.createElement("div");
                    wrapper.className = "file-item-wrapper";

                    const deleteBtn = document.createElement("button");
                    deleteBtn.type = "button";
                    deleteBtn.className = "delete-btn";
                    deleteBtn.innerHTML = '<i class="fa-solid fa-trash"></i>';
                    deleteBtn.addEventListener("click", () => {
                        selectedFiles = selectedFiles.filter((_, i) => i !== index);
                        syncFiles();
                        renderFiles();
                    });

                    const banner = document.createElement("div");
                    banner.className = "file-banner";

                    const info = document.createElement("div");
                    info.className = "file-info";

                    const icon = document.createElement("div");
                    icon.className = "file-icon";
                    icon.innerHTML =
                        file.type === "application/pdf" ?
                        '<i class="fa-solid fa-file-pdf"></i>' :
                        '<i class="fa-solid fa-image"></i>';

                    const text = document.createElement("div");
                    text.className = "file-text";

                    const name = document.createElement("div");
                    name.className = "file-name";
                    name.textContent = file.name;

                    const type = document.createElement("div");
                    type.className = "file-type";
                    type.textContent = (file.size / 1024 / 1024).toFixed(2) + " MB";

                    text.appendChild(name);
                    text.appendChild(type);
                    info.appendChild(icon);
                    info.appendChild(text);

                    const actions = document.createElement("div");
                    actions.className = "file-actions";

                    const view = document.createElement("a");
                    view.href = "#";
                    view.innerHTML = '<i class="fa-solid fa-eye"></i>';
                    view.addEventListener("click", (e) => {
                        e.preventDefault();
                        if (file.type.startsWith("image/")) {
                            const reader = new FileReader();
                            reader.onload = () => {
                                if (previewImage) previewImage.src = reader.result;
                                if (previewCaption) previewCaption.textContent = file.name;
                                previewModal?.classList.add("active");
                            };
                            reader.readAsDataURL(file);
                        } else {
                            const url = URL.createObjectURL(file);
                            window.open(url, "_blank", "noopener");
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
                const dt = new DataTransfer();
                selectedFiles.forEach((file) => dt.items.add(file));
                fileInput.files = dt.files;
            };

            const addFiles = (files) => {
                if (!files) return;
                const existing = new Set(selectedFiles.map((file) => `${file.name}-${file.size}`));

                Array.from(files).forEach((file) => {
                    const key = `${file.name}-${file.size}`;
                    if (existing.has(key)) return;
                    if (!allowedTypes.includes(file.type)) {
                        alert("รองรับเฉพาะไฟล์ PDF, JPG และ PNG");
                        return;
                    }
                    if (selectedFiles.length >= maxFiles) {
                        alert(`แนบไฟล์ได้สูงสุด ${maxFiles} ไฟล์`);
                        return;
                    }
                    selectedFiles.push(file);
                    existing.add(key);
                });

                syncFiles();
                renderFiles();
            };

            fileInput.addEventListener("change", (e) => {
                addFiles(e.target.files);
            });

            if (closePreviewBtn) {
                closePreviewBtn.addEventListener("click", () => previewModal?.classList.remove("active"));
            }
            if (previewModal) {
                previewModal.addEventListener("click", (e) => {
                    if (e.target === previewModal) previewModal.classList.remove("active");
                });
            }

            renderFiles();
        }

        setupFileUpload("cover_attachment", "cover_attachmentList", 1);
        setupFileUpload("attachment", "attachmentList", 4);

        setupFileUpload("cover_attachment_modal", "cover_attachmentList_modal", 1);
        setupFileUpload("attachment_modal", "attachmentList_modal", 4);

        setupFileUpload("cover_attachment_edit", "cover_attachmentList_edit", 1);
        setupFileUpload("attachment_edit", "attachmentList_edit", 4);

        const viewModal = document.getElementById('modalViewOverlay');
        const editModal = document.getElementById('modalEditOverlay');

        const closeViewBtn = document.getElementById('closeModalView');
        const closeEditBtn = document.getElementById('closeModalEdit');

        const openViewBtns = document.querySelectorAll('.js-open-view-modal');
        const openEditBtns = document.querySelectorAll('.js-open-edit-modal');

        openViewBtns.forEach((btn) => {
            btn.addEventListener('click', (event) => {
                event.preventDefault();

                if (viewModal) viewModal.style.display = 'flex';
            });
        });

        openEditBtns.forEach((btn) => {
            btn.addEventListener('click', (event) => {
                event.preventDefault();

                if (editModal) editModal.style.display = 'flex';
            });
        });

        closeViewBtn?.addEventListener('click', () => {
            if (viewModal) viewModal.style.display = 'none';
        });

        closeEditBtn?.addEventListener('click', () => {
            if (editModal) editModal.style.display = 'none';
        });

        window.addEventListener('click', (event) => {
            if (event.target === viewModal) {
                viewModal.style.display = 'none';
            }
            if (event.target === editModal) {
                editModal.style.display = 'none';
            }
        });
    });
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';

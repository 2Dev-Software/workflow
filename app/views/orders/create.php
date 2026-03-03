<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../auth/csrf.php';

$values = (array) ($values ?? []);
$display_order_no = trim((string) ($display_order_no ?? ''));
$issuer_name = trim((string) ($issuer_name ?? ''));
$faction_options = (array) ($faction_options ?? []);
$edit_order = $edit_order ?? null;
$edit_order_id = (int) ($edit_order_id ?? 0);
$is_edit_mode = $edit_order_id > 0 && !empty($edit_order);

$values = array_merge([
    'subject' => '',
    'effective_date' => '',
    'order_date' => '',
    'group_fid' => '',
], $values);

$page_title = 'ยินดีต้อนรับ';
$page_subtitle = 'คำสั่งราชการ / ออกเลขคำสั่งราชการ';
$submit_label = $is_edit_mode ? 'บันทึกการแก้ไข' : 'บันทึกออกเลข';
$is_track_active = (bool) ($is_track_active ?? false);
$filter_query = (string) ($filter_query ?? '');
$filter_status = (string) ($filter_status ?? 'all');
$filter_sort = (string) ($filter_sort ?? 'newest');
$sent_items = (array) ($sent_items ?? []);
$status_map = (array) ($status_map ?? []);
$read_stats_map = (array) ($read_stats_map ?? []);
$detail_map = (array) ($detail_map ?? []);
$edit_modal_attachments_map = (array) ($edit_modal_attachments_map ?? []);
$send_modal_payload_map = (array) ($send_modal_payload_map ?? []);
$send_modal_values = (array) ($send_modal_values ?? [
    'faction_ids' => [],
    'role_ids' => [],
    'person_ids' => [],
]);
$send_modal_open_order_id = (int) ($send_modal_open_order_id ?? 0);
$send_modal_summary = (array) ($send_modal_summary ?? [
    'selected_sources' => 0,
    'unique_recipients' => 0,
]);
$send_picker_factions = (array) ($send_picker_factions ?? []);
$send_picker_roles = (array) ($send_picker_roles ?? []);
$send_picker_teachers = (array) ($send_picker_teachers ?? []);
$send_picker_faction_member_map = (array) ($send_picker_faction_member_map ?? []);
$send_picker_role_member_map = (array) ($send_picker_role_member_map ?? []);
$selected_send_faction_ids = array_map('strval', (array) ($send_modal_values['faction_ids'] ?? []));
$selected_send_role_ids = array_map('strval', (array) ($send_modal_values['role_ids'] ?? []));
$selected_send_person_ids = array_map('strval', (array) ($send_modal_values['person_ids'] ?? []));
$send_is_selected = static function (string $value, array $selected): bool {
    return in_array($value, $selected, true);
};
$send_teacher_name_map = [];
$send_teacher_faction_map = [];

foreach ($send_picker_teachers as $send_teacher_row) {
    $send_pid = trim((string) ($send_teacher_row['pID'] ?? ''));

    if ($send_pid === '') {
        continue;
    }
    $send_teacher_name_map[$send_pid] = trim((string) ($send_teacher_row['fName'] ?? ''));
    $send_teacher_faction_map[$send_pid] = trim((string) ($send_teacher_row['factionName'] ?? ''));
}
$default_group_fid = '';

$issuer_display_name = $issuer_name !== '' ? $issuer_name : '-';

if (!empty($faction_options)) {
    $first_group_fid = array_key_first($faction_options);
    $default_group_fid = $first_group_fid !== null ? (string) $first_group_fid : '';
}

$selected_group_fid = trim((string) ($values['group_fid'] ?? ''));
if ($selected_group_fid === '' && $default_group_fid !== '') {
    $selected_group_fid = $default_group_fid;
}
$selected_group_name = (string) ($faction_options[$selected_group_fid] ?? '');

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

$format_thai_date = static function (?string $date_value) use ($thai_months): string {
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

    return $day . ' ' . $month_label . ' ' . $year;
};

$parse_order_meta = static function (?string $detail_text): array {
    $text = trim((string) $detail_text);
    $meta = [
        'effective_date' => '',
        'order_date' => '',
        'issuer_name' => '',
        'group_name' => '',
    ];

    if ($text === '') {
        return $meta;
    }

    if (preg_match('/^ทั้งนี้ตั้งแต่วันที่:\s*(.+)$/m', $text, $matches) === 1) {
        $meta['effective_date'] = trim((string) ($matches[1] ?? ''));
    }

    if (preg_match('/^สั่ง ณ วันที่:\s*(.+)$/m', $text, $matches) === 1) {
        $meta['order_date'] = trim((string) ($matches[1] ?? ''));
    }

    if (preg_match('/^ผู้ออกเลขคำสั่ง:\s*(.+)$/m', $text, $matches) === 1) {
        $value = trim((string) ($matches[1] ?? ''));
        $meta['issuer_name'] = $value !== '-' ? $value : '';
    }

    if (preg_match('/^กลุ่ม:\s*(.+)$/m', $text, $matches) === 1) {
        $value = trim((string) ($matches[1] ?? ''));
        $meta['group_name'] = $value !== '-' ? $value : '';
    }

    return $meta;
};

ob_start();
?>
<style>
    .circular-track-modal-host {
        width: 0;
        height: 0;
        padding: 0;
        margin: 0;
        border: 0;
        background: transparent;
    }

    .circular-track-modal-host #modalOrderEditOverlay .content-modal .content-topic-sec {
        align-items: flex-start;
    }

    .circular-track-modal-host #modalOrderEditOverlay .content-modal .content-topic-sec .more-details {
        flex: 1;
    }

    .circular-track-modal-host #modalOrderEditOverlay .content-modal .content-topic-sec input {
        width: 100%;
        height: 50px;
        border: none;
        border-radius: 8px;
        border: 1px solid var(--color-secondary);
        background-color: var(--color-neutral-lightest);
        padding: 10px 20px;
        font-size: var(--font-size-body-1);
        color: var(--color-secondary);
        transition: 0.4s;
    }

    .circular-track-modal-host #modalOrderEditOverlay .content-modal .content-topic-sec input::placeholder {
        color: var(--color-secondary);
    }

    .circular-track-modal-host #modalOrderEditOverlay .content-modal .content-topic-sec input:hover,
    .circular-track-modal-host #modalOrderEditOverlay .content-modal .content-topic-sec input:active,
    .circular-track-modal-host #modalOrderEditOverlay .content-modal .content-topic-sec input:focus {
        outline: none;
    }

    .circular-track-modal-host #modalOrderEditOverlay .content-modal .content-topic-sec input:disabled {
        width: 100%;
        min-height: 50px;
        font-weight: 600;
        cursor: not-allowed;
        color: var(--color-neutral-dark);
        background-color: rgba(var(--rgb-neutral-medium), 0.25);
        border: none;
    }

    .circular-track-modal-host #modalOrderEditOverlay .content-modal .content-topic-sec input.order-no-display[disabled] {
        background-color: #eef3ff;
        color: var(--color-primary-dark);
        font-weight: 600;
        cursor: not-allowed;
        border: 1px solid var(--color-secondary);
    }

    .circular-track-modal-host #modalOrderEditOverlay .content-modal>.content-topic-sec:first-of-type {
        border-bottom: none;
        padding-bottom: 0;
        margin-bottom: 20px;
    }

    .circular-track-modal-host #modalOrderEditOverlay .content-modal .content-topic-sec .custom-select-wrapper {
        position: relative;
        width: 100%;
        -webkit-user-select: none;
        user-select: none;
    }

    .circular-track-modal-host #modalOrderEditOverlay .content-modal .content-topic-sec .custom-select-trigger {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        height: 50px;
        padding: 0 20px;
        border-radius: 6px;
        background-color: var(--color-neutral-lightest);
        color: var(--color-secondary);
        border: 1px solid var(--color-secondary);
        cursor: pointer;
        transition: all 0.2s;
    }

    .circular-track-modal-host #modalOrderEditOverlay .content-modal .content-topic-sec .custom-select-trigger .select-value {
        margin: 0;
        font-size: var(--font-size-body-1);
        color: var(--color-secondary);
    }

    .circular-track-modal-host #modalOrderEditOverlay .content-modal .content-topic-sec .custom-select-wrapper i {
        font-size: var(--font-size-body-1);
        display: flex;
        justify-content: center;
        align-items: center;
        transition: transform 0.4s;
    }

    .circular-track-modal-host #modalOrderEditOverlay .content-modal .content-topic-sec .custom-select-wrapper.open i {
        transform: rotate(180deg);
    }

    .circular-track-modal-host #modalOrderEditOverlay .content-modal .content-topic-sec .custom-options {
        position: absolute;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        top: 80%;
        left: 0;
        right: 0;
        background: var(--color-neutral-lightest);
        border-radius: 6px;
        box-shadow: 0 4px 12px rgba(var(--rgb-neutral-dark), 0.25);
        z-index: 111;
        opacity: 0;
        visibility: hidden;
        transform: translateY(-25px);
        transition: all 0.2s ease;
        overflow: hidden;
    }

    .circular-track-modal-host #modalOrderEditOverlay .content-modal .content-topic-sec .custom-select-wrapper.open .custom-options {
        opacity: 1;
        visibility: visible;
        transform: translateY(10px);
    }

    .circular-track-modal-host #modalOrderEditOverlay .content-modal .content-topic-sec .custom-option {
        padding: 12px 20px;
        margin: 0;
        font-size: var(--font-size-body-1);
        color: var(--color-secondary);
        cursor: pointer;
        transition: background 0.3s;
        width: 100%;
    }

    .circular-track-modal-host #modalOrderEditOverlay .content-modal .content-topic-sec .custom-option:hover {
        background-color: rgba(var(--rgb-primary-dark), 0.1);
    }

    .circular-track-modal-host #modalOrderEditOverlay .content-modal .content-topic-sec .custom-option.selected {
        font-weight: bold;
        background-color: rgba(var(--rgb-primary-dark), 0.1);
    }

    .circular-track-modal-host #modalOrderEditOverlay .content-modal .content-file-sec {
        margin: 20px 0 0;
        border-bottom: none !important;
        padding-bottom: 0;
    }

    .circular-track-modal-host #modalOrderEditOverlay .content-modal .content-file-sec .existing-file-section {
        margin: 0 0 12px;
    }

    .circular-track-modal-host #modalOrderEditOverlay .content-modal .content-file-sec label {
        display: block;
        margin: 0 0 10px;
        font-size: var(--font-size-body-1);
        color: var(--color-secondary);
    }

    .circular-track-modal-host #modalOrderEditOverlay .content-modal .content-file-sec .existing-file-empty {
        margin: 0;
        font-size: var(--font-size-body-2);
        color: var(--color-secondary);
    }

    .circular-track-modal-host #modalOrderEditOverlay .content-modal .content-file-sec .upload-layout {
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 20px;
        margin: 0 0 20px;
    }

    .circular-track-modal-host #modalOrderEditOverlay .content-modal .content-file-sec .upload-layout input {
        display: none;
    }

    .circular-track-modal-host #modalOrderEditOverlay .content-modal .content-file-sec .upload-layout .upload-box {
        width: 50%;
        height: 440px;
        border-radius: 8px;
        background-color: var(--color-neutral-lightest);
        border: 2px dashed var(--color-secondary);
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        cursor: pointer;
        transition: 0.3s;
    }

    .circular-track-modal-host #modalOrderEditOverlay .content-modal .content-file-sec .upload-layout .upload-box:hover,
    .circular-track-modal-host #modalOrderEditOverlay .content-modal .content-file-sec .upload-layout .upload-box:active,
    .circular-track-modal-host #modalOrderEditOverlay .content-modal .content-file-sec .upload-layout .upload-box:focus,
    .circular-track-modal-host #modalOrderEditOverlay .content-modal .content-file-sec .upload-layout .upload-box.active {
        background-color: rgba(var(--rgb-neutral-dark), 0.04);
    }

    .circular-track-modal-host #modalOrderEditOverlay .content-modal .content-file-sec .upload-layout .upload-box i {
        font-size: var(--font-size-h1);
        color: var(--color-secondary);
    }

    .circular-track-modal-host #modalOrderEditOverlay .content-modal .content-file-sec .upload-layout .upload-box p {
        font-size: var(--font-size-body-1);
        font-weight: bold;
        color: var(--color-secondary);
    }

    .circular-track-modal-host #modalOrderEditOverlay .content-modal .content-file-sec .file-hint {
        font-size: var(--font-size-h1);
        color: var(--color-danger);
    }

    .circular-track-modal-host #modalOrderEditOverlay .content-modal .content-file-sec .form-group.row {
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: 20px;
        margin: 0 0 50px;
    }

    .circular-track-modal-host #modalOrderEditOverlay .content-modal .content-file-sec .form-group.row button {
        width: 140px;
        height: 40px;
        border-radius: 8px;
        border: none;
        background-color: var(--color-secondary);
        font-size: var(--font-size-body-1);
        color: var(--color-neutral-lightest);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: 0.3s;
    }

    .circular-track-modal-host #modalOrderEditOverlay .content-modal .content-file-sec .form-group.row button:hover {
        background-color: var(--color-primary-deep);
    }

    .circular-track-modal-host #modalOrderEditOverlay .content-modal .content-file-sec .form-group.row button p {
        margin: 0;
        color: var(--color-neutral-lightest);
        font-size: var(--font-size-body-2);
    }

    .circular-track-modal-host #modalOrderEditOverlay .content-modal .content-file-sec .form-group.row .file-hint {
        font-size: var(--font-size-body-1);
        font-weight: bold;
        color: var(--color-danger);
    }

    .circular-track-modal-host #modalOrderEditOverlay .content-modal .content-file-sec .file-list {
        display: flex;
        flex-direction: column;
        flex-wrap: wrap;
        gap: 10px;
        margin: 0 0 20px;
    }

    .circular-track-modal-host #modalOrderEditOverlay .content-modal .content-file-sec .file-item-wrapper {
        display: flex;
        align-items: center;
        width: 425px;
        gap: 15px;
        animation: fadeIn 0.3s ease;
    }

    .circular-track-modal-host #modalOrderEditOverlay .content-modal .content-file-sec .delete-btn {
        background: none;
        border: none;
        color: var(--color-danger);
        font-size: var(--font-size-h4);
        cursor: pointer;
        transition: transform 0.2s;
    }

    .circular-track-modal-host #modalOrderEditOverlay .content-modal .content-file-sec .delete-btn:hover {
        transform: scale(1.2);
    }

    .circular-track-modal-host #modalOrderEditOverlay .content-modal .content-file-sec .file-banner {
        flex: 1;
        width: 100%;
        background-color: var(--color-secondary);
        border-radius: 8px;
        padding: 10px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        color: var(--color-neutral-lightest);
        box-shadow: 0 2px 6px rgba(var(--rgb-neutral-dark), 0.05);
        min-width: 0;
        max-width: none;
        height: auto;
        gap: 0;
    }

    .circular-track-modal-host #modalOrderEditOverlay .content-modal .content-file-sec .file-info {
        display: flex;
        align-items: center;
        gap: 10px;
        width: auto;
    }

    .circular-track-modal-host #modalOrderEditOverlay .content-modal .content-file-sec .file-icon {
        font-size: var(--font-size-h1);
        color: var(--color-secondary);
        background-color: var(--color-neutral-lightest);
        width: 60px;
        height: 60px;
        display: flex;
        justify-content: center;
        align-items: center;
        border-radius: 6px;
    }

    .circular-track-modal-host #modalOrderEditOverlay .content-modal .content-file-sec .file-text {
        display: flex;
        flex-direction: column;
        line-height: normal;
    }

    .circular-track-modal-host #modalOrderEditOverlay .content-modal .content-file-sec .file-name {
        font-size: var(--font-size-body-2);
        font-weight: bold;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 250px;
        width: 90%;
        border-bottom: 3px solid var(--color-neutral-lightest);
    }

    .circular-track-modal-host #modalOrderEditOverlay .content-modal .content-file-sec .file-type {
        font-size: var(--font-size-body-2);
        opacity: 0.9;
    }

    .circular-track-modal-host #modalOrderEditOverlay .content-modal .content-file-sec .file-actions {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        width: 30px;
        height: 30px;
        border-radius: 6px;
        background-color: var(--color-neutral-lightest);
        font-size: var(--font-size-body-2);
    }

    .circular-track-modal-host #modalOrderEditOverlay .content-modal .content-file-sec .file-actions a {
        color: var(--color-secondary);
    }

    .circular-track-modal-host #modalOrderSendOverlay .content-modal {
        max-height: 72vh;
        overflow: auto;
    }

    .circular-track-modal-host #modalOrderSendOverlay .content-topic-sec {
        align-items: flex-start;
    }

    .circular-track-modal-host #modalOrderSendOverlay .content-topic-sec .more-details {
        flex: 1;
    }

    .circular-track-modal-host #modalOrderSendOverlay .content-topic-sec input {
        width: 100%;
        height: 50px;
        border-radius: 8px;
        border: 1px solid var(--color-secondary);
        background-color: var(--color-neutral-lightest);
        padding: 10px 20px;
        color: var(--color-secondary);
        font-size: var(--font-size-body-1);
    }

    .circular-track-modal-host #modalOrderSendOverlay .content-topic-sec input:disabled {
        background-color: #eef3ff;
        color: var(--color-primary-dark);
        font-weight: 600;
        cursor: not-allowed;
    }

    .circular-track-modal-host #modalOrderSendOverlay .content-file-sec {
        margin: 18px 0;
        padding-bottom: 0;
        border-bottom: none;
    }

    .circular-track-modal-host #modalOrderSendOverlay .content-file-sec .file-section {
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin-top: 8px;
    }

    .circular-track-modal-host #modalOrderSendOverlay .content-file-sec .file-banner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        border-radius: 10px;
        background-color: var(--color-secondary);
        color: var(--color-neutral-lightest);
        padding: 10px 12px;
        width: 40%;
        min-width: none;
        max-width: none
    }

    .circular-track-modal-host #modalOrderSendOverlay .content-file-sec .file-info {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
    }

    .circular-track-modal-host #modalOrderSendOverlay .content-file-sec .file-icon {
        width: 56px;
        height: 56px;
        border-radius: 8px;
        background-color: rgba(var(--rgb-neutral-lightest), 0.92);
        color: var(--color-secondary);
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: var(--font-size-h2);
        flex-shrink: 0;
    }

    .circular-track-modal-host #modalOrderSendOverlay .content-file-sec .file-text {
        display: flex;
        flex-direction: column;
        min-width: 0;
    }

    .circular-track-modal-host #modalOrderSendOverlay .content-file-sec .file-name {
        font-size: var(--font-size-body-1);
        font-weight: bold;
        border-bottom: 2px solid rgba(var(--rgb-neutral-lightest), 0.9);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .circular-track-modal-host #modalOrderSendOverlay .content-file-sec .file-type {
        font-size: var(--font-size-body-2);
        opacity: 0.95;
    }

    .circular-track-modal-host #modalOrderSendOverlay .content-file-sec .file-actions {
        width: 30px;
        height: 30px;
        border-radius: 6px;
        background-color: var(--color-neutral-lightest);
        display: flex;
        justify-content: center;
        align-items: center;
        flex-shrink: 0;
    }

    .circular-track-modal-host #modalOrderSendOverlay .content-file-sec .file-actions a {
        color: var(--color-secondary);
    }

    .circular-track-modal-host #modalOrderSendOverlay .orders-send-modal-shell .orders-send-summary {
        margin: 16px 0;
    }

    .circular-track-modal-host #modalOrderSendOverlay .orders-send-modal-shell .booking-actions {
        justify-content: flex-end;
        margin-top: 18px;
    }

    .circular-track-modal-host #modalOrderSendOverlay .orders-send-track-table td:nth-child(2),
    .circular-track-modal-host #modalOrderSendOverlay .orders-send-track-table td:nth-child(3),
    .circular-track-modal-host #modalOrderSendOverlay .orders-send-track-table th:nth-child(2),
    .circular-track-modal-host #modalOrderSendOverlay .orders-send-track-table th:nth-child(3) {
        text-align: center;
    }

    .circular-track-modal-host #modalOrderSendOverlay .orders-send-track-empty {
        text-align: center;
    }

    .circular-track-modal-host #modalOrderViewOverlay .content-modal {
        max-height: 72vh;
        overflow: auto;
    }

    .circular-track-modal-host #modalOrderViewOverlay .content-topic-sec {
        align-items: flex-start;
    }

    .circular-track-modal-host #modalOrderViewOverlay .content-topic-sec .more-details {
        flex: 1;
    }

    .circular-track-modal-host #modalOrderViewOverlay .content-topic-sec input {
        width: 100%;
        height: 50px;
        border-radius: 8px;
        border: 1px solid var(--color-secondary);
        background-color: var(--color-neutral-lightest);
        padding: 10px 20px;
        color: var(--color-secondary);
        font-size: var(--font-size-body-1);
    }

    .circular-track-modal-host #modalOrderViewOverlay .content-topic-sec input:disabled {
        background-color: #eef3ff;
        color: var(--color-primary-dark);
        font-weight: 600;
        cursor: not-allowed;
    }

    .circular-track-modal-host #modalOrderViewOverlay .content-file-sec {
        margin: 18px 0;
        padding-bottom: 0;
        border-bottom: none;
    }

    .circular-track-modal-host #modalOrderViewOverlay .content-file-sec .file-section {
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin-top: 8px;
    }

    .circular-track-modal-host #modalOrderViewOverlay .content-file-sec .file-banner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        border-radius: 10px;
        background-color: var(--color-secondary);
        color: var(--color-neutral-lightest);
        padding: 10px 12px;
        width: 40%;
        min-width: none;
        max-width: none
    }

    .circular-track-modal-host #modalOrderViewOverlay .content-file-sec .file-info {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
    }

    .circular-track-modal-host #modalOrderViewOverlay .content-file-sec .file-icon {
        width: 56px;
        height: 56px;
        border-radius: 8px;
        background-color: rgba(var(--rgb-neutral-lightest), 0.92);
        color: var(--color-secondary);
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: var(--font-size-h2);
        flex-shrink: 0;
    }

    .circular-track-modal-host #modalOrderViewOverlay .content-file-sec .file-text {
        display: flex;
        flex-direction: column;
        min-width: 0;
    }

    .circular-track-modal-host #modalOrderViewOverlay .content-file-sec .file-name {
        font-size: var(--font-size-body-1);
        font-weight: bold;
        border-bottom: 2px solid rgba(var(--rgb-neutral-lightest), 0.9);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .circular-track-modal-host #modalOrderViewOverlay .content-file-sec .file-type {
        font-size: var(--font-size-body-2);
        opacity: 0.95;
    }

    .circular-track-modal-host #modalOrderViewOverlay .content-file-sec .file-actions {
        width: 30px;
        height: 30px;
        border-radius: 6px;
        background-color: var(--color-neutral-lightest);
        display: flex;
        justify-content: center;
        align-items: center;
        flex-shrink: 0;
    }

    .circular-track-modal-host #modalOrderViewOverlay .content-file-sec .file-actions a {
        color: var(--color-secondary);
    }

    .circular-track-modal-host #modalOrderViewOverlay .orders-send-modal-shell .orders-send-summary {
        margin: 16px 0;
    }

    .circular-track-modal-host #modalOrderViewOverlay .orders-send-modal-shell .booking-actions {
        justify-content: flex-end;
        margin-top: 18px;
    }

    .circular-track-modal-host #modalOrderViewOverlay .orders-send-track-table td:nth-child(2),
    .circular-track-modal-host #modalOrderViewOverlay .orders-send-track-table td:nth-child(3),
    .circular-track-modal-host #modalOrderViewOverlay .orders-send-track-table th:nth-child(2),
    .circular-track-modal-host #modalOrderViewOverlay .orders-send-track-table th:nth-child(3) {
        text-align: center;
    }

    .circular-track-modal-host #modalOrderViewOverlay .orders-send-track-empty {
        text-align: center;
    }
</style>
<div class="content-header">
    <h1><?= h($page_title) ?></h1>
    <p><?= h($page_subtitle) ?></p>
</div>

<div class="tabs-container setting-page">
    <div class="button-container vehicle">
        <button class="tab-btn <?= $is_track_active ? '' : 'active' ?>"
            onclick="openTab('orderReceive', event)">ออกเลขคำสั่ง</button>
        <button class="tab-btn <?= $is_track_active ? 'active' : '' ?>" onclick="openTab('orderMine', event)">เลขคำสั่งของฉัน</button>
    </div>
</div>

<div class="content-order create tab-content <?= $is_track_active ? '' : 'active' ?>" id="orderReceive">
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <?php if ($is_edit_mode): ?>
            <input type="hidden" name="order_id" value="<?= (int) $edit_order_id ?>">
        <?php endif; ?>

        <div class="form-group row">
            <div class="input-group">
                <p><strong>คำสั่งที่</strong></p>
                <input
                    type="text"
                    class="order-no-display"
                    value="<?= h($display_order_no !== '' ? $display_order_no : '-') ?>"
                    disabled>
            </div>
            <div class="input-group">
                <p><strong>เรื่อง</strong></p>
                <input
                    type="text"
                    name="subject"
                    value="<?= h((string) ($values['subject'] ?? '')) ?>"
                    placeholder="ระบุหัวข้อคำสั่ง"
                    maxlength="300"
                    required>
            </div>
        </div>

        <div class="form-group row">
            <div class="input-group">
                <p><strong>ทั้งนี้ตั้งแต่วันที่</strong></p>
                <input
                    type="date"
                    name="effective_date"
                    value="<?= h((string) ($values['effective_date'] ?? '')) ?>"
                    required>
            </div>
            <div class="input-group">
                <p><strong>สั่ง ณ วันที่</strong></p>
                <input
                    type="date"
                    name="order_date"
                    value="<?= h((string) ($values['order_date'] ?? '')) ?>"
                    required>
            </div>
        </div>

        <div class="form-group row">
            <div class="input-group">
                <p><strong>ผู้ออกเลขคำสั่ง</strong></p>
                <input type="text" class="order-no-display" value="<?= h($issuer_display_name) ?>" disabled>
            </div>
            <div class="input-group">
                <p><strong>กลุ่ม</strong></p>
                <div class="custom-select-wrapper">
                    <div class="custom-select-trigger">
                        <p class="select-value"><?= h($selected_group_name !== '' ? $selected_group_name : 'เลือกกลุ่ม') ?></p>
                        <i class="fa-solid fa-chevron-down"></i>
                    </div>

                    <div class="custom-options">
                        <?php foreach ($faction_options as $fid => $name): ?>
                            <?php $group_fid_value = (string) $fid; ?>
                            <div class="custom-option<?= $group_fid_value === $selected_group_fid ? ' selected' : '' ?>" data-value="<?= h($group_fid_value) ?>">
                                <?= h((string) $name) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <input type="hidden" name="group_fid" value="<?= h($selected_group_fid) ?>">
                </div>
            </div>
        </div>

        <div class="form-group button">
            <div class="input-group">
                <button class="submit" type="submit">
                    <p><?= h($submit_label) ?></p>
                </button>
            </div>
        </div>
    </form>
</div>

<section class="enterprise-card tab-content <?= $is_track_active ? 'active' : '' ?>" id="orderMine">
    <div class="enterprise-card-header">
        <div class="enterprise-card-title-group">
            <h2 class="enterprise-card-title">ค้นหาและกรองรายการ</h2>
        </div>
    </div>

    <form method="GET" class="circular-my-filter-grid">
        <div class="approval-filter-group">
            <div class="room-admin-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input class="form-input" type="search" name="q" value="<?= h($filter_query) ?>"
                    placeholder="ค้นหาเลขคำสั่งหรือเรื่อง" autocomplete="off">
            </div>
            <div class="room-admin-filter">
                <div class="custom-select-wrapper">
                    <div class="custom-select-trigger">
                        <p class="select-value">
                            <?php
                            $status_label = 'ทั้งหมด';

                            if ($filter_status === 'waiting_attachment') {
                                $status_label = 'รอการแนบไฟล์';
                            } elseif ($filter_status === 'complete') {
                                $status_label = 'แนบไฟล์สำเร็จ';
                            }
                            echo h($status_label);
                            ?>
                        </p>
                        <i class="fa-solid fa-chevron-down"></i>
                    </div>

                    <div class="custom-options">
                        <div class="custom-option" data-value="all">ทั้งหมด</div>
                        <div class="custom-option" data-value="waiting_attachment">รอการแนบไฟล์</div>
                        <div class="custom-option" data-value="complete">แนบไฟล์สำเร็จ</div>
                    </div>

                    <select class="form-input" name="status">
                        <option value="all" <?= $filter_status === 'all' ? 'selected' : '' ?>>ทั้งหมด</option>
                        <option value="waiting_attachment" <?= $filter_status === 'waiting_attachment' ? 'selected' : '' ?>>รอการแนบไฟล์</option>
                        <option value="complete" <?= $filter_status === 'complete' ? 'selected' : '' ?>>แนบไฟล์สำเร็จ</option>
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

    <div class="enterprise-card-header order-mine-list-header">
        <div class="enterprise-card-title-group">
            <h2 class="enterprise-card-title">รายการคำสั่งของฉัน</h2>
        </div>
    </div>

    <div class="table-responsive circular-my-table-wrap order-create">
        <script type="application/json" class="js-order-send-map">
            <?= (string) json_encode($send_modal_payload_map, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
        </script>
        <table class="custom-table circular-my-table">
            <thead>
                <tr>
                    <th>เรื่อง</th>
                    <th>สถานะ</th>
                    <th>วันที่ดำเนินการ</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($sent_items)) : ?>
                    <tr>
                        <td colspan="4" class="enterprise-empty">ไม่มีรายการคำสั่งตามเงื่อนไข</td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($sent_items as $item) : ?>
                        <?php
                        $order_id = (int) ($item['orderID'] ?? 0);
                        $order_no = trim((string) ($item['orderNo'] ?? ''));
                        $detail_text = trim((string) ($item['detail'] ?? ''));
                        $parsed_meta = $parse_order_meta($detail_text);
                        $effective_date_raw = trim((string) ($parsed_meta['effective_date'] ?? ''));
                        $order_date_raw = trim((string) ($parsed_meta['order_date'] ?? ''));
                        $effective_date_display = $format_thai_date((string) ($parsed_meta['effective_date'] ?? ''));
                        $order_date_display = $format_thai_date((string) ($parsed_meta['order_date'] ?? ''));
                        $issuer_name_from_detail = trim((string) ($parsed_meta['issuer_name'] ?? ''));
                        $issuer_for_modal = $issuer_name_from_detail !== '' ? $issuer_name_from_detail : $issuer_display_name;
                        $group_name = trim((string) ($parsed_meta['group_name'] ?? ''));
                        $group_fid_for_modal = '';
                        if ($group_name !== '') {
                            $group_fid_found = array_search($group_name, $faction_options, true);
                            if ($group_fid_found !== false) {
                                $group_fid_for_modal = (string) $group_fid_found;
                            }
                        }
                        if ($group_fid_for_modal === '') {
                            $group_fid_for_modal = $default_group_fid;
                        }
                        $status_key = strtoupper(trim((string) ($item['status'] ?? '')));
                        $status_meta = $status_map[$status_key] ?? ['label' => ($status_key !== '' ? $status_key : '-'), 'pill' => 'pending'];
                        $created_at = (string) ($item['createdAt'] ?? '');
                        $date_display = $format_thai_datetime($created_at);
                        $can_edit = in_array($status_key, [ORDER_STATUS_WAITING_ATTACHMENT, ORDER_STATUS_COMPLETE], true);
                        $can_manage_send = in_array($status_key, [ORDER_STATUS_COMPLETE, ORDER_STATUS_SENT], true);
                        $edit_action_label = $status_key === ORDER_STATUS_WAITING_ATTACHMENT ? 'ดู/แนบไฟล์' : 'ดู/แก้ไข';
                        $order_existing_files = (array) ($edit_modal_attachments_map[(string) $order_id] ?? []);
                        $order_existing_files_json = json_encode($order_existing_files, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        if ($order_existing_files_json === false) {
                            $order_existing_files_json = '[]';
                        }
                        ?>
                        <tr>
                            <td>
                                <div class="circular-my-subject"><?= h((string) ($item['subject'] ?? '-')) ?></div>
                                <?php if ($order_no !== '') : ?>
                                    <div class="circular-my-meta">เลขที่คำสั่ง <?= h($order_no) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-pill <?= h((string) ($status_meta['pill'] ?? 'pending')) ?>"><?= h((string) ($status_meta['label'] ?? '-')) ?></span>
                                <p class="viewer">อ่านแล้ว 1 จาก 5 คน</p>
                            </td>
                            <td><?= h($date_display) ?></td>
                            <td>
                                <div class="circular-my-actions">
                                    <?php if ($can_edit && $order_id > 0) : ?>
                                        <button
                                            class="booking-action-btn secondary js-open-order-edit-modal"
                                            type="button"
                                            data-order-id="<?= h((string) $order_id) ?>"
                                            data-order-no="<?= h($order_no !== '' ? $order_no : '-') ?>"
                                            data-order-subject="<?= h((string) ($item['subject'] ?? '-')) ?>"
                                            data-order-issuer="<?= h($issuer_for_modal) ?>"
                                            data-order-detail="<?= h($detail_text !== '' ? $detail_text : '-') ?>"
                                            data-order-status="<?= h((string) ($status_meta['label'] ?? '-')) ?>"
                                            data-order-created="<?= h($date_display) ?>"
                                            data-order-date="<?= h($order_date_display) ?>"
                                            data-order-date-raw="<?= h($order_date_raw) ?>"
                                            data-order-effective-date="<?= h($effective_date_display) ?>"
                                            data-order-effective-date-raw="<?= h($effective_date_raw) ?>"
                                            data-order-group="<?= h($group_name !== '' ? $group_name : '-') ?>"
                                            data-order-group-fid="<?= h($group_fid_for_modal) ?>"
                                            data-order-files="<?= h($order_existing_files_json) ?>"
                                            title="<?= h($edit_action_label) ?>"
                                            aria-label="<?= h($edit_action_label) ?>">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                            <span class="tooltip"><?= h($edit_action_label) ?></span>
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($can_manage_send && $order_id > 0) : ?>
                                        <button
                                            class="booking-action-btn secondary js-open-order-send-modal"
                                            type="button"
                                            data-order-id="<?= h((string) $order_id) ?>">
                                            <i class="fa-solid fa-paper-plane"></i>
                                            <span class="tooltip"><?= $status_key === ORDER_STATUS_SENT ? 'ติดตามการส่ง' : 'ส่งคำสั่ง' ?></span>
                                        </button>
                                    <?php endif; ?>
                                    <button
                                        class="booking-action-btn secondary js-open-order-view-modal"
                                        type="button">
                                        <i class="fa-solid fa-eye"></i>
                                        <span class="tooltip">ผู้รับเอกสาร</span>
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
    <div class="modal-overlay-circular-notice-index outside-person" id="modalOrderSendOverlay">
        <div class="modal-content">
            <div class="header-modal">
                <div class="first-header">
                    <p id="modalOrderSendTitle">ส่งคำสั่งราชการ</p>
                </div>
                <div class="sec-header">
                    <i class="fa-solid fa-xmark" id="closeModalOrderSend"></i>
                </div>
            </div>

            <div class="content-modal">
                <div class="content-topic-sec">
                    <div class="more-details">
                        <p><strong>คำสั่งที่</strong></p>
                        <input type="text" id="modalOrderSendNo" class="order-no-display" value="-" disabled>
                    </div>
                    <div class="more-details">
                        <p><strong>เรื่อง</strong></p>
                        <input type="text" id="modalOrderSendSubject" class="order-no-display" value="-" disabled>
                    </div>
                </div>

                <div class="content-topic-sec">
                    <div class="more-details">
                        <p><strong>ทั้งนี้ตั้งแต่วันที่</strong></p>
                        <input type="date" id="modalOrderSendEffectiveDate" class="order-no-display" value="" disabled>
                    </div>
                    <div class="more-details">
                        <p><strong>สั่ง ณ วันที่</strong></p>
                        <input type="date" id="modalOrderSendDate" class="order-no-display" value="" disabled>
                    </div>
                </div>

                <div class="content-topic-sec">
                    <div class="more-details">
                        <p><strong>ผู้ออกเลขคำสั่ง</strong></p>
                        <input type="text" id="modalOrderSendIssuer" class="order-no-display" value="-" disabled>
                    </div>
                    <div class="more-details">
                        <p><strong>กลุ่ม</strong></p>
                        <input type="text" id="modalOrderSendGroup" class="order-no-display" value="-" disabled>
                    </div>
                </div>

                <div class="content-file-sec">
                    <p><strong>ไฟล์เอกสารแนบจากระบบ</strong></p>
                    <div class="file-section" id="modalOrderSendFileSection"></div>
                </div>

                <div class="orders-send-modal-shell orders-send-card">
                    <div id="modalOrderSendFormSection">
                        <form method="POST" action="orders-create.php" class="orders-send-form" id="modalOrderSendForm">
                            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                            <input type="hidden" name="order_action" value="send">
                            <input type="hidden" name="send_order_id" id="modalOrderSendOrderId" value="">

                            <div class="form-group receive" data-order-send-recipients>
                                <label>ส่งถึง :</label>
                                <div class="dropdown-container">
                                    <div class="search-input-wrapper" id="orderSendRecipientToggle">
                                        <input type="text" id="orderSendMainInput" class="search-input" placeholder="ค้นหา หรือ เลือกข้อมูล..." autocomplete="off">
                                        <i class="fa-solid fa-chevron-down"></i>
                                    </div>

                                    <div class="dropdown-content" id="orderSendDropdownContent">
                                        <div class="dropdown-header">
                                            <label class="select-all-box">
                                                <input type="checkbox" id="orderSendSelectAll">เลือกทั้งหมด
                                            </label>
                                        </div>

                                        <div class="dropdown-list">
                                            <div class="category-group">
                                                <div class="category-title">
                                                    <span>หน่วยงาน</span>
                                                </div>
                                                <div class="category-items">
                                                    <div class="item item-group is-collapsed" data-faction-id="5">
                                                        <div class="group-header">
                                                            <label class="item-main">
                                                                <input type="checkbox" class="item-checkbox group-item-checkbox faction-item-checkbox" data-group="faction" data-group-key="faction-5" data-group-label="กลุ่มบริหารกิจการนักเรียน" data-members="[{&quot;pID&quot;:&quot;3820400215231&quot;,&quot;name&quot;:&quot;นางชมทิศา ขันภักดี&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารกิจการนักเรียน&quot;},{&quot;pID&quot;:&quot;3950300068146&quot;,&quot;name&quot;:&quot;นางพวงทิพย์ ทวีรส&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารกิจการนักเรียน&quot;},{&quot;pID&quot;:&quot;1829900172052&quot;,&quot;name&quot;:&quot;นางสาวชาลิสา จิตต์พันธ์&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารกิจการนักเรียน&quot;},{&quot;pID&quot;:&quot;3820800038999&quot;,&quot;name&quot;:&quot;นางสาวนิรัตน์ เพชรแก้ว&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารกิจการนักเรียน&quot;},{&quot;pID&quot;:&quot;1829900170670&quot;,&quot;name&quot;:&quot;นางสาวอรบุษย์ หนักแน่น&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารกิจการนักเรียน&quot;},{&quot;pID&quot;:&quot;3601000301019&quot;,&quot;name&quot;:&quot;นางสุนิษา  จินดาพล&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารกิจการนักเรียน&quot;},{&quot;pID&quot;:&quot;3820400309367&quot;,&quot;name&quot;:&quot;นางเขมษิญากรณ์ อุดมคุณ&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารกิจการนักเรียน&quot;},{&quot;pID&quot;:&quot;3930300329632&quot;,&quot;name&quot;:&quot;นางเพ็ญแข หวานสนิท&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารกิจการนักเรียน&quot;},{&quot;pID&quot;:&quot;3820400261097&quot;,&quot;name&quot;:&quot;นายจรุง  บำรุงเขตต์&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารกิจการนักเรียน&quot;},{&quot;pID&quot;:&quot;3820700017680&quot;,&quot;name&quot;:&quot;นายณณัฐพล  บุญสุรัชต์สิรี&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารกิจการนักเรียน&quot;},{&quot;pID&quot;:&quot;1319800069611&quot;,&quot;name&quot;:&quot;นายณัฐพงษ์ สัจจารักษ์&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารกิจการนักเรียน&quot;},{&quot;pID&quot;:&quot;1839900193629&quot;,&quot;name&quot;:&quot;นายธีรภัส  สฤษดิสุข&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารกิจการนักเรียน&quot;},{&quot;pID&quot;:&quot;1841500136302&quot;,&quot;name&quot;:&quot;นายธีระวัฒน์ เพชรขุ้ม&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารกิจการนักเรียน&quot;},{&quot;pID&quot;:&quot;1829900109890&quot;,&quot;name&quot;:&quot;นายพจนันท์  พรหมสงค์&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารกิจการนักเรียน&quot;},{&quot;pID&quot;:&quot;1829900012446&quot;,&quot;name&quot;:&quot;นายรชต  ปานบุญ&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารกิจการนักเรียน&quot;},{&quot;pID&quot;:&quot;3820100028745&quot;,&quot;name&quot;:&quot;นายวรานนท์ ภาระพฤติ&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารกิจการนักเรียน&quot;},{&quot;pID&quot;:&quot;1829900093446&quot;,&quot;name&quot;:&quot;นายศุภสวัสดิ์ กาญวิจิต&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารกิจการนักเรียน&quot;},{&quot;pID&quot;:&quot;3929900087867&quot;,&quot;name&quot;:&quot;นายอนุสรณ์ ชูทอง&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารกิจการนักเรียน&quot;},{&quot;pID&quot;:&quot;1901100006087&quot;,&quot;name&quot;:&quot;นายเจนสกนธ์  ศรัณย์บัณฑิต&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารกิจการนักเรียน&quot;},{&quot;pID&quot;:&quot;1829900072562&quot;,&quot;name&quot;:&quot;นายเอกพงษ์ สงวนทรัพย์&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารกิจการนักเรียน&quot;}]" name="faction_ids[]" value="5">
                                                                <span class="item-title">กลุ่มบริหารกิจการนักเรียน</span>
                                                                <small class="item-subtext">สมาชิกทั้งหมด 20 คน</small>
                                                            </label>
                                                            <button type="button" class="group-toggle" aria-expanded="false" title="แสดง/ซ่อนรายชื่อสมาชิก">
                                                                <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                                                            </button>
                                                        </div>

                                                        <ol class="member-sublist">
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-5" data-member-name="นางชมทิศา ขันภักดี" data-group-label="กลุ่มบริหารกิจการนักเรียน" name="person_ids[]" value="3820400215231">
                                                                    <span class="member-name">นางชมทิศา ขันภักดี</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-5" data-member-name="นางพวงทิพย์ ทวีรส" data-group-label="กลุ่มบริหารกิจการนักเรียน" name="person_ids[]" value="3950300068146">
                                                                    <span class="member-name">นางพวงทิพย์ ทวีรส</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-5" data-member-name="นางสาวชาลิสา จิตต์พันธ์" data-group-label="กลุ่มบริหารกิจการนักเรียน" name="person_ids[]" value="1829900172052">
                                                                    <span class="member-name">นางสาวชาลิสา จิตต์พันธ์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-5" data-member-name="นางสาวนิรัตน์ เพชรแก้ว" data-group-label="กลุ่มบริหารกิจการนักเรียน" name="person_ids[]" value="3820800038999">
                                                                    <span class="member-name">นางสาวนิรัตน์ เพชรแก้ว</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-5" data-member-name="นางสาวอรบุษย์ หนักแน่น" data-group-label="กลุ่มบริหารกิจการนักเรียน" name="person_ids[]" value="1829900170670">
                                                                    <span class="member-name">นางสาวอรบุษย์ หนักแน่น</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-5" data-member-name="นางสุนิษา  จินดาพล" data-group-label="กลุ่มบริหารกิจการนักเรียน" name="person_ids[]" value="3601000301019">
                                                                    <span class="member-name">นางสุนิษา จินดาพล</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-5" data-member-name="นางเขมษิญากรณ์ อุดมคุณ" data-group-label="กลุ่มบริหารกิจการนักเรียน" name="person_ids[]" value="3820400309367">
                                                                    <span class="member-name">นางเขมษิญากรณ์ อุดมคุณ</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-5" data-member-name="นางเพ็ญแข หวานสนิท" data-group-label="กลุ่มบริหารกิจการนักเรียน" name="person_ids[]" value="3930300329632">
                                                                    <span class="member-name">นางเพ็ญแข หวานสนิท</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-5" data-member-name="นายจรุง  บำรุงเขตต์" data-group-label="กลุ่มบริหารกิจการนักเรียน" name="person_ids[]" value="3820400261097">
                                                                    <span class="member-name">นายจรุง บำรุงเขตต์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-5" data-member-name="นายณณัฐพล  บุญสุรัชต์สิรี" data-group-label="กลุ่มบริหารกิจการนักเรียน" name="person_ids[]" value="3820700017680">
                                                                    <span class="member-name">นายณณัฐพล บุญสุรัชต์สิรี</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-5" data-member-name="นายณัฐพงษ์ สัจจารักษ์" data-group-label="กลุ่มบริหารกิจการนักเรียน" name="person_ids[]" value="1319800069611">
                                                                    <span class="member-name">นายณัฐพงษ์ สัจจารักษ์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-5" data-member-name="นายธีรภัส  สฤษดิสุข" data-group-label="กลุ่มบริหารกิจการนักเรียน" name="person_ids[]" value="1839900193629">
                                                                    <span class="member-name">นายธีรภัส สฤษดิสุข</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-5" data-member-name="นายธีระวัฒน์ เพชรขุ้ม" data-group-label="กลุ่มบริหารกิจการนักเรียน" name="person_ids[]" value="1841500136302">
                                                                    <span class="member-name">นายธีระวัฒน์ เพชรขุ้ม</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-5" data-member-name="นายพจนันท์  พรหมสงค์" data-group-label="กลุ่มบริหารกิจการนักเรียน" name="person_ids[]" value="1829900109890">
                                                                    <span class="member-name">นายพจนันท์ พรหมสงค์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-5" data-member-name="นายรชต  ปานบุญ" data-group-label="กลุ่มบริหารกิจการนักเรียน" name="person_ids[]" value="1829900012446">
                                                                    <span class="member-name">นายรชต ปานบุญ</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-5" data-member-name="นายวรานนท์ ภาระพฤติ" data-group-label="กลุ่มบริหารกิจการนักเรียน" name="person_ids[]" value="3820100028745">
                                                                    <span class="member-name">นายวรานนท์ ภาระพฤติ</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-5" data-member-name="นายศุภสวัสดิ์ กาญวิจิต" data-group-label="กลุ่มบริหารกิจการนักเรียน" name="person_ids[]" value="1829900093446">
                                                                    <span class="member-name">นายศุภสวัสดิ์ กาญวิจิต</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-5" data-member-name="นายอนุสรณ์ ชูทอง" data-group-label="กลุ่มบริหารกิจการนักเรียน" name="person_ids[]" value="3929900087867">
                                                                    <span class="member-name">นายอนุสรณ์ ชูทอง</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-5" data-member-name="นายเจนสกนธ์  ศรัณย์บัณฑิต" data-group-label="กลุ่มบริหารกิจการนักเรียน" name="person_ids[]" value="1901100006087">
                                                                    <span class="member-name">นายเจนสกนธ์ ศรัณย์บัณฑิต</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-5" data-member-name="นายเอกพงษ์ สงวนทรัพย์" data-group-label="กลุ่มบริหารกิจการนักเรียน" name="person_ids[]" value="1829900072562">
                                                                    <span class="member-name">นายเอกพงษ์ สงวนทรัพย์</span>
                                                                </label>
                                                            </li>
                                                        </ol>
                                                    </div>
                                                    <div class="item item-group is-collapsed" data-faction-id="4">
                                                        <div class="group-header">
                                                            <label class="item-main">
                                                                <input type="checkbox" class="item-checkbox group-item-checkbox faction-item-checkbox" data-group="faction" data-group-key="faction-4" data-group-label="กลุ่มบริหารงานทั่วไป" data-members="[{&quot;pID&quot;:&quot;1820500007021&quot;,&quot;name&quot;:&quot;นางจิตติพร เกตุรักษ์&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานทั่วไป&quot;},{&quot;pID&quot;:&quot;3820100172170&quot;,&quot;name&quot;:&quot;นางพูนสุข ถิ่นลิพอน&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานทั่วไป&quot;},{&quot;pID&quot;:&quot;1809900084706&quot;,&quot;name&quot;:&quot;นางภทรมน ลิ่มบุตร&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานทั่วไป&quot;},{&quot;pID&quot;:&quot;3820100025495&quot;,&quot;name&quot;:&quot;นางวาสนา  สุทธจิตร์&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานทั่วไป&quot;},{&quot;pID&quot;:&quot;3850100320012&quot;,&quot;name&quot;:&quot;นางสาวธารทิพย์ ภาระพฤติ&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานทั่วไป&quot;},{&quot;pID&quot;:&quot;1829900174284&quot;,&quot;name&quot;:&quot;นางสาวนิรชา ธรรมัสโร&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานทั่วไป&quot;},{&quot;pID&quot;:&quot;3829900019706&quot;,&quot;name&quot;:&quot;นางสาวปาณิสรา  มงคลบุตร&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานทั่วไป&quot;},{&quot;pID&quot;:&quot;1920100023843&quot;,&quot;name&quot;:&quot;นางสาวพรทิพย์ สมบัติบุญ&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานทั่วไป&quot;},{&quot;pID&quot;:&quot;1101401730717&quot;,&quot;name&quot;:&quot;นางสาวพรรณพนัช  คงผอม&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานทั่วไป&quot;},{&quot;pID&quot;:&quot;1820500148121&quot;,&quot;name&quot;:&quot;นางสาวรัตนาพร พรประสิทธิ์&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานทั่วไป&quot;},{&quot;pID&quot;:&quot;3820500121271&quot;,&quot;name&quot;:&quot;นางสาวราศรี  อนันตมงคลกุล&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานทั่วไป&quot;},{&quot;pID&quot;:&quot;1809901015490&quot;,&quot;name&quot;:&quot;นางสาวสรัลรัตน์ จันทับ&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานทั่วไป&quot;},{&quot;pID&quot;:&quot;1820800031408&quot;,&quot;name&quot;:&quot;นางสาวสุดาทิพย์ ยกย่อง&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานทั่วไป&quot;},{&quot;pID&quot;:&quot;1860700158147&quot;,&quot;name&quot;:&quot;นายนพดล วงศ์สุวัฒน์&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานทั่วไป&quot;},{&quot;pID&quot;:&quot;1860100007288&quot;,&quot;name&quot;:&quot;นายนพพร  ถิ่นไทย&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานทั่วไป&quot;},{&quot;pID&quot;:&quot;1102003266698&quot;,&quot;name&quot;:&quot;นายนรินทร์เพชร นิลเวช&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานทั่วไป&quot;},{&quot;pID&quot;:&quot;1160100618291&quot;,&quot;name&quot;:&quot;นายวิศรุต ชามทอง&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานทั่วไป&quot;},{&quot;pID&quot;:&quot;3810500157631&quot;,&quot;name&quot;:&quot;นายสหัส เสือยืนยง&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานทั่วไป&quot;},{&quot;pID&quot;:&quot;1829900162341&quot;,&quot;name&quot;:&quot;นายอิสรพงศ์ สัตปานนท์&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานทั่วไป&quot;},{&quot;pID&quot;:&quot;3180600191510&quot;,&quot;name&quot;:&quot;นายเพลิน โอรักษ์&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานทั่วไป&quot;},{&quot;pID&quot;:&quot;1809900094507&quot;,&quot;name&quot;:&quot;ว่าที่ร้อยตรีเรืองเดชย์  ผสารพจน์&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานทั่วไป&quot;}]" name="faction_ids[]" value="4">
                                                                <span class="item-title">กลุ่มบริหารงานทั่วไป</span>
                                                                <small class="item-subtext">สมาชิกทั้งหมด 21 คน</small>
                                                            </label>
                                                            <button type="button" class="group-toggle" aria-expanded="false" title="แสดง/ซ่อนรายชื่อสมาชิก">
                                                                <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                                                            </button>
                                                        </div>

                                                        <ol class="member-sublist">
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-4" data-member-name="นางจิตติพร เกตุรักษ์" data-group-label="กลุ่มบริหารงานทั่วไป" name="person_ids[]" value="1820500007021">
                                                                    <span class="member-name">นางจิตติพร เกตุรักษ์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-4" data-member-name="นางพูนสุข ถิ่นลิพอน" data-group-label="กลุ่มบริหารงานทั่วไป" name="person_ids[]" value="3820100172170">
                                                                    <span class="member-name">นางพูนสุข ถิ่นลิพอน</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-4" data-member-name="นางภทรมน ลิ่มบุตร" data-group-label="กลุ่มบริหารงานทั่วไป" name="person_ids[]" value="1809900084706">
                                                                    <span class="member-name">นางภทรมน ลิ่มบุตร</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-4" data-member-name="นางวาสนา  สุทธจิตร์" data-group-label="กลุ่มบริหารงานทั่วไป" name="person_ids[]" value="3820100025495">
                                                                    <span class="member-name">นางวาสนา สุทธจิตร์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-4" data-member-name="นางสาวธารทิพย์ ภาระพฤติ" data-group-label="กลุ่มบริหารงานทั่วไป" name="person_ids[]" value="3850100320012">
                                                                    <span class="member-name">นางสาวธารทิพย์ ภาระพฤติ</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-4" data-member-name="นางสาวนิรชา ธรรมัสโร" data-group-label="กลุ่มบริหารงานทั่วไป" name="person_ids[]" value="1829900174284">
                                                                    <span class="member-name">นางสาวนิรชา ธรรมัสโร</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-4" data-member-name="นางสาวปาณิสรา  มงคลบุตร" data-group-label="กลุ่มบริหารงานทั่วไป" name="person_ids[]" value="3829900019706">
                                                                    <span class="member-name">นางสาวปาณิสรา มงคลบุตร</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-4" data-member-name="นางสาวพรทิพย์ สมบัติบุญ" data-group-label="กลุ่มบริหารงานทั่วไป" name="person_ids[]" value="1920100023843">
                                                                    <span class="member-name">นางสาวพรทิพย์ สมบัติบุญ</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-4" data-member-name="นางสาวพรรณพนัช  คงผอม" data-group-label="กลุ่มบริหารงานทั่วไป" name="person_ids[]" value="1101401730717">
                                                                    <span class="member-name">นางสาวพรรณพนัช คงผอม</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-4" data-member-name="นางสาวรัตนาพร พรประสิทธิ์" data-group-label="กลุ่มบริหารงานทั่วไป" name="person_ids[]" value="1820500148121">
                                                                    <span class="member-name">นางสาวรัตนาพร พรประสิทธิ์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-4" data-member-name="นางสาวราศรี  อนันตมงคลกุล" data-group-label="กลุ่มบริหารงานทั่วไป" name="person_ids[]" value="3820500121271">
                                                                    <span class="member-name">นางสาวราศรี อนันตมงคลกุล</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-4" data-member-name="นางสาวสรัลรัตน์ จันทับ" data-group-label="กลุ่มบริหารงานทั่วไป" name="person_ids[]" value="1809901015490">
                                                                    <span class="member-name">นางสาวสรัลรัตน์ จันทับ</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-4" data-member-name="นางสาวสุดาทิพย์ ยกย่อง" data-group-label="กลุ่มบริหารงานทั่วไป" name="person_ids[]" value="1820800031408">
                                                                    <span class="member-name">นางสาวสุดาทิพย์ ยกย่อง</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-4" data-member-name="นายนพดล วงศ์สุวัฒน์" data-group-label="กลุ่มบริหารงานทั่วไป" name="person_ids[]" value="1860700158147">
                                                                    <span class="member-name">นายนพดล วงศ์สุวัฒน์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-4" data-member-name="นายนพพร  ถิ่นไทย" data-group-label="กลุ่มบริหารงานทั่วไป" name="person_ids[]" value="1860100007288">
                                                                    <span class="member-name">นายนพพร ถิ่นไทย</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-4" data-member-name="นายนรินทร์เพชร นิลเวช" data-group-label="กลุ่มบริหารงานทั่วไป" name="person_ids[]" value="1102003266698">
                                                                    <span class="member-name">นายนรินทร์เพชร นิลเวช</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-4" data-member-name="นายวิศรุต ชามทอง" data-group-label="กลุ่มบริหารงานทั่วไป" name="person_ids[]" value="1160100618291">
                                                                    <span class="member-name">นายวิศรุต ชามทอง</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-4" data-member-name="นายสหัส เสือยืนยง" data-group-label="กลุ่มบริหารงานทั่วไป" name="person_ids[]" value="3810500157631">
                                                                    <span class="member-name">นายสหัส เสือยืนยง</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-4" data-member-name="นายอิสรพงศ์ สัตปานนท์" data-group-label="กลุ่มบริหารงานทั่วไป" name="person_ids[]" value="1829900162341">
                                                                    <span class="member-name">นายอิสรพงศ์ สัตปานนท์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-4" data-member-name="นายเพลิน โอรักษ์" data-group-label="กลุ่มบริหารงานทั่วไป" name="person_ids[]" value="3180600191510">
                                                                    <span class="member-name">นายเพลิน โอรักษ์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-4" data-member-name="ว่าที่ร้อยตรีเรืองเดชย์  ผสารพจน์" data-group-label="กลุ่มบริหารงานทั่วไป" name="person_ids[]" value="1809900094507">
                                                                    <span class="member-name">ว่าที่ร้อยตรีเรืองเดชย์ ผสารพจน์</span>
                                                                </label>
                                                            </li>
                                                        </ol>
                                                    </div>
                                                    <div class="item item-group is-collapsed" data-faction-id="3">
                                                        <div class="group-header">
                                                            <label class="item-main">
                                                                <input type="checkbox" class="item-checkbox group-item-checkbox faction-item-checkbox" data-group="faction" data-group-key="faction-3" data-group-label="กลุ่มบริหารงานบุคคลและงบประมาณ" data-members="[{&quot;pID&quot;:&quot;5800900028151&quot;,&quot;name&quot;:&quot;นางจริยาวดี  เวชจันทร์&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานบุคคลและงบประมาณ&quot;},{&quot;pID&quot;:&quot;3800400522290&quot;,&quot;name&quot;:&quot;นางจิราภรณ์  เสรีรักษ์&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานบุคคลและงบประมาณ&quot;},{&quot;pID&quot;:&quot;3920100747937&quot;,&quot;name&quot;:&quot;นางจุไรรัตน์ สวัสดิ์วงศ์&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานบุคคลและงบประมาณ&quot;},{&quot;pID&quot;:&quot;1829900007736&quot;,&quot;name&quot;:&quot;นางปวีณา  บำรุงภักดิ์&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานบุคคลและงบประมาณ&quot;},{&quot;pID&quot;:&quot;1930600099890&quot;,&quot;name&quot;:&quot;นางฝาติหม๊ะ ขนาดผล&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานบุคคลและงบประมาณ&quot;},{&quot;pID&quot;:&quot;1829900179103&quot;,&quot;name&quot;:&quot;นางสาวกนกลักษณ์ พันธ์สวัสดิ์&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานบุคคลและงบประมาณ&quot;},{&quot;pID&quot;:&quot;1810500062871&quot;,&quot;name&quot;:&quot;นางสาวกานต์พิชชา ปากลาว&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานบุคคลและงบประมาณ&quot;},{&quot;pID&quot;:&quot;1820500147966&quot;,&quot;name&quot;:&quot;นางสาวธนวรรณ พิทักษ์คง&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานบุคคลและงบประมาณ&quot;},{&quot;pID&quot;:&quot;1829900119712&quot;,&quot;name&quot;:&quot;นางสาวธิดารัตน์ ทองกอบ&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานบุคคลและงบประมาณ&quot;},{&quot;pID&quot;:&quot;1920600250041&quot;,&quot;name&quot;:&quot;นางสาวนฤมล บุญถาวร&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานบุคคลและงบประมาณ&quot;},{&quot;pID&quot;:&quot;1829900118058&quot;,&quot;name&quot;:&quot;นางสาวนัยน์เนตร ทองวล&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานบุคคลและงบประมาณ&quot;},{&quot;pID&quot;:&quot;1910300050321&quot;,&quot;name&quot;:&quot;นางสาวนิลญา หมานมิตร&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานบุคคลและงบประมาณ&quot;},{&quot;pID&quot;:&quot;1829900051727&quot;,&quot;name&quot;:&quot;นางสาวบงกชรัตน์  มาลี&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานบุคคลและงบประมาณ&quot;},{&quot;pID&quot;:&quot;1840100431373&quot;,&quot;name&quot;:&quot;นางสาวบุษรา  เมืองชู&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานบุคคลและงบประมาณ&quot;},{&quot;pID&quot;:&quot;1829900090897&quot;,&quot;name&quot;:&quot;นางสาวปริษา  แก้วเขียว&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานบุคคลและงบประมาณ&quot;},{&quot;pID&quot;:&quot;1820400055491&quot;,&quot;name&quot;:&quot;นางสาวปาณิสรา  มัจฉาเวช&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานบุคคลและงบประมาณ&quot;},{&quot;pID&quot;:&quot;1820600006469&quot;,&quot;name&quot;:&quot;นางสาวปิยธิดา นิยมเดชา&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานบุคคลและงบประมาณ&quot;},{&quot;pID&quot;:&quot;3820100171700&quot;,&quot;name&quot;:&quot;นางสาวพิมพ์จันทร์  สุวรรณดี&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานบุคคลและงบประมาณ&quot;},{&quot;pID&quot;:&quot;1820500130320&quot;,&quot;name&quot;:&quot;นางสาวลภัสภาส์ หนูคง&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานบุคคลและงบประมาณ&quot;},{&quot;pID&quot;:&quot;1940100013597&quot;,&quot;name&quot;:&quot;นางสาววรินญา โรจธนะวรรธน์&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานบุคคลและงบประมาณ&quot;},{&quot;pID&quot;:&quot;1840100326120&quot;,&quot;name&quot;:&quot;นางสาวศรัณย์รัชต์ สุขผ่องใส&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานบุคคลและงบประมาณ&quot;},{&quot;pID&quot;:&quot;1102001245405&quot;,&quot;name&quot;:&quot;นางสาวอมราภรณ์ เพ็ชรสุ่ม&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานบุคคลและงบประมาณ&quot;},{&quot;pID&quot;:&quot;3820700050342&quot;,&quot;name&quot;:&quot;นางสุมณฑา  เกิดทรัพย์&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานบุคคลและงบประมาณ&quot;},{&quot;pID&quot;:&quot;1820700004867&quot;,&quot;name&quot;:&quot;นางอรชา ชูเชื้อ&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานบุคคลและงบประมาณ&quot;},{&quot;pID&quot;:&quot;1800800331088&quot;,&quot;name&quot;:&quot;นายสราวุธ กุหลาบวรรณ&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานบุคคลและงบประมาณ&quot;},{&quot;pID&quot;:&quot;1640700056303&quot;,&quot;name&quot;:&quot;นายไชยวัฒน์ สังข์ทอง&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานบุคคลและงบประมาณ&quot;}]" name="faction_ids[]" value="3">
                                                                <span class="item-title">กลุ่มบริหารงานบุคคลและงบประมาณ</span>
                                                                <small class="item-subtext">สมาชิกทั้งหมด 26 คน</small>
                                                            </label>
                                                            <button type="button" class="group-toggle" aria-expanded="false" title="แสดง/ซ่อนรายชื่อสมาชิก">
                                                                <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                                                            </button>
                                                        </div>

                                                        <ol class="member-sublist">
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-3" data-member-name="นางจริยาวดี  เวชจันทร์" data-group-label="กลุ่มบริหารงานบุคคลและงบประมาณ" name="person_ids[]" value="5800900028151">
                                                                    <span class="member-name">นางจริยาวดี เวชจันทร์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-3" data-member-name="นางจิราภรณ์  เสรีรักษ์" data-group-label="กลุ่มบริหารงานบุคคลและงบประมาณ" name="person_ids[]" value="3800400522290">
                                                                    <span class="member-name">นางจิราภรณ์ เสรีรักษ์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-3" data-member-name="นางจุไรรัตน์ สวัสดิ์วงศ์" data-group-label="กลุ่มบริหารงานบุคคลและงบประมาณ" name="person_ids[]" value="3920100747937">
                                                                    <span class="member-name">นางจุไรรัตน์ สวัสดิ์วงศ์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-3" data-member-name="นางปวีณา  บำรุงภักดิ์" data-group-label="กลุ่มบริหารงานบุคคลและงบประมาณ" name="person_ids[]" value="1829900007736">
                                                                    <span class="member-name">นางปวีณา บำรุงภักดิ์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-3" data-member-name="นางฝาติหม๊ะ ขนาดผล" data-group-label="กลุ่มบริหารงานบุคคลและงบประมาณ" name="person_ids[]" value="1930600099890">
                                                                    <span class="member-name">นางฝาติหม๊ะ ขนาดผล</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-3" data-member-name="นางสาวกนกลักษณ์ พันธ์สวัสดิ์" data-group-label="กลุ่มบริหารงานบุคคลและงบประมาณ" name="person_ids[]" value="1829900179103">
                                                                    <span class="member-name">นางสาวกนกลักษณ์ พันธ์สวัสดิ์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-3" data-member-name="นางสาวกานต์พิชชา ปากลาว" data-group-label="กลุ่มบริหารงานบุคคลและงบประมาณ" name="person_ids[]" value="1810500062871">
                                                                    <span class="member-name">นางสาวกานต์พิชชา ปากลาว</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-3" data-member-name="นางสาวธนวรรณ พิทักษ์คง" data-group-label="กลุ่มบริหารงานบุคคลและงบประมาณ" name="person_ids[]" value="1820500147966">
                                                                    <span class="member-name">นางสาวธนวรรณ พิทักษ์คง</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-3" data-member-name="นางสาวธิดารัตน์ ทองกอบ" data-group-label="กลุ่มบริหารงานบุคคลและงบประมาณ" name="person_ids[]" value="1829900119712">
                                                                    <span class="member-name">นางสาวธิดารัตน์ ทองกอบ</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-3" data-member-name="นางสาวนฤมล บุญถาวร" data-group-label="กลุ่มบริหารงานบุคคลและงบประมาณ" name="person_ids[]" value="1920600250041">
                                                                    <span class="member-name">นางสาวนฤมล บุญถาวร</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-3" data-member-name="นางสาวนัยน์เนตร ทองวล" data-group-label="กลุ่มบริหารงานบุคคลและงบประมาณ" name="person_ids[]" value="1829900118058">
                                                                    <span class="member-name">นางสาวนัยน์เนตร ทองวล</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-3" data-member-name="นางสาวนิลญา หมานมิตร" data-group-label="กลุ่มบริหารงานบุคคลและงบประมาณ" name="person_ids[]" value="1910300050321">
                                                                    <span class="member-name">นางสาวนิลญา หมานมิตร</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-3" data-member-name="นางสาวบงกชรัตน์  มาลี" data-group-label="กลุ่มบริหารงานบุคคลและงบประมาณ" name="person_ids[]" value="1829900051727">
                                                                    <span class="member-name">นางสาวบงกชรัตน์ มาลี</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-3" data-member-name="นางสาวบุษรา  เมืองชู" data-group-label="กลุ่มบริหารงานบุคคลและงบประมาณ" name="person_ids[]" value="1840100431373">
                                                                    <span class="member-name">นางสาวบุษรา เมืองชู</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-3" data-member-name="นางสาวปริษา  แก้วเขียว" data-group-label="กลุ่มบริหารงานบุคคลและงบประมาณ" name="person_ids[]" value="1829900090897">
                                                                    <span class="member-name">นางสาวปริษา แก้วเขียว</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-3" data-member-name="นางสาวปาณิสรา  มัจฉาเวช" data-group-label="กลุ่มบริหารงานบุคคลและงบประมาณ" name="person_ids[]" value="1820400055491">
                                                                    <span class="member-name">นางสาวปาณิสรา มัจฉาเวช</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-3" data-member-name="นางสาวปิยธิดา นิยมเดชา" data-group-label="กลุ่มบริหารงานบุคคลและงบประมาณ" name="person_ids[]" value="1820600006469">
                                                                    <span class="member-name">นางสาวปิยธิดา นิยมเดชา</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-3" data-member-name="นางสาวพิมพ์จันทร์  สุวรรณดี" data-group-label="กลุ่มบริหารงานบุคคลและงบประมาณ" name="person_ids[]" value="3820100171700">
                                                                    <span class="member-name">นางสาวพิมพ์จันทร์ สุวรรณดี</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-3" data-member-name="นางสาวลภัสภาส์ หนูคง" data-group-label="กลุ่มบริหารงานบุคคลและงบประมาณ" name="person_ids[]" value="1820500130320">
                                                                    <span class="member-name">นางสาวลภัสภาส์ หนูคง</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-3" data-member-name="นางสาววรินญา โรจธนะวรรธน์" data-group-label="กลุ่มบริหารงานบุคคลและงบประมาณ" name="person_ids[]" value="1940100013597">
                                                                    <span class="member-name">นางสาววรินญา โรจธนะวรรธน์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-3" data-member-name="นางสาวศรัณย์รัชต์ สุขผ่องใส" data-group-label="กลุ่มบริหารงานบุคคลและงบประมาณ" name="person_ids[]" value="1840100326120">
                                                                    <span class="member-name">นางสาวศรัณย์รัชต์ สุขผ่องใส</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-3" data-member-name="นางสาวอมราภรณ์ เพ็ชรสุ่ม" data-group-label="กลุ่มบริหารงานบุคคลและงบประมาณ" name="person_ids[]" value="1102001245405">
                                                                    <span class="member-name">นางสาวอมราภรณ์ เพ็ชรสุ่ม</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-3" data-member-name="นางสุมณฑา  เกิดทรัพย์" data-group-label="กลุ่มบริหารงานบุคคลและงบประมาณ" name="person_ids[]" value="3820700050342">
                                                                    <span class="member-name">นางสุมณฑา เกิดทรัพย์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-3" data-member-name="นางอรชา ชูเชื้อ" data-group-label="กลุ่มบริหารงานบุคคลและงบประมาณ" name="person_ids[]" value="1820700004867">
                                                                    <span class="member-name">นางอรชา ชูเชื้อ</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-3" data-member-name="นายสราวุธ กุหลาบวรรณ" data-group-label="กลุ่มบริหารงานบุคคลและงบประมาณ" name="person_ids[]" value="1800800331088">
                                                                    <span class="member-name">นายสราวุธ กุหลาบวรรณ</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-3" data-member-name="นายไชยวัฒน์ สังข์ทอง" data-group-label="กลุ่มบริหารงานบุคคลและงบประมาณ" name="person_ids[]" value="1640700056303">
                                                                    <span class="member-name">นายไชยวัฒน์ สังข์ทอง</span>
                                                                </label>
                                                            </li>
                                                        </ol>
                                                    </div>
                                                    <div class="item item-group is-collapsed" data-faction-id="2">
                                                        <div class="group-header">
                                                            <label class="item-main">
                                                                <input type="checkbox" class="item-checkbox group-item-checkbox faction-item-checkbox" data-group="faction" data-group-key="faction-2" data-group-label="กลุ่มบริหารงานวิชาการ" data-members="[{&quot;pID&quot;:&quot;3810100580006&quot;,&quot;name&quot;:&quot;นางกนกวรรณ  ณ นคร&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานวิชาการ&quot;},{&quot;pID&quot;:&quot;3820100025592&quot;,&quot;name&quot;:&quot;นางจารุวรรณ ส่องศิริ&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานวิชาการ&quot;},{&quot;pID&quot;:&quot;3930300511171&quot;,&quot;name&quot;:&quot;นางณิภาภรณ์  ไชยชนะ&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานวิชาการ&quot;},{&quot;pID&quot;:&quot;3840100521778&quot;,&quot;name&quot;:&quot;นางดวงกมล  เพ็ชรพรหม&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานวิชาการ&quot;},{&quot;pID&quot;:&quot;3820300027670&quot;,&quot;name&quot;:&quot;นางดาริน ทรายทอง&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานวิชาการ&quot;},{&quot;pID&quot;:&quot;1829900063989&quot;,&quot;name&quot;:&quot;นางธนิษฐา  ยงยุทธ์&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานวิชาการ&quot;},{&quot;pID&quot;:&quot;3331001384867&quot;,&quot;name&quot;:&quot;นางประภาพร  อุดมผลชัยเจริญ&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานวิชาการ&quot;},{&quot;pID&quot;:&quot;1920600003469&quot;,&quot;name&quot;:&quot;นางผกาวรรณ  โชติวัฒนากร&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานวิชาการ&quot;},{&quot;pID&quot;:&quot;3940400027034&quot;,&quot;name&quot;:&quot;นางพนิดา ค้าของ&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานวิชาการ&quot;},{&quot;pID&quot;:&quot;1839900175043&quot;,&quot;name&quot;:&quot;นางพรพิมล แซ่เจี่ย&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานวิชาการ&quot;},{&quot;pID&quot;:&quot;1829900003064&quot;,&quot;name&quot;:&quot;นางพิมพา ทองอุไร&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานวิชาการ&quot;},{&quot;pID&quot;:&quot;1829900054688&quot;,&quot;name&quot;:&quot;นางสาวกนกรัตน์ อุ้ยเฉ้ง&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานวิชาการ&quot;},{&quot;pID&quot;:&quot;1829900059485&quot;,&quot;name&quot;:&quot;นางสาวจันทิพา ประทีป ณ ถลาง&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานวิชาการ&quot;},{&quot;pID&quot;:&quot;1930500083592&quot;,&quot;name&quot;:&quot;นางสาวจิราวัลย์  อินทร์อักษร&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานวิชาการ&quot;},{&quot;pID&quot;:&quot;3829900033725&quot;,&quot;name&quot;:&quot;นางสาวชนิกานต์  สวัสดิวงค์&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานวิชาการ&quot;},{&quot;pID&quot;:&quot;3840200430855&quot;,&quot;name&quot;:&quot;นางสาวณพสร สามสุวรรณ&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานวิชาการ&quot;},{&quot;pID&quot;:&quot;1729900457121&quot;,&quot;name&quot;:&quot;นางสาวณัฐวรรณ  ทรัพย์เฉลิม&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานวิชาการ&quot;},{&quot;pID&quot;:&quot;1829900202598&quot;,&quot;name&quot;:&quot;นางสาวธนวรรณ สมัครการ&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานวิชาการ&quot;},{&quot;pID&quot;:&quot;1820700006258&quot;,&quot;name&quot;:&quot;นางสาวนุชรีย์ หัศนี&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานวิชาการ&quot;},{&quot;pID&quot;:&quot;3840700282162&quot;,&quot;name&quot;:&quot;นางสาวประภัสสร  โอจันทร์&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานวิชาการ&quot;},{&quot;pID&quot;:&quot;1410100117524&quot;,&quot;name&quot;:&quot;นางสาวประภาพรรณ กุลแก้ว&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานวิชาการ&quot;},{&quot;pID&quot;:&quot;1829900096909&quot;,&quot;name&quot;:&quot;นางสาวพิมพ์ประภา  ผลากิจ&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานวิชาการ&quot;},{&quot;pID&quot;:&quot;3820400028481&quot;,&quot;name&quot;:&quot;นางสาวยศยา ศักดิ์ศิลปศาสตร์&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานวิชาการ&quot;},{&quot;pID&quot;:&quot;1829900012535&quot;,&quot;name&quot;:&quot;นางสาวรัชฎาพร สุวรรณสาม&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานวิชาการ&quot;},{&quot;pID&quot;:&quot;1820500097624&quot;,&quot;name&quot;:&quot;นางสาวรัชนีกร ผอมจีน&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานวิชาการ&quot;},{&quot;pID&quot;:&quot;3820700136859&quot;,&quot;name&quot;:&quot;นางสาวลภัสนันท์ บำรุงวงศ์&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานวิชาการ&quot;},{&quot;pID&quot;:&quot;3801600044431&quot;,&quot;name&quot;:&quot;นางสาวศศิธร นาคสง&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานวิชาการ&quot;},{&quot;pID&quot;:&quot;1829900099401&quot;,&quot;name&quot;:&quot;นางสาวศุลีพร ขันภักดี&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานวิชาการ&quot;},{&quot;pID&quot;:&quot;1829900065485&quot;,&quot;name&quot;:&quot;นางสาวองค์ปรางค์ แสงสุรินทร์&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานวิชาการ&quot;},{&quot;pID&quot;:&quot;1909901558298&quot;,&quot;name&quot;:&quot;นางสาวอภิชญา จันทร์มา&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานวิชาการ&quot;},{&quot;pID&quot;:&quot;1800100218262&quot;,&quot;name&quot;:&quot;นางสาวอาตีนา  พัชนี&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานวิชาการ&quot;},{&quot;pID&quot;:&quot;1800800204043&quot;,&quot;name&quot;:&quot;นางสาวอินทิรา บุญนิสสัย&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานวิชาการ&quot;},{&quot;pID&quot;:&quot;1930500116202&quot;,&quot;name&quot;:&quot;นางสุรางค์รัศมิ์ ย้อยพระจันทร์&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานวิชาการ&quot;},{&quot;pID&quot;:&quot;3820700019381&quot;,&quot;name&quot;:&quot;นางเสาวลีย์ จันทร์ทอง&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานวิชาการ&quot;},{&quot;pID&quot;:&quot;1859900070560&quot;,&quot;name&quot;:&quot;นายจตุรวิทย์ มิตรวงศ์&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานวิชาการ&quot;},{&quot;pID&quot;:&quot;1809901028575&quot;,&quot;name&quot;:&quot;นายธนวัฒน์ ศิริพงศ์ประพันธ์&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานวิชาการ&quot;},{&quot;pID&quot;:&quot;1959900030702&quot;,&quot;name&quot;:&quot;นายธันวิน  ณ นคร&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานวิชาการ&quot;},{&quot;pID&quot;:&quot;1839900094990&quot;,&quot;name&quot;:&quot;นายนพรัตน์ ย้อยพระจันทร์&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานวิชาการ&quot;},{&quot;pID&quot;:&quot;1819900163142&quot;,&quot;name&quot;:&quot;นายบพิธ มังคะลา&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานวิชาการ&quot;},{&quot;pID&quot;:&quot;1920400002230&quot;,&quot;name&quot;:&quot;นายประสิทธิ์  สะไน&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานวิชาการ&quot;},{&quot;pID&quot;:&quot;3940400221191&quot;,&quot;name&quot;:&quot;นายพิพัฒน์ ไชยชนะ&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานวิชาการ&quot;},{&quot;pID&quot;:&quot;3820800037747&quot;,&quot;name&quot;:&quot;นายศรายุทธ  มิตรวงค์&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานวิชาการ&quot;},{&quot;pID&quot;:&quot;1829900056460&quot;,&quot;name&quot;:&quot;นายศุภวุฒิ &nbsp;อินทร์แก้ว&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานวิชาการ&quot;},{&quot;pID&quot;:&quot;3820700143669&quot;,&quot;name&quot;:&quot;นายสมชาย สุทธจิตร์&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานวิชาการ&quot;},{&quot;pID&quot;:&quot;3820400194578&quot;,&quot;name&quot;:&quot;นายสุพัฒน์  เจริญฤทธิ์&quot;,&quot;faction&quot;:&quot;กลุ่มบริหารงานวิชาการ&quot;}]" name="faction_ids[]" value="2">
                                                                <span class="item-title">กลุ่มบริหารงานวิชาการ</span>
                                                                <small class="item-subtext">สมาชิกทั้งหมด 45 คน</small>
                                                            </label>
                                                            <button type="button" class="group-toggle" aria-expanded="false" title="แสดง/ซ่อนรายชื่อสมาชิก">
                                                                <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                                                            </button>
                                                        </div>

                                                        <ol class="member-sublist">
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-2" data-member-name="นางกนกวรรณ  ณ นคร" data-group-label="กลุ่มบริหารงานวิชาการ" name="person_ids[]" value="3810100580006">
                                                                    <span class="member-name">นางกนกวรรณ ณ นคร</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-2" data-member-name="นางจารุวรรณ ส่องศิริ" data-group-label="กลุ่มบริหารงานวิชาการ" name="person_ids[]" value="3820100025592">
                                                                    <span class="member-name">นางจารุวรรณ ส่องศิริ</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-2" data-member-name="นางณิภาภรณ์  ไชยชนะ" data-group-label="กลุ่มบริหารงานวิชาการ" name="person_ids[]" value="3930300511171">
                                                                    <span class="member-name">นางณิภาภรณ์ ไชยชนะ</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-2" data-member-name="นางดวงกมล  เพ็ชรพรหม" data-group-label="กลุ่มบริหารงานวิชาการ" name="person_ids[]" value="3840100521778">
                                                                    <span class="member-name">นางดวงกมล เพ็ชรพรหม</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-2" data-member-name="นางดาริน ทรายทอง" data-group-label="กลุ่มบริหารงานวิชาการ" name="person_ids[]" value="3820300027670">
                                                                    <span class="member-name">นางดาริน ทรายทอง</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-2" data-member-name="นางธนิษฐา  ยงยุทธ์" data-group-label="กลุ่มบริหารงานวิชาการ" name="person_ids[]" value="1829900063989">
                                                                    <span class="member-name">นางธนิษฐา ยงยุทธ์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-2" data-member-name="นางประภาพร  อุดมผลชัยเจริญ" data-group-label="กลุ่มบริหารงานวิชาการ" name="person_ids[]" value="3331001384867">
                                                                    <span class="member-name">นางประภาพร อุดมผลชัยเจริญ</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-2" data-member-name="นางผกาวรรณ  โชติวัฒนากร" data-group-label="กลุ่มบริหารงานวิชาการ" name="person_ids[]" value="1920600003469">
                                                                    <span class="member-name">นางผกาวรรณ โชติวัฒนากร</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-2" data-member-name="นางพนิดา ค้าของ" data-group-label="กลุ่มบริหารงานวิชาการ" name="person_ids[]" value="3940400027034">
                                                                    <span class="member-name">นางพนิดา ค้าของ</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-2" data-member-name="นางพรพิมล แซ่เจี่ย" data-group-label="กลุ่มบริหารงานวิชาการ" name="person_ids[]" value="1839900175043">
                                                                    <span class="member-name">นางพรพิมล แซ่เจี่ย</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-2" data-member-name="นางพิมพา ทองอุไร" data-group-label="กลุ่มบริหารงานวิชาการ" name="person_ids[]" value="1829900003064">
                                                                    <span class="member-name">นางพิมพา ทองอุไร</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-2" data-member-name="นางสาวกนกรัตน์ อุ้ยเฉ้ง" data-group-label="กลุ่มบริหารงานวิชาการ" name="person_ids[]" value="1829900054688">
                                                                    <span class="member-name">นางสาวกนกรัตน์ อุ้ยเฉ้ง</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-2" data-member-name="นางสาวจันทิพา ประทีป ณ ถลาง" data-group-label="กลุ่มบริหารงานวิชาการ" name="person_ids[]" value="1829900059485">
                                                                    <span class="member-name">นางสาวจันทิพา ประทีป ณ ถลาง</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-2" data-member-name="นางสาวจิราวัลย์  อินทร์อักษร" data-group-label="กลุ่มบริหารงานวิชาการ" name="person_ids[]" value="1930500083592">
                                                                    <span class="member-name">นางสาวจิราวัลย์ อินทร์อักษร</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-2" data-member-name="นางสาวชนิกานต์  สวัสดิวงค์" data-group-label="กลุ่มบริหารงานวิชาการ" name="person_ids[]" value="3829900033725">
                                                                    <span class="member-name">นางสาวชนิกานต์ สวัสดิวงค์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-2" data-member-name="นางสาวณพสร สามสุวรรณ" data-group-label="กลุ่มบริหารงานวิชาการ" name="person_ids[]" value="3840200430855">
                                                                    <span class="member-name">นางสาวณพสร สามสุวรรณ</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-2" data-member-name="นางสาวณัฐวรรณ  ทรัพย์เฉลิม" data-group-label="กลุ่มบริหารงานวิชาการ" name="person_ids[]" value="1729900457121">
                                                                    <span class="member-name">นางสาวณัฐวรรณ ทรัพย์เฉลิม</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-2" data-member-name="นางสาวธนวรรณ สมัครการ" data-group-label="กลุ่มบริหารงานวิชาการ" name="person_ids[]" value="1829900202598">
                                                                    <span class="member-name">นางสาวธนวรรณ สมัครการ</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-2" data-member-name="นางสาวนุชรีย์ หัศนี" data-group-label="กลุ่มบริหารงานวิชาการ" name="person_ids[]" value="1820700006258">
                                                                    <span class="member-name">นางสาวนุชรีย์ หัศนี</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-2" data-member-name="นางสาวประภัสสร  โอจันทร์" data-group-label="กลุ่มบริหารงานวิชาการ" name="person_ids[]" value="3840700282162">
                                                                    <span class="member-name">นางสาวประภัสสร โอจันทร์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-2" data-member-name="นางสาวประภาพรรณ กุลแก้ว" data-group-label="กลุ่มบริหารงานวิชาการ" name="person_ids[]" value="1410100117524">
                                                                    <span class="member-name">นางสาวประภาพรรณ กุลแก้ว</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-2" data-member-name="นางสาวพิมพ์ประภา  ผลากิจ" data-group-label="กลุ่มบริหารงานวิชาการ" name="person_ids[]" value="1829900096909">
                                                                    <span class="member-name">นางสาวพิมพ์ประภา ผลากิจ</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-2" data-member-name="นางสาวยศยา ศักดิ์ศิลปศาสตร์" data-group-label="กลุ่มบริหารงานวิชาการ" name="person_ids[]" value="3820400028481">
                                                                    <span class="member-name">นางสาวยศยา ศักดิ์ศิลปศาสตร์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-2" data-member-name="นางสาวรัชฎาพร สุวรรณสาม" data-group-label="กลุ่มบริหารงานวิชาการ" name="person_ids[]" value="1829900012535">
                                                                    <span class="member-name">นางสาวรัชฎาพร สุวรรณสาม</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-2" data-member-name="นางสาวรัชนีกร ผอมจีน" data-group-label="กลุ่มบริหารงานวิชาการ" name="person_ids[]" value="1820500097624">
                                                                    <span class="member-name">นางสาวรัชนีกร ผอมจีน</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-2" data-member-name="นางสาวลภัสนันท์ บำรุงวงศ์" data-group-label="กลุ่มบริหารงานวิชาการ" name="person_ids[]" value="3820700136859">
                                                                    <span class="member-name">นางสาวลภัสนันท์ บำรุงวงศ์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-2" data-member-name="นางสาวศศิธร นาคสง" data-group-label="กลุ่มบริหารงานวิชาการ" name="person_ids[]" value="3801600044431">
                                                                    <span class="member-name">นางสาวศศิธร นาคสง</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-2" data-member-name="นางสาวศุลีพร ขันภักดี" data-group-label="กลุ่มบริหารงานวิชาการ" name="person_ids[]" value="1829900099401">
                                                                    <span class="member-name">นางสาวศุลีพร ขันภักดี</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-2" data-member-name="นางสาวองค์ปรางค์ แสงสุรินทร์" data-group-label="กลุ่มบริหารงานวิชาการ" name="person_ids[]" value="1829900065485">
                                                                    <span class="member-name">นางสาวองค์ปรางค์ แสงสุรินทร์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-2" data-member-name="นางสาวอภิชญา จันทร์มา" data-group-label="กลุ่มบริหารงานวิชาการ" name="person_ids[]" value="1909901558298">
                                                                    <span class="member-name">นางสาวอภิชญา จันทร์มา</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-2" data-member-name="นางสาวอาตีนา  พัชนี" data-group-label="กลุ่มบริหารงานวิชาการ" name="person_ids[]" value="1800100218262">
                                                                    <span class="member-name">นางสาวอาตีนา พัชนี</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-2" data-member-name="นางสาวอินทิรา บุญนิสสัย" data-group-label="กลุ่มบริหารงานวิชาการ" name="person_ids[]" value="1800800204043">
                                                                    <span class="member-name">นางสาวอินทิรา บุญนิสสัย</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-2" data-member-name="นางสุรางค์รัศมิ์ ย้อยพระจันทร์" data-group-label="กลุ่มบริหารงานวิชาการ" name="person_ids[]" value="1930500116202">
                                                                    <span class="member-name">นางสุรางค์รัศมิ์ ย้อยพระจันทร์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-2" data-member-name="นางเสาวลีย์ จันทร์ทอง" data-group-label="กลุ่มบริหารงานวิชาการ" name="person_ids[]" value="3820700019381">
                                                                    <span class="member-name">นางเสาวลีย์ จันทร์ทอง</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-2" data-member-name="นายจตุรวิทย์ มิตรวงศ์" data-group-label="กลุ่มบริหารงานวิชาการ" name="person_ids[]" value="1859900070560">
                                                                    <span class="member-name">นายจตุรวิทย์ มิตรวงศ์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-2" data-member-name="นายธนวัฒน์ ศิริพงศ์ประพันธ์" data-group-label="กลุ่มบริหารงานวิชาการ" name="person_ids[]" value="1809901028575">
                                                                    <span class="member-name">นายธนวัฒน์ ศิริพงศ์ประพันธ์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-2" data-member-name="นายธันวิน  ณ นคร" data-group-label="กลุ่มบริหารงานวิชาการ" name="person_ids[]" value="1959900030702">
                                                                    <span class="member-name">นายธันวิน ณ นคร</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-2" data-member-name="นายนพรัตน์ ย้อยพระจันทร์" data-group-label="กลุ่มบริหารงานวิชาการ" name="person_ids[]" value="1839900094990">
                                                                    <span class="member-name">นายนพรัตน์ ย้อยพระจันทร์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-2" data-member-name="นายบพิธ มังคะลา" data-group-label="กลุ่มบริหารงานวิชาการ" name="person_ids[]" value="1819900163142">
                                                                    <span class="member-name">นายบพิธ มังคะลา</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-2" data-member-name="นายประสิทธิ์  สะไน" data-group-label="กลุ่มบริหารงานวิชาการ" name="person_ids[]" value="1920400002230">
                                                                    <span class="member-name">นายประสิทธิ์ สะไน</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-2" data-member-name="นายพิพัฒน์ ไชยชนะ" data-group-label="กลุ่มบริหารงานวิชาการ" name="person_ids[]" value="3940400221191">
                                                                    <span class="member-name">นายพิพัฒน์ ไชยชนะ</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-2" data-member-name="นายศรายุทธ  มิตรวงค์" data-group-label="กลุ่มบริหารงานวิชาการ" name="person_ids[]" value="3820800037747">
                                                                    <span class="member-name">นายศรายุทธ มิตรวงค์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-2" data-member-name="นายศุภวุฒิ &nbsp;อินทร์แก้ว" data-group-label="กลุ่มบริหารงานวิชาการ" name="person_ids[]" value="1829900056460">
                                                                    <span class="member-name">นายศุภวุฒิ &nbsp;อินทร์แก้ว</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-2" data-member-name="นายสมชาย สุทธจิตร์" data-group-label="กลุ่มบริหารงานวิชาการ" name="person_ids[]" value="3820700143669">
                                                                    <span class="member-name">นายสมชาย สุทธจิตร์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-2" data-member-name="นายสุพัฒน์  เจริญฤทธิ์" data-group-label="กลุ่มบริหารงานวิชาการ" name="person_ids[]" value="3820400194578">
                                                                    <span class="member-name">นายสุพัฒน์ เจริญฤทธิ์</span>
                                                                </label>
                                                            </li>
                                                        </ol>
                                                    </div>
                                                    <div class="item item-group is-collapsed" data-faction-id="6">
                                                        <div class="group-header">
                                                            <label class="item-main">
                                                                <input type="checkbox" class="item-checkbox group-item-checkbox faction-item-checkbox" data-group="faction" data-group-key="faction-6" data-group-label="กลุ่มสนับสนุนการสอน" data-members="[{&quot;pID&quot;:&quot;1820700059157&quot;,&quot;name&quot;:&quot;นางสาวนัฐลิณี ทอสงค์&quot;,&quot;faction&quot;:&quot;กลุ่มสนับสนุนการสอน&quot;},{&quot;pID&quot;:&quot;1829900149409&quot;,&quot;name&quot;:&quot;นางสาวอุบลวรรณ คงสม&quot;,&quot;faction&quot;:&quot;กลุ่มสนับสนุนการสอน&quot;},{&quot;pID&quot;:&quot;3810200084621&quot;,&quot;name&quot;:&quot;นายสิงหนาท  แต่งแก้ว&quot;,&quot;faction&quot;:&quot;กลุ่มสนับสนุนการสอน&quot;}]" name="faction_ids[]" value="6">
                                                                <span class="item-title">กลุ่มสนับสนุนการสอน</span>
                                                                <small class="item-subtext">สมาชิกทั้งหมด 3 คน</small>
                                                            </label>
                                                            <button type="button" class="group-toggle" aria-expanded="false" title="แสดง/ซ่อนรายชื่อสมาชิก">
                                                                <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                                                            </button>
                                                        </div>

                                                        <ol class="member-sublist">
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-6" data-member-name="นางสาวนัฐลิณี ทอสงค์" data-group-label="กลุ่มสนับสนุนการสอน" name="person_ids[]" value="1820700059157">
                                                                    <span class="member-name">นางสาวนัฐลิณี ทอสงค์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-6" data-member-name="นางสาวอุบลวรรณ คงสม" data-group-label="กลุ่มสนับสนุนการสอน" name="person_ids[]" value="1829900149409">
                                                                    <span class="member-name">นางสาวอุบลวรรณ คงสม</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="faction-6" data-member-name="นายสิงหนาท  แต่งแก้ว" data-group-label="กลุ่มสนับสนุนการสอน" name="person_ids[]" value="3810200084621">
                                                                    <span class="member-name">นายสิงหนาท แต่งแก้ว</span>
                                                                </label>
                                                            </li>
                                                        </ol>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="category-group">
                                                <div class="category-title">
                                                    <span>กลุ่มสาระ</span>
                                                </div>
                                                <div class="category-items">
                                                    <div class="item item-group is-collapsed">
                                                        <div class="group-header">
                                                            <label class="item-main">
                                                                <input type="checkbox" class="item-checkbox group-item-checkbox department-item-checkbox" data-group="department" data-group-key="department-9" data-group-label="กลุ่มกิจกรรมพัฒนาผู้เรียน" data-members="[{&quot;pID&quot;:&quot;1820700006258&quot;,&quot;name&quot;:&quot;นางสาวนุชรีย์ หัศนี&quot;,&quot;faction&quot;:&quot;กลุ่มกิจกรรมพัฒนาผู้เรียน&quot;},{&quot;pID&quot;:&quot;1102001245405&quot;,&quot;name&quot;:&quot;นางสาวอมราภรณ์ เพ็ชรสุ่ม&quot;,&quot;faction&quot;:&quot;กลุ่มกิจกรรมพัฒนาผู้เรียน&quot;},{&quot;pID&quot;:&quot;3810200084621&quot;,&quot;name&quot;:&quot;นายสิงหนาท  แต่งแก้ว&quot;,&quot;faction&quot;:&quot;กลุ่มกิจกรรมพัฒนาผู้เรียน&quot;}]" value="department-9">
                                                                <span class="item-title">กลุ่มกิจกรรมพัฒนาผู้เรียน</span>
                                                                <small class="item-subtext">สมาชิกทั้งหมด 3 คน</small>
                                                            </label>
                                                            <button type="button" class="group-toggle" aria-expanded="false" title="แสดง/ซ่อนรายชื่อสมาชิก">
                                                                <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                                                            </button>
                                                        </div>

                                                        <ol class="member-sublist">
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-9" data-member-name="นางสาวนุชรีย์ หัศนี" data-group-label="กลุ่มกิจกรรมพัฒนาผู้เรียน" name="person_ids[]" value="1820700006258">
                                                                    <span class="member-name">นางสาวนุชรีย์ หัศนี</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-9" data-member-name="นางสาวอมราภรณ์ เพ็ชรสุ่ม" data-group-label="กลุ่มกิจกรรมพัฒนาผู้เรียน" name="person_ids[]" value="1102001245405">
                                                                    <span class="member-name">นางสาวอมราภรณ์ เพ็ชรสุ่ม</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-9" data-member-name="นายสิงหนาท  แต่งแก้ว" data-group-label="กลุ่มกิจกรรมพัฒนาผู้เรียน" name="person_ids[]" value="3810200084621">
                                                                    <span class="member-name">นายสิงหนาท แต่งแก้ว</span>
                                                                </label>
                                                            </li>
                                                        </ol>
                                                    </div>
                                                    <div class="item item-group is-collapsed">
                                                        <div class="group-header">
                                                            <label class="item-main">
                                                                <input type="checkbox" class="item-checkbox group-item-checkbox department-item-checkbox" data-group="department" data-group-key="department-10" data-group-label="กลุ่มคอมพิวเตอร์และเทคโนโลยี" data-members="[{&quot;pID&quot;:&quot;1930500083592&quot;,&quot;name&quot;:&quot;นางสาวจิราวัลย์  อินทร์อักษร&quot;,&quot;faction&quot;:&quot;กลุ่มคอมพิวเตอร์และเทคโนโลยี&quot;},{&quot;pID&quot;:&quot;3801600044431&quot;,&quot;name&quot;:&quot;นางสาวศศิธร นาคสง&quot;,&quot;faction&quot;:&quot;กลุ่มคอมพิวเตอร์และเทคโนโลยี&quot;},{&quot;pID&quot;:&quot;1859900070560&quot;,&quot;name&quot;:&quot;นายจตุรวิทย์ มิตรวงศ์&quot;,&quot;faction&quot;:&quot;กลุ่มคอมพิวเตอร์และเทคโนโลยี&quot;},{&quot;pID&quot;:&quot;1959900030702&quot;,&quot;name&quot;:&quot;นายธันวิน  ณ นคร&quot;,&quot;faction&quot;:&quot;กลุ่มคอมพิวเตอร์และเทคโนโลยี&quot;},{&quot;pID&quot;:&quot;1819900163142&quot;,&quot;name&quot;:&quot;นายบพิธ มังคะลา&quot;,&quot;faction&quot;:&quot;กลุ่มคอมพิวเตอร์และเทคโนโลยี&quot;},{&quot;pID&quot;:&quot;3810500157631&quot;,&quot;name&quot;:&quot;นายสหัส เสือยืนยง&quot;,&quot;faction&quot;:&quot;กลุ่มคอมพิวเตอร์และเทคโนโลยี&quot;}]" value="department-10">
                                                                <span class="item-title">กลุ่มคอมพิวเตอร์และเทคโนโลยี</span>
                                                                <small class="item-subtext">สมาชิกทั้งหมด 6 คน</small>
                                                            </label>
                                                            <button type="button" class="group-toggle" aria-expanded="false" title="แสดง/ซ่อนรายชื่อสมาชิก">
                                                                <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                                                            </button>
                                                        </div>

                                                        <ol class="member-sublist">
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-10" data-member-name="นางสาวจิราวัลย์  อินทร์อักษร" data-group-label="กลุ่มคอมพิวเตอร์และเทคโนโลยี" name="person_ids[]" value="1930500083592">
                                                                    <span class="member-name">นางสาวจิราวัลย์ อินทร์อักษร</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-10" data-member-name="นางสาวศศิธร นาคสง" data-group-label="กลุ่มคอมพิวเตอร์และเทคโนโลยี" name="person_ids[]" value="3801600044431">
                                                                    <span class="member-name">นางสาวศศิธร นาคสง</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-10" data-member-name="นายจตุรวิทย์ มิตรวงศ์" data-group-label="กลุ่มคอมพิวเตอร์และเทคโนโลยี" name="person_ids[]" value="1859900070560">
                                                                    <span class="member-name">นายจตุรวิทย์ มิตรวงศ์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-10" data-member-name="นายธันวิน  ณ นคร" data-group-label="กลุ่มคอมพิวเตอร์และเทคโนโลยี" name="person_ids[]" value="1959900030702">
                                                                    <span class="member-name">นายธันวิน ณ นคร</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-10" data-member-name="นายบพิธ มังคะลา" data-group-label="กลุ่มคอมพิวเตอร์และเทคโนโลยี" name="person_ids[]" value="1819900163142">
                                                                    <span class="member-name">นายบพิธ มังคะลา</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-10" data-member-name="นายสหัส เสือยืนยง" data-group-label="กลุ่มคอมพิวเตอร์และเทคโนโลยี" name="person_ids[]" value="3810500157631">
                                                                    <span class="member-name">นายสหัส เสือยืนยง</span>
                                                                </label>
                                                            </li>
                                                        </ol>
                                                    </div>
                                                    <div class="item item-group is-collapsed">
                                                        <div class="group-header">
                                                            <label class="item-main">
                                                                <input type="checkbox" class="item-checkbox group-item-checkbox department-item-checkbox" data-group="department" data-group-key="department-11" data-group-label="กลุ่มธุรการ" data-members="[{&quot;pID&quot;:&quot;3820400234871&quot;,&quot;name&quot;:&quot;นางนวลน้อย  ชูสงค์&quot;,&quot;faction&quot;:&quot;กลุ่มธุรการ&quot;},{&quot;pID&quot;:&quot;1800700082485&quot;,&quot;name&quot;:&quot;นางสาว ณัฐชลียา ยิ่งคง&quot;,&quot;faction&quot;:&quot;กลุ่มธุรการ&quot;},{&quot;pID&quot;:&quot;1829900082835&quot;,&quot;name&quot;:&quot;นางสาวจารุลักษณ์  ตรีศรี&quot;,&quot;faction&quot;:&quot;กลุ่มธุรการ&quot;},{&quot;pID&quot;:&quot;3820100155283&quot;,&quot;name&quot;:&quot;นางสาวจิราวรรณ ว่องปลูกศิลป์&quot;,&quot;faction&quot;:&quot;กลุ่มธุรการ&quot;},{&quot;pID&quot;:&quot;2800800033557&quot;,&quot;name&quot;:&quot;นางสาวธัญเรศ  วรศานต์&quot;,&quot;faction&quot;:&quot;กลุ่มธุรการ&quot;},{&quot;pID&quot;:&quot;3820600035619&quot;,&quot;name&quot;:&quot;นางสาวนภัสสร  รัฐการ&quot;,&quot;faction&quot;:&quot;กลุ่มธุรการ&quot;},{&quot;pID&quot;:&quot;1810600075673&quot;,&quot;name&quot;:&quot;นางสาวประภัสสร พันธ์แก้ว&quot;,&quot;faction&quot;:&quot;กลุ่มธุรการ&quot;},{&quot;pID&quot;:&quot;3820100140782&quot;,&quot;name&quot;:&quot;นางสาวศศิธร  มธุรส&quot;,&quot;faction&quot;:&quot;กลุ่มธุรการ&quot;},{&quot;pID&quot;:&quot;3810300076964&quot;,&quot;name&quot;:&quot;นายอดิศักดิ์  ธรรมจิตต์&quot;,&quot;faction&quot;:&quot;กลุ่มธุรการ&quot;},{&quot;pID&quot;:&quot;1640700056303&quot;,&quot;name&quot;:&quot;นายไชยวัฒน์ สังข์ทอง&quot;,&quot;faction&quot;:&quot;กลุ่มธุรการ&quot;}]" value="department-11">
                                                                <span class="item-title">กลุ่มธุรการ</span>
                                                                <small class="item-subtext">สมาชิกทั้งหมด 10 คน</small>
                                                            </label>
                                                            <button type="button" class="group-toggle" aria-expanded="false" title="แสดง/ซ่อนรายชื่อสมาชิก">
                                                                <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                                                            </button>
                                                        </div>

                                                        <ol class="member-sublist">
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-11" data-member-name="นางนวลน้อย  ชูสงค์" data-group-label="กลุ่มธุรการ" name="person_ids[]" value="3820400234871">
                                                                    <span class="member-name">นางนวลน้อย ชูสงค์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-11" data-member-name="นางสาว ณัฐชลียา ยิ่งคง" data-group-label="กลุ่มธุรการ" name="person_ids[]" value="1800700082485">
                                                                    <span class="member-name">นางสาว ณัฐชลียา ยิ่งคง</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-11" data-member-name="นางสาวจารุลักษณ์  ตรีศรี" data-group-label="กลุ่มธุรการ" name="person_ids[]" value="1829900082835">
                                                                    <span class="member-name">นางสาวจารุลักษณ์ ตรีศรี</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-11" data-member-name="นางสาวจิราวรรณ ว่องปลูกศิลป์" data-group-label="กลุ่มธุรการ" name="person_ids[]" value="3820100155283">
                                                                    <span class="member-name">นางสาวจิราวรรณ ว่องปลูกศิลป์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-11" data-member-name="นางสาวธัญเรศ  วรศานต์" data-group-label="กลุ่มธุรการ" name="person_ids[]" value="2800800033557">
                                                                    <span class="member-name">นางสาวธัญเรศ วรศานต์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-11" data-member-name="นางสาวนภัสสร  รัฐการ" data-group-label="กลุ่มธุรการ" name="person_ids[]" value="3820600035619">
                                                                    <span class="member-name">นางสาวนภัสสร รัฐการ</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-11" data-member-name="นางสาวประภัสสร พันธ์แก้ว" data-group-label="กลุ่มธุรการ" name="person_ids[]" value="1810600075673">
                                                                    <span class="member-name">นางสาวประภัสสร พันธ์แก้ว</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-11" data-member-name="นางสาวศศิธร  มธุรส" data-group-label="กลุ่มธุรการ" name="person_ids[]" value="3820100140782">
                                                                    <span class="member-name">นางสาวศศิธร มธุรส</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-11" data-member-name="นายอดิศักดิ์  ธรรมจิตต์" data-group-label="กลุ่มธุรการ" name="person_ids[]" value="3810300076964">
                                                                    <span class="member-name">นายอดิศักดิ์ ธรรมจิตต์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-11" data-member-name="นายไชยวัฒน์ สังข์ทอง" data-group-label="กลุ่มธุรการ" name="person_ids[]" value="1640700056303">
                                                                    <span class="member-name">นายไชยวัฒน์ สังข์ทอง</span>
                                                                </label>
                                                            </li>
                                                        </ol>
                                                    </div>
                                                    <div class="item item-group is-collapsed">
                                                        <div class="group-header">
                                                            <label class="item-main">
                                                                <input type="checkbox" class="item-checkbox group-item-checkbox department-item-checkbox" data-group="department" data-group-key="department-7" data-group-label="กลุ่มสาระฯ การงานอาชีพ" data-members="[{&quot;pID&quot;:&quot;1829900062591&quot;,&quot;name&quot;:&quot;นางสาวจารุวรรณ ผลแก้ว&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ การงานอาชีพ&quot;},{&quot;pID&quot;:&quot;3810500179350&quot;,&quot;name&quot;:&quot;นางสาวนงลักษณ์   แก้วสว่าง&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ การงานอาชีพ&quot;},{&quot;pID&quot;:&quot;1849900176813&quot;,&quot;name&quot;:&quot;นายชนม์กมล เพ็ขรพรหม&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ การงานอาชีพ&quot;},{&quot;pID&quot;:&quot;1829900003064&quot;,&quot;name&quot;:&quot;นางพิมพา ทองอุไร&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ การงานอาชีพ&quot;},{&quot;pID&quot;:&quot;5800900028151&quot;,&quot;name&quot;:&quot;นางจริยาวดี  เวชจันทร์&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ การงานอาชีพ&quot;},{&quot;pID&quot;:&quot;3800400522290&quot;,&quot;name&quot;:&quot;นางจิราภรณ์  เสรีรักษ์&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ การงานอาชีพ&quot;},{&quot;pID&quot;:&quot;3820100172170&quot;,&quot;name&quot;:&quot;นางพูนสุข ถิ่นลิพอน&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ การงานอาชีพ&quot;},{&quot;pID&quot;:&quot;1809900084706&quot;,&quot;name&quot;:&quot;นางภทรมน ลิ่มบุตร&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ การงานอาชีพ&quot;},{&quot;pID&quot;:&quot;1860100007288&quot;,&quot;name&quot;:&quot;นายนพพร  ถิ่นไทย&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ การงานอาชีพ&quot;}]" value="department-7">
                                                                <span class="item-title">กลุ่มสาระฯ การงานอาชีพ</span>
                                                                <small class="item-subtext">สมาชิกทั้งหมด 9 คน</small>
                                                            </label>
                                                            <button type="button" class="group-toggle" aria-expanded="false" title="แสดง/ซ่อนรายชื่อสมาชิก">
                                                                <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                                                            </button>
                                                        </div>

                                                        <ol class="member-sublist">
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-7" data-member-name="นางสาวจารุวรรณ ผลแก้ว" data-group-label="กลุ่มสาระฯ การงานอาชีพ" name="person_ids[]" value="1829900062591">
                                                                    <span class="member-name">นางสาวจารุวรรณ ผลแก้ว</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-7" data-member-name="นางสาวนงลักษณ์   แก้วสว่าง" data-group-label="กลุ่มสาระฯ การงานอาชีพ" name="person_ids[]" value="3810500179350">
                                                                    <span class="member-name">นางสาวนงลักษณ์ แก้วสว่าง</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-7" data-member-name="นายชนม์กมล เพ็ขรพรหม" data-group-label="กลุ่มสาระฯ การงานอาชีพ" name="person_ids[]" value="1849900176813">
                                                                    <span class="member-name">นายชนม์กมล เพ็ขรพรหม</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-7" data-member-name="นางพิมพา ทองอุไร" data-group-label="กลุ่มสาระฯ การงานอาชีพ" name="person_ids[]" value="1829900003064">
                                                                    <span class="member-name">นางพิมพา ทองอุไร</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-7" data-member-name="นางจริยาวดี  เวชจันทร์" data-group-label="กลุ่มสาระฯ การงานอาชีพ" name="person_ids[]" value="5800900028151">
                                                                    <span class="member-name">นางจริยาวดี เวชจันทร์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-7" data-member-name="นางจิราภรณ์  เสรีรักษ์" data-group-label="กลุ่มสาระฯ การงานอาชีพ" name="person_ids[]" value="3800400522290">
                                                                    <span class="member-name">นางจิราภรณ์ เสรีรักษ์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-7" data-member-name="นางพูนสุข ถิ่นลิพอน" data-group-label="กลุ่มสาระฯ การงานอาชีพ" name="person_ids[]" value="3820100172170">
                                                                    <span class="member-name">นางพูนสุข ถิ่นลิพอน</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-7" data-member-name="นางภทรมน ลิ่มบุตร" data-group-label="กลุ่มสาระฯ การงานอาชีพ" name="person_ids[]" value="1809900084706">
                                                                    <span class="member-name">นางภทรมน ลิ่มบุตร</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-7" data-member-name="นายนพพร  ถิ่นไทย" data-group-label="กลุ่มสาระฯ การงานอาชีพ" name="person_ids[]" value="1860100007288">
                                                                    <span class="member-name">นายนพพร ถิ่นไทย</span>
                                                                </label>
                                                            </li>
                                                        </ol>
                                                    </div>
                                                    <div class="item item-group is-collapsed">
                                                        <div class="group-header">
                                                            <label class="item-main">
                                                                <input type="checkbox" class="item-checkbox group-item-checkbox department-item-checkbox" data-group="department" data-group-key="department-2" data-group-label="กลุ่มสาระฯ คณิตศาสตร์" data-members="[{&quot;pID&quot;:&quot;1829900206275&quot;,&quot;name&quot;:&quot;นายภูมิวิชญ์ จีนนาพัฒ&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ คณิตศาสตร์&quot;},{&quot;pID&quot;:&quot;3810100580006&quot;,&quot;name&quot;:&quot;นางกนกวรรณ  ณ นคร&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ คณิตศาสตร์&quot;},{&quot;pID&quot;:&quot;3331001384867&quot;,&quot;name&quot;:&quot;นางประภาพร  อุดมผลชัยเจริญ&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ คณิตศาสตร์&quot;},{&quot;pID&quot;:&quot;1920600003469&quot;,&quot;name&quot;:&quot;นางผกาวรรณ  โชติวัฒนากร&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ คณิตศาสตร์&quot;},{&quot;pID&quot;:&quot;1839900175043&quot;,&quot;name&quot;:&quot;นางพรพิมล แซ่เจี่ย&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ คณิตศาสตร์&quot;},{&quot;pID&quot;:&quot;1829900096909&quot;,&quot;name&quot;:&quot;นางสาวพิมพ์ประภา  ผลากิจ&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ คณิตศาสตร์&quot;},{&quot;pID&quot;:&quot;1820500097624&quot;,&quot;name&quot;:&quot;นางสาวรัชนีกร ผอมจีน&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ คณิตศาสตร์&quot;},{&quot;pID&quot;:&quot;1909901558298&quot;,&quot;name&quot;:&quot;นางสาวอภิชญา จันทร์มา&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ คณิตศาสตร์&quot;},{&quot;pID&quot;:&quot;1800800204043&quot;,&quot;name&quot;:&quot;นางสาวอินทิรา บุญนิสสัย&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ คณิตศาสตร์&quot;},{&quot;pID&quot;:&quot;3820700019381&quot;,&quot;name&quot;:&quot;นางเสาวลีย์ จันทร์ทอง&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ คณิตศาสตร์&quot;},{&quot;pID&quot;:&quot;1809901028575&quot;,&quot;name&quot;:&quot;นายธนวัฒน์ ศิริพงศ์ประพันธ์&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ คณิตศาสตร์&quot;},{&quot;pID&quot;:&quot;3940400221191&quot;,&quot;name&quot;:&quot;นายพิพัฒน์ ไชยชนะ&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ คณิตศาสตร์&quot;},{&quot;pID&quot;:&quot;1930600099890&quot;,&quot;name&quot;:&quot;นางฝาติหม๊ะ ขนาดผล&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ คณิตศาสตร์&quot;},{&quot;pID&quot;:&quot;1829900119712&quot;,&quot;name&quot;:&quot;นางสาวธิดารัตน์ ทองกอบ&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ คณิตศาสตร์&quot;},{&quot;pID&quot;:&quot;1829900051727&quot;,&quot;name&quot;:&quot;นางสาวบงกชรัตน์  มาลี&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ คณิตศาสตร์&quot;},{&quot;pID&quot;:&quot;1800800331088&quot;,&quot;name&quot;:&quot;นายสราวุธ กุหลาบวรรณ&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ คณิตศาสตร์&quot;},{&quot;pID&quot;:&quot;1920100023843&quot;,&quot;name&quot;:&quot;นางสาวพรทิพย์ สมบัติบุญ&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ คณิตศาสตร์&quot;},{&quot;pID&quot;:&quot;1820500148121&quot;,&quot;name&quot;:&quot;นางสาวรัตนาพร พรประสิทธิ์&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ คณิตศาสตร์&quot;},{&quot;pID&quot;:&quot;3929900087867&quot;,&quot;name&quot;:&quot;นายอนุสรณ์ ชูทอง&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ คณิตศาสตร์&quot;},{&quot;pID&quot;:&quot;1820700059157&quot;,&quot;name&quot;:&quot;นางสาวนัฐลิณี ทอสงค์&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ คณิตศาสตร์&quot;}]" value="department-2">
                                                                <span class="item-title">กลุ่มสาระฯ คณิตศาสตร์</span>
                                                                <small class="item-subtext">สมาชิกทั้งหมด 20 คน</small>
                                                            </label>
                                                            <button type="button" class="group-toggle" aria-expanded="false" title="แสดง/ซ่อนรายชื่อสมาชิก">
                                                                <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                                                            </button>
                                                        </div>

                                                        <ol class="member-sublist">
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-2" data-member-name="นายภูมิวิชญ์ จีนนาพัฒ" data-group-label="กลุ่มสาระฯ คณิตศาสตร์" name="person_ids[]" value="1829900206275">
                                                                    <span class="member-name">นายภูมิวิชญ์ จีนนาพัฒ</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-2" data-member-name="นางกนกวรรณ  ณ นคร" data-group-label="กลุ่มสาระฯ คณิตศาสตร์" name="person_ids[]" value="3810100580006">
                                                                    <span class="member-name">นางกนกวรรณ ณ นคร</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-2" data-member-name="นางประภาพร  อุดมผลชัยเจริญ" data-group-label="กลุ่มสาระฯ คณิตศาสตร์" name="person_ids[]" value="3331001384867">
                                                                    <span class="member-name">นางประภาพร อุดมผลชัยเจริญ</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-2" data-member-name="นางผกาวรรณ  โชติวัฒนากร" data-group-label="กลุ่มสาระฯ คณิตศาสตร์" name="person_ids[]" value="1920600003469">
                                                                    <span class="member-name">นางผกาวรรณ โชติวัฒนากร</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-2" data-member-name="นางพรพิมล แซ่เจี่ย" data-group-label="กลุ่มสาระฯ คณิตศาสตร์" name="person_ids[]" value="1839900175043">
                                                                    <span class="member-name">นางพรพิมล แซ่เจี่ย</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-2" data-member-name="นางสาวพิมพ์ประภา  ผลากิจ" data-group-label="กลุ่มสาระฯ คณิตศาสตร์" name="person_ids[]" value="1829900096909">
                                                                    <span class="member-name">นางสาวพิมพ์ประภา ผลากิจ</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-2" data-member-name="นางสาวรัชนีกร ผอมจีน" data-group-label="กลุ่มสาระฯ คณิตศาสตร์" name="person_ids[]" value="1820500097624">
                                                                    <span class="member-name">นางสาวรัชนีกร ผอมจีน</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-2" data-member-name="นางสาวอภิชญา จันทร์มา" data-group-label="กลุ่มสาระฯ คณิตศาสตร์" name="person_ids[]" value="1909901558298">
                                                                    <span class="member-name">นางสาวอภิชญา จันทร์มา</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-2" data-member-name="นางสาวอินทิรา บุญนิสสัย" data-group-label="กลุ่มสาระฯ คณิตศาสตร์" name="person_ids[]" value="1800800204043">
                                                                    <span class="member-name">นางสาวอินทิรา บุญนิสสัย</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-2" data-member-name="นางเสาวลีย์ จันทร์ทอง" data-group-label="กลุ่มสาระฯ คณิตศาสตร์" name="person_ids[]" value="3820700019381">
                                                                    <span class="member-name">นางเสาวลีย์ จันทร์ทอง</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-2" data-member-name="นายธนวัฒน์ ศิริพงศ์ประพันธ์" data-group-label="กลุ่มสาระฯ คณิตศาสตร์" name="person_ids[]" value="1809901028575">
                                                                    <span class="member-name">นายธนวัฒน์ ศิริพงศ์ประพันธ์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-2" data-member-name="นายพิพัฒน์ ไชยชนะ" data-group-label="กลุ่มสาระฯ คณิตศาสตร์" name="person_ids[]" value="3940400221191">
                                                                    <span class="member-name">นายพิพัฒน์ ไชยชนะ</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-2" data-member-name="นางฝาติหม๊ะ ขนาดผล" data-group-label="กลุ่มสาระฯ คณิตศาสตร์" name="person_ids[]" value="1930600099890">
                                                                    <span class="member-name">นางฝาติหม๊ะ ขนาดผล</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-2" data-member-name="นางสาวธิดารัตน์ ทองกอบ" data-group-label="กลุ่มสาระฯ คณิตศาสตร์" name="person_ids[]" value="1829900119712">
                                                                    <span class="member-name">นางสาวธิดารัตน์ ทองกอบ</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-2" data-member-name="นางสาวบงกชรัตน์  มาลี" data-group-label="กลุ่มสาระฯ คณิตศาสตร์" name="person_ids[]" value="1829900051727">
                                                                    <span class="member-name">นางสาวบงกชรัตน์ มาลี</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-2" data-member-name="นายสราวุธ กุหลาบวรรณ" data-group-label="กลุ่มสาระฯ คณิตศาสตร์" name="person_ids[]" value="1800800331088">
                                                                    <span class="member-name">นายสราวุธ กุหลาบวรรณ</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-2" data-member-name="นางสาวพรทิพย์ สมบัติบุญ" data-group-label="กลุ่มสาระฯ คณิตศาสตร์" name="person_ids[]" value="1920100023843">
                                                                    <span class="member-name">นางสาวพรทิพย์ สมบัติบุญ</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-2" data-member-name="นางสาวรัตนาพร พรประสิทธิ์" data-group-label="กลุ่มสาระฯ คณิตศาสตร์" name="person_ids[]" value="1820500148121">
                                                                    <span class="member-name">นางสาวรัตนาพร พรประสิทธิ์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-2" data-member-name="นายอนุสรณ์ ชูทอง" data-group-label="กลุ่มสาระฯ คณิตศาสตร์" name="person_ids[]" value="3929900087867">
                                                                    <span class="member-name">นายอนุสรณ์ ชูทอง</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-2" data-member-name="นางสาวนัฐลิณี ทอสงค์" data-group-label="กลุ่มสาระฯ คณิตศาสตร์" name="person_ids[]" value="1820700059157">
                                                                    <span class="member-name">นางสาวนัฐลิณี ทอสงค์</span>
                                                                </label>
                                                            </li>
                                                        </ol>
                                                    </div>
                                                    <div class="item item-group is-collapsed">
                                                        <div class="group-header">
                                                            <label class="item-main">
                                                                <input type="checkbox" class="item-checkbox group-item-checkbox department-item-checkbox" data-group="department" data-group-key="department-8" data-group-label="กลุ่มสาระฯ ภาษาต่างประเทศ" data-members="[{&quot;pID&quot;:&quot;1820800093039&quot;,&quot;name&quot;:&quot;นางสาวปาริชาต เดชอาษา&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ ภาษาต่างประเทศ&quot;},{&quot;pID&quot;:&quot;1809900831358&quot;,&quot;name&quot;:&quot;นางสาวพลอยไพลิน เที่ยวแสวง&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ ภาษาต่างประเทศ&quot;},{&quot;pID&quot;:&quot;3820300027670&quot;,&quot;name&quot;:&quot;นางดาริน ทรายทอง&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ ภาษาต่างประเทศ&quot;},{&quot;pID&quot;:&quot;3940400027034&quot;,&quot;name&quot;:&quot;นางพนิดา ค้าของ&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ ภาษาต่างประเทศ&quot;},{&quot;pID&quot;:&quot;1829900054688&quot;,&quot;name&quot;:&quot;นางสาวกนกรัตน์ อุ้ยเฉ้ง&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ ภาษาต่างประเทศ&quot;},{&quot;pID&quot;:&quot;1829900059485&quot;,&quot;name&quot;:&quot;นางสาวจันทิพา ประทีป ณ ถลาง&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ ภาษาต่างประเทศ&quot;},{&quot;pID&quot;:&quot;1729900457121&quot;,&quot;name&quot;:&quot;นางสาวณัฐวรรณ  ทรัพย์เฉลิม&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ ภาษาต่างประเทศ&quot;},{&quot;pID&quot;:&quot;1829900202598&quot;,&quot;name&quot;:&quot;นางสาวธนวรรณ สมัครการ&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ ภาษาต่างประเทศ&quot;},{&quot;pID&quot;:&quot;1829900065485&quot;,&quot;name&quot;:&quot;นางสาวองค์ปรางค์ แสงสุรินทร์&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ ภาษาต่างประเทศ&quot;},{&quot;pID&quot;:&quot;1930500116202&quot;,&quot;name&quot;:&quot;นางสุรางค์รัศมิ์ ย้อยพระจันทร์&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ ภาษาต่างประเทศ&quot;},{&quot;pID&quot;:&quot;1810500062871&quot;,&quot;name&quot;:&quot;นางสาวกานต์พิชชา ปากลาว&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ ภาษาต่างประเทศ&quot;},{&quot;pID&quot;:&quot;1910300050321&quot;,&quot;name&quot;:&quot;นางสาวนิลญา หมานมิตร&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ ภาษาต่างประเทศ&quot;},{&quot;pID&quot;:&quot;1829900090897&quot;,&quot;name&quot;:&quot;นางสาวปริษา  แก้วเขียว&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ ภาษาต่างประเทศ&quot;},{&quot;pID&quot;:&quot;1940100013597&quot;,&quot;name&quot;:&quot;นางสาววรินญา โรจธนะวรรธน์&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ ภาษาต่างประเทศ&quot;},{&quot;pID&quot;:&quot;1829900162341&quot;,&quot;name&quot;:&quot;นายอิสรพงศ์ สัตปานนท์&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ ภาษาต่างประเทศ&quot;},{&quot;pID&quot;:&quot;3950300068146&quot;,&quot;name&quot;:&quot;นางพวงทิพย์ ทวีรส&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ ภาษาต่างประเทศ&quot;},{&quot;pID&quot;:&quot;3820400309367&quot;,&quot;name&quot;:&quot;นางเขมษิญากรณ์ อุดมคุณ&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ ภาษาต่างประเทศ&quot;},{&quot;pID&quot;:&quot;3930300329632&quot;,&quot;name&quot;:&quot;นางเพ็ญแข หวานสนิท&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ ภาษาต่างประเทศ&quot;},{&quot;pID&quot;:&quot;3820700017680&quot;,&quot;name&quot;:&quot;นายณณัฐพล  บุญสุรัชต์สิรี&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ ภาษาต่างประเทศ&quot;},{&quot;pID&quot;:&quot;1841500136302&quot;,&quot;name&quot;:&quot;นายธีระวัฒน์ เพชรขุ้ม&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ ภาษาต่างประเทศ&quot;}]" value="department-8">
                                                                <span class="item-title">กลุ่มสาระฯ ภาษาต่างประเทศ</span>
                                                                <small class="item-subtext">สมาชิกทั้งหมด 20 คน</small>
                                                            </label>
                                                            <button type="button" class="group-toggle" aria-expanded="false" title="แสดง/ซ่อนรายชื่อสมาชิก">
                                                                <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                                                            </button>
                                                        </div>

                                                        <ol class="member-sublist">
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-8" data-member-name="นางสาวปาริชาต เดชอาษา" data-group-label="กลุ่มสาระฯ ภาษาต่างประเทศ" name="person_ids[]" value="1820800093039">
                                                                    <span class="member-name">นางสาวปาริชาต เดชอาษา</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-8" data-member-name="นางสาวพลอยไพลิน เที่ยวแสวง" data-group-label="กลุ่มสาระฯ ภาษาต่างประเทศ" name="person_ids[]" value="1809900831358">
                                                                    <span class="member-name">นางสาวพลอยไพลิน เที่ยวแสวง</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-8" data-member-name="นางดาริน ทรายทอง" data-group-label="กลุ่มสาระฯ ภาษาต่างประเทศ" name="person_ids[]" value="3820300027670">
                                                                    <span class="member-name">นางดาริน ทรายทอง</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-8" data-member-name="นางพนิดา ค้าของ" data-group-label="กลุ่มสาระฯ ภาษาต่างประเทศ" name="person_ids[]" value="3940400027034">
                                                                    <span class="member-name">นางพนิดา ค้าของ</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-8" data-member-name="นางสาวกนกรัตน์ อุ้ยเฉ้ง" data-group-label="กลุ่มสาระฯ ภาษาต่างประเทศ" name="person_ids[]" value="1829900054688">
                                                                    <span class="member-name">นางสาวกนกรัตน์ อุ้ยเฉ้ง</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-8" data-member-name="นางสาวจันทิพา ประทีป ณ ถลาง" data-group-label="กลุ่มสาระฯ ภาษาต่างประเทศ" name="person_ids[]" value="1829900059485">
                                                                    <span class="member-name">นางสาวจันทิพา ประทีป ณ ถลาง</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-8" data-member-name="นางสาวณัฐวรรณ  ทรัพย์เฉลิม" data-group-label="กลุ่มสาระฯ ภาษาต่างประเทศ" name="person_ids[]" value="1729900457121">
                                                                    <span class="member-name">นางสาวณัฐวรรณ ทรัพย์เฉลิม</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-8" data-member-name="นางสาวธนวรรณ สมัครการ" data-group-label="กลุ่มสาระฯ ภาษาต่างประเทศ" name="person_ids[]" value="1829900202598">
                                                                    <span class="member-name">นางสาวธนวรรณ สมัครการ</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-8" data-member-name="นางสาวองค์ปรางค์ แสงสุรินทร์" data-group-label="กลุ่มสาระฯ ภาษาต่างประเทศ" name="person_ids[]" value="1829900065485">
                                                                    <span class="member-name">นางสาวองค์ปรางค์ แสงสุรินทร์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-8" data-member-name="นางสุรางค์รัศมิ์ ย้อยพระจันทร์" data-group-label="กลุ่มสาระฯ ภาษาต่างประเทศ" name="person_ids[]" value="1930500116202">
                                                                    <span class="member-name">นางสุรางค์รัศมิ์ ย้อยพระจันทร์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-8" data-member-name="นางสาวกานต์พิชชา ปากลาว" data-group-label="กลุ่มสาระฯ ภาษาต่างประเทศ" name="person_ids[]" value="1810500062871">
                                                                    <span class="member-name">นางสาวกานต์พิชชา ปากลาว</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-8" data-member-name="นางสาวนิลญา หมานมิตร" data-group-label="กลุ่มสาระฯ ภาษาต่างประเทศ" name="person_ids[]" value="1910300050321">
                                                                    <span class="member-name">นางสาวนิลญา หมานมิตร</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-8" data-member-name="นางสาวปริษา  แก้วเขียว" data-group-label="กลุ่มสาระฯ ภาษาต่างประเทศ" name="person_ids[]" value="1829900090897">
                                                                    <span class="member-name">นางสาวปริษา แก้วเขียว</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-8" data-member-name="นางสาววรินญา โรจธนะวรรธน์" data-group-label="กลุ่มสาระฯ ภาษาต่างประเทศ" name="person_ids[]" value="1940100013597">
                                                                    <span class="member-name">นางสาววรินญา โรจธนะวรรธน์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-8" data-member-name="นายอิสรพงศ์ สัตปานนท์" data-group-label="กลุ่มสาระฯ ภาษาต่างประเทศ" name="person_ids[]" value="1829900162341">
                                                                    <span class="member-name">นายอิสรพงศ์ สัตปานนท์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-8" data-member-name="นางพวงทิพย์ ทวีรส" data-group-label="กลุ่มสาระฯ ภาษาต่างประเทศ" name="person_ids[]" value="3950300068146">
                                                                    <span class="member-name">นางพวงทิพย์ ทวีรส</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-8" data-member-name="นางเขมษิญากรณ์ อุดมคุณ" data-group-label="กลุ่มสาระฯ ภาษาต่างประเทศ" name="person_ids[]" value="3820400309367">
                                                                    <span class="member-name">นางเขมษิญากรณ์ อุดมคุณ</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-8" data-member-name="นางเพ็ญแข หวานสนิท" data-group-label="กลุ่มสาระฯ ภาษาต่างประเทศ" name="person_ids[]" value="3930300329632">
                                                                    <span class="member-name">นางเพ็ญแข หวานสนิท</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-8" data-member-name="นายณณัฐพล  บุญสุรัชต์สิรี" data-group-label="กลุ่มสาระฯ ภาษาต่างประเทศ" name="person_ids[]" value="3820700017680">
                                                                    <span class="member-name">นายณณัฐพล บุญสุรัชต์สิรี</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-8" data-member-name="นายธีระวัฒน์ เพชรขุ้ม" data-group-label="กลุ่มสาระฯ ภาษาต่างประเทศ" name="person_ids[]" value="1841500136302">
                                                                    <span class="member-name">นายธีระวัฒน์ เพชรขุ้ม</span>
                                                                </label>
                                                            </li>
                                                        </ol>
                                                    </div>
                                                    <div class="item item-group is-collapsed">
                                                        <div class="group-header">
                                                            <label class="item-main">
                                                                <input type="checkbox" class="item-checkbox group-item-checkbox department-item-checkbox" data-group="department" data-group-key="department-1" data-group-label="กลุ่มสาระฯ ภาษาไทย" data-members="[{&quot;pID&quot;:&quot;1829900103735&quot;,&quot;name&quot;:&quot;นางสาวจันทนี บุญนำ&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ ภาษาไทย&quot;},{&quot;pID&quot;:&quot;1829900141980&quot;,&quot;name&quot;:&quot;นางสาวสุกานดา ปานมั่งคั่ง&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ ภาษาไทย&quot;},{&quot;pID&quot;:&quot;3840100521778&quot;,&quot;name&quot;:&quot;นางดวงกมล  เพ็ชรพรหม&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ ภาษาไทย&quot;},{&quot;pID&quot;:&quot;3840200430855&quot;,&quot;name&quot;:&quot;นางสาวณพสร สามสุวรรณ&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ ภาษาไทย&quot;},{&quot;pID&quot;:&quot;3820400028481&quot;,&quot;name&quot;:&quot;นางสาวยศยา ศักดิ์ศิลปศาสตร์&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ ภาษาไทย&quot;},{&quot;pID&quot;:&quot;3820700136859&quot;,&quot;name&quot;:&quot;นางสาวลภัสนันท์ บำรุงวงศ์&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ ภาษาไทย&quot;},{&quot;pID&quot;:&quot;1829900118058&quot;,&quot;name&quot;:&quot;นางสาวนัยน์เนตร ทองวล&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ ภาษาไทย&quot;},{&quot;pID&quot;:&quot;1840100431373&quot;,&quot;name&quot;:&quot;นางสาวบุษรา  เมืองชู&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ ภาษาไทย&quot;},{&quot;pID&quot;:&quot;1820500007021&quot;,&quot;name&quot;:&quot;นางจิตติพร เกตุรักษ์&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ ภาษาไทย&quot;},{&quot;pID&quot;:&quot;1101401730717&quot;,&quot;name&quot;:&quot;นางสาวพรรณพนัช  คงผอม&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ ภาษาไทย&quot;},{&quot;pID&quot;:&quot;3820500121271&quot;,&quot;name&quot;:&quot;นางสาวราศรี  อนันตมงคลกุล&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ ภาษาไทย&quot;},{&quot;pID&quot;:&quot;1860700158147&quot;,&quot;name&quot;:&quot;นายนพดล วงศ์สุวัฒน์&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ ภาษาไทย&quot;},{&quot;pID&quot;:&quot;1102003266698&quot;,&quot;name&quot;:&quot;นายนรินทร์เพชร นิลเวช&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ ภาษาไทย&quot;},{&quot;pID&quot;:&quot;1829900109890&quot;,&quot;name&quot;:&quot;นายพจนันท์  พรหมสงค์&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ ภาษาไทย&quot;}]" value="department-1">
                                                                <span class="item-title">กลุ่มสาระฯ ภาษาไทย</span>
                                                                <small class="item-subtext">สมาชิกทั้งหมด 14 คน</small>
                                                            </label>
                                                            <button type="button" class="group-toggle" aria-expanded="false" title="แสดง/ซ่อนรายชื่อสมาชิก">
                                                                <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                                                            </button>
                                                        </div>

                                                        <ol class="member-sublist">
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-1" data-member-name="นางสาวจันทนี บุญนำ" data-group-label="กลุ่มสาระฯ ภาษาไทย" name="person_ids[]" value="1829900103735">
                                                                    <span class="member-name">นางสาวจันทนี บุญนำ</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-1" data-member-name="นางสาวสุกานดา ปานมั่งคั่ง" data-group-label="กลุ่มสาระฯ ภาษาไทย" name="person_ids[]" value="1829900141980">
                                                                    <span class="member-name">นางสาวสุกานดา ปานมั่งคั่ง</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-1" data-member-name="นางดวงกมล  เพ็ชรพรหม" data-group-label="กลุ่มสาระฯ ภาษาไทย" name="person_ids[]" value="3840100521778">
                                                                    <span class="member-name">นางดวงกมล เพ็ชรพรหม</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-1" data-member-name="นางสาวณพสร สามสุวรรณ" data-group-label="กลุ่มสาระฯ ภาษาไทย" name="person_ids[]" value="3840200430855">
                                                                    <span class="member-name">นางสาวณพสร สามสุวรรณ</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-1" data-member-name="นางสาวยศยา ศักดิ์ศิลปศาสตร์" data-group-label="กลุ่มสาระฯ ภาษาไทย" name="person_ids[]" value="3820400028481">
                                                                    <span class="member-name">นางสาวยศยา ศักดิ์ศิลปศาสตร์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-1" data-member-name="นางสาวลภัสนันท์ บำรุงวงศ์" data-group-label="กลุ่มสาระฯ ภาษาไทย" name="person_ids[]" value="3820700136859">
                                                                    <span class="member-name">นางสาวลภัสนันท์ บำรุงวงศ์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-1" data-member-name="นางสาวนัยน์เนตร ทองวล" data-group-label="กลุ่มสาระฯ ภาษาไทย" name="person_ids[]" value="1829900118058">
                                                                    <span class="member-name">นางสาวนัยน์เนตร ทองวล</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-1" data-member-name="นางสาวบุษรา  เมืองชู" data-group-label="กลุ่มสาระฯ ภาษาไทย" name="person_ids[]" value="1840100431373">
                                                                    <span class="member-name">นางสาวบุษรา เมืองชู</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-1" data-member-name="นางจิตติพร เกตุรักษ์" data-group-label="กลุ่มสาระฯ ภาษาไทย" name="person_ids[]" value="1820500007021">
                                                                    <span class="member-name">นางจิตติพร เกตุรักษ์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-1" data-member-name="นางสาวพรรณพนัช  คงผอม" data-group-label="กลุ่มสาระฯ ภาษาไทย" name="person_ids[]" value="1101401730717">
                                                                    <span class="member-name">นางสาวพรรณพนัช คงผอม</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-1" data-member-name="นางสาวราศรี  อนันตมงคลกุล" data-group-label="กลุ่มสาระฯ ภาษาไทย" name="person_ids[]" value="3820500121271">
                                                                    <span class="member-name">นางสาวราศรี อนันตมงคลกุล</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-1" data-member-name="นายนพดล วงศ์สุวัฒน์" data-group-label="กลุ่มสาระฯ ภาษาไทย" name="person_ids[]" value="1860700158147">
                                                                    <span class="member-name">นายนพดล วงศ์สุวัฒน์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-1" data-member-name="นายนรินทร์เพชร นิลเวช" data-group-label="กลุ่มสาระฯ ภาษาไทย" name="person_ids[]" value="1102003266698">
                                                                    <span class="member-name">นายนรินทร์เพชร นิลเวช</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-1" data-member-name="นายพจนันท์  พรหมสงค์" data-group-label="กลุ่มสาระฯ ภาษาไทย" name="person_ids[]" value="1829900109890">
                                                                    <span class="member-name">นายพจนันท์ พรหมสงค์</span>
                                                                </label>
                                                            </li>
                                                        </ol>
                                                    </div>
                                                    <div class="item item-group is-collapsed">
                                                        <div class="group-header">
                                                            <label class="item-main">
                                                                <input type="checkbox" class="item-checkbox group-item-checkbox department-item-checkbox" data-group="department" data-group-key="department-3" data-group-label="กลุ่มสาระฯ วิทยาศาสตร์" data-members="[{&quot;pID&quot;:&quot;1819300006267&quot;,&quot;name&quot;:&quot;นายคุณากร ประดับศิลป์&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ วิทยาศาสตร์&quot;},{&quot;pID&quot;:&quot;3820400295111&quot;,&quot;name&quot;:&quot;นายนิมิตร สุสิมานนท์&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ วิทยาศาสตร์&quot;},{&quot;pID&quot;:&quot;3930300511171&quot;,&quot;name&quot;:&quot;นางณิภาภรณ์  ไชยชนะ&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ วิทยาศาสตร์&quot;},{&quot;pID&quot;:&quot;1829900063989&quot;,&quot;name&quot;:&quot;นางธนิษฐา  ยงยุทธ์&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ วิทยาศาสตร์&quot;},{&quot;pID&quot;:&quot;1829900012535&quot;,&quot;name&quot;:&quot;นางสาวรัชฎาพร สุวรรณสาม&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ วิทยาศาสตร์&quot;},{&quot;pID&quot;:&quot;1829900099401&quot;,&quot;name&quot;:&quot;นางสาวศุลีพร ขันภักดี&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ วิทยาศาสตร์&quot;},{&quot;pID&quot;:&quot;1800100218262&quot;,&quot;name&quot;:&quot;นางสาวอาตีนา  พัชนี&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ วิทยาศาสตร์&quot;},{&quot;pID&quot;:&quot;1839900094990&quot;,&quot;name&quot;:&quot;นายนพรัตน์ ย้อยพระจันทร์&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ วิทยาศาสตร์&quot;},{&quot;pID&quot;:&quot;3920100747937&quot;,&quot;name&quot;:&quot;นางจุไรรัตน์ สวัสดิ์วงศ์&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ วิทยาศาสตร์&quot;},{&quot;pID&quot;:&quot;1829900179103&quot;,&quot;name&quot;:&quot;นางสาวกนกลักษณ์ พันธ์สวัสดิ์&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ วิทยาศาสตร์&quot;},{&quot;pID&quot;:&quot;1920600250041&quot;,&quot;name&quot;:&quot;นางสาวนฤมล บุญถาวร&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ วิทยาศาสตร์&quot;},{&quot;pID&quot;:&quot;1820400055491&quot;,&quot;name&quot;:&quot;นางสาวปาณิสรา  มัจฉาเวช&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ วิทยาศาสตร์&quot;},{&quot;pID&quot;:&quot;3820100171700&quot;,&quot;name&quot;:&quot;นางสาวพิมพ์จันทร์  สุวรรณดี&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ วิทยาศาสตร์&quot;},{&quot;pID&quot;:&quot;1840100326120&quot;,&quot;name&quot;:&quot;นางสาวศรัณย์รัชต์ สุขผ่องใส&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ วิทยาศาสตร์&quot;},{&quot;pID&quot;:&quot;3820700050342&quot;,&quot;name&quot;:&quot;นางสุมณฑา  เกิดทรัพย์&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ วิทยาศาสตร์&quot;},{&quot;pID&quot;:&quot;1820700004867&quot;,&quot;name&quot;:&quot;นางอรชา ชูเชื้อ&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ วิทยาศาสตร์&quot;},{&quot;pID&quot;:&quot;3820400215231&quot;,&quot;name&quot;:&quot;นางชมทิศา ขันภักดี&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ วิทยาศาสตร์&quot;},{&quot;pID&quot;:&quot;1829900172052&quot;,&quot;name&quot;:&quot;นางสาวชาลิสา จิตต์พันธ์&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ วิทยาศาสตร์&quot;},{&quot;pID&quot;:&quot;1829900170670&quot;,&quot;name&quot;:&quot;นางสาวอรบุษย์ หนักแน่น&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ วิทยาศาสตร์&quot;},{&quot;pID&quot;:&quot;3601000301019&quot;,&quot;name&quot;:&quot;นางสุนิษา  จินดาพล&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ วิทยาศาสตร์&quot;},{&quot;pID&quot;:&quot;1319800069611&quot;,&quot;name&quot;:&quot;นายณัฐพงษ์ สัจจารักษ์&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ วิทยาศาสตร์&quot;},{&quot;pID&quot;:&quot;1839900193629&quot;,&quot;name&quot;:&quot;นายธีรภัส  สฤษดิสุข&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ วิทยาศาสตร์&quot;},{&quot;pID&quot;:&quot;1829900012446&quot;,&quot;name&quot;:&quot;นายรชต  ปานบุญ&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ วิทยาศาสตร์&quot;},{&quot;pID&quot;:&quot;1829900149409&quot;,&quot;name&quot;:&quot;นางสาวอุบลวรรณ คงสม&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ วิทยาศาสตร์&quot;}]" value="department-3">
                                                                <span class="item-title">กลุ่มสาระฯ วิทยาศาสตร์</span>
                                                                <small class="item-subtext">สมาชิกทั้งหมด 24 คน</small>
                                                            </label>
                                                            <button type="button" class="group-toggle" aria-expanded="false" title="แสดง/ซ่อนรายชื่อสมาชิก">
                                                                <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                                                            </button>
                                                        </div>

                                                        <ol class="member-sublist">
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-3" data-member-name="นายคุณากร ประดับศิลป์" data-group-label="กลุ่มสาระฯ วิทยาศาสตร์" name="person_ids[]" value="1819300006267">
                                                                    <span class="member-name">นายคุณากร ประดับศิลป์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-3" data-member-name="นายนิมิตร สุสิมานนท์" data-group-label="กลุ่มสาระฯ วิทยาศาสตร์" name="person_ids[]" value="3820400295111">
                                                                    <span class="member-name">นายนิมิตร สุสิมานนท์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-3" data-member-name="นางณิภาภรณ์  ไชยชนะ" data-group-label="กลุ่มสาระฯ วิทยาศาสตร์" name="person_ids[]" value="3930300511171">
                                                                    <span class="member-name">นางณิภาภรณ์ ไชยชนะ</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-3" data-member-name="นางธนิษฐา  ยงยุทธ์" data-group-label="กลุ่มสาระฯ วิทยาศาสตร์" name="person_ids[]" value="1829900063989">
                                                                    <span class="member-name">นางธนิษฐา ยงยุทธ์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-3" data-member-name="นางสาวรัชฎาพร สุวรรณสาม" data-group-label="กลุ่มสาระฯ วิทยาศาสตร์" name="person_ids[]" value="1829900012535">
                                                                    <span class="member-name">นางสาวรัชฎาพร สุวรรณสาม</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-3" data-member-name="นางสาวศุลีพร ขันภักดี" data-group-label="กลุ่มสาระฯ วิทยาศาสตร์" name="person_ids[]" value="1829900099401">
                                                                    <span class="member-name">นางสาวศุลีพร ขันภักดี</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-3" data-member-name="นางสาวอาตีนา  พัชนี" data-group-label="กลุ่มสาระฯ วิทยาศาสตร์" name="person_ids[]" value="1800100218262">
                                                                    <span class="member-name">นางสาวอาตีนา พัชนี</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-3" data-member-name="นายนพรัตน์ ย้อยพระจันทร์" data-group-label="กลุ่มสาระฯ วิทยาศาสตร์" name="person_ids[]" value="1839900094990">
                                                                    <span class="member-name">นายนพรัตน์ ย้อยพระจันทร์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-3" data-member-name="นางจุไรรัตน์ สวัสดิ์วงศ์" data-group-label="กลุ่มสาระฯ วิทยาศาสตร์" name="person_ids[]" value="3920100747937">
                                                                    <span class="member-name">นางจุไรรัตน์ สวัสดิ์วงศ์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-3" data-member-name="นางสาวกนกลักษณ์ พันธ์สวัสดิ์" data-group-label="กลุ่มสาระฯ วิทยาศาสตร์" name="person_ids[]" value="1829900179103">
                                                                    <span class="member-name">นางสาวกนกลักษณ์ พันธ์สวัสดิ์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-3" data-member-name="นางสาวนฤมล บุญถาวร" data-group-label="กลุ่มสาระฯ วิทยาศาสตร์" name="person_ids[]" value="1920600250041">
                                                                    <span class="member-name">นางสาวนฤมล บุญถาวร</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-3" data-member-name="นางสาวปาณิสรา  มัจฉาเวช" data-group-label="กลุ่มสาระฯ วิทยาศาสตร์" name="person_ids[]" value="1820400055491">
                                                                    <span class="member-name">นางสาวปาณิสรา มัจฉาเวช</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-3" data-member-name="นางสาวพิมพ์จันทร์  สุวรรณดี" data-group-label="กลุ่มสาระฯ วิทยาศาสตร์" name="person_ids[]" value="3820100171700">
                                                                    <span class="member-name">นางสาวพิมพ์จันทร์ สุวรรณดี</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-3" data-member-name="นางสาวศรัณย์รัชต์ สุขผ่องใส" data-group-label="กลุ่มสาระฯ วิทยาศาสตร์" name="person_ids[]" value="1840100326120">
                                                                    <span class="member-name">นางสาวศรัณย์รัชต์ สุขผ่องใส</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-3" data-member-name="นางสุมณฑา  เกิดทรัพย์" data-group-label="กลุ่มสาระฯ วิทยาศาสตร์" name="person_ids[]" value="3820700050342">
                                                                    <span class="member-name">นางสุมณฑา เกิดทรัพย์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-3" data-member-name="นางอรชา ชูเชื้อ" data-group-label="กลุ่มสาระฯ วิทยาศาสตร์" name="person_ids[]" value="1820700004867">
                                                                    <span class="member-name">นางอรชา ชูเชื้อ</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-3" data-member-name="นางชมทิศา ขันภักดี" data-group-label="กลุ่มสาระฯ วิทยาศาสตร์" name="person_ids[]" value="3820400215231">
                                                                    <span class="member-name">นางชมทิศา ขันภักดี</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-3" data-member-name="นางสาวชาลิสา จิตต์พันธ์" data-group-label="กลุ่มสาระฯ วิทยาศาสตร์" name="person_ids[]" value="1829900172052">
                                                                    <span class="member-name">นางสาวชาลิสา จิตต์พันธ์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-3" data-member-name="นางสาวอรบุษย์ หนักแน่น" data-group-label="กลุ่มสาระฯ วิทยาศาสตร์" name="person_ids[]" value="1829900170670">
                                                                    <span class="member-name">นางสาวอรบุษย์ หนักแน่น</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-3" data-member-name="นางสุนิษา  จินดาพล" data-group-label="กลุ่มสาระฯ วิทยาศาสตร์" name="person_ids[]" value="3601000301019">
                                                                    <span class="member-name">นางสุนิษา จินดาพล</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-3" data-member-name="นายณัฐพงษ์ สัจจารักษ์" data-group-label="กลุ่มสาระฯ วิทยาศาสตร์" name="person_ids[]" value="1319800069611">
                                                                    <span class="member-name">นายณัฐพงษ์ สัจจารักษ์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-3" data-member-name="นายธีรภัส  สฤษดิสุข" data-group-label="กลุ่มสาระฯ วิทยาศาสตร์" name="person_ids[]" value="1839900193629">
                                                                    <span class="member-name">นายธีรภัส สฤษดิสุข</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-3" data-member-name="นายรชต  ปานบุญ" data-group-label="กลุ่มสาระฯ วิทยาศาสตร์" name="person_ids[]" value="1829900012446">
                                                                    <span class="member-name">นายรชต ปานบุญ</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-3" data-member-name="นางสาวอุบลวรรณ คงสม" data-group-label="กลุ่มสาระฯ วิทยาศาสตร์" name="person_ids[]" value="1829900149409">
                                                                    <span class="member-name">นางสาวอุบลวรรณ คงสม</span>
                                                                </label>
                                                            </li>
                                                        </ol>
                                                    </div>
                                                    <div class="item item-group is-collapsed">
                                                        <div class="group-header">
                                                            <label class="item-main">
                                                                <input type="checkbox" class="item-checkbox group-item-checkbox department-item-checkbox" data-group="department" data-group-key="department-6" data-group-label="กลุ่มสาระฯ ศิลปะ" data-members="[{&quot;pID&quot;:&quot;3840700282162&quot;,&quot;name&quot;:&quot;นางสาวประภัสสร  โอจันทร์&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ ศิลปะ&quot;},{&quot;pID&quot;:&quot;1829900056460&quot;,&quot;name&quot;:&quot;นายศุภวุฒิ &nbsp;อินทร์แก้ว&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ ศิลปะ&quot;},{&quot;pID&quot;:&quot;3820400194578&quot;,&quot;name&quot;:&quot;นายสุพัฒน์  เจริญฤทธิ์&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ ศิลปะ&quot;},{&quot;pID&quot;:&quot;3850100320012&quot;,&quot;name&quot;:&quot;นางสาวธารทิพย์ ภาระพฤติ&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ ศิลปะ&quot;},{&quot;pID&quot;:&quot;3829900019706&quot;,&quot;name&quot;:&quot;นางสาวปาณิสรา  มงคลบุตร&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ ศิลปะ&quot;},{&quot;pID&quot;:&quot;1160100618291&quot;,&quot;name&quot;:&quot;นายวิศรุต ชามทอง&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ ศิลปะ&quot;},{&quot;pID&quot;:&quot;1901100006087&quot;,&quot;name&quot;:&quot;นายเจนสกนธ์  ศรัณย์บัณฑิต&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ ศิลปะ&quot;}]" value="department-6">
                                                                <span class="item-title">กลุ่มสาระฯ ศิลปะ</span>
                                                                <small class="item-subtext">สมาชิกทั้งหมด 7 คน</small>
                                                            </label>
                                                            <button type="button" class="group-toggle" aria-expanded="false" title="แสดง/ซ่อนรายชื่อสมาชิก">
                                                                <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                                                            </button>
                                                        </div>

                                                        <ol class="member-sublist">
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-6" data-member-name="นางสาวประภัสสร  โอจันทร์" data-group-label="กลุ่มสาระฯ ศิลปะ" name="person_ids[]" value="3840700282162">
                                                                    <span class="member-name">นางสาวประภัสสร โอจันทร์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-6" data-member-name="นายศุภวุฒิ &nbsp;อินทร์แก้ว" data-group-label="กลุ่มสาระฯ ศิลปะ" name="person_ids[]" value="1829900056460">
                                                                    <span class="member-name">นายศุภวุฒิ &nbsp;อินทร์แก้ว</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-6" data-member-name="นายสุพัฒน์  เจริญฤทธิ์" data-group-label="กลุ่มสาระฯ ศิลปะ" name="person_ids[]" value="3820400194578">
                                                                    <span class="member-name">นายสุพัฒน์ เจริญฤทธิ์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-6" data-member-name="นางสาวธารทิพย์ ภาระพฤติ" data-group-label="กลุ่มสาระฯ ศิลปะ" name="person_ids[]" value="3850100320012">
                                                                    <span class="member-name">นางสาวธารทิพย์ ภาระพฤติ</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-6" data-member-name="นางสาวปาณิสรา  มงคลบุตร" data-group-label="กลุ่มสาระฯ ศิลปะ" name="person_ids[]" value="3829900019706">
                                                                    <span class="member-name">นางสาวปาณิสรา มงคลบุตร</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-6" data-member-name="นายวิศรุต ชามทอง" data-group-label="กลุ่มสาระฯ ศิลปะ" name="person_ids[]" value="1160100618291">
                                                                    <span class="member-name">นายวิศรุต ชามทอง</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-6" data-member-name="นายเจนสกนธ์  ศรัณย์บัณฑิต" data-group-label="กลุ่มสาระฯ ศิลปะ" name="person_ids[]" value="1901100006087">
                                                                    <span class="member-name">นายเจนสกนธ์ ศรัณย์บัณฑิต</span>
                                                                </label>
                                                            </li>
                                                        </ol>
                                                    </div>
                                                    <div class="item item-group is-collapsed">
                                                        <div class="group-header">
                                                            <label class="item-main">
                                                                <input type="checkbox" class="item-checkbox group-item-checkbox department-item-checkbox" data-group="department" data-group-key="department-4" data-group-label="กลุ่มสาระฯ สังคมศึกษา ศาสนาและวัฒนธรรม" data-members="[{&quot;pID&quot;:&quot;1830101156953&quot;,&quot;name&quot;:&quot;นางสาวนัสรีน สุวิสัน&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ สังคมศึกษา ศาสนาและวัฒนธรรม&quot;},{&quot;pID&quot;:&quot;1810300103434&quot;,&quot;name&quot;:&quot;นางสาวปณิดา คลองรั้ว&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ สังคมศึกษา ศาสนาและวัฒนธรรม&quot;},{&quot;pID&quot;:&quot;1820501214179&quot;,&quot;name&quot;:&quot;นายมงคล ตันเจริญรัตน์&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ สังคมศึกษา ศาสนาและวัฒนธรรม&quot;},{&quot;pID&quot;:&quot;3820100025592&quot;,&quot;name&quot;:&quot;นางจารุวรรณ ส่องศิริ&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ สังคมศึกษา ศาสนาและวัฒนธรรม&quot;},{&quot;pID&quot;:&quot;3829900033725&quot;,&quot;name&quot;:&quot;นางสาวชนิกานต์  สวัสดิวงค์&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ สังคมศึกษา ศาสนาและวัฒนธรรม&quot;},{&quot;pID&quot;:&quot;1410100117524&quot;,&quot;name&quot;:&quot;นางสาวประภาพรรณ กุลแก้ว&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ สังคมศึกษา ศาสนาและวัฒนธรรม&quot;},{&quot;pID&quot;:&quot;1829900007736&quot;,&quot;name&quot;:&quot;นางปวีณา  บำรุงภักดิ์&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ สังคมศึกษา ศาสนาและวัฒนธรรม&quot;},{&quot;pID&quot;:&quot;1820500147966&quot;,&quot;name&quot;:&quot;นางสาวธนวรรณ พิทักษ์คง&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ สังคมศึกษา ศาสนาและวัฒนธรรม&quot;},{&quot;pID&quot;:&quot;1820600006469&quot;,&quot;name&quot;:&quot;นางสาวปิยธิดา นิยมเดชา&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ สังคมศึกษา ศาสนาและวัฒนธรรม&quot;},{&quot;pID&quot;:&quot;1820500130320&quot;,&quot;name&quot;:&quot;นางสาวลภัสภาส์ หนูคง&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ สังคมศึกษา ศาสนาและวัฒนธรรม&quot;},{&quot;pID&quot;:&quot;3820100025495&quot;,&quot;name&quot;:&quot;นางวาสนา  สุทธจิตร์&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ สังคมศึกษา ศาสนาและวัฒนธรรม&quot;},{&quot;pID&quot;:&quot;1829900174284&quot;,&quot;name&quot;:&quot;นางสาวนิรชา ธรรมัสโร&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ สังคมศึกษา ศาสนาและวัฒนธรรม&quot;},{&quot;pID&quot;:&quot;1809901015490&quot;,&quot;name&quot;:&quot;นางสาวสรัลรัตน์ จันทับ&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ สังคมศึกษา ศาสนาและวัฒนธรรม&quot;},{&quot;pID&quot;:&quot;1820800031408&quot;,&quot;name&quot;:&quot;นางสาวสุดาทิพย์ ยกย่อง&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ สังคมศึกษา ศาสนาและวัฒนธรรม&quot;},{&quot;pID&quot;:&quot;3180600191510&quot;,&quot;name&quot;:&quot;นายเพลิน โอรักษ์&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ สังคมศึกษา ศาสนาและวัฒนธรรม&quot;},{&quot;pID&quot;:&quot;1809900094507&quot;,&quot;name&quot;:&quot;ว่าที่ร้อยตรีเรืองเดชย์  ผสารพจน์&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ สังคมศึกษา ศาสนาและวัฒนธรรม&quot;},{&quot;pID&quot;:&quot;3820100028745&quot;,&quot;name&quot;:&quot;นายวรานนท์ ภาระพฤติ&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ สังคมศึกษา ศาสนาและวัฒนธรรม&quot;},{&quot;pID&quot;:&quot;1829900093446&quot;,&quot;name&quot;:&quot;นายศุภสวัสดิ์ กาญวิจิต&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ สังคมศึกษา ศาสนาและวัฒนธรรม&quot;}]" value="department-4">
                                                                <span class="item-title">กลุ่มสาระฯ สังคมศึกษา ศาสนาและวัฒนธรรม</span>
                                                                <small class="item-subtext">สมาชิกทั้งหมด 18 คน</small>
                                                            </label>
                                                            <button type="button" class="group-toggle" aria-expanded="false" title="แสดง/ซ่อนรายชื่อสมาชิก">
                                                                <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                                                            </button>
                                                        </div>

                                                        <ol class="member-sublist">
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-4" data-member-name="นางสาวนัสรีน สุวิสัน" data-group-label="กลุ่มสาระฯ สังคมศึกษา ศาสนาและวัฒนธรรม" name="person_ids[]" value="1830101156953">
                                                                    <span class="member-name">นางสาวนัสรีน สุวิสัน</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-4" data-member-name="นางสาวปณิดา คลองรั้ว" data-group-label="กลุ่มสาระฯ สังคมศึกษา ศาสนาและวัฒนธรรม" name="person_ids[]" value="1810300103434">
                                                                    <span class="member-name">นางสาวปณิดา คลองรั้ว</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-4" data-member-name="นายมงคล ตันเจริญรัตน์" data-group-label="กลุ่มสาระฯ สังคมศึกษา ศาสนาและวัฒนธรรม" name="person_ids[]" value="1820501214179">
                                                                    <span class="member-name">นายมงคล ตันเจริญรัตน์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-4" data-member-name="นางจารุวรรณ ส่องศิริ" data-group-label="กลุ่มสาระฯ สังคมศึกษา ศาสนาและวัฒนธรรม" name="person_ids[]" value="3820100025592">
                                                                    <span class="member-name">นางจารุวรรณ ส่องศิริ</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-4" data-member-name="นางสาวชนิกานต์  สวัสดิวงค์" data-group-label="กลุ่มสาระฯ สังคมศึกษา ศาสนาและวัฒนธรรม" name="person_ids[]" value="3829900033725">
                                                                    <span class="member-name">นางสาวชนิกานต์ สวัสดิวงค์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-4" data-member-name="นางสาวประภาพรรณ กุลแก้ว" data-group-label="กลุ่มสาระฯ สังคมศึกษา ศาสนาและวัฒนธรรม" name="person_ids[]" value="1410100117524">
                                                                    <span class="member-name">นางสาวประภาพรรณ กุลแก้ว</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-4" data-member-name="นางปวีณา  บำรุงภักดิ์" data-group-label="กลุ่มสาระฯ สังคมศึกษา ศาสนาและวัฒนธรรม" name="person_ids[]" value="1829900007736">
                                                                    <span class="member-name">นางปวีณา บำรุงภักดิ์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-4" data-member-name="นางสาวธนวรรณ พิทักษ์คง" data-group-label="กลุ่มสาระฯ สังคมศึกษา ศาสนาและวัฒนธรรม" name="person_ids[]" value="1820500147966">
                                                                    <span class="member-name">นางสาวธนวรรณ พิทักษ์คง</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-4" data-member-name="นางสาวปิยธิดา นิยมเดชา" data-group-label="กลุ่มสาระฯ สังคมศึกษา ศาสนาและวัฒนธรรม" name="person_ids[]" value="1820600006469">
                                                                    <span class="member-name">นางสาวปิยธิดา นิยมเดชา</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-4" data-member-name="นางสาวลภัสภาส์ หนูคง" data-group-label="กลุ่มสาระฯ สังคมศึกษา ศาสนาและวัฒนธรรม" name="person_ids[]" value="1820500130320">
                                                                    <span class="member-name">นางสาวลภัสภาส์ หนูคง</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-4" data-member-name="นางวาสนา  สุทธจิตร์" data-group-label="กลุ่มสาระฯ สังคมศึกษา ศาสนาและวัฒนธรรม" name="person_ids[]" value="3820100025495">
                                                                    <span class="member-name">นางวาสนา สุทธจิตร์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-4" data-member-name="นางสาวนิรชา ธรรมัสโร" data-group-label="กลุ่มสาระฯ สังคมศึกษา ศาสนาและวัฒนธรรม" name="person_ids[]" value="1829900174284">
                                                                    <span class="member-name">นางสาวนิรชา ธรรมัสโร</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-4" data-member-name="นางสาวสรัลรัตน์ จันทับ" data-group-label="กลุ่มสาระฯ สังคมศึกษา ศาสนาและวัฒนธรรม" name="person_ids[]" value="1809901015490">
                                                                    <span class="member-name">นางสาวสรัลรัตน์ จันทับ</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-4" data-member-name="นางสาวสุดาทิพย์ ยกย่อง" data-group-label="กลุ่มสาระฯ สังคมศึกษา ศาสนาและวัฒนธรรม" name="person_ids[]" value="1820800031408">
                                                                    <span class="member-name">นางสาวสุดาทิพย์ ยกย่อง</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-4" data-member-name="นายเพลิน โอรักษ์" data-group-label="กลุ่มสาระฯ สังคมศึกษา ศาสนาและวัฒนธรรม" name="person_ids[]" value="3180600191510">
                                                                    <span class="member-name">นายเพลิน โอรักษ์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-4" data-member-name="ว่าที่ร้อยตรีเรืองเดชย์  ผสารพจน์" data-group-label="กลุ่มสาระฯ สังคมศึกษา ศาสนาและวัฒนธรรม" name="person_ids[]" value="1809900094507">
                                                                    <span class="member-name">ว่าที่ร้อยตรีเรืองเดชย์ ผสารพจน์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-4" data-member-name="นายวรานนท์ ภาระพฤติ" data-group-label="กลุ่มสาระฯ สังคมศึกษา ศาสนาและวัฒนธรรม" name="person_ids[]" value="3820100028745">
                                                                    <span class="member-name">นายวรานนท์ ภาระพฤติ</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-4" data-member-name="นายศุภสวัสดิ์ กาญวิจิต" data-group-label="กลุ่มสาระฯ สังคมศึกษา ศาสนาและวัฒนธรรม" name="person_ids[]" value="1829900093446">
                                                                    <span class="member-name">นายศุภสวัสดิ์ กาญวิจิต</span>
                                                                </label>
                                                            </li>
                                                        </ol>
                                                    </div>
                                                    <div class="item item-group is-collapsed">
                                                        <div class="group-header">
                                                            <label class="item-main">
                                                                <input type="checkbox" class="item-checkbox group-item-checkbox department-item-checkbox" data-group="department" data-group-key="department-5" data-group-label="กลุ่มสาระฯ สุขศึกษาและพลศึกษา" data-members="[{&quot;pID&quot;:&quot;1920400002230&quot;,&quot;name&quot;:&quot;นายประสิทธิ์  สะไน&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ สุขศึกษาและพลศึกษา&quot;},{&quot;pID&quot;:&quot;3820800037747&quot;,&quot;name&quot;:&quot;นายศรายุทธ  มิตรวงค์&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ สุขศึกษาและพลศึกษา&quot;},{&quot;pID&quot;:&quot;3820700143669&quot;,&quot;name&quot;:&quot;นายสมชาย สุทธจิตร์&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ สุขศึกษาและพลศึกษา&quot;},{&quot;pID&quot;:&quot;3820800038999&quot;,&quot;name&quot;:&quot;นางสาวนิรัตน์ เพชรแก้ว&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ สุขศึกษาและพลศึกษา&quot;},{&quot;pID&quot;:&quot;3820400261097&quot;,&quot;name&quot;:&quot;นายจรุง  บำรุงเขตต์&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ สุขศึกษาและพลศึกษา&quot;},{&quot;pID&quot;:&quot;1829900072562&quot;,&quot;name&quot;:&quot;นายเอกพงษ์ สงวนทรัพย์&quot;,&quot;faction&quot;:&quot;กลุ่มสาระฯ สุขศึกษาและพลศึกษา&quot;}]" value="department-5">
                                                                <span class="item-title">กลุ่มสาระฯ สุขศึกษาและพลศึกษา</span>
                                                                <small class="item-subtext">สมาชิกทั้งหมด 6 คน</small>
                                                            </label>
                                                            <button type="button" class="group-toggle" aria-expanded="false" title="แสดง/ซ่อนรายชื่อสมาชิก">
                                                                <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                                                            </button>
                                                        </div>

                                                        <ol class="member-sublist">
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-5" data-member-name="นายประสิทธิ์  สะไน" data-group-label="กลุ่มสาระฯ สุขศึกษาและพลศึกษา" name="person_ids[]" value="1920400002230">
                                                                    <span class="member-name">นายประสิทธิ์ สะไน</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-5" data-member-name="นายศรายุทธ  มิตรวงค์" data-group-label="กลุ่มสาระฯ สุขศึกษาและพลศึกษา" name="person_ids[]" value="3820800037747">
                                                                    <span class="member-name">นายศรายุทธ มิตรวงค์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-5" data-member-name="นายสมชาย สุทธจิตร์" data-group-label="กลุ่มสาระฯ สุขศึกษาและพลศึกษา" name="person_ids[]" value="3820700143669">
                                                                    <span class="member-name">นายสมชาย สุทธจิตร์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-5" data-member-name="นางสาวนิรัตน์ เพชรแก้ว" data-group-label="กลุ่มสาระฯ สุขศึกษาและพลศึกษา" name="person_ids[]" value="3820800038999">
                                                                    <span class="member-name">นางสาวนิรัตน์ เพชรแก้ว</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-5" data-member-name="นายจรุง  บำรุงเขตต์" data-group-label="กลุ่มสาระฯ สุขศึกษาและพลศึกษา" name="person_ids[]" value="3820400261097">
                                                                    <span class="member-name">นายจรุง บำรุงเขตต์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="department-5" data-member-name="นายเอกพงษ์ สงวนทรัพย์" data-group-label="กลุ่มสาระฯ สุขศึกษาและพลศึกษา" name="person_ids[]" value="1829900072562">
                                                                    <span class="member-name">นายเอกพงษ์ สงวนทรัพย์</span>
                                                                </label>
                                                            </li>
                                                        </ol>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="category-group">
                                                <div class="category-title">
                                                    <span>อื่นๆ</span>
                                                </div>
                                                <div class="category-items">
                                                    <div class="item item-group is-collapsed">
                                                        <div class="group-header">
                                                            <label class="item-main">
                                                                <input type="checkbox" class="item-checkbox group-item-checkbox" data-group="special" data-group-key="special-executive" data-group-label="คณะผู้บริหารสถานศึกษา" data-members="[{&quot;pID&quot;:&quot;1820500005169&quot;,&quot;name&quot;:&quot;นางสาวศริญญา  ผั้วผดุง&quot;,&quot;faction&quot;:&quot;คณะผู้บริหารสถานศึกษา&quot;},{&quot;pID&quot;:&quot;3810500334835&quot;,&quot;name&quot;:&quot;นายดลยวัฒน์ สันติพิทักษ์&quot;,&quot;faction&quot;:&quot;คณะผู้บริหารสถานศึกษา&quot;},{&quot;pID&quot;:&quot;1820500004103&quot;,&quot;name&quot;:&quot;นายยุทธนา สุวรรณวิสุทธิ์&quot;,&quot;faction&quot;:&quot;คณะผู้บริหารสถานศึกษา&quot;},{&quot;pID&quot;:&quot;3430200354125&quot;,&quot;name&quot;:&quot;นายไกรวิชญ์ อ่อนแก้ว&quot;,&quot;faction&quot;:&quot;คณะผู้บริหารสถานศึกษา&quot;}]" value="special-executive">
                                                                <span class="item-title">คณะผู้บริหารสถานศึกษา</span>
                                                                <small class="item-subtext">สมาชิกทั้งหมด 4 คน</small>
                                                            </label>
                                                            <button type="button" class="group-toggle" aria-expanded="false" title="แสดง/ซ่อนรายชื่อสมาชิก">
                                                                <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                                                            </button>
                                                        </div>

                                                        <ol class="member-sublist">
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="special-executive" data-member-name="นางสาวศริญญา  ผั้วผดุง" data-group-label="คณะผู้บริหารสถานศึกษา" name="person_ids[]" value="1820500005169">
                                                                    <span class="member-name">นางสาวศริญญา ผั้วผดุง</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="special-executive" data-member-name="นายดลยวัฒน์ สันติพิทักษ์" data-group-label="คณะผู้บริหารสถานศึกษา" name="person_ids[]" value="3810500334835">
                                                                    <span class="member-name">นายดลยวัฒน์ สันติพิทักษ์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="special-executive" data-member-name="นายยุทธนา สุวรรณวิสุทธิ์" data-group-label="คณะผู้บริหารสถานศึกษา" name="person_ids[]" value="1820500004103">
                                                                    <span class="member-name">นายยุทธนา สุวรรณวิสุทธิ์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="special-executive" data-member-name="นายไกรวิชญ์ อ่อนแก้ว" data-group-label="คณะผู้บริหารสถานศึกษา" name="person_ids[]" value="3430200354125">
                                                                    <span class="member-name">นายไกรวิชญ์ อ่อนแก้ว</span>
                                                                </label>
                                                            </li>
                                                        </ol>
                                                    </div>
                                                    <div class="item item-group is-collapsed">
                                                        <div class="group-header">
                                                            <label class="item-main">
                                                                <input type="checkbox" class="item-checkbox group-item-checkbox" data-group="special" data-group-key="special-subject-head" data-group-label="หัวหน้ากลุ่มสาระ" data-members="[{&quot;pID&quot;:&quot;5800900028151&quot;,&quot;name&quot;:&quot;นางจริยาวดี  เวชจันทร์&quot;,&quot;faction&quot;:&quot;หัวหน้ากลุ่มสาระ&quot;},{&quot;pID&quot;:&quot;3840100521778&quot;,&quot;name&quot;:&quot;นางดวงกมล  เพ็ชรพรหม&quot;,&quot;faction&quot;:&quot;หัวหน้ากลุ่มสาระ&quot;},{&quot;pID&quot;:&quot;3940400027034&quot;,&quot;name&quot;:&quot;นางพนิดา ค้าของ&quot;,&quot;faction&quot;:&quot;หัวหน้ากลุ่มสาระ&quot;},{&quot;pID&quot;:&quot;1820700006258&quot;,&quot;name&quot;:&quot;นางสาวนุชรีย์ หัศนี&quot;,&quot;faction&quot;:&quot;หัวหน้ากลุ่มสาระ&quot;},{&quot;pID&quot;:&quot;1820800031408&quot;,&quot;name&quot;:&quot;นางสาวสุดาทิพย์ ยกย่อง&quot;,&quot;faction&quot;:&quot;หัวหน้ากลุ่มสาระ&quot;},{&quot;pID&quot;:&quot;3820700019381&quot;,&quot;name&quot;:&quot;นางเสาวลีย์ จันทร์ทอง&quot;,&quot;faction&quot;:&quot;หัวหน้ากลุ่มสาระ&quot;},{&quot;pID&quot;:&quot;1959900030702&quot;,&quot;name&quot;:&quot;นายธันวิน  ณ นคร&quot;,&quot;faction&quot;:&quot;หัวหน้ากลุ่มสาระ&quot;},{&quot;pID&quot;:&quot;1839900094990&quot;,&quot;name&quot;:&quot;นายนพรัตน์ ย้อยพระจันทร์&quot;,&quot;faction&quot;:&quot;หัวหน้ากลุ่มสาระ&quot;},{&quot;pID&quot;:&quot;3820700143669&quot;,&quot;name&quot;:&quot;นายสมชาย สุทธจิตร์&quot;,&quot;faction&quot;:&quot;หัวหน้ากลุ่มสาระ&quot;},{&quot;pID&quot;:&quot;3820400194578&quot;,&quot;name&quot;:&quot;นายสุพัฒน์  เจริญฤทธิ์&quot;,&quot;faction&quot;:&quot;หัวหน้ากลุ่มสาระ&quot;}]" value="special-subject-head">
                                                                <span class="item-title">หัวหน้ากลุ่มสาระ</span>
                                                                <small class="item-subtext">สมาชิกทั้งหมด 10 คน</small>
                                                            </label>
                                                            <button type="button" class="group-toggle" aria-expanded="false" title="แสดง/ซ่อนรายชื่อสมาชิก">
                                                                <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                                                            </button>
                                                        </div>

                                                        <ol class="member-sublist">
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="special-subject-head" data-member-name="นางจริยาวดี  เวชจันทร์" data-group-label="หัวหน้ากลุ่มสาระ" name="person_ids[]" value="5800900028151">
                                                                    <span class="member-name">นางจริยาวดี เวชจันทร์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="special-subject-head" data-member-name="นางดวงกมล  เพ็ชรพรหม" data-group-label="หัวหน้ากลุ่มสาระ" name="person_ids[]" value="3840100521778">
                                                                    <span class="member-name">นางดวงกมล เพ็ชรพรหม</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="special-subject-head" data-member-name="นางพนิดา ค้าของ" data-group-label="หัวหน้ากลุ่มสาระ" name="person_ids[]" value="3940400027034">
                                                                    <span class="member-name">นางพนิดา ค้าของ</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="special-subject-head" data-member-name="นางสาวนุชรีย์ หัศนี" data-group-label="หัวหน้ากลุ่มสาระ" name="person_ids[]" value="1820700006258">
                                                                    <span class="member-name">นางสาวนุชรีย์ หัศนี</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="special-subject-head" data-member-name="นางสาวสุดาทิพย์ ยกย่อง" data-group-label="หัวหน้ากลุ่มสาระ" name="person_ids[]" value="1820800031408">
                                                                    <span class="member-name">นางสาวสุดาทิพย์ ยกย่อง</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="special-subject-head" data-member-name="นางเสาวลีย์ จันทร์ทอง" data-group-label="หัวหน้ากลุ่มสาระ" name="person_ids[]" value="3820700019381">
                                                                    <span class="member-name">นางเสาวลีย์ จันทร์ทอง</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="special-subject-head" data-member-name="นายธันวิน  ณ นคร" data-group-label="หัวหน้ากลุ่มสาระ" name="person_ids[]" value="1959900030702">
                                                                    <span class="member-name">นายธันวิน ณ นคร</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="special-subject-head" data-member-name="นายนพรัตน์ ย้อยพระจันทร์" data-group-label="หัวหน้ากลุ่มสาระ" name="person_ids[]" value="1839900094990">
                                                                    <span class="member-name">นายนพรัตน์ ย้อยพระจันทร์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="special-subject-head" data-member-name="นายสมชาย สุทธจิตร์" data-group-label="หัวหน้ากลุ่มสาระ" name="person_ids[]" value="3820700143669">
                                                                    <span class="member-name">นายสมชาย สุทธจิตร์</span>
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="item member-item">
                                                                    <input type="checkbox" class="member-checkbox" data-member-group-key="special-subject-head" data-member-name="นายสุพัฒน์  เจริญฤทธิ์" data-group-label="หัวหน้ากลุ่มสาระ" name="person_ids[]" value="3820400194578">
                                                                    <span class="member-name">นายสุพัฒน์ เจริญฤทธิ์</span>
                                                                </label>
                                                            </li>
                                                        </ol>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="sent-notice-selected">
                                    <button id="modalOrderSendBtnShowRecipients" type="button">
                                        <p>แสดงผู้รับทั้งหมด</p>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div id="modalOrderTrackSection" style="display: none;">
                        <div class="orders-send-summary">
                            <p>จำนวนผู้รับทั้งหมด: <strong id="modalOrderTrackTotal">0</strong> คน</p>
                            <p>ผู้เปิดอ่านแล้ว: <strong id="modalOrderTrackRead">0</strong> คน</p>
                        </div>

                        <div class="table-responsive">
                            <table class="custom-table orders-send-track-table">
                                <thead>
                                    <tr>
                                        <th>ชื่อผู้รับ</th>
                                        <th>สถานะ</th>
                                        <th>เวลาอ่านล่าสุด</th>
                                    </tr>
                                </thead>
                                <tbody id="modalOrderTrackBody">
                                    <tr>
                                        <td colspan="3" class="orders-send-track-empty">ไม่พบข้อมูลผู้รับ</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <p class="orders-send-warning" id="modalOrderTrackRecallLocked" style="display: none;">มีผู้รับเปิดอ่านแล้ว ไม่สามารถดึงกลับได้</p>

                        <div class="booking-actions">
                            <form method="POST" action="orders-create.php" id="modalOrderRecallForm">
                                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                                <input type="hidden" name="order_action" value="recall">
                                <input type="hidden" name="send_order_id" id="modalOrderRecallOrderId" value="">
                                <button type="submit" class="booking-action-btn secondary" id="modalOrderRecallButton">
                                    <i class="fa-solid fa-rotate-left"></i>
                                    <span>ดึงกลับเพื่อแก้ไข/ส่งใหม่</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="footer-modal">
                <form method="POST" action="orders-create.php" class="orders-send-form" id="modalOrderSendForm">

                    <button type="submit" form="modalOrderEditForm">
                        <p>ส่งคำสั่ง</p>
                    </button>

                </form>

            </div>
        </div>
    </div>
</div>

<div class="content-circular-notice-index circular-track-modal-host">
    <div class="modal-overlay-circular-notice-index outside-person" id="modalOrderViewOverlay">
        <div class="modal-content">
            <div class="header-modal">
                <div class="first-header">
                    <p>รายชื่อผู้รับเอกสาร</p>
                </div>
                <div class="sec-header">
                    <i class="fa-solid fa-xmark" id="closeModalOrderView"></i>
                </div>
            </div>
            <div class="content-modal">
                <div class="content-topic-sec">
                    <div class="more-details">
                        <p><strong>คำสั่งที่</strong></p>
                        <input type="text" id="modalOrderSendNo" class="order-no-display" value="-" disabled>
                    </div>
                    <div class="more-details">
                        <p><strong>เรื่อง</strong></p>
                        <input type="text" id="modalOrderSendSubject" class="order-no-display" value="-" disabled>
                    </div>
                </div>

                <div class="content-topic-sec">
                    <div class="more-details">
                        <p><strong>ทั้งนี้ตั้งแต่วันที่</strong></p>
                        <input type="date" id="modalOrderSendEffectiveDate" class="order-no-display" value="" disabled>
                    </div>
                    <div class="more-details">
                        <p><strong>สั่ง ณ วันที่</strong></p>
                        <input type="date" id="modalOrderSendDate" class="order-no-display" value="" disabled>
                    </div>
                </div>

                <div class="content-topic-sec">
                    <div class="more-details">
                        <p><strong>ผู้ออกเลขคำสั่ง</strong></p>
                        <input type="text" id="modalOrderSendIssuer" class="order-no-display" value="-" disabled>
                    </div>
                    <div class="more-details">
                        <p><strong>กลุ่ม</strong></p>
                        <input type="text" id="modalOrderSendGroup" class="order-no-display" value="-" disabled>
                    </div>
                </div>

                <div class="orders-send-modal-shell orders-send-card">
                    <div id="modalOrderSendFormSection">
                        <form method="POST" action="orders-create.php" class="orders-send-form" id="modalOrderSendForm">
                            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                            <input type="hidden" name="order_action" value="send">
                            <input type="hidden" name="send_order_id" id="modalOrderSendOrderId" value="">
                        </form>
                    </div>
                </div>

                <div class="content-file-sec">
                    <p><strong>ไฟล์เอกสารแนบจากระบบ</strong></p>
                    <div class="file-section" id="modalOrderSendFileSection"></div>
                </div>

                <div class="content-table-sec">
                    <div class="table-responsive">
                        <table class="custom-table orders-send-track-table">
                            <thead>
                                <tr>
                                    <th>ชื่อจริง-นามสกุล</th>
                                    <th style="width: 20%">สถานะ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Lorem ipsum dolor sit amet consectetur.</td>
                                    <td> <span class="status-pill approved">รับเอกสารแล้ว</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <div class="footer-modal">
                <form method="POST" action="orders-create.php" class="orders-send-form" id="modalOrderSendForm">

                    <button type="submit" form="modalOrderEditForm">
                        <p>ส่งคำสั่ง</p>
                    </button>

                </form>

            </div>
        </div>
    </div>
</div>

<div id="modalOrderSendRecipientModal" class="modal-overlay-recipient">
    <div class="modal-container">
        <div class="modal-header">
            <div class="modal-title">
                <i class="fa-solid fa-users"></i>
                <span>รายชื่อผู้รับคำสั่งราชการ</span>
            </div>
            <button class="modal-close" id="modalOrderSendRecipientClose" type="button">
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
                <tbody id="modalOrderSendRecipientTableBody"></tbody>
            </table>
        </div>
    </div>
</div>

<div class="content-circular-notice-index circular-track-modal-host">
    <div class="modal-overlay-circular-notice-index outside-person" id="modalOrderEditOverlay">
        <div class="modal-content">
            <div class="header-modal">
                <div class="first-header">
                    <p>แก้ไขและแนบไฟล์คำสั่งราชการ</p>
                </div>
                <div class="sec-header">
                    <i class="fa-solid fa-xmark" id="closeModalOrderEdit"></i>
                </div>
            </div>

            <div class="content-modal">
                <form method="POST" action="orders-create.php" enctype="multipart/form-data" id="modalOrderEditForm">

                    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                    <input type="hidden" name="order_id" id="modalOrderId" value="">
                    <input type="hidden" name="from_track_modal" value="1">

                    <div class="content-topic-sec">
                        <div class="more-details">
                            <p><strong>คำสั่งที่</strong></p>
                            <input type="text" id="modalOrderNo" class="order-no-display" placeholder="-" disabled>
                        </div>
                        <div class="more-details">
                            <p><strong>เรื่อง</strong></p>
                            <input type="text" id="modalOrderSubject" name="subject" placeholder="ระบุหัวข้อคำสั่ง" maxlength="300" required>
                        </div>
                    </div>

                    <div class="content-topic-sec">
                        <div class="more-details">
                            <p><strong>ทั้งนี้ตั้งแต่วันที่</strong></p>
                            <input type="date" id="modalOrderEffectiveDate" name="effective_date" required>
                        </div>
                        <div class="more-details">
                            <p><strong>สั่ง ณ วันที่</strong></p>
                            <input type="date" id="modalOrderDate" name="order_date" required>
                        </div>
                    </div>

                    <div class="content-topic-sec">
                        <div class="more-details">
                            <p><strong>ผู้ออกเลขคำสั่ง</strong></p>
                            <input type="text" id="modalOrderIssuer" class="order-no-display" placeholder="-" disabled>
                        </div>
                        <div class="more-details">
                            <p><strong>กลุ่ม</strong></p>
                            <div class="custom-select-wrapper" id="modalOrderGroupWrapper">
                                <div class="custom-select-trigger">
                                    <p class="select-value"><?= h($selected_group_name !== '' ? $selected_group_name : 'เลือกกลุ่ม') ?></p>
                                    <i class="fa-solid fa-chevron-down"></i>
                                </div>

                                <div class="custom-options">
                                    <?php foreach ($faction_options as $fid => $name): ?>
                                        <?php $modal_group_fid = (string) $fid; ?>
                                        <div class="custom-option<?= $modal_group_fid === $selected_group_fid ? ' selected' : '' ?>" data-value="<?= h($modal_group_fid) ?>">
                                            <?= h((string) $name) ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <input type="hidden" id="modalOrderGroupFid" name="group_fid" value="<?= h($selected_group_fid) ?>">
                            </div>
                        </div>
                    </div>

                    <div class="content-file-sec">


                        <label>อัปโหลดไฟล์เอกสาร</label>
                        <section class="upload-layout">
                            <input
                                type="file"
                                id="fileInput_modal"
                                name="attachments[]"
                                multiple
                                accept="application/pdf,image/png,image/jpeg"
                                style="display: none;">

                            <div class="upload-box" id="dropzone_modal">
                                <i class="fa-solid fa-upload"></i>
                                <p>ลากไฟล์มาวางที่นี่</p>
                            </div>

                            <div class="existing-file-section">
                                <!-- <label>ไฟล์ที่แนบแล้ว</label> -->
                                <div class="file-list" id="existingFileListContainer_modal">
                                    <p class="existing-file-empty">ยังไม่มีไฟล์แนบ</p>
                                </div>
                            </div>

                        </section>

                        <div class="row form-group">
                            <button class="btn btn-upload-small" type="button" id="btnAddFiles_modal">
                                <p>เพิ่มไฟล์</p>
                            </button>
                            <div class="file-hint">
                                <p>* แนบไฟล์ได้สูงสุด 5 ไฟล์ (รวม PNG และ PDF) *</p>
                            </div>
                        </div>

                    </div>
                </form>
            </div>


            <div class="footer-modal">
                <button type="submit" form="modalOrderEditForm">
                    <p>บันทึกการแก้ไข</p>
                </button>
            </div>
        </div>
    </div>
</div>

<div id="imagePreviewModal" class="modal-overlay-preview">
    <span class="close-preview" id="closePreviewBtn">&times;</span>
    <img class="preview-content" id="previewImage" alt="">
    <div id="previewCaption"></div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        function setupFileUpload(inputId, listId, maxFiles = 1, options = {}) {
            const fileInput = document.getElementById(inputId);
            const fileList = document.getElementById(listId);
            const dropzone = options.dropzoneId ? document.getElementById(options.dropzoneId) : null;
            const addFilesBtn = options.addButtonId ? document.getElementById(options.addButtonId) : null;
            const previewModal = document.getElementById("imagePreviewModal");
            const previewImage = document.getElementById("previewImage");
            const previewCaption = document.getElementById("previewCaption");
            const closePreviewBtn = document.getElementById("closePreviewBtn");
            const allowedTypes = ["application/pdf", "image/jpeg", "image/png"];
            let selectedFiles = [];

            if (!fileInput) return null;

            const renderFiles = () => {
                if (!fileList) return;

                const newFileElements = fileList.querySelectorAll('.new-file-item');
                newFileElements.forEach(el => el.remove());

                let emptyMsg = fileList.querySelector('.existing-file-empty');
                if (selectedFiles.length === 0) {
                    const hasExistingFiles = fileList.querySelectorAll('.file-item-wrapper').length > 0;
                    if (!hasExistingFiles) {
                        if (!emptyMsg) {
                            emptyMsg = document.createElement('p');
                            emptyMsg.className = 'existing-file-empty';
                            emptyMsg.textContent = 'ยังไม่มีไฟล์แนบ';
                            fileList.appendChild(emptyMsg);
                        } else {
                            emptyMsg.style.display = 'block';
                        }
                    }
                    return;
                }

                if (emptyMsg) emptyMsg.style.display = 'none';

                selectedFiles.forEach((file, index) => {
                    const wrapper = document.createElement("div");
                    wrapper.className = "file-item-wrapper new-file-item";

                    const deleteBtn = document.createElement("button");
                    deleteBtn.type = "button";
                    deleteBtn.className = "delete-btn";
                    deleteBtn.innerHTML = '<i class="fa-solid fa-trash-can"></i>';
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
                    icon.innerHTML = file.type === "application/pdf" ?
                        '<i class="fa-solid fa-file-pdf"></i>' :
                        '<i class="fa-solid fa-file-image"></i>';

                    const text = document.createElement("div");
                    text.className = "file-text";

                    const name = document.createElement("div");
                    name.className = "file-name";
                    name.textContent = file.name;

                    const type = document.createElement("div");
                    type.className = "file-type";
                    type.textContent = file.type || "ไฟล์แนบ";

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

            const resetFiles = () => {
                selectedFiles = [];
                syncFiles();
                renderFiles();
            };

            const addFiles = (files) => {
                if (!files) return;
                const existing = new Set(selectedFiles.map((file) => `${file.name}-${file.size}-${file.lastModified}`));

                const existingDbFilesCount = fileList ? fileList.querySelectorAll('.file-item-wrapper:not(.new-file-item)').length : 0;

                let currentTotal = existingDbFilesCount + selectedFiles.length;
                let showLimitAlert = false;

                Array.from(files).forEach((file) => {
                    if (currentTotal >= maxFiles) {
                        showLimitAlert = true;
                        return;
                    }

                    const key = `${file.name}-${file.size}-${file.lastModified}`;
                    if (existing.has(key)) return;
                    if (!allowedTypes.includes(file.type)) return;

                    selectedFiles.push(file);
                    existing.add(key);
                    currentTotal++;
                });

                if (showLimitAlert) {
                    alert(`คุณสามารถแนบไฟล์ได้สูงสุดรวมกัน ${maxFiles} ไฟล์เท่านั้น`);
                }

                syncFiles();
                renderFiles();
            };

            fileInput.addEventListener("change", (e) => {
                addFiles(e.target.files);
            });

            if (dropzone) {
                dropzone.addEventListener("click", () => fileInput.click());
                dropzone.addEventListener("dragover", (e) => {
                    e.preventDefault();
                    dropzone.classList.add("active");
                });
                dropzone.addEventListener("dragleave", () => {
                    dropzone.classList.remove("active");
                });
                dropzone.addEventListener("drop", (e) => {
                    e.preventDefault();
                    dropzone.classList.remove("active");
                    addFiles(e.dataTransfer?.files || []);
                });
            }

            if (addFilesBtn) {
                addFilesBtn.addEventListener("click", () => fileInput.click());
            }

            if (closePreviewBtn) {
                closePreviewBtn.addEventListener("click", () => previewModal?.classList.remove("active"));
            }
            if (previewModal) {
                previewModal.addEventListener("click", (e) => {
                    if (e.target === previewModal) previewModal.classList.remove("active");
                });
            }

            renderFiles();
            return {
                reset: resetFiles,
            };
        }

        const modalAttachmentUpload = setupFileUpload("fileInput_modal", "existingFileListContainer_modal", 5, {
            dropzoneId: "dropzone_modal",
            addButtonId: "btnAddFiles_modal",
        });

        const orderEditModal = document.getElementById('modalOrderEditOverlay');
        const closeOrderEditModalBtn = document.getElementById('closeModalOrderEdit');
        const modalOrderId = document.getElementById('modalOrderId');
        const modalOrderNo = document.getElementById('modalOrderNo');
        const modalOrderSubject = document.getElementById('modalOrderSubject');
        const modalOrderEffectiveDate = document.getElementById('modalOrderEffectiveDate');
        const modalOrderDate = document.getElementById('modalOrderDate');
        const modalOrderIssuer = document.getElementById('modalOrderIssuer');
        const modalOrderGroupFid = document.getElementById('modalOrderGroupFid');
        const modalExistingFileList = document.getElementById('existingFileListContainer_modal');
        const modalOrderGroupWrapper = document.getElementById('modalOrderGroupWrapper');
        const modalOrderGroupDisplay = modalOrderGroupWrapper?.querySelector('.select-value') ?? null;
        const modalOrderGroupOptions = modalOrderGroupWrapper ?
            Array.from(modalOrderGroupWrapper.querySelectorAll('.custom-option')) : [];
        const orderSendModal = document.getElementById('modalOrderSendOverlay');
        const closeOrderSendModalBtn = document.getElementById('closeModalOrderSend');
        const modalOrderSendTitle = document.getElementById('modalOrderSendTitle');
        const modalOrderSendNo = document.getElementById('modalOrderSendNo');
        const modalOrderSendSubject = document.getElementById('modalOrderSendSubject');
        const modalOrderSendEffectiveDate = document.getElementById('modalOrderSendEffectiveDate');
        const modalOrderSendDate = document.getElementById('modalOrderSendDate');
        const modalOrderSendIssuer = document.getElementById('modalOrderSendIssuer');
        const modalOrderSendGroup = document.getElementById('modalOrderSendGroup');
        const modalOrderSendFileSection = document.getElementById('modalOrderSendFileSection');
        const modalOrderSendFormSection = document.getElementById('modalOrderSendFormSection');
        const modalOrderTrackSection = document.getElementById('modalOrderTrackSection');
        const modalOrderSendForm = document.getElementById('modalOrderSendForm');
        const modalOrderSendOrderId = document.getElementById('modalOrderSendOrderId');
        const modalOrderTrackTotal = document.getElementById('modalOrderTrackTotal');
        const modalOrderTrackRead = document.getElementById('modalOrderTrackRead');
        const modalOrderTrackBody = document.getElementById('modalOrderTrackBody');
        const modalOrderTrackRecallLocked = document.getElementById('modalOrderTrackRecallLocked');
        const modalOrderRecallForm = document.getElementById('modalOrderRecallForm');
        const modalOrderRecallOrderId = document.getElementById('modalOrderRecallOrderId');
        const modalOrderRecallButton = document.getElementById('modalOrderRecallButton');
        const modalOrderSendBtnShowRecipients = document.getElementById('modalOrderSendBtnShowRecipients');
        const modalOrderSendRecipientModal = document.getElementById('modalOrderSendRecipientModal');
        const modalOrderSendRecipientClose = document.getElementById('modalOrderSendRecipientClose');
        const modalOrderSendRecipientTableBody = document.getElementById('modalOrderSendRecipientTableBody');
        const initialSendModalOrderId = <?= (int) $send_modal_open_order_id ?>;
        let orderSendModalData = {};
        const syncOrderSendModalData = () => {
            const mapElement = document.querySelector('#orderMine .js-order-send-map');
            if (!mapElement) {
                orderSendModalData = {};
                return;
            }
            try {
                const parsed = JSON.parse(mapElement.textContent || '{}');
                if (parsed && typeof parsed === 'object') {
                    orderSendModalData = parsed;
                    return;
                }
            } catch (error) {
                console.error('Invalid send modal data', error);
            }
            orderSendModalData = {};
        };

        const syncModalGroupSelect = (targetValue = '') => {
            if (!modalOrderGroupFid || !modalOrderGroupWrapper) {
                return;
            }

            const normalizedTarget = String(targetValue || '').trim();
            let matchedOption = null;

            if (normalizedTarget !== '') {
                matchedOption = modalOrderGroupOptions.find((option) => {
                    return String(option.getAttribute('data-value') || '') === normalizedTarget;
                }) || null;
            }

            if (!matchedOption && modalOrderGroupOptions.length > 0) {
                [matchedOption] = modalOrderGroupOptions;
            }

            const nextValue = matchedOption ? String(matchedOption.getAttribute('data-value') || '') : '';
            modalOrderGroupFid.value = nextValue;

            modalOrderGroupOptions.forEach((option) => {
                option.classList.toggle('selected', option === matchedOption);
            });

            if (modalOrderGroupDisplay) {
                modalOrderGroupDisplay.textContent = matchedOption ?
                    String(matchedOption.textContent || '').trim() :
                    'เลือกกลุ่ม';
            }

            modalOrderGroupWrapper.classList.remove('open');
        };

        syncModalGroupSelect(modalOrderGroupFid?.value || '');

        const setupOrderSendRecipientDropdown = () => {
            if (!modalOrderSendForm) {
                return () => {};
            }

            const recipientSection = modalOrderSendForm.querySelector('[data-order-send-recipients]');
            const dropdown = document.getElementById('orderSendDropdownContent');
            const toggle = document.getElementById('orderSendRecipientToggle');
            const searchInput = document.getElementById('orderSendMainInput');
            const selectAll = document.getElementById('orderSendSelectAll');

            if (!recipientSection || !dropdown || !toggle || !searchInput || !selectAll) {
                return () => {};
            }

            const groupChecks = Array.from(modalOrderSendForm.querySelectorAll('.group-item-checkbox'));
            const memberChecks = Array.from(modalOrderSendForm.querySelectorAll('.member-checkbox'));
            const groupItems = Array.from(modalOrderSendForm.querySelectorAll('.dropdown-list .item-group'));
            const directPersonItems = Array.from(modalOrderSendForm.querySelectorAll('.dropdown-list .category-items > label.item.member-item[data-search]'));
            const categoryGroups = Array.from(modalOrderSendForm.querySelectorAll('.dropdown-list .category-group'));

            const normalizeSearchText = (value) => String(value || '')
                .toLowerCase()
                .replace(/\s+/g, '')
                .replace(/[^0-9a-z\u0E00-\u0E7F]/gi, '');

            const getMemberChecksByGroupKey = (groupKey) => {
                return memberChecks.filter((el) => String(el.dataset.memberGroupKey || '') === String(groupKey));
            };

            const syncMemberByPid = (pid, checked, source) => {
                const normalizedPid = String(pid || '').trim();
                if (normalizedPid === '') {
                    return;
                }
                memberChecks.forEach((memberCheck) => {
                    if (memberCheck === source) {
                        return;
                    }
                    if (String(memberCheck.value || '') !== normalizedPid) {
                        return;
                    }
                    if (memberCheck.disabled) {
                        return;
                    }
                    memberCheck.checked = checked;
                });
            };

            const setGroupCollapsed = (groupItem, collapsed) => {
                if (!groupItem) {
                    return;
                }
                groupItem.classList.toggle('is-collapsed', collapsed);
                const toggleBtn = groupItem.querySelector('.group-toggle');
                if (toggleBtn) {
                    toggleBtn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
                }
            };

            const setDropdownVisible = (visible) => {
                dropdown.classList.toggle('show', visible);
                toggle.classList.toggle('active', visible);
            };

            const updateSelectAllState = () => {
                const allChecks = [...groupChecks, ...memberChecks];
                const checkedCount = allChecks.filter((el) => el.checked).length;
                selectAll.checked = allChecks.length > 0 && checkedCount === allChecks.length;
                selectAll.indeterminate = checkedCount > 0 && checkedCount < allChecks.length;

                groupChecks.forEach((groupCheck) => {
                    const groupKey = groupCheck.getAttribute('data-group-key') || '';
                    const members = getMemberChecksByGroupKey(groupKey);
                    if (members.length <= 0) {
                        groupCheck.indeterminate = false;
                        return;
                    }
                    const checkedMembers = members.filter((el) => el.checked).length;
                    if (checkedMembers === 0) {
                        groupCheck.checked = false;
                        groupCheck.indeterminate = false;
                        return;
                    }
                    if (checkedMembers === members.length) {
                        groupCheck.checked = true;
                        groupCheck.indeterminate = false;
                        return;
                    }
                    groupCheck.checked = false;
                    groupCheck.indeterminate = true;
                });
            };

            const filterRecipientDropdown = (rawQuery) => {
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
                        const rowText = normalizeSearchText(row.textContent || '');
                        const matched = isGroupMatch || rowText.includes(query);
                        row.style.display = matched ? '' : 'none';
                        if (matched) {
                            hasMemberMatch = true;
                        }
                    });

                    const isVisible = isGroupMatch || hasMemberMatch;
                    groupItem.style.display = isVisible ? '' : 'none';
                    if (isVisible) {
                        setGroupCollapsed(groupItem, false);
                    }
                });

                directPersonItems.forEach((item) => {
                    if (query === '') {
                        item.style.display = '';
                        return;
                    }
                    const rowText = normalizeSearchText(item.textContent || '');
                    item.style.display = rowText.includes(query) ? '' : 'none';
                });

                categoryGroups.forEach((category) => {
                    const hasVisibleGroup = Array.from(category.querySelectorAll('.category-items .item-group'))
                        .some((item) => item.style.display !== 'none');
                    const hasVisiblePerson = Array.from(category.querySelectorAll('.category-items > label.item.member-item[data-search]'))
                        .some((item) => item.style.display !== 'none');
                    category.style.display = hasVisibleGroup || hasVisiblePerson ? '' : 'none';
                });
            };

            toggle.addEventListener('click', (event) => {
                event.stopPropagation();
                const clickedInput = event.target instanceof HTMLElement && (
                    event.target.matches('input.search-input') ||
                    !!event.target.closest('input.search-input')
                );
                if (clickedInput) {
                    setDropdownVisible(true);
                    return;
                }
                setDropdownVisible(!dropdown.classList.contains('show'));
            });

            document.addEventListener('click', (event) => {
                if (!dropdown.contains(event.target) && !toggle.contains(event.target)) {
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

            searchInput.addEventListener('focus', () => {
                setDropdownVisible(true);
            });
            searchInput.addEventListener('input', () => {
                setDropdownVisible(true);
                filterRecipientDropdown(searchInput.value || '');
            });
            searchInput.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    setDropdownVisible(false);
                }
            });

            selectAll.addEventListener('change', () => {
                const checked = selectAll.checked;
                [...groupChecks, ...memberChecks].forEach((el) => {
                    if (!el.disabled) {
                        el.checked = checked;
                    }
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

            groupChecks.forEach((item) => {
                if (!item.checked) {
                    return;
                }
                const groupKey = item.getAttribute('data-group-key') || '';
                const members = getMemberChecksByGroupKey(groupKey);
                members.forEach((member) => {
                    member.checked = true;
                    syncMemberByPid(member.value || '', true, member);
                });
            });

            recipientSection.classList.remove('u-hidden');
            updateSelectAllState();
            filterRecipientDropdown('');

            return updateSelectAllState;
        };

        const escapeHtml = (value) => {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        };

        const renderOrderSendFiles = (orderId, files) => {
            const fileSections = document.querySelectorAll('#modalOrderSendFileSection');

            if (fileSections.length === 0) {
                return;
            }

            if (!Array.isArray(files) || files.length <= 0) {
                const emptyHtml = '<div class="file-banner"><div class="file-info"><div class="file-text"><span class="file-name">ไม่มีไฟล์แนบ</span></div></div></div>';
                fileSections.forEach(el => el.innerHTML = emptyHtml);
                return;
            }

            const safeOrderId = encodeURIComponent(String(orderId || '').trim());
            const html = files.map((file) => {
                const fileId = encodeURIComponent(String(file?.fileID || ''));
                const fileName = escapeHtml(String(file?.fileName || '-'));
                const mimeType = escapeHtml(String(file?.mimeType || 'ไฟล์แนบ'));
                const viewHref = `public/api/file-download.php?module=orders&entity_id=${safeOrderId}&file_id=${fileId}`;
                const iconHtml = String(file?.mimeType || '').toLowerCase() === 'application/pdf' ?
                    '<i class="fa-solid fa-file-pdf"></i>' :
                    '<i class="fa-solid fa-image"></i>';

                return `<div class="file-banner">
                    <div class="file-info">
                        <div class="file-icon">${iconHtml}</div>
                        <div class="file-text">
                            <span class="file-name">${fileName}</span>
                            <span class="file-type">${mimeType}</span>
                        </div>
                    </div>
                    <div class="file-actions">
                        <a href="${viewHref}" target="_blank" rel="noopener">
                            <i class="fa-solid fa-eye"></i>
                        </a>
                    </div>
                </div>`;
            }).join('');

            fileSections.forEach(el => el.innerHTML = html);
        };
        const renderExistingOrderFiles = (orderId, rawJson) => {
            if (!modalExistingFileList) {
                return;
            }

            let files = [];
            try {
                const parsed = JSON.parse(String(rawJson || '[]'));
                files = Array.isArray(parsed) ? parsed : [];
            } catch (error) {
                files = [];
            }

            if (files.length <= 0) {
                modalExistingFileList.innerHTML = '<p class="existing-file-empty">ยังไม่มีไฟล์แนบ</p>';
                return;
            }

            const safeOrderId = encodeURIComponent(String(orderId || '').trim());

            const rowsHtml = files.map((file) => {
                const fileId = encodeURIComponent(String(file.fileID || ''));
                const fileName = escapeHtml(String(file.fileName || '-'));
                const mimeType = escapeHtml(String(file.mimeType || 'ไฟล์แนบ'));
                const viewHref = `public/api/file-download.php?module=orders&entity_id=${safeOrderId}&file_id=${fileId}`;
                const iconHtml = String(file.mimeType || '').toLowerCase() === 'application/pdf' ?
                    '<i class="fa-solid fa-file-pdf" aria-hidden="true"></i>' :
                    '<i class="fa-solid fa-file-image" aria-hidden="true"></i>';

                return `<div class="file-item-wrapper" id="existing-file-${fileId}">
                    <button type="button" class="delete-btn js-delete-existing" data-file-id="${fileId}" title="ลบไฟล์">
                        <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                    </button>
                    <div class="file-banner">
                        <div class="file-info">
                            <div class="file-icon">${iconHtml}</div>
                            <div class="file-text">
                                <span class="file-name">${fileName}</span>
                                <span class="file-type">${mimeType}</span>
                            </div>
                        </div>
                        <div class="file-actions">
                            <a href="${viewHref}" target="_blank" rel="noopener" class="action-btn" title="ดูตัวอย่าง">
                                <i class="fa-solid fa-eye" aria-hidden="true"></i>
                            </a>
                        </div>
                    </div>
                </div>`;
            }).join('');

            modalExistingFileList.innerHTML = rowsHtml;

            const deleteBtns = modalExistingFileList.querySelectorAll('.js-delete-existing');
            deleteBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const fId = this.getAttribute('data-file-id');
                    const wrapper = document.getElementById(`existing-file-${fId}`);

                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'deleted_existing_files[]';
                    hiddenInput.value = decodeURIComponent(fId);
                    document.getElementById('modalOrderEditForm').appendChild(hiddenInput);

                    if (wrapper) wrapper.remove();

                    if (modalExistingFileList.querySelectorAll('.file-item-wrapper').length === 0) {
                        let emptyMsg = modalExistingFileList.querySelector('.existing-file-empty');
                        if (!emptyMsg) {
                            const p = document.createElement('p');
                            p.className = 'existing-file-empty';
                            p.textContent = 'ยังไม่มีไฟล์แนบ';
                            modalExistingFileList.appendChild(p);
                        } else {
                            emptyMsg.style.display = 'block';
                        }
                    }
                });
            });
        };

        const collectRecipientSummary = () => {
            if (!modalOrderSendForm) {
                return {
                    selectedSources: 0,
                    uniqueRecipients: 0,
                };
            }

            const checkedFactionOptions = Array.from(modalOrderSendForm.querySelectorAll('input[name="faction_ids[]"]:checked'));
            const checkedRoleOptions = Array.from(modalOrderSendForm.querySelectorAll('input[name="role_ids[]"]:checked'));
            const checkedPersonOptions = Array.from(modalOrderSendForm.querySelectorAll('input[name="person_ids[]"]:checked'));
            const checkedPersonSources = new Set(
                checkedPersonOptions
                .map((input) => String(input.value || '').trim())
                .filter((value) => value !== '')
            );
            const checkedOptions = [...checkedFactionOptions, ...checkedRoleOptions, ...checkedPersonOptions];
            const recipients = new Set();

            checkedOptions.forEach((option) => {
                const memberAttr = String(option.getAttribute('data-member-pids') || '').trim();
                if (memberAttr === '') {
                    return;
                }
                memberAttr.split(',').map((pid) => pid.trim()).filter((pid) => pid !== '').forEach((pid) => recipients.add(pid));
            });

            return {
                selectedSources: checkedFactionOptions.length + checkedRoleOptions.length + checkedPersonSources.size,
                uniqueRecipients: recipients.size,
            };
        };

        const refreshRecipientSummary = () => {
            const summary = collectRecipientSummary();
            return summary;
        };

        const renderRecipients = () => {
            if (!modalOrderSendRecipientTableBody || !modalOrderSendForm) return;
            modalOrderSendRecipientTableBody.innerHTML = '';
            const checkedGroups = Array.from(modalOrderSendForm.querySelectorAll('.group-item-checkbox:checked'));
            const checkedMembers = Array.from(modalOrderSendForm.querySelectorAll('.member-checkbox:checked'));

            if (checkedGroups.length === 0 && checkedMembers.length === 0) {
                const row = document.createElement('tr');
                row.innerHTML = '<td colspan="3" style="text-align:center; padding: 16px;">ไม่มีผู้รับที่เลือก</td>';
                modalOrderSendRecipientTableBody.appendChild(row);
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
                row.innerHTML = `<td>${index + 1}</td><td>${escapeHtml(recipient.name)}</td><td>${escapeHtml(recipient.faction)}</td>`;
                modalOrderSendRecipientTableBody.appendChild(row);
            });
        };

        const closeOrderSendModal = () => {
            if (!orderSendModal) {
                return;
            }
            orderSendModal.style.display = 'none';
            modalOrderSendRecipientModal?.classList.remove('active');
        };

        const openOrderSendModal = (orderIdRaw) => {
            if (!orderSendModal) {
                return;
            }

            syncOrderSendModalData();

            const orderId = String(orderIdRaw || '').trim();
            if (orderId === '') {
                return;
            }

            const payload = orderSendModalData[orderId];
            if (!payload || typeof payload !== 'object') {
                return;
            }

            const orderNo = String(payload.orderNo || '').trim();
            const subject = String(payload.subject || '').trim();
            const effectiveDate = String(payload.effectiveDate || '').trim();
            const orderDate = String(payload.orderDate || '').trim();
            const issuerName = String(payload.issuerName || '').trim();
            const groupName = String(payload.groupName || '').trim();
            const attachments = Array.isArray(payload.attachments) ? payload.attachments : [];
            const status = String(payload.status || '').trim().toUpperCase();
            const readStats = Array.isArray(payload.readStats) ? payload.readStats : [];
            const readTotal = Number.isFinite(Number(payload.readTotal)) ? Number(payload.readTotal) : readStats.length;
            const readDone = Number.isFinite(Number(payload.readDone)) ? Number(payload.readDone) : readStats.filter((row) => Number(row.isRead) === 1).length;
            const canRecall = Number(payload.canRecall) === 1 || payload.canRecall === true;

            if (modalOrderSendNo) {
                modalOrderSendNo.value = orderNo !== '' ? orderNo : '-';
            }
            if (modalOrderSendSubject) {
                modalOrderSendSubject.value = subject !== '' ? subject : '-';
            }
            if (modalOrderSendEffectiveDate) {
                modalOrderSendEffectiveDate.value = /^\d{4}-\d{2}-\d{2}$/.test(effectiveDate) ? effectiveDate : '';
            }
            if (modalOrderSendDate) {
                modalOrderSendDate.value = /^\d{4}-\d{2}-\d{2}$/.test(orderDate) ? orderDate : '';
            }
            if (modalOrderSendIssuer) {
                modalOrderSendIssuer.value = issuerName !== '' ? issuerName : '-';
            }
            if (modalOrderSendGroup) {
                modalOrderSendGroup.value = groupName !== '' ? groupName : '-';
            }
            renderOrderSendFiles(orderId, attachments);

            if (modalOrderSendOrderId) {
                modalOrderSendOrderId.value = orderId;
            }
            if (modalOrderRecallOrderId) {
                modalOrderRecallOrderId.value = orderId;
            }

            const isSent = status === 'SENT';
            if (modalOrderSendTitle) {
                modalOrderSendTitle.textContent = isSent ? 'ติดตามการส่งคำสั่งราชการ' : 'ส่งคำสั่งราชการ';
            }
            syncOrderSendRecipientState();

            if (modalOrderSendFormSection) {
                modalOrderSendFormSection.style.display = isSent ? 'none' : '';
            }
            if (modalOrderTrackSection) {
                modalOrderTrackSection.style.display = isSent ? '' : 'none';
            }

            if (isSent) {
                if (modalOrderTrackTotal) {
                    modalOrderTrackTotal.textContent = String(readTotal);
                }
                if (modalOrderTrackRead) {
                    modalOrderTrackRead.textContent = String(readDone);
                }
                if (modalOrderTrackBody) {
                    if (readStats.length <= 0) {
                        modalOrderTrackBody.innerHTML = '<tr><td colspan="3" class="orders-send-track-empty">ไม่พบข้อมูลผู้รับ</td></tr>';
                    } else {
                        const rowsHtml = readStats.map((row) => {
                            const name = escapeHtml(row.name || '-');
                            const isRead = Number(row.isRead) === 1;
                            const readAtValue = isRead && String(row.readAt || '').trim() !== '' ? String(row.readAt) : '-';
                            const readAt = escapeHtml(readAtValue);
                            const pill = `<span class="status-pill ${isRead ? 'approved' : 'pending'}">${isRead ? 'อ่านแล้ว' : 'ยังไม่อ่าน'}</span>`;
                            return `<tr><td>${name}</td><td>${pill}</td><td>${readAt}</td></tr>`;
                        }).join('');
                        modalOrderTrackBody.innerHTML = rowsHtml;
                    }
                }
                if (modalOrderRecallForm) {
                    modalOrderRecallForm.style.display = canRecall ? '' : 'none';
                }
                if (modalOrderRecallButton) {
                    modalOrderRecallButton.disabled = !canRecall;
                }
                if (modalOrderTrackRecallLocked) {
                    modalOrderTrackRecallLocked.style.display = canRecall ? 'none' : '';
                }
            } else {
                refreshRecipientSummary();
            }

            orderSendModal.style.display = 'flex';
        };

        syncOrderSendModalData();
        const syncOrderSendRecipientState = setupOrderSendRecipientDropdown();
        refreshRecipientSummary();

        modalOrderSendForm?.addEventListener('change', (event) => {
            const target = event.target;
            if (!(target instanceof HTMLInputElement)) {
                return;
            }
            if (!target.matches('input[data-recipient-option]')) {
                return;
            }
            refreshRecipientSummary();
        });

        modalOrderSendForm?.addEventListener('submit', (event) => {
            const summary = refreshRecipientSummary();
            if (summary.uniqueRecipients <= 0) {
                event.preventDefault();
            }
        });

        closeOrderSendModalBtn?.addEventListener('click', () => {
            closeOrderSendModal();
        });

        modalOrderSendBtnShowRecipients?.addEventListener('click', () => {
            renderRecipients();
            modalOrderSendRecipientModal?.classList.add('active');
        });

        modalOrderSendRecipientClose?.addEventListener('click', () => {
            modalOrderSendRecipientModal?.classList.remove('active');
        });

        modalOrderSendRecipientModal?.addEventListener('click', (event) => {
            if (event.target === modalOrderSendRecipientModal) {
                modalOrderSendRecipientModal.classList.remove('active');
            }
        });

        const openOrderEditModal = (trigger) => {
            if (!orderEditModal) return;

            const orderId = String(trigger.getAttribute('data-order-id') || '').trim();
            const orderNo = String(trigger.getAttribute('data-order-no') || '').trim();
            const orderSubject = String(trigger.getAttribute('data-order-subject') || '').trim();
            const orderIssuer = String(trigger.getAttribute('data-order-issuer') || '').trim();
            const orderDateRaw = String(trigger.getAttribute('data-order-date-raw') || '').trim();
            const orderEffectiveDateRaw = String(trigger.getAttribute('data-order-effective-date-raw') || '').trim();
            const orderGroupFid = String(trigger.getAttribute('data-order-group-fid') || '').trim();
            const orderFiles = String(trigger.getAttribute('data-order-files') || '[]');

            if (modalOrderId) modalOrderId.value = orderId;
            if (modalOrderNo) modalOrderNo.value = orderNo !== '' ? orderNo : '-';
            if (modalOrderSubject) modalOrderSubject.value = orderSubject !== '' ? orderSubject : '';
            if (modalOrderEffectiveDate) modalOrderEffectiveDate.value = /^\d{4}-\d{2}-\d{2}$/.test(orderEffectiveDateRaw) ? orderEffectiveDateRaw : '';
            if (modalOrderDate) modalOrderDate.value = /^\d{4}-\d{2}-\d{2}$/.test(orderDateRaw) ? orderDateRaw : '';
            if (modalOrderIssuer) modalOrderIssuer.value = orderIssuer !== '' ? orderIssuer : '-';
            syncModalGroupSelect(orderGroupFid);
            renderExistingOrderFiles(orderId, orderFiles);

            modalAttachmentUpload?.reset?.();

            orderEditModal.style.display = 'flex';
        };

        document.addEventListener('click', (event) => {
            const trigger = event.target instanceof Element ? event.target.closest('.js-open-order-edit-modal') : null;

            if (!trigger) {
                return;
            }

            event.preventDefault();
            openOrderEditModal(trigger);
        });

        closeOrderEditModalBtn?.addEventListener('click', () => {
            if (orderEditModal) {
                orderEditModal.style.display = 'none';
            }
        });

        document.addEventListener('click', (event) => {
            const trigger = event.target instanceof Element ? event.target.closest('.js-open-order-send-modal') : null;
            if (!trigger) {
                return;
            }
            event.preventDefault();
            const orderId = String(trigger.getAttribute('data-order-id') || '').trim();
            openOrderSendModal(orderId);
        });

        window.addEventListener('click', (event) => {
            if (event.target === orderEditModal) {
                orderEditModal.style.display = 'none';
            }
            if (event.target === orderSendModal) {
                closeOrderSendModal();
            }
        });

        if (initialSendModalOrderId > 0) {
            openOrderSendModal(String(initialSendModalOrderId));
        }

        const trackFilterForm = document.querySelector('#orderMine form.circular-my-filter-grid');
        const trackTableWrap = document.querySelector('#orderMine .table-responsive.circular-my-table-wrap');

        if (trackFilterForm && trackTableWrap) {
            const queryInput = trackFilterForm.querySelector('input[name="q"]');
            const statusInput = trackFilterForm.querySelector('select[name="status"]');
            const sortInput = trackFilterForm.querySelector('select[name="sort"]');
            let debounceTimer = null;
            let activeController = null;
            let latestRequestId = 0;

            const buildTrackUrl = () => {
                const params = new URLSearchParams(new FormData(trackFilterForm));

                return `${window.location.pathname}?${params.toString()}`;
            };

            const refreshTrackTable = (delayMs = 0) => {
                window.clearTimeout(debounceTimer);
                debounceTimer = window.setTimeout(async () => {
                    const requestUrl = buildTrackUrl();
                    window.history.replaceState({}, '', requestUrl);

                    if (activeController) {
                        activeController.abort();
                    }

                    const controller = new AbortController();
                    activeController = controller;
                    const requestId = ++latestRequestId;

                    trackTableWrap.style.opacity = '0.55';
                    trackTableWrap.style.pointerEvents = 'none';

                    try {
                        const response = await fetch(requestUrl, {
                            method: 'GET',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin',
                            signal: controller.signal,
                        });

                        if (!response.ok) {
                            throw new Error(`HTTP ${response.status}`);
                        }

                        const html = await response.text();

                        if (requestId !== latestRequestId) {
                            return;
                        }

                        const doc = new DOMParser().parseFromString(html, 'text/html');
                        const nextTableWrap = doc.querySelector('#orderMine .table-responsive.circular-my-table-wrap');

                        if (nextTableWrap) {
                            trackTableWrap.innerHTML = nextTableWrap.innerHTML;
                            syncOrderSendModalData();
                        }
                    } catch (error) {
                        if (error && error.name !== 'AbortError') {
                            console.error('Failed to refresh order list:', error);
                        }
                    } finally {
                        if (requestId === latestRequestId) {
                            trackTableWrap.style.opacity = '';
                            trackTableWrap.style.pointerEvents = '';
                        }
                    }
                }, delayMs);
            };

            trackFilterForm.addEventListener('submit', (event) => {
                event.preventDefault();
                refreshTrackTable(0);
            });

            if (queryInput) {
                queryInput.addEventListener('input', () => {
                    refreshTrackTable(280);
                });
                queryInput.addEventListener('search', () => {
                    refreshTrackTable(0);
                });
            }

            if (statusInput) {
                statusInput.addEventListener('change', () => {
                    refreshTrackTable(0);
                });
            }

            if (sortInput) {
                sortInput.addEventListener('change', () => {
                    refreshTrackTable(0);
                });
            }
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        if (window.__ordersCreateModalFallbackBound) {
            return;
        }
        window.__ordersCreateModalFallbackBound = true;

        const editModal = document.getElementById('modalOrderEditOverlay');
        const sendModal = document.getElementById('modalOrderSendOverlay');
        const viewModal = document.getElementById('modalOrderViewOverlay')
        const closeEdit = document.getElementById('closeModalOrderEdit');
        const closeSend = document.getElementById('closeModalOrderSend');
        const closeView = document.getElementById('closeModalOrderView')

        const setValue = (id, value) => {
            const el = document.getElementById(id);
            if (!el) return;
            el.value = value ?? '';
        };

        const parseSendPayload = (orderId) => {
            const mapEl = document.querySelector('#orderMine .js-order-send-map');
            if (!mapEl) return null;
            try {
                const parsed = JSON.parse(mapEl.textContent || '{}');
                if (!parsed || typeof parsed !== 'object') return null;
                return parsed[String(orderId)] || null;
            } catch (error) {
                return null;
            }
        };

        const openEditFallback = (trigger) => {
            if (!editModal || !trigger) return;
            setValue('modalOrderId', String(trigger.getAttribute('data-order-id') || '').trim());
            setValue('modalOrderNo', String(trigger.getAttribute('data-order-no') || '').trim() || '-');
            setValue('modalOrderSubject', String(trigger.getAttribute('data-order-subject') || '').trim());
            setValue('modalOrderEffectiveDate', String(trigger.getAttribute('data-order-effective-date-raw') || '').trim());
            setValue('modalOrderDate', String(trigger.getAttribute('data-order-date-raw') || '').trim());
            setValue('modalOrderIssuer', String(trigger.getAttribute('data-order-issuer') || '').trim() || '-');
            editModal.style.display = 'flex';
        };

        const openSendFallback = (trigger) => {
            if (!sendModal || !trigger) return;
            const orderId = String(trigger.getAttribute('data-order-id') || '').trim();
            const payload = parseSendPayload(orderId);
            if (payload && typeof payload === 'object') {
                setValue('modalOrderSendOrderId', orderId);
                setValue('modalOrderRecallOrderId', orderId);
                setValue('modalOrderSendNo', String(payload.orderNo || '').trim() || '-');
                setValue('modalOrderSendSubject', String(payload.subject || '').trim() || '-');
                setValue('modalOrderSendEffectiveDate', String(payload.effectiveDate || '').trim());
                setValue('modalOrderSendDate', String(payload.orderDate || '').trim());
                setValue('modalOrderSendIssuer', String(payload.issuerName || '').trim() || '-');
                setValue('modalOrderSendGroup', String(payload.groupName || '').trim() || '-');

                const status = String(payload.status || '').trim().toUpperCase();
                const isSent = status === 'SENT';
                const title = document.getElementById('modalOrderSendTitle');
                const formSection = document.getElementById('modalOrderSendFormSection');
                const trackSection = document.getElementById('modalOrderTrackSection');
                if (title) title.textContent = isSent ? 'ติดตามการส่งคำสั่งราชการ' : 'ส่งคำสั่งราชการ';
                if (formSection) formSection.style.display = isSent ? 'none' : '';
                if (trackSection) trackSection.style.display = isSent ? '' : 'none';
            }
            sendModal.style.display = 'flex';
        };

        closeEdit?.addEventListener('click', () => {
            if (editModal) editModal.style.display = 'none';
        });
        closeSend?.addEventListener('click', () => {
            if (sendModal) sendModal.style.display = 'none';
        });
        closeView?.addEventListener('click', () => {
            if (viewModal) viewModal.style.display = 'none';
        });

        window.addEventListener('click', (event) => {
            if (event.target === editModal) {
                editModal.style.display = 'none';
            }
            if (event.target === sendModal) {
                sendModal.style.display = 'none';
            }
            if (event.target === viewModal) {
                viewModal.style.display = 'none';
            }
        });

        document.addEventListener('click', (event) => {
            const target = event.target instanceof Element ? event.target : null;
            if (!target) return;

            const editTrigger = target.closest('.js-open-order-edit-modal');
            if (editTrigger) {
                window.setTimeout(() => {
                    if (editModal && editModal.style.display !== 'flex') {
                        openEditFallback(editTrigger);
                    }
                }, 0);
            }

            const sendTrigger = target.closest('.js-open-order-send-modal');
            if (sendTrigger) {
                window.setTimeout(() => {
                    if (sendModal && sendModal.style.display !== 'flex') {
                        openSendFallback(sendTrigger);
                    }
                }, 0);
            }
            const viewTrigger = target.closest('.js-open-order-view-modal');
            if (viewTrigger) {
                window.setTimeout(() => {
                    if (viewModal && viewModal.style.display !== 'flex') {
                        viewModal.style.display = 'flex';
                    }
                }, 0);
            }
        }, true);
    });
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';

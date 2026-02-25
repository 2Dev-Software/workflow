<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../auth/csrf.php';

$values = $values ?? ['writeDate' => '', 'subject' => '', 'detail' => ''];
$current_user = (array) ($current_user ?? []);
$factions = (array) ($factions ?? []);

$selected_sender_fid = trim((string) ($values['sender_fid'] ?? ''));

if ($selected_sender_fid === '' && !empty($factions)) {
    $selected_sender_fid = (string) ($factions[0]['fID'] ?? '');
}

$signature_src = trim((string) ($current_user['signature'] ?? ''));
$current_name = trim((string) ($current_user['fName'] ?? ''));
$current_position = trim((string) ($current_user['position_name'] ?? ''));

ob_start();
?>
<style>
    .content-memo .memo-detail {
        --memo-label-width: 56px;
    }

    .content-memo .memo-detail .form-group-row.memo-subject-row {
        gap: 10px;
    }

    .content-memo .memo-detail .form-group-row.memo-to-row {
        gap: 10px;
    }

    .content-memo .memo-detail .form-group-row.memo-subject-row>p:first-child,
    .content-memo .memo-detail .form-group-row.memo-to-row>p:first-child {
        width: var(--memo-label-width);
        min-width: var(--memo-label-width);
    }

    .content-memo .memo-detail .form-group-row.memo-subject-row input[name="subject"] {
        flex: 1 1 auto;
        min-width: 0;
    }

    .circular-my-filter-grid {
        display: flex;
        gap: 20px;
        align-items: center;
        flex-wrap: wrap;
        flex-direction: row;
        margin: 0 0 40px;
    }
</style>
<div class="content-header">
    <h1>ยินดีต้อนรับ</h1>
    <p>บันทึกข้อความ</p>
</div>

<div class="tabs-container setting-page">
    <div class="button-container vehicle">
        <button class="tab-btn <?= $is_track_active ? '' : 'active' ?>"
            onclick="openTab('memoBook', event)">บันทึกข้อความ</button>
        <button class="tab-btn <?= $is_track_active ? 'active' : '' ?>" onclick="openTab('memoMine', event)">บันทึกข้อความของฉัน</button>
    </div>
</div>

<div class="content-memo tab-content active" id="memoBook">
    <div class="memo-header">
        <img src="assets/img/garuda-logo.png" alt="">
        <p>บันทึกข้อความ</p>
        <div></div>
    </div>

    <form method="POST" id="circularComposeForm">
        <?= csrf_field() ?>
        <input type="hidden" name="flow_mode" value="CHAIN">
        <input type="hidden" name="to_choice" value="DIRECTOR">

        <div class="memo-detail">
            <div class="form-group-row">
                <p><strong>ส่วนราชการ</strong></p>

                <div class="custom-select-wrapper">
                    <div class="custom-select-trigger">
                        <p class="select-value">
                            <?php
                            $selected_faction_name = '';

                            foreach ($factions as $faction) {
                                if ((string) ($faction['fID'] ?? '') === $selected_sender_fid) {
                                    $selected_faction_name = (string) ($faction['fname'] ?? '');
                                    break;
                                }
                            }
                            echo h($selected_faction_name !== '' ? $selected_faction_name : 'เลือกส่วนราชการ');
                            ?>
                        </p>
                        <i class="fa-solid fa-chevron-down"></i>
                    </div>

                    <div class="custom-options">
                        <?php foreach ($factions as $faction) : ?>
                            <?php $fid = (string) ($faction['fID'] ?? ''); ?>
                            <div class="custom-option<?= $fid === $selected_sender_fid ? ' selected' : '' ?>" data-value="<?= h($fid) ?>">
                                <?= h((string) ($faction['fname'] ?? '')) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <input type="hidden" name="sender_fid" value="<?= h($selected_sender_fid) ?>">
                </div>

                <p><strong>โรงเรียนดีบุกพังงาวิทยายน</strong></p>
            </div>

            <div class="form-group-row memo-subject-row">
                <p><strong>เรื่อง</strong></p>
                <input type="text" name="subject" value="<?= h((string) ($values['subject'] ?? '')) ?>" required>
            </div>

            <div class="form-group-row memo-to-row">
                <p><strong>เรียน</strong></p>
                <p>ผู้อำนวยการโรงเรียนดีบุกพังงาวิทยายน</p>
            </div>

            <div class="content-editor">
                <p><strong>รายละเอียด:</strong></p>
                <textarea name="detail" id="memo_editor"><?= h((string) ($values['detail'] ?? '')) ?></textarea>
            </div>

            <div class="form-group-row signature">
                <img src="<?= h($signature_src) ?>" alt="">
                <p>(<?= h($current_name !== '' ? $current_name : '-') ?>)</p>
                <p><?= h($current_position !== '' ? $current_position : '-') ?></p>
            </div>

            <div class="form-group-row submit">
                <button type="submit">บันทึกเอกสาร</button>
            </div>
        </div>
    </form>
</div>

<div class="content-my-memo enterprise-card tab-content" id="memoMine">

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
                <input class="form-input" type="search" name="q" value=""
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
            <h2 class="enterprise-card-title">รายการบันทึกข้อความ</h2>
        </div>
    </div>

    <div class="table-responsive">
        <table class="custom-table booking-table memo-mine-table">
            <thead>
                <tr>
                    <th>เรื่อง</th>
                    <th>สถานะ</th>
                    <th>วัันที่ส่ง</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <p>loremmmmmmm</p>
                    </td>
                    <td>
                        <span class="status-pill approved">
                            อนุมัติการจองสำเร็จ </span>
                    </td>
                    <td>
                        9 กุมภาพันธ์ 2569<br>
                        <span class="detail-subtext">22:31</span>
                    </td>
                    <td>
                        <button type="button" class="booking-action-btn secondary js-open-view-modal" data-vehicle-approval-action="detail" data-vehicle-booking-action="detail" data-vehicle-booking-id="4">

                            <i class="fa-solid fa-eye" aria-hidden="true"></i>
                            <span class="tooltip">ดูรายละเอียด</span>
                        </button>

                        <button type="button" class="booking-action-btn secondary js-open-edit-modal" data-vehicle-approval-action="detail" data-vehicle-booking-action="detail" data-vehicle-booking-id="4">

                            <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                            <span class="tooltip">ดู/แก้ไข</span>
                        </button>

                        <a href="public/api/vehicle-booking-pdf.php?booking_id=4&amp;v=1771388613" class="booking-action-btn secondary" target="_blank" rel="noopener">
                            <i class="fa-solid fa-file-pdf" aria-hidden="true"></i>
                            <span class="tooltip">ดาวน์โหลดไฟล์</span>
                        </a>

                        <button type="button" class="booking-action-btn secondary js-open-suggest-modal" data-vehicle-approval-action="detail" data-vehicle-booking-action="detail" data-vehicle-booking-id="4">

                            <i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i>
                            <span class="tooltip">เสนอแฟ้ม</span>
                        </button>

                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<div class="modal-overlay-memo details" id="modalViewOverlay" style="display: none;">
    <div class="modal-content">
        <div class="header-modal">
            <p id="modalTypeLabel">รายละเอียด</p>
            <i class="fa-solid fa-xmark" id="closeModalView" aria-hidden="true"></i>
        </div>

        <div class="content-modal">

        </div>

    </div>

    <!-- <div class="footer-modal">
                    <form method="POST" id="modalArchiveForm">
                        <input type="hidden" name="csrf_token" value="3ece51cef25df8dcbb025b7f59af78f9d7fa9c90963b44be41d39e6d5152a6ac">                        <input type="hidden" name="inbox_id" id="modalInboxId" value="10">
                        <input type="hidden" name="action" value="archive">
                        <button type="submit">
                            <p>ย้ายกลับ</p>
                        </button>
                    </form>
                </div> -->

</div>

<div class="modal-overlay-memo details" id="modalEditOverlay" style="display: none;">
    <div class="modal-content">
        <div class="header-modal">
            <p id="modalTypeLabel">รายละเอียด</p>
            <i class="fa-solid fa-xmark" id="closeModalEdit" aria-hidden="true"></i>
        </div>

        <div class="content-modal">

            <div class="content-memo" style="box-shadow: none;">
                <div class="memo-header">
                    <img src="assets/img/garuda-logo.png" alt="">
                    <p>บันทึกข้อความ</p>
                    <div></div>
                </div>

                <form method="POST" id="circularComposeForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="flow_mode" value="CHAIN">
                    <input type="hidden" name="to_choice" value="DIRECTOR">

                    <div class="memo-detail">
                        <div class="form-group-row">
                            <p><strong>ส่วนราชการ</strong></p>

                            <div class="custom-select-wrapper">
                                <div class="custom-select-trigger">
                                    <p class="select-value">
                                        <?php
                                        $selected_faction_name = '';

                                        foreach ($factions as $faction) {
                                            if ((string) ($faction['fID'] ?? '') === $selected_sender_fid) {
                                                $selected_faction_name = (string) ($faction['fname'] ?? '');
                                                break;
                                            }
                                        }
                                        echo h($selected_faction_name !== '' ? $selected_faction_name : 'เลือกส่วนราชการ');
                                        ?>
                                    </p>
                                    <i class="fa-solid fa-chevron-down"></i>
                                </div>

                                <div class="custom-options">
                                    <?php foreach ($factions as $faction) : ?>
                                        <?php $fid = (string) ($faction['fID'] ?? ''); ?>
                                        <div class="custom-option<?= $fid === $selected_sender_fid ? ' selected' : '' ?>" data-value="<?= h($fid) ?>">
                                            <?= h((string) ($faction['fname'] ?? '')) ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <input type="hidden" name="sender_fid" value="<?= h($selected_sender_fid) ?>">
                            </div>

                            <p><strong>โรงเรียนดีบุกพังงาวิทยายน</strong></p>
                        </div>

                        <div class="form-group-row memo-subject-row">
                            <p><strong>เรื่อง</strong></p>
                            <input type="text" name="subject" value="<?= h((string) ($values['subject'] ?? '')) ?>" required>
                        </div>

                        <div class="form-group-row memo-to-row">
                            <p><strong>เรียน</strong></p>
                            <p>ผู้อำนวยการโรงเรียนดีบุกพังงาวิทยายน</p>
                        </div>

                        <div class="content-editor">
                            <p><strong>รายละเอียด:</strong></p>
                            <br>
                            <textarea name="detail" id="memo_editor"><?= h((string) ($values['detail'] ?? '')) ?></textarea>
                        </div>

                        <div class="form-group-row signature">
                            <img src="<?= h($signature_src) ?>" alt="">
                            <p>(<?= h($current_name !== '' ? $current_name : '-') ?>)</p>
                            <p><?= h($current_position !== '' ? $current_position : '-') ?></p>
                        </div>

                        <!-- <div class="form-group-row submit">
                            <button type="submit">บันทึกเอกสาร</button>
                        </div> -->
                    </div>
                </form>
            </div>

        </div>

        <div class="footer-modal">
            <form method="POST" id="modalArchiveForm">
                <input type="hidden" name="csrf_token" value="3ece51cef25df8dcbb025b7f59af78f9d7fa9c90963b44be41d39e6d5152a6ac"> <input type="hidden" name="inbox_id" id="modalInboxId" value="10">
                <input type="hidden" name="action" value="archive">
                <button type="submit">
                    <p>เสนอแฟ้ม</p>
                </button>
            </form>
        </div>

    </div>
</div>

<div class="modal-overlay-memo suggest" id="modalSuggOverlay" style="display: none;">
    <div class="modal-content">
        <div class="header-modal">
            <p id="modalTypeLabel">รายละเอียด</p>
            <i class="fa-solid fa-xmark" id="closeModalSugg" aria-hidden="true"></i>
        </div>

        <div class="content-modal">

            <div class="content-memo">
                <div class="memo-header">
                    <img src="assets/img/garuda-logo.png" alt="">
                    <p>บันทึกข้อความ</p>
                    <div></div>
                </div>

                <form method="POST" id="circularComposeForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="flow_mode" value="CHAIN">
                    <input type="hidden" name="to_choice" value="DIRECTOR">

                    <div class="memo-detail">
                        <div class="form-group-row">
                            <p><strong>ส่วนราชการ</strong></p>

                            <div class="custom-select-wrapper">
                                <div class="custom-select-trigger">
                                    <p class="select-value">
                                        <?php
                                        $selected_faction_name = '';

                                        foreach ($factions as $faction) {
                                            if ((string) ($faction['fID'] ?? '') === $selected_sender_fid) {
                                                $selected_faction_name = (string) ($faction['fname'] ?? '');
                                                break;
                                            }
                                        }
                                        echo h($selected_faction_name !== '' ? $selected_faction_name : 'เลือกส่วนราชการ');
                                        ?>
                                    </p>
                                    <i class="fa-solid fa-chevron-down"></i>
                                </div>

                                <div class="custom-options">
                                    <?php foreach ($factions as $faction) : ?>
                                        <?php $fid = (string) ($faction['fID'] ?? ''); ?>
                                        <div class="custom-option<?= $fid === $selected_sender_fid ? ' selected' : '' ?>" data-value="<?= h($fid) ?>">
                                            <?= h((string) ($faction['fname'] ?? '')) ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <input type="hidden" name="sender_fid" value="<?= h($selected_sender_fid) ?>">
                            </div>

                            <p><strong>โรงเรียนดีบุกพังงาวิทยายน</strong></p>
                        </div>

                        <div class="form-group-row memo-subject-row">
                            <p><strong>เรื่อง</strong></p>
                            <input type="text" name="subject" value="<?= h((string) ($values['subject'] ?? '')) ?>" required>
                        </div>

                        <div class="form-group-row memo-to-row">
                            <p><strong>เรียน</strong></p>
                            <p>ผู้อำนวยการโรงเรียนดีบุกพังงาวิทยายน</p>
                        </div>

                        <div class="content-editor">
                            <p><strong>รายละเอียด:</strong></p>
                            <br>
                            <textarea name="detail" id="memo_editor"><?= h((string) ($values['detail'] ?? '')) ?></textarea>
                        </div>


                        <div class="memo-file-row file-sec">
                            <div class="memo-input-content">
                                <label>แนบเอกสาร <strong>(แนบเอกสารได้สูงสุด 5 ไฟล์)</strong></label>
                                <div>
                                    <button type="button" class="btn btn-upload-small" onclick="document.getElementById('attachment').click()">
                                        <p>เพิ่มไฟล์</p>
                                    </button>
                                </div>
                                <input type="file" id="attachment" name="attachments[]" class="file-input" multiple="" accept=".pdf,image/png,image/jpeg" hidden="">
                                <p class="form-error hidden" id="attachmentError">แนบได้สูงสุด 5 ไฟล์</p>
                            </div>

                            <div class="file-list" id="attachmentList" aria-live="polite">
                                <p class="attachment-empty">ยังไม่มีไฟล์แนบ</p>
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

                        <div class="form-group-row signature">
                            <img src="<?= h($signature_src) ?>" alt="">
                            <p>(<?= h($current_name !== '' ? $current_name : '-') ?>)</p>
                            <p><?= h($current_position !== '' ? $current_position : '-') ?></p>
                        </div>

                        <!-- <div class="form-group-row submit">
                            <button type="submit">บันทึกเอกสาร</button>
                        </div> -->
                    </div>
                </form>
            </div>

        </div>

        <div class="footer-modal">
            <form method="POST" id="modalArchiveForm">
                <input type="hidden" name="csrf_token" value="3ece51cef25df8dcbb025b7f59af78f9d7fa9c90963b44be41d39e6d5152a6ac"> <input type="hidden" name="inbox_id" id="modalInboxId" value="10">
                <input type="hidden" name="action" value="archive">
                <button type="submit">
                    <p>เสนอแฟ้ม</p>
                </button>
            </form>
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

<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js"></script>
<script>
    tinymce.init({
        selector: '#memo_editor',
        height: 500,
        menubar: false,
        language: 'th_TH',
        plugins: 'searchreplace autolink directionality visualblocks visualchars image link media codesample table charmap pagebreak nonbreaking anchor insertdatetime advlist lists wordcount help charmap emoticons',
        toolbar: 'undo redo | fontfamily | fontsize | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | outdent indent |  numlist bullist | forecolor backcolor removeformat | pagebreak | charmap emoticons',
        font_family_formats: 'TH Sarabun New=Sarabun, sans-serif;',
        font_size_formats: '8pt 9pt 10pt 12pt 14pt 16pt 18pt 20pt 22pt 24pt 26pt 36pt 48pt 72pt',
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
        branding: false
    });

    document.addEventListener('DOMContentLoaded', function() {
        return;
    });

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
        const openDetailBtns = document.querySelectorAll('.js-open-view-modal');
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



    const viewModal = document.getElementById('modalViewOverlay');
    const editModal = document.getElementById('modalEditOverlay');
    const suggModal = document.getElementById('modalSuggOverlay');

    const closeViewBtn = document.getElementById('closeModalView');
    const closeEditBtn = document.getElementById('closeModalEdit');
    const closeSuggBtn = document.getElementById('closeModalSugg');

    const openViewBtns = document.querySelectorAll('.js-open-view-modal');
    const openEditBtns = document.querySelectorAll('.js-open-edit-modal');
    const openSuggBtns = document.querySelectorAll('.js-open-suggest-modal');

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

    openSuggBtns.forEach((btn) => {
        btn.addEventListener('click', (even) => {
            event.preventDefault();

            if (suggModal) suggModal.style.display = 'flex';
        })
    })

    closeViewBtn?.addEventListener('click', () => {
        if (viewModal) viewModal.style.display = 'none';
    });

    closeEditBtn?.addEventListener('click', () => {
        if (editModal) editModal.style.display = 'none';
    });

    closeSuggBtn?.addEventListener('click', () => {
        if (suggModal) suggModal.style.display = 'none';
    })

    window.addEventListener('click', (event) => {
        if (event.target === viewModal) {
            viewModal.style.display = 'none';
        }
        if (event.target === editModal) {
            editModal.style.display = 'none';
        }
        if (event.target === suggModal) {
            suggModal.style.display = 'none';
        }
    });


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

</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';

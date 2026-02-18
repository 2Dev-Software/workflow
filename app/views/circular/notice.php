<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../auth/csrf.php';
require_once __DIR__ . '/../view.php';

$items = (array) ($items ?? []);
$box_key = (string) ($box_key ?? 'normal');
$archived = (bool) ($archived ?? false);
$filter_type = (string) ($filter_type ?? 'all');
$filter_read = (string) ($filter_read ?? 'all');
$filter_sort = (string) ($filter_sort ?? 'newest');
$filter_view = (string) ($filter_view ?? 'table1');
$filter_search = (string) ($filter_search ?? '');
$is_outside_view = (bool) ($is_outside_view ?? false);
$director_label = (string) ($director_label ?? 'ผอ./รักษาการ');

ob_start();
?>

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

$sender_factions = [];

foreach ($factions as $faction) {
    $fid = (int) ($faction['fID'] ?? 0);
    $faction_name = trim((string) ($faction['fName'] ?? ''));

    if ($fid <= 0 || $faction_name === '') {
        continue;
    }
    $normalized_faction_name = preg_replace('/\s+/u', '', $faction_name);

    if (strpos((string) $normalized_faction_name, 'ฝ่ายบริหาร') !== false) {
        continue;
    }
    $sender_factions[] = [
        'fID' => $fid,
        'fName' => $faction_name,
    ];
}

ob_start();
?>
<style>
    .circular-action-stack {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .circular-action-stack .button-more-details,
    .circular-action-stack .button-open-workflow {
        min-width: 92px;
    }

    .circular-action-stack .button-open-workflow {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        height: 32px;
        border: 1px solid rgba(var(--rgb-secondary), 0.4);
        border-radius: 8px;
        background: #fff;
        color: var(--color-secondary);
        text-decoration: none;
        font-size: 13px;
        font-weight: 700;
        padding: 0 10px;
        line-height: 1;
    }

    .circular-action-stack .button-open-workflow:hover {
        background: rgba(var(--rgb-secondary), 0.08);
    }

    .circular-sender-stack {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 2px;
        line-height: 1.25;
    }

    .circular-sender-name {
        font-weight: 700;
        color: var(--color-primary-dark);
    }

    .circular-sender-faction {
        font-size: 12px;
        color: #111111;
    }

    #modalSender {
        white-space: pre-line;
    }

    .content-circular-notice-index .modal-overlay-circular-notice-index .modal-content .content-modal .content-topic-sec:nth-child(3) {
        border: none;
        margin: 30px 0 0;
        flex-direction: column;
        align-items: start;
    }

    .content-circular-notice-index .modal-overlay-circular-notice-index.keep-sending .content-modal .content-topic-sec input.subject-disabled-solid[disabled] {
        opacity: 1;
        color: var(--color-secondary);
        -webkit-text-fill-color: var(--color-secondary);
        background-color: rgba(var(--rgb-neutral-medium), 0.25);
        cursor: not-allowed;
    }
</style>
<div class="content-header">
    <h1>ยินดีต้อนรับ</h1>
    <p>หนังสือเวียน / <?= h($archived ? 'หนังสือเวียนที่จัดเก็บ' : 'กล่องข้อความ') ?></p>
</div>

<form id="circularFilterForm" method="GET">
    <input type="hidden" name="box" value="<?= h($box_key) ?>">
    <?php if ($archived) : ?>
        <input type="hidden" name="archived" value="1">
    <?php endif; ?>
    <input type="hidden" name="type" id="filterTypeInput" value="<?= h($filter_type) ?>">
    <input type="hidden" name="read" id="filterReadInput" value="<?= h($filter_read) ?>">
    <input type="hidden" name="sort" id="filterSortInput" value="<?= h($filter_sort) ?>">
    <input type="hidden" name="view" id="filterViewInput" value="<?= h($filter_view) ?>">
</form>
<input type="hidden" id="csrfToken" value="<?= h(csrf_token()) ?>">

<header class="header-circular-notice-index<?= h($is_outside_view ? ' outside-person' : '') ?>">
    <div class="circular-notice-index-control">
        <div class="page-selector">
            <p>แสดงตามประเภทหนังสือ</p>

            <div class="custom-select-wrapper" data-target="filterTypeInput">
                <div class="custom-select-trigger">
                    <p class="select-value"><?= h($filter_type === 'internal' ? 'ภายใน' : ($filter_type === 'external' ? 'ภายนอก' : 'ทั้งหมด')) ?></p>
                    <i class="fa-solid fa-chevron-down"></i>
                </div>

                <div class="custom-options">
                    <div class="custom-option<?= h($filter_type === 'external' ? ' selected' : '') ?>" data-value="external">ภายนอก</div>
                    <div class="custom-option<?= h($filter_type === 'internal' ? ' selected' : '') ?>" data-value="internal">ภายใน</div>
                    <div class="custom-option<?= h($filter_type === 'all' ? ' selected' : '') ?>" data-value="all">ทั้งหมด</div>
                </div>
            </div>
        </div>
        <div class="page-selector">
            <p>แสดงตามสถานะหนังสือ</p>

            <div class="custom-select-wrapper" data-target="filterReadInput">
                <div class="custom-select-trigger">
                    <p class="select-value"><?= h($filter_read === 'read' ? 'อ่านแล้ว' : ($filter_read === 'unread' ? 'ยังไม่อ่าน' : 'ทั้งหมด')) ?></p>
                    <i class="fa-solid fa-chevron-down"></i>
                </div>

                <div class="custom-options">
                    <div class="custom-option<?= h($filter_read === 'read' ? ' selected' : '') ?>" data-value="read">อ่านแล้ว</div>
                    <div class="custom-option<?= h($filter_read === 'unread' ? ' selected' : '') ?>" data-value="unread">ยังไม่อ่าน</div>
                    <div class="custom-option<?= h($filter_read === 'all' ? ' selected' : '') ?>" data-value="all">ทั้งหมด</div>
                </div>
            </div>
        </div>

        <div class="page-selector">
            <p>แสดงตาม</p>

            <div class="custom-select-wrapper" data-target="filterSortInput">
                <div class="custom-select-trigger">
                    <p class="select-value"><?= h($filter_sort === 'oldest' ? 'เก่าไปใหม่' : 'ใหม่ไปเก่า') ?></p>
                    <i class="fa-solid fa-chevron-down"></i>
                </div>

                <div class="custom-options">
                    <div class="custom-option<?= h($filter_sort === 'newest' ? ' selected' : '') ?>" data-value="newest">ใหม่ไปเก่า</div>
                    <div class="custom-option<?= h($filter_sort === 'oldest' ? ' selected' : '') ?>" data-value="oldest">เก่าไปใหม่</div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($is_outside_view) : ?>
        <div class="table-change">
            <p>ตาราง</p>
            <div class="button-table">
                <button class="<?= h($filter_view === 'table1' ? 'active' : '') ?>" type="button" data-view="table1">ตาราง 1</button>
                <button class="<?= h($filter_view === 'table2' ? 'active' : '') ?>" type="button" data-view="table2">ตาราง 2</button>
            </div>
        </div>
    <?php endif; ?>
</header>

<section class="content-circular-notice-index" data-circular-notice>
    <div class="search-bar">
        <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="search-input" name="q" form="circularFilterForm" value="<?= h($filter_search) ?>" placeholder="ค้นหาข้อความด้วย...">
        </div>
    </div>

    <?php if (!$is_outside_view) : ?>
        <form id="bulkActionForm" method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="<?= h($archived ? 'unarchive_selected' : 'archive_selected') ?>">
            <div class="table-circular-notice-index">
                <table>
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" class="check-table checkall" id="checkAllCircular">
                            </th>
                            <th>ประเภทหนังสือ</th>
                            <th>หัวเรื่อง</th>
                            <th>ผู้ส่ง</th>
                            <th>วันที่ส่ง</th>
                            <th>สถานะ</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($items)) : ?>
                            <tr>
                                <td colspan="7" class="enterprise-empty">ไม่มีรายการ</td>
                            </tr>
                        <?php else : ?>
                            <?php foreach ($items as $item) : ?>
                                <?php
                                $is_read = (bool) ($item['is_read'] ?? false);
                                $file_json = (string) ($item['files_json'] ?? '[]');
                                $sender_modal_text = trim((string) ($item['sender_name'] ?? '-'));

                                if (!empty($item['sender_faction_name'])) {
                                    $sender_modal_text .= "\n" . trim((string) $item['sender_faction_name']);
                                }
                                ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" class="check-table" name="selected_ids[]" value="<?= h((string) (int) ($item['inbox_id'] ?? 0)) ?>">
                                    </td>
                                    <td><?= h((string) ($item['type_label'] ?? '')) ?></td>
                                    <td><?= h((string) ($item['subject'] ?? '')) ?></td>
                                    <td>
                                        <div class="circular-sender-stack">
                                            <span class="circular-sender-name"><?= h((string) ($item['sender_name'] ?? '-')) ?></span>
                                            <?php if (!empty($item['sender_faction_name'])) : ?>
                                                <span class="circular-sender-faction"><?= h((string) $item['sender_faction_name']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><?= h((string) ($item['delivered_date'] ?? '-')) ?></td>
                                    <td><span class="status-badge <?= h($is_read ? 'read' : 'unread') ?>"><?= h($is_read ? 'อ่านแล้ว' : 'ยังไม่อ่าน') ?></span></td>
                                    <td>
                                        <div class="circular-action-stack">
                                            <button
                                                class="booking-action-btn secondary js-open-circular-modal"
                                                type="button"
                                                data-circular-id="<?= h((string) (int) ($item['circular_id'] ?? 0)) ?>"
                                                data-inbox-id="<?= h((string) (int) ($item['inbox_id'] ?? 0)) ?>"
                                                data-subject="<?= h((string) ($item['subject'] ?? '')) ?>"
                                                data-sender="<?= h($sender_modal_text) ?>"
                                                data-date="<?= h((string) ($item['delivered_date_long'] ?? $item['delivered_date'] ?? '-')) ?>"
                                                data-detail="<?= h((string) ($item['detail'] ?? '')) ?>"
                                                data-link="<?= h((string) ($item['link_url'] ?? '')) ?>"
                                                data-type="<?= h((string) ($item['type_label'] ?? '')) ?>"
                                                data-files="<?= h($file_json) ?>">
                                                <i class="fa-solid fa-eye"></i>
                                                <span class="tooltip">ดูรายละเอียด</span>
                                            </button>
                                            <a class="booking-action-btn secondary" href="circular-view.php?inbox_id=<?= h((string) (int) ($item['inbox_id'] ?? 0)) ?>">
                                                <i class="fa-solid fa-arrow-right-from-bracket"></i>
                                                <span class="tooltip">ส่งต่อ</span>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </form>
    <?php else : ?>
        <div class="table-circular-notice-index outside-person">
            <table>
                <thead>
                    <tr>
                        <th>วันที่รับ</th>
                        <th>เลขที่ / เรื่อง</th>
                        <th>ความเร่งด่วน</th>
                        <th>สถานะปัจุบัน</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)) : ?>
                        <tr>
                            <td colspan="5" class="enterprise-empty">ไม่มีรายการ</td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($items as $item) : ?>
                            <?php
                            $file_json = (string) ($item['files_json'] ?? '[]');
                            $priority_label = (string) ($item['ext_priority_label'] ?? 'ปกติ');
                            ?>
                            <tr>
                                <td>
                                    <p><?= h((string) ($item['delivered_date'] ?? '-')) ?></p>
                                    <p><?= h((string) ($item['delivered_time'] ?? '-')) ?></p>
                                </td>
                                <td>
                                    <p><?= h((string) ($item['ext_book_no'] ?? '-')) ?></p>
                                    <p><?= h((string) ($item['subject'] ?? '')) ?></p>
                                </td>
                                <td><button class="urgency-status <?= h((string) ($item['urgency_class'] ?? 'normal')) ?>">
                                        <p><?= h($priority_label) ?></p>
                                    </button></td>
                                <td><?= h((string) ($item['status_label'] ?? '-')) ?></td>
                                <td>
                                    <div class="circular-action-stack">
                                        <button
                                            class="button-more-details js-open-circular-modal"
                                            type="button"
                                            data-circular-id="<?= h((string) (int) ($item['circular_id'] ?? 0)) ?>"
                                            data-inbox-id="<?= h((string) (int) ($item['inbox_id'] ?? 0)) ?>"
                                            data-urgency="<?= h($priority_label) ?>"
                                            data-urgency-class="<?= h((string) ($item['urgency_class'] ?? 'normal')) ?>"
                                            data-bookno="<?= h((string) ($item['ext_book_no'] ?? '')) ?>"
                                            data-issued="<?= h((string) ($item['ext_issued_date'] ?? '-')) ?>"
                                            data-from="<?= h((string) ($item['ext_from_text'] ?? '')) ?>"
                                            data-to="<?= h($director_label) ?>"
                                            data-subject="<?= h((string) ($item['subject'] ?? '')) ?>"
                                            data-detail="<?= h((string) ($item['detail'] ?? '')) ?>"
                                            data-status="<?= h((string) ($item['status_label'] ?? '-')) ?>"
                                            data-consider="<?= h((string) ($item['consider_class'] ?? 'considering')) ?>"
                                            data-files="<?= h($file_json) ?>"
                                            data-received-time="<?= h((string) ($item['delivered_time'] ?? '-')) ?>">
                                            <p>รายละเอียด</p>
                                        </button>
                                        <a class="button-open-workflow" href="circular-view.php?inbox_id=<?= h((string) (int) ($item['inbox_id'] ?? 0)) ?>">อ่าน/ดำเนินการ</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php if (!$is_outside_view) : ?>
        <div class="modal-overlay-circular-notice-index keep-sending" id="modalNoticeKeepOverlay">
            <div class="modal-content">
                <div class="header-modal">
                    <p id="">ส่งหนังสือเวียนต่อ</p>
                    <i class="fa-solid fa-xmark" id="closeModalNoticeKeep"></i>
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
                                    <!-- <tr>
                                        <td colspan="3" class="enterprise-empty">ไม่พบข้อมูลผู้รับ</td>
                                    </tr> -->
                                    <tr>
                                        <td>นายธันวิน ณ นคร</td>
                                        <td><span class="status-pill pending">ยังไม่อ่าน</span></td>
                                        <td>-</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>


                    <div class="content-topic-sec">
                        <p><strong>เรื่อง</strong></p>
                        <input type="text" id="modalSubject" class="subject-disabled-solid" value="" disabled>
                    </div>
                    <div class="content-topic-sec">
                        <p><strong>รายละเอียด</strong></p>
                        <textarea name="detail" id="memo_editor"><?php //h((string) $values['detail'])
                                                                    ?></textarea>
                    </div>

                    <div class="content-topic-sec row">
                        <p><strong>ผู้ส่ง</strong></p>
                        <div class="room-admin-filter">
                            <div class="custom-select-wrapper">
                                <div class="custom-select-trigger">
                                    <p class="select-value"><?= h(!empty($sender_factions) ? (string) ($sender_factions[0]['fName'] ?? '') : 'ไม่มีข้อมูลหน่วยงาน') ?></p>
                                    <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                                </div>

                                <div class="custom-options">
                                    <?php if (empty($sender_factions)) : ?>
                                        <div class="custom-option selected" data-value="">ไม่มีข้อมูลหน่วยงาน</div>
                                    <?php else : ?>
                                        <?php foreach ($sender_factions as $index => $sender_faction) : ?>
                                            <?php
                                            $sender_fid = (string) ((int) ($sender_faction['fID'] ?? 0));
                                            $sender_faction_name = (string) ($sender_faction['fName'] ?? '');
                                            ?>
                                            <div class="custom-option<?= h($index === 0 ? ' selected' : '') ?>" data-value="<?= h($sender_fid) ?>"><?= h($sender_faction_name) ?></div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>

                                <select class="form-input" name="sender_fid">
                                    <?php if (empty($sender_factions)) : ?>
                                        <option value="">ไม่มีข้อมูลหน่วยงาน</option>
                                    <?php else : ?>
                                        <?php foreach ($sender_factions as $index => $sender_faction) : ?>
                                            <?php
                                            $sender_fid = (string) ((int) ($sender_faction['fID'] ?? 0));
                                            $sender_faction_name = (string) ($sender_faction['fName'] ?? '');
                                            ?>
                                            <option value="<?= h($sender_fid) ?>" <?= h($index === 0 ? 'selected' : '') ?>><?= h($sender_faction_name) ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group receive" data-recipients-section="">
                        <label>ส่งถึง :</label>
                        <div class="dropdown-container">
                            <div class="search-input-wrapper" id="recipientToggle">
                                <input type="text" id="mainInput" class="search-input" placeholder="ค้นหา หรือ เลือกข้อมูล..." autocomplete="off">
                                <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                            </div>

                            <div class="dropdown-content" id="dropdownContent">
                                <div class="dropdown-header">
                                    <label class="select-all-box">
                                        <input type="checkbox" id="selectAll">เลือกทั้งหมด
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
                            <button id="btnShowRecipients" type="button">
                                <p>แสดงผู้รับทั้งหมด</p>
                            </button>
                        </div>

                        <div id="recipientModal" class="modal-overlay-recipient">
                            <div class="modal-container">
                                <div class="modal-header">
                                    <div class="modal-title">
                                        <i class="fa-solid fa-users" aria-hidden="true"></i>
                                        <span>รายชื่อผู้รับหนังสือเวียน</span>
                                    </div>
                                    <button class="modal-close" id="closeModalBtn" type="button">
                                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
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
                                        <tbody id="recipientTableBody">
                                            <tr>
                                                <td colspan="3" style="text-align:center; padding: 16px;">ไม่มีผู้รับที่เลือก</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>

                <div class="footer-modal">
                    <form method="POST" id="modalArchiveForm">
                        <?= csrf_field() ?>
                        <input type="hidden" name="inbox_id" id="modalInboxId" value="">
                        <input type="hidden" name="action" value="<?= h($archived ? 'unarchive' : 'archive') ?>">
                        <button type="submit">
                            <p><?= h($archived ? 'ย้ายกลับ' : 'ส่งต่อ') ?></p>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    <?php else : ?>

        <div class="modal-overlay-circular-notice-index outside-person" id="modalNoticeKeepOverlay">
            <div class="modal-content">
                <div class="header-modal">
                    <div class="first-header">
                        <p>แสดงข้อความรายละเอียดหนังสือเวียน</p>
                    </div>
                    <div class="sec-header">
                        <div class="consider-status considering" id="modalConsiderStatus">กำลังเสนอ</div>
                        <i class="fa-solid fa-xmark" id="closeModalNoticeKeep"></i>
                    </div>
                </div>

                <div class="content-modal">
                    <div class="content-topic-sec">
                        <p><strong>ความเร่งด่วน :</strong></p>
                        <button class="urgency-status normal" id="modalUrgency">
                            <p>ปกติ</p>
                        </button>
                    </div>
                    <div class="content-topic-sec">
                        <div class="more-details">
                            <p><strong>เลขที่หนังสือ :</strong></p>
                            <input type="text" id="modalBookNo" placeholder="-" disabled>
                        </div>
                        <div class="more-details">
                            <p><strong>ลงวันที่ : </strong></p>
                            <input type="text" id="modalIssuedDate" placeholder="-" disabled>
                        </div>
                        <div class="more-details">
                            <p><strong>จาก : </strong></p>
                            <input type="text" id="modalFromText" placeholder="-" disabled>
                        </div>
                        <div class="more-details">
                            <p><strong>ถึง : </strong></p>
                            <input type="text" id="modalToText" placeholder="-" disabled>
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
                        <p>ไฟล์เอกสารแนบจากระบบ</p>
                        <div class="file-section" id="modalFileSection"></div>
                    </div>

                    <div class="content-time-and-considered-sec">
                        <div class="more-details">
                            <p><strong>รับหนังสือเข้าระบบ : </strong></p>
                            <input type="text" id="modalReceivedTime" placeholder="-" disabled>
                        </div>
                        <div class="more-details">
                            <p><strong>สถานะ : </strong></p>
                            <input type="text" id="modalStatus" placeholder="-" disabled>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php endif; ?>
</section>

<?php if (!$is_outside_view) : ?>
    <div class="button-circular-notice-index">
        <button class="button-keep" type="submit" form="bulkActionForm">
            <i class="fa-solid fa-file-import"></i>
            <p><?= h($archived ? 'ย้ายกลับ' : 'ส่งต่อ') ?></p>
        </button>
    </div>
<?php else : ?>
    <div class="button-circular-notice-index"></div>
<?php endif; ?>

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
    });
</script>

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

    document.addEventListener('DOMContentLoaded', function() {
        const detailModal = document.getElementById('modalNoticeKeepOverlay');
        const closeDetailModalBtn = document.getElementById('closeModalNoticeKeep');
        const openDetailBtns = document.querySelectorAll('.js-open-circular-modal');

        openDetailBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                if (detailModal) {
                    detailModal.style.display = 'flex';
                }
            });
        });

        if (closeDetailModalBtn) {
            closeDetailModalBtn.addEventListener('click', () => {
                detailModal.style.display = 'none';
            });
        }

        if (detailModal) {
            detailModal.addEventListener('click', (e) => {
                if (e.target === detailModal) {
                    detailModal.style.display = 'none';
                }
            });
        }
    });
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';

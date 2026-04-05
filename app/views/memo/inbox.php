<?php

declare(strict_types=1);

require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../auth/csrf.php';
require_once __DIR__ . '/../../modules/memos/status.php';

$items = (array) ($items ?? []);
$page = (int) ($page ?? 1);
$total_pages = (int) ($total_pages ?? 1);
$search = trim((string) ($search ?? ''));
$status_filter = (string) ($status_filter ?? 'all');
$filtered_total = (int) ($filtered_total ?? 0);
$pagination_base_url = (string) ($pagination_base_url ?? 'memo-inbox.php');
$dh_year_options = array_values(array_filter(array_map('intval', (array) ($dh_year_options ?? [])), static function (int $year): bool {
    return $year > 0;
}));
$selected_dh_year = (int) ($selected_dh_year ?? ($dh_year_options[0] ?? 0));
$dh_year_label = $selected_dh_year > 0 ? (string) $selected_dh_year : '-';
$filter_search = $search;
$filter_read = trim((string) ($_GET['read'] ?? 'all'));
$filter_sort = trim((string) ($_GET['sort'] ?? 'newest'));
$filter_view = trim((string) ($_GET['view'] ?? 'table1'));

if (!in_array($filter_read, ['all', 'read', 'unread'], true)) {
    $filter_read = 'all';
}

if (!in_array($filter_sort, ['newest', 'oldest'], true)) {
    $filter_sort = 'newest';
}

if (!in_array($filter_view, ['table1', 'table2'], true)) {
    $filter_view = 'table1';
}

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

$format_thai_datetime = static function (?string $date_value) use ($thai_months): array {
    $date_value = trim((string) $date_value);

    if ($date_value === '' || strpos($date_value, '0000-00-00') === 0) {
        return ['-', '-'];
    }

    $date_obj = DateTime::createFromFormat('Y-m-d H:i:s', $date_value);

    if ($date_obj === false) {
        $date_obj = DateTime::createFromFormat('Y-m-d H:i', $date_value);
    }

    if ($date_obj === false) {
        return [$date_value, '-'];
    }

    $day = (int) $date_obj->format('j');
    $month = $thai_months[(int) $date_obj->format('n')] ?? '';
    $year = (int) $date_obj->format('Y') + 543;

    return [trim($day . ' ' . $month . ' ' . $year), $date_obj->format('H:i') . ' น.'];
};

$values = $values ?? ['writeDate' => '', 'subject' => '', 'detail' => ''];
$current_user = (array) ($current_user ?? []);
$current_pid = trim((string) ($current_user['pID'] ?? ''));
$factions = (array) ($factions ?? []);
$deputy_candidates = array_values(array_filter((array) ($deputy_candidates ?? []), static function ($candidate): bool {
    return is_array($candidate) && trim((string) ($candidate['pID'] ?? '')) !== '' && trim((string) ($candidate['name'] ?? '')) !== '';
}));
$deputy_candidates_json = json_encode(array_map(static function (array $candidate): array {
    return [
        'pID' => trim((string) ($candidate['pID'] ?? '')),
        'name' => trim((string) ($candidate['name'] ?? '')),
        'positionName' => trim((string) ($candidate['positionName'] ?? '')),
    ];
}, $deputy_candidates), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

if ($deputy_candidates_json === false) {
    $deputy_candidates_json = '[]';
}

$selected_sender_fid = trim((string) ($values['sender_fid'] ?? ''));

if ($selected_sender_fid === '' && !empty($factions)) {
    $selected_sender_fid = (string) ($factions[0]['fID'] ?? '');
}

$selected_faction_name = '';

foreach ($factions as $faction) {
    if ((string) ($faction['fID'] ?? '') === $selected_sender_fid) {
        $selected_faction_name = trim((string) ($faction['fname'] ?? ''));
        break;
    }
}

$signature_src = trim((string) ($current_user['signature'] ?? ''));
$current_name = trim((string) ($current_user['fName'] ?? ''));
$current_position = trim((string) ($current_user['position_name'] ?? ''));

ob_start();
?>

<style>
    #modalEditOverlay [data-memo-stage-section] {
        position: relative;
        z-index: 1;
    }

    #modalEditOverlay [data-memo-review-action-row] {
        position: relative;
        z-index: 25;
        margin: 6px 0 10px;
    }

    #modalEditOverlay [data-memo-review-action-row] .custom-select-wrapper {
        pointer-events: auto;
        z-index: 30;
    }

    #modalEditOverlay [data-memo-review-action-row] .custom-options {
        z-index: 35;
    }
</style>

<div class="content-header">
    <h1>ยินดีต้อนรับ</h1>
    <p>บันทึกข้อความ / กล่องบันทึกข้อความ</p>
</div>

<form id="circularFilterForm" method="GET">
    <input type="hidden" name="dh_year" id="filterYearInput" value="<?= h((string) $selected_dh_year) ?>">
    <input type="hidden" name="read" id="filterReadInput" value="<?= h($filter_read) ?>">
    <input type="hidden" name="sort" id="filterSortInput" value="<?= h($filter_sort) ?>">
    <input type="hidden" name="view" id="filterViewInput" value="<?= h($filter_view) ?>">
</form>

<input type="hidden" id="csrfToken" value="<?= h(csrf_token()) ?>">

<header class="header-circular-notice-keep">
    <div class="circular-notice-keep-control">
        <div class="page-selector">
            <p>แสดงตามปีสารบรรณ</p>
            <div class="custom-select-wrapper" data-target="filterYearInput">
                <div class="custom-select-trigger">
                    <p class="select-value"><?= h($dh_year_label) ?></p>
                    <i class="fa-solid fa-chevron-down"></i>
                </div>
                <div class="custom-options">
                    <?php if (empty($dh_year_options)) : ?>
                        <div class="custom-option selected" data-value="<?= h((string) $selected_dh_year) ?>"><?= h($dh_year_label) ?></div>
                    <?php else : ?>
                        <?php foreach ($dh_year_options as $year_option) : ?>
                            <div class="custom-option<?= $selected_dh_year === (int) $year_option ? ' selected' : '' ?>" data-value="<?= h((string) $year_option) ?>"><?= h((string) $year_option) ?></div>
                        <?php endforeach; ?>
                    <?php endif; ?>
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
                    <div class="custom-option<?= $filter_read === 'read' ? ' selected' : '' ?>" data-value="read">อ่านแล้ว</div>
                    <div class="custom-option<?= $filter_read === 'unread' ? ' selected' : '' ?>" data-value="unread">ยังไม่อ่าน</div>
                    <div class="custom-option<?= $filter_read === 'all' ? ' selected' : '' ?>" data-value="all">ทั้งหมด</div>
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
                    <div class="custom-option<?= $filter_sort === 'newest' ? ' selected' : '' ?>" data-value="newest">ใหม่ไปเก่า</div>
                    <div class="custom-option<?= $filter_sort === 'oldest' ? ' selected' : '' ?>" data-value="oldest">เก่าไปใหม่</div>
                </div>
            </div>
        </div>
    </div>
</header>

<section
    class="content-circular-notice-index"
    data-circular-notice
    data-ajax-filter="true"
    data-ajax-target=".table-circular-notice-index">
    <div class="search-bar">
        <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input
                type="text"
                id="search-input"
                name="q"
                form="circularFilterForm"
                value="<?= h($filter_search) ?>"
                placeholder="ค้นหาข้อความด้วย..."
                data-auto-submit="true"
                data-auto-submit-delay="450">
        </div>
    </div>

    <form id="bulkActionForm" method="POST">
        <?= csrf_field() ?>
        <div class="table-circular-notice-index memo-inbox-table">
            <table>
                <thead>
                    <tr>
                        <th>
                            <input type="checkbox" class="check-table checkall" id="checkAllCircular">
                        </th>
                        <th>เรื่อง</th>
                        <th>ผู้เสนอแฟ้ม</th>
                        <th>วันที่เสนอแฟ้ม</th>
                        <th>สถานะ</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)) : ?>
                        <tr>
                            <td colspan="6" class="enterprise-empty">ไม่มีรายการ</td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($items as $item) : ?>
                            <?php
                            $memo_id = (int) ($item['memoID'] ?? 0);
                            $subject = trim((string) ($item['subject'] ?? ''));
                            $detail = (string) ($item['detail'] ?? '');
                            $creator_name = trim((string) ($item['creatorName'] ?? ''));
                            $creator_signature = trim((string) ($item['creatorSignature'] ?? ''));
                            $creator_section = trim((string) ($item['creatorFactionName'] ?? ''));

                            if ($creator_section === '') {
                                $creator_section = trim((string) ($item['creatorDepartmentName'] ?? ''));
                            }

                            $creator_position = trim((string) ($item['creatorPositionName'] ?? ''));
                            $reviewer_role = strtoupper(trim((string) ($item['reviewerRole'] ?? '')));
                            $status = (string) ($item['status'] ?? '');
                            $status_meta = memo_status_meta($status);
                            $status_class = (string) ($status_meta['pill_variant'] ?? 'pending');
                            $submitted_at = trim((string) ($item['submittedAt'] ?? ''));
                            $created_at = trim((string) ($item['createdAt'] ?? ''));
                            [$date_line, $time_line] = $format_thai_datetime($submitted_at !== '' ? $submitted_at : $created_at);
                            $head_pid = trim((string) ($item['headResolvedPID'] ?? ''));
                            $deputy_pid = trim((string) ($item['deputyResolvedPID'] ?? ''));
                            $director_pid = trim((string) ($item['directorResolvedPID'] ?? ''));
                            ?>
                            <tr>
                                <td>
                                    <input type="checkbox" class="check-table" name="selected_ids[]" value="<?= h((string) $memo_id) ?>">
                                </td>
                                <td><?= h($subject !== '' ? $subject : '-') ?></td>
                                <td>
                                    <div class="circular-sender-stack">
                                        <span class="circular-sender-name"><?= h($creator_name !== '' ? $creator_name : '-') ?></span>
                                    </div>
                                </td>
                                <td>
                                    <p><?= h($date_line) ?></p>
                                    <p class="detail-subtext"><?= h($time_line) ?></p>
                                </td>
                                <td><span class="status-pill <?= h($status_class) ?>"><?= h((string) ($status_meta['label'] ?? '-')) ?></span></td>
                                <td>
                                    <button
                                        class="booking-action-btn secondary js-open-edit-modal"
                                        type="button"
                                        data-memo-id="<?= h((string) $memo_id) ?>"
                                        data-subject="<?= h($subject) ?>"
                                        data-detail="<?= h($detail) ?>"
                                        data-section="<?= h($creator_section !== '' ? $creator_section : ($selected_faction_name !== '' ? $selected_faction_name : 'กลุ่ม')) ?>"
                                        data-name="<?= h($creator_name !== '' ? $creator_name : '-') ?>"
                                        data-position="<?= h($creator_position !== '' ? $creator_position : '-') ?>"
                                        data-signature="<?= h($creator_signature !== '' ? $creator_signature : $signature_src) ?>"
                                        data-reviewer-role="<?= h($reviewer_role) ?>"
                                        data-head-pid="<?= h($head_pid) ?>"
                                        data-head-name="<?= h((string) ($item['headName'] ?? '')) ?>"
                                        data-head-position="<?= h((string) ($item['headPositionName'] ?? '')) ?>"
                                        data-head-signature="<?= h((string) ($item['headSignature'] ?? '')) ?>"
                                        data-head-note="<?= h((string) ($item['headNote'] ?? '')) ?>"
                                        data-deputy-pid="<?= h($deputy_pid) ?>"
                                        data-deputy-name="<?= h((string) ($item['deputyName'] ?? '')) ?>"
                                        data-deputy-position="<?= h((string) ($item['deputyPositionName'] ?? '')) ?>"
                                        data-deputy-signature="<?= h((string) ($item['deputySignature'] ?? '')) ?>"
                                        data-deputy-note="<?= h((string) ($item['deputyNote'] ?? '')) ?>"
                                        data-director-pid="<?= h($director_pid) ?>"
                                        data-director-name="<?= h((string) ($item['directorName'] ?? '')) ?>"
                                        data-director-position="<?= h((string) ($item['directorPositionName'] ?? '')) ?>"
                                        data-director-signature="<?= h((string) ($item['directorSignature'] ?? '')) ?>"
                                        data-director-note="<?= h((string) ($item['directorNote'] ?? '')) ?>">
                                        <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                        <span class="tooltip">ดูรายละเอียด</span>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </form>

    <div class="modal-overlay-memo details" id="modalEditOverlay" style="display: none;">
        <div class="modal-content">
            <div class="header-modal">
                <p id="modalTypeLabel">รายละเอียดบันทึกข้อความ</p>
                <i class="fa-solid fa-xmark" id="closeModalEdit" aria-hidden="true"></i>
            </div>

            <div class="content-modal">
                <div class="content-memo" style="box-shadow: none;">
                    <div class="memo-header">
                        <img src="assets/img/garuda-logo.png" alt="">
                        <p>บันทึกข้อความ</p>
                        <div></div>
                    </div>

                    <div class="memo-detail">
                        <div class="form-group-row">
                            <p><strong>ส่วนราชการ</strong></p>

                            <div class="custom-select-wrapper" aria-disabled="true" style="pointer-events: none;">
                                <div class="custom-select-trigger">
                                    <p class="select-value" data-memo-detail-section><?= h($selected_faction_name !== '' ? $selected_faction_name : 'เลือกส่วนราชการ') ?></p>
                                    <i class="fa-solid fa-chevron-down"></i>
                                </div>

                                <div class="custom-options">
                                    <?php foreach ($factions as $faction) : ?>
                                        <div class="custom-option<?= (string) ($faction['fID'] ?? '') === $selected_sender_fid ? ' selected' : '' ?>" data-value="<?= h((string) ($faction['fID'] ?? '')) ?>">
                                            <?= h((string) ($faction['fname'] ?? '')) ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <p><strong>โรงเรียนดีบุกพังงาวิทยายน</strong></p>
                        </div>

                        <div class="form-group-row memo-subject-row">
                            <p><strong>เรื่อง</strong></p>
                            <input type="text" name="subject" value="<?= h((string) ($values['subject'] ?? '')) ?>" data-memo-detail-subject readonly required>
                        </div>

                        <div class="form-group-row memo-to-row">
                            <p><strong>เรียน</strong></p>
                            <p>ผู้อำนวยการโรงเรียนดีบุกพังงาวิทยายน</p>
                        </div>

                        <div class="content-editor">
                            <p><strong>รายละเอียด:</strong></p>
                            <br>
                            <textarea name="detail" id="memo_detail_editor" data-memo-detail-body readonly><?= h((string) ($values['detail'] ?? '')) ?></textarea>
                        </div>

                        <div class="form-group-row signature">
                            <img src="<?= h($signature_src !== '' ? $signature_src : 'assets/img/garuda-logo.png') ?>" alt="" data-memo-detail-signature-image>
                            <p data-memo-detail-signature-name>(<?= h($current_name !== '' ? $current_name : '-') ?>)</p>
                            <p data-memo-detail-signature-position><?= h($current_position !== '' ? $current_position : '-') ?></p>
                        </div>
                        <br><br><br>
                        <?php foreach ([
                            'HEAD' => 'ความคิดเห็นและข้อเสนอแนะของหัวหน้ากลุ่มสาระการเรียนรู้',
                            'DEPUTY' => 'ความคิดเห็นและข้อเสนอแนะของรองผู้อำนวยการ',
                            'DIRECTOR' => 'ความคิดเห็นและข้อเสนอแนะของผู้อำนวยการโรงเรียน',
                        ] as $stage_key => $stage_label) : ?>
                            <div class="content-editor secondary" data-memo-stage-section="<?= h($stage_key) ?>" style="display: none;">
                                <p><strong data-memo-stage-label="<?= h($stage_key) ?>"><?= h($stage_label) ?></strong></p>
                                <br>
                                <textarea name="modal_<?= strtolower($stage_key) ?>_note" id="memo_detail_<?= h($stage_key) ?>" data-memo-stage-note="<?= h($stage_key) ?>" rows="7" readonly></textarea>
                            </div>

                            <div class="form-group-row signature secondary" data-memo-stage-signature="<?= h($stage_key) ?>" style="display: none;">
                                <img src="<?= h($signature_src !== '' ? $signature_src : 'assets/img/garuda-logo.png') ?>" alt="" data-memo-stage-signature-image="<?= h($stage_key) ?>">
                                <p data-memo-stage-signature-name="<?= h($stage_key) ?>">(<?= h($current_name !== '' ? $current_name : '-') ?>)</p>
                                <p data-memo-stage-signature-position="<?= h($stage_key) ?>"><?= h($current_position !== '' ? $current_position : '-') ?></p>
                            </div>
                            <br><br><br>
                        <?php endforeach; ?>

                        <div class="form-group-row" data-memo-review-action-row style="display: none;">
                            <p><strong>เสนอ :</strong></p>

                            <div class="custom-select-wrapper" data-memo-action-wrapper data-custom-select-manual="1">
                                <div class="custom-select-trigger">
                                    <p class="select-value" data-memo-action-value>เลือกการดำเนินการ</p>
                                    <i class="fa-solid fa-chevron-down"></i>
                                </div>

                                <div class="custom-options" data-memo-action-options></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="footer-modal">
                <form method="POST" id="modalArchiveForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="memo_id" id="modalMemoId" value="">
                    <input type="hidden" name="action" id="modalMemoAction" value="">
                    <input type="hidden" name="target_pid" id="modalMemoTargetPid" value="">
                    <input type="hidden" name="note" id="modalMemoNote" value="">
                    <button type="submit" style="width: auto;">
                        <p>เสนอแฟ้ม</p>
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js"></script>
<script>
    tinymce.init({
        selector: '#memo_detail_editor, #memo_detail_HEAD, #memo_detail_DEPUTY, #memo_detail_DIRECTOR',
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

    const editModal = document.getElementById('modalEditOverlay');
    const closeEditBtn = editModal?.querySelector('#closeModalEdit');
    const modalTypeLabel = editModal?.querySelector('#modalTypeLabel');
    const modalSectionField = editModal?.querySelector('[data-memo-detail-section]');
    const modalSubjectField = editModal?.querySelector('[data-memo-detail-subject]');
    const modalBodyField = editModal?.querySelector('[data-memo-detail-body]');
    const modalSignatureImage = editModal?.querySelector('[data-memo-detail-signature-image]');
    const modalSignatureName = editModal?.querySelector('[data-memo-detail-signature-name]');
    const modalSignaturePosition = editModal?.querySelector('[data-memo-detail-signature-position]');
    const modalFooterForm = document.getElementById('modalArchiveForm');
    const modalMemoIdInput = document.getElementById('modalMemoId');
    const modalMemoActionInput = document.getElementById('modalMemoAction');
    const modalMemoTargetPidInput = document.getElementById('modalMemoTargetPid');
    const modalMemoNoteInput = document.getElementById('modalMemoNote');
    const modalFooterButton = modalFooterForm?.querySelector('button');
    const modalFooterButtonLabel = modalFooterButton?.querySelector('p');
    const modalActionRow = editModal?.querySelector('[data-memo-review-action-row]');
    const modalActionWrapper = editModal?.querySelector('[data-memo-action-wrapper]');
    const modalActionValue = editModal?.querySelector('[data-memo-action-value]');
    const modalActionOptions = editModal?.querySelector('[data-memo-action-options]');
    const modalDetailContainer = editModal?.querySelector('.memo-detail');
    const defaultSignatureImage = modalSignatureImage?.getAttribute('src') ?? '';
    const defaultSignatureName = modalSignatureName?.textContent ?? '';
    const defaultSignaturePosition = modalSignaturePosition?.textContent ?? '';
    const deputyCandidates = <?= $deputy_candidates_json ?>;
    const stageOrderByRole = {
        HEAD: ['HEAD'],
        DEPUTY: ['HEAD', 'DEPUTY'],
        DIRECTOR: ['HEAD', 'DEPUTY', 'DIRECTOR'],
    };
    const stageMeta = {
        HEAD: {
            prefix: 'head',
            label: 'ความคิดเห็นและข้อเสนอแนะของหัวหน้ากลุ่มสาระการเรียนรู้',
        },
        DEPUTY: {
            prefix: 'deputy',
            label: 'ความคิดเห็นและข้อเสนอแนะของรองผู้อำนวยการ',
        },
        DIRECTOR: {
            prefix: 'director',
            label: 'ความคิดเห็นและข้อเสนอแนะของผู้อำนวยการโรงเรียน',
        },
    };
    const stageKeys = ['HEAD', 'DEPUTY', 'DIRECTOR'];
    const stageElements = stageKeys.reduce((accumulator, stage) => {
        accumulator[stage] = {
            section: editModal?.querySelector('[data-memo-stage-section="' + stage + '"]') || null,
            label: editModal?.querySelector('[data-memo-stage-label="' + stage + '"]') || null,
            note: editModal?.querySelector('[data-memo-stage-note="' + stage + '"]') || null,
            signature: editModal?.querySelector('[data-memo-stage-signature="' + stage + '"]') || null,
            signatureImage: editModal?.querySelector('[data-memo-stage-signature-image="' + stage + '"]') || null,
            signatureName: editModal?.querySelector('[data-memo-stage-signature-name="' + stage + '"]') || null,
            signaturePosition: editModal?.querySelector('[data-memo-stage-signature-position="' + stage + '"]') || null,
        };

        return accumulator;
    }, {});
    let currentActionOptions = [];
    let currentEditableReviewField = null;

    const moveActionRowAfterStage = (stage) => {
        if (!modalActionRow || !modalDetailContainer) {
            return;
        }

        const targetStage = stageElements[stage] || null;
        const targetSection = targetStage?.section || null;

        if (targetSection) {
            const noteField = targetStage?.note || null;
            const labelRow = targetStage?.label?.closest('p') || null;

            if (noteField && noteField.parentNode === targetSection) {
                targetSection.insertBefore(modalActionRow, noteField);
                return;
            }

            if (labelRow && labelRow.parentNode === targetSection) {
                labelRow.insertAdjacentElement('afterend', modalActionRow);
                return;
            }

            targetSection.appendChild(modalActionRow);
            return;
        }

        const anchor = targetStage?.signature || null;

        if (anchor && anchor.parentNode) {
            anchor.insertAdjacentElement('afterend', modalActionRow);
            return;
        }

        modalDetailContainer.appendChild(modalActionRow);
    };

    const syncMemoDetailEditor = (detailValue) => {
        const detailText = typeof detailValue === 'string' ? detailValue : '';
        const editor = typeof tinymce !== 'undefined' ? tinymce.get('memo_detail_editor') : null;

        if (modalBodyField) {
            modalBodyField.value = detailText;
        }

        if (editor) {
            editor.setContent(detailText);

            if (editor.mode && typeof editor.mode.set === 'function') {
                editor.mode.set('readonly');
            }
        }
    };

    const formatSignatureName = (value, fallbackValue = '-') => {
        const cleanValue = String(value || '').trim();
        const fallbackCleanValue = String(fallbackValue || '').replace(/^\(|\)$/g, '').trim();

        return '(' + (cleanValue || fallbackCleanValue || '-') + ')';
    };

    const hideStageSection = (stage) => {
        const elements = stageElements[stage];

        if (!elements) {
            return;
        }

        if (elements.section) {
            elements.section.style.display = 'none';
        }

        if (elements.signature) {
            elements.signature.style.display = 'none';
        }

        if (elements.note) {
            elements.note.value = '';
            elements.note.readOnly = true;
        }
    };

    const readStagePayload = (trigger, stage) => {
        const prefix = stageMeta[stage]?.prefix || '';
        const suffix = prefix.charAt(0).toUpperCase() + prefix.slice(1);

        return {
            pid: String(trigger.dataset[prefix + 'Pid'] || '').trim(),
            name: String(trigger.dataset[prefix + 'Name'] || '').trim(),
            position: String(trigger.dataset[prefix + 'Position'] || '').trim(),
            signature: String(trigger.dataset[prefix + 'Signature'] || '').trim(),
            note: String(trigger.dataset[prefix + 'Note'] || ''),
            hasAnyData: Boolean(
                String(trigger.dataset[prefix + 'Pid'] || '').trim() ||
                String(trigger.dataset[prefix + 'Name'] || '').trim() ||
                String(trigger.dataset[prefix + 'Position'] || '').trim() ||
                String(trigger.dataset[prefix + 'Signature'] || '').trim() ||
                String(trigger.dataset[prefix + 'Note'] || '').trim()
            ),
        };
    };

    const renderStageSection = (stage, payload, editable) => {
        const elements = stageElements[stage];

        if (!elements) {
            return;
        }

        if (elements.label) {
            elements.label.textContent = stageMeta[stage]?.label || 'ความคิดเห็นและข้อเสนอแนะ';
        }

        if (elements.note) {
            elements.note.value = payload.note || '';
            elements.note.readOnly = !editable;
        }

        if (elements.signatureImage) {
            elements.signatureImage.setAttribute('src', payload.signature || defaultSignatureImage);
        }

        if (elements.signatureName) {
            elements.signatureName.textContent = formatSignatureName(payload.name, '-');
        }

        if (elements.signaturePosition) {
            elements.signaturePosition.textContent = payload.position || '-';
        }

        if (elements.section) {
            elements.section.style.display = '';
        }

        if (elements.signature) {
            elements.signature.style.display = '';
        }
    };

    const buildActionOptions = (reviewerRole, trigger) => {
        const deputyName = String(trigger.dataset.deputyName || '').trim();
        const deputyPid = String(trigger.dataset.deputyPid || '').trim();
        const directorName = String(trigger.dataset.directorName || '').trim();
        const directorPid = String(trigger.dataset.directorPid || '').trim();

        if (reviewerRole === 'HEAD') {
            const deputyForwardOptions = deputyCandidates.length > 0
                ? deputyCandidates.map((candidate) => ({
                    key: 'forward:' + candidate.pID,
                    value: 'forward',
                    label: candidate.name,
                    submitLabel: 'เสนอแฟ้ม',
                    targetPid: candidate.pID,
                }))
                : [{
                    key: 'forward:' + (deputyPid || directorPid || 'fallback'),
                    value: 'forward',
                    label: deputyName || directorName || 'รองผู้อำนวยการ',
                    submitLabel: 'เสนอแฟ้ม',
                    targetPid: deputyPid || '',
                }];

            return [
                ...deputyForwardOptions,
                {
                    key: 'return',
                    value: 'return',
                    label: 'ตีกลับแก้ไข',
                    submitLabel: 'ตีกลับแก้ไข',
                },
            ];
        }

        if (reviewerRole === 'DEPUTY') {
            return [{
                    key: 'forward:' + (directorPid || 'director'),
                    value: 'forward',
                    label: directorName || 'ผู้อำนวยการโรงเรียน',
                    submitLabel: 'เสนอแฟ้ม',
                    targetPid: directorPid || '',
                },
                {
                    key: 'return',
                    value: 'return',
                    label: 'ตีกลับแก้ไข',
                    submitLabel: 'ตีกลับแก้ไข',
                },
            ];
        }

        if (reviewerRole === 'DIRECTOR') {
            return [{
                    key: 'director_approve',
                    value: 'director_approve',
                    label: 'อนุมัติและปิดงาน',
                    submitLabel: 'อนุมัติและปิดงาน',
                },
                {
                    key: 'director_reject',
                    value: 'director_reject',
                    label: 'ไม่อนุมัติ',
                    submitLabel: 'ไม่อนุมัติ',
                },
                {
                    key: 'return',
                    value: 'return',
                    label: 'ตีกลับแก้ไข',
                    submitLabel: 'ตีกลับแก้ไข',
                },
            ];
        }

        return [];
    };

    const applySelectedAction = (value) => {
        const selected = currentActionOptions.find((option) => option.key === value) || currentActionOptions[0] || null;

        if (!selected) {
            if (modalMemoActionInput) {
                modalMemoActionInput.value = '';
            }

            if (modalMemoTargetPidInput) {
                modalMemoTargetPidInput.value = '';
            }

            if (modalActionValue) {
                modalActionValue.textContent = 'เลือกการดำเนินการ';
            }

            return;
        }

        if (modalMemoActionInput) {
            modalMemoActionInput.value = selected.value;
        }

        if (modalMemoTargetPidInput) {
            modalMemoTargetPidInput.value = selected.targetPid || '';
        }

        if (modalActionValue) {
            modalActionValue.textContent = selected.label;
        }

        if (modalFooterButtonLabel) {
            modalFooterButtonLabel.textContent = selected.submitLabel || selected.label;
        }

        modalActionOptions?.querySelectorAll('.custom-option').forEach((option) => {
            option.classList.toggle('selected', option.getAttribute('data-memo-action-option') === selected.key);
        });
    };

    const resetReviewState = () => {
        currentEditableReviewField = null;
        currentActionOptions = [];

        stageKeys.forEach((stage) => {
            hideStageSection(stage);
        });

        if (modalActionRow) {
            modalActionRow.style.display = 'none';
        }

        if (modalActionWrapper) {
            modalActionWrapper.classList.remove('open');
            modalActionWrapper.classList.remove('is-disabled');
            modalActionWrapper.style.pointerEvents = 'auto';
        }

        if (modalActionOptions) {
            modalActionOptions.innerHTML = '';
        }

        if (modalActionValue) {
            modalActionValue.textContent = 'เลือกการดำเนินการ';
        }

        if (modalMemoActionInput) {
            modalMemoActionInput.value = '';
        }

        if (modalMemoTargetPidInput) {
            modalMemoTargetPidInput.value = '';
        }

        if (modalMemoNoteInput) {
            modalMemoNoteInput.value = '';
        }

        if (modalFooterButton) {
            modalFooterButton.style.display = 'none';
        }

        if (modalFooterButtonLabel) {
            modalFooterButtonLabel.textContent = 'เสนอแฟ้ม';
        }

        if (modalActionRow && modalDetailContainer) {
            modalDetailContainer.appendChild(modalActionRow);
        }
    };

    const closeEditModal = () => {
        if (!editModal) {
            return;
        }

        resetReviewState();
        editModal.style.display = 'none';
    };

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('.js-open-edit-modal');

        if (!trigger || !editModal) {
            return;
        }

        event.preventDefault();

        if (modalTypeLabel) {
            modalTypeLabel.textContent = 'รายละเอียดบันทึกข้อความ';
        }

        if (modalSectionField) {
            modalSectionField.textContent = trigger.dataset.section || 'กลุ่ม';
        }

        if (modalSubjectField) {
            modalSubjectField.value = trigger.dataset.subject || '';
            modalSubjectField.readOnly = true;
        }

        syncMemoDetailEditor(trigger.dataset.detail || '');

        if (modalSignatureImage) {
            modalSignatureImage.setAttribute('src', trigger.dataset.signature || defaultSignatureImage);
        }

        if (modalSignatureName) {
            modalSignatureName.textContent = '(' + (trigger.dataset.name || defaultSignatureName.replace(/^\(|\)$/g, '') || '-') + ')';
        }

        if (modalSignaturePosition) {
            modalSignaturePosition.textContent = trigger.dataset.position || defaultSignaturePosition || '-';
        }

        resetReviewState();

        const reviewerRole = String(trigger.dataset.reviewerRole || '').toUpperCase();
        const stageSequence = stageOrderByRole[reviewerRole] || [];

        if (modalMemoIdInput) {
            modalMemoIdInput.value = trigger.dataset.memoId || '';
        }

        stageSequence.forEach((stage) => {
            const payload = readStagePayload(trigger, stage);
            const shouldShow = payload.hasAnyData || stage === reviewerRole;

            if (!shouldShow) {
                return;
            }

            const isEditable = stage === reviewerRole;
            renderStageSection(stage, payload, isEditable);

            if (isEditable) {
                currentEditableReviewField = stageElements[stage]?.note || null;
            }
        });

        currentActionOptions = buildActionOptions(reviewerRole, trigger);

        if (currentActionOptions.length > 0) {
            moveActionRowAfterStage(reviewerRole);

            if (modalActionRow) {
                modalActionRow.style.display = '';
            }

            if (modalFooterButton) {
                modalFooterButton.style.display = '';
            }

            if (modalActionOptions) {
                modalActionOptions.innerHTML = currentActionOptions.map((option, index) => (
                    '<div class="custom-option' + (index === 0 ? ' selected' : '') + '" data-memo-action-option="' + option.key + '">' +
                    option.label +
                    '</div>'
                )).join('');
            }

            applySelectedAction(currentActionOptions[0].value);
        }

        editModal.style.display = 'flex';
    });

    modalActionWrapper?.querySelector('.custom-select-trigger')?.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
        modalActionWrapper.classList.toggle('open');
    });

    modalActionOptions?.addEventListener('click', (event) => {
        const option = event.target.closest('[data-memo-action-option]');

        if (!option) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        applySelectedAction(option.getAttribute('data-memo-action-option') || '');
        modalActionWrapper?.classList.remove('open');
    });

    modalFooterForm?.addEventListener('submit', (event) => {
        const currentAction = String(modalMemoActionInput?.value || '').trim();
        const noteValue = String(currentEditableReviewField?.value || '').trim();

        if (modalMemoNoteInput) {
            modalMemoNoteInput.value = noteValue;
        }

        if ((currentAction === 'return' || currentAction === 'director_reject') && noteValue === '') {
            event.preventDefault();
            window.alert('กรุณากรอกความเห็น');
            currentEditableReviewField?.focus();
        }
    });

    closeEditBtn?.addEventListener('click', closeEditModal);

    window.addEventListener('click', (event) => {
        if (event.target === editModal) {
            closeEditModal();
        }

        if (modalActionWrapper && !modalActionWrapper.contains(event.target)) {
            modalActionWrapper.classList.remove('open');
        }
    });
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';

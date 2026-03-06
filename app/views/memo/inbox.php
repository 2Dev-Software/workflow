<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../auth/csrf.php';

$items = (array) ($items ?? []);
$page = max(1, (int) ($page ?? 1));
$total_pages = max(1, (int) ($total_pages ?? 1));
$search = trim((string) ($search ?? ''));
$status_filter = trim((string) ($status_filter ?? 'all'));
$filtered_total = (int) ($filtered_total ?? 0);
$pagination_base_url = (string) ($pagination_base_url ?? 'memo-inbox.php');

$memo_page_my = 'memo.php';
$memo_page_inbox = 'memo-inbox.php';
$memo_page_archive = 'memo-archive.php';
$memo_page_view = 'memo-view.php';

$status_map = [
    'SUBMITTED' => ['label' => 'รอพิจารณา', 'variant' => 'pending'],
    'IN_REVIEW' => ['label' => 'กำลังพิจารณา', 'variant' => 'processing'],
    'RETURNED' => ['label' => 'ตีกลับแก้ไข', 'variant' => 'rejected'],
    'APPROVED_UNSIGNED' => ['label' => 'อนุมัติ (รอแนบไฟล์)', 'variant' => 'pending'],
    'SIGNED' => ['label' => 'ลงนามแล้ว', 'variant' => 'approved'],
    'REJECTED' => ['label' => 'ไม่อนุมัติ', 'variant' => 'rejected'],
    'CANCELLED' => ['label' => 'ยกเลิก', 'variant' => 'neutral'],
];

$status_options = [
    'all' => 'ทั้งหมด',
    'SUBMITTED' => 'รอพิจารณา',
    'IN_REVIEW' => 'กำลังพิจารณา',
    'RETURNED' => 'ตีกลับแก้ไข',
    'APPROVED_UNSIGNED' => 'อนุมัติ (รอแนบไฟล์)',
    'SIGNED' => 'ลงนามแล้ว',
    'REJECTED' => 'ไม่อนุมัติ',
    'CANCELLED' => 'ยกเลิก',
];

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

$format_thai_datetime_pair = static function (?string $datetime_value) use ($thai_months): array {
    $datetime_value = trim((string) $datetime_value);

    if ($datetime_value === '' || strpos($datetime_value, '0000-00-00') === 0) {
        return ['-', '-'];
    }

    try {
        $date = new DateTime($datetime_value);
    } catch (Throwable $exception) {
        return [$datetime_value, '-'];
    }

    $day = (int) $date->format('j');
    $month = (int) $date->format('n');
    $year = (int) $date->format('Y') + 543;

    return [
        trim($day . ' ' . ($thai_months[$month] ?? '') . ' ' . $year),
        $date->format('H:i') . ' น.',
    ];
};

$format_thai_datetime_inline = static function (?string $datetime_value) use ($format_thai_datetime_pair): string {
    [$date_line, $time_line] = $format_thai_datetime_pair($datetime_value);

    if ($date_line === '-') {
        return '-';
    }

    return trim($date_line . ' ' . $time_line);
};

$truncate_subject = static function (string $value, int $limit = 90): string {
    $value = trim($value);

    if ($value === '') {
        return '-';
    }

    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        if (mb_strlen($value, 'UTF-8') <= $limit) {
            return $value;
        }

        return mb_substr($value, 0, $limit, 'UTF-8') . '...';
    }

    if (strlen($value) <= $limit) {
        return $value;
    }

    return substr($value, 0, $limit) . '...';
};

ob_start();
?>
<style>
    .memo-inbox-table tbody td:first-child {
        min-width: 320px;
    }

    .memo-inbox-subject {
        margin: 0;
        color: #121212;
        font-weight: 600;
        line-height: 1.45;
        word-break: break-word;
    }

    .memo-inbox-subtext {
        margin: 4px 0 0;
        color: #5b6470;
        font-size: 0.92rem;
    }

    .memo-inbox-date-col {
        text-align: left;
        white-space: nowrap;
    }

    .memo-inbox-date-col p {
        margin: 0;
    }

    .memo-inbox-date-col .detail-subtext {
        color: #5b6470;
        font-size: 0.92rem;
    }

    .memo-inbox-status {
        display: flex;
        flex-direction: column;
        gap: 6px;
        align-items: flex-start;
    }

    .memo-inbox-action {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .memo-inbox-modal .modal-content {
        max-width: 920px;
        width: min(92vw, 920px);
    }

    .memo-inbox-modal .memo-detail textarea[readonly] {
        min-height: 180px;
        resize: none;
        overflow: hidden;
    }

    .memo-inbox-modal-note {
        display: none;
        margin-top: 18px;
    }

    .memo-inbox-modal-note.is-visible {
        display: block;
    }

    .memo-inbox-attachments {
        display: grid;
        gap: 12px;
    }

    .memo-inbox-attachment-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 12px 14px;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        background: #fff;
    }

    .memo-inbox-attachment-name {
        color: #121212;
        font-weight: 500;
        word-break: break-word;
    }

    .memo-inbox-attachment-link {
        color: #175cd3;
        font-weight: 600;
        text-decoration: none;
        white-space: nowrap;
    }

    .memo-inbox-modal-footer {
        display: flex;
        justify-content: flex-end;
        padding: 20px 24px 24px;
    }

    .memo-inbox-modal-footer .button-open-workflow,
    .memo-inbox-modal-footer .booking-action-btn {
        min-width: 180px;
        justify-content: center;
    }
</style>

<div class="content-header">
    <h1>ยินดีต้อนรับ</h1>
    <p>บันทึกข้อความ / กล่องบันทึกข้อความ</p>
</div>

<div class="enterprise-tabs">
    <a class="enterprise-tab" href="<?= h($memo_page_my) ?>">บันทึกข้อความของฉัน</a>
    <a class="enterprise-tab active" href="<?= h($memo_page_inbox) ?>">กล่องบันทึกข้อความ</a>
    <a class="enterprise-tab" href="<?= h($memo_page_archive) ?>">บันทึกข้อความที่จัดเก็บ</a>
</div>

<section class="enterprise-card">
    <div class="enterprise-card-header">
        <div class="enterprise-card-title-group">
            <h2 class="enterprise-card-title">ค้นหาและกรองรายการ</h2>
        </div>
    </div>

    <form method="GET" action="memo-inbox.php" class="circular-my-filter-grid" id="memoInboxFilterForm">
        <div class="approval-filter-group">
            <div class="room-admin-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input class="form-input" type="search" name="q" value="<?= h($search) ?>" placeholder="ค้นหาเลขที่หรือเรื่อง" autocomplete="off">
            </div>
            <div class="room-admin-filter">
                <div class="custom-select-wrapper">
                    <div class="custom-select-trigger">
                        <p class="select-value"><?= h($status_options[$status_filter] ?? 'ทั้งหมด') ?></p>
                        <i class="fa-solid fa-chevron-down"></i>
                    </div>
                    <div class="custom-options">
                        <?php foreach ($status_options as $option_value => $option_label) : ?>
                            <div class="custom-option<?= $status_filter === $option_value ? ' selected' : '' ?>" data-value="<?= h($option_value) ?>"><?= h($option_label) ?></div>
                        <?php endforeach; ?>
                    </div>
                    <select class="form-input" name="status">
                        <?php foreach ($status_options as $option_value => $option_label) : ?>
                            <option value="<?= h($option_value) ?>" <?= $status_filter === $option_value ? 'selected' : '' ?>><?= h($option_label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </form>

    <div class="enterprise-card-header">
        <div class="enterprise-card-title-group">
            <h2 class="enterprise-card-title">รายการรอพิจารณา/ลงนาม</h2>
            <p class="enterprise-card-subtitle">กำลังแสดง <?= h((string) $filtered_total) ?> รายการ</p>
        </div>
    </div>

    <div class="table-responsive">
        <table class="custom-table booking-table memo-inbox-table">
            <thead>
                <tr>
                    <th>เรื่อง</th>
                    <th>ผู้เสนอแฟ้ม</th>
                    <th class="memo-inbox-date-col">วันที่เสนอแฟ้ม</th>
                    <th>สถานะ</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)) : ?>
                    <tr>
                        <td colspan="5" class="booking-empty">ไม่พบรายการในกล่องบันทึกข้อความ</td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($items as $item) : ?>
                        <?php
                        $memo_id = (int) ($item['memoID'] ?? 0);
                        $subject = trim((string) ($item['subject'] ?? ''));
                        $status = strtoupper(trim((string) ($item['status'] ?? '')));
                        $status_meta = $status_map[$status] ?? ['label' => ($status !== '' ? $status : '-'), 'variant' => 'neutral'];
                        $creator_name = trim((string) ($item['creatorName'] ?? ''));
                        $memo_no = trim((string) ($item['memoNo'] ?? ''));
                        $detail = trim((string) ($item['detail'] ?? ''));
                        $review_note = trim((string) ($item['reviewNote'] ?? ''));
                        $to_type = strtoupper(trim((string) ($item['toType'] ?? '')));
                        $approver_name = trim((string) ($item['approverName'] ?? ''));

                        if ($approver_name === '' && $to_type === 'DIRECTOR') {
                            $approver_name = 'ผู้อำนวยการ/รักษาการ';
                        }

                        if ($approver_name === '') {
                            $approver_name = '-';
                        }

                        $submitted_at = trim((string) ($item['submittedAt'] ?? ''));
                        $created_at = trim((string) ($item['createdAt'] ?? ''));
                        [$submitted_date, $submitted_time] = $format_thai_datetime_pair($submitted_at !== '' ? $submitted_at : $created_at);
                        $submitted_inline = $format_thai_datetime_inline($submitted_at !== '' ? $submitted_at : $created_at);
                        $reviewed_inline = $format_thai_datetime_inline((string) ($item['reviewedAt'] ?? ''));
                        $is_read = trim((string) ($item['firstReadAt'] ?? '')) !== '';
                        $view_href = $memo_page_view . '?memo_id=' . $memo_id;
                        $attachments = $memo_id > 0 ? memo_get_attachments($memo_id) : [];
                        $attachment_payload = [];

                        foreach ($attachments as $attachment) {
                            $file_id = (int) ($attachment['fileID'] ?? 0);

                            if ($file_id <= 0) {
                                continue;
                            }

                            $attachment_payload[] = [
                                'fileID' => $file_id,
                                'fileName' => (string) ($attachment['fileName'] ?? ''),
                            ];
                        }

                        $attachment_json = json_encode($attachment_payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                        if ($attachment_json === false) {
                            $attachment_json = '[]';
                        }
                        ?>
                        <tr>
                            <td>
                                <p class="memo-inbox-subject"><?= h($truncate_subject($subject)) ?></p>
                                <p class="memo-inbox-subtext"><?= h($memo_no !== '' ? ('เลขที่ ' . $memo_no) : ('#' . $memo_id)) ?></p>
                            </td>
                            <td><?= h($creator_name !== '' ? $creator_name : '-') ?></td>
                            <td class="memo-inbox-date-col">
                                <p><?= h($submitted_date) ?></p>
                                <p class="detail-subtext"><?= h($submitted_time) ?></p>
                            </td>
                            <td>
                                <div class="memo-inbox-status">
                                    <span class="status-pill <?= h((string) ($status_meta['variant'] ?? 'neutral')) ?>">
                                        <?= h((string) ($status_meta['label'] ?? '-')) ?>
                                    </span>
                                    <span class="detail-subtext"><?= h($is_read ? 'อ่านแล้ว' : 'ยังไม่อ่าน') ?></span>
                                </div>
                            </td>
                            <td>
                                <div class="memo-inbox-action">
                                    <button
                                        type="button"
                                        class="booking-action-btn secondary js-open-memo-inbox-modal"
                                        data-memo-id="<?= h((string) $memo_id) ?>"
                                        data-subject="<?= h($subject !== '' ? $subject : '-') ?>"
                                        data-sender="<?= h($creator_name !== '' ? $creator_name : '-') ?>"
                                        data-approver="<?= h($approver_name) ?>"
                                        data-status="<?= h((string) ($status_meta['label'] ?? '-')) ?>"
                                        data-submitted="<?= h($submitted_inline) ?>"
                                        data-reviewed="<?= h($reviewed_inline) ?>"
                                        data-detail-b64="<?= h(base64_encode($detail)) ?>"
                                        data-review-note-b64="<?= h(base64_encode($review_note)) ?>"
                                        data-attachments="<?= h($attachment_json) ?>"
                                        data-view-href="<?= h($view_href) ?>">
                                        <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                        <span class="tooltip">รายละเอียด</span>
                                    </button>
                                    <a class="booking-action-btn secondary" href="<?= h($view_href) ?>">
                                        <i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i>
                                        <span class="tooltip">อ่าน/ดำเนินการ</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($total_pages > 1) : ?>
        <?php component_render('pagination', [
            'page' => $page,
            'total_pages' => $total_pages,
            'base_url' => $pagination_base_url,
            'class' => 'u-mt-2',
        ]); ?>
    <?php endif; ?>
</section>

<div class="modal-overlay-memo details memo-inbox-modal" id="memoInboxModalOverlay" style="display: none;">
    <div class="modal-content">
        <div class="header-modal">
            <p>รายละเอียดบันทึกข้อความ</p>
            <i class="fa-solid fa-xmark" id="closeMemoInboxModal" aria-hidden="true"></i>
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
                        <p><strong>ผู้เสนอแฟ้ม</strong></p>
                        <input type="text" id="memoInboxModalSender" value="-" disabled>
                        <p><strong>โรงเรียนดีบุกพังงาวิทยายน</strong></p>
                    </div>

                    <div class="form-group-row memo-subject-row">
                        <p><strong>เรื่อง</strong></p>
                        <input type="text" id="memoInboxModalSubject" value="-" disabled>
                    </div>

                    <div class="form-group-row memo-to-row">
                        <p><strong>เรียน</strong></p>
                        <p id="memoInboxModalApprover">-</p>
                    </div>

                    <div class="form-group-row">
                        <p><strong>วันที่เสนอแฟ้ม</strong></p>
                        <input type="text" id="memoInboxModalSubmittedAt" value="-" disabled>
                    </div>

                    <div class="form-group-row">
                        <p><strong>สถานะ</strong></p>
                        <input type="text" id="memoInboxModalStatus" value="-" disabled>
                    </div>

                    <div class="content-editor">
                        <p><strong>รายละเอียด:</strong></p>
                        <textarea id="memoInboxModalDetail" readonly></textarea>
                    </div>

                    <div class="content-editor memo-inbox-modal-note" id="memoInboxModalNoteSection">
                        <p><strong>ความเห็นผู้พิจารณา:</strong></p>
                        <textarea id="memoInboxModalNote" readonly></textarea>
                    </div>

                    <div class="content-file-sec">
                        <p><strong>ไฟล์เอกสารแนบจากระบบ</strong></p>
                        <div class="memo-inbox-attachments" id="memoInboxModalAttachments">
                            <p class="existing-file-empty">ยังไม่มีไฟล์แนบ</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="memo-inbox-modal-footer">
            <a class="booking-action-btn secondary" id="memoInboxModalActionLink" href="<?= h($memo_page_view) ?>">
                <i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i>
                <span>อ่าน/ดำเนินการ</span>
            </a>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const filterForm = document.getElementById('memoInboxFilterForm');
        const searchInput = filterForm ? filterForm.querySelector('input[name="q"]') : null;
        const statusSelect = filterForm ? filterForm.querySelector('select[name="status"]') : null;
        let searchTimer = null;

        if (searchInput && filterForm) {
            searchInput.addEventListener('input', () => {
                if (searchTimer) {
                    window.clearTimeout(searchTimer);
                }
                searchTimer = window.setTimeout(() => filterForm.submit(), 300);
            });
        }

        statusSelect?.addEventListener('change', () => filterForm?.submit());

        const overlay = document.getElementById('memoInboxModalOverlay');
        const closeBtn = document.getElementById('closeMemoInboxModal');
        const openButtons = document.querySelectorAll('.js-open-memo-inbox-modal');
        const senderInput = document.getElementById('memoInboxModalSender');
        const subjectInput = document.getElementById('memoInboxModalSubject');
        const approverLabel = document.getElementById('memoInboxModalApprover');
        const submittedInput = document.getElementById('memoInboxModalSubmittedAt');
        const statusInput = document.getElementById('memoInboxModalStatus');
        const detailTextarea = document.getElementById('memoInboxModalDetail');
        const noteSection = document.getElementById('memoInboxModalNoteSection');
        const noteTextarea = document.getElementById('memoInboxModalNote');
        const attachmentsHost = document.getElementById('memoInboxModalAttachments');
        const actionLink = document.getElementById('memoInboxModalActionLink');

        const decodeBase64Utf8 = (value) => {
            const raw = String(value || '').trim();

            if (raw === '') {
                return '';
            }

            try {
                const binary = window.atob(raw);
                const bytes = Uint8Array.from(binary, (char) => char.charCodeAt(0));

                if (window.TextDecoder) {
                    return new TextDecoder().decode(bytes);
                }

                let result = '';
                bytes.forEach((byte) => {
                    result += String.fromCharCode(byte);
                });
                return result;
            } catch (error) {
                return '';
            }
        };

        const resizeTextarea = (textarea) => {
            if (!textarea) {
                return;
            }

            textarea.style.height = 'auto';
            textarea.style.height = Math.max(textarea.scrollHeight, 120) + 'px';
        };

        const renderAttachments = (memoId, rawJson) => {
            if (!attachmentsHost) {
                return;
            }

            let items = [];

            try {
                items = JSON.parse(String(rawJson || '[]'));
            } catch (error) {
                items = [];
            }

            if (!Array.isArray(items) || items.length === 0) {
                attachmentsHost.innerHTML = '<p class="existing-file-empty">ยังไม่มีไฟล์แนบ</p>';
                return;
            }

            attachmentsHost.innerHTML = items.map((item) => {
                const fileId = Number(item && item.fileID ? item.fileID : 0);
                const fileName = String(item && item.fileName ? item.fileName : '-');

                if (fileId <= 0) {
                    return '';
                }

                const href = 'public/api/file-download.php?module=memos&entity_id='
                    + encodeURIComponent(String(memoId))
                    + '&file_id='
                    + encodeURIComponent(String(fileId));

                return '<div class="memo-inbox-attachment-item">'
                    + '<span class="memo-inbox-attachment-name">' + fileName.replace(/[&<>"]/g, (char) => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;'}[char])) + '</span>'
                    + '<a class="memo-inbox-attachment-link" href="' + href + '" target="_blank" rel="noopener">ดูไฟล์</a>'
                    + '</div>';
            }).join('');
        };

        const setModalText = (element, value) => {
            if (!element) {
                return;
            }

            if ('value' in element) {
                element.value = value;
                return;
            }

            element.textContent = value;
        };

        const openModal = (button) => {
            const memoId = String(button.getAttribute('data-memo-id') || '').trim();
            const subject = String(button.getAttribute('data-subject') || '-').trim() || '-';
            const sender = String(button.getAttribute('data-sender') || '-').trim() || '-';
            const approver = String(button.getAttribute('data-approver') || '-').trim() || '-';
            const status = String(button.getAttribute('data-status') || '-').trim() || '-';
            const submitted = String(button.getAttribute('data-submitted') || '-').trim() || '-';
            const detail = decodeBase64Utf8(button.getAttribute('data-detail-b64'));
            const reviewNote = decodeBase64Utf8(button.getAttribute('data-review-note-b64'));
            const viewHref = String(button.getAttribute('data-view-href') || 'memo-view.php').trim() || 'memo-view.php';

            setModalText(senderInput, sender);
            setModalText(subjectInput, subject);
            setModalText(approverLabel, approver);
            setModalText(submittedInput, submitted);
            setModalText(statusInput, status);
            setModalText(detailTextarea, detail !== '' ? detail : '-');
            resizeTextarea(detailTextarea);

            if (reviewNote !== '') {
                noteSection?.classList.add('is-visible');
                setModalText(noteTextarea, reviewNote);
                resizeTextarea(noteTextarea);
            } else {
                noteSection?.classList.remove('is-visible');
                setModalText(noteTextarea, '');
                resizeTextarea(noteTextarea);
            }

            renderAttachments(memoId, button.getAttribute('data-attachments'));

            if (actionLink) {
                actionLink.setAttribute('href', viewHref);
            }

            if (overlay) {
                overlay.style.display = 'flex';
            }
        };

        openButtons.forEach((button) => {
            button.addEventListener('click', () => openModal(button));
        });

        closeBtn?.addEventListener('click', () => {
            if (overlay) {
                overlay.style.display = 'none';
            }
        });

        overlay?.addEventListener('click', (event) => {
            if (event.target === overlay) {
                overlay.style.display = 'none';
            }
        });
    });
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';

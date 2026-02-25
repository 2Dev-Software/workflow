<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../auth/csrf.php';

$items = (array) ($items ?? []);
$is_registry = (bool) ($is_registry ?? false);

$rows = [];

foreach ($items as $item) {
    $status = (string) ($item['status'] ?? '');
    $is_waiting = $status === OUTGOING_STATUS_WAITING_ATTACHMENT;

    $rows[] = [
        (string) ($item['outgoingNo'] ?? ''),
        (string) ($item['subject'] ?? ''),
        [
            'component' => [
                'name' => 'status-pill',
                'params' => [
                    'label' => $is_waiting ? 'รอแนบไฟล์' : 'สมบูรณ์',
                    'variant' => $is_waiting ? 'pending' : 'approved',
                ],
            ],
        ],
        [
            'component' => [
                'name' => 'outgoing-attach-form',
                'params' => [
                    'outgoing_id' => (int) ($item['outgoingID'] ?? 0),
                    'enabled' => $is_waiting && $is_registry,
                    'locked' => !$is_registry,
                ],
            ],
        ],
    ];
}

$items = (array) ($items ?? []);
$page = (int) ($page ?? 1);
$total_pages = (int) ($total_pages ?? 1);
$search = trim((string) ($search ?? ''));
$status_filter = (string) ($status_filter ?? 'all');
$filtered_total = (int) ($filtered_total ?? 0);
$pagination_base_url = (string) ($pagination_base_url ?? 'memo-archive.php');

$memo_page_my = 'memo.php';
$memo_page_inbox = 'memo-inbox.php';
$memo_page_archive = 'memo-archive.php';
$memo_page_view = 'memo-view.php';

$status_map = [
    'DRAFT' => ['label' => 'ร่าง', 'variant' => 'neutral'],
    'SUBMITTED' => ['label' => 'รอพิจารณา', 'variant' => 'warning'],
    'IN_REVIEW' => ['label' => 'กำลังพิจารณา', 'variant' => 'warning'],
    'RETURNED' => ['label' => 'ตีกลับแก้ไข', 'variant' => 'danger'],
    'APPROVED_UNSIGNED' => ['label' => 'อนุมัติ (รอแนบไฟล์)', 'variant' => 'warning'],
    'SIGNED' => ['label' => 'ลงนามแล้ว', 'variant' => 'success'],
    'REJECTED' => ['label' => 'ไม่อนุมัติ', 'variant' => 'danger'],
    'CANCELLED' => ['label' => 'ยกเลิก', 'variant' => 'neutral'],
];

$status_options = [
    'all' => 'ทั้งหมด',
    'DRAFT' => 'ร่าง',
    'SUBMITTED' => 'รอพิจารณา',
    'IN_REVIEW' => 'กำลังพิจารณา',
    'RETURNED' => 'ตีกลับแก้ไข',
    'APPROVED_UNSIGNED' => 'อนุมัติ (รอแนบไฟล์)',
    'SIGNED' => 'ลงนามแล้ว',
    'REJECTED' => 'ไม่อนุมัติ',
    'CANCELLED' => 'ยกเลิก',
];

$rows = [];

foreach ($items as $item) {
    $memo_id = (int) ($item['memoID'] ?? 0);
    $memo_no = trim((string) ($item['memoNo'] ?? ''));
    $status = (string) ($item['status'] ?? '');
    $status_meta = $status_map[$status] ?? ['label' => $status !== '' ? $status : '-', 'variant' => 'neutral'];
    $approver = trim((string) ($item['approverName'] ?? ''));
    $approver = $approver !== '' ? $approver : '-';
    $view_href = $memo_page_view;
    $view_href .= (strpos($view_href, '?') === false ? '?' : '&') . 'memo_id=' . $memo_id;

    $rows[] = [
        $memo_no !== '' ? $memo_no : ('#' . $memo_id),
        [
            'link' => [
                'href' => $view_href,
                'label' => (string) ($item['subject'] ?? ''),
            ],
        ],
        $approver,
        [
            'component' => [
                'name' => 'badge',
                'params' => [
                    'label' => $status_meta['label'],
                    'variant' => $status_meta['variant'],
                ],
            ],
        ],
        (string) ($item['createdAt'] ?? ''),
        [
            'form' => [
                'method' => 'post',
                'action' => $memo_page_archive,
                'hidden' => [
                    'action' => 'unarchive',
                    'memo_id' => $memo_id,
                ],
                'button' => [
                    'label' => 'นำออก',
                    'variant' => 'secondary',
                    'type' => 'submit',
                ],
            ],
        ],
    ];
}

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

require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../auth/csrf.php';

$items = (array) ($items ?? []);
$box_key = (string) ($box_key ?? 'normal');
$filter_type = (string) ($filter_type ?? 'all');
$filter_read = (string) ($filter_read ?? 'all');
$filter_sort = (string) ($filter_sort ?? 'newest');
$filter_view = (string) ($filter_view ?? 'table1');
$filter_search = (string) ($filter_search ?? '');
$is_outside_view = (bool) ($is_outside_view ?? false);
$director_label = (string) ($director_label ?? 'ผอ./รักษาการ');

$type_external_checked = $filter_type === 'external' || $filter_type === 'all';
$type_internal_checked = $filter_type === 'internal' || $filter_type === 'all';
$read_checked = $filter_read === 'read' || $filter_read === 'all';
$unread_checked = $filter_read === 'unread' || $filter_read === 'all';

ob_start();
?>
<div class="content-header">
    <h1>หนังสือออกภายนอก</h1>
    <p>รายการหนังสือส่งออก</p>
</div>

<header class="header-outgoing">
    <div class="outgoing-control">
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
</header>

<section class="content-outgoing-table" data-circular-notice>
    <div class="search-bar">
        <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="search-input" name="q" form="circularFilterForm" value="<?= h($filter_search) ?>" placeholder="ค้นหาข้อความด้วย...">
        </div>
    </div>

    <div class="table-outgoing">
        <table>
            <thead>
                <tr>
                    <th>ประเภทหนังสือ</th>
                    <th>เรื่อง</th>
                    <th>ผู้เสนอแฟ้ม</th>
                    <th>วันที่เสนอแฟ้ม</th>
                    <th>สถานะ</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>

                <!-- MOCK-UP DATA -->
                <tr>
                    <td>ภายใน</td>
                    <td>เอกสารราชการ</td>
                    <td>นางสาวกานต์พิชชา ปานสาว</td>
                    <td>03/02/2569</td>
                    <td><span class="status-badge read">อ่านแล้ว</span></td>
                    <td>
                        <button type="button" class="booking-action-btn secondary js-open-edit-modal" data-vehicle-approval-action="detail" data-vehicle-booking-action="detail" data-vehicle-booking-id="4">

                            <i class="fa-solid fa-eye" aria-hidden="true"></i>
                            <span class="tooltip">ดูรายละเอียด</span>
                        </button>
                    </td>
                </tr>

                <!-- <?php //if (empty($items)) : 
                        ?>
                        <tr>
                            <td colspan="6" class="booking-empty">ไม่มีรายการ</td>
                        </tr>
                    <?php //else : 
                    ?>
                        <?php //foreach ($items as $item) : 
                        ?>
                            <?php //$file_json = (string) ($item['files_json'] ?? '[]'); 
                            ?>
                            <tr>
                                <td><?= h((string) ($item['type_label'] ?? '')) ?></td>
                                <td><?= h((string) ($item['subject'] ?? '')) ?></td>
                                <td><?= h((string) ($item['sender_name'] ?? '-')) ?></td>
                                <td><?= h((string) ($item['delivered_date'] ?? '-')) ?></td>
                                <td><span class="status-badge <?= h(($item['is_read'] ?? false) ? 'read' : 'unread') ?>"><?= h(($item['is_read'] ?? false) ? 'อ่านแล้ว' : 'ยังไม่อ่าน') ?></span></td>
                                <td>
                                    <button
                                        class="booking-action-btn secondary js-open-circular-modal"
                                        type="button"
                                        data-circular-id="<?= h((string) (int) ($item['circular_id'] ?? 0)) ?>"
                                        data-inbox-id="<?= h((string) (int) ($item['inbox_id'] ?? 0)) ?>"
                                        data-subject="<?= h((string) ($item['subject'] ?? '')) ?>"
                                        data-sender="<?= h((string) ($item['sender_name'] ?? '-')) ?>"
                                        data-date="<?= h((string) ($item['delivered_date_long'] ?? $item['delivered_date'] ?? '-')) ?>"
                                        data-detail="<?= h((string) ($item['detail'] ?? '')) ?>"
                                        data-link="<?= h((string) ($item['link_url'] ?? '')) ?>"
                                        data-type="<?= h((string) ($item['type_label'] ?? '')) ?>"
                                        data-files="<?= h($file_json) ?>">
                                        <i class="fa-solid fa-eye"></i>
                                        <span class="tooltip">ดูรายละเอียด</span>
                                    </button>
                                </td>
                            </tr>
                        <?php //endforeach; 
                        ?>
                    <?php //endif; 
                    ?> -->
            </tbody>
        </table>
    </div>

    <div class="modal-overlay-outgoing details" id="modalEditOverlay" style="display: none;">
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
                                <!-- <img src="<?= h($signature_src) ?>" alt="">
                                    <p>(<?= h($current_name !== '' ? $current_name : '-') ?>)</p>
                                    <p><?= h($current_position !== '' ? $current_position : '-') ?></p> -->
                                <img src="assets/img/signature/1829900159722/signature_20260211_170950_6f853801016c.png" alt="">
                                <p>(นางสาวกนกรัตน์ บุญถาวร)</p>
                                <p>เจ้าหน้าที่</p>
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
</section>

<div class="button-outgoing"></div>

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

    const openEditBtns = document.querySelectorAll('.js-open-edit-modal');
    const closeEditBtn = document.getElementById('closeModalEdit');
    const editModal = document.getElementById('modalEditOverlay');
    openEditBtns.forEach((btn) => {
        btn.addEventListener('click', (event) => {
            event.preventDefault();

            if (editModal) editModal.style.display = 'flex';
        });
    });
    closeEditBtn?.addEventListener('click', () => {
        if (editModal) editModal.style.display = 'none';
    });

    window.addEventListener('click', (event) => {
        if (event.target === editModal) {
            editModal.style.display = 'none';
        }
    });
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';

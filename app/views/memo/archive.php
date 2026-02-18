<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../auth/csrf.php';

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

ob_start();
?>
<div class="content-header">
    <h1>ที่จัดเก็บบันทึกข้อความ</h1>
    <p>แฟ้มจัดเก็บ (Archived)</p>
</div>

<div class="enterprise-tabs">
    <a class="enterprise-tab" href="<?= h($memo_page_my) ?>">บันทึกของฉัน</a>
    <a class="enterprise-tab" href="<?= h($memo_page_inbox) ?>">Inbox ผู้พิจารณา</a>
    <a class="enterprise-tab is-active" href="<?= h($memo_page_archive) ?>">ที่จัดเก็บ</a>
</div>

<section class="booking-card booking-list-card">
    <div class="booking-card-header">
        <div class="booking-card-title-group">
            <h2 class="booking-card-title">รายการที่จัดเก็บ</h2>
            <p class="booking-card-subtitle">กำลังแสดง <?= h((string) $filtered_total) ?> รายการ</p>
        </div>
    </div>

    <form class="booking-form booking-filter-form" method="get" action="<?= h($memo_page_archive) ?>">
        <div class="booking-form-grid">
            <?php component_render('input', [
                'name' => 'q',
                'label' => 'ค้นหา',
                'placeholder' => 'เลขที่ / เรื่อง',
                'value' => $search,
                'attrs' => ['autocomplete' => 'off'],
            ]); ?>
            <?php component_render('select', [
                'name' => 'status',
                'label' => 'สถานะ',
                'options' => $status_options,
                'selected' => $status_filter,
            ]); ?>
            <div class="c-field form-group full">
                <div class="booking-actions">
                    <?php component_render('button', [
                        'label' => 'ค้นหา',
                        'variant' => 'primary',
                        'type' => 'submit',
                    ]); ?>
                    <?php component_render('button', [
                        'label' => 'ล้างตัวกรอง',
                        'variant' => 'secondary',
                        'href' => $memo_page_archive,
                    ]); ?>
                </div>
            </div>
        </div>
    </form>

    <?php if (empty($items)) : ?>
        <?php component_render('empty-state', [
            'title' => 'ยังไม่มีรายการที่จัดเก็บ',
            'message' => 'เมื่อปิดงานแล้ว คุณสามารถกด "จัดเก็บ" ในหน้ารายละเอียดได้',
        ]); ?>
    <?php else : ?>
        <?php component_render('table', [
            'headers' => ['เลขที่', 'เรื่อง', 'ผู้พิจารณา', 'สถานะ', 'เวลา', ''],
            'rows' => $rows,
            'empty_text' => 'ไม่มีรายการ',
        ]); ?>
        <?php component_render('pagination', [
            'page' => $page,
            'total_pages' => $total_pages,
            'base_url' => $pagination_base_url,
            'class' => 'u-mt-2',
        ]); ?>
    <?php endif; ?>
</section>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';

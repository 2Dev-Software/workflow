<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../auth/csrf.php';

$items = (array) ($items ?? []);
$page = (int) ($page ?? 1);
$total_pages = (int) ($total_pages ?? 1);
$search = trim((string) ($search ?? ''));
$status_filter = (string) ($status_filter ?? 'all');
$filtered_total = (int) ($filtered_total ?? 0);
$pagination_base_url = (string) ($pagination_base_url ?? 'memo-inbox.php');

$memo_page_my = 'memo.php';
$memo_page_inbox = 'memo-inbox.php';
$memo_page_archive = 'memo-archive.php';
$memo_page_view = 'memo-view.php';

$status_map = [
    'SUBMITTED' => ['label' => 'รอพิจารณา', 'variant' => 'warning'],
    'IN_REVIEW' => ['label' => 'กำลังพิจารณา', 'variant' => 'warning'],
    'RETURNED' => ['label' => 'ตีกลับแก้ไข', 'variant' => 'danger'],
    'APPROVED_UNSIGNED' => ['label' => 'อนุมัติ (รอแนบไฟล์)', 'variant' => 'warning'],
    'SIGNED' => ['label' => 'ลงนามแล้ว', 'variant' => 'success'],
    'REJECTED' => ['label' => 'ไม่อนุมัติ', 'variant' => 'danger'],
    'CANCELLED' => ['label' => 'ยกเลิก', 'variant' => 'neutral'],
    'DRAFT' => ['label' => 'ร่าง', 'variant' => 'neutral'],
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

$rows = [];
foreach ($items as $item) {
    $memo_id = (int) ($item['memoID'] ?? 0);
    $status = (string) ($item['status'] ?? '');
    $status_meta = $status_map[$status] ?? ['label' => $status !== '' ? $status : '-', 'variant' => 'neutral'];
    $memo_no = trim((string) ($item['memoNo'] ?? ''));
    $is_read = !empty($item['firstReadAt']);
    $view_href = $memo_page_view;
    $view_href .= (strpos($view_href, '?') === false ? '?' : '&') . 'memo_id=' . $memo_id;

    $rows[] = [
        [
            'component' => [
                'name' => 'badge',
                'params' => [
                    'label' => $is_read ? 'อ่านแล้ว' : 'ยังไม่อ่าน',
                    'variant' => $is_read ? 'success' : 'warning',
                ],
            ],
        ],
        $memo_no !== '' ? $memo_no : ('#' . $memo_id),
        [
            'link' => [
                'href' => $view_href,
                'label' => (string) ($item['subject'] ?? ''),
            ],
        ],
        (string) ($item['creatorName'] ?? ''),
        (string) (($item['submittedAt'] ?? '') !== '' ? ($item['submittedAt'] ?? '') : ($item['createdAt'] ?? '')),
        [
            'component' => [
                'name' => 'badge',
                'params' => [
                    'label' => $status_meta['label'],
                    'variant' => $status_meta['variant'],
                ],
            ],
        ],
        [
            'component' => [
                'name' => 'button',
                'params' => [
                    'label' => 'พิจารณา',
                    'variant' => 'primary',
                    'href' => $view_href,
                ],
            ],
        ],
    ];
}

ob_start();
?>
<div class="content-header">
    <h1>Inbox บันทึกข้อความ</h1>
    <p>รายการรอพิจารณา/ลงนาม และประวัติการพิจารณา</p>
</div>

<div class="enterprise-tabs">
    <a class="enterprise-tab" href="<?= h($memo_page_my) ?>">บันทึกของฉัน</a>
    <a class="enterprise-tab is-active" href="<?= h($memo_page_inbox) ?>">Inbox ผู้พิจารณา</a>
    <a class="enterprise-tab" href="<?= h($memo_page_archive) ?>">ที่จัดเก็บ</a>
</div>

<section class="booking-card booking-list-card">
    <div class="booking-card-header">
        <div class="booking-card-title-group">
            <h2 class="booking-card-title">รายการบันทึกข้อความ</h2>
            <p class="booking-card-subtitle">กำลังแสดง <?= h((string) $filtered_total) ?> รายการ</p>
        </div>
    </div>

    <form class="booking-form booking-filter-form" method="get" action="<?= h($memo_page_inbox) ?>">
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
                        'href' => $memo_page_inbox,
                    ]); ?>
                </div>
            </div>
        </div>
    </form>

    <?php if (empty($items)) : ?>
        <?php component_render('empty-state', [
            'title' => 'ไม่มีรายการใน Inbox',
            'message' => 'ยังไม่มีบันทึกข้อความที่ถูกส่งมาให้พิจารณา',
        ]); ?>
    <?php else : ?>
        <?php component_render('table', [
            'headers' => ['อ่านแล้ว', 'เลขที่', 'เรื่อง', 'ผู้เสนอ', 'วันที่ส่ง', 'สถานะ', ''],
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

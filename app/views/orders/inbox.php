<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../auth/csrf.php';

$items = (array) ($items ?? []);
$archived = (bool) ($archived ?? false);
$page = (int) ($page ?? 1);
$total_pages = (int) ($total_pages ?? 1);
$search = trim((string) ($search ?? ''));
$status_filter = (string) ($status_filter ?? 'all');
$sort = (string) ($sort ?? 'newest');
$per_page = (string) ($per_page ?? '10');
$summary = (array) ($summary ?? []);
$summary_total = (int) ($summary['total'] ?? 0);
$summary_read = (int) ($summary['read'] ?? 0);
$summary_unread = (int) ($summary['unread'] ?? 0);
$filtered_total = (int) ($filtered_total ?? 0);
$pagination_base_url = (string) ($pagination_base_url ?? ('orders-inbox.php?archived=' . ($archived ? '1' : '0')));

$status_options = [
    'all' => 'ทั้งหมด',
    'unread' => 'ยังไม่อ่าน',
    'read' => 'อ่านแล้ว',
];
$sort_options = [
    'newest' => 'ใหม่ไปเก่า',
    'oldest' => 'เก่าไปใหม่',
    'order_no' => 'เลขที่คำสั่ง',
    'unread_first' => 'ยังไม่อ่านก่อน',
];
$per_page_options = [
    '10' => '10',
    '20' => '20',
    '50' => '50',
    'all' => 'ทั้งหมด',
];

$rows = [];

foreach ($items as $item) {
    $is_read = (int) ($item['isRead'] ?? 0) === 1;
    $rows[] = [
        [
            'component' => [
                'name' => 'status-pill',
                'params' => [
                    'label' => $is_read ? 'อ่านแล้ว' : 'ยังไม่อ่าน',
                    'variant' => $is_read ? 'approved' : 'pending',
                ],
            ],
        ],
        (string) ($item['orderNo'] ?? ''),
        [
            'link' => [
                'href' => 'orders-view.php?inbox_id=' . (int) ($item['inboxID'] ?? 0),
                'label' => (string) ($item['subject'] ?? ''),
            ],
        ],
        (string) ($item['senderName'] ?? ''),
        (string) ($item['deliveredAt'] ?? '-'),
        [
            'component' => [
                'name' => 'orders-action-group',
                'params' => [
                    'view_href' => 'orders-view.php?inbox_id=' . (int) ($item['inboxID'] ?? 0),
                    'archive_action' => 'orders-inbox.php?' . http_build_query([
                        'archived' => $archived ? '1' : '0',
                        'q' => $search,
                        'status' => $status_filter,
                        'sort' => $sort,
                        'per_page' => $per_page,
                        'page' => (string) $page,
                    ]),
                    'show_archive' => !$archived,
                    'show_unarchive' => $archived,
                    'inbox_id' => (int) ($item['inboxID'] ?? 0),
                ],
            ],
        ],
    ];
}

$summary_title = $archived ? 'คำสั่งที่จัดเก็บ' : 'กล่องคำสั่ง';
$subtitle_parts = [$summary_title];

if ($search !== '') {
    $subtitle_parts[] = 'ค้นหา "' . $search . '"';
}
if ($status_filter !== 'all') {
    $subtitle_parts[] = 'สถานะ: ' . ($status_options[$status_filter] ?? '');
}
$subtitle = trim(implode(' · ', array_filter($subtitle_parts)));

$unread_query = http_build_query([
    'archived' => $archived ? '1' : '0',
    'status' => 'unread',
    'q' => $search,
    'sort' => $sort,
    'per_page' => $per_page,
]);

ob_start();
?>
<div class="content-header">
    <h1>กล่องคำสั่งราชการ</h1>
    <p><?= h($summary_title) ?></p>
</div>

<div class="enterprise-tabs">
    <a class="enterprise-tab<?= $archived ? '' : ' is-active' ?>" href="orders-inbox.php?archived=0">กล่องเข้า</a>
    <a class="enterprise-tab<?= $archived ? ' is-active' : '' ?>" href="orders-inbox.php?archived=1">ที่จัดเก็บ</a>
</div>

<div class="booking-summary orders-summary">
    <div class="booking-summary-item">
        <h3><?= h((string) $summary_total) ?></h3>
        <p>ทั้งหมด</p>
    </div>
    <div class="booking-summary-item">
        <h3><?= h((string) $summary_unread) ?></h3>
        <p>ยังไม่อ่าน</p>
    </div>
    <div class="booking-summary-item">
        <h3><?= h((string) $summary_read) ?></h3>
        <p>อ่านแล้ว</p>
    </div>
</div>

<section class="booking-card booking-list-card orders-filters" data-orders-inbox>
    <div class="booking-card-header">
        <div class="booking-card-title-group">
            <h2 class="booking-card-title">รายการคำสั่งราชการ</h2>
            <p class="booking-card-subtitle"><?= h($subtitle) ?></p>
        </div>
        <div class="booking-actions">
            <?php component_render('button', [
                'label' => 'ดูเฉพาะยังไม่อ่าน',
                'variant' => 'secondary',
                'href' => 'orders-inbox.php?' . $unread_query,
                'attrs' => [
                    'data-orders-unread-cta' => '1',
                ],
            ]); ?>
        </div>
    </div>

    <form class="booking-form booking-filter-form" method="get" action="orders-inbox.php">
        <input type="hidden" name="archived" value="<?= h($archived ? '1' : '0') ?>">
        <div class="booking-form-grid">
            <?php component_render('input', [
                'name' => 'q',
                'label' => 'ค้นหา',
                'placeholder' => 'เลขที่ / เรื่อง / ผู้ส่ง',
                'value' => $search,
                'attrs' => [
                    'autocomplete' => 'off',
                ],
            ]); ?>
            <?php component_render('select', [
                'name' => 'status',
                'label' => 'สถานะการอ่าน',
                'options' => $status_options,
                'selected' => $status_filter,
            ]); ?>
            <?php component_render('select', [
                'name' => 'sort',
                'label' => 'เรียงตาม',
                'options' => $sort_options,
                'selected' => $sort,
            ]); ?>
            <?php component_render('select', [
                'name' => 'per_page',
                'label' => 'จำนวนต่อหน้า',
                'options' => $per_page_options,
                'selected' => $per_page,
            ]); ?>
            <div class="c-field form-group full">
                <div class="booking-actions">
                    <?php component_render('button', [
                        'label' => 'กรองข้อมูล',
                        'variant' => 'primary',
                        'type' => 'submit',
                    ]); ?>
                    <?php component_render('button', [
                        'label' => 'ล้างตัวกรอง',
                        'variant' => 'secondary',
                        'href' => 'orders-inbox.php?archived=' . ($archived ? '1' : '0'),
                    ]); ?>
                </div>
            </div>
        </div>
    </form>

    <?php if (empty($items)) : ?>
        <?php component_render('empty-state', [
            'title' => 'ไม่มีรายการคำสั่ง',
            'message' => $archived ? 'ยังไม่มีคำสั่งที่จัดเก็บ' : 'ยังไม่มีคำสั่งในกล่องเข้า',
        ]); ?>
    <?php else : ?>
        <p class="booking-note">กำลังแสดง <?= h((string) $filtered_total) ?> รายการ</p>
        <?php component_render('table', [
            'headers' => ['สถานะ', 'เลขที่คำสั่ง', 'เรื่อง', 'ผู้ส่ง', 'วันที่รับ', 'จัดการ'],
            'rows' => $rows,
            'empty_text' => 'ไม่มีรายการ',
        ]); ?>
        <?php if ($per_page !== 'all') : ?>
            <?php component_render('pagination', [
                'page' => $page,
                'total_pages' => $total_pages,
                'base_url' => $pagination_base_url,
                'class' => 'u-mt-2',
            ]); ?>
        <?php endif; ?>
    <?php endif; ?>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';

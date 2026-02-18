<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../auth/csrf.php';

$items = $items ?? [];
$archived = (bool) ($archived ?? false);
$page = (int) ($page ?? 1);
$total_pages = (int) ($total_pages ?? 1);
$search = trim((string) ($search ?? ''));
$status_filter = (string) ($status_filter ?? 'all');
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
        [
            'component' => [
                'name' => 'orders-action-group',
                'params' => [
                    'view_href' => 'orders-view.php?inbox_id=' . (int) ($item['inboxID'] ?? 0),
                    'show_archive' => !$archived,
                    'inbox_id' => (int) ($item['inboxID'] ?? 0),
                ],
            ],
        ],
    ];
}

$summary_title = $archived ? 'คำสั่งที่จัดเก็บ' : 'คำสั่งใหม่';
$subtitle_parts = [$summary_title];

if ($search !== '') {
    $subtitle_parts[] = 'ค้นหา "' . $search . '"';
}

if ($status_filter !== 'all') {
    $subtitle_parts[] = 'สถานะ: ' . ($status_options[$status_filter] ?? '');
}
$subtitle = trim(implode(' · ', array_filter($subtitle_parts)));

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

<section class="booking-card booking-list-card">
    <div class="booking-card-header">
        <div class="booking-card-title-group">
            <h2 class="booking-card-title">รายการคำสั่งราชการ</h2>
            <p class="booking-card-subtitle"><?= h($subtitle) ?></p>
        </div>
        <div class="c-badge-group">
            <?php component_render('badge', [
                'label' => 'ยังไม่อ่าน ' . $summary_unread,
                'variant' => 'warning',
            ]); ?>
            <?php component_render('badge', [
                'label' => 'อ่านแล้ว ' . $summary_read,
                'variant' => 'success',
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
                        'href' => 'orders-inbox.php?archived=' . ($archived ? '1' : '0'),
                    ]); ?>
                </div>
            </div>
        </div>
    </form>

    <?php if (empty($items)) : ?>
        <?php component_render('empty-state', [
            'title' => 'ไม่มีรายการคำสั่ง',
            'message' => $archived ? 'ยังไม่มีคำสั่งที่จัดเก็บ' : 'ยังไม่มีคำสั่งใหม่ในกล่องเข้า',
        ]); ?>
    <?php else : ?>
        <p class="booking-note">กำลังแสดง <?= h((string) $filtered_total) ?> รายการ</p>
        <?php component_render('table', [
            'headers' => ['สถานะ', 'เลขที่คำสั่ง', 'เรื่อง', 'ผู้ส่ง', 'จัดการ'],
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

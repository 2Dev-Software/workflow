<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../auth/csrf.php';
require_once __DIR__ . '/../view.php';

$items = $items ?? [];
$box_key = $box_key ?? 'normal';
$is_registry = (bool) ($is_registry ?? false);
$is_director_box = (bool) ($is_director_box ?? false);

$rows = [];
foreach ($items as $item) {
    $is_read = (int) ($item['isRead'] ?? 0) === 1;
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
        [
            'link' => [
                'href' => 'circular-view.php?inbox_id=' . (int) ($item['inboxID'] ?? 0),
                'label' => (string) ($item['subject'] ?? ''),
            ],
        ],
        (string) ($item['senderName'] ?? ''),
        (string) ($item['deliveredAt'] ?? ''),
        [
            'form' => [
                'method' => 'post',
                'action' => '',
                'hidden' => [
                    'inbox_id' => (int) ($item['inboxID'] ?? 0),
                    'action' => 'unarchive',
                ],
                'button' => [
                    'label' => 'ย้ายกลับ',
                    'variant' => 'ghost',
                    'size' => 'sm',
                ],
            ],
        ],
    ];
}

ob_start();
?>
<div class="content-header">
    <h1>ยินดีต้อนรับ</h1>
    <p>หนังสือเวียน / ที่จัดเก็บ</p>
</div>

<div class="enterprise-tabs">
    <a class="enterprise-tab<?= $box_key === 'normal' ? ' is-active' : '' ?>" href="circular-notice.php?box=normal">กล่องเข้า</a>
    <?php if ($is_director_box) : ?>
        <a class="enterprise-tab<?= $box_key === 'director' ? ' is-active' : '' ?>" href="circular-notice.php?box=director">กล่องพิเศษ ผอ.</a>
    <?php endif; ?>
    <?php if ($is_registry) : ?>
        <a class="enterprise-tab<?= $box_key === 'clerk_return' ? ' is-active' : '' ?>" href="circular-notice.php?box=clerk_return">กล่องคืนจากผอ.</a>
    <?php endif; ?>
    <a class="enterprise-tab" href="circular-notice.php?box=<?= h($box_key) ?>">กลับไปกล่องเข้า</a>
</div>

<section class="enterprise-card c-card">
    <div class="enterprise-card-header">
        <div class="enterprise-card-title-group">
            <h2 class="enterprise-card-title">รายการหนังสือเวียน</h2>
            <p class="enterprise-card-subtitle">กล่องข้อความที่จัดเก็บ</p>
        </div>
    </div>

    <?php if (empty($items)) : ?>
        <?php component_render('empty-state', [
            'title' => 'ยังไม่มีหนังสือที่จัดเก็บ',
            'message' => 'เมื่อจัดเก็บหนังสือแล้วจะแสดงที่นี่',
        ]); ?>
    <?php else : ?>
        <?php component_render('table', [
            'headers' => ['สถานะ', 'เรื่อง', 'ผู้ส่ง', 'เวลา', 'การจัดการ'],
            'rows' => $rows,
            'empty_text' => 'ไม่มีรายการ',
            'class' => 'custom-table',
            'wrap_class' => 'table-responsive',
        ]); ?>
    <?php endif; ?>

    <?php component_render('pagination', [
        'page' => 1,
        'total_pages' => 1,
        'base_url' => 'circular-archive.php',
        'class' => 'u-mt-2',
    ]); ?>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';

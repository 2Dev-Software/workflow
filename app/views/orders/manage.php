<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../auth/csrf.php';

$orders = $orders ?? [];
$page = (int) ($page ?? 1);
$total_pages = (int) ($total_pages ?? 1);
$status_map = [
    ORDER_STATUS_WAITING_ATTACHMENT => ['label' => 'รอแนบไฟล์', 'variant' => 'warning'],
    ORDER_STATUS_COMPLETE => ['label' => 'พร้อมส่ง', 'variant' => 'success'],
    ORDER_STATUS_SENT => ['label' => 'ส่งแล้ว', 'variant' => 'neutral'],
];

$rows = [];
foreach ($orders as $order) {
    $status = (string) ($order['status'] ?? '');
    $status_meta = $status_map[$status] ?? ['label' => $status !== '' ? $status : '-', 'variant' => 'neutral'];
    $read_count = (int) ($order['readCount'] ?? 0);
    $recipient_count = (int) ($order['recipientCount'] ?? 0);

    if ($status === ORDER_STATUS_WAITING_ATTACHMENT) {
        $action_cell = [
            'component' => [
                'name' => 'orders-attach-form',
                'params' => [
                    'order_id' => (int) ($order['orderID'] ?? 0),
                    'button_label' => 'แนบไฟล์',
                ],
            ],
        ];
    } elseif ($status === ORDER_STATUS_COMPLETE) {
        $action_cell = [
            'component' => [
                'name' => 'button',
                'params' => [
                    'label' => 'ส่งคำสั่ง',
                    'variant' => 'primary',
                    'size' => 'sm',
                    'href' => 'orders-send.php?order_id=' . (int) ($order['orderID'] ?? 0),
                ],
            ],
        ];
    } else {
        $action_cell = '-';
    }

    $rows[] = [
        (string) ($order['orderNo'] ?? ''),
        (string) ($order['subject'] ?? ''),
        [
            'component' => [
                'name' => 'badge',
                'params' => [
                    'label' => $status_meta['label'],
                    'variant' => $status_meta['variant'],
                ],
            ],
        ],
        $recipient_count > 0 ? $read_count . '/' . $recipient_count : '-',
        $action_cell,
    ];
}

ob_start();
?>
<div class="content-header">
    <h1>คำสั่งของฉัน</h1>
    <p>จัดการคำสั่ง</p>
</div>

<section class="enterprise-card">
    <div class="enterprise-card-header">
        <div class="enterprise-card-title-group">
            <h2 class="enterprise-card-title">คำสั่งของฉัน</h2>
            <p class="enterprise-card-subtitle">แนบไฟล์และส่งคำสั่ง</p>
        </div>
    </div>

    <?php if (empty($orders)) : ?>
        <?php component_render('empty-state', [
            'title' => 'ไม่มีรายการคำสั่ง',
            'message' => 'ยังไม่มีคำสั่งที่ต้องจัดการในขณะนี้',
        ]); ?>
    <?php else : ?>
        <?php component_render('table', [
            'headers' => ['เลขที่คำสั่ง', 'เรื่อง', 'สถานะ', 'อ่านแล้ว/ทั้งหมด', 'จัดการ'],
            'rows' => $rows,
            'empty_text' => 'ไม่มีรายการ',
        ]); ?>
        <?php component_render('pagination', [
            'page' => $page,
            'total_pages' => $total_pages,
            'base_url' => 'orders-manage.php',
            'class' => 'u-mt-2',
        ]); ?>
    <?php endif; ?>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';

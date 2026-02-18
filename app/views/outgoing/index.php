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

ob_start();
?>
<div class="content-header">
    <h1>หนังสือออกภายนอก</h1>
    <p>รายการหนังสือส่งออก</p>
</div>

<section class="booking-card booking-list-card">
    <div class="booking-card-header">
        <div class="booking-card-title-group">
            <h2 class="booking-card-title">รายการหนังสือออกภายนอก</h2>
            <p class="booking-card-subtitle">ติดตามสถานะหนังสือส่งออก</p>
        </div>
        <?php if ($is_registry) : ?>
            <?php component_render('button', [
                'label' => 'ออกเลขหนังสือใหม่',
                'variant' => 'primary',
                'href' => 'outgoing-create.php',
            ]); ?>
        <?php else : ?>
            <?php component_render('badge', [
                'label' => 'เฉพาะสารบรรณ',
                'variant' => 'warning',
            ]); ?>
        <?php endif; ?>
    </div>

    <?php if (empty($items)) : ?>
        <?php component_render('empty-state', [
            'title' => 'ไม่มีรายการหนังสือออก',
            'message' => 'ยังไม่มีการออกเลขหนังสือภายนอก',
        ]); ?>
    <?php else : ?>
        <?php component_render('table', [
            'headers' => ['เลขที่หนังสือ', 'เรื่อง', 'สถานะ', 'จัดการ'],
            'rows' => $rows,
            'empty_text' => 'ไม่มีรายการ',
        ]); ?>
    <?php endif; ?>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';

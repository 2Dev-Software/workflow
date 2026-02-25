<?php
require_once __DIR__ . '/../../helpers.php';

$item = $item ?? null;
$attachments = (array) ($attachments ?? []);
$timeline_events = (array) ($timeline_events ?? []);

$format_size = static function (int $bytes): string {
    if ($bytes <= 0) {
        return '-';
    }

    $units = ['B', 'KB', 'MB', 'GB'];
    $value = (float) $bytes;
    $index = 0;

    while ($value >= 1024 && $index < (count($units) - 1)) {
        $value /= 1024;
        $index++;
    }

    return number_format($value, $index === 0 ? 0 : 2) . ' ' . $units[$index];
};

ob_start();
?>
<div class="content-header">
    <h1><?= h($item['subject'] ?? 'คำสั่งราชการ') ?></h1>
    <p>รายละเอียดคำสั่งราชการ</p>
</div>

<section class="enterprise-card orders-view-card" data-orders-view>
    <div class="enterprise-card-header">
        <div class="enterprise-card-title-group">
            <h2 class="enterprise-card-title">ข้อมูลคำสั่ง</h2>
            <p class="enterprise-card-subtitle">เลขที่คำสั่ง ผู้ส่ง และสถานะการอ่านของรายการนี้</p>
        </div>
    </div>

    <?php if (!$item) : ?>
        <?php component_render('empty-state', [
            'title' => 'ไม่พบคำสั่ง',
            'message' => 'ไม่สามารถแสดงรายละเอียดคำสั่งได้ในขณะนี้',
        ]); ?>
    <?php else : ?>
        <div class="enterprise-info">
            <div class="enterprise-info-row">
                <span class="enterprise-info-label">เลขที่คำสั่ง</span>
                <span class="enterprise-info-value"><?= h((string) ($item['orderNo'] ?? '-')) ?></span>
            </div>
            <div class="enterprise-info-row">
                <span class="enterprise-info-label">ผู้ส่ง</span>
                <span class="enterprise-info-value"><?= h((string) ($item['senderName'] ?? '-')) ?></span>
            </div>
            <div class="enterprise-info-row">
                <span class="enterprise-info-label">วันที่ส่งถึงผู้รับ</span>
                <span class="enterprise-info-value"><?= h((string) ($item['deliveredAt'] ?? '-')) ?></span>
            </div>
            <div class="enterprise-info-row">
                <span class="enterprise-info-label">สถานะอ่านของรายการนี้</span>
                <span class="enterprise-info-value"><?= (int) ($item['isRead'] ?? 0) === 1 ? 'อ่านแล้ว' : 'ยังไม่อ่าน' ?></span>
            </div>
        </div>

        <div class="enterprise-divider"></div>
        <div class="enterprise-panel">
            <p><strong>รายละเอียด</strong></p>
            <p><?= nl2br(h((string) ($item['detail'] ?? '-'))) ?></p>
        </div>

        <div class="enterprise-divider"></div>
        <div class="enterprise-panel">
            <p><strong>ไฟล์แนบ</strong></p>
            <?php if (empty($attachments)) : ?>
                <p class="attachment-empty">ไม่มีไฟล์แนบ</p>
            <?php else : ?>
                <div class="table-responsive">
                    <table class="custom-table booking-table">
                        <thead>
                            <tr>
                                <th>ชื่อไฟล์</th>
                                <th>ประเภท</th>
                                <th>ขนาด</th>
                                <th>จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attachments as $file) : ?>
                                <tr>
                                    <td><?= h((string) ($file['fileName'] ?? '-')) ?></td>
                                    <td><?= h((string) ($file['mimeType'] ?? '-')) ?></td>
                                    <td><?= h($format_size((int) ($file['fileSize'] ?? 0))) ?></td>
                                    <td>
                                        <a class="booking-action-btn secondary" href="public/api/file-download.php?module=orders&entity_id=<?= h((string) ($item['orderID'] ?? 0)) ?>&file_id=<?= h((string) ($file['fileID'] ?? '')) ?>" target="_blank" rel="noopener">ดูไฟล์</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>

<?php if ($item) : ?>
    <?php component_render('orders-timeline', [
        'events' => $timeline_events,
    ]); ?>
<?php endif; ?>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';

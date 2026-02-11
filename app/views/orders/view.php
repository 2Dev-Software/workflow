<?php
require_once __DIR__ . '/../../helpers.php';

$item = $item ?? null;
$attachments = $attachments ?? [];

ob_start();
?>
<div class="content-header">
    <h1><?= h($item['subject'] ?? 'คำสั่งราชการ') ?></h1>
    <p>คำสั่งราชการ</p>
</div>

<section class="enterprise-card">
    <div class="enterprise-card-header">
        <div class="enterprise-card-title-group">
            <h2 class="enterprise-card-title">รายละเอียดคำสั่งราชการ</h2>
            <p class="enterprise-card-subtitle">ข้อมูลคำสั่งและไฟล์แนบ</p>
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
                <span class="enterprise-info-value"><?= h($item['orderNo'] ?? '') ?></span>
            </div>
            <div class="enterprise-info-row">
                <span class="enterprise-info-label">ผู้ส่ง</span>
                <span class="enterprise-info-value"><?= h($item['senderName'] ?? '') ?></span>
            </div>
            <div class="enterprise-info-row">
                <span class="enterprise-info-label">วันที่สร้าง</span>
                <span class="enterprise-info-value"><?= h($item['createdAt'] ?? '') ?></span>
            </div>
        </div>

        <?php if (!empty($item['detail'])) : ?>
            <div class="enterprise-divider"></div>
            <div class="enterprise-panel">
                <p><strong>รายละเอียด:</strong></p>
                <p><?= nl2br(h((string) ($item['detail'] ?? ''))) ?></p>
            </div>
        <?php endif; ?>

        <?php if (!empty($attachments)) : ?>
            <div class="enterprise-divider"></div>
            <div class="enterprise-panel">
                <p><strong>ไฟล์แนบ:</strong></p>
                <div class="attachment-list">
                    <?php foreach ($attachments as $file) : ?>
                        <div class="attachment-item">
                            <span class="attachment-name"><?= h($file['fileName'] ?? '') ?></span>
                            <?php if (!empty($file['fileID']) && !empty($item['orderID'])) : ?>
                                <a class="attachment-link" href="public/api/file-download.php?module=orders&entity_id=<?= h((string) $item['orderID']) ?>&file_id=<?= h((string) $file['fileID']) ?>" target="_blank" rel="noopener">ดูไฟล์</a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';

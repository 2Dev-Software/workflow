<?php
$params = $params ?? [];
$events = (array) ($params['events'] ?? []);
?>
<section class="enterprise-card orders-timeline">
    <div class="enterprise-card-header">
        <div class="enterprise-card-title-group">
            <h2 class="enterprise-card-title">Timeline การดำเนินการ</h2>
            <p class="enterprise-card-subtitle">ลำดับเหตุการณ์ของคำสั่งฉบับนี้</p>
        </div>
    </div>

    <?php if (empty($events)) : ?>
        <div class="enterprise-panel">
            <p class="attachment-empty">ยังไม่มีเหตุการณ์</p>
        </div>
    <?php else : ?>
        <ol class="orders-timeline-list">
            <?php foreach ($events as $event) : ?>
                <li class="orders-timeline-item">
                    <div class="orders-timeline-marker" aria-hidden="true"></div>
                    <div class="orders-timeline-body">
                        <div class="orders-timeline-head">
                            <strong><?= h((string) ($event['label'] ?? '-')) ?></strong>
                            <span><?= h((string) ($event['at'] ?? '-')) ?></span>
                        </div>
                        <p class="orders-timeline-meta">
                            โดย <?= h((string) ($event['actorName'] ?? '-')) ?>
                        </p>
                        <?php if (!empty($event['note'])) : ?>
                            <p class="orders-timeline-note"><?= h((string) $event['note']) ?></p>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ol>
    <?php endif; ?>
</section>

<?php
$params = $params ?? [];
$view_href = (string) ($params['view_href'] ?? '#');
$archive_action = (string) ($params['archive_action'] ?? '');
$show_archive = (bool) ($params['show_archive'] ?? false);
$show_unarchive = (bool) ($params['show_unarchive'] ?? false);
$archive_label = (string) ($params['archive_label'] ?? 'จัดเก็บ');
$unarchive_label = (string) ($params['unarchive_label'] ?? 'ยกเลิกจัดเก็บ');
$inbox_id = (int) ($params['inbox_id'] ?? 0);
$extra_class = (string) ($params['class'] ?? '');
?>
<div class="<?= h(trim('booking-action-group orders-action-group ' . $extra_class)) ?>">
    <a class="booking-action-btn secondary" href="<?= h($view_href) ?>">ดูรายละเอียด</a>
    <?php if ($show_archive && $inbox_id > 0) : ?>
        <form method="post" action="<?= h($archive_action) ?>">
            <?php if (function_exists('csrf_field')) : ?>
                <?= csrf_field() ?>
            <?php endif; ?>
            <input type="hidden" name="action" value="archive">
            <input type="hidden" name="inbox_id" value="<?= h((string) $inbox_id) ?>">
            <button type="submit" class="booking-action-btn danger"><?= h($archive_label) ?></button>
        </form>
    <?php endif; ?>
    <?php if ($show_unarchive && $inbox_id > 0) : ?>
        <form method="post" action="<?= h($archive_action) ?>">
            <?php if (function_exists('csrf_field')) : ?>
                <?= csrf_field() ?>
            <?php endif; ?>
            <input type="hidden" name="action" value="unarchive">
            <input type="hidden" name="inbox_id" value="<?= h((string) $inbox_id) ?>">
            <button type="submit" class="booking-action-btn secondary"><?= h($unarchive_label) ?></button>
        </form>
    <?php endif; ?>
</div>

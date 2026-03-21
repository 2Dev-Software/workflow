<?php
$params = $params ?? [];
$repair_id = (int) ($params['repair_id'] ?? 0);
$can_edit = (bool) ($params['can_edit'] ?? false);
$can_delete = (bool) ($params['can_delete'] ?? false);
$extra_class = (string) ($params['class'] ?? '');
$base_url = trim((string) ($params['base_url'] ?? 'repairs.php'));
$view_label = trim((string) ($params['view_label'] ?? 'อ่าน'));
$edit_label = trim((string) ($params['edit_label'] ?? 'แก้ไข'));
$delete_label = trim((string) ($params['delete_label'] ?? 'ลบ'));

$view_href = $repair_id > 0 ? ($base_url . '?view_id=' . $repair_id) : '#';
$edit_href = $repair_id > 0 ? ($base_url . '?edit_id=' . $repair_id) : '#';
?>
<div class="<?= h(trim('booking-action-group repairs-action-group ' . $extra_class)) ?>">
    <a class="booking-action-btn secondary" href="<?= h($view_href) ?>"><?= h($view_label) ?></a>
    <?php if ($can_edit) : ?>
        <a class="booking-action-btn secondary" href="<?= h($edit_href) ?>"><?= h($edit_label) ?></a>
    <?php endif; ?>
    <?php if ($can_delete) : ?>
        <form method="post" action="<?= h($base_url) ?>" data-confirm="ยืนยันการลบรายการนี้?">
            <?php if (function_exists('csrf_field')) : ?>
                <?= csrf_field() ?>
            <?php endif; ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="repair_id" value="<?= h((string) $repair_id) ?>">
            <button type="submit" class="booking-action-btn danger"><?= h($delete_label) ?></button>
        </form>
    <?php endif; ?>
</div>

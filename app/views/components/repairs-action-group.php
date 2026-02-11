<?php
$params = $params ?? [];
$repair_id = (int) ($params['repair_id'] ?? 0);
$can_edit = (bool) ($params['can_edit'] ?? false);
$can_delete = (bool) ($params['can_delete'] ?? false);
$extra_class = (string) ($params['class'] ?? '');

$view_href = $repair_id > 0 ? ('repairs.php?view_id=' . $repair_id) : '#';
$edit_href = $repair_id > 0 ? ('repairs.php?edit_id=' . $repair_id) : '#';
?>
<div class="<?= h(trim('booking-action-group repairs-action-group ' . $extra_class)) ?>">
    <a class="booking-action-btn secondary" href="<?= h($view_href) ?>">อ่าน</a>
    <?php if ($can_edit) : ?>
        <a class="booking-action-btn secondary" href="<?= h($edit_href) ?>">แก้ไข</a>
    <?php endif; ?>
    <?php if ($can_delete) : ?>
        <form method="post" action="repairs.php" onsubmit="return confirm('ยืนยันการลบรายการนี้?');">
            <?php if (function_exists('csrf_field')) : ?>
                <?= csrf_field() ?>
            <?php endif; ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="repair_id" value="<?= h((string) $repair_id) ?>">
            <button type="submit" class="booking-action-btn danger">ลบ</button>
        </form>
    <?php endif; ?>
</div>

<?php
$params = $params ?? [];
$order_id = (int) ($params['order_id'] ?? 0);
$action = (string) ($params['action'] ?? 'attach');
$input_name = (string) ($params['input_name'] ?? 'attachments[]');
$accept = (string) ($params['accept'] ?? 'application/pdf,image/png,image/jpeg');
$multiple = array_key_exists('multiple', $params) ? (bool) $params['multiple'] : true;
$button_label = (string) ($params['button_label'] ?? 'แนบไฟล์');
$button_variant = (string) ($params['button_variant'] ?? 'secondary');
$button_size = (string) ($params['button_size'] ?? 'sm');
$extra_class = (string) ($params['class'] ?? '');
?>
<form method="POST" enctype="multipart/form-data" class="<?= h(trim('c-table__form booking-attach-form ' . $extra_class)) ?>">
    <?php if (function_exists('csrf_field')) : ?>
        <?= csrf_field() ?>
    <?php endif; ?>
    <input type="hidden" name="action" value="<?= h($action) ?>">
    <input type="hidden" name="order_id" value="<?= h((string) $order_id) ?>">
    <input
        type="file"
        name="<?= h($input_name) ?>"
        class="c-input form-input"
        accept="<?= h($accept) ?>"
        <?= $multiple ? 'multiple' : '' ?>>
    <?php component_render('button', [
        'label' => $button_label,
        'variant' => $button_variant,
        'size' => $button_size,
        'type' => 'submit',
    ]); ?>
</form>

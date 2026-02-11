<?php
$params = $params ?? [];
$name = (string) ($params['name'] ?? '');
$label = (string) ($params['label'] ?? '');
$id = (string) ($params['id'] ?? $name);
$value = (string) ($params['value'] ?? '');
$placeholder = (string) ($params['placeholder'] ?? '');
$rows = (int) ($params['rows'] ?? 4);
$required = (bool) ($params['required'] ?? false);
$disabled = (bool) ($params['disabled'] ?? false);
$field_class = (string) ($params['field_class'] ?? '');
$label_class = (string) ($params['label_class'] ?? '');
$extra_class = (string) ($params['class'] ?? '');
$attrs = (array) ($params['attrs'] ?? []);

$textarea_attrs = array_merge($attrs, [
    'class' => trim('c-textarea form-input ' . $extra_class),
    'id' => $id,
    'name' => $name,
    'rows' => $rows,
    'placeholder' => $placeholder,
]);
if ($required) {
    $textarea_attrs['required'] = true;
}
if ($disabled) {
    $textarea_attrs['disabled'] = true;
}
?>
<div class="<?= h(trim('c-field form-group ' . $field_class)) ?>">
    <?php if ($label !== '') : ?>
        <label class="<?= h(trim('c-label form-label ' . $label_class)) ?>" for="<?= h($id) ?>"><?= h($label) ?></label>
    <?php endif; ?>
    <textarea<?= component_attr($textarea_attrs) ?>><?= h($value) ?></textarea>
</div>

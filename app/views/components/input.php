<?php
$params = $params ?? [];
$name = (string) ($params['name'] ?? '');
$type = (string) ($params['type'] ?? 'text');
$value = (string) ($params['value'] ?? '');
$label = (string) ($params['label'] ?? '');
$id = (string) ($params['id'] ?? $name);
$placeholder = (string) ($params['placeholder'] ?? '');
$help = (string) ($params['help'] ?? '');
$required = (bool) ($params['required'] ?? false);
$disabled = (bool) ($params['disabled'] ?? false);
$field_class = (string) ($params['field_class'] ?? '');
$label_class = (string) ($params['label_class'] ?? '');
$help_class = (string) ($params['help_class'] ?? '');
$extra_class = (string) ($params['class'] ?? '');
$attrs = (array) ($params['attrs'] ?? []);

$input_attrs = array_merge($attrs, [
    'class' => trim('c-input form-input ' . $extra_class),
    'id' => $id,
    'name' => $name,
    'type' => $type,
    'placeholder' => $placeholder,
]);
if ($type !== 'file') {
    $input_attrs['value'] = $value;
}
if ($required) {
    $input_attrs['required'] = true;
}
if ($disabled) {
    $input_attrs['disabled'] = true;
}
?>
<div class="<?= h(trim('c-field form-group ' . $field_class)) ?>">
    <?php if ($label !== '') : ?>
        <label class="<?= h(trim('c-label form-label ' . $label_class)) ?>" for="<?= h($id) ?>"><?= h($label) ?></label>
    <?php endif; ?>
    <input<?= component_attr($input_attrs) ?> />
    <?php if ($help !== '') : ?>
        <small class="<?= h(trim('c-help form-hint ' . $help_class)) ?>"><?= h($help) ?></small>
    <?php endif; ?>
</div>

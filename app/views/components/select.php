<?php
$params = $params ?? [];
$name = (string) ($params['name'] ?? '');
$label = (string) ($params['label'] ?? '');
$id = (string) ($params['id'] ?? $name);
$options = (array) ($params['options'] ?? []);
$selected = $params['selected'] ?? '';
$required = (bool) ($params['required'] ?? false);
$disabled = (bool) ($params['disabled'] ?? false);
$field_class = (string) ($params['field_class'] ?? '');
$label_class = (string) ($params['label_class'] ?? '');
$extra_class = (string) ($params['class'] ?? '');
$attrs = (array) ($params['attrs'] ?? []);

$select_attrs = array_merge($attrs, [
    'class' => trim('c-select form-input ' . $extra_class),
    'id' => $id,
    'name' => $name,
]);

if ($required) {
    $select_attrs['required'] = true;
}

if ($disabled) {
    $select_attrs['disabled'] = true;
}
?>
<div class="<?= h(trim('c-field form-group ' . $field_class)) ?>">
    <?php if ($label !== '') : ?>
        <label class="<?= h(trim('c-label form-label ' . $label_class)) ?>" for="<?= h($id) ?>"><?= h($label) ?></label>
    <?php endif; ?>
    <select<?= component_attr($select_attrs) ?>>
        <?php foreach ($options as $value => $text) : ?>
            <?php
            $is_selected = false;

            if (is_array($selected)) {
                $is_selected = in_array((string) $value, array_map('strval', $selected), true);
            } else {
                $is_selected = ((string) $value === (string) $selected);
            }
            ?>
            <option value="<?= h((string) $value) ?>"<?= $is_selected ? ' selected' : '' ?>>
                <?= h((string) $text) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>

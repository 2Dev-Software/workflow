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

// Optional backward-safe additions.
$searchable = (bool) ($params['searchable'] ?? false);
$multiple_summary = (bool) ($params['multiple_summary'] ?? false);
$empty_text = (string) ($params['empty_text'] ?? 'ไม่พบข้อมูล');
$helper_text = (string) ($params['helper_text'] ?? '');
$search_placeholder = (string) ($params['search_placeholder'] ?? 'ค้นหา...');

$selected_values = is_array($selected) ? array_map('strval', $selected) : [(string) $selected];
$is_multiple = !empty($attrs['multiple']) || str_ends_with($name, '[]');
$selected_count = 0;

foreach ($options as $value => $text) {
    if (in_array((string) $value, $selected_values, true)) {
        $selected_count++;
    }
}

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

    <div class="c-select-wrap<?= $searchable ? ' is-searchable' : '' ?>"<?= $searchable ? ' data-select-searchable="1"' : '' ?>>
        <?php if ($searchable) : ?>
            <div class="c-select-search">
                <input
                    type="search"
                    class="c-input form-input"
                    data-select-search-input
                    data-select-target="<?= h($id) ?>"
                    placeholder="<?= h($search_placeholder) ?>"
                    autocomplete="off"
                    aria-label="ค้นหาในรายการ <?= h($label !== '' ? $label : $name) ?>">
            </div>
        <?php endif; ?>
        <select<?= component_attr($select_attrs) ?>>
            <?php if (empty($options)) : ?>
                <option value="" disabled><?= h($empty_text) ?></option>
            <?php else : ?>
                <?php foreach ($options as $value => $text) : ?>
                    <?php
                    $is_selected = in_array((string) $value, $selected_values, true);
                    ?>
                    <option value="<?= h((string) $value) ?>"<?= $is_selected ? ' selected' : '' ?>>
                        <?= h((string) $text) ?>
                    </option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
    </div>

    <?php if ($multiple_summary && $is_multiple) : ?>
        <small class="c-help form-hint" data-select-multiple-summary data-select-target="<?= h($id) ?>">
            เลือกแล้ว <?= h((string) $selected_count) ?> รายการ
        </small>
    <?php endif; ?>

    <?php if ($helper_text !== '') : ?>
        <small class="c-help form-hint"><?= h($helper_text) ?></small>
    <?php endif; ?>
</div>

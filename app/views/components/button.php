<?php
$params = $params ?? [];
$label = (string) ($params['label'] ?? '');
$variant = (string) ($params['variant'] ?? 'primary');
$size = (string) ($params['size'] ?? 'md');
$href = $params['href'] ?? null;
$type = (string) ($params['type'] ?? 'button');
$icon = (string) ($params['icon'] ?? '');
$extra_class = (string) ($params['class'] ?? '');
$attrs = (array) ($params['attrs'] ?? []);

$variant_map = [
    'primary' => 'btn-confirm',
    'secondary' => 'btn-outline',
    'ghost' => 'btn-outline',
    'link' => 'btn-link',
    'danger' => 'btn-danger',
];
$variant_class = $variant_map[$variant] ?? 'btn-confirm';
$size_class = in_array($size, ['sm', 'md'], true) ? ('c-button--' . $size) : '';
$classes = trim('c-button ' . $size_class . ' ' . $variant_class . ' ' . $extra_class);
$attrs['class'] = $classes;

if ($href) : ?>
    <a<?= component_attr($attrs) ?> href="<?= h((string) $href) ?>">
        <?php if ($icon !== '') : ?>
            <i class="<?= h($icon) ?>" aria-hidden="true"></i>
        <?php endif; ?>
        <span><?= h($label) ?></span>
    </a>
<?php else : ?>
    <button<?= component_attr($attrs) ?> type="<?= h($type) ?>">
        <?php if ($icon !== '') : ?>
            <i class="<?= h($icon) ?>" aria-hidden="true"></i>
        <?php endif; ?>
        <span><?= h($label) ?></span>
    </button>
<?php endif; ?>

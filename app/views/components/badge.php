<?php
$params = $params ?? [];
$label = (string) ($params['label'] ?? '');
$variant = (string) ($params['variant'] ?? 'neutral');
$extra_class = (string) ($params['class'] ?? '');
$attrs = (array) ($params['attrs'] ?? []);

$attrs['class'] = trim('c-badge c-badge--' . $variant . ' ' . $extra_class);
?>
<span<?= component_attr($attrs) ?>><?= h($label) ?></span>

<?php
$params = $params ?? [];
$label = (string) ($params['label'] ?? '');
$variant = (string) ($params['variant'] ?? 'pending');
$extra_class = (string) ($params['class'] ?? '');
$attrs = (array) ($params['attrs'] ?? []);

$attrs['class'] = trim('status-pill ' . $variant . ' ' . $extra_class);
?>
<span<?= component_attr($attrs) ?>><?= h($label) ?></span>

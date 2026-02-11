<?php
$params = $params ?? [];
$extra_class = (string) ($params['class'] ?? '');
$attrs = (array) ($params['attrs'] ?? []);

$attrs['class'] = trim('c-toast-container ' . $extra_class);
$attrs['data-toast-container'] = 'true';
$attrs['aria-live'] = 'polite';
$attrs['aria-atomic'] = 'true';
?>
<div<?= component_attr($attrs) ?>></div>

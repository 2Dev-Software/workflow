<?php
$params = $params ?? [];
$type = (string) ($params['type'] ?? 'info');
$title = (string) ($params['title'] ?? '');
$message = (string) ($params['message'] ?? '');
$extra_class = (string) ($params['class'] ?? '');
$attrs = (array) ($params['attrs'] ?? []);

$attrs['class'] = trim('c-alert c-alert--' . $type . ' ' . $extra_class);
?>
<div<?= component_attr($attrs) ?> role="alert">
    <?php if ($title !== '') : ?>
        <strong class="c-alert__title"><?= h($title) ?></strong>
    <?php endif; ?>
    <?php if ($message !== '') : ?>
        <p class="c-alert__message"><?= h($message) ?></p>
    <?php endif; ?>
</div>

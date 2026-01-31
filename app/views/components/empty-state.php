<?php
$params = $params ?? [];
$title = (string) ($params['title'] ?? 'ยังไม่มีข้อมูล');
$message = (string) ($params['message'] ?? 'เมื่อมีข้อมูลจะแสดงที่นี่');
$action = (array) ($params['action'] ?? []);
$extra_class = (string) ($params['class'] ?? '');
$attrs = (array) ($params['attrs'] ?? []);

$attrs['class'] = trim('c-empty ' . $extra_class);
?>
<div<?= component_attr($attrs) ?>>
    <div class="c-empty__icon">
        <i class="fa-regular fa-folder-open" aria-hidden="true"></i>
    </div>
    <h3 class="c-empty__title"><?= h($title) ?></h3>
    <p class="c-empty__message"><?= h($message) ?></p>
    <?php if (!empty($action)) : ?>
        <div class="c-empty__action">
            <?php if (function_exists('component_render')) : ?>
                <?php component_render('button', $action); ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

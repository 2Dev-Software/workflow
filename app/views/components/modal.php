<?php
$params = $params ?? [];
$id = (string) ($params['id'] ?? 'modal');
$title = (string) ($params['title'] ?? '');
$body = (string) ($params['body'] ?? '');
$actions = (array) ($params['actions'] ?? []);
$extra_class = (string) ($params['class'] ?? '');
$attrs = (array) ($params['attrs'] ?? []);

$attrs['class'] = trim('c-modal ' . $extra_class);
$attrs['id'] = $id;
$attrs['aria-hidden'] = 'true';
?>
<div<?= component_attr($attrs) ?> role="dialog" aria-modal="true">
    <div class="c-modal__content">
        <header class="c-modal__header">
            <h3 class="c-modal__title"><?= h($title) ?></h3>
            <button type="button" class="c-button c-button--ghost c-button--sm" data-modal-close>ปิด</button>
        </header>
        <div class="c-modal__body">
            <p><?= h($body) ?></p>
        </div>
        <?php if (!empty($actions)) : ?>
            <footer class="c-modal__footer">
                <?php foreach ($actions as $action) : ?>
                    <?php if (function_exists('component_render')) : ?>
                        <?php component_render('button', $action); ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            </footer>
        <?php endif; ?>
    </div>
</div>

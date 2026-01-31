<?php
$params = $params ?? [];
$title = (string) ($params['title'] ?? '');
$subtitle = (string) ($params['subtitle'] ?? '');
$content = (string) ($params['content'] ?? '');
$extra_class = (string) ($params['class'] ?? '');
$attrs = (array) ($params['attrs'] ?? []);

$attrs['class'] = trim('c-card ' . $extra_class);
?>
<section<?= component_attr($attrs) ?>>
    <?php if ($title !== '' || $subtitle !== '') : ?>
        <header class="c-card__header">
            <?php if ($title !== '') : ?>
                <h3 class="c-card__title"><?= h($title) ?></h3>
            <?php endif; ?>
            <?php if ($subtitle !== '') : ?>
                <p class="c-card__subtitle"><?= h($subtitle) ?></p>
            <?php endif; ?>
        </header>
    <?php endif; ?>
    <?php if ($content !== '') : ?>
        <div class="c-card__body">
            <p><?= h($content) ?></p>
        </div>
    <?php endif; ?>
</section>

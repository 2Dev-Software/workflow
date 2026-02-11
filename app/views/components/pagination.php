<?php
$params = $params ?? [];
$page = (int) ($params['page'] ?? 1);
$total_pages = (int) ($params['total_pages'] ?? 1);
$base_url = (string) ($params['base_url'] ?? '');
$extra_class = (string) ($params['class'] ?? '');
$attrs = (array) ($params['attrs'] ?? []);

$page = max(1, $page);
$total_pages = max(1, $total_pages);
$prev = max(1, $page - 1);
$next = min($total_pages, $page + 1);
$separator = strpos($base_url, '?') !== false ? '&' : '?';

$attrs['class'] = trim('c-pagination ' . $extra_class);
?>
<nav<?= component_attr($attrs) ?> aria-label="Pagination">
    <span class="c-pagination__info">หน้า <?= h((string) $page) ?> จาก <?= h((string) $total_pages) ?></span>
    <div class="c-pagination__actions">
        <a class="c-button c-button--sm btn-outline" href="<?= h($base_url) ?><?= h($separator) ?>page=<?= h((string) $prev) ?>">ก่อนหน้า</a>
        <a class="c-button c-button--sm btn-outline" href="<?= h($base_url) ?><?= h($separator) ?>page=<?= h((string) $next) ?>">ถัดไป</a>
    </div>
</nav>

<?php
declare(strict_types=1);

$vehicle_approval_total_pages = (int) ($vehicle_approval_total_pages ?? 0);
$vehicle_approval_page = (int) ($vehicle_approval_page ?? 1);
$vehicle_approval_per_page = $vehicle_approval_per_page ?? 10;

if ($vehicle_approval_per_page === 'all' || $vehicle_approval_total_pages <= 1) {
    if ($vehicle_approval_per_page === 'all') {
        return;
    }
    // Keep the layout consistent even when there is only 1 page.
    $vehicle_approval_total_pages = 1;
}

$total_pages = $vehicle_approval_total_pages;
$current_page = max(1, min($vehicle_approval_page, $total_pages));
$prev_page = max(1, $current_page - 1);
$next_page = min($total_pages, $current_page + 1);
?>
<button type="button" data-page="<?= h((string) $prev_page) ?>" <?= $current_page <= 1 ? 'disabled' : '' ?>
    aria-label="Previous page">
    <i class="fas fa-chevron-left" aria-hidden="true"></i>
</button>
<?php
$start_page = 1;
$end_page = $total_pages;

if ($total_pages > 7) {
    if ($current_page <= 4) {
        $end_page = 5;
    } elseif ($current_page >= $total_pages - 3) {
        $start_page = $total_pages - 4;
    } else {
        $start_page = $current_page - 2;
        $end_page = $current_page + 2;
    }
}

if ($start_page > 1) {
    ?>
    <button type="button" data-page="1" <?= $current_page === 1 ? 'class="active"' : '' ?>>1</button>
    <?php if ($start_page > 2) : ?>
        <span class="enterprise-ellipsis">...</span>
    <?php endif; ?>
    <?php
}

for ($i = $start_page; $i <= $end_page; $i++) {
    ?>
    <button type="button" data-page="<?= h((string) $i) ?>" <?= $i === $current_page ? 'class="active"' : '' ?>>
        <?= h((string) $i) ?>
    </button>
    <?php
}

if ($end_page < $total_pages) {
    if ($end_page < $total_pages - 1) {
        ?>
        <span class="enterprise-ellipsis">...</span>
        <?php
    }
    ?>
    <button type="button" data-page="<?= h((string) $total_pages) ?>" <?= $current_page === $total_pages ? 'class="active"' : '' ?>>
        <?= h((string) $total_pages) ?>
    </button>
    <?php
}
?>
<button type="button" data-page="<?= h((string) $next_page) ?>" <?= $current_page >= $total_pages ? 'disabled' : '' ?>
    aria-label="Next page">
    <i class="fas fa-chevron-right" aria-hidden="true"></i>
</button>

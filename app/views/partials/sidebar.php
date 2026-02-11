<?php
$active_path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?? '';
$nav_items = [
    ['label' => 'แดชบอร์ด', 'path' => '/dashboard', 'icon' => 'fa-solid fa-chart-line'],
    ['label' => 'กล่องหนังสือเข้า', 'path' => '/inbox', 'icon' => 'fa-regular fa-envelope'],
    ['label' => 'หนังสือเวียนภายใน', 'path' => '/circulars', 'icon' => 'fa-regular fa-paper-plane'],
    ['label' => 'หนังสือเวียนภายนอก', 'path' => '/external', 'icon' => 'fa-solid fa-share-nodes'],
    ['label' => 'หนังสือออก', 'path' => '/outgoing', 'icon' => 'fa-regular fa-file-lines'],
    ['label' => 'คำสั่งราชการ', 'path' => '/orders', 'icon' => 'fa-solid fa-gavel'],
    ['label' => 'จองห้อง/รถ', 'path' => '/booking', 'icon' => 'fa-regular fa-calendar'],
    ['label' => 'แจ้งซ่อม', 'path' => '/repairs', 'icon' => 'fa-solid fa-screwdriver-wrench'],
    ['label' => 'Health Check', 'path' => '/health', 'icon' => 'fa-solid fa-shield-heart'],
];
?>
<aside class="layout-sidebar" aria-label="เมนูหลัก">
    <div class="sidebar-header">
        <div class="brand">
            <span class="brand__mark">DB</span>
            <div class="brand__text">
                <span class="brand__title">DB HUB</span>
                <span class="brand__subtitle">เอกสารเวียนองค์กร</span>
            </div>
        </div>
        <button type="button" class="icon-button" data-sidebar-toggle aria-label="ย่อ/ขยายแถบเมนู">
            <i class="fa-solid fa-bars"></i>
        </button>
    </div>

    <nav class="sidebar-nav">
        <?php foreach ($nav_items as $item) : ?>
            <?php $is_active = str_contains((string) $active_path, $item['path']); ?>
            <a class="sidebar-link<?= $is_active ? ' is-active' : '' ?>" href="<?= h(app_url($item['path'])) ?>">
                <i class="<?= h($item['icon']) ?>"></i>
                <span><?= h($item['label']) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">
        <div class="user-card">
            <div class="user-card__avatar">
                <i class="fa-solid fa-user"></i>
            </div>
            <div class="user-card__info">
                <span class="user-card__name"><?= h((string) ($_SESSION['user_name'] ?? 'ผู้ใช้งาน')) ?></span>
                <span class="user-card__meta">ออนไลน์</span>
            </div>
        </div>
        <a class="sidebar-link" href="<?= h(app_url('/logout')) ?>">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>ออกจากระบบ</span>
        </a>
    </div>
</aside>

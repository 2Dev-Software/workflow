<header class="layout-topbar">
    <div class="topbar-left">
        <h1 class="page-title"><?= h($title ?? 'แดชบอร์ด') ?></h1>
        <p class="page-subtitle"><?= h($subtitle ?? 'ภาพรวมการทำงานล่าสุด') ?></p>
    </div>
    <div class="topbar-actions">
        <div class="search-field">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="search" name="q" placeholder="ค้นหาเอกสารหรือเลขที่" aria-label="ค้นหา">
        </div>
        <button class="icon-button" type="button" aria-label="การแจ้งเตือน">
            <span class="badge badge--pulse">3</span>
            <i class="fa-regular fa-bell"></i>
        </button>
        <button class="btn btn--primary" type="button">
            <i class="fa-regular fa-plus"></i>
            สร้างหนังสือ
        </button>
    </div>
</header>

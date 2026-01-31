<?php
$title = 'Health Check';
$subtitle = 'ตรวจสอบความพร้อมระบบ';

ob_start();
?>
<section class="card">
    <div class="card__header">
        <h3>สถานะระบบ</h3>
        <span class="badge badge--neutral">Version <?= h((string) ($migration_version ?? 0)) ?></span>
    </div>
    <div class="health-grid">
        <div class="health-item">
            <span>DB Connection</span>
            <strong class="<?= ($checks['db_connection'] ?? false) ? 'text-success' : 'text-danger' ?>">
                <?= ($checks['db_connection'] ?? false) ? 'OK' : 'FAIL' ?>
            </strong>
        </div>
        <div class="health-item">
            <span>Migrations Table</span>
            <strong class="<?= ($checks['migrations_table'] ?? false) ? 'text-success' : 'text-danger' ?>">
                <?= ($checks['migrations_table'] ?? false) ? 'OK' : 'MISSING' ?>
            </strong>
        </div>
        <div class="health-item">
            <span>Session Path</span>
            <strong class="<?= ($checks['session_path']['writable'] ?? false) ? 'text-success' : 'text-danger' ?>">
                <?= ($checks['session_path']['writable'] ?? false) ? 'WRITABLE' : 'NOT WRITABLE' ?>
            </strong>
        </div>
        <div class="health-item">
            <span>Upload Root</span>
            <strong class="<?= ($checks['upload_root']['writable'] ?? false) ? 'text-success' : 'text-danger' ?>">
                <?= ($checks['upload_root']['writable'] ?? false) ? 'WRITABLE' : 'NOT WRITABLE' ?>
            </strong>
        </div>
        <div class="health-item">
            <span>Upload Max</span>
            <strong><?= h((string) ($checks['max_upload_size'] ?? '')) ?></strong>
        </div>
        <div class="health-item">
            <span>Post Max</span>
            <strong><?= h((string) ($checks['max_post_size'] ?? '')) ?></strong>
        </div>
        <div class="health-item">
            <span>Timezone</span>
            <strong><?= h((string) ($checks['timezone'] ?? '')) ?></strong>
        </div>
    </div>

    <div class="card__header">
        <h3>PHP Extensions</h3>
    </div>
    <div class="health-grid">
        <?php foreach (($checks['extensions'] ?? []) as $ext => $ok) : ?>
            <div class="health-item">
                <span><?= h($ext) ?></span>
                <strong class="<?= $ok ? 'text-success' : 'text-danger' ?>"><?= $ok ? 'OK' : 'MISSING' ?></strong>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';

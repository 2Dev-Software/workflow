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
        <div class="health-item">
            <span>DB Driver</span>
            <strong><?= h((string) ($checks['db_driver'] ?? '')) ?></strong>
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

    <div class="card__header">
        <h3>PDF Runtime</h3>
    </div>
    <div class="health-grid">
        <div class="health-item">
            <span>Vendor Autoload</span>
            <strong class="<?= ($checks['vendor_autoload'] ?? false) ? 'text-success' : 'text-danger' ?>">
                <?= ($checks['vendor_autoload'] ?? false) ? 'OK' : 'MISSING' ?>
            </strong>
        </div>
        <div class="health-item">
            <span>mPDF Class</span>
            <strong class="<?= ($checks['pdf_runtime']['mpdf_class'] ?? false) ? 'text-success' : 'text-danger' ?>">
                <?= ($checks['pdf_runtime']['mpdf_class'] ?? false) ? 'OK' : 'MISSING' ?>
            </strong>
        </div>
        <div class="health-item">
            <span>ConfigVariables Class</span>
            <strong class="<?= ($checks['pdf_runtime']['config_variables_class'] ?? false) ? 'text-success' : 'text-danger' ?>">
                <?= ($checks['pdf_runtime']['config_variables_class'] ?? false) ? 'OK' : 'MISSING' ?>
            </strong>
        </div>
        <div class="health-item">
            <span>FontVariables Class</span>
            <strong class="<?= ($checks['pdf_runtime']['font_variables_class'] ?? false) ? 'text-success' : 'text-danger' ?>">
                <?= ($checks['pdf_runtime']['font_variables_class'] ?? false) ? 'OK' : 'MISSING' ?>
            </strong>
        </div>
        <div class="health-item">
            <span>mb_regex_encoding()</span>
            <strong class="<?= ($checks['pdf_runtime']['mb_regex_encoding'] ?? false) ? 'text-success' : 'text-danger' ?>">
                <?= ($checks['pdf_runtime']['mb_regex_encoding'] ?? false) ? 'OK' : 'MISSING' ?>
            </strong>
        </div>
        <div class="health-item">
            <span>imagecreatetruecolor()</span>
            <strong class="<?= ($checks['pdf_runtime']['imagecreatetruecolor'] ?? false) ? 'text-success' : 'text-danger' ?>">
                <?= ($checks['pdf_runtime']['imagecreatetruecolor'] ?? false) ? 'OK' : 'MISSING' ?>
            </strong>
        </div>
        <div class="health-item">
            <span>finfo_open()</span>
            <strong class="<?= ($checks['pdf_runtime']['finfo_open'] ?? false) ? 'text-success' : 'text-danger' ?>">
                <?= ($checks['pdf_runtime']['finfo_open'] ?? false) ? 'OK' : 'MISSING' ?>
            </strong>
        </div>
        <div class="health-item">
            <span>PDF Temp Dir</span>
            <strong class="<?= ($checks['pdf_runtime']['temp_dir']['writable'] ?? false) ? 'text-success' : 'text-danger' ?>">
                <?= ($checks['pdf_runtime']['temp_dir']['writable'] ?? false) ? 'WRITABLE' : 'NOT WRITABLE' ?>
            </strong>
        </div>
        <div class="health-item">
            <span>PDF Temp Path</span>
            <strong><?= h((string) ($checks['pdf_runtime']['temp_dir']['path'] ?? '')) ?></strong>
        </div>
    </div>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';

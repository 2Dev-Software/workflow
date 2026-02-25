<?php
require_once __DIR__ . '/../../helpers.php';

$dashboard_counts = (array) ($dashboard_counts ?? []);
$dashboard_shortcuts = (array) ($dashboard_shortcuts ?? []);

$visible_shortcuts = array_values(array_filter($dashboard_shortcuts, static function ($shortcut): bool {
    return !empty($shortcut['visible']);
}));

ob_start();
?>
<div class="dashboard-container">
    <!-- <div class="content waiting-circular">
        <div class="content-header">
            <p>หนังสือเวียนใหม่ที่รออ่าน</p>
        </div>
        <div class="content-info">
            <p><?= h((string) ((int) ($dashboard_counts['unread_circulars'] ?? 0))) ?></p>
            <i class="fa-book fa-solid"></i>
        </div>
    </div>
    <div class="content waiting-official-order">
        <div class="content-header">
            <p>คำสั่งราชการใหม่ที่รออ่าน</p>
        </div>
        <div class="content-info">
            <p><?= h((string) ((int) ($dashboard_counts['unread_orders'] ?? 0))) ?></p>
            <i class="fa-book fa-solid"></i>
        </div>
    </div>
    <div class="content waiting-manager">
        <div class="content-header">
            <p>หนังสือที่รอเสนอผู้บริหาร</p>
        </div>
        <div class="content-info">
            <p><?= h((string) ((int) ($dashboard_counts['pending_manager'] ?? 0))) ?></p>
            <i class="fa-book fa-solid"></i>
        </div>
    </div>
    <div class="content waiting-approve">
        <div class="content-header">
            <p>หนังสือที่รอเซ็นอนุมัติ</p>
        </div>
        <div class="content-info">
            <p><?= h((string) ((int) ($dashboard_counts['pending_approvals'] ?? 0))) ?></p>
            <i class="fa-book fa-solid"></i>
        </div>
    </div> -->

    <div class="dashboard-header">
        <p><strong>แผนผังระบบสำนักงานอิเล็กทรอนิกส์</strong></p>
    </div>

    <div class="dashboard-content">
        <?php foreach ($visible_shortcuts as $shortcut) : ?>
            <?php
            $shortcut_href = trim((string) ($shortcut['href'] ?? '#'));
            $shortcut_icon = trim((string) ($shortcut['icon'] ?? 'fa-link'));
            $shortcut_label = trim((string) ($shortcut['label'] ?? ''));
            ?>
            <a href="<?= h($shortcut_href !== '' ? $shortcut_href : '#') ?>">
                <div class="card-shortcut">
                    <i class="fa-solid <?= h($shortcut_icon) ?>"></i>
                    <p><strong><?= h($shortcut_label) ?></strong></p>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';

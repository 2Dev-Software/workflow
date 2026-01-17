<?php
if (empty($alert) || !is_array($alert)) {
    return;
}

$alert_type = (string) ($alert['type'] ?? 'danger');
$allowed_types = ['success', 'warning', 'danger'];
if (!in_array($alert_type, $allowed_types, true)) {
    $alert_type = 'danger';
}

$alert_title = (string) ($alert['title'] ?? '');
$alert_message = (string) ($alert['message'] ?? '');
$alert_button_label = (string) ($alert['button_label'] ?? 'ยืนยัน');
$alert_auto = (bool) ($alert['auto'] ?? false);
$alert_hide_button = (bool) ($alert['hide_button'] ?? false);
$alert_redirect = (string) ($alert['redirect'] ?? '');
$alert_delay_ms = (int) ($alert['delay_ms'] ?? 1000);
if ($alert_delay_ms < 0) {
    $alert_delay_ms = 0;
}

$icon_map = [
    'success' => 'fa-check',
    'warning' => 'fa-triangle-exclamation',
    'danger' => 'fa-xmark',
];
$alert_icon = $icon_map[$alert_type] ?? 'fa-xmark';
?>

<div
    class="alert-overlay"
    data-alert-redirect="<?= htmlspecialchars($alert_redirect, ENT_QUOTES, 'UTF-8') ?>"
    data-alert-delay="<?= htmlspecialchars((string) $alert_delay_ms, ENT_QUOTES, 'UTF-8') ?>">
    <div class="alert-box <?= htmlspecialchars($alert_type, ENT_QUOTES, 'UTF-8') ?><?= $alert_auto ? ' auto' : '' ?>">
        <div class="alert-header">
            <div class="icon-circle"><i class="fa-solid <?= htmlspecialchars($alert_icon, ENT_QUOTES, 'UTF-8') ?>"></i></div>
        </div>
        <div class="alert-body">
            <h1><?= htmlspecialchars($alert_title, ENT_QUOTES, 'UTF-8') ?></h1>
            <?php if ($alert_message !== '') : ?>
                <p><?= htmlspecialchars($alert_message, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
            <?php if (!$alert_hide_button && $alert_button_label !== '') : ?>
                <button type="button" class="btn-close-alert" data-alert-close="true">
                    <?= htmlspecialchars($alert_button_label, ENT_QUOTES, 'UTF-8') ?>
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($alert_redirect !== '') : ?>
    <script>
        window.setTimeout(function () {
            window.location.href = <?= json_encode($alert_redirect, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
        }, <?= $alert_delay_ms ?>);
    </script>
<?php endif; ?>

<script>
    document.querySelectorAll('[data-alert-close="true"]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const overlay = btn.closest('.alert-overlay');
            if (overlay) {
                overlay.remove();
            }
        });
    });
</script>

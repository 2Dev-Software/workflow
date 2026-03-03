<?php
if (empty($alert) || !is_array($alert)) {
    return;
}

$alert_type = (string) ($alert['type'] ?? 'danger');
$allowed_types = ['success', 'warning', 'danger', 'info'];

if (!in_array($alert_type, $allowed_types, true)) {
    $alert_type = 'danger';
}

$payload = [
    'type' => $alert_type,
    'title' => (string) ($alert['title'] ?? ''),
    'message' => (string) ($alert['message'] ?? ''),
    'button_label' => (string) ($alert['button_label'] ?? 'ยืนยัน'),
    'hide_button' => (bool) ($alert['hide_button'] ?? false),
    'redirect' => (string) ($alert['redirect'] ?? ''),
    'delay_ms' => max(0, (int) ($alert['delay_ms'] ?? 1000)),
];

$json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

if ($json === false) {
    return;
}
?>
<div class="js-app-alert-payload" data-app-alert="<?= htmlspecialchars($json, ENT_QUOTES, 'UTF-8') ?>" hidden></div>

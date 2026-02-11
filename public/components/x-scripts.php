<?php
// Cache-busting: avoid stale JS while the app is actively being refactored.
$asset_version = static function (string $relativePath): string {
    $relativePath = ltrim($relativePath, '/');
    $fullPath = __DIR__ . '/../../' . $relativePath;
    $mtime = @filemtime($fullPath);
    return $mtime ? (string) $mtime : '0.1.0-beta';
};
?>

<script src="assets/js/vendor/jquery-3.7.1.min.js?v=<?= htmlspecialchars($asset_version('assets/js/vendor/jquery-3.7.1.min.js'), ENT_QUOTES, 'UTF-8') ?>"></script>

<script src="assets/js/main.js?v=<?= htmlspecialchars($asset_version('assets/js/main.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="assets/js/app.js?v=<?= htmlspecialchars($asset_version('assets/js/app.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="assets/js/modules/ajax.js?v=<?= htmlspecialchars($asset_version('assets/js/modules/ajax.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="assets/js/modules/modal.js?v=<?= htmlspecialchars($asset_version('assets/js/modules/modal.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="assets/js/modules/toast.js?v=<?= htmlspecialchars($asset_version('assets/js/modules/toast.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="assets/js/modules/form.js?v=<?= htmlspecialchars($asset_version('assets/js/modules/form.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="assets/js/modules/upload.js?v=<?= htmlspecialchars($asset_version('assets/js/modules/upload.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="assets/js/modules/setting-select.js?v=<?= htmlspecialchars($asset_version('assets/js/modules/setting-select.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="assets/js/modules/vehicle-management.js?v=<?= htmlspecialchars($asset_version('assets/js/modules/vehicle-management.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="assets/js/modules/room-booking.js?v=<?= htmlspecialchars($asset_version('assets/js/modules/room-booking.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="assets/js/modules/room-booking-approval.js?v=<?= htmlspecialchars($asset_version('assets/js/modules/room-booking-approval.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="assets/js/modules/room-management.js?v=<?= htmlspecialchars($asset_version('assets/js/modules/room-management.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="assets/js/modules/circular-notice.js?v=<?= htmlspecialchars($asset_version('assets/js/modules/circular-notice.js'), ENT_QUOTES, 'UTF-8') ?>"></script>

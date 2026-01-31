<?php
require_once __DIR__ . '/src/Services/auth/auth-guard.php';
require_once __DIR__ . '/src/Services/security/security-service.php';
require_once __DIR__ . '/app/auth/csrf.php';
require_once __DIR__ . '/app/modules/circulars/repository.php';
require_once __DIR__ . '/app/modules/circulars/service.php';
require_once __DIR__ . '/app/rbac/current_user.php';

$current_user = current_user();
$current_pid = (string) ($current_user['pID'] ?? '');

$alert = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $alert = ['type' => 'danger', 'title' => 'ไม่สามารถยืนยันความปลอดภัย', 'message' => 'กรุณาลองใหม่อีกครั้ง'];
    } else {
        $action = $_POST['action'] ?? '';
        $circular_id = isset($_POST['circular_id']) ? (int) $_POST['circular_id'] : 0;
        if ($action === 'recall' && $circular_id > 0) {
            $ok = circular_recall_internal($circular_id, $current_pid);
            $alert = $ok
                ? ['type' => 'success', 'title' => 'ดึงหนังสือกลับแล้ว', 'message' => '']
                : ['type' => 'warning', 'title' => 'ไม่สามารถดึงกลับได้', 'message' => 'มีผู้รับอ่านแล้ว'];
        } elseif ($action === 'resend' && $circular_id > 0) {
            $ok = circular_resend_internal($circular_id, $current_pid);
            $alert = $ok
                ? ['type' => 'success', 'title' => 'ส่งใหม่เรียบร้อย', 'message' => '']
                : ['type' => 'warning', 'title' => 'ไม่สามารถส่งใหม่ได้', 'message' => ''];
        }
    }
}

$sent_items = circular_list_sent($current_pid);
?>
<!DOCTYPE html>
<html lang="th">
<?php require_once __DIR__ . '/public/components/x-head.php'; ?>
<body>
    <?php require_once __DIR__ . '/public/components/layout/preloader.php'; ?>
    <?php if (!empty($alert)) : ?>
        <?php require __DIR__ . '/public/components/x-alert.php'; ?>
    <?php endif; ?>
    <?php require_once __DIR__ . '/public/components/partials/x-sidebar.php'; ?>

    <section class="main-section">
        <?php require_once __DIR__ . '/public/components/partials/x-navigation.php'; ?>
        <main class="content-wrapper">
            <div class="content-header">
                <h1>หนังสือเวียนที่ส่งแล้ว</h1>
                <p>ติดตามสถานะการอ่าน</p>
            </div>

            <section class="enterprise-card">
                <div class="enterprise-card-header">
                    <div class="enterprise-card-title-group">
                        <h2 class="enterprise-card-title">รายการหนังสือเวียนที่ส่งแล้ว</h2>
                        <p class="enterprise-card-subtitle">ติดตามการอ่านและการดึงกลับ</p>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="custom-table">
                    <thead>
                        <tr>
                            <th>เรื่อง</th>
                            <th>ประเภท</th>
                            <th>สถานะ</th>
                            <th>อ่านแล้ว/ทั้งหมด</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($sent_items)) : ?>
                            <tr><td colspan="5" class="enterprise-empty">ไม่มีรายการ</td></tr>
                        <?php else : ?>
                            <?php foreach ($sent_items as $item) : ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['subject'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($item['circularType'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($item['status'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= (int) ($item['readCount'] ?? 0) ?>/<?= (int) ($item['recipientCount'] ?? 0) ?></td>
                                    <td>
                                        <?php if (($item['circularType'] ?? '') === 'INTERNAL') : ?>
                                            <form method="POST" class="enterprise-inline-form">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="recall">
                                                <input type="hidden" name="circular_id" value="<?= (int) $item['circularID'] ?>">
                                                <button type="submit" class="btn">ดึงกลับ</button>
                                            </form>
                                            <?php if (($item['status'] ?? '') === 'RECALLED') : ?>
                                                <form method="POST" class="enterprise-inline-form">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="resend">
                                                    <input type="hidden" name="circular_id" value="<?= (int) $item['circularID'] ?>">
                                                    <button type="submit" class="btn">ส่งใหม่</button>
                                                </form>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    </table>
                </div>
            </section>
        </main>

        <?php require_once __DIR__ . '/public/components/partials/x-footer.php'; ?>
    </section>
    <?php require_once __DIR__ . '/public/components/x-scripts.php'; ?>
</body>
</html>

<?php
require_once __DIR__ . '/src/Services/auth/auth-guard.php';
require_once __DIR__ . '/src/Services/security/security-service.php';
require_once __DIR__ . '/app/config/constants.php';
require_once __DIR__ . '/app/auth/csrf.php';
require_once __DIR__ . '/app/rbac/current_user.php';
require_once __DIR__ . '/app/modules/circulars/repository.php';
require_once __DIR__ . '/app/modules/system/system.php';
require_once __DIR__ . '/app/modules/audit/logger.php';
require_once __DIR__ . '/app/rbac/roles.php';

$current_user = current_user();
$current_pid = $current_user['pID'] ?? '';
$position_ids = current_user_position_ids();
$connection = db_connection();
$is_registry = rbac_user_has_role($connection, (string) $current_pid, ROLE_REGISTRY);
if (!$is_registry && (int) ($current_user['roleID'] ?? 0) === 2) {
    $is_registry = true;
}
$director_pid = system_get_current_director_pid();
$is_director_box = $director_pid !== null && $director_pid === $current_pid;

$box = $_GET['box'] ?? 'normal';
$archived = isset($_GET['archived']) && $_GET['archived'] === '1';

$box_map = [
    'normal' => INBOX_TYPE_NORMAL,
    'director' => INBOX_TYPE_DIRECTOR,
    'clerk' => INBOX_TYPE_CLERK,
    'clerk_return' => INBOX_TYPE_CLERK_RETURN,
];
$box_key = array_key_exists($box, $box_map) ? $box : 'normal';
$inbox_type = $box_map[$box_key];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $alert = [
            'type' => 'danger',
            'title' => 'ไม่สามารถยืนยันความปลอดภัย',
            'message' => 'กรุณาลองใหม่อีกครั้ง',
        ];
    } else {
        $action = $_POST['action'] ?? '';
        $inbox_id = (int) ($_POST['inbox_id'] ?? 0);
        if ($action === 'archive' && $inbox_id > 0) {
            circular_archive_inbox($inbox_id, (string) $current_pid);
            $alert = [
                'type' => 'success',
                'title' => 'จัดเก็บเรียบร้อย',
                'message' => '',
            ];
        } elseif ($action === 'unarchive' && $inbox_id > 0) {
            circular_unarchive_inbox($inbox_id, (string) $current_pid);
            $alert = [
                'type' => 'success',
                'title' => 'ย้ายกลับเรียบร้อย',
                'message' => '',
            ];
        }
    }
}

$circular_inbox = circular_get_inbox((string) $current_pid, $inbox_type, $archived);
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
                <h1>ยินดีต้อนรับ</h1>
                <p>หนังสือเวียน / กล่องข้อความ</p>
            </div>

            <div class="enterprise-tabs">
                <a class="enterprise-tab<?= $box_key === 'normal' ? ' is-active' : '' ?>" href="circular-notice.php?box=normal">กล่องเข้า</a>
                <?php if ($is_director_box) : ?>
                    <a class="enterprise-tab<?= $box_key === 'director' ? ' is-active' : '' ?>" href="circular-notice.php?box=director">กล่องพิเศษ ผอ.</a>
                <?php endif; ?>
                <?php if ($is_registry) : ?>
                    <a class="enterprise-tab<?= $box_key === 'clerk_return' ? ' is-active' : '' ?>" href="circular-notice.php?box=clerk_return">กล่องคืนจากผอ.</a>
                <?php endif; ?>
                <a class="enterprise-tab" href="circular-notice.php?box=<?= htmlspecialchars($box_key, ENT_QUOTES, 'UTF-8') ?>&archived=<?= $archived ? '0' : '1' ?>">
                    <?= $archived ? 'กลับไปกล่องเข้า' : 'ดูที่จัดเก็บ' ?>
                </a>
            </div>

            <section class="enterprise-card">
                <div class="enterprise-card-header">
                    <div class="enterprise-card-title-group">
                        <h2 class="enterprise-card-title">รายการหนังสือเวียน</h2>
                        <p class="enterprise-card-subtitle">กล่องข้อความ <?= $archived ? 'ที่จัดเก็บ' : 'เข้า' ?></p>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="custom-table">
                    <thead>
                        <tr>
                            <th>สถานะ</th>
                            <th>เรื่อง</th>
                            <th>ผู้ส่ง</th>
                            <th>เวลา</th>
                            <th>การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($circular_inbox)) : ?>
                            <tr>
                                <td colspan="5" class="enterprise-empty">ไม่มีรายการ</td>
                            </tr>
                        <?php else : ?>
                            <?php foreach ($circular_inbox as $item) : ?>
                                <tr>
                                    <td><?= (int) ($item['isRead'] ?? 0) === 1 ? 'อ่านแล้ว' : 'ยังไม่อ่าน' ?></td>
                                    <td>
                                        <a href="circular-view.php?inbox_id=<?= (int) $item['inboxID'] ?>">
                                            <?= htmlspecialchars($item['subject'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars($item['senderName'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($item['deliveredAt'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <form method="POST" class="enterprise-inline-form">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="inbox_id" value="<?= (int) $item['inboxID'] ?>">
                                            <?php if ($archived) : ?>
                                                <input type="hidden" name="action" value="unarchive">
                                                <button type="submit" class="btn">ย้ายกลับ</button>
                                            <?php else : ?>
                                                <input type="hidden" name="action" value="archive">
                                                <button type="submit" class="btn">จัดเก็บ</button>
                                            <?php endif; ?>
                                        </form>
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

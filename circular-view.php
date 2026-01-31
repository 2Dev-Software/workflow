<?php
require_once __DIR__ . '/src/Services/auth/auth-guard.php';
require_once __DIR__ . '/src/Services/security/security-service.php';
require_once __DIR__ . '/app/auth/csrf.php';
require_once __DIR__ . '/app/rbac/current_user.php';
require_once __DIR__ . '/app/modules/circulars/service.php';
require_once __DIR__ . '/app/modules/circulars/repository.php';
require_once __DIR__ . '/app/modules/users/lists.php';
require_once __DIR__ . '/app/modules/system/system.php';
require_once __DIR__ . '/app/rbac/roles.php';

$current_user = current_user();
$current_pid = (string) ($current_user['pID'] ?? '');
$position_ids = current_user_position_ids();
$connection = db_connection();
$is_registry = rbac_user_has_role($connection, $current_pid, ROLE_REGISTRY);
if (!$is_registry && (int) ($current_user['roleID'] ?? 0) === 2) {
    $is_registry = true;
}
$is_deputy = in_array(2, $position_ids, true);
$director_pid = system_get_current_director_pid();
$is_director = $director_pid !== null && $director_pid === $current_pid;

$inbox_id = isset($_GET['inbox_id']) ? (int) $_GET['inbox_id'] : 0;
if ($inbox_id <= 0) {
    header('Location: circular-notice.php', true, 302);
    exit();
}

$item = circular_get_inbox_item($inbox_id, $current_pid);
if (!$item) {
    header('Location: circular-notice.php', true, 302);
    exit();
}

if ((int) ($item['isRead'] ?? 0) === 0) {
    circular_mark_read($inbox_id, $current_pid);
}

$alert = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $alert = [
            'type' => 'danger',
            'title' => 'ไม่สามารถยืนยันความปลอดภัย',
            'message' => 'กรุณาลองใหม่อีกครั้ง',
        ];
    } else {
        $action = $_POST['action'] ?? '';
        try {
            if ($action === 'archive') {
                circular_archive_inbox($inbox_id, $current_pid);
                $alert = ['type' => 'success', 'title' => 'จัดเก็บเรียบร้อย', 'message' => ''];
            }

            if ($action === 'forward') {
                $faction_ids = $_POST['faction_ids'] ?? [];
                $role_ids = $_POST['role_ids'] ?? [];
                $person_ids = $_POST['person_ids'] ?? [];

                $targets = [];
                foreach ((array) $faction_ids as $fid) {
                    $targets[] = ['targetType' => 'UNIT', 'fID' => (int) $fid];
                }
                foreach ((array) $role_ids as $rid) {
                    $targets[] = ['targetType' => 'ROLE', 'roleID' => (int) $rid];
                }
                foreach ((array) $person_ids as $pid) {
                    $targets[] = ['targetType' => 'PERSON', 'pID' => (string) $pid];
                }

                $pids = circular_resolve_person_ids((array) $faction_ids, (array) $role_ids, (array) $person_ids);
                circular_forward((int) $item['circularID'], $current_pid, ['pids' => $pids, 'targets' => $targets]);
                $alert = ['type' => 'success', 'title' => 'ส่งต่อเรียบร้อย', 'message' => ''];
            }

            if ($action === 'director_review' && $is_director) {
                $comment = trim((string) ($_POST['comment'] ?? ''));
                $new_fid = isset($_POST['extGroupFID']) ? (int) $_POST['extGroupFID'] : null;
                circular_director_review((int) $item['circularID'], $current_pid, $comment !== '' ? $comment : null, $new_fid && $new_fid > 0 ? $new_fid : null);
                $alert = ['type' => 'success', 'title' => 'ส่งกลับสารบรรณแล้ว', 'message' => ''];
            }

            if ($action === 'clerk_forward' && $is_registry) {
                $fID = isset($_POST['extGroupFID']) ? (int) $_POST['extGroupFID'] : null;
                circular_registry_forward_to_deputy((int) $item['circularID'], $current_pid, $fID && $fID > 0 ? $fID : null);
                $alert = ['type' => 'success', 'title' => 'ส่งต่อรองผู้อำนวยการแล้ว', 'message' => ''];
            }

            if ($action === 'deputy_distribute' && $is_deputy) {
                $faction_ids = $_POST['faction_ids'] ?? [];
                $role_ids = $_POST['role_ids'] ?? [];
                $person_ids = $_POST['person_ids'] ?? [];
                $comment = trim((string) ($_POST['comment'] ?? ''));
                $targets = [];
                foreach ((array) $faction_ids as $fid) {
                    $targets[] = ['targetType' => 'UNIT', 'fID' => (int) $fid];
                }
                foreach ((array) $role_ids as $rid) {
                    $targets[] = ['targetType' => 'ROLE', 'roleID' => (int) $rid];
                }
                foreach ((array) $person_ids as $pid) {
                    $targets[] = ['targetType' => 'PERSON', 'pID' => (string) $pid];
                }
                $pids = circular_resolve_person_ids((array) $faction_ids, (array) $role_ids, (array) $person_ids);
                circular_deputy_distribute((int) $item['circularID'], $current_pid, ['pids' => $pids, 'targets' => $targets], $comment !== '' ? $comment : null);
                $alert = ['type' => 'success', 'title' => 'กระจายหนังสือเรียบร้อย', 'message' => ''];
            }

            if ($action === 'announce') {
                circular_set_announcement((int) $item['circularID'], $current_pid);
                $alert = ['type' => 'success', 'title' => 'ตั้งเป็นข่าวประชาสัมพันธ์แล้ว', 'message' => ''];
            }
        } catch (Throwable $e) {
            $alert = ['type' => 'danger', 'title' => 'เกิดข้อผิดพลาด', 'message' => 'โปรดลองอีกครั้ง'];
        }
    }
}

$attachments = circular_get_attachments((int) $item['circularID']);
$factions = user_list_factions();
$roles = user_list_roles();
$teachers = user_list_teachers();
?>
<!DOCTYPE html>
<html lang="th">
<?php require_once __DIR__ . '/public/components/x-head.php'; ?>
<body>
    <?php require_once __DIR__ . '/public/components/layout/preloader.php'; ?>
    <?php if ($alert) : ?>
        <?php require __DIR__ . '/public/components/x-alert.php'; ?>
    <?php endif; ?>

    <?php require_once __DIR__ . '/public/components/partials/x-sidebar.php'; ?>

    <section class="main-section">
        <?php require_once __DIR__ . '/public/components/partials/x-navigation.php'; ?>

        <main class="content-wrapper">
            <div class="content-header">
                <h1><?= htmlspecialchars($item['subject'] ?? '', ENT_QUOTES, 'UTF-8') ?></h1>
                <p>หนังสือเวียน</p>
            </div>

            <?php
            $status_raw = strtoupper(trim((string) ($item['status'] ?? '')));
            $status_class = 'pending';
            if (in_array($status_raw, ['READ', 'FORWARDED', 'SENT', 'DISTRIBUTED', 'APPROVED'], true)) {
                $status_class = 'approved';
            } elseif (in_array($status_raw, ['REJECTED', 'CANCELLED'], true)) {
                $status_class = 'rejected';
            }
            ?>
            <section class="enterprise-card">
                <div class="enterprise-card-header">
                    <div class="enterprise-card-title-group">
                        <h2 class="enterprise-card-title">รายละเอียดหนังสือเวียน</h2>
                        <p class="enterprise-card-subtitle">ข้อมูลผู้ส่ง สถานะ และเอกสารแนบ</p>
                    </div>
                    <span class="status-pill <?= htmlspecialchars($status_class, ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars($item['status'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </div>

                <div class="enterprise-info">
                    <div class="enterprise-info-row">
                        <span class="enterprise-info-label">ผู้ส่ง</span>
                        <span class="enterprise-info-value"><?= htmlspecialchars($item['senderName'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="enterprise-info-row">
                        <span class="enterprise-info-label">ประเภท</span>
                        <span class="enterprise-info-value"><?= htmlspecialchars($item['circularType'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                </div>

                <?php if (!empty($item['detail'])) : ?>
                    <div class="enterprise-divider"></div>
                    <div class="enterprise-panel">
                        <p><strong>รายละเอียด:</strong></p>
                        <p><?= nl2br(htmlspecialchars((string) $item['detail'], ENT_QUOTES, 'UTF-8')) ?></p>
                    </div>
                <?php endif; ?>
                <?php if (!empty($item['linkURL'])) : ?>
                    <div class="enterprise-divider"></div>
                    <div class="enterprise-panel">
                        <p><strong>ลิงก์:</strong>
                            <a href="<?= htmlspecialchars($item['linkURL'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">เปิดลิงก์</a>
                        </p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($attachments)) : ?>
                    <div class="enterprise-divider"></div>
                    <div class="enterprise-panel">
                        <p><strong>ไฟล์แนบ:</strong></p>
                        <div class="attachment-list">
                            <?php foreach ($attachments as $file) : ?>
                                <div class="attachment-item">
                                    <span class="attachment-name">
                                        <?= htmlspecialchars($file['fileName'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                    <?php if (!empty($file['fileID'])) : ?>
                                        <a class="attachment-link" href="public/api/file-download.php?module=circulars&entity_id=<?= (int) $item['circularID'] ?>&file_id=<?= (int) $file['fileID'] ?>" target="_blank" rel="noopener">ดูไฟล์</a>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </section>

            <section class="enterprise-card">
                <div class="enterprise-card-header">
                    <div class="enterprise-card-title-group">
                        <h2 class="enterprise-card-title">การจัดการหนังสือเวียน</h2>
                        <p class="enterprise-card-subtitle">จัดเก็บ ส่งต่อ และดำเนินการตามบทบาท</p>
                    </div>
                </div>

                <div class="enterprise-form-grid">
                <form method="POST" class="enterprise-panel">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="archive">
                    <button type="submit" class="btn">จัดเก็บ</button>
                </form>

                <?php if ($is_director && $item['circularType'] === 'EXTERNAL' && $item['status'] === 'SENT') : ?>
                    <form method="POST" class="enterprise-panel">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="director_review">
                        <label>หมายเหตุ (ผอ.)</label>
                        <textarea name="comment" rows="3" class="form-input booking-textarea"></textarea>
                        <label>กำหนดฝ่าย (fID) ใหม่ (ถ้ามี)</label>
                        <input type="number" name="extGroupFID" class="form-input">
                        <button type="submit" class="btn">ส่งกลับสารบรรณ</button>
                    </form>
                <?php endif; ?>

                <?php if ($is_registry && $item['circularType'] === 'EXTERNAL' && in_array($item['status'], ['RETURNED', 'FORWARDED'], true)) : ?>
                    <form method="POST" class="enterprise-panel">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="clerk_forward">
                        <input type="number" name="extGroupFID" placeholder="ระบุฝ่าย (fID)" required class="form-input">
                        <button type="submit" class="btn">ส่งต่อรองผอ.</button>
                    </form>
                <?php endif; ?>

                <?php if ($is_deputy && $item['circularType'] === 'EXTERNAL' && ($item['status'] ?? '') === 'FORWARDED') : ?>
                    <form method="POST" class="enterprise-panel">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="deputy_distribute">
                        <label>ส่งต่อไปยังฝ่าย/บทบาท/บุคคล</label>
                        <textarea name="comment" rows="2" placeholder="ความคิดเห็น (ถ้ามี)" class="form-input booking-textarea"></textarea>
                        <select name="faction_ids[]" multiple class="form-input">
                            <?php foreach ($factions as $faction) : ?>
                                <option value="<?= (int) $faction['fID'] ?>"><?= htmlspecialchars($faction['fName'] ?? '', ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="role_ids[]" multiple class="form-input">
                            <?php foreach ($roles as $role) : ?>
                                <option value="<?= (int) $role['roleID'] ?>"><?= htmlspecialchars($role['roleName'] ?? '', ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="person_ids[]" multiple class="form-input">
                            <?php foreach ($teachers as $teacher) : ?>
                                <option value="<?= htmlspecialchars($teacher['pID'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($teacher['fName'] ?? '', ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn">กระจายหนังสือ</button>
                    </form>
                <?php endif; ?>

                <form method="POST" class="enterprise-panel">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="forward">
                    <label>ส่งต่อ</label>
                    <select name="faction_ids[]" multiple class="form-input">
                        <?php foreach ($factions as $faction) : ?>
                            <option value="<?= (int) $faction['fID'] ?>"><?= htmlspecialchars($faction['fName'] ?? '', ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="role_ids[]" multiple class="form-input">
                        <?php foreach ($roles as $role) : ?>
                            <option value="<?= (int) $role['roleID'] ?>"><?= htmlspecialchars($role['roleName'] ?? '', ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="person_ids[]" multiple class="form-input">
                        <?php foreach ($teachers as $teacher) : ?>
                            <option value="<?= htmlspecialchars($teacher['pID'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($teacher['fName'] ?? '', ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn">ส่งต่อ</button>
                </form>

                <?php if (in_array(2, $position_ids, true)) : ?>
                    <form method="POST" class="enterprise-panel">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="announce">
                        <button type="submit" class="btn">ตั้งเป็นข่าวประชาสัมพันธ์</button>
                    </form>
                <?php endif; ?>
                </div>
            </section>
        </main>

        <?php require_once __DIR__ . '/public/components/partials/x-footer.php'; ?>
    </section>

    <?php require_once __DIR__ . '/public/components/x-scripts.php'; ?>
</body>
</html>

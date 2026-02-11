<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../auth/csrf.php';

$item = $item ?? [];
$attachments = (array) ($attachments ?? []);
$factions = (array) ($factions ?? []);
$roles = (array) ($roles ?? []);
$teachers = (array) ($teachers ?? []);
$is_registry = (bool) ($is_registry ?? false);
$is_deputy = (bool) ($is_deputy ?? false);
$is_director = (bool) ($is_director ?? false);
$position_ids = (array) ($position_ids ?? []);
$sender_name = trim((string) ($item['senderName'] ?? ''));
$sender_faction_name = trim((string) ($item['senderFactionName'] ?? ''));
$sender_display = '-';
if ($sender_name !== '' && $sender_faction_name !== '') {
    $sender_display = $sender_name . ' / ' . $sender_faction_name;
} elseif ($sender_name !== '') {
    $sender_display = $sender_name;
} elseif ($sender_faction_name !== '') {
    $sender_display = $sender_faction_name;
}

$status_raw = strtoupper(trim((string) ($item['status'] ?? '')));
$status_label_map = [
    INTERNAL_STATUS_DRAFT => 'ร่าง',
    INTERNAL_STATUS_SENT => 'ส่งแล้ว',
    INTERNAL_STATUS_RECALLED => 'ดึงกลับ',
    INTERNAL_STATUS_ARCHIVED => 'จัดเก็บ',
    EXTERNAL_STATUS_SUBMITTED => 'รับเข้าแล้ว',
    EXTERNAL_STATUS_PENDING_REVIEW => 'รอพิจารณา',
    EXTERNAL_STATUS_REVIEWED => 'พิจารณาแล้ว',
    EXTERNAL_STATUS_FORWARDED => 'ส่งแล้ว',
];
$status_label = $status_label_map[$status_raw] ?? $status_raw;
$status_class = 'pending';
if (in_array($status_raw, [INTERNAL_STATUS_SENT, INTERNAL_STATUS_ARCHIVED, EXTERNAL_STATUS_REVIEWED, EXTERNAL_STATUS_FORWARDED], true)) {
    $status_class = 'approved';
} elseif (in_array($status_raw, [INTERNAL_STATUS_RECALLED], true)) {
    $status_class = 'rejected';
}

ob_start();
?>
<div class="content-header">
    <h1><?= h((string) ($item['subject'] ?? '')) ?></h1>
    <p>หนังสือเวียน</p>
</div>

<section class="enterprise-card">
    <div class="enterprise-card-header">
        <div class="enterprise-card-title-group">
            <h2 class="enterprise-card-title">รายละเอียดหนังสือเวียน</h2>
            <p class="enterprise-card-subtitle">ข้อมูลผู้ส่ง สถานะ และเอกสารแนบ</p>
        </div>
        <span class="status-pill <?= h($status_class) ?>">
            <?= h((string) $status_label) ?>
        </span>
    </div>

    <div class="enterprise-info">
        <div class="enterprise-info-row">
            <span class="enterprise-info-label">ผู้ส่ง</span>
            <span class="enterprise-info-value"><?= h($sender_display) ?></span>
        </div>
        <div class="enterprise-info-row">
            <span class="enterprise-info-label">ประเภท</span>
            <span class="enterprise-info-value"><?= h((string) ($item['circularType'] ?? '')) ?></span>
        </div>
    </div>

    <?php if (!empty($item['detail'])) : ?>
        <div class="enterprise-divider"></div>
        <div class="enterprise-panel">
            <p><strong>รายละเอียด:</strong></p>
            <p><?= nl2br(h((string) $item['detail'])) ?></p>
        </div>
    <?php endif; ?>
    <?php if (!empty($item['linkURL'])) : ?>
        <div class="enterprise-divider"></div>
        <div class="enterprise-panel">
            <p><strong>ลิงก์:</strong>
                <a href="<?= h((string) $item['linkURL']) ?>" target="_blank" rel="noopener">เปิดลิงก์</a>
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
                            <?= h((string) ($file['fileName'] ?? '')) ?>
                        </span>
                        <?php if (!empty($file['fileID'])) : ?>
                            <a class="attachment-link" href="public/api/file-download.php?module=circulars&entity_id=<?= h((string) (int) ($item['circularID'] ?? 0)) ?>&file_id=<?= h((string) (int) ($file['fileID'] ?? 0)) ?>" target="_blank" rel="noopener">ดูไฟล์</a>
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

        <?php if ($is_director && ($item['circularType'] ?? '') === 'EXTERNAL' && ($item['status'] ?? '') === EXTERNAL_STATUS_PENDING_REVIEW) : ?>
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

        <?php if ($is_registry && ($item['circularType'] ?? '') === 'EXTERNAL' && in_array((string) ($item['status'] ?? ''), [EXTERNAL_STATUS_REVIEWED, EXTERNAL_STATUS_FORWARDED], true)) : ?>
            <form method="POST" class="enterprise-panel">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="clerk_forward">
                <input type="number" name="extGroupFID" placeholder="ระบุฝ่าย (fID)" required class="form-input">
                <button type="submit" class="btn">ส่งต่อรองผอ.</button>
            </form>
        <?php endif; ?>

        <?php if ($is_deputy && ($item['circularType'] ?? '') === 'EXTERNAL' && ((string) ($item['status'] ?? '')) === EXTERNAL_STATUS_FORWARDED) : ?>
            <form method="POST" class="enterprise-panel">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="deputy_distribute">
                <label>ส่งต่อไปยังฝ่าย/บทบาท/บุคคล</label>
                <textarea name="comment" rows="2" placeholder="ความคิดเห็น (ถ้ามี)" class="form-input booking-textarea"></textarea>
                <select name="faction_ids[]" multiple class="form-input">
                    <?php foreach ($factions as $faction) : ?>
                        <option value="<?= h((string) (int) ($faction['fID'] ?? 0)) ?>"><?= h((string) ($faction['fName'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="role_ids[]" multiple class="form-input">
                    <?php foreach ($roles as $role) : ?>
                        <option value="<?= h((string) (int) ($role['roleID'] ?? 0)) ?>"><?= h((string) ($role['roleName'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="person_ids[]" multiple class="form-input">
                    <?php foreach ($teachers as $teacher) : ?>
                        <option value="<?= h((string) ($teacher['pID'] ?? '')) ?>"><?= h((string) ($teacher['fName'] ?? '')) ?></option>
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
                    <option value="<?= h((string) (int) ($faction['fID'] ?? 0)) ?>"><?= h((string) ($faction['fName'] ?? '')) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="role_ids[]" multiple class="form-input">
                <?php foreach ($roles as $role) : ?>
                    <option value="<?= h((string) (int) ($role['roleID'] ?? 0)) ?>"><?= h((string) ($role['roleName'] ?? '')) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="person_ids[]" multiple class="form-input">
                <?php foreach ($teachers as $teacher) : ?>
                    <option value="<?= h((string) ($teacher['pID'] ?? '')) ?>"><?= h((string) ($teacher['fName'] ?? '')) ?></option>
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
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';

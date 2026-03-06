<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../auth/csrf.php';

$values = array_merge([
    'extPriority' => 'ปกติ',
    'extBookNo' => '',
    'extIssuedDate' => '',
    'subject' => '',
    'extFromText' => '',
    'extGroupFID' => '',
    'linkURL' => '',
    'detail' => '',
    'reviewerPID' => '',
], (array) ($values ?? []));
$factions = (array) ($factions ?? []);
$reviewers = (array) ($reviewers ?? []);
$is_edit_mode = (bool) ($is_edit_mode ?? false);
$edit_circular_id = (int) ($edit_circular_id ?? 0);
$editable_circular = (array) ($editable_circular ?? []);
$existing_attachments = (array) ($existing_attachments ?? []);

$priority_options = [
    'ปกติ' => 'ปกติ',
    'ด่วน' => 'ด่วน',
    'ด่วนมาก' => 'ด่วนมาก',
    'ด่วนที่สุด' => 'ด่วนที่สุด',
];

$faction_options = ['' => 'เลือกกลุ่ม/ฝ่าย'];

foreach ($factions as $faction) {
    $fid = (int) ($faction['fID'] ?? 0);
    $name = trim((string) ($faction['fName'] ?? ''));

    if ($fid <= 0 || $name === '') {
        continue;
    }

    $faction_options[(string) $fid] = $name;
}

$reviewer_options = ['' => 'เลือกผู้พิจารณา'];

foreach ($reviewers as $reviewer) {
    $pid = trim((string) ($reviewer['pID'] ?? ''));
    $label = trim((string) ($reviewer['label'] ?? ''));

    if ($pid === '' || $label === '') {
        continue;
    }

    $reviewer_options[$pid] = $label;
}

$current_status = trim((string) ($editable_circular['status'] ?? ''));
$status_label = $current_status !== '' ? $current_status : '-';
$status_variant = 'pending';

if ($current_status === EXTERNAL_STATUS_SUBMITTED) {
    $status_label = 'กำลังเสนอ';
    $status_variant = 'primary';
} elseif ($current_status === EXTERNAL_STATUS_PENDING_REVIEW) {
    $status_label = 'รอพิจารณา';
    $status_variant = 'pending';
} elseif ($current_status === EXTERNAL_STATUS_REVIEWED) {
    $status_label = 'พิจารณาแล้ว';
    $status_variant = 'approved';
} elseif ($current_status === EXTERNAL_STATUS_FORWARDED) {
    $status_label = 'ส่งแล้ว';
    $status_variant = 'approved';
}

ob_start();
?>
<div class="content-header">
    <h1>ยินดีต้อนรับ</h1>
    <p>หนังสือเวียนภายนอก / ลงทะเบียนรับหนังสือเวียน</p>
</div>

<style>
    .outgoing-receive-page .booking-form-grid {
        align-items: start;
    }

    .outgoing-receive-page .outgoing-receive-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 16px;
    }

    .outgoing-receive-page .outgoing-receive-meta-card {
        min-width: 180px;
        padding: 12px 14px;
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 14px;
        background: #fff;
    }

    .outgoing-receive-page .outgoing-receive-meta-label {
        display: block;
        color: #64748b;
        font-size: 0.88rem;
        margin-bottom: 4px;
    }

    .outgoing-receive-page .outgoing-receive-meta-value {
        color: #0f172a;
        font-weight: 600;
    }

    .outgoing-receive-page .outgoing-receive-file-panel {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 14px;
        padding: 16px;
        background: #fff;
    }

    .outgoing-receive-page .outgoing-receive-file-list {
        margin: 0;
        padding-left: 20px;
    }

    .outgoing-receive-page .outgoing-receive-file-list li + li {
        margin-top: 8px;
    }

    .outgoing-receive-page .outgoing-receive-file-item {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 10px;
    }

    .outgoing-receive-page .outgoing-receive-file-remove {
        color: #b91c1c;
        font-size: 0.92rem;
    }

    .outgoing-receive-page .outgoing-receive-help {
        color: #64748b;
        font-size: 0.92rem;
        margin: 0;
    }

    .outgoing-receive-page .outgoing-receive-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        align-items: center;
    }
</style>

<div class="content-area outgoing-receive-page">
    <section class="booking-card booking-form-card">
        <div class="booking-card-header">
            <div class="booking-card-title-group">
                <h2 class="booking-card-title"><?= h($is_edit_mode ? 'แก้ไขและส่งใหม่' : 'ลงทะเบียนรับหนังสือเวียน') ?></h2>
                <p class="booking-card-subtitle">
                    <?= h($is_edit_mode ? 'แก้ไขข้อมูลหนังสือเวียนภายนอกที่ยังอยู่ระหว่างเสนอ' : 'บันทึกหนังสือเวียนภายนอกและส่งเสนอให้ ผอ./รอง/รักษาการ') ?>
                </p>
            </div>
        </div>

        <?php if ($is_edit_mode && $edit_circular_id > 0) : ?>
            <div class="outgoing-receive-meta">
                <div class="outgoing-receive-meta-card">
                    <span class="outgoing-receive-meta-label">เลขที่รายการ</span>
                    <div class="outgoing-receive-meta-value">#<?= h((string) $edit_circular_id) ?></div>
                </div>
                <div class="outgoing-receive-meta-card">
                    <span class="outgoing-receive-meta-label">สถานะปัจจุบัน</span>
                    <?php component_render('status-pill', [
                        'label' => $status_label,
                        'variant' => $status_variant,
                    ]); ?>
                </div>
                <div class="outgoing-receive-meta-card">
                    <span class="outgoing-receive-meta-label">ไฟล์แนบปัจจุบัน</span>
                    <div class="outgoing-receive-meta-value"><?= h((string) count($existing_attachments)) ?> ไฟล์</div>
                </div>
            </div>
        <?php endif; ?>

        <form class="booking-form" method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <?php if ($is_edit_mode && $edit_circular_id > 0) : ?>
                <input type="hidden" name="edit_circular_id" value="<?= h((string) $edit_circular_id) ?>">
            <?php endif; ?>

            <div class="booking-form-grid">
                <?php component_render('select', [
                    'name' => 'extPriority',
                    'label' => 'ประเภท',
                    'selected' => (string) $values['extPriority'],
                    'options' => $priority_options,
                    'required' => true,
                ]); ?>

                <?php component_render('input', [
                    'name' => 'extBookNo',
                    'label' => 'เลขที่หนังสือ',
                    'value' => (string) $values['extBookNo'],
                    'required' => true,
                ]); ?>

                <?php component_render('input', [
                    'name' => 'extIssuedDate',
                    'label' => 'ลงวันที่',
                    'type' => 'date',
                    'value' => (string) $values['extIssuedDate'],
                    'required' => true,
                ]); ?>

                <?php component_render('input', [
                    'name' => 'extFromText',
                    'label' => 'จาก',
                    'value' => (string) $values['extFromText'],
                    'required' => true,
                ]); ?>

                <?php component_render('input', [
                    'name' => 'subject',
                    'label' => 'เรื่อง',
                    'value' => (string) $values['subject'],
                    'required' => true,
                    'field_class' => 'full',
                ]); ?>

                <?php component_render('select', [
                    'name' => 'extGroupFID',
                    'label' => 'หนังสือของกลุ่ม/ฝ่าย',
                    'selected' => (string) $values['extGroupFID'],
                    'options' => $faction_options,
                    'field_class' => 'full',
                ]); ?>

                <?php component_render('textarea', [
                    'name' => 'detail',
                    'label' => 'รายละเอียด',
                    'value' => (string) $values['detail'],
                    'rows' => 6,
                    'field_class' => 'full',
                    'class' => 'booking-textarea',
                ]); ?>

                <?php component_render('input', [
                    'name' => 'linkURL',
                    'label' => 'แนบลิงก์',
                    'type' => 'url',
                    'value' => (string) $values['linkURL'],
                    'field_class' => 'full',
                    'placeholder' => 'https://',
                ]); ?>

                <?php component_render('select', [
                    'name' => 'reviewerPID',
                    'label' => 'ส่งพิจารณา',
                    'selected' => (string) $values['reviewerPID'],
                    'options' => $reviewer_options,
                    'required' => true,
                    'field_class' => 'full',
                    'helper_text' => 'เลือก ผอ. / รองผู้อำนวยการ / รองรักษาราชการแทน ตามสิทธิ์ที่ระบบกำหนด',
                ]); ?>

                <div class="c-field form-group full">
                    <label class="c-label form-label" for="outgoingReceiveAttachments">เอกสารแนบ</label>
                    <input
                        id="outgoingReceiveAttachments"
                        class="c-input form-input"
                        type="file"
                        name="attachments[]"
                        multiple
                        accept=".pdf,.jpg,.jpeg,.png">
                    <small class="c-help form-hint">แนบได้สูงสุด 5 ไฟล์ รองรับ PDF, JPG, JPEG, PNG</small>
                </div>

                <?php if ($is_edit_mode && !empty($existing_attachments)) : ?>
                    <div class="c-field form-group full">
                        <label class="c-label form-label">ไฟล์แนบปัจจุบัน</label>
                        <div class="outgoing-receive-file-panel">
                            <ol class="outgoing-receive-file-list">
                                <?php foreach ($existing_attachments as $attachment) : ?>
                                    <?php
                                    $file_id = (int) ($attachment['fileID'] ?? 0);
                                    $file_name = trim((string) ($attachment['fileName'] ?? ''));

                                    if ($file_id <= 0 || $file_name === '') {
                                        continue;
                                    }
                                    ?>
                                    <li>
                                        <div class="outgoing-receive-file-item">
                                            <a
                                                class="c-link"
                                                href="public/api/file-download.php?module=circulars&entity_id=<?= h((string) $edit_circular_id) ?>&file_id=<?= h((string) $file_id) ?>"
                                                target="_blank"
                                                rel="noopener"><?= h($file_name) ?></a>
                                            <label class="outgoing-receive-file-remove">
                                                <input type="checkbox" name="remove_file_ids[]" value="<?= h((string) $file_id) ?>">
                                                นำไฟล์นี้ออก
                                            </label>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ol>
                            <p class="outgoing-receive-help">ถ้าติ๊กนำไฟล์ออก ระบบจะลบไฟล์นั้นเมื่อบันทึกการแก้ไข</p>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="c-field form-group full">
                    <div class="outgoing-receive-actions">
                        <button
                            type="submit"
                            class="c-button c-button--md btn-confirm"
                            data-confirm="<?= h($is_edit_mode ? 'ยืนยันการแก้ไขและส่งใหม่ใช่หรือไม่?' : 'ยืนยันการลงทะเบียนรับหนังสือเวียนใช่หรือไม่?') ?>"
                            data-confirm-title="ยืนยันการบันทึก"
                            data-confirm-ok="ยืนยัน"
                            data-confirm-cancel="ยกเลิก">
                            <span><?= h($is_edit_mode ? 'บันทึกการแก้ไขและส่งใหม่' : 'ลงทะเบียนรับหนังสือ') ?></span>
                        </button>
                        <?php component_render('button', [
                            'label' => 'กล่องกำลังเสนอ',
                            'variant' => 'secondary',
                            'href' => 'outgoing-notice.php?box=clerk&type=external&read=all&sort=newest&view=table1',
                        ]); ?>
                    </div>
                </div>
            </div>
        </form>
    </section>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';

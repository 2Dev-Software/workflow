<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../auth/csrf.php';

$values = (array) ($values ?? []);
$requests = (array) ($requests ?? []);
$current_pid = (string) ($current_pid ?? '');
$page = (int) ($page ?? 1);
$total_pages = (int) ($total_pages ?? 1);
$total_count = (int) ($total_count ?? 0);
$page_count = count($requests);
$view_item = $view_item ?? null;
$view_attachments = (array) ($view_attachments ?? []);
$edit_item = $edit_item ?? null;
$edit_attachments = (array) ($edit_attachments ?? []);
$is_editing = $edit_item !== null;
$mode = (string) ($mode ?? 'report');
$base_url = (string) ($base_url ?? 'repairs.php');
$page_title = (string) ($page_title ?? 'แจ้งเหตุซ่อมแซม');
$page_subtitle = (string) ($page_subtitle ?? 'บันทึกและติดตามสถานะงานซ่อม');
$form_title = (string) ($form_title ?? 'แจ้งเหตุซ่อมแซม');
$form_subtitle = (string) ($form_subtitle ?? 'กรอกข้อมูลให้ครบถ้วนเพื่อส่งคำขอซ่อม');
$list_title = (string) ($list_title ?? 'รายการแจ้งซ่อม');
$list_subtitle = (string) ($list_subtitle ?? 'ติดตามสถานะงานซ่อมทั้งหมด');
$empty_title = (string) ($empty_title ?? 'ยังไม่มีรายการแจ้งซ่อม');
$empty_message = (string) ($empty_message ?? 'เมื่อมีการแจ้งซ่อม รายการจะแสดงที่หน้านี้');
$show_form = (bool) ($show_form ?? false);
$show_requester_column = (bool) ($show_requester_column ?? false);
$transition_actions = (array) ($transition_actions ?? []);

$values = array_merge([
    'subject' => '',
    'location' => '',
    'equipment' => '',
    'detail' => '',
], $values);

$status_map = [
    REPAIR_STATUS_PENDING => ['label' => 'รอดำเนินการ', 'variant' => 'pending'],
    REPAIR_STATUS_IN_PROGRESS => ['label' => 'กำลังดำเนินการ', 'variant' => 'processing'],
    REPAIR_STATUS_COMPLETED => ['label' => 'เสร็จสิ้น', 'variant' => 'approved'],
    REPAIR_STATUS_REJECTED => ['label' => 'ไม่อนุมัติ', 'variant' => 'rejected'],
    REPAIR_STATUS_CANCELLED => ['label' => 'ยกเลิก', 'variant' => 'rejected'],
];

$headers = $show_requester_column
    ? ['หัวข้อ', 'สถานที่', 'อุปกรณ์', 'สถานะ', 'ผู้แจ้ง', 'วันที่แจ้ง', 'จัดการ']
    : ['หัวข้อ', 'สถานที่', 'อุปกรณ์', 'สถานะ', 'วันที่แจ้ง', 'จัดการ'];

$rows = [];

foreach ($requests as $req) {
    $status_key = (string) ($req['status'] ?? REPAIR_STATUS_PENDING);
    $status = $status_map[$status_key] ?? ['label' => $status_key, 'variant' => 'pending'];
    $is_owner = (string) ($req['requesterPID'] ?? '') === $current_pid;
    $can_edit = $mode === 'report' && $status_key === REPAIR_STATUS_PENDING && $is_owner;

    $row = [
        (string) ($req['subject'] ?? ''),
        (string) ($req['location'] ?? '-'),
        (string) ($req['equipment'] ?? '-'),
        [
            'component' => [
                'name' => 'status-pill',
                'params' => [
                    'label' => $status['label'],
                    'variant' => $status['variant'],
                ],
            ],
        ],
    ];

    if ($show_requester_column) {
        $row[] = (string) ($req['requesterName'] ?? '-');
    }

    $row[] = (string) ($req['createdAt'] ?? '');
    $row[] = [
        'component' => [
            'name' => 'repairs-action-group',
            'params' => [
                'repair_id' => (int) ($req['repairID'] ?? 0),
                'base_url' => $base_url,
                'view_label' => $mode === 'report' ? 'อ่าน' : 'ดูรายละเอียด',
                'can_edit' => $can_edit,
                'can_delete' => $can_edit,
            ],
        ],
    ];

    $rows[] = $row;
}

$detail_status = null;

if ($view_item) {
    $detail_key = (string) ($view_item['status'] ?? REPAIR_STATUS_PENDING);
    $detail_status = $status_map[$detail_key] ?? ['label' => $detail_key, 'variant' => 'pending'];
}

$edit_status = null;

if ($edit_item) {
    $edit_key = (string) ($edit_item['status'] ?? REPAIR_STATUS_PENDING);
    $edit_status = $status_map[$edit_key] ?? ['label' => $edit_key, 'variant' => 'pending'];
}

ob_start();
?>
<div class="content-header">
    <h1><?= h($page_title) ?></h1>
    <p><?= h($page_subtitle) ?></p>
</div>

<?php if ($show_form) : ?>
    <section class="booking-card booking-form-card">
        <div class="booking-card-header">
            <div class="booking-card-title-group">
                <h2 class="booking-card-title"><?= h($is_editing ? 'แก้ไขรายการแจ้งซ่อม' : $form_title) ?></h2>
                <p class="booking-card-subtitle"><?= h($is_editing ? 'แก้ไขได้เฉพาะรายการที่รอดำเนินการ' : $form_subtitle) ?></p>
            </div>
            <?php if ($is_editing && $edit_status) : ?>
                <?php component_render('status-pill', [
                    'label' => $edit_status['label'],
                    'variant' => $edit_status['variant'],
                ]); ?>
            <?php endif; ?>
        </div>

        <form class="booking-form" method="post" enctype="multipart/form-data" data-confirm-title="ยืนยันการบันทึก" data-confirm-ok="ยืนยัน" data-confirm-cancel="ยกเลิก">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="<?= h($is_editing ? 'update' : 'create') ?>">
            <?php if ($is_editing) : ?>
                <input type="hidden" name="repair_id" value="<?= h((string) ($edit_item['repairID'] ?? 0)) ?>">
            <?php endif; ?>
            <div class="booking-form-grid">
                <?php component_render('input', [
                    'name' => 'subject',
                    'label' => 'หัวข้อ',
                    'value' => $values['subject'],
                    'placeholder' => 'ระบุหัวข้อที่ต้องการแจ้งซ่อม',
                    'required' => true,
                ]); ?>
                <?php component_render('input', [
                    'name' => 'location',
                    'label' => 'สถานที่',
                    'value' => $values['location'],
                    'placeholder' => 'เช่น อาคาร 1 ห้อง 205',
                ]); ?>
                <?php component_render('input', [
                    'name' => 'equipment',
                    'label' => 'อุปกรณ์',
                    'value' => $values['equipment'],
                    'placeholder' => 'เช่น โปรเจกเตอร์ / เครื่องปรับอากาศ',
                ]); ?>
                <?php component_render('textarea', [
                    'name' => 'detail',
                    'label' => 'รายละเอียดเพิ่มเติม',
                    'value' => $values['detail'],
                    'placeholder' => 'อธิบายอาการหรือปัญหาที่พบ',
                    'rows' => 5,
                    'class' => 'booking-textarea',
                    'field_class' => 'full',
                ]); ?>
                <?php component_render('input', [
                    'name' => 'attachments[]',
                    'label' => 'แนบรูป',
                    'type' => 'file',
                    'field_class' => 'full',
                    'help' => 'รองรับไฟล์รูป JPG/PNG/WEBP/GIF แนบได้ไม่จำกัดจำนวน',
                    'attrs' => [
                        'multiple' => true,
                        'accept' => '.jpg,.jpeg,.png,.webp,.gif,image/jpeg,image/png,image/webp,image/gif',
                    ],
                ]); ?>
                <div class="c-field form-group full">
                    <div class="booking-actions">
                        <?php component_render('button', [
                            'label' => $is_editing ? 'บันทึกการแก้ไข' : 'ส่งแจ้งซ่อม',
                            'variant' => 'primary',
                            'type' => 'submit',
                            'attrs' => [
                                'data-confirm' => $is_editing ? 'ยืนยันการบันทึกการแก้ไขรายการนี้ใช่หรือไม่?' : 'ยืนยันการส่งแจ้งซ่อมรายการนี้ใช่หรือไม่?',
                            ],
                        ]); ?>
                        <?php component_render('button', [
                            'label' => $is_editing ? 'ยกเลิกการแก้ไข' : 'ล้างฟอร์ม',
                            'variant' => 'secondary',
                            'href' => $base_url,
                        ]); ?>
                    </div>
                </div>
            </div>
        </form>

        <?php if ($is_editing && !empty($edit_attachments)) : ?>
            <div class="enterprise-divider"></div>
            <div class="enterprise-panel">
                <p><strong>ไฟล์ที่แนบแล้ว:</strong></p>
                <div class="attachment-list">
                    <?php foreach ($edit_attachments as $file) : ?>
                        <div class="attachment-item">
                            <span class="attachment-name"><?= h($file['fileName'] ?? '') ?></span>
                            <a class="attachment-link" href="public/api/file-download.php?module=repairs&entity_id=<?= h((string) ($edit_item['repairID'] ?? 0)) ?>&file_id=<?= h((string) ($file['fileID'] ?? '')) ?>" target="_blank" rel="noopener">ดูไฟล์</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php if ($view_item) : ?>
    <section class="booking-card">
        <div class="booking-card-header">
            <div class="booking-card-title-group">
                <h2 class="booking-card-title">รายละเอียดแจ้งซ่อม</h2>
                <p class="booking-card-subtitle">ข้อมูลการแจ้งซ่อมและการดำเนินการ</p>
            </div>
            <?php if ($detail_status) : ?>
                <?php component_render('status-pill', [
                    'label' => $detail_status['label'],
                    'variant' => $detail_status['variant'],
                ]); ?>
            <?php endif; ?>
        </div>

        <div class="enterprise-info">
            <div class="enterprise-info-row">
                <span class="enterprise-info-label">หัวข้อ</span>
                <span class="enterprise-info-value"><?= h($view_item['subject'] ?? '') ?></span>
            </div>
            <div class="enterprise-info-row">
                <span class="enterprise-info-label">สถานที่</span>
                <span class="enterprise-info-value"><?= h($view_item['location'] ?? '-') ?></span>
            </div>
            <div class="enterprise-info-row">
                <span class="enterprise-info-label">อุปกรณ์</span>
                <span class="enterprise-info-value"><?= h($view_item['equipment'] ?? '-') ?></span>
            </div>
            <?php if (!empty($view_item['requesterName'])) : ?>
                <div class="enterprise-info-row">
                    <span class="enterprise-info-label">ผู้แจ้ง</span>
                    <span class="enterprise-info-value"><?= h($view_item['requesterName'] ?? '') ?></span>
                </div>
            <?php endif; ?>
            <?php if (!empty($view_item['assignedToName'])) : ?>
                <div class="enterprise-info-row">
                    <span class="enterprise-info-label">ผู้รับผิดชอบ</span>
                    <span class="enterprise-info-value"><?= h($view_item['assignedToName'] ?? '') ?></span>
                </div>
            <?php endif; ?>
            <div class="enterprise-info-row">
                <span class="enterprise-info-label">วันที่แจ้ง</span>
                <span class="enterprise-info-value"><?= h($view_item['createdAt'] ?? '') ?></span>
            </div>
            <?php if (!empty($view_item['resolvedAt'])) : ?>
                <div class="enterprise-info-row">
                    <span class="enterprise-info-label">ปิดงานเมื่อ</span>
                    <span class="enterprise-info-value"><?= h($view_item['resolvedAt'] ?? '') ?></span>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($view_item['detail'])) : ?>
            <div class="enterprise-divider"></div>
            <div class="enterprise-panel">
                <p><strong>รายละเอียด:</strong></p>
                <p><?= nl2br(h((string) ($view_item['detail'] ?? ''))) ?></p>
            </div>
        <?php endif; ?>

        <?php if (!empty($view_attachments)) : ?>
            <div class="enterprise-divider"></div>
            <div class="enterprise-panel">
                <p><strong>ไฟล์แนบ:</strong></p>
                <div class="attachment-list">
                    <?php foreach ($view_attachments as $file) : ?>
                        <div class="attachment-item">
                            <span class="attachment-name"><?= h($file['fileName'] ?? '') ?></span>
                            <a class="attachment-link" href="public/api/file-download.php?module=repairs&entity_id=<?= h((string) ($view_item['repairID'] ?? 0)) ?>&file_id=<?= h((string) ($file['fileID'] ?? '')) ?>" target="_blank" rel="noopener">ดูไฟล์</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($transition_actions)) : ?>
            <div class="enterprise-divider"></div>
            <div class="enterprise-panel">
                <p><strong><?= h($mode === 'approval' ? 'การพิจารณา' : 'จัดการสถานะงานซ่อม') ?>:</strong></p>
                <div class="booking-actions">
                    <?php foreach ($transition_actions as $action) : ?>
                        <form method="post" action="<?= h($base_url) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="transition">
                            <input type="hidden" name="repair_id" value="<?= h((string) ($view_item['repairID'] ?? 0)) ?>">
                            <input type="hidden" name="target_status" value="<?= h((string) ($action['target_status'] ?? '')) ?>">
                            <?php component_render('button', [
                                'label' => (string) ($action['label'] ?? ''),
                                'variant' => (string) ($action['variant'] ?? 'primary'),
                                'type' => 'submit',
                                'attrs' => [
                                    'data-confirm' => (string) ($action['confirm'] ?? 'ยืนยันการทำรายการนี้ใช่หรือไม่?'),
                                    'data-confirm-title' => (string) ($action['confirm_title'] ?? 'ยืนยันการทำรายการ'),
                                    'data-confirm-ok' => 'ยืนยัน',
                                    'data-confirm-cancel' => 'ยกเลิก',
                                ],
                            ]); ?>
                        </form>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>

<section class="booking-card booking-list-card">
    <div class="booking-card-header">
        <div class="booking-card-title-group">
            <h2 class="booking-card-title"><?= h($list_title) ?></h2>
            <p class="booking-card-subtitle"><?= h($list_subtitle) ?></p>
        </div>
    </div>

    <?php if (empty($requests)) : ?>
        <?php component_render('empty-state', [
            'title' => $empty_title,
            'message' => $empty_message,
        ]); ?>
    <?php else : ?>
        <p class="booking-note">กำลังแสดง <?= h((string) $page_count) ?> รายการ จากทั้งหมด <?= h((string) $total_count) ?> รายการ</p>
        <?php component_render('table', [
            'headers' => $headers,
            'rows' => $rows,
            'empty_text' => 'ไม่มีรายการ',
        ]); ?>
        <?php component_render('pagination', [
            'page' => $page,
            'total_pages' => $total_pages,
            'base_url' => $base_url,
            'class' => 'u-mt-2',
        ]); ?>
    <?php endif; ?>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';

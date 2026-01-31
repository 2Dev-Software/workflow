<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../auth/csrf.php';

$values = (array) ($values ?? []);
$requests = (array) ($requests ?? []);
$is_facility = (bool) ($is_facility ?? false);
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

$values = array_merge([
    'subject' => '',
    'location' => '',
    'detail' => '',
], $values);

$status_map = [
    'PENDING' => ['label' => 'รอดำเนินการ', 'variant' => 'pending'],
    'IN_PROGRESS' => ['label' => 'กำลังดำเนินการ', 'variant' => 'pending'],
    'COMPLETED' => ['label' => 'เสร็จสิ้น', 'variant' => 'approved'],
    'REJECTED' => ['label' => 'ไม่อนุมัติ', 'variant' => 'rejected'],
    'CANCELLED' => ['label' => 'ยกเลิก', 'variant' => 'rejected'],
];

$headers = $is_facility
    ? ['หัวข้อ', 'สถานที่/อุปกรณ์', 'สถานะ', 'ผู้แจ้ง', 'เวลา', 'จัดการ']
    : ['หัวข้อ', 'สถานที่/อุปกรณ์', 'สถานะ', 'เวลา', 'จัดการ'];

$rows = [];
foreach ($requests as $req) {
    $status_key = (string) ($req['status'] ?? 'PENDING');
    $status = $status_map[$status_key] ?? ['label' => $status_key, 'variant' => 'pending'];
    $is_owner = (string) ($req['requesterPID'] ?? '') === $current_pid;
    $can_edit = $status_key === 'PENDING' && ($is_facility || $is_owner);

    $row = [
        (string) ($req['subject'] ?? ''),
        (string) ($req['location'] ?? '-'),
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

    if ($is_facility) {
        $row[] = (string) ($req['requesterName'] ?? '-');
    }

    $row[] = (string) ($req['createdAt'] ?? '');
    $row[] = [
        'component' => [
            'name' => 'repairs-action-group',
            'params' => [
                'repair_id' => (int) ($req['repairID'] ?? 0),
                'can_edit' => $can_edit,
                'can_delete' => $can_edit,
            ],
        ],
    ];

    $rows[] = $row;
}

$detail_status = null;
if ($view_item) {
    $detail_key = (string) ($view_item['status'] ?? 'PENDING');
    $detail_status = $status_map[$detail_key] ?? ['label' => $detail_key, 'variant' => 'pending'];
}

$edit_status = null;
if ($edit_item) {
    $edit_key = (string) ($edit_item['status'] ?? 'PENDING');
    $edit_status = $status_map[$edit_key] ?? ['label' => $edit_key, 'variant' => 'pending'];
}

ob_start();
?>
<div class="content-header">
    <h1>แจ้งเหตุซ่อมแซม</h1>
    <p>บันทึกและติดตามสถานะงานซ่อม</p>
</div>

<section class="booking-card booking-form-card">
    <div class="booking-card-header">
        <div class="booking-card-title-group">
            <h2 class="booking-card-title"><?= $is_editing ? 'แก้ไขรายการแจ้งซ่อม' : 'แจ้งเหตุซ่อมแซม' ?></h2>
            <p class="booking-card-subtitle"><?= $is_editing ? 'แก้ไขได้เฉพาะรายการที่รอดำเนินการ' : 'กรอกข้อมูลให้ครบถ้วนเพื่อส่งคำขอซ่อม' ?></p>
        </div>
        <?php if ($is_editing && $edit_status) : ?>
            <?php component_render('status-pill', [
                'label' => $edit_status['label'],
                'variant' => $edit_status['variant'],
            ]); ?>
        <?php endif; ?>
    </div>

    <form class="booking-form" method="post" enctype="multipart/form-data">
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
                'label' => 'สถานที่/อุปกรณ์',
                'value' => $values['location'],
                'placeholder' => 'เช่น ห้องคอมพิวเตอร์/เครื่องโปรเจคเตอร์',
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
                'label' => 'แนบรูป/ไฟล์',
                'type' => 'file',
                'field_class' => 'full',
                'help' => 'รองรับไฟล์ PDF/JPG/PNG สูงสุด 5 ไฟล์',
                'attrs' => [
                    'multiple' => true,
                    'accept' => '.pdf,.jpg,.jpeg,.png',
                ],
            ]); ?>
            <div class="c-field form-group full">
                <div class="booking-actions">
                    <?php component_render('button', [
                        'label' => $is_editing ? 'บันทึกการแก้ไข' : 'ส่งแจ้งซ่อม',
                        'variant' => 'primary',
                        'type' => 'submit',
                    ]); ?>
                    <?php component_render('button', [
                        'label' => $is_editing ? 'ยกเลิกการแก้ไข' : 'ล้างฟอร์ม',
                        'variant' => 'secondary',
                        'href' => 'repairs.php',
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

<?php if ($view_item) : ?>
    <section class="booking-card">
        <div class="booking-card-header">
            <div class="booking-card-title-group">
                <h2 class="booking-card-title">รายละเอียดแจ้งซ่อม</h2>
                <p class="booking-card-subtitle">ข้อมูลการแจ้งซ่อม</p>
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
                <span class="enterprise-info-label">สถานที่/อุปกรณ์</span>
                <span class="enterprise-info-value"><?= h($view_item['location'] ?? '-') ?></span>
            </div>
            <?php if (!empty($view_item['requesterName'])) : ?>
                <div class="enterprise-info-row">
                    <span class="enterprise-info-label">ผู้แจ้ง</span>
                    <span class="enterprise-info-value"><?= h($view_item['requesterName'] ?? '') ?></span>
                </div>
            <?php endif; ?>
            <div class="enterprise-info-row">
                <span class="enterprise-info-label">วันที่แจ้ง</span>
                <span class="enterprise-info-value"><?= h($view_item['createdAt'] ?? '') ?></span>
            </div>
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
    </section>
<?php endif; ?>

<section class="booking-card booking-list-card">
    <div class="booking-card-header">
        <div class="booking-card-title-group">
            <h2 class="booking-card-title">รายการแจ้งซ่อม</h2>
            <p class="booking-card-subtitle">ติดตามสถานะงานซ่อมทั้งหมด</p>
        </div>
    </div>

    <?php if (empty($requests)) : ?>
        <?php component_render('empty-state', [
            'title' => 'ยังไม่มีรายการแจ้งซ่อม',
            'message' => 'เมื่อมีการแจ้งซ่อม รายการจะแสดงที่หน้านี้',
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
            'base_url' => 'repairs.php',
            'class' => 'u-mt-2',
        ]); ?>
    <?php endif; ?>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';

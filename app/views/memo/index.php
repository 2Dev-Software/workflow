<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../auth/csrf.php';

$values = $values ?? ['subject' => '', 'detail' => ''];
$memos = $memos ?? [];

$status_map = [
    'DRAFT' => ['label' => 'ร่าง', 'variant' => 'neutral'],
    'SUBMITTED' => ['label' => 'เสนอแล้ว', 'variant' => 'warning'],
    'APPROVED' => ['label' => 'อนุมัติ', 'variant' => 'success'],
    'REJECTED' => ['label' => 'ไม่อนุมัติ', 'variant' => 'danger'],
];

$rows = [];
foreach ($memos as $memo) {
    $status = (string) ($memo['status'] ?? '');
    $status_meta = $status_map[$status] ?? ['label' => $status !== '' ? $status : '-', 'variant' => 'neutral'];
    $rows[] = [
        (string) ($memo['subject'] ?? ''),
        [
            'component' => [
                'name' => 'badge',
                'params' => [
                    'label' => $status_meta['label'],
                    'variant' => $status_meta['variant'],
                ],
            ],
        ],
        (string) ($memo['createdAt'] ?? ''),
    ];
}

ob_start();
?>
<div class="content-header">
    <h1>บันทึกข้อความ</h1>
    <p>บันทึกข้อความเสนอ/ลายเซ็น</p>
</div>

<section class="enterprise-card">
    <div class="enterprise-card-header">
        <div class="enterprise-card-title-group">
            <h2 class="enterprise-card-title">สร้างบันทึกข้อความ</h2>
            <p class="enterprise-card-subtitle">บันทึกข้อความเสนอ/ลายเซ็น</p>
        </div>
    </div>

    <form method="POST" enctype="multipart/form-data" data-validate>
        <?= csrf_field() ?>

        <?php component_render('input', [
            'name' => 'subject',
            'label' => 'หัวข้อ',
            'value' => $values['subject'] ?? '',
            'required' => true,
        ]); ?>

        <?php component_render('textarea', [
            'name' => 'detail',
            'label' => 'รายละเอียด',
            'value' => $values['detail'] ?? '',
            'rows' => 4,
        ]); ?>

        <?php component_render('input', [
            'name' => 'attachments[]',
            'label' => 'แนบไฟล์ (สูงสุด 5 ไฟล์)',
            'type' => 'file',
            'attrs' => [
                'multiple' => true,
                'accept' => 'application/pdf,image/png,image/jpeg',
            ],
            'help' => 'รองรับ PDF / PNG / JPG',
        ]); ?>

        <?php component_render('button', [
            'label' => 'บันทึกข้อความ',
            'variant' => 'primary',
            'type' => 'submit',
        ]); ?>
    </form>
</section>

<section class="enterprise-card">
    <div class="enterprise-card-header">
        <div class="enterprise-card-title-group">
            <h2 class="enterprise-card-title">รายการบันทึกข้อความ</h2>
            <p class="enterprise-card-subtitle">ประวัติการสร้างบันทึกข้อความ</p>
        </div>
    </div>

    <?php if (empty($memos)) : ?>
        <?php component_render('empty-state', [
            'title' => 'ไม่มีรายการบันทึกข้อความ',
            'message' => 'ยังไม่มีบันทึกข้อความที่สร้างไว้ในขณะนี้',
        ]); ?>
    <?php else : ?>
        <?php component_render('table', [
            'headers' => ['หัวข้อ', 'สถานะ', 'เวลา'],
            'rows' => $rows,
            'empty_text' => 'ไม่มีรายการ',
        ]); ?>
    <?php endif; ?>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';

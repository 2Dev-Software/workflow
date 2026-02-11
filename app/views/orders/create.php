<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../auth/csrf.php';

$values = $values ?? ['subject' => '', 'detail' => ''];

ob_start();
?>
<div class="content-header">
    <h1>ออกคำสั่งราชการ</h1>
    <p>สร้างคำสั่งใหม่</p>
</div>

<section class="enterprise-card">
    <div class="enterprise-card-header">
        <div class="enterprise-card-title-group">
            <h2 class="enterprise-card-title">สร้างคำสั่งใหม่</h2>
            <p class="enterprise-card-subtitle">ออกเลขคำสั่งและแนบเอกสาร</p>
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
            'label' => 'สร้างคำสั่ง',
            'variant' => 'primary',
            'type' => 'submit',
        ]); ?>
    </form>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';

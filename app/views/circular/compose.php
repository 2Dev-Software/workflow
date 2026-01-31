<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../auth/csrf.php';
require_once __DIR__ . '/../view.php';

$values = $values ?? [];
$factions = $factions ?? [];
$roles = $roles ?? [];
$teachers = $teachers ?? [];
$is_registry = (bool) ($is_registry ?? false);

$faction_options = [];
foreach ($factions as $faction) {
    $faction_options[(int) ($faction['fID'] ?? 0)] = (string) ($faction['fName'] ?? '');
}

$role_options = [];
foreach ($roles as $role) {
    $role_options[(int) ($role['roleID'] ?? 0)] = (string) ($role['roleName'] ?? '');
}

$teacher_options = [];
foreach ($teachers as $teacher) {
    $teacher_options[(string) ($teacher['pID'] ?? '')] = (string) ($teacher['fName'] ?? '');
}

ob_start();
?>
<div class="content-header">
    <h1>ส่งหนังสือเวียน</h1>
    <p>สร้างหนังสือเวียนภายใน/ภายนอก</p>
</div>

<section class="enterprise-card">
    <div class="enterprise-card-header">
        <div class="enterprise-card-title-group">
            <h2 class="enterprise-card-title">สร้างหนังสือเวียน</h2>
            <p class="enterprise-card-subtitle">กรอกข้อมูลและส่งหนังสือเวียนภายใน/ภายนอก</p>
        </div>
    </div>

    <form method="POST" enctype="multipart/form-data" data-validate>
        <?= csrf_field() ?>

<?php
$type_options = [
    'INTERNAL' => 'ภายใน',
];
if ($is_registry) {
    $type_options['EXTERNAL'] = 'ภายนอก (สารบรรณ)';
}
component_render('select', [
    'name' => 'circular_type',
    'label' => 'ประเภทหนังสือเวียน',
    'options' => $type_options,
    'selected' => $values['circular_type'] ?? 'INTERNAL',
    'class' => 'form-input',
]);
?>

        <?php component_render('input', [
            'name' => 'subject',
            'label' => 'หัวข้อเรื่อง',
            'value' => $values['subject'] ?? '',
            'required' => true,
            'class' => 'form-input',
        ]); ?>

        <?php component_render('textarea', [
            'name' => 'detail',
            'label' => 'รายละเอียด',
            'value' => $values['detail'] ?? '',
            'rows' => 4,
            'class' => 'form-input booking-textarea',
        ]); ?>

        <?php component_render('input', [
            'name' => 'linkURL',
            'label' => 'แนบลิงก์ (ถ้ามี)',
            'type' => 'url',
            'value' => $values['linkURL'] ?? '',
            'class' => 'form-input',
        ]); ?>

        <?php component_render('input', [
            'name' => 'fromFID',
            'label' => 'ส่งในนามกลุ่ม/ฝ่าย (fID)',
            'type' => 'number',
            'value' => $values['fromFID'] ?? '',
            'class' => 'form-input',
        ]); ?>

        <fieldset class="enterprise-panel">
            <legend>ผู้รับ (สำหรับหนังสือเวียนภายใน)</legend>

            <?php component_render('select', [
                'name' => 'faction_ids[]',
                'label' => 'กลุ่ม/ฝ่าย',
                'options' => $faction_options,
                'selected' => $values['faction_ids'] ?? [],
                'class' => 'form-input',
                'attrs' => ['multiple' => true],
            ]); ?>

            <?php component_render('select', [
                'name' => 'role_ids[]',
                'label' => 'บทบาทในระบบ',
                'options' => $role_options,
                'selected' => $values['role_ids'] ?? [],
                'class' => 'form-input',
                'attrs' => ['multiple' => true],
            ]); ?>

            <?php component_render('select', [
                'name' => 'person_ids[]',
                'label' => 'บุคคล',
                'options' => $teacher_options,
                'selected' => $values['person_ids'] ?? [],
                'class' => 'form-input',
                'attrs' => ['multiple' => true],
            ]); ?>
        </fieldset>

        <?php if ($is_registry) : ?>
            <fieldset class="enterprise-panel">
                <legend>ข้อมูลหนังสือเวียนภายนอก (สารบรรณ)</legend>

                <?php component_render('select', [
                    'name' => 'extPriority',
                    'label' => 'ระดับความเร่งด่วน',
                    'options' => [
                        'ปกติ' => 'ปกติ',
                        'ด่วน' => 'ด่วน',
                        'ด่วนมาก' => 'ด่วนมาก',
                        'ด่วนที่สุด' => 'ด่วนที่สุด',
                    ],
                    'selected' => $values['extPriority'] ?? 'ปกติ',
                    'class' => 'form-input',
                ]); ?>

                <?php component_render('input', [
                    'name' => 'extBookNo',
                    'label' => 'เลขที่หนังสือ',
                    'value' => $values['extBookNo'] ?? '',
                    'class' => 'form-input',
                ]); ?>

                <?php component_render('input', [
                    'name' => 'extIssuedDate',
                    'label' => 'ลงวันที่ (พ.ศ.)',
                    'type' => 'date',
                    'value' => $values['extIssuedDate'] ?? '',
                    'class' => 'form-input',
                ]); ?>

                <?php component_render('input', [
                    'name' => 'extFromText',
                    'label' => 'จาก',
                    'value' => $values['extFromText'] ?? '',
                    'class' => 'form-input',
                ]); ?>

                <?php component_render('input', [
                    'name' => 'extGroupFID',
                    'label' => 'หนังสือของกลุ่ม/ฝ่าย (fID)',
                    'type' => 'number',
                    'value' => $values['extGroupFID'] ?? '',
                    'class' => 'form-input',
                ]); ?>

                <label class="c-field">
                    <input type="checkbox" name="send_now" value="1" <?= ($values['send_now'] ?? '0') === '1' ? 'checked' : '' ?>>
                    <span>ส่งให้ ผอ./รักษาการทันที</span>
                </label>
            </fieldset>
        <?php endif; ?>

        <div class="c-field">
            <label class="c-label">ไฟล์แนบ (สูงสุด 5 ไฟล์)</label>
            <input type="file" name="attachments[]" class="form-input" multiple accept="application/pdf,image/png,image/jpeg">
        </div>

        <?php component_render('button', [
            'label' => 'บันทึก/ส่งหนังสือ',
            'variant' => 'primary',
            'type' => 'submit',
        ]); ?>
    </form>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';

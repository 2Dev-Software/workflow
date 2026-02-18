<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../auth/csrf.php';

$order = $order ?? null;
$values = $values ?? ['faction_ids' => [], 'role_ids' => [], 'person_ids' => []];
$factions = $factions ?? [];
$roles = $roles ?? [];
$teachers = $teachers ?? [];

$faction_options = ['' => 'เลือกกลุ่ม/ฝ่าย'];

foreach ($factions as $faction) {
    $faction_options[(int) ($faction['fID'] ?? 0)] = (string) ($faction['fName'] ?? '');
}

$role_options = ['' => 'เลือกบทบาทในระบบ'];

foreach ($roles as $role) {
    $role_options[(int) ($role['roleID'] ?? 0)] = (string) ($role['roleName'] ?? '');
}

$person_options = ['' => 'เลือกบุคคล'];

foreach ($teachers as $teacher) {
    $person_options[(string) ($teacher['pID'] ?? '')] = (string) ($teacher['fName'] ?? '');
}

ob_start();
?>
<div class="content-header">
    <h1>ส่งคำสั่งราชการ</h1>
    <p>เลือกผู้รับ</p>
</div>

<section class="enterprise-card">
    <div class="enterprise-card-header">
        <div class="enterprise-card-title-group">
            <h2 class="enterprise-card-title">เลือกผู้รับคำสั่ง</h2>
            <p class="enterprise-card-subtitle">ส่งคำสั่งไปยังฝ่าย บทบาท หรือบุคคล</p>
        </div>
    </div>

    <?php if ($order) : ?>
        <div class="enterprise-info">
            <div class="enterprise-info-row">
                <span class="enterprise-info-label">เลขที่คำสั่ง</span>
                <span class="enterprise-info-value"><?= h($order['orderNo'] ?? '') ?></span>
            </div>
            <div class="enterprise-info-row">
                <span class="enterprise-info-label">เรื่อง</span>
                <span class="enterprise-info-value"><?= h($order['subject'] ?? '') ?></span>
            </div>
        </div>
        <div class="enterprise-divider"></div>
    <?php endif; ?>

    <?php if (!$order) : ?>
        <?php component_render('empty-state', [
            'title' => 'ไม่พบคำสั่ง',
            'message' => 'ไม่สามารถส่งคำสั่งได้ในขณะนี้',
        ]); ?>
    <?php else : ?>
        <form method="POST" data-validate>
            <?= csrf_field() ?>

            <div class="enterprise-form-grid">
                <div class="full">
                    <?php component_render('select', [
                        'name' => 'faction_ids[]',
                        'id' => 'orderFaction',
                        'label' => 'กลุ่ม/ฝ่าย',
                        'options' => $faction_options,
                        'selected' => $values['faction_ids'] ?? [],
                        'attrs' => [
                            'multiple' => true,
                            'size' => 6,
                        ],
                    ]); ?>
                </div>

                <div class="full">
                    <?php component_render('select', [
                        'name' => 'role_ids[]',
                        'id' => 'orderRoles',
                        'label' => 'บทบาทในระบบ',
                        'options' => $role_options,
                        'selected' => $values['role_ids'] ?? [],
                        'attrs' => [
                            'multiple' => true,
                            'size' => 6,
                        ],
                    ]); ?>
                </div>

                <div class="full">
                    <?php component_render('select', [
                        'name' => 'person_ids[]',
                        'id' => 'orderPeople',
                        'label' => 'บุคคล',
                        'options' => $person_options,
                        'selected' => $values['person_ids'] ?? [],
                        'attrs' => [
                            'multiple' => true,
                            'size' => 8,
                        ],
                    ]); ?>
                </div>
            </div>

            <div class="booking-actions">
                <?php component_render('button', [
                    'label' => 'ส่งคำสั่ง',
                    'variant' => 'primary',
                    'type' => 'submit',
                ]); ?>
            </div>
        </form>
    <?php endif; ?>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';

<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../auth/csrf.php';

$order = $order ?? null;
$values = (array) ($values ?? ['faction_ids' => [], 'role_ids' => [], 'person_ids' => []]);
$factions = (array) ($factions ?? []);
$roles = (array) ($roles ?? []);
$teachers = (array) ($teachers ?? []);
$faction_member_map = (array) ($faction_member_map ?? []);
$role_member_map = (array) ($role_member_map ?? []);
$selected_summary = (array) ($selected_summary ?? []);
$selected_sources = (int) ($selected_summary['selected_sources'] ?? 0);
$unique_recipients = (int) ($selected_summary['unique_recipients'] ?? 0);
$order_no = (string) ($order['orderNo'] ?? '');
$order_subject = (string) ($order['subject'] ?? '');

ob_start();
?>
<div class="content-header">
    <h1>ส่งคำสั่งราชการ</h1>
    <p>เลือกผู้รับแบบกลุ่ม/บทบาท/บุคคล พร้อมตรวจสอบจำนวนผู้รับก่อนส่ง</p>
</div>

<section class="enterprise-card orders-send-card" data-orders-send data-order-no="<?= h($order_no) ?>" data-order-subject="<?= h($order_subject) ?>">
    <div class="enterprise-card-header">
        <div class="enterprise-card-title-group">
            <h2 class="enterprise-card-title">เลือกผู้รับคำสั่ง</h2>
            <p class="enterprise-card-subtitle">รองรับการเลือกซ้อนหลายแหล่ง และรวมผู้รับซ้ำให้อัตโนมัติ</p>
        </div>
    </div>

    <?php if (!$order) : ?>
        <?php component_render('empty-state', [
            'title' => 'ไม่พบคำสั่ง',
            'message' => 'ไม่สามารถส่งคำสั่งได้ในขณะนี้',
        ]); ?>
    <?php else : ?>
        <div class="enterprise-info">
            <div class="enterprise-info-row">
                <span class="enterprise-info-label">เลขที่คำสั่ง</span>
                <span class="enterprise-info-value"><?= h($order_no) ?></span>
            </div>
            <div class="enterprise-info-row">
                <span class="enterprise-info-label">เรื่อง</span>
                <span class="enterprise-info-value"><?= h($order_subject) ?></span>
            </div>
        </div>

        <div class="enterprise-divider"></div>

        <form method="POST" class="orders-send-form" data-orders-send-form>
            <?= csrf_field() ?>

            <?php component_render('recipient-picker', [
                'factions' => $factions,
                'roles' => $roles,
                'teachers' => $teachers,
                'selected_faction_ids' => (array) ($values['faction_ids'] ?? []),
                'selected_role_ids' => (array) ($values['role_ids'] ?? []),
                'selected_person_ids' => (array) ($values['person_ids'] ?? []),
                'faction_member_map' => $faction_member_map,
                'role_member_map' => $role_member_map,
            ]); ?>

            <div class="enterprise-divider"></div>

            <div class="orders-send-summary" data-recipient-summary>
                <p>จำนวนแหล่งที่เลือก: <strong data-recipient-source-count><?= h((string) $selected_sources) ?></strong></p>
                <p>จำนวนผู้รับจริง (ไม่ซ้ำ): <strong data-recipient-unique-count><?= h((string) $unique_recipients) ?></strong> คน</p>
                <p class="orders-send-warning" data-recipient-warning></p>
            </div>

            <div class="orders-send-confirm">
                <p>ก่อนส่ง ระบบจะยืนยันข้อมูลอีกครั้ง: เลขที่คำสั่ง, เรื่อง, และจำนวนผู้รับ</p>
            </div>

            <div class="booking-actions">
                <?php component_render('button', [
                    'label' => 'ส่งคำสั่ง',
                    'variant' => 'primary',
                    'type' => 'submit',
                    'attrs' => [
                        'data-orders-send-submit' => '1',
                    ],
                ]); ?>
                <?php component_render('button', [
                    'label' => 'กลับหน้าคำสั่งของฉัน',
                    'variant' => 'secondary',
                    'href' => 'orders-create.php',
                ]); ?>
            </div>
        </form>
    <?php endif; ?>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';

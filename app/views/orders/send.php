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
$read_stats = (array) ($read_stats ?? []);
$selected_sources = (int) ($selected_summary['selected_sources'] ?? 0);
$unique_recipients = (int) ($selected_summary['unique_recipients'] ?? 0);
$order_no = (string) ($order['orderNo'] ?? '');
$order_subject = (string) ($order['subject'] ?? '');
$order_status = (string) ($order['status'] ?? '');
$is_complete = $order_status === ORDER_STATUS_COMPLETE;
$is_sent = $order_status === ORDER_STATUS_SENT;
$read_total = count($read_stats);
$read_done = 0;

foreach ($read_stats as $read_row) {
    if ((int) ($read_row['isRead'] ?? 0) === 1) {
        $read_done++;
    }
}
$can_recall = $is_sent && $read_done === 0;

ob_start();
?>
<div class="content-header">
    <h1><?= $is_sent ? 'ติดตามการส่งคำสั่งราชการ' : 'ส่งคำสั่งราชการ' ?></h1>
    <p><?= $is_sent ? 'ติดตามผู้เปิดอ่าน และดึงกลับเพื่อแก้ไข/ส่งใหม่' : 'เลือกผู้รับแบบกลุ่ม/บทบาท/บุคคล พร้อมตรวจสอบจำนวนผู้รับก่อนส่ง' ?></p>
</div>

<section class="enterprise-card orders-send-card" data-orders-send data-order-no="<?= h($order_no) ?>" data-order-subject="<?= h($order_subject) ?>">
    <div class="enterprise-card-header">
        <div class="enterprise-card-title-group">
            <h2 class="enterprise-card-title"><?= $is_sent ? 'สรุปการส่งคำสั่ง' : 'เลือกผู้รับคำสั่ง' ?></h2>
            <p class="enterprise-card-subtitle">
                <?= $is_sent
                    ? 'แสดงรายชื่อผู้รับและสถานะการเปิดอ่านล่าสุด'
                    : 'รองรับการเลือกซ้อนหลายแหล่ง และรวมผู้รับซ้ำให้อัตโนมัติ' ?>
            </p>
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

        <?php if ($is_complete) : ?>
            <div class="enterprise-divider"></div>

            <form method="POST" class="orders-send-form" data-orders-send-form>
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="send">

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
                        'href' => 'orders-create.php?tab=track',
                    ]); ?>
                </div>
            </form>
        <?php elseif ($is_sent) : ?>
            <div class="enterprise-divider"></div>

            <div class="orders-send-summary">
                <p>จำนวนผู้รับทั้งหมด: <strong><?= h((string) $read_total) ?></strong> คน</p>
                <p>ผู้เปิดอ่านแล้ว: <strong><?= h((string) $read_done) ?></strong> คน</p>
            </div>

            <?php if (empty($read_stats)) : ?>
                <?php component_render('empty-state', [
                    'title' => 'ไม่พบข้อมูลผู้รับ',
                    'message' => 'ยังไม่มีข้อมูลผู้รับสำหรับคำสั่งฉบับนี้',
                ]); ?>
            <?php else : ?>
                <div class="table-responsive">
                    <table class="custom-table booking-table">
                        <thead>
                            <tr>
                                <th>ชื่อผู้รับ</th>
                                <th>สถานะ</th>
                                <th>เวลาอ่านล่าสุด</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($read_stats as $read_row) : ?>
                                <?php $is_read = (int) ($read_row['isRead'] ?? 0) === 1; ?>
                                <tr>
                                    <td><?= h((string) ($read_row['fName'] ?? '-')) ?></td>
                                    <td>
                                        <?php component_render('status-pill', [
                                            'label' => $is_read ? 'อ่านแล้ว' : 'ยังไม่อ่าน',
                                            'variant' => $is_read ? 'approved' : 'pending',
                                        ]); ?>
                                    </td>
                                    <td><?= h($is_read ? (string) ($read_row['readAt'] ?? '-') : '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <div class="booking-actions u-mt-2">
                <?php if ($can_recall) : ?>
                    <form method="POST">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="recall">
                        <?php component_render('button', [
                            'label' => 'ดึงกลับเพื่อแก้ไข/ส่งใหม่',
                            'variant' => 'danger',
                            'type' => 'submit',
                        ]); ?>
                    </form>
                <?php else : ?>
                    <p class="orders-send-warning">มีผู้รับเปิดอ่านแล้ว ไม่สามารถดึงกลับได้</p>
                <?php endif; ?>

                <?php component_render('button', [
                    'label' => 'กลับหน้าคำสั่งของฉัน',
                    'variant' => 'secondary',
                    'href' => 'orders-create.php?tab=track',
                ]); ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';

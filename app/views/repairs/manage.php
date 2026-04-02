<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../auth/csrf.php';

$requests = (array) ($requests ?? []);
$page = (int) ($page ?? 1);
$total_pages = (int) ($total_pages ?? 1);
$view_item = $view_item ?? null;
$view_attachments = (array) ($view_attachments ?? []);
$base_url = (string) ($base_url ?? 'repairs-management.php');
$page_title = (string) ($page_title ?? 'ยินดีต้อนรับ');
$page_subtitle = (string) ($page_subtitle ?? 'แจ้งเหตุซ่อมแซม / จัดการงานซ่อม');
$list_title = (string) ($list_title ?? 'รายการงานซ่อมทั้งหมด');
$list_subtitle = (string) ($list_subtitle ?? '');
$empty_title = (string) ($empty_title ?? 'ยังไม่มีรายการงานซ่อม');
$empty_message = (string) ($empty_message ?? '');
$transition_actions = (array) ($transition_actions ?? []);

$thai_months = [
    1 => 'มกราคม',
    2 => 'กุมภาพันธ์',
    3 => 'มีนาคม',
    4 => 'เมษายน',
    5 => 'พฤษภาคม',
    6 => 'มิถุนายน',
    7 => 'กรกฎาคม',
    8 => 'สิงหาคม',
    9 => 'กันยายน',
    10 => 'ตุลาคม',
    11 => 'พฤศจิกายน',
    12 => 'ธันวาคม',
];

$format_thai_datetime = static function (?string $datetime) use ($thai_months): string {
    $datetime = trim((string) $datetime);

    if ($datetime === '' || $datetime === '0000-00-00 00:00:00') {
        return '-';
    }

    $date_obj = DateTime::createFromFormat('Y-m-d H:i:s', $datetime);

    if ($date_obj === false) {
        $date_obj = DateTime::createFromFormat('Y-m-d H:i', $datetime);
    }

    if ($date_obj === false) {
        return $datetime;
    }

    $day = (int) $date_obj->format('j');
    $month = (int) $date_obj->format('n');
    $year = (int) $date_obj->format('Y') + 543;
    $month_label = $thai_months[$month] ?? '';

    return trim($day . ' ' . $month_label . ' ' . $year . ' เวลา ' . $date_obj->format('H:i') . ' น.');
};

$status_map = (array) ($status_map ?? [
    REPAIR_STATUS_PENDING => ['label' => 'ส่งคำร้องสำเร็จ', 'variant' => 'pending'],
    REPAIR_STATUS_IN_PROGRESS => ['label' => 'กำลังดำเนินการ', 'variant' => 'processing'],
    REPAIR_STATUS_COMPLETED => ['label' => 'เสร็จสิ้น', 'variant' => 'approved'],
    REPAIR_STATUS_CANCELLED => ['label' => 'ยกเลิกคำร้อง', 'variant' => 'rejected'],
    REPAIR_STATUS_REJECTED => ['label' => 'ยกเลิกคำร้อง', 'variant' => 'rejected'],
]);

$detail_status_key = (string) ($view_item['status'] ?? REPAIR_STATUS_PENDING);
$detail_status = $status_map[$detail_status_key] ?? ['label' => $detail_status_key, 'variant' => 'pending'];
$modal_close_url = $base_url;
$pagination_base_url = $base_url;

if ($page > 1) {
    $modal_close_url .= '?page=' . $page;
}

ob_start();
?>
<div class="content-header">
    <h1><?= h($page_title) ?></h1>
    <p><?= h($page_subtitle) ?></p>
</div>

<section class="booking-card booking-list-card room-admin-card">
    <div class="booking-card-header">
        <div class="booking-card-title-group">
            <h2 class="booking-card-title">ทีมผู้ดูแล</h2>
        </div>
        <div>
            <div class="room-admin-member-count">ทั้งหมด <? //= h((string) $room_staff_count) 
                                                            ?> คน</div>
            <button type="button" class="btn-outline" data-room-modal-open="roomMemberModal">เพิ่มสมาชิก</button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="custom-table booking-table room-admin-member-table">
            <thead>
                <tr>
                    <th>ชื่อ-สกุล</th>
                    <th>ตำแหน่ง</th>
                    <th>บทบาท</th>
                    <th>สถานะ</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <!-- <tbody>
                <?php if (empty($room_staff_members)) : ?>
                    <tr>
                        <td colspan="5" class="booking-empty">ยังไม่มีผู้รับผิดชอบห้อง</td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($room_staff_members as $member) : ?>
                        <?php
                        $member_pid = (string) ($member['pID'] ?? '');
                        $member_name = trim((string) ($member['name'] ?? ''));
                        $member_position = trim((string) ($member['position_name'] ?? ''));
                        $member_role = trim((string) ($member['role_name'] ?? ''));
                        $member_department = trim((string) ($member['department_name'] ?? ''));
                        ?>
                        <tr>
                            <td><strong><?= h($member_name !== '' ? $member_name : 'ไม่ระบุชื่อ') ?></strong></td>
                            <td>
                                <div class="room-admin-member-position"><?= h($member_position !== '' ? $member_position : '-') ?></div>
                                <?php if ($member_department !== '') : ?>
                                    <div class="room-admin-member-subtext"><?= h($member_department) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><span class="room-admin-member-tag"><?= h($member_role !== '' ? $member_role : '-') ?></span></td>
                            <td><span class="member-status-pill">อยู่ในทีมแล้ว</span></td>
                            <td>
                                <div class="booking-action-group">
                                    <form class="booking-action-form" data-member-remove-form method="POST"
                                        action="<?= h($room_management_post_action) ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="member_action" value="remove">
                                        <input type="hidden" name="member_pid" value="<?= h($member_pid) ?>">
                                        <button type="submit" class="booking-action-btn danger">
                                            <i class="fa-solid fa-trash"></i>
                                            <span class="tooltip danger">นำออกจากบทบาท</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody> -->
            <tbody>
                <tr>
                    <td><strong>นางจริยาวดี เวชจันทร์</strong></td>
                    <td>
                        <div class="room-admin-member-position">ครู (หัวหน้ากลุ่มสาระ)</div>
                        <div class="room-admin-member-subtext">กลุ่มสาระฯ การงานอาชีพ</div>
                    </td>
                    <td><span class="room-admin-member-tag">เจ้าหน้าที่สถานที่</span></td>
                    <td><span class="member-status-pill">อยู่ในทีมแล้ว</span></td>
                    <td>
                        <div class="booking-action-group">
                            <form class="booking-action-form" data-member-remove-form="" method="POST" action="room-management.php?q=&amp;status=all&amp;room=all">
                                <input type="hidden" name="csrf_token" value="346f7456b6559d311989d6a0227ef936da71a0cdfb883a2ff7f0bcaa2d575b43"> <input type="hidden" name="member_action" value="remove">
                                <input type="hidden" name="member_pid" value="5800900028151">
                                <button type="submit" class="booking-action-btn danger">
                                    <i class="fa-solid fa-trash"></i>
                                    <span class="tooltip danger">นำออกจากบทบาท</span>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<?php if ($view_item) : ?>
    <div id="repairManagementDetailModal" class="modal-overlay">
        <div class="modal-content booking-detail-modal approval-detail-modal">
            <header class="modal-header">
                <div class="modal-title">
                    <span>รายละเอียดคำขอซ่อม</span>
                </div>
                <div>
                    <span class="status-pill <?= h((string) ($detail_status['variant'] ?? 'pending')) ?>">
                        <?= h((string) ($detail_status['label'] ?? '-')) ?>
                    </span>
                    <a class="close-modal-btn" href="<?= h($modal_close_url) ?>">
                        <i class="fa-solid fa-xmark"></i>
                    </a>
                </div>
            </header>

            <div class="modal-body booking-detail-body">
                <div class="booking-detail-row">
                    <div class="booking-detail-content">
                        <label>หัวข้อ</label>
                        <input type="text" value="<?= h((string) ($view_item['subject'] ?? '-')) ?>" disabled>
                    </div>
                </div>

                <div class="booking-detail-row">
                    <div class="booking-detail-content">
                        <label>รายละเอียดเพิ่มเติม</label>
                        <textarea rows="4" disabled><?= h((string) ($view_item['detail'] ?? '')) ?></textarea>
                    </div>
                </div>

                <div class="booking-detail-row">
                    <div class="booking-detail-content">
                        <label>สถานที่</label>
                        <input type="text" value="<?= h((string) ($view_item['location'] ?? '-')) ?>" disabled>
                    </div>
                    <div class="booking-detail-content">
                        <label>อุปกรณ์</label>
                        <input type="text" value="<?= h((string) ($view_item['equipment'] ?? '-')) ?>" disabled>
                    </div>
                </div>

                <div class="booking-detail-row">
                    <div class="booking-detail-content">
                        <label>ผู้แจ้ง</label>
                        <input type="text" value="<?= h((string) ($view_item['requesterName'] ?? '-')) ?>" disabled>
                    </div>
                    <div class="booking-detail-content">
                        <label>วันที่แจ้ง</label>
                        <input type="text" value="<?= h($format_thai_datetime((string) ($view_item['createdAt'] ?? ''))) ?>" disabled>
                    </div>
                    <div class="booking-detail-content">
                        <label>อัปเดตล่าสุด</label>
                        <input type="text" value="<?= h($format_thai_datetime((string) ($view_item['updatedAt'] ?? ''))) ?>" disabled>
                    </div>
                </div>

                <div class="booking-detail-row">
                    <div class="booking-detail-content">
                        <label>ผู้รับผิดชอบ</label>
                        <input type="text" value="<?= h((string) ($view_item['assignedToName'] ?? '-') !== '' ? (string) ($view_item['assignedToName'] ?? '-') : '-') ?>" disabled>
                    </div>
                    <div class="booking-detail-content">
                        <label>ปิดงานเมื่อ</label>
                        <input type="text" value="<?= h($format_thai_datetime((string) ($view_item['resolvedAt'] ?? ''))) ?>" disabled>
                    </div>
                </div>

                <div class="booking-detail-row">
                    <div class="booking-detail-content">
                        <label>ไฟล์แนบ</label>
                        <div class="attachment-list">
                            <?php if (empty($view_attachments)) : ?>
                                <p class="attachment-empty">ยังไม่มีไฟล์แนบ</p>
                            <?php else : ?>
                                <?php foreach ($view_attachments as $file) : ?>
                                    <?php
                                    $file_id = (int) ($file['fileID'] ?? 0);
                                    $file_name = trim((string) ($file['fileName'] ?? 'ไฟล์แนบ'));
                                    $file_url = 'public/api/file-download.php?module=repairs&entity_id=' . (int) ($view_item['repairID'] ?? 0) . '&file_id=' . $file_id;
                                    ?>
                                    <div class="attachment-item">
                                        <span class="attachment-name"><?= h($file_name) ?></span>
                                        <a class="attachment-link" href="<?= h($file_url) ?>" target="_blank" rel="noopener">ดูไฟล์</a>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($transition_actions)) : ?>
                <div class="footer-modal operation">
                    <form method="POST" action="<?= h($base_url) ?>" class="orders-send-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="transition">
                        <input type="hidden" name="repair_id" value="<?= h((string) ((int) ($view_item['repairID'] ?? 0))) ?>">

                        <?php foreach ($transition_actions as $action) : ?>
                            <?php
                            $target_status = trim((string) ($action['target_status'] ?? ''));
                            $label = trim((string) ($action['label'] ?? 'ดำเนินการ'));
                            $is_danger = trim((string) ($action['variant'] ?? '')) === 'danger';
                            ?>
                            <button
                                type="submit"
                                name="target_status"
                                value="<?= h($target_status) ?>"
                                class="<?= $is_danger ? 'btn-outline' : '' ?>"
                                data-confirm="<?= h((string) ($action['confirm'] ?? 'ยืนยันการดำเนินการนี้ใช่หรือไม่?')) ?>"
                                data-confirm-title="<?= h((string) ($action['confirm_title'] ?? 'ยืนยันการดำเนินการ')) ?>"
                                data-confirm-ok="ยืนยัน"
                                data-confirm-cancel="ยกเลิก">
                                <p><?= h($label) ?></p>
                            </button>
                        <?php endforeach; ?>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('repairManagementDetailModal');
            if (!modal) {
                return;
            }

            modal.addEventListener('click', function(event) {
                if (event.target === modal) {
                    window.location.href = <?= json_encode($modal_close_url, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
                }
            });

            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    window.location.href = <?= json_encode($modal_close_url, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
                }
            });
        });
    </script>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';

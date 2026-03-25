<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../auth/csrf.php';

$requests = (array) ($requests ?? []);
$page = (int) ($page ?? 1);
$total_pages = (int) ($total_pages ?? 1);
$total_count = (int) ($total_count ?? 0);
$view_item = $view_item ?? null;
$view_attachments = (array) ($view_attachments ?? []);
$base_url = (string) ($base_url ?? 'repairs-approval.php');
$page_title = (string) ($page_title ?? 'ยินดีต้อนรับ');
$page_subtitle = (string) ($page_subtitle ?? 'แจ้งเหตุซ่อมแซม / อนุมัติการซ่อมแซม');
$list_title = (string) ($list_title ?? 'รายการรออนุมัติการซ่อมแซม');
$list_subtitle = (string) ($list_subtitle ?? '');
$empty_title = (string) ($empty_title ?? 'ยังไม่มีรายการรออนุมัติ');
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

$status_map = [
    REPAIR_STATUS_PENDING => ['label' => 'รอดำเนินการ', 'variant' => 'pending'],
    REPAIR_STATUS_IN_PROGRESS => ['label' => 'กำลังดำเนินการ', 'variant' => 'processing'],
    REPAIR_STATUS_COMPLETED => ['label' => 'เสร็จสิ้น', 'variant' => 'approved'],
    REPAIR_STATUS_REJECTED => ['label' => 'ไม่อนุมัติ', 'variant' => 'rejected'],
    REPAIR_STATUS_CANCELLED => ['label' => 'ยกเลิก', 'variant' => 'rejected'],
];

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

<div class="content-area booking-page">
    <section class="booking-card booking-list-card">
        <div class="booking-card-header">
            <div class="booking-card-title-group">
                <h2 class="booking-card-title"><?= h($list_title) ?></h2>
                <?php if ($list_subtitle !== '') : ?>
                    <p class="booking-card-subtitle"><?= h($list_subtitle) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="table-responsive">
            <table class="custom-table booking-table">
                <thead>
                    <tr>
                        <th>หัวข้อ</th>
                        <th>สถานที่</th>
                        <th>อุปกรณ์</th>
                        <th>ผู้แจ้ง</th>
                        <th>สถานะ</th>
                        <th>วันที่แจ้ง</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($requests)) : ?>
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <strong><?= h($empty_title) ?></strong>
                                    <p><?= h($empty_message) ?></p>
                                </div>
                            </td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($requests as $request) : ?>
                            <?php
                            $repair_id = (int) ($request['repairID'] ?? 0);
                            $status_key = (string) ($request['status'] ?? REPAIR_STATUS_PENDING);
                            $status_meta = $status_map[$status_key] ?? ['label' => $status_key, 'variant' => 'pending'];
                            ?>
                            <tr>
                                <td><?= h((string) ($request['subject'] ?? '-')) ?></td>
                                <td><?= h((string) ($request['location'] ?? '-')) ?></td>
                                <td><?= h((string) ($request['equipment'] ?? '-')) ?></td>
                                <td><?= h((string) ($request['requesterName'] ?? '-')) ?></td>
                                <td>
                                    <span class="status-pill <?= h((string) ($status_meta['variant'] ?? 'pending')) ?>">
                                        <?= h((string) ($status_meta['label'] ?? '-')) ?>
                                    </span>
                                </td>
                                <td><?= h($format_thai_datetime((string) ($request['createdAt'] ?? ''))) ?></td>
                                <td>
                                    <?php component_render('repairs-action-group', [
                                        'repair_id' => $repair_id,
                                        'base_url' => $base_url,
                                        'view_label' => 'ดูรายละเอียด',
                                        'can_edit' => false,
                                        'can_delete' => false,
                                    ]); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1) : ?>
            <?php component_render('pagination', [
                'page' => $page,
                'total_pages' => $total_pages,
                'base_url' => $pagination_base_url,
            ]); ?>
        <?php endif; ?>
    </section>
</div>

<?php if ($view_item) : ?>
    <div id="repairApprovalDetailModal" class="modal-overlay">
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
            const modal = document.getElementById('repairApprovalDetailModal');
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

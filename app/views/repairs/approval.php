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

<style>
    .content-circular-notice-index {
        background-color: none;
        border: none;
    }

    .container-circular-notice-sending {
        padding: 0;
        box-shadow: none;
    }

    hr {
        background-color: var(--color-secondary);
        height: 2px;
        border: none;
        margin: 24px 0 34px 0;
    }

    .form-group label b {
        font-size: var(--font-size-title);
    }

    .form-group .custom-select-wrapper .custom-options {
        top: 100px;
        transform: translateY(-70px);
    }
</style>

<div class="content-header">
    <h1><?= h($page_title) ?></h1>
    <p><?= h($page_subtitle) ?></p>
</div>

<div class="content-area booking-page">
    <section class="booking-card booking-list-card approval-filter-card">
        <div class="booking-card-header">
            <div class="booking-card-title-group">
                <h2 class="booking-card-title">ค้นหาและกรองรายการ</h2>
            </div>
        </div>

        <form class="approval-toolbar" method="get" action="vehicle-reservation-approval.php"
            data-approval-filter-form id="vehicleApprovalFilterForm">
            <div class="approval-filter-group">
                <div class="room-admin-search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input class="form-input" type="search" name="q"
                        value="<? //= htmlspecialchars($vehicle_approval_query, ENT_QUOTES, 'UTF-8') 
                                ?>"
                        placeholder="ค้นหาผู้ขอจอง/รถ/ทะเบียน" autocomplete="off">
                </div>
                <div class="room-admin-filter">

                    <div class="custom-select-wrapper">
                        <div class="custom-select-trigger">
                            <p class="select-value">ทั้งหมด</p>
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>

                        <div class="custom-options">
                            <div class="custom-option" data-value="all">ทุกสถานะ</div>
                            <div class="custom-option" data-value="pending">รออนุมัติ</div>
                            <div class="custom-option" data-value="approved">อนุมัติแล้ว</div>
                            <!-- <?php if ($show_rejected_filter) : ?>
                                <div class="custom-option" data-value="rejected"><?= htmlspecialchars($rejected_filter_label, ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?> -->
                        </div>

                        <select class="form-input" name="status">
                            <option value="all">ทุกสถานะ</option>
                            <option value="pending" <?= $vehicle_approval_status === 'pending' ? 'selected' : '' ?>>รออนุมัติ</option>
                            <option value="approved" <?= $vehicle_approval_status === 'approved' ? 'selected' : '' ?>>อนุมัติแล้ว</option>
                            <?php if ($show_rejected_filter) : ?>
                                <option value="rejected" <?= $vehicle_approval_status === 'rejected' ? 'selected' : '' ?>><?= htmlspecialchars($rejected_filter_label, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endif; ?>
                        </select>
                    </div>

                </div>
                <div class="room-admin-filter">
                    <div class="custom-select-wrapper">
                        <div class="custom-select-trigger">
                            <p class="select-value">ทั้งหมด</p>
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>

                        <div class="custom-options">
                            <div class="custom-option" data-value="all">ทุกยานพาหนะ</div>
                            <!-- <?php foreach ($vehicle_list as $vehicle_item): ?>
                                <?php
                                        $vehicle_id = (string) ($vehicle_item['vehicleID'] ?? '');

                                        if ($vehicle_id === '') {
                                            continue;
                                        }
                                        $plate = trim((string) ($vehicle_item['vehiclePlate'] ?? ''));
                                        $type = trim((string) ($vehicle_item['vehicleType'] ?? ''));
                                        $model = trim((string) ($vehicle_item['vehicleModel'] ?? ''));
                                        $label = $plate !== '' ? $plate : $type;

                                        if ($label === '') {
                                            $label = $vehicle_id;
                                        }
                                        $detail = trim($type . ' ' . $model);

                                        if ($detail !== '') {
                                            $label .= ' - ' . $detail;
                                        }
                                ?>
                                <div class="custom-option" data-value="<?= htmlspecialchars($vehicle_id, ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            <?php endforeach; ?> -->
                        </div>

                        <select class="form-input" name="vehicle">
                            <option value="all">ทุกยานพาหนะ</option>
                            <?php foreach ($vehicle_list as $vehicle_item): ?>
                                <?php
                                $vehicle_id = (string) ($vehicle_item['vehicleID'] ?? '');

                                if ($vehicle_id === '') {
                                    continue;
                                }
                                $plate = trim((string) ($vehicle_item['vehiclePlate'] ?? ''));
                                $type = trim((string) ($vehicle_item['vehicleType'] ?? ''));
                                $label = $plate !== '' ? $plate : $type;

                                if ($label === '') {
                                    $label = $vehicle_id;
                                }
                                ?>
                                <option value="<?= htmlspecialchars($vehicle_id, ENT_QUOTES, 'UTF-8') ?>"
                                    <?= $vehicle_approval_vehicle === $vehicle_id ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                </div>

            </div>
        </form>
    </section>

    <section class="booking-card booking-list-card booking-list-row approval-table-card">
        <div class="booking-card-header">
            <div class="booking-card-title-group">
                <h2 class="booking-card-title">รายการคำขอจองรถ</h2>
            </div>
        </div>

        <div class="table-responsive">
            <table class="custom-table booking-table approval-table">
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
                <!-- <tbody>
                    <? //php require __DIR__ . '/../../../public/components/partials/vehicle-reservation-approval-table-rows.php'; 
                    ?>
                </tbody> -->

                <tbody>
                    <tr class="approval-row pending">
                        <td>
                            Lorem ipsum dolor sit.
                        </td>
                        <td>
                            หกฟหกดหกดหกดหกดห
                        </td>
                        <td>
                            -
                        </td>
                        <td>
                            นางสาวทิพยรัตน์ บุญมณี
                        </td>
                        <td>
                            <span class="status-pill pending">รอดำเนินการ</span>
                        </td>
                        <td>
                            29 มกราคม 2569 เวลา 13:17น.
                        </td>
                        <td class="booking-action-cell">
                            <div class="booking-action-group">
                                <button type="button" class="booking-action-btn secondary" data-vehicle-approval-action="detail" data-approval-id="29" data-approval-code="29" data-approval-vehicle-id="" data-approval-vehicle="-" data-approval-driver-id="" data-approval-driver-name="" data-approval-driver-tel="" data-approval-date="15-16 มีนาคม 2569" data-approval-time="13:49-17:49" data-approval-requester="นางสาวทิพยรัตน์ บุญมณี" data-approval-department="กลุ่มธุรการ" data-approval-contact="0882747041" data-approval-purpose="dfasfdssdfdsf" data-approval-location="dsfdsfsdf" data-approval-passengers="5" data-approval-driver="-" data-approval-status="PENDING" data-approval-status-label="ส่งเอกสารแล้ว" data-approval-status-class="pending" data-approval-name="รอการอนุมัติ" data-approval-at="-" data-approval-created="15 มีนาคม 2569 เวลา 11:49" data-approval-updated="15 มีนาคม 2569 เวลา 11:49" data-approval-assigned-note="" data-approval-approval-note="" data-approval-attachments="[{&quot;fileID&quot;:160,&quot;fileName&quot;:&quot;Screenshot 2569-03-14 at 16.18.17 (2).png&quot;,&quot;filePath&quot;:&quot;assets/uploads/vehicle-bookings/vehicle_booking_20260315_114959_c34fd5d75ef1.png&quot;,&quot;mimeType&quot;:&quot;image/png&quot;,&quot;fileSize&quot;:1681069},{&quot;fileID&quot;:161,&quot;fileName&quot;:&quot;Screenshot 2569-03-14 at 17.14.58.png&quot;,&quot;filePath&quot;:&quot;assets/uploads/vehicle-bookings/vehicle_booking_20260315_114959_30fe750e2f29.png&quot;,&quot;mimeType&quot;:&quot;image/png&quot;,&quot;fileSize&quot;:192064}]">
                                    <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                    <span class="tooltip">ดูรายละเอียด</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

    </section>
</div>

<? //php if ($view_item) : 
?>

<div class="content-circular-notice-index">
    <div class="modal-overlay-circular-notice-index" id="vehicleApprovalDetailModal">
        <div class="modal-content">

            <div class="header-modal">
                <div class="first-header">
                    <p>แสดงข้อความรายละเอียดแจ้งเหตุซ่อมแซม</p>
                </div>
                <div class="sec-header close-modal-btn">
                    <i class="fa-solid fa-xmark" id=""></i>
                </div>
            </div>

            <div class="content-modal">

                <form method="" class="container-circular-notice-sending" id="repairs">

                    <div class="sender-row">
                        <div class="form-group">
                            <label for="">หัวข้อ</label>
                            <input type="text" placeholder="ระบุหัวข้อที่ต้องการแจ้งซ่อม" disabled>
                        </div>
                        <div class="form-group">
                            <label for="">สถานที่</label>
                            <input type="text" placeholder="เช่น อาคาร 1 ห้อง 205" disabled>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="">อุปกรณ์</label>
                        <input type="text" placeholder="เช่น โปรเจคเตอร์ / เครื่องปรับอากาศ" disabled>
                    </div>

                    <div class="form-group">
                        <label for="">รายละเอียดเพิ่มเติม</label>
                        <textarea name="" rows="4" placeholder="อธิบายอาการหรือปัญหาที่พบ" disabled></textarea>
                    </div>

                    <div class="form-group">
                        <label>อัปโหลดไฟล์เอกสาร</label>
                        <section class="upload-layout">
                            <div class="file-list" id="fileListContainer">
                                <div class="file-item-wrapper">
                                    <div class="file-banner">
                                        <div class="file-info">
                                            <div class="file-icon"><i class="fa-solid fa-file-image" aria-hidden="true"></i></div>
                                            <div class="file-text"><span class="file-name">Screenshot_20260221_224247.png</span><span class="file-type">981.3 KB</span></div>
                                        </div>
                                        <div class="file-actions-group" style="display: flex; gap: 10px;">
                                            <div class="file-actions"><a href="#" target="_blank" rel="noopener"><i class="fa-solid fa-eye" aria-hidden="true"></i></a></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>

                    <div class="sender-row">
                        <div class="form-group">
                            <label for="">ผู้ส่ง</label>
                            <input type="text" placeholder="นางสาวทิพยรัตน์ บุญมณี" disabled>
                        </div>
                        <div class="form-group">
                            <label for="">วันที่แจ้ง</label>
                            <input type="text" placeholder="29 มกราคม 2569 เวลา 13:17น." disabled>
                        </div>
                    </div>

                    <hr>

                    <div class="form-group">
                        <label for=""><b>การพิจารณา</b></label>
                    </div>

                    <div class="form-group">
                        <div class="custom-select-wrapper open">
                            <div class="custom-select-trigger">
                                <p class="select-value">ทุกสถานะ</p>
                                <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                            </div>

                            <div class="custom-options">
                                <div class="custom-option selected" data-value="all">ทุกสถานะ</div>
                                <div class="custom-option" data-value="pending">รออนุมัติ</div>
                                <div class="custom-option" data-value="approved">อนุมัติแล้ว</div>
                            </div>

                            <select class="form-input" name="status">
                                <option value="all">ทุกสถานะ</option>
                                <option value="pending">รออนุมัติ</option>
                                <option value="approved">อนุมัติแล้ว</option>
                            </select>

                        </div>
                    </div>

                    <div class="form-group">
                        <label for="">รายละเอียดเพิ่มเติม</label>
                        <textarea name="" rows="4" placeholder="อธิบายอาการหรือปัญหาที่พบ"></textarea>
                    </div>

                </form>
            </div>

            <div class="footer-modal">
                <form method="POST" id="">
                    <button>
                        <p>บันทึก</p>
                    </button>
                </form>
            </div>

        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('vehicleApprovalDetailModal');

        const openBtns = document.querySelectorAll('[data-vehicle-approval-action="detail"]');
        const closeBtns = document.querySelectorAll('[data-vehicle-approval-close]');

        const openModal = (e) => {
            e.preventDefault();
            modal.classList.remove('hidden');
            modal.style.display = 'flex';
        };

        const closeModal = () => {
            modal.classList.add('hidden');
            modal.style.display = 'none';
        };

        openBtns.forEach(btn => btn.addEventListener('click', openModal));

        closeBtns.forEach(btn => btn.addEventListener('click', closeModal));

        window.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal();
            }
        });
    });
</script>
<? //php endif; 
?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';

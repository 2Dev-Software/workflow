<?php
/*
UI patterns borrowed from room-booking-approval.php:
- booking-card layout, approval filter card, approval table styling, status-pill badges
- detail modal + confirmation modal patterns
- AJAX filter behavior and row rendering via partial
UX fixes applied:
- clear summary chips for pending/approved/rejected counts
- consistent approve/reject actions with confirmation and optional reason
- structured detail modal with key booking info and attachments
Responsive behavior summary:
- desktop: table layout with detail modal
- tablet: stacked filter controls + table scroll
- mobile: compact table with accessible action button opening modal
*/
require_once __DIR__ . '/src/Services/auth/auth-guard.php';
require_once __DIR__ . '/src/Services/security/security-service.php';
require_once __DIR__ . '/src/Services/system/system-year.php';
require_once __DIR__ . '/src/Services/teacher/teacher-profile.php';
require_once __DIR__ . '/src/Services/system/exec-duty-current.php';
require_once __DIR__ . '/src/Services/vehicle/vehicle-reservation-utils.php';
$actor_pid = (string) ($_SESSION['pID'] ?? '');
$role_id = (int) ($teacher['roleID'] ?? 0);
$position_id = (int) ($teacher['positionID'] ?? 0);
$acting_pid = '';
if (($exec_duty_current_status ?? 0) === 2 && !empty($exec_duty_current_pid)) {
    $acting_pid = (string) $exec_duty_current_pid;
}

$vehicle_approval_is_director = $position_id === 1 || ($acting_pid !== '' && $acting_pid === $actor_pid);
$vehicle_approval_is_vehicle_officer = in_array($role_id, [1, 3], true);
if (!$vehicle_approval_is_director && !$vehicle_approval_is_vehicle_officer) {
    header('Location: dashboard.php', true, 302);
    exit();
}

$currentThaiYear = (int) date('Y') + 543;
$dh_year_value = (int) ($dh_year !== '' ? $dh_year : $currentThaiYear);
if ($dh_year_value < 2500) {
    $dh_year_value = $currentThaiYear;
}

require_once __DIR__ . '/src/Services/vehicle/vehicle-reservation-approval-actions.php';
require_once __DIR__ . '/src/Services/vehicle/vehicle-reservation-approval-data.php';

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

$format_thai_date = static function (string $date) use ($thai_months): string {
    $date_obj = DateTime::createFromFormat('Y-m-d', $date);
    if ($date_obj === false) {
        return $date;
    }

    $day = (int) $date_obj->format('j');
    $month = (int) $date_obj->format('n');
    $year = (int) $date_obj->format('Y') + 543;
    $month_label = $thai_months[$month] ?? '';

    return trim($day . ' ' . $month_label . ' ' . $year);
};

$format_thai_date_range = static function (string $start, string $end) use ($format_thai_date, $thai_months): string {
    if ($end === '' || $start === $end) {
        return $format_thai_date($start);
    }

    $start_obj = DateTime::createFromFormat('Y-m-d', $start);
    $end_obj = DateTime::createFromFormat('Y-m-d', $end);
    if ($start_obj === false || $end_obj === false) {
        return $format_thai_date($start) . ' - ' . $format_thai_date($end);
    }

    $start_day = (int) $start_obj->format('j');
    $start_month = (int) $start_obj->format('n');
    $start_year = (int) $start_obj->format('Y') + 543;
    $end_day = (int) $end_obj->format('j');
    $end_month = (int) $end_obj->format('n');
    $end_year = (int) $end_obj->format('Y') + 543;
    $start_month_label = $thai_months[$start_month] ?? '';
    $end_month_label = $thai_months[$end_month] ?? '';

    if ($start_year === $end_year && $start_month === $end_month) {
        return trim($start_day . '-' . $end_day . ' ' . $start_month_label . ' ' . $start_year);
    }

    if ($start_year === $end_year) {
        return trim($start_day . ' ' . $start_month_label . ' - ' . $end_day . ' ' . $end_month_label . ' ' . $start_year);
    }

    return trim($start_day . ' ' . $start_month_label . ' ' . $start_year . ' - ' . $end_day . ' ' . $end_month_label . ' ' . $end_year);
};

$format_thai_datetime = static function (string $datetime) use ($thai_months): string {
    if ($datetime === '' || strpos($datetime, '0000-00-00') === 0) {
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

    return trim($day . ' ' . $month_label . ' ' . $year . ' เวลา ' . $date_obj->format('H:i'));
};

$vehicle_approval_status_labels = [
    'PENDING' => ['label' => 'รออนุมัติ', 'class' => 'pending'],
    'ASSIGNED' => ['label' => 'ส่งต่อผู้บริหาร', 'class' => 'pending'],
    'APPROVED' => ['label' => 'อนุมัติแล้ว', 'class' => 'approved'],
    'REJECTED' => ['label' => 'ไม่อนุมัติ', 'class' => 'rejected'],
    'CANCELLED' => ['label' => 'ยกเลิก', 'class' => 'rejected'],
    'COMPLETED' => ['label' => 'เสร็จสิ้น', 'class' => 'approved'],
    'DRAFT' => ['label' => 'แบบร่าง', 'class' => 'pending'],
];

$vehicle_approval_alert = null;
if (isset($_SESSION['vehicle_approval_alert'])) {
    $vehicle_approval_alert = $_SESSION['vehicle_approval_alert'];
    unset($_SESSION['vehicle_approval_alert']);
}

$vehicle_approval_return_url = 'vehicle-reservation-approval.php';
if (!empty($_SERVER['QUERY_STRING'])) {
    $vehicle_approval_return_url .= '?' . $_SERVER['QUERY_STRING'];
}

if (isset($_GET['ajax_filter'])) {
    require __DIR__ . '/public/components/partials/vehicle-reservation-approval-table-rows.php';
    exit();
}
?>
<!DOCTYPE html>
<html lang="th">
<?php require_once __DIR__ . '/public/components/x-head.php'; ?>
<body data-vehicle-approval-mode="<?= htmlspecialchars($vehicle_approval_mode ?? 'officer', ENT_QUOTES, 'UTF-8') ?>"
    data-vehicle-approval-can-assign="<?= !empty($vehicle_approval_can_assign) ? '1' : '0' ?>"
    data-vehicle-approval-can-finalize="<?= !empty($vehicle_approval_can_finalize) ? '1' : '0' ?>">

    <?php require_once __DIR__ . '/public/components/layout/preloader.php'; ?>
    <?php if (!empty($vehicle_approval_alert)): ?>
        <?php $alert = $vehicle_approval_alert; ?>
        <?php require __DIR__ . '/public/components/x-alert.php'; ?>
    <?php endif; ?>
    <?php require_once __DIR__ . '/public/components/partials/x-sidebar.php'; ?>

    <section class="main-section">
        <?php require_once __DIR__ . '/public/components/partials/x-navigation.php'; ?>

        <main class="content-wrapper">
            <div class="content-header">
                <h1>ยินดีต้อนรับ</h1>
                <p>อนุมัติการจองยานพาหนะ</p>
            </div>

            <div class="content-area booking-page">
                <section class="booking-card booking-list-card approval-filter-card">
                    <div class="booking-card-header">
                        <div class="booking-card-title-group">
                            <h2 class="booking-card-title">ค้นหาและกรองรายการ</h2>
                            <p class="booking-card-subtitle">ค้นหาจากผู้ขอจอง รถ และสถานะเพื่อจัดการได้รวดเร็ว</p>
                        </div>
                        <div class="approval-summary-chip enterprise-toolbar">
                            <span class="status-pill pending approval-chip">รออนุมัติ
                                <?= htmlspecialchars((string) $vehicle_booking_pending_total, ENT_QUOTES, 'UTF-8') ?> รายการ</span>
                            <span class="status-pill approved approval-chip">อนุมัติแล้ว
                                <?= htmlspecialchars((string) $vehicle_booking_approved_total, ENT_QUOTES, 'UTF-8') ?> รายการ</span>
                            <span class="status-pill rejected approval-chip">ไม่อนุมัติ/ยกเลิก
                                <?= htmlspecialchars((string) $vehicle_booking_rejected_total, ENT_QUOTES, 'UTF-8') ?> รายการ</span>
                        </div>
                    </div>

                    <form class="approval-toolbar" method="get" action="vehicle-reservation-approval.php" data-approval-filter-form>
                        <div class="approval-filter-group">
                            <div class="room-admin-search">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <input class="form-input" type="search" name="q"
                                    value="<?= htmlspecialchars($vehicle_approval_query, ENT_QUOTES, 'UTF-8') ?>"
                                    placeholder="ค้นหาผู้ขอจอง/รถ/ทะเบียน" autocomplete="off">
                            </div>
                            <div class="room-admin-filter">
                                <select class="form-input" name="status">
                                    <option value="all">ทุกสถานะ</option>
                                    <option value="pending" <?= $vehicle_approval_status === 'pending' ? 'selected' : '' ?>>รออนุมัติ</option>
                                    <option value="approved" <?= $vehicle_approval_status === 'approved' ? 'selected' : '' ?>>อนุมัติแล้ว</option>
                                    <option value="rejected" <?= $vehicle_approval_status === 'rejected' ? 'selected' : '' ?>>ไม่อนุมัติ/ยกเลิก</option>
                                </select>
                            </div>
                            <div class="room-admin-filter">
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
                            <div class="room-admin-filter">
                                <input class="form-input" type="date" name="date_from" value="<?= htmlspecialchars($vehicle_approval_date_from, ENT_QUOTES, 'UTF-8') ?>" placeholder="จากวันที่">
                            </div>
                            <div class="room-admin-filter">
                                <input class="form-input" type="date" name="date_to" value="<?= htmlspecialchars($vehicle_approval_date_to, ENT_QUOTES, 'UTF-8') ?>" placeholder="ถึงวันที่">
                            </div>
                        </div>
                    </form>
                </section>

                <section class="booking-card booking-list-card booking-list-row approval-table-card">
                    <div class="booking-card-header">
                        <div class="booking-card-title-group">
                            <h2 class="booking-card-title">รายการคำขอจองรถ</h2>
                            <p class="booking-card-subtitle">จัดการคำขอจองยานพาหนะและบันทึกผลการอนุมัติ</p>
                        </div>
                    </div>

                    <div class="table-responsive approval-table-wrapper">
                        <table class="custom-table booking-table approval-table">
                            <thead>
                                <tr>
                                    <th>วัน/เวลา</th>
                                    <th>ผู้ขอจอง</th>
                                    <th>รถ/ทะเบียน</th>
                                    <th>วัตถุประสงค์</th>
                                    <th>สถานที่</th>
                                    <th>สถานะ</th>
                                    <th>จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php require __DIR__ . '/public/components/partials/vehicle-reservation-approval-table-rows.php'; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

        </main>

        <?php require_once __DIR__ . '/public/components/partials/x-footer.php'; ?>

    </section>

    <div id="vehicleApprovalDetailModal" class="modal-overlay hidden">
        <div class="modal-content booking-detail-modal approval-detail-modal">
            <header class="modal-header">
                <div class="modal-title">
                    <i class="fa-solid fa-clipboard-check"></i>
                    <span>รายละเอียดการอนุมัติการจองรถ</span>
                </div>
                <div class="close-modal-btn" data-vehicle-approval-close="vehicleApprovalDetailModal" aria-label="ปิดหน้าต่าง">
                    <i class="fa-solid fa-xmark"></i>
                </div>
            </header>
            <div class="modal-body booking-detail-body approval-detail-body">
                <div class="approval-detail-layout">
                    <section class="approval-panel approval-panel--request">
                        <div class="approval-panel-header">
                            <h4 class="approval-panel-title">ข้อมูลคำขอ</h4>
                            <span class="approval-panel-subtitle">รายละเอียดการใช้รถ</span>
                        </div>

                        <div class="booking-detail-grid approval-request-grid">
                            <div class="detail-item">
                                <p class="detail-label">ผู้ขอจอง</p>
                                <p class="detail-value" data-vehicle-approval-detail="requester">-</p>
                                <span class="detail-subtext" data-vehicle-approval-detail="department">-</span>
                            </div>
                            <div class="detail-item">
                                <p class="detail-label">ติดต่อ</p>
                                <p class="detail-value" data-vehicle-approval-detail="contact">-</p>
                            </div>
                            <div class="detail-item">
                                <p class="detail-label">วันที่</p>
                                <p class="detail-value" data-vehicle-approval-detail="date">-</p>
                            </div>
                            <div class="detail-item">
                                <p class="detail-label">เวลา</p>
                                <p class="detail-value" data-vehicle-approval-detail="time">-</p>
                            </div>
                            <div class="detail-item">
                                <p class="detail-label">รถ/ทะเบียน</p>
                                <p class="detail-value" data-vehicle-approval-detail="vehicle">-</p>
                                <span class="detail-subtext" data-vehicle-approval-detail="vehicle-code">-</span>
                            </div>
                            <div class="detail-item">
                                <p class="detail-label">ผู้ขับ/การติดต่อคนขับ</p>
                                <p class="detail-value" data-vehicle-approval-detail="driver">-</p>
                            </div>
                            <div class="detail-item">
                                <p class="detail-label">จำนวนผู้เดินทาง</p>
                                <p class="detail-value" data-vehicle-approval-detail="passengers">-</p>
                            </div>
                        </div>

                        <div class="booking-detail-section">
                            <h4>วัตถุประสงค์</h4>
                            <p data-vehicle-approval-detail="purpose">-</p>
                        </div>

                        <div class="booking-detail-section">
                            <h4>สถานที่</h4>
                            <p data-vehicle-approval-detail="location">-</p>
                        </div>

                        <div class="booking-detail-section">
                            <h4>ไฟล์แนบ</h4>
                            <div class="attachment-list" data-vehicle-approval-detail="attachments"></div>
                        </div>
                    </section>

                    <section class="approval-panel approval-panel--decision">
                        <div class="approval-panel-header">
                            <h4 class="approval-panel-title">การพิจารณา</h4>
                            <span class="approval-panel-subtitle">ผลล่าสุดและการมอบหมาย</span>
                        </div>

                        <div class="booking-detail-grid approval-decision-grid">
                            <div class="detail-item detail-half detail-status-item">
                                <p class="detail-label">สถานะ</p>
                                <span class="status-pill" data-vehicle-approval-detail="status">-</span>
                            </div>
                            <div class="detail-item detail-half" data-vehicle-approval-detail="approval-item">
                                <p class="detail-label" data-vehicle-approval-detail="approval-label">ผู้อนุมัติ</p>
                                <p class="detail-value" data-vehicle-approval-detail="approval-name">-</p>
                                <span class="detail-subtext" data-vehicle-approval-detail="approval-at">-</span>
                            </div>
                        </div>

                        <div class="booking-detail-section detail-reason-section hidden" data-vehicle-approval-detail="reason-row">
                            <h4>เหตุผลไม่อนุมัติ</h4>
                            <p data-vehicle-approval-detail="reason">-</p>
                        </div>

                        <form class="approval-decision-form" method="post" action="vehicle-reservation-approval.php" data-vehicle-approval-form>
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="vehicle_booking_id" value="">
                            <input type="hidden" name="approval_action" value="">
                            <input type="hidden" name="return_url" value="<?= htmlspecialchars($vehicle_approval_return_url, ENT_QUOTES, 'UTF-8') ?>">

                            <div class="booking-detail-section approval-decision hidden" data-vehicle-approval-assign>
                                <div class="approval-decision-head">
                                    <h4>มอบหมายรถและคนขับ</h4>
                                    <p class="detail-subtext">เลือกยานพาหนะและผู้ขับก่อนส่งต่อผู้บริหาร</p>
                                </div>
                                <div class="booking-detail-grid approval-request-grid">
                                    <div class="detail-item detail-full">
                                        <p class="detail-label">ยานพาหนะ</p>
                                        <select class="form-input" id="assignVehicleSelect" name="assign_vehicle_id" data-vehicle-approval-assign-input="vehicle">
                                            <option value="">เลือกยานพาหนะ</option>
                                            <?php foreach ($vehicle_list as $vehicle_item): ?>
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
                                                <option value="<?= htmlspecialchars($vehicle_id, ENT_QUOTES, 'UTF-8') ?>">
                                                    <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="detail-item detail-full">
                                        <p class="detail-label">ผู้ขับรถ</p>
                                        <select class="form-input" id="assignDriverSelect" name="assign_driver_pid" data-vehicle-approval-assign-input="driver">
                                            <option value="">เลือกผู้ขับรถ</option>
                                            <?php foreach ($vehicle_driver_list as $driver_item): ?>
                                                <?php
                                                $driver_id = (string) ($driver_item['pID'] ?? '');
                                                $driver_name = trim((string) ($driver_item['name'] ?? ''));
                                                $driver_tel = trim((string) ($driver_item['telephone'] ?? ''));
                                                if ($driver_id === '' || $driver_name === '') {
                                                    continue;
                                                }
                                                $driver_label = $driver_name;
                                                if ($driver_tel !== '') {
                                                    $driver_label .= ' (' . $driver_tel . ')';
                                                }
                                                ?>
                                                <option value="<?= htmlspecialchars($driver_id, ENT_QUOTES, 'UTF-8') ?>"
                                                    data-driver-name="<?= htmlspecialchars($driver_name, ENT_QUOTES, 'UTF-8') ?>"
                                                    data-driver-tel="<?= htmlspecialchars($driver_tel, ENT_QUOTES, 'UTF-8') ?>">
                                                    <?= htmlspecialchars($driver_label, ENT_QUOTES, 'UTF-8') ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <span class="detail-subtext">หากไม่มีรายชื่อ ให้กรอกชื่อและเบอร์โทรด้านล่าง</span>
                                    </div>
                                    <div class="detail-item detail-half">
                                        <p class="detail-label">ชื่อผู้ขับ (กรอกเอง)</p>
                                        <input class="form-input" id="assignDriverName" type="text" name="assign_driver_name" placeholder="เช่น นายสมชาย">
                                    </div>
                                    <div class="detail-item detail-half">
                                        <p class="detail-label">เบอร์โทรคนขับ</p>
                                        <input class="form-input" id="assignDriverTel" type="tel" name="assign_driver_tel" placeholder="เช่น 0890000000">
                                    </div>
                                </div>
                            </div>

                            <div class="booking-detail-section approval-decision">
                                <div class="approval-decision-head">
                                    <h4>บันทึกผล</h4>
                                    <p class="detail-subtext">เหตุผลนี้จะแสดงให้ผู้ขอจอง</p>
                                </div>
                                <p class="detail-label">เหตุผล (ถ้าไม่อนุมัติ)</p>
                                <textarea class="form-input booking-textarea" id="vehicleApprovalReason" name="statusReason" rows="3" placeholder="ระบุเหตุผลให้ผู้ขอจองทราบ"></textarea>
                                <p class="detail-subtext">เหตุผลนี้จะแสดงให้ผู้ขอจอง</p>
                            </div>

                            <div class="booking-detail-meta">
                                <div class="detail-meta-item">
                                    <span>สร้างรายการ</span>
                                    <strong data-vehicle-approval-detail="created">-</strong>
                                </div>
                                <div class="detail-meta-item">
                                    <span>อัปเดตล่าสุด</span>
                                    <strong data-vehicle-approval-detail="updated">-</strong>
                                </div>
                            </div>

                            <div class="booking-actions">
                                <button type="button" class="btn-outline" data-vehicle-approval-close="vehicleApprovalDetailModal">ปิด</button>
                                <button type="submit" class="btn-outline" data-vehicle-approval-submit="reject">ไม่อนุมัติ</button>
                                <button type="submit" class="btn-confirm" data-vehicle-approval-submit="approve">อนุมัติรายการ</button>
                            </div>
                        </form>
                    </section>
                </div>
            </div>
        </div>
    </div>

    <div id="vehicleApprovalConfirmationModal" class="alert-overlay hidden">
        <div class="alert-box">
            <div class="alert-header">
                <div class="icon-circle">
                    <i class="fa-solid"></i>
                </div>
            </div>
            <div class="alert-body">
                <h1 id="vehicleApprovalConfirmTitle"></h1>
                <p id="vehicleApprovalConfirmMessage"></p>
                <div class="alert-actions">
                    <button type="button" class="btn-close-alert btn-cancel-alert" data-vehicle-approval-confirm-close>ยกเลิก</button>
                    <button type="button" class="btn-close-alert" id="vehicleApprovalConfirmBtn">ยืนยัน</button>
                </div>
            </div>
        </div>
    </div>

    <?php require_once __DIR__ . '/public/components/x-scripts.php'; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const detailModal = document.getElementById('vehicleApprovalDetailModal');
            const confirmModal = document.getElementById('vehicleApprovalConfirmationModal');
            const confirmBox = confirmModal ? confirmModal.querySelector('.alert-box') : null;
            const confirmIcon = confirmBox ? confirmBox.querySelector('.icon-circle i') : null;
            const confirmTitle = document.getElementById('vehicleApprovalConfirmTitle');
            const confirmMessage = document.getElementById('vehicleApprovalConfirmMessage');
            const confirmBtn = document.getElementById('vehicleApprovalConfirmBtn');
            const confirmCloseBtn = confirmModal ? confirmModal.querySelector('[data-vehicle-approval-confirm-close]') : null;
            const closeButtons = document.querySelectorAll('[data-vehicle-approval-close]');

            const approvalForm = detailModal ? detailModal.querySelector('[data-vehicle-approval-form]') : null;
            const approvalIdInput = approvalForm ? approvalForm.querySelector('[name="vehicle_booking_id"]') : null;
            const approvalActionInput = approvalForm ? approvalForm.querySelector('[name="approval_action"]') : null;
            const approvalReasonInput = approvalForm ? approvalForm.querySelector('[name="statusReason"]') : null;
            const approvalActionButtons = detailModal ? detailModal.querySelectorAll('[data-vehicle-approval-submit]') : [];

            const approvalAssignSection = detailModal ? detailModal.querySelector('[data-vehicle-approval-assign]') : null;
            const assignVehicleSelect = approvalForm ? approvalForm.querySelector('[name=\"assign_vehicle_id\"]') : null;
            const assignDriverSelect = approvalForm ? approvalForm.querySelector('[name=\"assign_driver_pid\"]') : null;
            const assignDriverNameInput = approvalForm ? approvalForm.querySelector('[name=\"assign_driver_name\"]') : null;
            const assignDriverTelInput = approvalForm ? approvalForm.querySelector('[name=\"assign_driver_tel\"]') : null;

            const canAssign = document.body?.dataset.vehicleApprovalCanAssign === '1';
            const canFinalize = document.body?.dataset.vehicleApprovalCanFinalize === '1';

            let pendingAction = '';
            let currentStatus = '';
            let canAssignStage = false;
            let canFinalizeStage = false;

            function toggleApprovalReasonRequired(isRequired) {
                if (!approvalReasonInput) return;
                if (isRequired) {
                    approvalReasonInput.setAttribute('required', 'required');
                    approvalReasonInput.setAttribute('aria-required', 'true');
                } else {
                    approvalReasonInput.removeAttribute('required');
                    approvalReasonInput.removeAttribute('aria-required');
                }
            }

            function submitApprovalForm() {
                if (!approvalForm) return false;
                if (typeof approvalForm.reportValidity === 'function' && !approvalForm.reportValidity()) {
                    return false;
                }
                if (typeof approvalForm.requestSubmit === 'function') {
                    approvalForm.requestSubmit();
                } else {
                    approvalForm.submit();
                }
                return true;
            }

            const filterForm = document.querySelector('[data-approval-filter-form]');
            const tableBody = document.querySelector('.booking-list-card table tbody');
            const searchInput = filterForm ? filterForm.querySelector('input[name="q"]') : null;
            const filterSelects = filterForm ? filterForm.querySelectorAll('select, input[type="date"]') : [];
            let searchTimeout;

            function fetchResults() {
                if (!filterForm || !tableBody) return;
                const formData = new FormData(filterForm);
                const params = new URLSearchParams(formData);
                params.append('ajax_filter', '1');

                const url = filterForm.action;
                const fullUrl = url + '?' + params.toString();
                const historyUrl = url + '?' + new URLSearchParams(formData).toString();
                window.history.pushState({}, '', historyUrl);

                fetch(fullUrl)
                    .then(response => response.text())
                    .then(html => {
                        tableBody.innerHTML = html;
                    })
                    .catch(error => console.error('Error loading data:', error));
            }

            if (filterForm) {
                if (searchInput) {
                    searchInput.addEventListener('input', function () {
                        clearTimeout(searchTimeout);
                        searchTimeout = setTimeout(fetchResults, 400);
                    });
                }
                filterSelects.forEach(function (select) {
                    select.addEventListener('change', fetchResults);
                });
                filterForm.addEventListener('submit', function (e) {
                    e.preventDefault();
                    fetchResults();
                });
            }

            const detailFields = detailModal
                ? {
                    vehicle: detailModal.querySelector('[data-vehicle-approval-detail="vehicle"]'),
                    vehicleCode: detailModal.querySelector('[data-vehicle-approval-detail="vehicle-code"]'),
                    date: detailModal.querySelector('[data-vehicle-approval-detail="date"]'),
                    time: detailModal.querySelector('[data-vehicle-approval-detail="time"]'),
                    requester: detailModal.querySelector('[data-vehicle-approval-detail="requester"]'),
                    department: detailModal.querySelector('[data-vehicle-approval-detail="department"]'),
                    contact: detailModal.querySelector('[data-vehicle-approval-detail="contact"]'),
                    passengers: detailModal.querySelector('[data-vehicle-approval-detail="passengers"]'),
                    purpose: detailModal.querySelector('[data-vehicle-approval-detail="purpose"]'),
                    location: detailModal.querySelector('[data-vehicle-approval-detail="location"]'),
                    driver: detailModal.querySelector('[data-vehicle-approval-detail="driver"]'),
                    attachments: detailModal.querySelector('[data-vehicle-approval-detail="attachments"]'),
                    status: detailModal.querySelector('[data-vehicle-approval-detail="status"]'),
                    reasonRow: detailModal.querySelector('[data-vehicle-approval-detail="reason-row"]'),
                    reason: detailModal.querySelector('[data-vehicle-approval-detail="reason"]'),
                    approvalItem: detailModal.querySelector('[data-vehicle-approval-detail="approval-item"]'),
                    approvalLabel: detailModal.querySelector('[data-vehicle-approval-detail="approval-label"]'),
                    approvalName: detailModal.querySelector('[data-vehicle-approval-detail="approval-name"]'),
                    approvalAt: detailModal.querySelector('[data-vehicle-approval-detail="approval-at"]'),
                    created: detailModal.querySelector('[data-vehicle-approval-detail="created"]'),
                    updated: detailModal.querySelector('[data-vehicle-approval-detail="updated"]'),
                }
                : {};

            function openDetailModal(button) {
                if (!detailModal || !button) return;

                const data = button.dataset;
                const statusClass = data.approvalStatusClass || 'pending';
                const statusValue = (data.approvalStatus || '').toUpperCase();
                const isPending = statusValue === '' || statusValue === 'PENDING' || statusValue === 'DRAFT';
                const isAssigned = statusValue === 'ASSIGNED';
                const isRejected = statusValue === 'REJECTED' || statusValue === 'CANCELLED';
                const attachmentsRaw = data.approvalAttachments || '[]';
                let attachments = [];
                try {
                    attachments = JSON.parse(attachmentsRaw);
                } catch (e) {
                    attachments = [];
                }

                if (detailFields.vehicle) detailFields.vehicle.textContent = data.approvalVehicle || '-';
                if (detailFields.vehicleCode) {
                    detailFields.vehicleCode.textContent = data.approvalCode
                        ? 'รหัสคำขอ ' + data.approvalCode
                        : '-';
                }
                if (detailFields.date) detailFields.date.textContent = data.approvalDate || '-';
                if (detailFields.time) detailFields.time.textContent = data.approvalTime || '-';
                if (detailFields.requester) detailFields.requester.textContent = data.approvalRequester || '-';
                if (detailFields.department) detailFields.department.textContent = data.approvalDepartment || '-';
                if (detailFields.contact) detailFields.contact.textContent = data.approvalContact || '-';
                if (detailFields.passengers) detailFields.passengers.textContent = data.approvalPassengers || '-';
                if (detailFields.purpose) detailFields.purpose.textContent = data.approvalPurpose || '-';
                if (detailFields.location) detailFields.location.textContent = data.approvalLocation || '-';
                if (detailFields.driver) detailFields.driver.textContent = data.approvalDriver || '-';
                if (detailFields.status) {
                    detailFields.status.textContent = data.approvalStatusLabel || (isPending ? 'รออนุมัติ' : '-');
                    detailFields.status.className = `status-pill ${statusClass}`;
                }
                if (detailFields.reasonRow) {
                    detailFields.reasonRow.classList.toggle('hidden', !isRejected);
                }
                if (detailFields.reason) {
                    detailFields.reason.textContent = isRejected
                        ? (data.approvalReason || 'ไม่ระบุเหตุผล')
                        : '-';
                }
                if (detailFields.approvalLabel && detailFields.approvalName && detailFields.approvalAt) {
                    if (isPending) {
                        detailFields.approvalLabel.textContent = 'ผู้อนุมัติ';
                        detailFields.approvalName.textContent = 'รอการอนุมัติ';
                        detailFields.approvalAt.textContent = '-';
                    } else if (isAssigned) {
                        detailFields.approvalLabel.textContent = 'ผู้มอบหมาย';
                        detailFields.approvalName.textContent = data.approvalName || '-';
                        detailFields.approvalAt.textContent = data.approvalAt || '-';
                    } else {
                        detailFields.approvalLabel.textContent = isRejected ? 'ผู้ไม่อนุมัติ' : 'ผู้อนุมัติ';
                        detailFields.approvalName.textContent = data.approvalName || '-';
                        detailFields.approvalAt.textContent = data.approvalAt || '-';
                    }
                }
                if (detailFields.created) detailFields.created.textContent = data.approvalCreated || '-';
                if (detailFields.updated) detailFields.updated.textContent = data.approvalUpdated || '-';

                if (detailFields.attachments) {
                    detailFields.attachments.innerHTML = '';
                    if (!attachments.length) {
                        const empty = document.createElement('p');
                        empty.className = 'attachment-empty';
                        empty.textContent = 'ไม่มีไฟล์แนบ';
                        detailFields.attachments.appendChild(empty);
                    } else {
                        attachments.forEach((file) => {
                            const item = document.createElement('div');
                            item.className = 'attachment-item';
                            const name = document.createElement('span');
                            name.className = 'attachment-name';
                            name.textContent = file.fileName || 'ไฟล์แนบ';
                            item.appendChild(name);
                            if (file.filePath) {
                                const link = document.createElement('a');
                                link.href = file.filePath;
                                link.target = '_blank';
                                link.rel = 'noopener';
                                link.className = 'attachment-link';
                                link.textContent = 'ดูไฟล์';
                                item.appendChild(link);
                            }
                            detailFields.attachments.appendChild(item);
                        });
                    }
                }

                if (approvalIdInput) approvalIdInput.value = data.approvalId || '';
                if (approvalActionInput) approvalActionInput.value = '';
                if (approvalReasonInput) {
                    approvalReasonInput.value = isRejected && data.approvalReason && data.approvalReason !== 'ไม่ระบุเหตุผล'
                        ? data.approvalReason
                        : '';
                }
                toggleApprovalReasonRequired(false);

                currentStatus = statusValue || 'PENDING';
                canAssignStage = canAssign && (currentStatus === 'PENDING' || currentStatus === 'DRAFT');
                canFinalizeStage = canFinalize && currentStatus === 'ASSIGNED';

                if (approvalAssignSection) {
                    approvalAssignSection.classList.toggle('hidden', !canAssignStage);
                }

                const assignInputs = [assignVehicleSelect, assignDriverSelect, assignDriverNameInput, assignDriverTelInput];
                assignInputs.forEach(function (input) {
                    if (!input) return;
                    input.disabled = !canAssignStage;
                });

                if (assignVehicleSelect) {
                    assignVehicleSelect.value = data.approvalVehicleId || '';
                }
                if (assignDriverSelect) {
                    assignDriverSelect.value = data.approvalDriverId || '';
                }
                if (assignDriverNameInput) {
                    assignDriverNameInput.value = data.approvalDriverName || '';
                }
                if (assignDriverTelInput) {
                    assignDriverTelInput.value = data.approvalDriverTel || '';
                }

                if (approvalActionButtons.length > 0) {
                    const allowAction = canAssignStage || canFinalizeStage;
                    approvalActionButtons.forEach(function (btn) {
                        btn.disabled = !allowAction;
                        btn.classList.toggle('disabled', !allowAction);
                    });
                }

                detailModal.classList.remove('hidden');
            }

            function closeDetailModal() {
                if (!detailModal) return;
                detailModal.classList.add('hidden');
            }

            function openConfirm(action) {
                if (!confirmModal) return;
                pendingAction = action;
                const isAssignAction = action === 'approve' && canAssignStage;
                const isFinalApprove = action === 'approve' && canFinalizeStage;

                if (confirmTitle) {
                    if (isAssignAction) {
                        confirmTitle.textContent = 'ยืนยันการมอบหมายรถ';
                    } else if (isFinalApprove) {
                        confirmTitle.textContent = 'ยืนยันการอนุมัติ';
                    } else {
                        confirmTitle.textContent = 'ยืนยันการไม่อนุมัติ';
                    }
                }
                if (confirmMessage) {
                    if (isAssignAction) {
                        confirmMessage.textContent = 'ต้องการมอบหมายรถและส่งต่อให้ผู้บริหารพิจารณาใช่หรือไม่';
                    } else if (isFinalApprove) {
                        confirmMessage.textContent = 'ต้องการอนุมัติรายการจองรถนี้ใช่หรือไม่';
                    } else {
                        confirmMessage.textContent = 'ต้องการไม่อนุมัติรายการจองรถนี้ใช่หรือไม่';
                    }
                }
                if (confirmIcon) {
                    confirmIcon.className = action === 'approve'
                        ? 'fa-solid fa-circle-check'
                        : 'fa-solid fa-triangle-exclamation';
                }
                confirmModal.classList.remove('hidden');
            }

            function closeConfirm() {
                if (!confirmModal) return;
                confirmModal.classList.add('hidden');
            }

            document.addEventListener('click', function (event) {
                const target = event.target;
                if (!(target instanceof HTMLElement)) return;

                const detailBtn = target.closest('[data-vehicle-approval-action="detail"]');
                if (detailBtn) {
                    openDetailModal(detailBtn);
                    return;
                }

                if (target.closest('[data-vehicle-approval-close]')) {
                    closeDetailModal();
                }
            });

            closeButtons.forEach(function (btn) {
                btn.addEventListener('click', closeDetailModal);
            });

            if (detailModal) {
                detailModal.addEventListener('click', function (event) {
                    if (event.target === detailModal) {
                        closeDetailModal();
                    }
                });
            }

            approvalActionButtons.forEach(function (button) {
                button.addEventListener('click', function (event) {
                    event.preventDefault();
                    const action = button.getAttribute('data-vehicle-approval-submit');
                    if (!action || !approvalActionInput) return;

                    if (!(canAssignStage || canFinalizeStage)) {
                        return;
                    }

                    if (action === 'approve' && canAssignStage) {
                        const hasVehicle = assignVehicleSelect && assignVehicleSelect.value !== '';
                        const hasDriver = (assignDriverSelect && assignDriverSelect.value !== '') ||
                            (assignDriverNameInput && assignDriverNameInput.value.trim() !== '');
                        if (!hasVehicle || !hasDriver) {
                            alert('กรุณาเลือกยานพาหนะและผู้ขับรถก่อนส่งต่อผู้บริหาร');
                            return;
                        }
                    }

                    approvalActionInput.value = action;
                    toggleApprovalReasonRequired(action === 'reject');
                    openConfirm(action);
                });
            });

            if (confirmBtn) {
                confirmBtn.addEventListener('click', function () {
                    closeConfirm();
                    if (pendingAction === 'reject') {
                        toggleApprovalReasonRequired(true);
                    }
                    submitApprovalForm();
                });
            }

            if (confirmCloseBtn) {
                confirmCloseBtn.addEventListener('click', function () {
                    closeConfirm();
                });
            }

            if (confirmModal) {
                confirmModal.addEventListener('click', function (event) {
                    if (event.target === confirmModal) {
                        closeConfirm();
                    }
                });
            }

            if (assignDriverSelect && assignDriverNameInput && assignDriverTelInput) {
                assignDriverSelect.addEventListener('change', function () {
                    const selected = assignDriverSelect.options[assignDriverSelect.selectedIndex];
                    if (!selected || !selected.value) return;
                    const name = selected.dataset.driverName || '';
                    const tel = selected.dataset.driverTel || '';
                    if (name) {
                        assignDriverNameInput.value = name;
                    }
                    if (tel) {
                        assignDriverTelInput.value = tel;
                    }
                });
            }
        });
    </script>
</body>
</html>

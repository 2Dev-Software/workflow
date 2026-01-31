<?php
require_once __DIR__ . '/src/Services/auth/auth-guard.php';
require_once __DIR__ . '/src/Services/security/security-service.php';
require_once __DIR__ . '/src/Services/teacher/teacher-profile.php';
require_once __DIR__ . '/src/Services/system/system-year.php';
require_once __DIR__ . '/src/Services/vehicle/vehicle-reservation-data.php';

$teacher_name = (string) ($teacher['fName'] ?? '');
$current_pid = (string) ($_SESSION['pID'] ?? '');
$currentThaiYear = (int) date('Y') + 543;
$dh_year_value = (int) ($dh_year !== '' ? $dh_year : $currentThaiYear);
if ($dh_year_value < 2500) {
    $dh_year_value = $currentThaiYear;
}
$vehicle_booking_year = $dh_year_value;
$requester_pid = $current_pid;
$today = date('Y-m-d');

$vehicle_reservation_status_labels = [
    'DRAFT' => ['label' => 'แบบร่าง', 'class' => 'pending'],
    'PENDING' => ['label' => 'รออนุมัติ', 'class' => 'pending'],
    'ASSIGNED' => ['label' => 'มอบหมายแล้ว', 'class' => 'approved'],
    'APPROVED' => ['label' => 'อนุมัติแล้ว', 'class' => 'approved'],
    'REJECTED' => ['label' => 'ไม่อนุมัติ', 'class' => 'rejected'],
    'CANCELLED' => ['label' => 'ยกเลิก', 'class' => 'rejected'],
    'COMPLETED' => ['label' => 'เสร็จสิ้น', 'class' => 'approved'],
];

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

$format_thai_datetime = static function (string $datetime) use ($thai_months): string {
    $datetime = trim($datetime);
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

    return trim($day . ' ' . $month_label . ' ' . $year . ' เวลา ' . $date_obj->format('H:i'));
};

$format_thai_datetime_range = static function (string $start, string $end) use ($format_thai_datetime): string {
    $start_label = $format_thai_datetime($start);
    $end_label = $format_thai_datetime($end);

    if ($end_label === '-' || $end === $start) {
        return $start_label;
    }

    return $start_label . ' - ' . $end_label;
};

require_once __DIR__ . '/src/Services/vehicle/vehicle-reservation-update.php';
require_once __DIR__ . '/src/Services/vehicle/vehicle-reservation-save.php';

$vehicle_departments = vehicle_reservation_get_departments($connection);
$vehicle_factions = vehicle_reservation_get_factions($connection);
$vehicle_teachers = vehicle_reservation_get_teachers($connection);
$vehicle_booking_history = vehicle_reservation_get_bookings($connection, $vehicle_booking_year, $requester_pid);
$vehicle_booking_ids = array_values(array_filter(array_map(
    static fn(array $booking): int => (int) ($booking['bookingID'] ?? 0),
    $vehicle_booking_history
)));
$vehicle_booking_attachments = vehicle_reservation_get_booking_attachments($connection, $vehicle_booking_ids);

$vehicle_teacher_map = [];
foreach ($vehicle_teachers as $teacher_item) {
    $teacher_id = trim((string) ($teacher_item['id'] ?? ''));
    if ($teacher_id === '') {
        continue;
    }
    $vehicle_teacher_map[$teacher_id] = trim((string) ($teacher_item['name'] ?? ''));
}

$vehicle_booking_payload = [];
foreach ($vehicle_booking_history as $booking_item) {
    $booking_id = (int) ($booking_item['bookingID'] ?? 0);
    $status_key = strtoupper((string) ($booking_item['status'] ?? 'PENDING'));
    $status_meta = $vehicle_reservation_status_labels[$status_key] ?? $vehicle_reservation_status_labels['PENDING'];
    $companion_ids = [];
    $raw_companions = $booking_item['companionIds'] ?? null;
    if (is_string($raw_companions) && $raw_companions !== '') {
        $decoded = json_decode($raw_companions, true);
        if (is_array($decoded)) {
            $companion_ids = array_values(array_filter(array_map(
                static fn($id): string => trim((string) $id),
                $decoded
            )));
        }
    }
    $companion_names = [];
    foreach ($companion_ids as $companion_id) {
        $name = $vehicle_teacher_map[$companion_id] ?? '';
        if ($name !== '') {
            $companion_names[] = $name;
        }
    }
    $attachments = $vehicle_booking_attachments[(string) $booking_id] ?? [];

    $updated_at_value = trim((string) ($booking_item['updatedAt'] ?? ''));
    if ($updated_at_value === '' || $updated_at_value === '0000-00-00 00:00:00') {
        $updated_at_value = (string) ($booking_item['createdAt'] ?? '');
    }

    $vehicle_booking_payload[] = [
        'id' => $booking_id,
        'department' => (string) ($booking_item['department'] ?? ''),
        'writeDate' => (string) ($booking_item['writeDate'] ?? ''),
        'purpose' => (string) ($booking_item['purpose'] ?? ''),
        'location' => (string) ($booking_item['location'] ?? ''),
        'passengerCount' => (int) ($booking_item['passengerCount'] ?? 0),
        'startAt' => (string) ($booking_item['startAt'] ?? ''),
        'endAt' => (string) ($booking_item['endAt'] ?? ''),
        'fuelSource' => (string) ($booking_item['fuelSource'] ?? ''),
        'companionIds' => $companion_ids,
        'companionNames' => $companion_names,
        'status' => $status_key,
        'statusLabel' => $status_meta['label'],
        'statusClass' => $status_meta['class'],
        'statusReason' => (string) ($booking_item['statusReason'] ?? ''),
        'attachments' => $attachments,
        'createdAt' => (string) ($booking_item['createdAt'] ?? ''),
        'updatedAt' => $updated_at_value,
        'createdAtLabel' => $format_thai_datetime((string) ($booking_item['createdAt'] ?? '')),
        'updatedAtLabel' => $format_thai_datetime($updated_at_value),
    ];
}

$vehicle_reservation_alert = $_SESSION['vehicle_reservation_alert'] ?? null;
unset($_SESSION['vehicle_reservation_alert']);

?>

<!DOCTYPE html>
<html lang="th">
<?php require_once __DIR__ . '/public/components/x-head.php'; ?>

<body>

    <?php require_once __DIR__ . '/public/components/layout/preloader.php'; ?>
    <?php if (!empty($vehicle_reservation_alert)) : ?>
        <?php $alert = $vehicle_reservation_alert; ?>
        <?php require __DIR__ . '/public/components/x-alert.php'; ?>
    <?php endif; ?>

    <?php require_once __DIR__ . '/public/components/partials/x-sidebar.php';
    ?>

    <section class="main-section">

        <?php require_once __DIR__ . '/public/components/partials/x-navigation.php';
        ?>

        <main class="content-wrapper">

            <div class="content-header">
                <h1>ยินดีต้อนรับ</h1>
                <p>การจองยานพาหนะ / บันทึกการจองยานพาหนะ</p>
            </div>

            <div class="tabs-container setting-page">
                <div class="button-container vehicle">
                    <button class="tab-btn active"
                        onclick="openTab('vehicleReservationForm', event)">จองยานพาหนะ</button>
                    <button class="tab-btn" onclick="openTab('vehicleHistory', event)">ประวัติการจอง</button>
                </div>
            </div>

            <div class="vehicle-content">
                <form id="vehicleReservationForm" class="tab-content active" method="post"
                    action="<?= htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') ?>"
                    enctype="multipart/form-data" data-vehicle-form>
                    <input type="hidden" name="csrf_token"
                        value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="dh_year"
                        value="<?= htmlspecialchars((string) $dh_year_value, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="requesterPID"
                        value="<?= htmlspecialchars($requester_pid, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="companionCount" id="companionCount" value="0">
                    <div class="vehicle-row">
                        <div class="vehicle-input-content">
                            <label>ส่วนราชการ</label>
                            <div class="custom-select-wrapper" id="dept-wrapper">
                                <input type="hidden" id="department" name="department" value="">

                                <div class="custom-select-trigger">
                                    <span class="select-value">เลือกส่วนราชการ</span>
                                    <i class="fa-solid fa-chevron-down arrow"></i>
                                </div>

                                <div class="custom-options">
                                    <?php foreach ($vehicle_departments as $dept): ?>
                                        <span class="custom-option"
                                            data-value="<?= htmlspecialchars($dept['name'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($dept['name'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endforeach; ?>
                                    <?php foreach ($vehicle_factions as $faction): ?>
                                        <span class="custom-option"
                                            data-value="<?= htmlspecialchars($faction['name'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($faction['name'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <p class="form-error hidden" id="departmentError">กรุณาเลือกส่วนราชการ</p>
                        </div>

                        <div class="vehicle-input-content">
                            <label for="writeDate">วันที่เขียน</label>
                            <input type="date" id="writeDate" name="writeDate"
                                value="<?= htmlspecialchars($today, ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                    </div>

                    <div class="vehicle-row">
                        <div class="vehicle-input-content">
                            <label>ข้าพเจ้าพร้อมด้วย</label>
                            <div class="go-with-dropdown">
                                <input type="text" id="searchInput" placeholder="ค้นหารายชื่อคุณครู" autocomplete="off"
                                    onkeyup="filterDropdown()" onclick="openDropdown()" />

                                <div id="myDropdown" class="go-with-dropdown-content">
                                    <?php foreach ($vehicle_teachers as $teacher_item): ?>
                                        <label class="dropdown-item">
                                            <input type="checkbox"
                                                name="companionIds[]"
                                                value="<?= htmlspecialchars($teacher_item['id'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-name="<?= htmlspecialchars($teacher_item['name'], ENT_QUOTES, 'UTF-8') ?>">
                                            <p><?= htmlspecialchars($teacher_item['name'], ENT_QUOTES, 'UTF-8') ?></p>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <button class="show-member" type="button">
                                <p>แสดงผู้เดินทางทั้งหมด</p>
                            </button>
                        </div>
                    </div>

                    <div id="memberModal" class="custom-modal">
                        <div class="custom-modal-content">
                            <div class="member-header">
                                <p>รายชื่อผู้เดินทางที่เลือก</p>
                                <i class="fa-solid fa-xmark close-modal"></i>
                            </div>
                            <div id="selectedMemberList" class="member-list-container">
                            </div>
                        </div>
                    </div>

                    <div class="column vehicle-row">
                        <div class="vehicle-input-content">
                            <label for="purpose">ขออนุญาตใช้รถเพื่อ</label>
                            <textarea id="purpose" name="purpose" placeholder="ระบุวัตถุประสงค์" rows="3"
                                required></textarea>
                        </div>

                        <div class="vehicle-input-content">
                            <label for="location">ณ (สถานที่)</label>
                            <input type="text" id="location" name="location" placeholder="ระบุสถานที่ปลายทาง" required>
                        </div>
                    </div>

                    <div class="vehicle-row">
                        <div class="vehicle-input-content with-unit">
                            <label for="passengerCount">มีคนนั่งจำนวน</label>
                            <div class="input-with-unit">
                                <input type="number" id="passengerCount" name="passengerCount" placeholder="จำนวน"
                                    min="1" max="9999" inputmode="numeric" readonly value="1">
                            </div>
                        </div>

                        <div class="vehicle-input-content">
                            <label for="startDate">ในวันที่</label>
                            <input type="date" id="startDate" name="startDate" required>
                        </div>

                        <div class="vehicle-input-content">
                            <label for="endDate">ถึงวันที่</label>
                            <input type="date" id="endDate" name="endDate" required>
                        </div>

                        <div class="vehicle-input-content">
                            <label>จำนวนวัน</label>
                            <div class="calculated-field" id="dayCount">
                                <i class="fa-regular fa-calendar"></i>
                                <p data-day-count aria-live="polite">-</p>
                            </div>
                        </div>
                    </div>

                    <div class="vehicle-row">
                        <div class="vehicle-input-content">
                            <label for="startTime">เวลาเริ่มต้น</label>
                            <input type="time" id="startTime" name="startTime" required>
                        </div>

                        <div class="vehicle-input-content">
                            <label for="endTime">เวลาสิ้นสุด</label>
                            <input type="time" id="endTime" name="endTime" required>
                        </div>
                    </div>

                    <div class="vehicle-row">
                        <div class="vehicle-input-content">
                            <label>ใช้น้ำมันเชื้อเพลิงจาก</label>
                            <div class="radio-group">
                                <div class="radio-item">
                                    <input type="radio" id="fuel-central" name="fuelSource" value="central" checked>
                                    <label for="fuel-central">ส่วนกลาง</label>
                                </div>
                                <div class="radio-item">
                                    <input type="radio" id="fuel-project" name="fuelSource" value="project">
                                    <label for="fuel-project">โครงการ</label>
                                </div>
                                <div class="radio-item">
                                    <input type="radio" id="fuel-user" name="fuelSource" value="user">
                                    <label for="fuel-user">ผู้ใช้</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="vehicle-row">
                        <div class="vehicle-input-content">
                            <label for="attachment">แนบเอกสาร</label>
                            <div class="vehicle-attachment-box">
                                <input type="file" id="attachment" name="attachments[]" class="file-input" multiple
                                    accept=".pdf,image/png,image/jpeg">
                                <label class="file-upload" for="attachment">
                                    <i class="fa-solid fa-paperclip"></i>
                                    <span id="fileLabel">แนบไฟล์เอกสาร</span>
                                </label>
                                <p class="form-hint">รองรับ PDF, JPG, PNG ไม่เกิน 10MB ต่อไฟล์ (สูงสุด 5 ไฟล์)</p>
                                <p class="form-error hidden" id="attachmentError">แนบได้สูงสุด 5 ไฟล์</p>
                            </div>
                            <div class="attachment-list" id="attachmentList" aria-live="polite"></div>
                        </div>
                    </div>

                    <div class="submit-section">
                        <button type="submit" class="btn-submit" name="vehicle_reservation_save" value="1">บันทึกจองยานพาหนะ</button>
                    </div>
                </form>

                <div class="vehicle-history tab-content" id="vehicleHistory">
                    <div class="table-responsive">
                        <table class="custom-table booking-table">
                            <thead>
                                <tr>
                                    <th>ช่วงเวลาใช้งาน</th>
                                    <th>สถานะ</th>
                                    <th>หมายเหตุ</th>
                                    <th>อัปเดตล่าสุด</th>
                                    <th>จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($vehicle_booking_history)) : ?>
                                    <tr>
                                        <td colspan="5" class="booking-empty">ยังไม่มีประวัติการจอง</td>
                                    </tr>
                                <?php else : ?>
                                    <?php foreach ($vehicle_booking_history as $booking) : ?>
                                        <?php
                                        $status_key = strtoupper((string) ($booking['status'] ?? 'PENDING'));
                                        $status_meta = $vehicle_reservation_status_labels[$status_key] ?? $vehicle_reservation_status_labels['PENDING'];
                                        $status_label = $status_meta['label'];
                                        $status_class = $status_meta['class'];
                                        $status_reason = trim((string) ($booking['statusReason'] ?? ''));
                                        $updated_at = trim((string) ($booking['updatedAt'] ?? ''));
                                        if ($updated_at === '' || $updated_at === '0000-00-00 00:00:00') {
                                            $updated_at = (string) ($booking['createdAt'] ?? '');
                                        }
                                        ?>
                                        <tr>
                                            <td><?= htmlspecialchars($format_thai_datetime_range((string) ($booking['startAt'] ?? ''), (string) ($booking['endAt'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td>
                                                <span class="status-pill <?= htmlspecialchars($status_class, ENT_QUOTES, 'UTF-8') ?>">
                                                    <?= htmlspecialchars($status_label, ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($status_reason !== '' ? $status_reason : '-', ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($format_thai_datetime($updated_at), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td>
                                                <button type="button" class="btn-outline vehicle-history-action"
                                                    data-vehicle-booking-action="detail"
                                                    data-vehicle-booking-id="<?= htmlspecialchars((string) ($booking['bookingID'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                                    <?= $status_key === 'PENDING' ? 'ดู/แก้ไข' : 'ดูรายละเอียด' ?>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

        </main>

        <div id="vehicleBookingDetailModal" class="modal-overlay hidden">
            <div class="modal-content booking-detail-modal vehicle-detail-modal">
                <header class="modal-header">
                    <div class="modal-title">
                        <i class="fa-solid fa-car-side"></i>
                        <span>รายละเอียดการจองยานพาหนะ</span>
                    </div>
                    <div class="close-modal-btn" data-vehicle-modal-close="vehicleBookingDetailModal">
                        <i class="fa-solid fa-xmark"></i>
                    </div>
                </header>
                <div class="modal-body booking-detail-body">
                    <div class="vehicle-detail-head">
                        <span class="status-pill pending" data-vehicle-detail="status-pill">รออนุมัติ</span>
                        <span class="detail-subtext" data-vehicle-detail="status-note">แก้ไขได้เฉพาะรายการที่รออนุมัติ</span>
                    </div>

                    <form class="vehicle-edit-form" method="post" action="vehicle-reservation.php"
                        enctype="multipart/form-data" data-vehicle-edit-form>
                        <input type="hidden" name="csrf_token"
                            value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="dh_year"
                            value="<?= htmlspecialchars((string) $dh_year_value, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="vehicle_booking_id" value="">
                        <input type="hidden" name="vehicle_reservation_update" value="1">

                        <div class="booking-detail-grid vehicle-edit-grid">
                            <div class="detail-item detail-half">
                                <label class="detail-label" for="vehicleEditDepartment">ส่วนราชการ</label>
                                <select class="form-input" id="vehicleEditDepartment" name="department" required>
                                    <option value="" disabled>เลือกส่วนราชการ</option>
                                    <?php foreach ($vehicle_departments as $dept): ?>
                                        <option value="<?= htmlspecialchars($dept['name'], ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars($dept['name'], ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                    <?php foreach ($vehicle_factions as $faction): ?>
                                        <option value="<?= htmlspecialchars($faction['name'], ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars($faction['name'], ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="detail-item detail-half">
                                <label class="detail-label" for="vehicleEditWriteDate">วันที่เขียน</label>
                                <input class="form-input" type="date" id="vehicleEditWriteDate" name="writeDate">
                            </div>
                            <div class="detail-item detail-full">
                                <label class="detail-label" for="vehicleEditPurpose">วัตถุประสงค์</label>
                                <textarea class="form-input booking-textarea" id="vehicleEditPurpose" name="purpose" rows="3"
                                    placeholder="ระบุวัตถุประสงค์"></textarea>
                            </div>
                            <div class="detail-item detail-full">
                                <label class="detail-label" for="vehicleEditLocation">สถานที่ปลายทาง</label>
                                <input class="form-input" type="text" id="vehicleEditLocation" name="location"
                                    placeholder="ระบุสถานที่ปลายทาง">
                            </div>
                            <div class="detail-item detail-half">
                                <label class="detail-label" for="vehicleEditPassengerCount">จำนวนผู้เดินทาง</label>
                                <input class="form-input" type="number" id="vehicleEditPassengerCount" name="passengerCount" readonly>
                            </div>
                            <div class="detail-item detail-half">
                                <label class="detail-label" for="vehicleEditStartDate">วันที่เริ่มเดินทาง</label>
                                <input class="form-input" type="date" id="vehicleEditStartDate" name="startDate" required>
                            </div>
                            <div class="detail-item detail-half">
                                <label class="detail-label" for="vehicleEditEndDate">วันที่สิ้นสุด</label>
                                <input class="form-input" type="date" id="vehicleEditEndDate" name="endDate" required>
                            </div>
                            <div class="detail-item detail-half">
                                <label class="detail-label" for="vehicleEditStartTime">เวลาเริ่มต้น</label>
                                <input class="form-input" type="time" id="vehicleEditStartTime" name="startTime" required>
                            </div>
                            <div class="detail-item detail-half">
                                <label class="detail-label" for="vehicleEditEndTime">เวลาสิ้นสุด</label>
                                <input class="form-input" type="time" id="vehicleEditEndTime" name="endTime" required>
                            </div>
                            <div class="detail-item detail-full">
                                <label class="detail-label">ใช้น้ำมันเชื้อเพลิงจาก</label>
                                <div class="radio-group">
                                    <div class="radio-item">
                                        <input type="radio" id="vehicleEditFuelCentral" name="fuelSource" value="central">
                                        <label for="vehicleEditFuelCentral">ส่วนกลาง</label>
                                    </div>
                                    <div class="radio-item">
                                        <input type="radio" id="vehicleEditFuelProject" name="fuelSource" value="project">
                                        <label for="vehicleEditFuelProject">โครงการ</label>
                                    </div>
                                    <div class="radio-item">
                                        <input type="radio" id="vehicleEditFuelUser" name="fuelSource" value="user">
                                        <label for="vehicleEditFuelUser">ผู้ใช้</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="booking-detail-section vehicle-companion-section">
                            <div class="vehicle-companion-head">
                                <h4>ผู้ร่วมเดินทาง</h4>
                                <span class="detail-subtext">เลือกผู้ร่วมเดินทางเพื่อคำนวณจำนวนผู้เดินทางอัตโนมัติ</span>
                            </div>
                            <label class="detail-label" for="vehicleEditSearchInput">ข้าพเจ้าพร้อมด้วย</label>
                            <div class="go-with-dropdown vehicle-companion-dropdown">
                                <input class="form-input" type="text" id="vehicleEditSearchInput"
                                    placeholder="ค้นหารายชื่อคุณครู" autocomplete="off">
                                <div id="vehicleEditDropdown" class="go-with-dropdown-content">
                                    <?php foreach ($vehicle_teachers as $teacher_item): ?>
                                        <label class="dropdown-item"
                                            data-name="<?= htmlspecialchars($teacher_item['name'], ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="checkbox" name="companionIds[]"
                                                value="<?= htmlspecialchars($teacher_item['id'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-name="<?= htmlspecialchars($teacher_item['name'], ENT_QUOTES, 'UTF-8') ?>">
                                            <p><?= htmlspecialchars($teacher_item['name'], ENT_QUOTES, 'UTF-8') ?></p>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="booking-detail-section vehicle-attachment-section">
                            <div class="vehicle-attachment-head">
                                <h4>ไฟล์แนบ</h4>
                                <span class="detail-subtext">ดูหรือดาวน์โหลดไฟล์ที่แนบไว้กับคำขอนี้</span>
                            </div>
                            <div class="attachment-list" id="vehicleExistingAttachments"></div>
                            <div class="vehicle-attachment-edit" data-vehicle-attachment-edit>
                                <input type="file" id="vehicleEditAttachments" name="attachments[]" class="file-input"
                                    multiple accept=".pdf,image/png,image/jpeg">
                                <label class="file-upload" for="vehicleEditAttachments">
                                    <i class="fa-solid fa-paperclip"></i>
                                    <span>แนบไฟล์เพิ่มเติม</span>
                                </label>
                                <p class="form-hint">รองรับ PDF, JPG, PNG ไม่เกิน 10MB ต่อไฟล์ (สูงสุด 5 ไฟล์)</p>
                                <p class="form-error hidden" id="vehicleEditAttachmentError"></p>
                                <div class="attachment-list" id="vehicleNewAttachments"></div>
                            </div>
                            <div id="vehicleRetainAttachments" class="hidden"></div>
                        </div>

                        <div class="booking-detail-meta">
                            <div class="detail-meta-item">
                                <span>สร้างรายการ</span>
                                <strong data-vehicle-detail="created">-</strong>
                            </div>
                            <div class="detail-meta-item">
                                <span>อัปเดตล่าสุด</span>
                                <strong data-vehicle-detail="updated">-</strong>
                            </div>
                        </div>

                        <div class="booking-detail-actions">
                            <button type="button" class="btn-outline" data-vehicle-modal-close="vehicleBookingDetailModal">ปิดหน้าต่าง</button>
                            <button type="submit" class="btn-confirm" data-vehicle-edit-submit>บันทึกการแก้ไข</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <?php require_once __DIR__ . '/public/components/partials/x-footer.php'; ?>


    </section>


    <?php require_once __DIR__ . '/public/components/x-scripts.php'; ?>
    <script>
        window.vehicleBookingHistory = <?= json_encode($vehicle_booking_payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        window.vehicleBookingFileEndpoint = 'public/api/vehicle-booking-file.php';
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const bookingData = Array.isArray(window.vehicleBookingHistory) ? window.vehicleBookingHistory : [];
            const bookingMap = bookingData.reduce((acc, item) => {
                if (item && item.id) acc[item.id] = item;
                return acc;
            }, {});

            const modal = document.getElementById('vehicleBookingDetailModal');
            const closeButtons = document.querySelectorAll('[data-vehicle-modal-close]');
            const openButtons = document.querySelectorAll('[data-vehicle-booking-action="detail"]');
            const form = modal ? modal.querySelector('[data-vehicle-edit-form]') : null;
            const statusPill = modal ? modal.querySelector('[data-vehicle-detail="status-pill"]') : null;
            const statusNote = modal ? modal.querySelector('[data-vehicle-detail="status-note"]') : null;
            const createdValue = modal ? modal.querySelector('[data-vehicle-detail="created"]') : null;
            const updatedValue = modal ? modal.querySelector('[data-vehicle-detail="updated"]') : null;
            const submitBtn = modal ? modal.querySelector('[data-vehicle-edit-submit]') : null;

            const fieldMap = modal
                ? {
                    bookingId: modal.querySelector('[name="vehicle_booking_id"]'),
                    department: document.getElementById('vehicleEditDepartment'),
                    writeDate: document.getElementById('vehicleEditWriteDate'),
                    purpose: document.getElementById('vehicleEditPurpose'),
                    location: document.getElementById('vehicleEditLocation'),
                    passengerCount: document.getElementById('vehicleEditPassengerCount'),
                    startDate: document.getElementById('vehicleEditStartDate'),
                    endDate: document.getElementById('vehicleEditEndDate'),
                    startTime: document.getElementById('vehicleEditStartTime'),
                    endTime: document.getElementById('vehicleEditEndTime'),
                    fuelRadios: modal.querySelectorAll('input[name="fuelSource"]'),
                    companionList: document.getElementById('vehicleEditDropdown'),
                    companionSearch: document.getElementById('vehicleEditSearchInput'),
                }
                : {};

            const existingAttachmentsEl = document.getElementById('vehicleExistingAttachments');
            const retainContainer = document.getElementById('vehicleRetainAttachments');
            const editAttachmentsInput = document.getElementById('vehicleEditAttachments');
            const editAttachmentsList = document.getElementById('vehicleNewAttachments');
            const editAttachmentError = document.getElementById('vehicleEditAttachmentError');
            const editAttachmentBox = modal ? modal.querySelector('[data-vehicle-attachment-edit]') : null;

            const MAX_ATTACHMENTS = 5;
            const MAX_ATTACHMENT_SIZE = 10 * 1024 * 1024;
            const ALLOWED_ATTACHMENT_TYPES = [
                'application/pdf',
                'image/jpeg',
                'image/png',
            ];

            let pendingEditFiles = [];
            let currentBooking = null;

            function formatFileSize(size) {
                if (!size) return '0 KB';
                const kb = size / 1024;
                if (kb < 1024) return `${Math.ceil(kb)} KB`;
                const mb = kb / 1024;
                return `${mb.toFixed(1)} MB`;
            }

            function setEditAttachmentError(message) {
                if (!editAttachmentError) return;
                editAttachmentError.textContent = message || '';
                editAttachmentError.classList.toggle('hidden', !message);
            }

            function syncEditAttachmentInput() {
                if (!editAttachmentsInput) return;
                const dataTransfer = new DataTransfer();
                pendingEditFiles.forEach((file) => dataTransfer.items.add(file));
                editAttachmentsInput.files = dataTransfer.files;
            }

            function renderNewAttachments() {
                if (!editAttachmentsList) return;
                editAttachmentsList.innerHTML = '';

                if (pendingEditFiles.length === 0) {
                    const empty = document.createElement('p');
                    empty.className = 'attachment-empty';
                    empty.textContent = 'ยังไม่มีไฟล์แนบใหม่';
                    editAttachmentsList.appendChild(empty);
                    return;
                }

                pendingEditFiles.forEach((file, index) => {
                    const item = document.createElement('div');
                    item.className = 'attachment-item';

                    const meta = document.createElement('div');
                    meta.className = 'attachment-meta';

                    const name = document.createElement('span');
                    name.className = 'attachment-name';
                    name.textContent = file.name;

                    const size = document.createElement('span');
                    size.className = 'attachment-size';
                    size.textContent = formatFileSize(file.size);

                    meta.appendChild(name);
                    meta.appendChild(size);

                    const actions = document.createElement('div');
                    actions.className = 'attachment-actions';

                    const removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.className = 'attachment-action remove';
                    removeBtn.textContent = 'ลบไฟล์';
                    removeBtn.addEventListener('click', () => {
                        pendingEditFiles = pendingEditFiles.filter((_, i) => i !== index);
                        syncEditAttachmentInput();
                        renderNewAttachments();
                        setEditAttachmentError('');
                    });

                    actions.appendChild(removeBtn);
                    item.appendChild(meta);
                    item.appendChild(actions);
                    editAttachmentsList.appendChild(item);
                });
            }

            function addNewAttachments(files) {
                if (!files || files.length === 0) return;
                const existingKeys = new Set(
                    pendingEditFiles.map(
                        (file) => `${file.name}-${file.size}-${file.lastModified}`
                    )
                );

                let hasInvalid = false;
                let hitLimit = false;
                const existingCount = currentBooking && Array.isArray(currentBooking.attachments)
                    ? currentBooking.attachments.length
                    : 0;

                Array.from(files).forEach((file) => {
                    const key = `${file.name}-${file.size}-${file.lastModified}`;
                    if (existingKeys.has(key)) {
                        return;
                    }

                    if (!ALLOWED_ATTACHMENT_TYPES.includes(file.type) || file.size > MAX_ATTACHMENT_SIZE) {
                        hasInvalid = true;
                        return;
                    }

                    if (existingCount + pendingEditFiles.length >= MAX_ATTACHMENTS) {
                        hitLimit = true;
                        return;
                    }

                    pendingEditFiles.push(file);
                    existingKeys.add(key);
                });

                if (hitLimit) {
                    setEditAttachmentError(`แนบได้สูงสุด ${MAX_ATTACHMENTS} ไฟล์`);
                } else if (hasInvalid) {
                    setEditAttachmentError('รองรับเฉพาะ PDF, JPG, PNG ขนาดไม่เกิน 10MB');
                } else {
                    setEditAttachmentError('');
                }

                syncEditAttachmentInput();
                renderNewAttachments();
            }

            function renderExistingAttachments(attachments, editable) {
                if (!existingAttachmentsEl || !retainContainer) return;
                existingAttachmentsEl.innerHTML = '';
                retainContainer.innerHTML = '';

                if (!attachments || attachments.length === 0) {
                    const empty = document.createElement('p');
                    empty.className = 'attachment-empty';
                    empty.textContent = 'ยังไม่มีไฟล์แนบ';
                    existingAttachmentsEl.appendChild(empty);
                    return;
                }

                attachments.forEach((file) => {
                    const item = document.createElement('div');
                    item.className = 'attachment-item';
                    item.dataset.fileId = String(file.fileID || '');

                    const meta = document.createElement('div');
                    meta.className = 'attachment-meta';

                    const name = document.createElement('span');
                    name.className = 'attachment-name';
                    name.textContent = file.fileName || 'ไฟล์แนบ';

                    const size = document.createElement('span');
                    size.className = 'attachment-size';
                    size.textContent = formatFileSize(file.fileSize);

                    meta.appendChild(name);
                    meta.appendChild(size);

                    const actions = document.createElement('div');
                    actions.className = 'attachment-actions';

                    const viewBtn = document.createElement('button');
                    viewBtn.type = 'button';
                    viewBtn.className = 'attachment-action view';
                    viewBtn.textContent = 'ดูไฟล์';
                    viewBtn.addEventListener('click', () => {
                        const url = `${window.vehicleBookingFileEndpoint}?booking_id=${currentBooking.id}&file_id=${file.fileID}`;
                        window.open(url, '_blank', 'noopener');
                    });

                    const downloadBtn = document.createElement('button');
                    downloadBtn.type = 'button';
                    downloadBtn.className = 'attachment-action';
                    downloadBtn.textContent = 'ดาวน์โหลด';
                    downloadBtn.addEventListener('click', () => {
                        const url = `${window.vehicleBookingFileEndpoint}?booking_id=${currentBooking.id}&file_id=${file.fileID}&download=1`;
                        window.open(url, '_blank', 'noopener');
                    });

                    actions.appendChild(viewBtn);
                    actions.appendChild(downloadBtn);

                    if (editable) {
                        const removeBtn = document.createElement('button');
                        removeBtn.type = 'button';
                        removeBtn.className = 'attachment-action remove';
                        removeBtn.textContent = 'ลบไฟล์';
                        removeBtn.addEventListener('click', () => {
                            currentBooking.attachments = currentBooking.attachments.filter(
                                (item) => item.fileID !== file.fileID
                            );
                            renderExistingAttachments(currentBooking.attachments, editable);
                            renderNewAttachments();
                        });
                        actions.appendChild(removeBtn);

                        const retainInput = document.createElement('input');
                        retainInput.type = 'hidden';
                        retainInput.name = 'retainAttachmentIds[]';
                        retainInput.value = String(file.fileID || '');
                        retainContainer.appendChild(retainInput);
                    }

                    item.appendChild(meta);
                    item.appendChild(actions);
                    existingAttachmentsEl.appendChild(item);
                });
            }

            function updateCompanionSummary() {
                if (!fieldMap.companionSearch || !fieldMap.companionList) return;
                const selected = fieldMap.companionList.querySelectorAll('input[type="checkbox"]:checked').length;
                if (document.activeElement === fieldMap.companionSearch) {
                    return;
                }
                fieldMap.companionSearch.value = selected > 0 ? `จำนวน ${selected} รายชื่อ` : '';
            }

            function filterCompanionDropdown(keyword) {
                if (!fieldMap.companionList) return;
                const searchText = keyword.trim().toLowerCase();
                fieldMap.companionList.querySelectorAll('.dropdown-item').forEach((item) => {
                    const name = (item.dataset.name || item.textContent || '').toLowerCase();
                    item.style.display = searchText !== '' && !name.includes(searchText) ? 'none' : '';
                });
            }

            function openCompanionDropdown() {
                if (fieldMap.companionList) {
                    fieldMap.companionList.classList.add('show');
                }
            }

            function closeCompanionDropdown() {
                if (fieldMap.companionList) {
                    fieldMap.companionList.classList.remove('show');
                }
                updateCompanionSummary();
            }

            function setEditable(editable) {
                if (!modal || !form) return;
                form.classList.toggle('is-readonly', !editable);
                if (submitBtn) {
                    submitBtn.disabled = !editable;
                    submitBtn.classList.toggle('hidden', !editable);
                }
                if (editAttachmentBox) {
                    editAttachmentBox.classList.toggle('hidden', !editable);
                }
                if (!editable) {
                    closeCompanionDropdown();
                }

                const inputs = form.querySelectorAll('input, textarea, select');
                inputs.forEach((input) => {
                    if (input.name === 'csrf_token' || input.name === 'vehicle_booking_id' || input.name === 'vehicle_reservation_update' || input.name === 'dh_year') {
                        return;
                    }
                    if (input.type === 'hidden') {
                        return;
                    }
                    if (input.id === 'vehicleEditPassengerCount') {
                        input.setAttribute('readonly', 'readonly');
                        return;
                    }
                    input.disabled = !editable;
                });

                if (statusNote) {
                    statusNote.textContent = editable
                        ? 'แก้ไขได้เฉพาะรายการที่รออนุมัติ'
                        : 'รายการนี้ไม่สามารถแก้ไขได้';
                }
            }

            function resetModal() {
                currentBooking = null;
                pendingEditFiles = [];
                syncEditAttachmentInput();
                renderNewAttachments();
                if (fieldMap.bookingId) fieldMap.bookingId.value = '';
                if (fieldMap.department) fieldMap.department.value = '';
                if (fieldMap.writeDate) fieldMap.writeDate.value = '';
                if (fieldMap.purpose) fieldMap.purpose.value = '';
                if (fieldMap.location) fieldMap.location.value = '';
                if (fieldMap.passengerCount) fieldMap.passengerCount.value = '1';
                if (fieldMap.startDate) fieldMap.startDate.value = '';
                if (fieldMap.endDate) fieldMap.endDate.value = '';
                if (fieldMap.startTime) fieldMap.startTime.value = '';
                if (fieldMap.endTime) fieldMap.endTime.value = '';
                if (fieldMap.fuelRadios) {
                    fieldMap.fuelRadios.forEach((radio) => {
                        radio.checked = false;
                    });
                }
                if (fieldMap.companionList) {
                    fieldMap.companionList.querySelectorAll('input[type="checkbox"]').forEach((box) => {
                        box.checked = false;
                    });
                }
                if (fieldMap.companionSearch) {
                    fieldMap.companionSearch.value = '';
                }
                if (fieldMap.companionList) {
                    fieldMap.companionList.classList.remove('show');
                }
                filterCompanionDropdown('');
                updateCompanionSummary();
                renderExistingAttachments([], false);
                setEditAttachmentError('');
            }

            function fillModal(data) {
                if (!data || !form) return;
                currentBooking = data;

                if (fieldMap.bookingId) fieldMap.bookingId.value = String(data.id || '');
                if (fieldMap.department) fieldMap.department.value = data.department || '';
                if (fieldMap.writeDate) fieldMap.writeDate.value = data.writeDate || '';
                if (fieldMap.purpose) fieldMap.purpose.value = data.purpose || '';
                if (fieldMap.location) fieldMap.location.value = data.location || '';

                const startAt = data.startAt || '';
                const endAt = data.endAt || '';
                if (fieldMap.startDate) fieldMap.startDate.value = startAt ? startAt.split(' ')[0] : '';
                if (fieldMap.endDate) fieldMap.endDate.value = endAt ? endAt.split(' ')[0] : '';
                if (fieldMap.startTime) fieldMap.startTime.value = startAt ? startAt.split(' ')[1].slice(0, 5) : '';
                if (fieldMap.endTime) fieldMap.endTime.value = endAt ? endAt.split(' ')[1].slice(0, 5) : '';

                const passengerValue = data.passengerCount || Math.max(1, (data.companionIds || []).length + 1);
                if (fieldMap.passengerCount) fieldMap.passengerCount.value = String(passengerValue);

                if (fieldMap.fuelRadios) {
                    fieldMap.fuelRadios.forEach((radio) => {
                        radio.checked = radio.value === (data.fuelSource || 'central');
                    });
                }

                if (fieldMap.companionList) {
                    const selectedSet = new Set(data.companionIds || []);
                    fieldMap.companionList.querySelectorAll('input[type="checkbox"]').forEach((box) => {
                        box.checked = selectedSet.has(box.value);
                    });
                }
                if (fieldMap.companionSearch) {
                    fieldMap.companionSearch.value = '';
                }
                filterCompanionDropdown('');
                updateCompanionSummary();

                if (statusPill) {
                    statusPill.textContent = data.statusLabel || 'รออนุมัติ';
                    statusPill.className = `status-pill ${data.statusClass || 'pending'}`;
                }

                if (createdValue) {
                    createdValue.textContent = data.createdAtLabel || data.createdAt || '-';
                }
                if (updatedValue) {
                    updatedValue.textContent = data.updatedAtLabel || data.updatedAt || '-';
                }

                const editable = data.status === 'PENDING';
                setEditable(editable);
                renderExistingAttachments(Array.isArray(data.attachments) ? data.attachments : [], editable);
                pendingEditFiles = [];
                syncEditAttachmentInput();
                renderNewAttachments();
            }

            function updatePassengerCountFromCompanions() {
                if (!fieldMap.companionList || !fieldMap.passengerCount) return;
                const selected = fieldMap.companionList.querySelectorAll('input[type="checkbox"]:checked').length;
                fieldMap.passengerCount.value = String(selected + 1);
                updateCompanionSummary();
            }

            if (fieldMap.companionList) {
                fieldMap.companionList.addEventListener('change', function (event) {
                    if (event.target && event.target.matches('input[type="checkbox"]')) {
                        updatePassengerCountFromCompanions();
                    }
                });
            }

            if (fieldMap.companionSearch && fieldMap.companionList) {
                fieldMap.companionSearch.addEventListener('focus', function () {
                    if (this.value.startsWith('จำนวน')) {
                        this.value = '';
                    }
                    openCompanionDropdown();
                    filterCompanionDropdown(this.value);
                });

                fieldMap.companionSearch.addEventListener('click', function () {
                    openCompanionDropdown();
                });

                fieldMap.companionSearch.addEventListener('input', function () {
                    filterCompanionDropdown(this.value);
                });
            }

            if (editAttachmentsInput) {
                editAttachmentsInput.addEventListener('change', function () {
                    addNewAttachments(this.files);
                });
            }

            openButtons.forEach((button) => {
                button.addEventListener('click', function () {
                    const bookingId = parseInt(button.dataset.vehicleBookingId || '0', 10);
                    const data = bookingMap[bookingId];
                    if (!data || !modal) return;
                    resetModal();
                    fillModal(data);
                    modal.classList.remove('hidden');
                });
            });

            closeButtons.forEach((button) => {
                button.addEventListener('click', function () {
                    const targetId = button.getAttribute('data-vehicle-modal-close');
                    if (!targetId) return;
                    const targetModal = document.getElementById(targetId);
                    if (targetModal) {
                        targetModal.classList.add('hidden');
                    }
                });
            });

            document.addEventListener('click', function (event) {
                if (!fieldMap.companionList || !fieldMap.companionSearch) return;
                const dropdownWrapper = fieldMap.companionSearch.closest('.go-with-dropdown');
                if (dropdownWrapper && !dropdownWrapper.contains(event.target)) {
                    closeCompanionDropdown();
                }
            });

            if (modal) {
                modal.addEventListener('click', function (event) {
                    if (event.target === modal) {
                        modal.classList.add('hidden');
                    }
                });
            }
        });
    </script>
</body>

</html>

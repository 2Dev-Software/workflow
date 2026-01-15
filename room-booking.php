<?php
require_once __DIR__ . '/src/Services/auth/auth-guard.php';
require_once __DIR__ . '/src/Services/security/security-service.php';
require_once __DIR__ . '/src/Services/system/system-year.php';
require_once __DIR__ . '/src/Services/teacher/teacher-profile.php';

$teacher_name = (string) ($teacher['fName'] ?? '');
$current_pid = (string) ($_SESSION['pID'] ?? '');
$currentThaiYear = (int) date('Y') + 543;
$dh_year_value = (int) ($dh_year !== '' ? $dh_year : $currentThaiYear);
if ($dh_year_value < 2500) {
    $dh_year_value = $currentThaiYear;
}
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

$format_thai_date_range = static function (string $start, string $end) use ($format_thai_date): string {
    if ($end === '' || $start === $end) {
        return $format_thai_date($start);
    }

    return $format_thai_date($start) . ' - ' . $format_thai_date($end);
};

$format_thai_datetime = static function (string $datetime) use ($thai_months): string {
    if ($datetime === '') {
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

$status_labels = [
    0 => ['label' => 'รออนุมัติ', 'class' => 'pending'],
    1 => ['label' => 'อนุมัติ', 'class' => 'approved'],
    2 => ['label' => 'ไม่อนุมัติ', 'class' => 'rejected'],
];

$room_booking_year = $dh_year_value;
require_once __DIR__ . '/src/Services/room/room-booking-save.php';
require_once __DIR__ . '/src/Services/room/room-booking-data.php';

$booking_alert = $_SESSION['room_booking_alert'] ?? null;
unset($_SESSION['room_booking_alert']);
?>
<!DOCTYPE html>
<html lang="th">
<?php require_once __DIR__ . '/public/components/x-head.php'; ?>

<body data-calendar-mode="room" data-calendar-thai-year="true">

    <?php require_once __DIR__ . '/public/components/layout/preloader.php'; ?>
    <?php if (!empty($booking_alert)) : ?>
        <?php $alert = $booking_alert; ?>
        <?php require __DIR__ . '/public/components/x-alert.php'; ?>
    <?php endif; ?>

    <?php require_once __DIR__ . '/public/components/partials/x-sidebar.php'; ?>

    <section class="main-section">

        <?php require_once __DIR__ . '/public/components/partials/x-navigation.php'; ?>

        <main class="content-wrapper">

            <div class="content-header">
                <h1>ยินดีต้อนรับ</h1>
                <p>จองสถานที่/ห้อง</p>
            </div>

            <div class="content-area booking-page">
                <div class="booking-layout">
                    <section class="booking-card booking-form-card">
                        <div class="booking-card-header">
                            <div class="booking-card-title-group">
                                <h2 class="booking-card-title">สร้างรายการจอง</h2>
                                <p class="booking-card-subtitle">กรอกข้อมูลให้ครบถ้วนเพื่อส่งคำขอจองห้อง</p>
                            </div>
                            <span class="booking-status-tag">เปิดให้จอง</span>
                        </div>

                        <form class="booking-form" method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="room_booking_save" value="1">
                            <input type="hidden" name="dh_year" value="<?= htmlspecialchars((string) $dh_year_value, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="requesterPID" value="<?= htmlspecialchars($_SESSION['pID'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="status" value="0">
                            <input type="hidden" name="statusReason" value="">

                            <div class="booking-form-grid">
                                <div class="form-group">
                                    <label class="form-label" for="bookingRoom">ห้อง/สถานที่</label>
                                    <select class="form-input" id="bookingRoom" name="roomID" required>
                                        <option value="" disabled selected>เลือกห้องหรือสถานที่</option>
                                        <?php foreach ($room_booking_rooms as $room_id => $room_label) : ?>
                                            <option value="<?= htmlspecialchars($room_id, ENT_QUOTES, 'UTF-8') ?>">
                                                <?= htmlspecialchars($room_label, ENT_QUOTES, 'UTF-8') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="bookingStartDate">วันที่เริ่มใช้</label>
                                    <input class="form-input" type="date" id="bookingStartDate" name="startDate" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="bookingEndDate">วันที่สิ้นสุด</label>
                                    <input class="form-input" type="date" id="bookingEndDate" name="endDate">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">ช่วงเวลา</label>
                                    <div class="booking-time-range">
                                        <input class="form-input" type="time" name="startTime" required>
                                        <span>ถึง</span>
                                        <input class="form-input" type="time" name="endTime" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="bookingCapacity">จำนวนผู้เข้าร่วม</label>
                                    <input class="form-input" type="number" id="bookingCapacity" name="attendeeCount" min="1" placeholder="ระบุจำนวนคน">
                                </div>
                                <div class="form-group full">
                                    <label class="form-label" for="bookingTopic">หัวข้อการจอง</label>
                                    <input class="form-input" type="text" id="bookingTopic" name="bookingTopic" placeholder="เช่น ประชุมกลุ่มสาระ/อบรม">
                                </div>
                                <div class="form-group full">
                                    <label class="form-label" for="bookingDetail">รายละเอียด/วัตถุประสงค์</label>
                                    <textarea class="form-input booking-textarea" id="bookingDetail" name="bookingDetail" rows="4" placeholder="ระบุรายละเอียดการใช้งาน"></textarea>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="bookingEquipment">อุปกรณ์ที่ต้องการ</label>
                                    <textarea class="form-input booking-textarea" id="bookingEquipment" name="equipmentDetail" rows="3" placeholder="โปรเจคเตอร์, ไมโครโฟน"></textarea>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="bookingOwner">ผู้ประสานงาน</label>
                                    <input class="form-input" type="text" id="bookingOwner" name="requesterDisplayName" value="<?= htmlspecialchars($teacher_name, ENT_QUOTES, 'UTF-8') ?>" readonly>
                                </div>
                            </div>

                            <div class="booking-actions">
                                <button type="button" class="btn-outline">ตรวจสอบเวลาว่าง</button>
                                <button type="button" class="btn-confirm">บันทึกการจอง</button>
                            </div>

                            <p class="booking-note">* ระบบจะส่งคำขอให้ผู้ดูแลพิจารณาอนุมัติ</p>
                        </form>
                    </section>

                    <section class="booking-card booking-calendar-card">
                        <div class="booking-card-header">
                            <div class="booking-card-title-group">
                                <h2 class="booking-card-title">ปฏิทินการจอง</h2>
                                <p class="booking-card-subtitle">กดวันที่เพื่อดูรายละเอียดการจอง</p>
                                </div>
                                <div class="booking-legend">
                                    <span class="legend-item"><span class="legend-dot available"></span> ว่าง</span>
                                    <span class="legend-item"><span class="legend-dot booked"></span> มีการจอง</span>
                                    <span class="legend-item"><span class="legend-dot pending"></span> รออนุมัติ</span>
                                </div>
                            </div>

                            <div class="booking-calendar">
                                <div class="container-calendar">
                                    <div class="calendar">
                                        <div class="header-calendar">
                                            <div class="month-year" id="month-year"></div>
                                            <div class="interact-button-calendar">
                                                <button id="prev-btn" type="button">
                                                    <i class="fa-solid fa-chevron-left"></i>
                                                </button>
                                                <button id="next-btn" type="button">
                                                    <i class="fa-solid fa-chevron-right"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="days-calendar">
                                            <div class="day">อา</div>
                                            <div class="day">จ</div>
                                            <div class="day">อ</div>
                                            <div class="day">พ</div>
                                            <div class="day">พฤ</div>
                                            <div class="day">ศ</div>
                                            <div class="day">ส</div>
                                        </div>
                                        <div class="dates-calendar" id="dates-calendar"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="booking-summary">
                                <div class="booking-summary-item">
                                    <h3><?= htmlspecialchars((string) $room_booking_total, ENT_QUOTES, 'UTF-8') ?> รายการ</h3>
                                    <p>รายการจองทั้งหมด</p>
                                </div>
                                <div class="booking-summary-item">
                                    <h3><?= htmlspecialchars((string) $room_booking_approved_total, ENT_QUOTES, 'UTF-8') ?> รายการ</h3>
                                    <p>อนุมัติแล้ว</p>
                                </div>
                                <div class="booking-summary-item">
                                    <h3><?= htmlspecialchars((string) $room_booking_pending_total, ENT_QUOTES, 'UTF-8') ?> รายการ</h3>
                                    <p>รออนุมัติ</p>
                                </div>
                            </div>
                        </section>
                </div>

                <section class="booking-card booking-list-card booking-list-row">
                    <div class="booking-card-header">
                        <div class="booking-card-title-group">
                            <h2 class="booking-card-title">รายการจองของฉัน</h2>
                            <p class="booking-card-subtitle"><?= htmlspecialchars($my_booking_subtitle, ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                        <button class="btn-link" type="button" data-booking-modal-open="bookingListModal">ดูทั้งหมด</button>
                    </div>

                    <div class="table-responsive">
                        <table class="custom-table booking-table">
                            <thead>
                                <tr>
                                    <th>ห้อง</th>
                                    <th>วันที่ใช้</th>
                                    <th>เวลา</th>
                                    <th>รายการ</th>
                                    <th>จำนวน</th>
                                    <th>สถานะ</th>
                                    <th>จัดการ</th>
                                </tr>
                            </thead>
                            <tbody data-empty-message="ยังไม่มีรายการจองของคุณ">
                                <?php if (empty($my_bookings_latest)) : ?>
                                    <tr>
                                        <td colspan="7" class="booking-empty">ยังไม่มีรายการจองของคุณ</td>
                                    </tr>
                                <?php else : ?>
                                    <?php foreach ($my_bookings_latest as $booking_item) : ?>
                                        <?php
                                        $status_value = (int) ($booking_item['status'] ?? 0);
                                        $status_label = $status_labels[$status_value]['label'] ?? $status_labels[0]['label'];
                                        $status_class = $status_labels[$status_value]['class'] ?? $status_labels[0]['class'];
                                        $detail_text = trim((string) ($booking_item['bookingDetail'] ?? ''));
                                        $detail_text = $detail_text !== '' ? $detail_text : 'ไม่มีรายละเอียดเพิ่มเติม';
                                        $status_reason_value = trim((string) ($booking_item['statusReason'] ?? ''));
                                        if ($status_value === 2 && $status_reason_value === '') {
                                            $status_reason_value = 'ไม่ระบุเหตุผล';
                                        }
                                        $status_reason_label = $status_value === 2 ? $status_reason_value : '-';
                                        $approver_name = trim((string) ($booking_item['approvedByName'] ?? ''));
                                        if ($approver_name === '' && !empty($booking_item['approvedByPID'])) {
                                            $approver_name = 'เจ้าหน้าที่ระบบ';
                                        }
                                        $approval_label = $status_value === 2 ? 'ผู้ไม่อนุมัติ' : 'ผู้อนุมัติ';
                                        $approval_name = $status_value === 0 ? 'รอการอนุมัติ' : ($approver_name !== '' ? $approver_name : 'เจ้าหน้าที่ระบบ');
                                        $approval_time = $format_thai_datetime((string) ($booking_item['approvedAt'] ?? ''));
                                        if ($approval_time === '-' || $approval_time === '') {
                                            $approval_at_label = '-';
                                        } else {
                                            $approval_at_label = ($status_value === 2 ? 'ไม่อนุมัติเมื่อ ' : 'อนุมัติเมื่อ ') . $approval_time;
                                        }
                                        $date_range = $format_thai_date_range(
                                            (string) ($booking_item['startDate'] ?? ''),
                                            (string) ($booking_item['endDate'] ?? '')
                                        );
                                        $time_range = trim((string) ($booking_item['startTime'] ?? '') . '-' . (string) ($booking_item['endTime'] ?? ''));
                                        $created_label = $format_thai_datetime((string) ($booking_item['createdAt'] ?? ''));
                                        $updated_label = $format_thai_datetime((string) ($booking_item['updatedAt'] ?? ''));
                                        ?>
                                        <tr>
                                            <td><?= htmlspecialchars($booking_item['roomName'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($date_range, ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($time_range, ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($booking_item['bookingTopic'] ?? 'ประชุม/อบรม', ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars((string) ($booking_item['attendeeCount'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td>
                                                <span class="status-pill <?= htmlspecialchars($status_class, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($status_label, ENT_QUOTES, 'UTF-8') ?></span>
                                                <?php if ($status_value === 2 && !empty($booking_item['statusReason'])) : ?>
                                                    <div class="status-reason">เหตุผล: <?= htmlspecialchars($booking_item['statusReason'], ENT_QUOTES, 'UTF-8') ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="booking-action-cell">
                                                <div class="booking-action-group">
                                                    <button
                                                        type="button"
                                                        class="booking-action-btn secondary"
                                                        data-booking-action="detail"
                                                        data-booking-id="<?= htmlspecialchars((string) ($booking_item['roomBookingID'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                        data-booking-room="<?= htmlspecialchars($booking_item['roomName'] ?? '-', ENT_QUOTES, 'UTF-8') ?>"
                                                        data-booking-date="<?= htmlspecialchars($date_range, ENT_QUOTES, 'UTF-8') ?>"
                                                        data-booking-time="<?= htmlspecialchars($time_range, ENT_QUOTES, 'UTF-8') ?>"
                                                        data-booking-topic="<?= htmlspecialchars($booking_item['bookingTopic'] ?? 'ประชุม/อบรม', ENT_QUOTES, 'UTF-8') ?>"
                                                        data-booking-detail="<?= htmlspecialchars($detail_text, ENT_QUOTES, 'UTF-8') ?>"
                                                        data-booking-attendees="<?= htmlspecialchars((string) ($booking_item['attendeeCount'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>"
                                                        data-booking-status="<?= htmlspecialchars((string) $status_value, ENT_QUOTES, 'UTF-8') ?>"
                                                        data-booking-status-label="<?= htmlspecialchars($status_label, ENT_QUOTES, 'UTF-8') ?>"
                                                        data-booking-status-class="<?= htmlspecialchars($status_class, ENT_QUOTES, 'UTF-8') ?>"
                                                        data-booking-status-reason="<?= htmlspecialchars($status_reason_label, ENT_QUOTES, 'UTF-8') ?>"
                                                        data-booking-approval-label="<?= htmlspecialchars($approval_label, ENT_QUOTES, 'UTF-8') ?>"
                                                        data-booking-approval-name="<?= htmlspecialchars($approval_name, ENT_QUOTES, 'UTF-8') ?>"
                                                        data-booking-approval-at="<?= htmlspecialchars($approval_at_label, ENT_QUOTES, 'UTF-8') ?>"
                                                        data-booking-created="<?= htmlspecialchars($created_label, ENT_QUOTES, 'UTF-8') ?>"
                                                        data-booking-updated="<?= htmlspecialchars($updated_label, ENT_QUOTES, 'UTF-8') ?>">
                                                        ดูรายละเอียด
                                                    </button>
                                                    <button
                                                        type="button"
                                                        class="booking-action-btn danger"
                                                        data-booking-action="delete"
                                                        data-booking-id="<?= htmlspecialchars((string) ($booking_item['roomBookingID'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                                        ลบ
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

        </main>

        <?php require_once __DIR__ . '/public/components/partials/x-footer.php'; ?>

    </section>

    <div id="bookingListModal" class="modal-overlay hidden">
        <div class="modal-content booking-modal">
            <header class="modal-header">
                <div class="modal-title">
                    <i class="fa-solid fa-list-check"></i>
                    <span>รายการจองของฉันทั้งหมด</span>
                </div>
                <div class="close-modal-btn" data-booking-modal-close="bookingListModal">
                    <i class="fa-solid fa-xmark"></i>
                </div>
            </header>
            <div class="modal-body booking-modal-body">
                <div class="table-responsive">
                    <table class="custom-table booking-table">
                        <thead>
                            <tr>
                                <th>ห้อง</th>
                                <th>วันที่ใช้</th>
                                <th>เวลา</th>
                                <th>รายการ</th>
                                <th>จำนวน</th>
                                <th>สถานะ</th>
                                <th>จัดการ</th>
                            </tr>
                        </thead>
                        <tbody data-empty-message="ยังไม่มีรายการจอง">
                            <?php if (empty($my_bookings_sorted)) : ?>
                                <tr>
                                    <td colspan="7" class="booking-empty">ยังไม่มีรายการจอง</td>
                                </tr>
                            <?php else : ?>
                                <?php foreach ($my_bookings_sorted as $booking_item) : ?>
                                    <?php
                                    $status_value = (int) ($booking_item['status'] ?? 0);
                                    $status_label = $status_labels[$status_value]['label'] ?? $status_labels[0]['label'];
                                    $status_class = $status_labels[$status_value]['class'] ?? $status_labels[0]['class'];
                                    $detail_text = trim((string) ($booking_item['bookingDetail'] ?? ''));
                                    $detail_text = $detail_text !== '' ? $detail_text : 'ไม่มีรายละเอียดเพิ่มเติม';
                                    $status_reason_value = trim((string) ($booking_item['statusReason'] ?? ''));
                                    if ($status_value === 2 && $status_reason_value === '') {
                                        $status_reason_value = 'ไม่ระบุเหตุผล';
                                    }
                                    $status_reason_label = $status_value === 2 ? $status_reason_value : '-';
                                    $approver_name = trim((string) ($booking_item['approvedByName'] ?? ''));
                                    if ($approver_name === '' && !empty($booking_item['approvedByPID'])) {
                                        $approver_name = 'เจ้าหน้าที่ระบบ';
                                    }
                                    $approval_label = $status_value === 2 ? 'ผู้ไม่อนุมัติ' : 'ผู้อนุมัติ';
                                    $approval_name = $status_value === 0 ? 'รอการอนุมัติ' : ($approver_name !== '' ? $approver_name : 'เจ้าหน้าที่ระบบ');
                                    $approval_time = $format_thai_datetime((string) ($booking_item['approvedAt'] ?? ''));
                                    if ($approval_time === '-' || $approval_time === '') {
                                        $approval_at_label = '-';
                                    } else {
                                        $approval_at_label = ($status_value === 2 ? 'ไม่อนุมัติเมื่อ ' : 'อนุมัติเมื่อ ') . $approval_time;
                                    }
                                    $date_range = $format_thai_date_range(
                                        (string) ($booking_item['startDate'] ?? ''),
                                        (string) ($booking_item['endDate'] ?? '')
                                    );
                                    $time_range = trim((string) ($booking_item['startTime'] ?? '') . '-' . (string) ($booking_item['endTime'] ?? ''));
                                    $created_label = $format_thai_datetime((string) ($booking_item['createdAt'] ?? ''));
                                    $updated_label = $format_thai_datetime((string) ($booking_item['updatedAt'] ?? ''));
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($booking_item['roomName'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($date_range, ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($time_range, ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($booking_item['bookingTopic'] ?? 'ประชุม/อบรม', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string) ($booking_item['attendeeCount'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td>
                                            <span class="status-pill <?= htmlspecialchars($status_class, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($status_label, ENT_QUOTES, 'UTF-8') ?></span>
                                            <?php if ($status_value === 2 && !empty($booking_item['statusReason'])) : ?>
                                                <div class="status-reason">เหตุผล: <?= htmlspecialchars($booking_item['statusReason'], ENT_QUOTES, 'UTF-8') ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="booking-action-cell">
                                            <div class="booking-action-group">
                                                <button
                                                    type="button"
                                                    class="booking-action-btn secondary"
                                                    data-booking-action="detail"
                                                    data-booking-id="<?= htmlspecialchars((string) ($booking_item['roomBookingID'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                    data-booking-room="<?= htmlspecialchars($booking_item['roomName'] ?? '-', ENT_QUOTES, 'UTF-8') ?>"
                                                    data-booking-date="<?= htmlspecialchars($date_range, ENT_QUOTES, 'UTF-8') ?>"
                                                    data-booking-time="<?= htmlspecialchars($time_range, ENT_QUOTES, 'UTF-8') ?>"
                                                    data-booking-topic="<?= htmlspecialchars($booking_item['bookingTopic'] ?? 'ประชุม/อบรม', ENT_QUOTES, 'UTF-8') ?>"
                                                    data-booking-detail="<?= htmlspecialchars($detail_text, ENT_QUOTES, 'UTF-8') ?>"
                                                    data-booking-attendees="<?= htmlspecialchars((string) ($booking_item['attendeeCount'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>"
                                                    data-booking-status="<?= htmlspecialchars((string) $status_value, ENT_QUOTES, 'UTF-8') ?>"
                                                    data-booking-status-label="<?= htmlspecialchars($status_label, ENT_QUOTES, 'UTF-8') ?>"
                                                    data-booking-status-class="<?= htmlspecialchars($status_class, ENT_QUOTES, 'UTF-8') ?>"
                                                    data-booking-status-reason="<?= htmlspecialchars($status_reason_label, ENT_QUOTES, 'UTF-8') ?>"
                                                    data-booking-approval-label="<?= htmlspecialchars($approval_label, ENT_QUOTES, 'UTF-8') ?>"
                                                    data-booking-approval-name="<?= htmlspecialchars($approval_name, ENT_QUOTES, 'UTF-8') ?>"
                                                    data-booking-approval-at="<?= htmlspecialchars($approval_at_label, ENT_QUOTES, 'UTF-8') ?>"
                                                    data-booking-created="<?= htmlspecialchars($created_label, ENT_QUOTES, 'UTF-8') ?>"
                                                    data-booking-updated="<?= htmlspecialchars($updated_label, ENT_QUOTES, 'UTF-8') ?>">
                                                    ดูรายละเอียด
                                                </button>
                                                <button
                                                    type="button"
                                                    class="booking-action-btn danger"
                                                    data-booking-action="delete"
                                                    data-booking-id="<?= htmlspecialchars((string) ($booking_item['roomBookingID'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                                    ลบ
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div id="bookingDetailModal" class="modal-overlay hidden">
        <div class="modal-content booking-detail-modal">
            <header class="modal-header">
                <div class="modal-title">
                    <i class="fa-solid fa-calendar-check"></i>
                    <span>รายละเอียดการจอง</span>
                </div>
                <div class="close-modal-btn" data-booking-modal-close="bookingDetailModal">
                    <i class="fa-solid fa-xmark"></i>
                </div>
            </header>
            <div class="modal-body booking-detail-body">
                <div class="booking-detail-grid">
                    <div class="detail-item">
                        <p class="detail-label">ห้อง/สถานที่</p>
                        <p class="detail-value" data-booking-detail="room">-</p>
                    </div>
                    <div class="detail-item">
                        <p class="detail-label">วันที่ใช้</p>
                        <p class="detail-value" data-booking-detail="date">-</p>
                    </div>
                    <div class="detail-item">
                        <p class="detail-label">เวลา</p>
                        <p class="detail-value" data-booking-detail="time">-</p>
                    </div>
                    <div class="detail-item detail-half">
                        <p class="detail-label">จำนวนผู้เข้าร่วม</p>
                        <p class="detail-value" data-booking-detail="attendees">-</p>
                    </div>
                    <div class="detail-item detail-status-item detail-half">
                        <p class="detail-label">สถานะ</p>
                        <span class="status-pill" data-booking-detail="status">-</span>
                    </div>
                    <div class="detail-item detail-approval-item detail-full" data-booking-detail="approval-item">
                        <p class="detail-label" data-booking-detail="approval-label">ผู้อนุมัติ</p>
                        <p class="detail-value" data-booking-detail="approval-name">-</p>
                        <span class="detail-subtext" data-booking-detail="approval-at">-</span>
                    </div>
                </div>

                <div class="booking-detail-section">
                    <h4>หัวข้อการจอง</h4>
                    <p data-booking-detail="topic">-</p>
                </div>

                <div class="booking-detail-section">
                    <h4>รายละเอียด/วัตถุประสงค์</h4>
                    <p data-booking-detail="detail">-</p>
                </div>

                <div class="booking-detail-section detail-reason-section hidden" data-booking-detail="reason-row">
                    <h4>เหตุผลการไม่อนุมัติ</h4>
                    <p data-booking-detail="reason">-</p>
                </div>

                <div class="booking-detail-meta">
                    <div class="detail-meta-item">
                        <span>สร้างรายการ</span>
                        <strong data-booking-detail="created">-</strong>
                    </div>
                    <div class="detail-meta-item">
                        <span>อัปเดตล่าสุด</span>
                        <strong data-booking-detail="updated">-</strong>
                    </div>
                </div>

                <div class="booking-detail-actions">
                    <button type="button" class="booking-action-btn secondary" data-booking-modal-close="bookingDetailModal">ปิดหน้าต่าง</button>
                </div>
            </div>
        </div>
    </div>

    <div id="bookingDeleteModal" class="alert-overlay hidden">
        <div class="alert-box danger booking-delete-alert">
            <div class="alert-header">
                <div class="icon-circle"><i class="fa-solid fa-trash-can"></i></div>
            </div>
            <div class="alert-body">
                <h1>ยืนยันการลบรายการจอง</h1>
                <p>ต้องการลบรายการจองนี้ใช่หรือไม่</p>
                <div class="alert-actions">
                    <button type="button" class="btn-close-alert" data-booking-delete-confirm="true">ลบรายการ</button>
                    <button type="button" class="btn-close-alert btn-cancel-alert" data-booking-delete-cancel="true">ยกเลิก</button>
                </div>
            </div>
        </div>
    </div>

    <div id="event-modal-overlay" class="modal-overlay hidden">
        <div class="modal-content">
            <header class="modal-header">
                <div class="modal-title">
                    <i class="fa-regular fa-calendar-days"></i>
                    <span id="modal-date-title">วันที่ ...</span>
                </div>
                <div class="close-modal-btn">
                    <i class="fa-solid fa-xmark" id="close-modal-btn"></i>
                </div>
            </header>

            <div class="modal-body">
                <div id="room-booking-section" class="booking-section">
                    <h4 class="section-title">ตารางการจองห้องประชุม</h4>
                    <div class="table-responsive">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>ห้อง</th>
                                    <th>เวลา</th>
                                    <th>รายการประชุม</th>
                                    <th>จำนวน</th>
                                    <th>ผู้จองห้อง</th>
                                </tr>
                            </thead>
                            <tbody id="room-table-body">
                            </tbody>
                        </table>
                    </div>
                </div>

                <div id="car-booking-section" class="booking-section">
                    <h4 class="section-title">ตารางการจองรถยนต์</h4>
                    <div class="table-responsive">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>ทะเบียนรถ</th>
                                    <th>เวลา</th>
                                    <th>รายละเอียด</th>
                                    <th>ผู้จองรถ</th>
                                </tr>
                            </thead>
                            <tbody id="car-table-body">
                            </tbody>
                        </table>
                    </div>
                </div>

                <div id="no-event-message" class="hidden">
                    ไม่มีรายการจองห้องในวันนี้
                </div>
            </div>
        </div>
    </div>

    <script>
        window.roomBookingEvents = <?= json_encode($room_booking_events, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        window.roomBookingDeleteEndpoint = "public/api/room-booking-delete.php";
        window.roomBookingCsrfToken = <?= json_encode($_SESSION['csrf_token'] ?? '', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    </script>

    <?php require_once __DIR__ . '/public/components/x-scripts.php'; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const bookingModal = document.getElementById('bookingListModal');
            const detailModal = document.getElementById('bookingDetailModal');
            const deleteModal = document.getElementById('bookingDeleteModal');
            const openButtons = document.querySelectorAll('[data-booking-modal-open]');
            const closeButtons = document.querySelectorAll('[data-booking-modal-close]');
            const detailButtons = document.querySelectorAll('[data-booking-action="detail"]');
            const deleteButtons = document.querySelectorAll('[data-booking-action="delete"]');
            const deleteConfirmButton = document.querySelector('[data-booking-delete-confirm="true"]');
            const deleteCancelButton = document.querySelector('[data-booking-delete-cancel="true"]');
            let pendingDeleteRows = [];
            let pendingDeleteId = '';
            const deleteEndpoint = window.roomBookingDeleteEndpoint || '';
            const csrfToken = window.roomBookingCsrfToken || '';

            const showBookingAlert = function (type, title, message) {
                const iconMap = {
                    success: 'fa-check',
                    warning: 'fa-triangle-exclamation',
                    danger: 'fa-xmark',
                };
                const alertType = iconMap[type] ? type : 'danger';
                const icon = iconMap[alertType] || 'fa-xmark';
                const overlay = document.createElement('div');
                overlay.className = 'alert-overlay';
                overlay.innerHTML =
                    '<div class="alert-box ' + alertType + '">' +
                    '<div class="alert-header"><div class="icon-circle"><i class="fa-solid ' + icon + '"></i></div></div>' +
                    '<div class="alert-body">' +
                    '<h1>' + title + '</h1>' +
                    (message ? '<p>' + message + '</p>' : '') +
                    '<button type="button" class="btn-close-alert" data-alert-close="true">ยืนยัน</button>' +
                    '</div></div>';
                document.body.appendChild(overlay);
                const closeBtn = overlay.querySelector('[data-alert-close="true"]');
                if (closeBtn) {
                    closeBtn.addEventListener('click', function () {
                        overlay.remove();
                    });
                }
            };

            const detailFields = detailModal
                ? {
                    room: detailModal.querySelector('[data-booking-detail="room"]'),
                    date: detailModal.querySelector('[data-booking-detail="date"]'),
                    time: detailModal.querySelector('[data-booking-detail="time"]'),
                    attendees: detailModal.querySelector('[data-booking-detail="attendees"]'),
                    topic: detailModal.querySelector('[data-booking-detail="topic"]'),
                    detail: detailModal.querySelector('[data-booking-detail="detail"]'),
                    status: detailModal.querySelector('[data-booking-detail="status"]'),
                    reason: detailModal.querySelector('[data-booking-detail="reason"]'),
                    reasonRow: detailModal.querySelector('[data-booking-detail="reason-row"]'),
                    approvalLabel: detailModal.querySelector('[data-booking-detail="approval-label"]'),
                    approvalName: detailModal.querySelector('[data-booking-detail="approval-name"]'),
                    approvalAt: detailModal.querySelector('[data-booking-detail="approval-at"]'),
                    approvalItem: detailModal.querySelector('[data-booking-detail="approval-item"]'),
                    created: detailModal.querySelector('[data-booking-detail="created"]'),
                    updated: detailModal.querySelector('[data-booking-detail="updated"]'),
                }
                : null;

            const setDetailValue = function (node, value, fallback = '-') {
                if (!node) return;
                const text = (value || '').toString().trim();
                node.textContent = text !== '' ? text : fallback;
            };

            openButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    const targetId = button.getAttribute('data-booking-modal-open');
                    if (!targetId) return;
                    const targetModal = document.getElementById(targetId);
                    if (targetModal) {
                        targetModal.classList.remove('hidden');
                    }
                });
            });

            closeButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    const targetId = button.getAttribute('data-booking-modal-close');
                    if (!targetId) return;
                    const targetModal = document.getElementById(targetId);
                    if (targetModal) {
                        targetModal.classList.add('hidden');
                    }
                });
            });

            detailButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    if (!detailModal || !detailFields) return;
                    const dataset = button.dataset || {};
                    const statusLabel = dataset.bookingStatusLabel || '-';
                    const statusClass = dataset.bookingStatusClass || 'pending';
                    const reasonValue = dataset.bookingStatus === '2'
                        ? (dataset.bookingStatusReason || 'ไม่ระบุเหตุผล')
                        : '-';
                    const approvalLabel = dataset.bookingApprovalLabel || 'ผู้อนุมัติ';
                    const approvalName = dataset.bookingApprovalName || '-';
                    const approvalAt = dataset.bookingApprovalAt || '-';

                    setDetailValue(detailFields.room, dataset.bookingRoom);
                    setDetailValue(detailFields.date, dataset.bookingDate);
                    setDetailValue(detailFields.time, dataset.bookingTime);
                    const attendeesValue = (dataset.bookingAttendees || '').toString().trim();
                    const attendeesLabel = attendeesValue !== '' && attendeesValue !== '-' ? `${attendeesValue} คน` : '-';
                    setDetailValue(detailFields.attendees, attendeesLabel);
                    setDetailValue(detailFields.topic, dataset.bookingTopic);
                    setDetailValue(detailFields.detail, dataset.bookingDetail, 'ไม่มีรายละเอียดเพิ่มเติม');
                    setDetailValue(detailFields.created, dataset.bookingCreated);
                    setDetailValue(detailFields.updated, dataset.bookingUpdated);
                    setDetailValue(detailFields.reason, reasonValue);
                    setDetailValue(detailFields.approvalLabel, approvalLabel, 'ผู้อนุมัติ');
                    setDetailValue(detailFields.approvalName, approvalName);
                    setDetailValue(detailFields.approvalAt, approvalAt);

                    if (detailFields.status) {
                        detailFields.status.textContent = statusLabel;
                        detailFields.status.classList.remove('approved', 'pending', 'rejected');
                        if (statusClass) {
                            detailFields.status.classList.add(statusClass);
                        }
                    }

                    if (detailFields.reasonRow) {
                        detailFields.reasonRow.classList.toggle('hidden', reasonValue === '-' || reasonValue === '');
                    }

                    if (detailFields.approvalItem) {
                        detailFields.approvalItem.classList.toggle('hidden', dataset.bookingStatus === '0');
                    }

                    if (detailFields.approvalAt) {
                        detailFields.approvalAt.classList.toggle('hidden', approvalAt === '-' || approvalAt === '');
                    }

                    detailModal.classList.remove('hidden');
                });
            });

            deleteButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    const bookingId = button.getAttribute('data-booking-id') || '';
                    if (!bookingId) return;
                    pendingDeleteId = bookingId;
                    pendingDeleteRows = [];
                    deleteButtons.forEach(function (item) {
                        if (item.getAttribute('data-booking-id') === bookingId) {
                            const row = item.closest('tr');
                            if (row) {
                                pendingDeleteRows.push(row);
                            }
                        }
                    });
                    if (deleteModal) {
                        deleteModal.classList.remove('hidden');
                    }
                });
            });

            const closeDeleteModal = function () {
                pendingDeleteRows = [];
                pendingDeleteId = '';
                if (deleteModal) {
                    deleteModal.classList.add('hidden');
                }
            };

            const updateEmptyState = function (tbody) {
                const dataRows = Array.from(tbody.querySelectorAll('tr')).filter(function (row) {
                    return !row.querySelector('.booking-empty');
                });
                if (dataRows.length === 0) {
                    const emptyMessage = tbody.getAttribute('data-empty-message') || 'ไม่พบข้อมูล';
                    const table = tbody.closest('table');
                    const colCount = table ? table.querySelectorAll('thead th').length : 1;
                    tbody.innerHTML = '<tr><td colspan="' + colCount + '" class="booking-empty">' + emptyMessage + '</td></tr>';
                }
            };

            if (deleteConfirmButton) {
                deleteConfirmButton.addEventListener('click', function () {
                    if (!pendingDeleteId) {
                        closeDeleteModal();
                        return;
                    }

                    const payload = {
                        booking_id: pendingDeleteId,
                        csrf_token: csrfToken,
                    };

                    if (!deleteEndpoint) {
                        showBookingAlert('danger', 'ไม่พบปลายทางบริการ', 'กรุณาลองใหม่อีกครั้ง');
                        closeDeleteModal();
                        return;
                    }

                    deleteConfirmButton.disabled = true;
                    deleteConfirmButton.textContent = 'กำลังลบ...';

                    fetch(deleteEndpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': csrfToken,
                        },
                        body: JSON.stringify(payload),
                    })
                        .then(function (response) {
                            return response.json().then(function (data) {
                                if (!response.ok) {
                                    throw new Error(data && data.message ? data.message : 'ลบรายการไม่สำเร็จ');
                                }
                                return data;
                            });
                        })
                        .then(function (data) {
                            pendingDeleteRows.forEach(function (row) {
                                row.remove();
                            });
                            document.querySelectorAll('tbody[data-empty-message]').forEach(updateEmptyState);

                            if (window.roomBookingEvents) {
                                Object.keys(window.roomBookingEvents).forEach(function (key) {
                                    const nextEvents = (window.roomBookingEvents[key] || []).filter(function (eventItem) {
                                        return eventItem.bookingId !== pendingDeleteId;
                                    });
                                    if (nextEvents.length > 0) {
                                        window.roomBookingEvents[key] = nextEvents;
                                    } else {
                                        delete window.roomBookingEvents[key];
                                    }
                                });
                            }

                            if (window.roomBookingCalendar && typeof window.roomBookingCalendar.updateCalendar === 'function') {
                                window.roomBookingCalendar.updateCalendar();
                            }

                            closeDeleteModal();
                        })
                        .catch(function (error) {
                            showBookingAlert('danger', 'ลบรายการไม่สำเร็จ', error.message || 'กรุณาลองใหม่อีกครั้ง');
                            closeDeleteModal();
                        })
                        .finally(function () {
                            deleteConfirmButton.disabled = false;
                            deleteConfirmButton.textContent = 'ลบรายการ';
                        });
                });
            }

            if (deleteCancelButton) {
                deleteCancelButton.addEventListener('click', closeDeleteModal);
            }

            if (bookingModal) {
                bookingModal.addEventListener('click', function (event) {
                    if (event.target === bookingModal) {
                        bookingModal.classList.add('hidden');
                    }
                });
            }

            if (deleteModal) {
                deleteModal.addEventListener('click', function (event) {
                    if (event.target === deleteModal) {
                        closeDeleteModal();
                    }
                });
            }
        });
    </script>
</body>

</html>

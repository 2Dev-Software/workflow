<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../auth/csrf.php';

$teacher_name = (string) ($teacher_name ?? '');
$dh_year_value = (int) ($dh_year_value ?? 0);
$room_booking_room_list = (array) ($room_booking_room_list ?? []);
$room_booking_total = (int) ($room_booking_total ?? 0);
$room_booking_approved_total = (int) ($room_booking_approved_total ?? 0);
$room_booking_pending_total = (int) ($room_booking_pending_total ?? 0);
$my_booking_subtitle = (string) ($my_booking_subtitle ?? 'ยังไม่มีรายการจอง');
$my_bookings_latest = (array) ($my_bookings_latest ?? []);
$my_bookings_sorted = (array) ($my_bookings_sorted ?? []);
$room_booking_events = (array) ($room_booking_events ?? []);
$booking_alert = $booking_alert ?? null;
$alert = $booking_alert;

$currentThaiYear = (int) date('Y') + 543;

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

$room_booking_events_json = json_encode($room_booking_events, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

if ($room_booking_events_json === false) {
    $room_booking_events_json = '{}';
}

$body_attrs = [
    'data-calendar-mode' => 'room',
    'data-calendar-thai-year' => 'true',
];

ob_start();
?>
<div class="content-header">
    <h1>ยินดีต้อนรับ</h1>
    <p>จองสถานที่/ห้อง</p>
</div>

<div class="content-area booking-page" data-room-booking data-delete-endpoint="public/api/room-booking-delete.php"
    data-check-endpoint="public/api/room-booking-check.php" data-csrf="<?= h(csrf_token()) ?>">
    <div class="booking-layout">
        <section class="booking-card booking-form-card">
            <div class="booking-card-header">
                <div class="booking-card-title-group">
                    <h2 class="booking-card-title">สร้างรายการจอง</h2>
                </div>
                <span class="booking-status-tag">เปิดให้จอง</span>
            </div>

            <form class="booking-form" method="POST" action="<?= h($_SERVER['PHP_SELF'] ?? 'room-booking.php') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="dh_year" value="<?= h((string) $dh_year_value) ?>">
                <input type="hidden" name="requesterPID" value="<?= h($_SESSION['pID'] ?? '') ?>">
                <input type="hidden" name="status" value="0">

                <div class="booking-form-grid">
                    <div class="form-group">
                        <label class="form-label" for="bookingRoom">ห้อง/สถานที่</label>
                        <select class="form-input" id="bookingRoom" name="roomID" required>
                            <option value="" disabled selected>เลือกห้องหรือสถานที่</option>
                            <?php foreach ($room_booking_room_list as $room) : ?>
                                <?php
                                $room_id = (string) ($room['roomID'] ?? '');
                                $room_label = (string) ($room['roomName'] ?? $room_id);
                                $room_status = (string) ($room['roomStatus'] ?? '');
                                $room_note = trim((string) ($room['roomNote'] ?? ''));
                                $room_status_label = $room_status !== '' ? $room_status : 'ไม่ระบุสถานะ';
                                $room_available = room_booking_is_room_available($room_status);
                                $room_option_label = $room_available
                                    ? $room_label
                                    : $room_label . ' (' . $room_status_label . ')';
                                $room_option_title = $room_note !== '' ? $room_note : $room_status_label;
                                ?>
                                <option value="<?= h($room_id) ?>" <?= $room_available ? '' : 'disabled' ?>
                                    data-room-status="<?= h($room_status_label) ?>" title="<?= h($room_option_title) ?>">
                                    <?= h($room_option_label) ?>
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
                        <input class="form-input" type="number" id="bookingCapacity" name="attendeeCount" min="1"
                            placeholder="ระบุจำนวนคน">
                    </div>
                    <div class="form-group full">
                        <label class="form-label" for="bookingTopic">หัวข้อการจอง</label>
                        <input class="form-input" type="text" id="bookingTopic" name="bookingTopic"
                            placeholder="เช่น ประชุมกลุ่มสาระ/อบรม">
                    </div>
                    <div class="form-group full">
                        <label class="form-label" for="bookingDetail">รายละเอียด/วัตถุประสงค์</label>
                        <textarea class="form-input booking-textarea" id="bookingDetail" name="bookingDetail" rows="4"
                            placeholder="ระบุรายละเอียดการใช้งาน"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="bookingEquipment">อุปกรณ์ที่ต้องการ</label>
                        <textarea class="form-input booking-textarea" id="bookingEquipment" name="equipmentDetail" rows="3"
                            placeholder="โปรเจคเตอร์, ไมโครโฟน"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="bookingOwner">ผู้ประสานงาน</label>
                        <input class="form-input" type="text" id="bookingOwner" name="requesterDisplayName"
                            value="<?= h($teacher_name) ?>" readonly>
                    </div>
                </div>

                <div class="booking-actions">
                    <button type="submit" class="btn-outline" name="room_booking_check" value="1">ตรวจสอบเวลาว่าง</button>
                    <button type="submit" class="btn-confirm" name="room_booking_save" value="1">บันทึกการจอง</button>
                </div>

                <p class="booking-note">* ระบบจะส่งคำขอให้ผู้ดูแลพิจารณาอนุมัติ</p>
            </form>
        </section>

        <section class="booking-card booking-calendar-card">
            <div class="booking-card-header">
                <div class="booking-card-title-group">
                    <h2 class="booking-card-title">ปฏิทินการจอง</h2>
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

            <!-- <div class="booking-summary">
                <div class="booking-summary-item">
                    <h3><?php //h((string) $room_booking_total)
                        ?> รายการ</h3>
                    <p>รายการจองทั้งหมด</p>
                </div>
                <div class="booking-summary-item">
                    <h3><?php //h((string) $room_booking_approved_total)
                        ?> รายการ</h3>
                    <p>อนุมัติแล้ว</p>
                </div>
                <div class="booking-summary-item">
                    <h3><?php //h((string) $room_booking_pending_total)
                        ?> รายการ</h3>
                    <p>รออนุมัติ</p>
                </div>
            </div> -->
        </section>
    </div>

    <section class="booking-card booking-list-card booking-list-row">
        <div class="booking-card-header">
            <div class="booking-card-title-group">
                <h2 class="booking-card-title">รายการจองของฉัน</h2>
                <p class="booking-card-subtitle"><?= h($my_booking_subtitle) ?></p>
            </div>
            <button class="btn-link" type="button" data-booking-modal-open="bookingListModal">ดูทั้งหมด</button>
        </div>

        <div class="table-responsive">
            <table class="custom-table booking-table">
                <thead>
                    <tr>
                        <th>ห้อง</th>
                        <th>ช่วงเวลาที่ใช้</th>
                        <th>รายการ</th>
                        <th>จำนวน</th>
                        <th>สถานะ</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($my_bookings_latest)) : ?>

                    <?php else : ?>
                        <?php foreach ($my_bookings_latest as $booking_item) : ?>
                            <?php
                            $status_value = (int) ($booking_item['status'] ?? 0);
                            $status_label = $status_labels[$status_value]['label'] ?? $status_labels[0]['label'];
                            $status_class = $status_labels[$status_value]['class'] ?? $status_labels[0]['class'];
                            $detail_text = trim((string) ($booking_item['bookingDetail'] ?? ''));
                            $detail_text = $detail_text !== '' ? $detail_text : 'ไม่มีรายละเอียดเพิ่มเติม';
                            // Requester view: show only status (do not display rejection reason).
                            $status_reason_label = '-';
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
                                <td><?= h($booking_item['roomName'] ?? '-') ?></td>
                                <td>
                                    <?= h($date_range) ?><br>
                                    <span class="detail-subtext"><?= h($time_range !== '' ? $time_range : '-') ?></span>
                                </td>
                                <td><?= h($booking_item['bookingTopic'] ?? 'ประชุม/อบรม') ?></td>
                                <td><?= h((string) ($booking_item['attendeeCount'] ?? '-')) ?></td>
                                <td>
                                    <span class="status-pill <?= h($status_class) ?>"><?= h($status_label) ?></span>
                                </td>
                                <td class="booking-action-cell">
                                    <div class="booking-action-group">
                                        <button type="button" class="booking-action-btn secondary" data-booking-action="detail"
                                            data-booking-id="<?= h((string) ($booking_item['roomBookingID'] ?? '')) ?>"
                                            data-booking-room="<?= h($booking_item['roomName'] ?? '-') ?>"
                                            data-booking-date="<?= h($date_range) ?>"
                                            data-booking-time="<?= h($time_range) ?>"
                                            data-booking-topic="<?= h($booking_item['bookingTopic'] ?? 'ประชุม/อบรม') ?>"
                                            data-booking-detail="<?= h($detail_text) ?>"
                                            data-booking-attendees="<?= h((string) ($booking_item['attendeeCount'] ?? '-')) ?>"
                                            data-booking-status="<?= h((string) $status_value) ?>"
                                            data-booking-status-label="<?= h($status_label) ?>"
                                            data-booking-status-class="<?= h($status_class) ?>"
                                            data-booking-status-reason="<?= h($status_reason_label) ?>"
                                            data-booking-approval-label="<?= h($approval_label) ?>"
                                            data-booking-approval-name="<?= h($approval_name) ?>"
                                            data-booking-approval-at="<?= h($approval_at_label) ?>"
                                            data-booking-created="<?= h($created_label) ?>"
                                            data-booking-updated="<?= h($updated_label) ?>">
                                            <i class="fa-solid fa-eye"></i>
                                            <span class="tooltip">ดูรายละเอียด</span>
                                        </button>
                                        <?php if ($status_value === 0): ?>
                                            <button type="button" class="booking-action-btn danger" data-booking-action="delete"
                                                data-booking-id="<?= h((string) ($booking_item['roomBookingID'] ?? '')) ?>">
                                                <i class="fa-solid fa-trash"></i>
                                                <span class="tooltip danger">ลบข้อมูลการจอง</span>
                                            </button>
                                        <?php endif; ?>
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
                            <th>ช่วงเวลาที่ใช้</th>
                            <th>รายการ</th>
                            <th>จำนวน</th>
                            <th>สถานะ</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody data-empty-message="ยังไม่มีรายการจอง">
                        <?php if (empty($my_bookings_sorted)) : ?>
                            <tr>
                                <td colspan="6" class="booking-empty">ยังไม่มีรายการจอง</td>
                            </tr>
                        <?php else : ?>
                            <?php foreach ($my_bookings_sorted as $booking_item) : ?>
                                <?php
                                $status_value = (int) ($booking_item['status'] ?? 0);
                                $status_label = $status_labels[$status_value]['label'] ?? $status_labels[0]['label'];
                                $status_class = $status_labels[$status_value]['class'] ?? $status_labels[0]['class'];
                                $detail_text = trim((string) ($booking_item['bookingDetail'] ?? ''));
                                $detail_text = $detail_text !== '' ? $detail_text : 'ไม่มีรายละเอียดเพิ่มเติม';
                                // Requester view: show only status (do not display rejection reason).
                                $status_reason_label = '-';
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
                                    <td><?= h($booking_item['roomName'] ?? '-') ?></td>
                                    <td>
                                        <?= h($date_range) ?><br>
                                        <span class="detail-subtext"><?= h($time_range !== '' ? $time_range : '-') ?></span>
                                    </td>
                                    <td><?= h($booking_item['bookingTopic'] ?? 'ประชุม/อบรม') ?></td>
                                    <td><?= h((string) ($booking_item['attendeeCount'] ?? '-')) ?></td>
                                    <td>
                                        <span class="status-pill <?= h($status_class) ?>"><?= h($status_label) ?></span>
                                    </td>
                                    <td class="booking-action-cell">
                                        <div class="booking-action-group">
                                            <button type="button" class="booking-action-btn secondary" data-booking-action="detail"
                                                data-booking-id="<?= h((string) ($booking_item['roomBookingID'] ?? '')) ?>"
                                                data-booking-room="<?= h($booking_item['roomName'] ?? '-') ?>"
                                                data-booking-date="<?= h($date_range) ?>"
                                                data-booking-time="<?= h($time_range) ?>"
                                                data-booking-topic="<?= h($booking_item['bookingTopic'] ?? 'ประชุม/อบรม') ?>"
                                                data-booking-detail="<?= h($detail_text) ?>"
                                                data-booking-attendees="<?= h((string) ($booking_item['attendeeCount'] ?? '-')) ?>"
                                                data-booking-status="<?= h((string) $status_value) ?>"
                                                data-booking-status-label="<?= h($status_label) ?>"
                                                data-booking-status-class="<?= h($status_class) ?>"
                                                data-booking-status-reason="<?= h($status_reason_label) ?>"
                                                data-booking-approval-label="<?= h($approval_label) ?>"
                                                data-booking-approval-name="<?= h($approval_name) ?>"
                                                data-booking-approval-at="<?= h($approval_at_label) ?>"
                                                data-booking-created="<?= h($created_label) ?>"
                                                data-booking-updated="<?= h($updated_label) ?>">
                                                <i class="fa-solid fa-eye"></i>
                                                <span class="tooltip">ดูรายละเอียด</span>
                                            </button>
                                            <?php if ($status_value === 0): ?>
                                                <button type="button" class="booking-action-btn danger" data-booking-action="delete"
                                                    data-booking-id="<?= h((string) ($booking_item['roomBookingID'] ?? '')) ?>">
                                                    <i class="fa-solid fa-trash"></i>
                                                    <span class="tooltip danger">ลบข้อมูลการจอง</span>
                                                </button>
                                            <?php endif; ?>
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
            <div>
                <span class="status-pill" data-booking-detail="status">-</span>
                <div class="close-modal-btn" data-booking-modal-close="bookingDetailModal">
                    <i class="fa-solid fa-xmark"></i>
                </div>
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

        </div>
        <!-- <div class="booking-detail-actions" style="flex-grow: 1; align-items: flex-end;">
            <button type="button" class="booking-action-btn" data-booking-modal-close="bookingDetailModal">ปิดหน้าต่าง</button>
        </div> -->
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
                        <tbody id="room-table-body"></tbody>
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
                        <tbody id="car-table-body"></tbody>
                    </table>
                </div>
            </div>

            <div id="no-event-message" class="hidden">
                ไม่มีรายการจองห้องในวันนี้
            </div>
        </div>
    </div>
</div>

<textarea id="roomBookingEventsData" class="hidden" aria-hidden="true"><?= h($room_booking_events_json) ?></textarea>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';

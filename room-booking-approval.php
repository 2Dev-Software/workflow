<?php
require_once __DIR__ . '/src/Services/auth/auth-guard.php';
require_once __DIR__ . '/src/Services/security/security-service.php';
require_once __DIR__ . '/src/Services/system/system-year.php';
require_once __DIR__ . '/src/Services/teacher/teacher-profile.php';

$allowed_room_roles = [1, 5];
$current_role_id = (int) ($teacher['roleID'] ?? ($teacher_role_id ?? 0));
if (!in_array($current_role_id, $allowed_room_roles, true)) {
    header('Location: dashboard.php', true, 302);
    exit();
}

require_once __DIR__ . '/src/Services/room/room-booking-approval-actions.php';

$currentThaiYear = (int) date('Y') + 543;
$dh_year_value = (int) ($dh_year !== '' ? $dh_year : $currentThaiYear);
if ($dh_year_value < 2500) {
    $dh_year_value = $currentThaiYear;
}

require_once __DIR__ . '/src/Services/room/room-booking-approval-data.php';

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

$room_booking_approval_status_labels = [
    0 => ['label' => 'รออนุมัติ', 'class' => 'pending'],
    1 => ['label' => 'อนุมัติแล้ว', 'class' => 'approved'],
    2 => ['label' => 'ไม่อนุมัติ', 'class' => 'rejected'],
];

$room_booking_approval_requests = $room_booking_approval_requests ?? [];
$room_booking_approval_total = $room_booking_approval_total ?? count($room_booking_approval_requests);
$room_booking_approval_pending_total = $room_booking_approval_pending_total ?? 0;
$room_booking_approval_approved_total = $room_booking_approval_approved_total ?? 0;
$room_booking_approval_rejected_total = $room_booking_approval_rejected_total ?? 0;
$room_booking_approval_query = $room_booking_approval_query ?? '';
$room_booking_approval_status = $room_booking_approval_status ?? 'all';
$room_booking_approval_room = $room_booking_approval_room ?? 'all';

$room_booking_approval_alert = null;
if (isset($_SESSION['room_booking_approval_alert'])) {
    $room_booking_approval_alert = $_SESSION['room_booking_approval_alert'];
    unset($_SESSION['room_booking_approval_alert']);
}

$room_booking_approval_return_url = 'room-booking-approval.php';
if (!empty($_SERVER['QUERY_STRING'])) {
    $room_booking_approval_return_url .= '?' . $_SERVER['QUERY_STRING'];
}
?>
<!DOCTYPE html>
<html lang="th">
<?php require_once __DIR__ . '/public/components/x-head.php'; ?>

<body>

    <?php require_once __DIR__ . '/public/components/layout/preloader.php'; ?>
    <?php if (!empty($room_booking_approval_alert)) : ?>
        <?php $alert = $room_booking_approval_alert; ?>
        <?php require __DIR__ . '/public/components/x-alert.php'; ?>
    <?php endif; ?>
    <?php require_once __DIR__ . '/public/components/partials/x-sidebar.php'; ?>

    <section class="main-section">

        <?php require_once __DIR__ . '/public/components/partials/x-navigation.php'; ?>

        <main class="content-wrapper">

            <div class="content-header">
                <h1>ยินดีต้อนรับ</h1>
                <p>อนุมัติการจองสถานที่/ห้อง</p>
            </div>

            <div class="content-area booking-page">

                <section class="booking-card booking-list-card approval-filter-card">
                    <div class="booking-card-header">
                        <div class="booking-card-title-group">
                            <h2 class="booking-card-title">ค้นหาและกรองรายการ</h2>
                            <p class="booking-card-subtitle">ค้นหาจากผู้ขอจอง ห้อง และสถานะเพื่อจัดการรายการได้รวดเร็ว</p>
                        </div>
                        <div class="approval-summary-chip">
                            <span class="status-pill pending approval-chip">รออนุมัติ <?= htmlspecialchars((string) $room_booking_approval_pending_total, ENT_QUOTES, 'UTF-8') ?> รายการ</span>
                        </div>
                    </div>

                    <form class="approval-toolbar" method="get" action="room-booking-approval.php" data-approval-filter-form>
                        <div class="approval-filter-group">
                            <div class="room-admin-search">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <input class="form-input" type="search" name="q" value="<?= htmlspecialchars($room_booking_approval_query, ENT_QUOTES, 'UTF-8') ?>" placeholder="ค้นหาชื่อผู้จอง/ห้อง/หัวข้อ" autocomplete="off">
                            </div>
                            <div class="room-admin-filter">
                                <select class="form-input" name="status">
                                    <option value="all">ทุกสถานะ</option>
                                    <option value="pending" <?= $room_booking_approval_status === 'pending' ? 'selected' : '' ?>>รออนุมัติ</option>
                                    <option value="approved" <?= $room_booking_approval_status === 'approved' ? 'selected' : '' ?>>อนุมัติแล้ว</option>
                                    <option value="rejected" <?= $room_booking_approval_status === 'rejected' ? 'selected' : '' ?>>ไม่อนุมัติ</option>
                                </select>
                            </div>
                            <div class="room-admin-filter">
                                <select class="form-input" name="room">
                                    <option value="all">ทุกห้อง</option>
                                    <?php foreach ($room_booking_room_list as $room_item) : ?>
                                        <?php
                                        $room_id = (string) ($room_item['roomID'] ?? '');
                                        $room_name = trim((string) ($room_item['roomName'] ?? ''));
                                        if ($room_id === '') {
                                            continue;
                                        }
                                        if ($room_name === '') {
                                            $room_name = $room_id;
                                        }
                                        ?>
                                        <option value="<?= htmlspecialchars($room_id, ENT_QUOTES, 'UTF-8') ?>" <?= $room_booking_approval_room === $room_id ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($room_name, ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </form>
                </section>

                <section class="booking-card booking-list-card booking-list-row approval-table-card">
                    <div class="booking-card-header">
                        <div class="booking-card-title-group">
                            <h2 class="booking-card-title">รายการคำขอจอง</h2>
                            <p class="booking-card-subtitle">จัดการคำขอจองห้องและบันทึกผลการอนุมัติ</p>
                        </div>
                    </div>

                    <div class="table-responsive approval-table-wrapper">
                        <table class="custom-table booking-table approval-table">
                            <thead>
                                <tr>
                                    <th>ห้อง/สถานที่</th>
                                    <th>วันที่ใช้</th>
                                    <th>เวลา</th>
                                    <th>ผู้ขอจอง</th>
                                    <th>หัวข้อ</th>
                                    <th>จำนวน</th>
                                    <th>สถานะ</th>
                                    <th>จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($room_booking_approval_requests)) : ?>
                                    <tr>
                                        <td colspan="8" class="booking-empty">ยังไม่มีรายการรออนุมัติ</td>
                                    </tr>
                                <?php else : ?>
                                    <?php foreach ($room_booking_approval_requests as $request_item) : ?>
                                        <?php
                                        $status_value = (int) ($request_item['status'] ?? 0);
                                        $status_label = $room_booking_approval_status_labels[$status_value]['label'] ?? $room_booking_approval_status_labels[0]['label'];
                                        $status_class = $room_booking_approval_status_labels[$status_value]['class'] ?? $room_booking_approval_status_labels[0]['class'];
                                        $date_range = $format_thai_date_range(
                                            (string) ($request_item['startDate'] ?? ''),
                                            (string) ($request_item['endDate'] ?? '')
                                        );
                                        $time_range = trim((string) ($request_item['startTime'] ?? '') . '-' . (string) ($request_item['endTime'] ?? ''));
                                        $detail_text = trim((string) ($request_item['bookingDetail'] ?? ''));
                                        $detail_text = $detail_text !== '' ? $detail_text : 'ไม่มีรายละเอียดเพิ่มเติม';
                                        $equipment_text = trim((string) ($request_item['equipmentDetail'] ?? ''));
                                        $equipment_text = $equipment_text !== '' ? $equipment_text : '-';
                                        $contact_phone = trim((string) ($request_item['contactPhone'] ?? ''));
                                        $contact_label = $contact_phone !== '' ? $contact_phone : '-';
                                        $status_reason = trim((string) ($request_item['statusReason'] ?? ''));
                                        if ($status_value === 2 && $status_reason === '') {
                                            $status_reason = 'ไม่ระบุเหตุผล';
                                        }
                                        $approval_name = trim((string) ($request_item['approvedByName'] ?? ''));
                                        if ($approval_name === '' && $status_value !== 0) {
                                            $approval_name = 'เจ้าหน้าที่ระบบ';
                                        }
                                        $approval_name = $status_value === 0 ? 'รอการอนุมัติ' : $approval_name;
                                        $approval_at = $format_thai_datetime((string) ($request_item['approvedAt'] ?? ''));
                                        $created_label = $format_thai_datetime((string) ($request_item['createdAt'] ?? ''));
                                        $updated_label = $format_thai_datetime((string) ($request_item['updatedAt'] ?? ''));
                                        ?>
                                        <tr class="approval-row <?= htmlspecialchars($status_class, ENT_QUOTES, 'UTF-8') ?>">
                                            <td><?= htmlspecialchars($request_item['roomName'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($date_range, ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($time_range, ENT_QUOTES, 'UTF-8') ?></td>
                                            <td>
                                                <?= htmlspecialchars($request_item['requesterName'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                                                <div class="detail-subtext"><?= htmlspecialchars($request_item['departmentName'] ?? '-', ENT_QUOTES, 'UTF-8') ?></div>
                                                <div class="detail-subtext">โทร <?= htmlspecialchars($contact_label, ENT_QUOTES, 'UTF-8') ?></div>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($request_item['bookingTopic'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                                                <div class="detail-subtext">ส่งคำขอเมื่อ <?= htmlspecialchars($created_label, ENT_QUOTES, 'UTF-8') ?></div>
                                            </td>
                                            <td><?= htmlspecialchars((string) ($request_item['attendeeCount'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td>
                                                <span class="status-pill <?= htmlspecialchars($status_class, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($status_label, ENT_QUOTES, 'UTF-8') ?></span>
                                                <?php if ($status_value === 2) : ?>
                                                    <div class="status-reason">เหตุผล: <?= htmlspecialchars($status_reason, ENT_QUOTES, 'UTF-8') ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="booking-action-cell">
                                                <div class="booking-action-group">
                                                    <button
                                                        type="button"
                                                        class="booking-action-btn secondary"
                                                        data-approval-action="detail"
                                                        data-approval-id="<?= htmlspecialchars((string) ($request_item['roomBookingID'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>"
                                                        data-approval-code="<?= htmlspecialchars((string) ($request_item['roomBookingID'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>"
                                                        data-approval-room="<?= htmlspecialchars($request_item['roomName'] ?? '-', ENT_QUOTES, 'UTF-8') ?>"
                                                        data-approval-date="<?= htmlspecialchars($date_range, ENT_QUOTES, 'UTF-8') ?>"
                                                        data-approval-time="<?= htmlspecialchars($time_range, ENT_QUOTES, 'UTF-8') ?>"
                                                        data-approval-requester="<?= htmlspecialchars($request_item['requesterName'] ?? '-', ENT_QUOTES, 'UTF-8') ?>"
                                                        data-approval-department="<?= htmlspecialchars($request_item['departmentName'] ?? '-', ENT_QUOTES, 'UTF-8') ?>"
                                                        data-approval-contact="<?= htmlspecialchars($contact_label, ENT_QUOTES, 'UTF-8') ?>"
                                                        data-approval-topic="<?= htmlspecialchars($request_item['bookingTopic'] ?? '-', ENT_QUOTES, 'UTF-8') ?>"
                                                        data-approval-detail="<?= htmlspecialchars($detail_text, ENT_QUOTES, 'UTF-8') ?>"
                                                        data-approval-equipment="<?= htmlspecialchars($equipment_text, ENT_QUOTES, 'UTF-8') ?>"
                                                        data-approval-attendees="<?= htmlspecialchars((string) ($request_item['attendeeCount'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>"
                                                        data-approval-status="<?= htmlspecialchars((string) $status_value, ENT_QUOTES, 'UTF-8') ?>"
                                                        data-approval-status-label="<?= htmlspecialchars($status_label, ENT_QUOTES, 'UTF-8') ?>"
                                                        data-approval-status-class="<?= htmlspecialchars($status_class, ENT_QUOTES, 'UTF-8') ?>"
                                                        data-approval-reason="<?= htmlspecialchars($status_reason, ENT_QUOTES, 'UTF-8') ?>"
                                                        data-approval-name="<?= htmlspecialchars($approval_name, ENT_QUOTES, 'UTF-8') ?>"
                                                        data-approval-at="<?= htmlspecialchars($approval_at, ENT_QUOTES, 'UTF-8') ?>"
                                                        data-approval-created="<?= htmlspecialchars($created_label, ENT_QUOTES, 'UTF-8') ?>"
                                                        data-approval-updated="<?= htmlspecialchars($updated_label, ENT_QUOTES, 'UTF-8') ?>">
                                                        ดูรายละเอียด
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

    <div id="bookingApprovalDetailModal" class="modal-overlay hidden">
        <div class="modal-content booking-detail-modal approval-detail-modal">
            <header class="modal-header">
                <div class="modal-title">
                    <i class="fa-solid fa-clipboard-check"></i>
                    <span>รายละเอียดการอนุมัติการจอง</span>
                </div>
                <div class="close-modal-btn" data-approval-modal-close="bookingApprovalDetailModal">
                    <i class="fa-solid fa-xmark"></i>
                </div>
            </header>
            <div class="modal-body booking-detail-body approval-detail-body" style="margin-top: 12px;">
                <div class="approval-detail-layout">
                    <section class="approval-panel approval-panel--request">
                        <div class="approval-panel-header">
                            <h4 class="approval-panel-title">ข้อมูลผู้ขอจอง</h4>
                            <span class="approval-panel-subtitle">รายละเอียดการใช้ห้อง</span>
                        </div>

                        <div class="booking-detail-grid approval-request-grid">
                            <div class="detail-item">
                                <p class="detail-label">ห้อง/สถานที่</p>
                                <p class="detail-value" data-approval-detail="room">-</p>
                                <span class="detail-subtext" data-approval-detail="code">-</span>
                            </div>
                            <div class="detail-item">
                                <p class="detail-label">วันที่ใช้</p>
                                <p class="detail-value" data-approval-detail="date">-</p>
                            </div>
                            <div class="detail-item">
                                <p class="detail-label">เวลา</p>
                                <p class="detail-value" data-approval-detail="time">-</p>
                            </div>
                            <div class="detail-item">
                                <p class="detail-label">ผู้ขอจอง</p>
                                <p class="detail-value" data-approval-detail="requester">-</p>
                                <span class="detail-subtext" data-approval-detail="department">-</span>
                            </div>
                            <div class="detail-item">
                                <p class="detail-label">ติดต่อ</p>
                                <p class="detail-value" data-approval-detail="contact">-</p>
                            </div>
                            <div class="detail-item">
                                <p class="detail-label">จำนวนผู้เข้าร่วม</p>
                                <p class="detail-value" data-approval-detail="attendees">-</p>
                            </div>
                        </div>

                        <div class="booking-detail-section">
                            <h4>หัวข้อ</h4>
                            <p data-approval-detail="topic">-</p>
                        </div>

                        <div class="booking-detail-section">
                            <h4>รายละเอียด</h4>
                            <p data-approval-detail="detail">-</p>
                        </div>

                        <div class="booking-detail-section">
                            <h4>อุปกรณ์</h4>
                            <p data-approval-detail="equipment">-</p>
                        </div>
                    </section>

                    <section class="approval-panel approval-panel--decision">
                        <div class="approval-panel-header">
                            <h4 class="approval-panel-title">การพิจารณา</h4>
                            <span class="approval-panel-subtitle">ผลล่าสุด</span>
                        </div>

                        <div class="booking-detail-grid approval-decision-grid">
                            <div class="detail-item detail-half detail-status-item">
                                <p class="detail-label">สถานะ</p>
                                <span class="status-pill" data-approval-detail="status">-</span>
                            </div>
                            <div class="detail-item detail-half" data-approval-detail="approval-item">
                                <p class="detail-label" data-approval-detail="approval-label">ผู้อนุมัติ</p>
                                <p class="detail-value" data-approval-detail="approval-name">-</p>
                                <span class="detail-subtext" data-approval-detail="approval-at">-</span>
                            </div>
                        </div>

                        <div class="booking-detail-section detail-reason-section hidden" data-approval-detail="reason-row">
                            <h4>เหตุผลไม่อนุมัติ</h4>
                            <p data-approval-detail="reason">-</p>
                        </div>

                        <form class="approval-decision-form" method="post" action="room-booking-approval.php" data-approval-form>
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="room_booking_id" value="">
                            <input type="hidden" name="approval_action" value="">
                            <input type="hidden" name="return_url" value="<?= htmlspecialchars($room_booking_approval_return_url, ENT_QUOTES, 'UTF-8') ?>">

                            <div class="booking-detail-section approval-decision">
                                <div class="approval-decision-head">
                                    <h4>บันทึกผล</h4>
                                    <p class="detail-subtext">เลือกผลและระบุเหตุผลเมื่อไม่อนุมัติ</p>
                                </div>
                                <p class="detail-label">เหตุผล (ถ้าไม่อนุมัติ)</p>
                                <textarea class="form-input booking-textarea" name="statusReason" rows="3" placeholder="ระบุเหตุผลให้ผู้ขอจองทราบ"></textarea>
                                <p class="detail-subtext">เหตุผลนี้จะแสดงให้ผู้ขอจอง</p>
                            </div>

                            <div class="booking-detail-meta">
                                <div class="detail-meta-item">
                                    <span>สร้างรายการ</span>
                                    <strong data-approval-detail="created">-</strong>
                                </div>
                                <div class="detail-meta-item">
                                    <span>อัปเดตล่าสุด</span>
                                    <strong data-approval-detail="updated">-</strong>
                                </div>
                            </div>

                            <div class="booking-actions">
                                <button type="submit" class="btn-outline" data-approval-submit="reject">ไม่อนุมัติ</button>
                                <button type="submit" class="btn-confirm" data-approval-submit="approve">อนุมัติรายการ</button>
                            </div>
                        </form>
                    </section>
                </div>
            </div>
        </div>
    </div>

    <?php require_once __DIR__ . '/public/components/x-scripts.php'; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const detailModal = document.getElementById('bookingApprovalDetailModal');
            const detailButtons = document.querySelectorAll('[data-approval-action="detail"]');
            const closeButtons = document.querySelectorAll('[data-approval-modal-close]');
            const filterForm = document.querySelector('[data-approval-filter-form]');

            if (filterForm) {
                const filterSelects = filterForm.querySelectorAll('select');
                filterSelects.forEach(function (select) {
                    select.addEventListener('change', function () {
                        filterForm.submit();
                    });
                });
            }

            const detailFields = detailModal
                ? {
                    room: detailModal.querySelector('[data-approval-detail="room"]'),
                    code: detailModal.querySelector('[data-approval-detail="code"]'),
                    date: detailModal.querySelector('[data-approval-detail="date"]'),
                    time: detailModal.querySelector('[data-approval-detail="time"]'),
                    requester: detailModal.querySelector('[data-approval-detail="requester"]'),
                    department: detailModal.querySelector('[data-approval-detail="department"]'),
                    contact: detailModal.querySelector('[data-approval-detail="contact"]'),
                    attendees: detailModal.querySelector('[data-approval-detail="attendees"]'),
                    status: detailModal.querySelector('[data-approval-detail="status"]'),
                    topic: detailModal.querySelector('[data-approval-detail="topic"]'),
                    detail: detailModal.querySelector('[data-approval-detail="detail"]'),
                    equipment: detailModal.querySelector('[data-approval-detail="equipment"]'),
                    reasonRow: detailModal.querySelector('[data-approval-detail="reason-row"]'),
                    reason: detailModal.querySelector('[data-approval-detail="reason"]'),
                    approvalItem: detailModal.querySelector('[data-approval-detail="approval-item"]'),
                    approvalLabel: detailModal.querySelector('[data-approval-detail="approval-label"]'),
                    approvalName: detailModal.querySelector('[data-approval-detail="approval-name"]'),
                    approvalAt: detailModal.querySelector('[data-approval-detail="approval-at"]'),
                    created: detailModal.querySelector('[data-approval-detail="created"]'),
                    updated: detailModal.querySelector('[data-approval-detail="updated"]'),
                }
                : {};

            const approvalForm = detailModal ? detailModal.querySelector('[data-approval-form]') : null;
            const approvalIdInput = approvalForm ? approvalForm.querySelector('[name="room_booking_id"]') : null;
            const approvalActionInput = approvalForm ? approvalForm.querySelector('[name="approval_action"]') : null;
            const approvalReasonInput = approvalForm ? approvalForm.querySelector('[name="statusReason"]') : null;
            const approvalActionButtons = detailModal ? detailModal.querySelectorAll('[data-approval-submit]') : [];

            detailButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    if (!detailModal) return;
                    const statusClass = button.dataset.approvalStatusClass || 'pending';
                    const statusLabel = button.dataset.approvalStatusLabel || '-';
                    const statusValue = parseInt(button.dataset.approvalStatus || '0', 10);
                    const reason = button.dataset.approvalReason || '-';
                    const approvalName = button.dataset.approvalName || '-';
                    const approvalAt = button.dataset.approvalAt || '-';

                    if (approvalIdInput) {
                        approvalIdInput.value = button.dataset.approvalId || '';
                    }
                    if (approvalActionInput) {
                        approvalActionInput.value = '';
                    }
                    if (approvalReasonInput) {
                        approvalReasonInput.value = '';
                    }

                    if (detailFields.room) detailFields.room.textContent = button.dataset.approvalRoom || '-';
                    if (detailFields.code) detailFields.code.textContent = 'รหัสคำขอ ' + (button.dataset.approvalCode || '-');
                    if (detailFields.date) detailFields.date.textContent = button.dataset.approvalDate || '-';
                    if (detailFields.time) detailFields.time.textContent = button.dataset.approvalTime || '-';
                    if (detailFields.requester) detailFields.requester.textContent = button.dataset.approvalRequester || '-';
                    if (detailFields.department) detailFields.department.textContent = button.dataset.approvalDepartment || '-';
                    if (detailFields.contact) detailFields.contact.textContent = button.dataset.approvalContact || '-';
                    if (detailFields.attendees) detailFields.attendees.textContent = button.dataset.approvalAttendees || '-';
                    if (detailFields.topic) detailFields.topic.textContent = button.dataset.approvalTopic || '-';
                    if (detailFields.detail) detailFields.detail.textContent = button.dataset.approvalDetail || '-';
                    if (detailFields.equipment) detailFields.equipment.textContent = button.dataset.approvalEquipment || '-';
                    if (detailFields.created) detailFields.created.textContent = button.dataset.approvalCreated || '-';
                    if (detailFields.updated) detailFields.updated.textContent = button.dataset.approvalUpdated || '-';

                    if (detailFields.status) {
                        detailFields.status.textContent = statusLabel;
                        detailFields.status.className = 'status-pill ' + statusClass;
                    }

                    if (detailFields.reasonRow && detailFields.reason) {
                        const showReason = statusValue === 2;
                        detailFields.reasonRow.classList.toggle('hidden', !showReason);
                        if (showReason) {
                            detailFields.reason.textContent = reason;
                        }
                    }

                    if (detailFields.approvalLabel && detailFields.approvalName && detailFields.approvalAt) {
                        if (statusValue === 0) {
                            detailFields.approvalLabel.textContent = 'ผู้อนุมัติ';
                            detailFields.approvalName.textContent = 'รอการอนุมัติ';
                            detailFields.approvalAt.textContent = '-';
                        } else {
                            detailFields.approvalLabel.textContent = statusValue === 2 ? 'ผู้ไม่อนุมัติ' : 'ผู้อนุมัติ';
                            detailFields.approvalName.textContent = approvalName || '-';
                            detailFields.approvalAt.textContent = approvalAt || '-';
                        }
                    }

                    detailModal.classList.remove('hidden');
                });
            });

            if (approvalActionButtons.length > 0) {
                approvalActionButtons.forEach(function (button) {
                    button.addEventListener('click', function () {
                        if (approvalActionInput) {
                            approvalActionInput.value = button.getAttribute('data-approval-submit') || '';
                        }
                    });
                });
            }

            closeButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    const targetId = button.getAttribute('data-approval-modal-close');
                    if (!targetId) return;
                    const targetModal = document.getElementById(targetId);
                    if (targetModal) {
                        targetModal.classList.add('hidden');
                    }
                });
            });

            if (detailModal) {
                detailModal.addEventListener('click', function (event) {
                    if (event.target === detailModal) {
                        detailModal.classList.add('hidden');
                    }
                });
            }
        });
    </script>

</body>

</html>

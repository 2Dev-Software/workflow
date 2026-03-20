<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../auth/csrf.php';

$room_booking_room_list = (array) ($room_booking_room_list ?? []);
$room_booking_approval_requests = (array) ($room_booking_approval_requests ?? []);
$room_booking_approval_total = (int) ($room_booking_approval_total ?? 0);
$room_booking_approval_pending_total = (int) ($room_booking_approval_pending_total ?? 0);
$room_booking_approval_approved_total = (int) ($room_booking_approval_approved_total ?? 0);
$room_booking_approval_rejected_total = (int) ($room_booking_approval_rejected_total ?? 0);
$room_booking_approval_query = (string) ($room_booking_approval_query ?? '');
$room_booking_approval_status = (string) ($room_booking_approval_status ?? 'all');
$room_booking_approval_room = (string) ($room_booking_approval_room ?? 'all');
$room_booking_approval_alert = $room_booking_approval_alert ?? null;
$room_booking_approval_return_url = (string) ($room_booking_approval_return_url ?? 'room-booking-approval.php');
$room_booking_approval_status_labels = (array) ($room_booking_approval_status_labels ?? []);

$alert = $room_booking_approval_alert;

ob_start();
?>
<div class="content-header">
    <h1>ยินดีต้อนรับ</h1>
    <p>อนุมัติการจองสถานที่/ห้อง</p>
</div>

<div class="content-area booking-page" data-room-booking-approval>
    <section class="booking-card booking-list-card approval-filter-card">
        <div class="booking-card-header">
            <div class="booking-card-title-group">
                <h2 class="booking-card-title">ค้นหาและกรองรายการ</h2>
            </div>
        </div>

        <form class="approval-toolbar" method="get" action="room-booking-approval.php" data-approval-filter-form>
            <div class="approval-filter-group">
                <div class="room-admin-search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input class="form-input" type="search" name="q" value="<?= h($room_booking_approval_query) ?>"
                        placeholder="ค้นหาชื่อผู้จอง/ห้อง/หัวข้อ" autocomplete="off">
                </div>
                <div class="room-admin-filter">
                    <div class="custom-select-wrapper">
                        <div class="custom-select-trigger">
                            <p class="select-value">ทุกสถานะ</p>
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>

                        <div class="custom-options">
                            <div class="custom-option" data-value="all">ทุกสถานะ</div>
                            <div class="custom-option" data-value="pending">รออนุมัติ</div>
                            <div class="custom-option" data-value="approved">อนุมัติแล้ว</div>
                            <div class="custom-option" data-value="rejected">ไม่อนุมัติ/ยกเลิก</div>
                        </div>

                        <select class="form-input" name="status">
                            <option value="all" <?= $room_booking_approval_status === 'all' ? 'selected' : '' ?>>ทุกสถานะ</option>
                            <option value="pending" <?= $room_booking_approval_status === 'pending' ? 'selected' : '' ?>>รออนุมัติ</option>
                            <option value="approved" <?= $room_booking_approval_status === 'approved' ? 'selected' : '' ?>>อนุมัติแล้ว</option>
                            <option value="rejected" <?= $room_booking_approval_status === 'rejected' ? 'selected' : '' ?>>ไม่อนุมัติ/ยกเลิก</option>
                        </select>
                    </div>
                </div>
                <div class="room-admin-filter">
                    <div class="custom-select-wrapper">
                        <div class="custom-select-trigger">
                            <p class="select-value">ทุกห้อง</p>
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>

                        <div class="custom-options">
                            <div class="custom-option" data-value="all">ทุกห้อง</div>
                            <?php foreach ($room_booking_room_list as $room_item): ?>
                                <?php
                                $room_id = trim((string) ($room_item['roomID'] ?? ''));

                                if ($room_id === '') {
                                    continue;
                                }
                                $room_name = trim((string) ($room_item['roomName'] ?? ''));
                                $room_name = $room_name !== '' ? $room_name : $room_id;
                                ?>
                                <div class="custom-option" data-value="<?= h($room_id) ?>"><?= h($room_name) ?></div>
                            <?php endforeach; ?>
                        </div>

                        <select class="form-input" name="room">
                            <option value="all" <?= $room_booking_approval_room === 'all' ? 'selected' : '' ?>>ทุกห้อง</option>
                            <?php foreach ($room_booking_room_list as $room_item): ?>
                                <?php
                                $room_id = trim((string) ($room_item['roomID'] ?? ''));

                                if ($room_id === '') {
                                    continue;
                                }
                                $room_name = trim((string) ($room_item['roomName'] ?? ''));
                                $room_name = $room_name !== '' ? $room_name : $room_id;
                                ?>
                                <option value="<?= h($room_id) ?>" <?= $room_booking_approval_room === $room_id ? 'selected' : '' ?>>
                                    <?= h($room_name) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

        </form>
    </section>

    <section class="booking-card booking-list-card">
        <div class="booking-card-header">
            <div class="booking-card-title-group">
                <h2 class="booking-card-title">รายการการจองสถานที่ทั้งหมด</h2>
            </div>
        </div>

        <div class="table-responsive">
            <table class="custom-table booking-table">
                <thead>
                    <tr>
                        <th>ห้อง</th>
                        <th>ช่วงเวลาที่ใช้</th>
                        <th>ผู้จอง</th>
                        <th>รายการ</th>
                        <th>จำนวน</th>
                        <th>สถานะ</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php require __DIR__ . '/../../../public/components/partials/room-booking-approval-table-rows.php'; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<div id="bookingApprovalDetailModal" class="modal-overlay hidden">
    <div class="modal-content booking-detail-modal approval-detail-modal">
        <header class="modal-header">
            <div class="modal-title">
                <span>รายละเอียดคำขอจอง</span>
            </div>
            <div>
                <span class="status-pill pending" data-approval-detail="status">รออนุมัติ</span>
                <div class="close-modal-btn" data-approval-modal-close="bookingApprovalDetailModal">
                    <i class="fa-solid fa-xmark"></i>
                </div>
            </div>
        </header>


        <div class="modal-body booking-detail-body">

            <div class="booking-detail-row">
                <div class="booking-detail-content">
                    <label>หัวข้อการจอง</label>
                    <input type="text" data-booking-detail="room" placeholder="-" disabled>
                </div>
            </div>

            <div class="booking-detail-row">
                <div class="booking-detail-content">
                    <label>รายละเอียด/วัตถุประสงค์</label>
                    <input type="text" data-booking-detail="room" placeholder="-" disabled>
                </div>
            </div>

            <div class="booking-detail-row">
                <div class="booking-detail-content">
                    <label>อุปกรณ์ที่ต้องการ</label>
                    <input type="text" data-booking-detail="room" placeholder="-" disabled>
                </div>
            </div>

            <div class="booking-detail-row">
                <div class="booking-detail-content">
                    <label>ห้อง/สถานที่</label>
                    <input type="text" data-booking-detail="room" placeholder="-" disabled>
                </div>
                <div class="booking-detail-content">
                    <label>ผู้ขอจอง</label>
                    <input type="text" data-booking-detail="room" placeholder="-" disabled>
                </div>
                <div class="booking-detail-content">
                    <label>โทรศัพท์</label>
                    <input type="text" data-booking-detail="room" placeholder="-" disabled>
                </div>
                <div class="booking-detail-content">
                    <label>จำนวนผู้เข้าร่วม</label>
                    <input type="text" data-booking-detail="room" placeholder="-" disabled>
                </div>
            </div>

            <div class="booking-detail-row">
                <div class="booking-detail-content">
                    <label>วันที่ใช้</label>
                    <input type="text" data-booking-detail="room" placeholder="-" disabled>
                </div>
                <div class="booking-detail-content">
                    <label>เวลา</label>
                    <input type="text" data-booking-detail="room" placeholder="-" disabled>
                </div>
            </div>

            <div class="booking-detail-row consider-section">
                <h1>การพิจารณา</h1>
                <div class="booking-detail-content">
                    <label>รายละเอียด</label>
                    <textarea name="" id="" rows="3"></textarea>
                </div>
                <div class="booking-detail-content-group">
                    <div class="booking-detail-content">
                        <label>สร้างรายการ</label>
                        <input type="text" data-booking-detail="room" placeholder="-" disabled>
                    </div>
                    <div class="booking-detail-content">
                        <label>อัปเดตล่าสุด</label>
                        <input type="text" data-booking-detail="room" placeholder="-" disabled>
                    </div>
                </div>
            </div>

        </div>


        <div class="footer-modal operation">
            <form method="POST" action="orders-create.php" class="orders-send-form" id="modalOrderSendForm">

                <button type="submit" class="btn-outline" form="modalOrderSendForm" data-approval-submit="reject">
                    <p>ไม่อนุมัติ</p>
                </button>

                <button type="submit" form="modalOrderSendForm" data-approval-submit="approve">
                    <p>อนุมัติรายการ</p>
                </button>

            </form>

        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';

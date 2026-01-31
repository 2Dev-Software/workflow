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
                <p class="booking-card-subtitle">ค้นหาจากผู้ขอจอง ห้อง และสถานะเพื่อจัดการรายการได้รวดเร็ว</p>
            </div>
            <div class="approval-summary-chip">
                <span class="status-pill pending approval-chip">รออนุมัติ
                    <?= h((string) $room_booking_approval_pending_total) ?> รายการ</span>
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
                            $room_name = (string) ($room_item['roomName'] ?? $room_id);
                            ?>
                            <option value="<?= h($room_id) ?>" <?= $room_booking_approval_room === $room_id ? 'selected' : '' ?>>
                                <?= h($room_name !== '' ? $room_name : $room_id) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="approval-filter-actions">
                <button type="submit" class="btn-confirm">ค้นหา</button>
                <a class="btn-outline" href="room-booking-approval.php">ล้างตัวกรอง</a>
            </div>
        </form>
    </section>

    <section class="booking-card booking-list-card">
        <div class="booking-card-header">
            <div class="booking-card-title-group">
                <h2 class="booking-card-title">รายการรออนุมัติ</h2>
                <p class="booking-card-subtitle">ตรวจสอบรายละเอียดและอนุมัติคำขอได้จากหน้านี้</p>
            </div>
            <div class="booking-summary">
                <div class="booking-summary-item">
                    <h3><?= h((string) $room_booking_approval_total) ?> รายการ</h3>
                    <p>ทั้งหมด</p>
                </div>
                <div class="booking-summary-item">
                    <h3><?= h((string) $room_booking_approval_approved_total) ?> รายการ</h3>
                    <p>อนุมัติแล้ว</p>
                </div>
                <div class="booking-summary-item">
                    <h3><?= h((string) $room_booking_approval_rejected_total) ?> รายการ</h3>
                    <p>ไม่อนุมัติ</p>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="custom-table booking-table">
                <thead>
                    <tr>
                        <th>ห้อง</th>
                        <th>วันที่ใช้</th>
                        <th>เวลา</th>
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
                <i class="fa-solid fa-calendar-check"></i>
                <span>รายละเอียดคำขอจอง</span>
            </div>
            <div class="close-modal-btn" data-approval-modal-close="bookingApprovalDetailModal">
                <i class="fa-solid fa-xmark"></i>
            </div>
        </header>
        <div class="modal-body booking-detail-body">
            <form class="approval-decision-form" method="post" action="room-booking-approval.php" data-approval-form>
                <?= csrf_field() ?>
                <input type="hidden" name="room_booking_id" value="">
                <input type="hidden" name="approval_action" value="">
                <input type="hidden" name="return_url" value="<?= h($room_booking_approval_return_url) ?>">

                <div class="booking-detail-grid">
                    <div class="detail-item">
                        <p class="detail-label">ห้อง/สถานที่</p>
                        <p class="detail-value" data-approval-detail="room">-</p>
                    </div>
                    <div class="detail-item">
                        <p class="detail-label">รหัสคำขอ</p>
                        <p class="detail-value" data-approval-detail="code">-</p>
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
                        <p class="detail-label">ผู้จอง</p>
                        <p class="detail-value" data-approval-detail="requester">-</p>
                        <span class="detail-subtext" data-approval-detail="department">-</span>
                    </div>
                    <div class="detail-item">
                        <p class="detail-label">โทรศัพท์</p>
                        <p class="detail-value" data-approval-detail="contact">-</p>
                    </div>
                    <div class="detail-item detail-half">
                        <p class="detail-label">จำนวนผู้เข้าร่วม</p>
                        <p class="detail-value" data-approval-detail="attendees">-</p>
                    </div>
                    <div class="detail-item detail-status-item detail-half">
                        <p class="detail-label">สถานะ</p>
                        <span class="status-pill" data-approval-detail="status">-</span>
                    </div>
                    <div class="detail-item detail-approval-item detail-full" data-approval-detail="approval-item">
                        <p class="detail-label" data-approval-detail="approval-label">ผู้อนุมัติ</p>
                        <p class="detail-value" data-approval-detail="approval-name">-</p>
                        <span class="detail-subtext" data-approval-detail="approval-at">-</span>
                    </div>
                </div>

                <div class="booking-detail-section">
                    <h4>หัวข้อการจอง</h4>
                    <p data-approval-detail="topic">-</p>
                </div>

                <div class="booking-detail-section">
                    <h4>รายละเอียด/วัตถุประสงค์</h4>
                    <p data-approval-detail="detail">-</p>
                </div>

                <div class="booking-detail-section">
                    <h4>อุปกรณ์ที่ต้องการ</h4>
                    <p data-approval-detail="equipment">-</p>
                </div>

                <div class="booking-detail-section detail-reason-section hidden" data-approval-detail="reason-row">
                    <h4>เหตุผลการไม่อนุมัติ</h4>
                    <p data-approval-detail="reason">-</p>
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

                <div class="booking-detail-actions">
                    <button type="button" class="booking-action-btn secondary" data-approval-modal-close="bookingApprovalDetailModal">ปิดหน้าต่าง</button>
                    <button type="button" class="booking-action-btn danger" data-approval-submit="reject">ไม่อนุมัติ</button>
                    <button type="button" class="booking-action-btn success" data-approval-submit="approve">อนุมัติ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="approvalConfirmationModal" class="alert-overlay hidden">
    <div class="alert-box">
        <div class="alert-header">
            <div class="icon-circle">
                <i class="fa-solid"></i>
            </div>
        </div>
        <div class="alert-body">
            <h1 id="approvalConfirmTitle"></h1>
            <p id="approvalConfirmMessage"></p>
            <div class="alert-actions">
                <button type="button" class="btn-close-alert btn-cancel-alert" data-approval-confirm-close>ยกเลิก</button>
                <button type="button" class="btn-close-alert" id="btnConfirmAction">ยืนยัน</button>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';

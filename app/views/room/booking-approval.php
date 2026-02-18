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
                <i class="fa-solid fa-clipboard-check" aria-hidden="true"></i>
                <span>รายละเอียดคำขอจอง</span>
            </div>
            <div>
                <span class="status-pill pending" data-approval-detail="status">รออนุมัติ</span>
                <div class="close-modal-btn" data-approval-modal-close="bookingApprovalDetailModal">
                    <i class="fa-solid fa-xmark"></i>
                </div>
            </div>
        </header>
        <div class="modal-body booking-detail-body approval-detail-body">
            <form class="approval-decision-form" method="post" action="room-booking-approval.php" data-approval-form>
                <?= csrf_field() ?>
                <input type="hidden" name="room_booking_id" value="">
                <input type="hidden" name="approval_action" value="">
                <input type="hidden" name="return_url" value="<?= h($room_booking_approval_return_url) ?>">
                <div class="approval-detail-layout">
                    <section class="approval-panel approval-panel--request">
                        <div class="approval-panel-header">
                            <h4 class="approval-panel-title">ข้อมูลคำขอ</h4>
                            <span class="approval-panel-subtitle">รายละเอียดการใช้สถานที่</span>
                        </div>

                        <div class="booking-detail-grid approval-request-grid">
                            <div class="detail-item">
                                <p class="detail-label">ห้อง/สถานที่</p>
                                <p class="detail-value" data-approval-detail="room">-</p>
                            </div>
                            <div class="detail-item">
                                <p class="detail-label">รหัสคำขอ</p>
                                <p class="detail-value" data-approval-detail="code">-</p>
                            </div>
                            <div class="detail-item">
                                <p class="detail-label">ผู้ขอจอง</p>
                                <p class="detail-value" data-approval-detail="requester">-</p>
                                <span class="detail-subtext" data-approval-detail="department">-</span>
                            </div>
                            <div class="detail-item room-booking">
                                <p class="detail-label">โทรศัพท์</p>
                                <p class="detail-value" data-approval-detail="contact">-</p>
                            </div>
                            <div class="detail-item room-booking">
                                <p class="detail-label">วันที่ใช้</p>
                                <p class="detail-value" data-approval-detail="date">-</p>
                            </div>
                            <div class="detail-item room-booking">
                                <p class="detail-label">เวลา</p>
                                <p class="detail-value" data-approval-detail="time">-</p>
                            </div>
                        </div>

                        <div class="booking-detail-section">
                            <h4>จำนวนผู้เข้าร่วม</h4>
                            <p data-approval-detail="attendees">-</p>
                        </div>

                    </section>

                    <section class="approval-panel approval-panel--decision">
                        <div class="approval-panel-header">
                            <h4 class="approval-panel-title">การพิจารณา</h4>
                            <span class="approval-panel-subtitle">ผลล่าสุดและการอนุมัติ</span>
                        </div>

                        <div class="detail-item" data-approval-detail="approval-item">
                            <p class="detail-label" data-approval-detail="approval-label">ผู้อนุมัติ</p>
                            <p class="detail-value" data-approval-detail="approval-name">รอการอนุมัติ</p>
                            <span class="detail-subtext" data-approval-detail="approval-at">-</span>
                        </div>

                        <div class="detail-item">
                            <p class="detail-label">หัวข้อการจอง</p>
                            <p class="detail-value" data-approval-detail="topic">-</p>
                        </div>

                        <div class="detail-item">
                            <p class="detail-label">รายละเอียด/วัตถุประสงค์</p>
                            <p class="detail-value" data-approval-detail="detail">-</p>
                        </div>

                        <div class="detail-item">
                            <p class="detail-label">อุปกรณ์ที่ต้องการ</p>
                            <p class="detail-value" data-approval-detail="equipment">-</p>
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
                            <button type="button" class="btn-outline"
                                data-approval-modal-close="bookingApprovalDetailModal">ปิด</button>
                            <button type="submit" class="btn-outline"
                                data-approval-submit="reject">ไม่อนุมัติ</button>
                            <button type="submit" class="btn-confirm"
                                data-approval-submit="approve">อนุมัติรายการ</button>
                        </div>
                    </section>
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

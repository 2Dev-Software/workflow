<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../auth/csrf.php';

$teacher_name = (string) ($teacher_name ?? '');
$dh_year_value = (int) ($dh_year_value ?? 0);
$requester_pid = (string) ($requester_pid ?? '');
$today = (string) ($today ?? date('Y-m-d'));
$vehicle_departments = (array) ($vehicle_departments ?? []);
$vehicle_factions = (array) ($vehicle_factions ?? []);
$vehicle_teachers = (array) ($vehicle_teachers ?? []);
$vehicle_booking_history = (array) ($vehicle_booking_history ?? []);
$vehicle_booking_payload = (array) ($vehicle_booking_payload ?? []);
$vehicle_reservation_status_labels = (array) ($vehicle_reservation_status_labels ?? []);
$format_thai_date_range = $format_thai_date_range ?? null;
$format_thai_datetime = $format_thai_datetime ?? null;
$format_thai_datetime_range = $format_thai_datetime_range ?? null;
$vehicle_reservation_alert = $vehicle_reservation_alert ?? null;
$alert = $vehicle_reservation_alert;

ob_start();
?>


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
        <?= csrf_field() ?>
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

        <div class="vehicle-row">
            <div class="vehicle-input-content">
                <label for="purpose">ขออนุญาตใช้รถเพื่อ</label>
                <textarea id="purpose" name="purpose" rows="5" placeholder="ระบุวัตถุประสงค์" required></textarea>
            </div>
        </div>

        <div class="vehicle-row">
            <div class="vehicle-input-content">
                <label for="location">ณ (สถานที่)</label>
                <input type="text" id="location" name="location" placeholder="ระบุสถานที่ปลายทาง" required>
            </div>

            <div class="vehicle-input-content">
                <label>มีคนนั่งจำนวน</label>
                <div class="calculated-field" id="passengerCountDisplay">
                    <i class="fa-solid fa-users" aria-hidden="true"></i>
                    <p data-passenger-count aria-live="polite">1 คน</p>
                </div>
                <input type="hidden" id="passengerCount" name="passengerCount" value="1">
            </div>
        </div>

        <div class="vehicle-row">
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

        <div class="vehicle-row file-sec">
            <div class="vehicle-input-content">
                <label>แนบเอกสาร <strong>(แนบเอกสารได้สูงสุด 5 ไฟล์)</strong></label>
                <div>
                    <button type="button" class="btn btn-upload-small"
                        onclick="document.getElementById('attachment').click()">
                        <p>เพิ่มไฟล์</p>
                    </button>
                </div>
                <input type="file" id="attachment" name="attachments[]" class="file-input" multiple
                    accept=".pdf,image/png,image/jpeg" hidden>
                <p class="form-error hidden" id="attachmentError">แนบได้สูงสุด 5 ไฟล์</p>
            </div>

            <div class="file-list" id="attachmentList" aria-live="polite"></div>
        </div>

        <div class="submit-section">
            <button type="submit" class="btn-submit" name="vehicle_reservation_save" value="1">บันทึกจองยานพาหนะ</button>
        </div>
    </form>

    <div class="vehicle-history tab-content" id="vehicleHistory">
        <div class="table-responsive">
            <table class="custom-table booking-table vehicle-booking-history-table">
                <thead>
                    <tr>
                        <th>ช่วงเวลาใช้งาน</th>
                        <th>สถานะ</th>
                        <th>วัตถุประสงค์</th>
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
                        <?php
                        // Cache-busting so PDF viewer always loads the latest template after refactors.
                        $vehicle_pdf_mtime = @filemtime(__DIR__ . '/../../../public/api/vehicle-booking-pdf.php');
                        ?>
                        <?php foreach ($vehicle_booking_history as $booking) : ?>
                            <?php
                            $status_key = strtoupper((string) ($booking['status'] ?? 'PENDING'));
                            $status_meta = $vehicle_reservation_status_labels[$status_key] ?? $vehicle_reservation_status_labels['PENDING'];
                            $status_label = $status_meta['label'];
                            $status_class = $status_meta['class'];
                            $updated_at = trim((string) ($booking['updatedAt'] ?? ''));

                            if ($updated_at === '' || $updated_at === '0000-00-00 00:00:00') {
                                $updated_at = (string) ($booking['createdAt'] ?? '');
                            }
                            $updated_date = $updated_at !== '' ? substr($updated_at, 0, 10) : '';
                            $updated_time = $updated_at !== '' && strlen($updated_at) >= 16 ? substr($updated_at, 11, 5) : '';
                            $updated_date_label = '-';

                            if ($updated_date !== '') {
                                if (is_callable($format_thai_date_range)) {
                                    $updated_date_label = $format_thai_date_range($updated_date, $updated_date);
                                } else {
                                    $updated_date_label = $updated_date;
                                }
                            }
                            $updated_time_label = $updated_time !== '' ? $updated_time : '-';

                            $start_at = (string) ($booking['startAt'] ?? '');
                            $end_at = (string) ($booking['endAt'] ?? '');
                            $start_date = $start_at !== '' ? substr($start_at, 0, 10) : '';
                            $end_date = $end_at !== '' ? substr($end_at, 0, 10) : '';
                            $date_range = '-';

                            if ($start_date !== '') {
                                if (is_callable($format_thai_date_range)) {
                                    $date_range = $format_thai_date_range($start_date, $end_date !== '' ? $end_date : $start_date);
                                } elseif (is_callable($format_thai_datetime_range)) {
                                    $date_range = $format_thai_datetime_range($start_at, $end_at);
                                } else {
                                    $date_range = $start_date;
                                }
                            }

                            $time_range = '-';

                            if ($start_at !== '' && $end_at !== '') {
                                $start_time = substr($start_at, 11, 5);
                                $end_time = substr($end_at, 11, 5);
                                $time_range = trim($start_time . '-' . $end_time);
                            }

                            $purpose_text = trim((string) ($booking['purpose'] ?? ''));
                            $purpose_text = $purpose_text !== '' ? $purpose_text : '-';
                            ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars($date_range, ENT_QUOTES, 'UTF-8') ?><br>
                                    <span class="detail-subtext"><?= htmlspecialchars($time_range, ENT_QUOTES, 'UTF-8') ?></span>
                                </td>
                                <td>
                                    <span class="status-pill <?= htmlspecialchars($status_class, ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars($status_label, ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($purpose_text, ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <?= htmlspecialchars($updated_date_label, ENT_QUOTES, 'UTF-8') ?><br>
                                    <span class="detail-subtext"><?= htmlspecialchars($updated_time_label, ENT_QUOTES, 'UTF-8') ?></span>
                                </td>
                                <td>
                                    <button type="button" class="booking-action-btn secondary" data-vehicle-approval-action="detail"
                                        data-vehicle-booking-action="detail"
                                        data-vehicle-booking-id="<?= htmlspecialchars((string) ($booking['bookingID'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

                                        <i class="fa-solid fa-eye"></i>
                                        <span class="tooltip"><?= $status_key === 'PENDING' ? 'ดู/แก้ไข' : 'ดูรายละเอียด' ?></span>
                                    </button>
                                    <?php if (!in_array($status_key, ['PENDING', 'ASSIGNED'], true)) : ?>
                                        <a href="public/api/vehicle-booking-pdf.php?booking_id=<?= urlencode((string) ($booking['bookingID'] ?? '')) ?>&v=<?= urlencode((string) ($vehicle_pdf_mtime ?: time())) ?>"
                                            class="booking-action-btn secondary" target="_blank" rel="noopener">
                                            <i class="fa-solid fa-file-pdf"></i>
                                            <span class="tooltip">ดูเอกสาร PDF</span>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<div id="vehicleBookingDetailModal" class="modal-overlay modal-overlay-vehicle-edit hidden">
    <div class="modal-content">
        <div class="header-modal">
            <div class="first-header">
                <p>รายละเอียดการจองยานพาหนะ</p>
            </div>
            <div class="sec-header">
                <i class="fa-solid fa-xmark" data-vehicle-modal-close="vehicleBookingDetailModal"></i>
            </div>
        </div>

        <form id="vehicleReservationEditForm" class="tab-content active" method="post"
            action="<?= htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') ?>"
            enctype="multipart/form-data" data-vehicle-form data-vehicle-edit-form>
            <?= csrf_field() ?>
            <input type="hidden" name="vehicle_booking_id" value="">
            <input type="hidden" name="dh_year"
                value="<?= htmlspecialchars((string) $dh_year_value, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="requesterPID"
                value="<?= htmlspecialchars($requester_pid, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="companionCount" id="vehicleEditCompanionCount" value="0">
            <div class="vehicle-row">
                <div class="vehicle-input-content">
                    <label>ส่วนราชการ</label>
                    <div class="custom-select-wrapper" id="vehicleEditDeptWrapper">
                        <input type="hidden" id="vehicleEditDepartment" name="department" value="">

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
                    <p class="form-error hidden" id="vehicleEditDepartmentError">กรุณาเลือกส่วนราชการ</p>
                </div>

                <div class="vehicle-input-content">
                    <label for="vehicleEditWriteDate">วันที่เขียน</label>
                    <input type="date" id="vehicleEditWriteDate" name="writeDate"
                        value="<?= htmlspecialchars($today, ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
            </div>

            <div class="vehicle-row">
                <div class="vehicle-input-content">
                    <label>ข้าพเจ้าพร้อมด้วย</label>
                    <div data-vehicle-companion-edit>
                        <div class="go-with-dropdown">
                            <input type="text" id="vehicleEditSearchInput" placeholder="ค้นหารายชื่อคุณครู" autocomplete="off" />

                            <div id="vehicleEditDropdown" class="go-with-dropdown-content">
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

                        <button id="openShowMemberVehicle" class="show-member" type="button">
                            <p>แสดงผู้เดินทางทั้งหมด</p>
                        </button>
                    </div>

                    <div class="calculated-field hidden" data-vehicle-companion-view>
                        <i class="fa-solid fa-users" aria-hidden="true"></i>
                        <p data-vehicle-passenger-list aria-live="polite">-</p>
                    </div>
                </div>
            </div>

            <div id="memberModalVehicle" class="custom-modal">
                <div class="custom-modal-content">
                    <div class="member-header">
                        <p>รายชื่อผู้เดินทางที่เลือก</p>
                        <i class="fa-solid fa-xmark close-modal"></i>
                    </div>
                    <div id="selectedMemberListVehicle" class="member-list-container">
                    </div>
                </div>
            </div>

            <div class="vehicle-row">
                <div class="vehicle-input-content">
                    <label for="vehicleEditPurpose">ขออนุญาตใช้รถเพื่อ</label>
                    <textarea id="vehicleEditPurpose" name="purpose" rows="5" placeholder="ระบุวัตถุประสงค์" required></textarea>
                </div>
            </div>

            <div class="vehicle-row">
                <div class="vehicle-input-content">
                    <label for="vehicleEditLocation">ณ (สถานที่)</label>
                    <input type="text" id="vehicleEditLocation" name="location" placeholder="ระบุสถานที่ปลายทาง" required>
                </div>

                <div class="vehicle-input-content">
                    <label>มีคนนั่งจำนวน</label>
                    <div class="calculated-field" id="vehicleEditPassengerCountDisplay">
                        <i class="fa-solid fa-users" aria-hidden="true"></i>
                        <p data-passenger-count aria-live="polite">1 คน</p>
                    </div>
                    <input type="hidden" id="vehicleEditPassengerCount" name="passengerCount" value="1">
                </div>
            </div>

            <div class="vehicle-row">
                <div class="vehicle-input-content">
                    <label for="vehicleEditStartDate">ในวันที่</label>
                    <input type="date" id="vehicleEditStartDate" name="startDate" required>
                </div>

                <div class="vehicle-input-content">
                    <label for="vehicleEditEndDate">ถึงวันที่</label>
                    <input type="date" id="vehicleEditEndDate" name="endDate" required>
                </div>

                <div class="vehicle-input-content">
                    <label>จำนวนวัน</label>
                    <div class="calculated-field" id="vehicleEditDayCount">
                        <i class="fa-regular fa-calendar"></i>
                        <p data-day-count aria-live="polite">-</p>
                    </div>
                </div>
            </div>

            <div class="vehicle-row">
                <div class="vehicle-input-content">
                    <label for="vehicleEditStartTime">เวลาเริ่มต้น</label>
                    <input type="time" id="vehicleEditStartTime" name="startTime" required>
                </div>

                <div class="vehicle-input-content">
                    <label for="vehicleEditEndTime">เวลาสิ้นสุด</label>
                    <input type="time" id="vehicleEditEndTime" name="endTime" required>
                </div>
            </div>

            <div class="vehicle-row">
                <div class="vehicle-input-content">
                    <label>ใช้น้ำมันเชื้อเพลิงจาก</label>
                    <div class="radio-group">
                        <div class="radio-item">
                            <input type="radio" id="vehicleEditFuelCentral" name="fuelSource" value="central" checked>
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

            <div class="vehicle-row file-sec">
                <div class="vehicle-input-content">
                    <label>แนบเอกสาร <strong>(แนบเอกสารได้สูงสุด 5 ไฟล์)</strong></label>

                    <div id="vehicleRetainAttachments"></div>

                    <div data-vehicle-attachment-edit>
                        <button type="button" class="btn btn-upload-small"
                            onclick="document.getElementById('vehicleEditAttachments').click()">
                            <p>เพิ่มไฟล์</p>
                        </button>
                    </div>

                    <input type="file" id="vehicleEditAttachments" name="attachments[]" class="file-input" multiple
                        accept=".pdf,image/png,image/jpeg" hidden>

                    <p class="form-error hidden" id="vehicleEditAttachmentError">แนบได้สูงสุด 5 ไฟล์</p>
                </div>

                <div class="file-list" id="vehicleAttachmentList" aria-live="polite"></div>
            </div>

            <div class="submit-section">
                <button type="submit" class="btn-submit" name="vehicle_reservation_update" value="1" data-vehicle-edit-submit>บันทึกการแก้ไข</button>
            </div>
        </form>

    </div>
</div>

<script>
    window.vehicleBookingHistory = <?= json_encode($vehicle_booking_payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    window.vehicleBookingFileEndpoint = 'public/api/vehicle-booking-file.php';
    window.vehicleReservationRequesterName = <?= json_encode($teacher_name, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
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

        const departmentWrapper = document.getElementById('vehicleEditDeptWrapper');
        const departmentDisplay = departmentWrapper ? departmentWrapper.querySelector('.select-value') : null;
        const departmentOptions = departmentWrapper ? departmentWrapper.querySelectorAll('.custom-option') : [];
        const dayCountDisplay = modal ? modal.querySelector('#vehicleEditDayCount [data-day-count]') : null;

        const companionEditBlock = modal ? modal.querySelector('[data-vehicle-companion-edit]') : null;
        const companionViewBlock = modal ? modal.querySelector('[data-vehicle-companion-view]') : null;
        const passengerListText = companionViewBlock ? companionViewBlock.querySelector('[data-vehicle-passenger-list]') : null;

        const fieldMap = modal ? {
            bookingId: modal.querySelector('[name="vehicle_booking_id"]'),
            department: document.getElementById('vehicleEditDepartment'),
            departmentWrapper: departmentWrapper,
            departmentDisplay: departmentDisplay,
            departmentOptions: departmentOptions,
            writeDate: document.getElementById('vehicleEditWriteDate'),
            purpose: document.getElementById('vehicleEditPurpose'),
            location: document.getElementById('vehicleEditLocation'),
            passengerCount: document.getElementById('vehicleEditPassengerCount'),
            passengerCountDisplay: modal.querySelector('#vehicleEditPassengerCountDisplay [data-passenger-count]'),
            startDate: document.getElementById('vehicleEditStartDate'),
            endDate: document.getElementById('vehicleEditEndDate'),
            startTime: document.getElementById('vehicleEditStartTime'),
            endTime: document.getElementById('vehicleEditEndTime'),
            fuelRadios: modal.querySelectorAll('input[name="fuelSource"]'),
            companionList: document.getElementById('vehicleEditDropdown'),
            companionSearch: document.getElementById('vehicleEditSearchInput'),
            dayCount: dayCountDisplay,
        } : {};

        const retainContainer = document.getElementById('vehicleRetainAttachments');
        const editAttachmentsInput = document.getElementById('vehicleEditAttachments');
        const editAttachmentsList = document.getElementById('vehicleAttachmentList');
        const editAttachmentError = document.getElementById('vehicleEditAttachmentError');
        const editAttachmentBox = modal ? modal.querySelector('[data-vehicle-attachment-edit]') : null;
        const editAttachmentButton = editAttachmentBox ? editAttachmentBox.querySelector('button') : null;
        const companionModal = document.getElementById('memberModalVehicle');
        const companionModalTrigger = document.getElementById('openShowMemberVehicle');
        const companionModalClose = companionModal ? companionModal.querySelector('.close-modal') : null;
        const companionModalList = document.getElementById('selectedMemberListVehicle');

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

        function setDepartmentValue(value) {
            if (!fieldMap.department) return;
            const safeValue = value || '';
            fieldMap.department.value = safeValue;
            if (fieldMap.departmentDisplay) {
                fieldMap.departmentDisplay.textContent = safeValue !== '' ? safeValue : 'เลือกส่วนราชการ';
            }
            if (fieldMap.departmentOptions && fieldMap.departmentOptions.length > 0) {
                fieldMap.departmentOptions.forEach((option) => {
                    option.classList.toggle('selected', option.getAttribute('data-value') === safeValue);
                });
            }
            if (fieldMap.departmentWrapper) {
                fieldMap.departmentWrapper.classList.remove('open');
            }
        }

        function updateModalDayCount() {
            if (!fieldMap.dayCount || !fieldMap.startDate || !fieldMap.endDate) return;
            const startDate = fieldMap.startDate.value;
            const endDate = fieldMap.endDate.value;

            if (startDate && fieldMap.endDate) {
                fieldMap.endDate.min = startDate;
            }

            if (!startDate || !endDate) {
                fieldMap.dayCount.textContent = '-';
                return;
            }

            const start = new Date(startDate);
            const end = new Date(endDate);
            const diffTime = end - start;
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
            fieldMap.dayCount.textContent = diffDays > 0 ? `${diffDays} วัน` : 'วันที่ไม่ถูกต้อง';
        }

        function renderAttachmentList(editable) {
            if (!editAttachmentsList || !retainContainer) return;
            editAttachmentsList.innerHTML = '';
            retainContainer.innerHTML = '';

            const existing = currentBooking && Array.isArray(currentBooking.attachments) ?
                currentBooking.attachments : [];

            // if (existing.length === 0 && pendingEditFiles.length === 0) {
            //     const empty = document.createElement('p');
            //     empty.className = 'attachment-empty';
            //     empty.textContent = 'ยังไม่มีไฟล์แนบ';
            //     editAttachmentsList.appendChild(empty);
            //     return;
            // }

            existing.forEach((file) => {
                const item = document.createElement('div');
                item.className = 'file-item-wrapper';
                item.dataset.fileId = String(file.fileID || '');

                if (editable) {
                    const removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.className = 'delete-btn';
                    removeBtn.innerHTML = '<i class="fa-solid fa-trash-can" aria-hidden="true"></i>';
                    removeBtn.addEventListener('click', () => {
                        currentBooking.attachments = currentBooking.attachments.filter(
                            (item) => item.fileID !== file.fileID
                        );
                        renderAttachmentList(editable);
                    });
                    item.appendChild(removeBtn);

                    const retainInput = document.createElement('input');
                    retainInput.type = 'hidden';
                    retainInput.name = 'retainAttachmentIds[]';
                    retainInput.value = String(file.fileID || '');
                    retainContainer.appendChild(retainInput);
                }

                const banner = document.createElement('div');
                banner.className = 'file-banner';

                const info = document.createElement('div');
                info.className = 'file-info';

                const iconWrap = document.createElement('div');
                iconWrap.className = 'file-icon';
                const icon = document.createElement('i');
                const isPdf = (file.mimeType || '') === 'application/pdf';
                const isImage = (file.mimeType || '') === 'image/jpeg' || (file.mimeType || '') === 'image/png';
                icon.className = isPdf ? 'fa-solid fa-file-pdf' : (isImage ? 'fa-solid fa-file-image' : 'fa-solid fa-file');
                icon.setAttribute('aria-hidden', 'true');
                iconWrap.appendChild(icon);

                const text = document.createElement('div');
                text.className = 'file-text';
                const name = document.createElement('span');
                name.className = 'file-name';
                name.textContent = file.fileName || 'ไฟล์แนบ';
                const type = document.createElement('span');
                type.className = 'file-type';
                type.textContent = file.mimeType || 'file';
                text.appendChild(name);
                text.appendChild(type);

                info.appendChild(iconWrap);
                info.appendChild(text);

                const actions = document.createElement('div');
                actions.className = 'file-actions';

                const viewBtn = document.createElement('a');
                viewBtn.href = 'javascript:void(0)';
                viewBtn.className = 'action-btn';
                viewBtn.title = 'ดูตัวอย่าง';
                viewBtn.innerHTML = '<i class="fa-solid fa-eye" aria-hidden="true"></i>';
                viewBtn.addEventListener('click', () => {
                    const url = `${window.vehicleBookingFileEndpoint}?booking_id=${currentBooking.id}&file_id=${file.fileID}`;
                    window.open(url, '_blank', 'noopener');
                });

                actions.appendChild(viewBtn);

                banner.appendChild(info);
                banner.appendChild(actions);
                item.appendChild(banner);
                editAttachmentsList.appendChild(item);
            });

            pendingEditFiles.forEach((file, index) => {
                const item = document.createElement('div');
                item.className = 'file-item-wrapper';

                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'delete-btn';
                removeBtn.innerHTML = '<i class="fa-solid fa-trash-can" aria-hidden="true"></i>';
                removeBtn.addEventListener('click', () => {
                    pendingEditFiles = pendingEditFiles.filter((_, i) => i !== index);
                    syncEditAttachmentInput();
                    renderAttachmentList(editable);
                    setEditAttachmentError('');
                });

                const banner = document.createElement('div');
                banner.className = 'file-banner';

                const info = document.createElement('div');
                info.className = 'file-info';

                const iconWrap = document.createElement('div');
                iconWrap.className = 'file-icon';
                const icon = document.createElement('i');
                const isPdf = file.type === 'application/pdf';
                const isImage = file.type === 'image/jpeg' || file.type === 'image/png';
                icon.className = isPdf ? 'fa-solid fa-file-pdf' : (isImage ? 'fa-solid fa-file-image' : 'fa-solid fa-file');
                icon.setAttribute('aria-hidden', 'true');
                iconWrap.appendChild(icon);

                const text = document.createElement('div');
                text.className = 'file-text';
                const name = document.createElement('span');
                name.className = 'file-name';
                name.textContent = file.name;
                const type = document.createElement('span');
                type.className = 'file-type';
                type.textContent = file.type || 'file';
                text.appendChild(name);
                text.appendChild(type);

                info.appendChild(iconWrap);
                info.appendChild(text);

                const actions = document.createElement('div');
                actions.className = 'file-actions';
                const viewLink = document.createElement('a');
                viewLink.href = 'javascript:void(0)';
                viewLink.className = 'action-btn';
                viewLink.title = 'ดูตัวอย่าง';
                viewLink.innerHTML = '<i class="fa-solid fa-eye" aria-hidden="true"></i>';
                viewLink.addEventListener('click', () => {
                    const url = URL.createObjectURL(file);
                    window.open(url, '_blank', 'noopener');
                    setTimeout(() => URL.revokeObjectURL(url), 1000);
                });
                actions.appendChild(viewLink);

                banner.appendChild(info);
                banner.appendChild(actions);
                item.appendChild(removeBtn);
                item.appendChild(banner);
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
            const existingCount = currentBooking && Array.isArray(currentBooking.attachments) ?
                currentBooking.attachments.length :
                0;

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
            renderAttachmentList(true);
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

        function openCompanionModal() {
            if (!companionModal) return;
            companionModal.style.display = 'flex';
            setTimeout(() => {
                companionModal.classList.add('show');
            }, 10);
        }

        function closeCompanionModal() {
            if (!companionModal) return;
            companionModal.classList.remove('show');
            setTimeout(() => {
                companionModal.style.display = 'none';
            }, 300);
        }

        function setEditable(editable) {
            if (!modal || !form) return;
            form.classList.toggle('is-readonly', !editable);
            if (companionEditBlock) {
                companionEditBlock.classList.toggle('hidden', !editable);
            }
            if (companionViewBlock) {
                companionViewBlock.classList.toggle('hidden', editable);
            }
            if (submitBtn) {
                submitBtn.disabled = !editable;
                submitBtn.classList.toggle('hidden', !editable);
            }
            if (editAttachmentBox) {
                editAttachmentBox.classList.toggle('hidden', !editable);
            }
            if (editAttachmentsInput) {
                editAttachmentsInput.disabled = !editable;
            }
            if (fieldMap.departmentWrapper) {
                fieldMap.departmentWrapper.style.pointerEvents = editable ? '' : 'none';
            }
            if (companionModalTrigger) {
                companionModalTrigger.disabled = !editable;
            }
            if (!editable) {
                closeCompanionDropdown();
                pendingEditFiles = [];
                syncEditAttachmentInput();
                setEditAttachmentError('');
            }

            const inputs = form.querySelectorAll('input, textarea, select');
            inputs.forEach((input) => {
                if (input.name === 'csrf_token' || input.name === 'vehicle_booking_id' || input.name === 'vehicle_reservation_update' || input.name === 'dh_year') {
                    return;
                }
                if (input.type === 'hidden') {
                    return;
                }
                input.disabled = !editable;
            });

            if (statusNote) {
                statusNote.textContent = editable ?
                    'แก้ไขได้เฉพาะรายการที่ส่งเอกสารแล้ว' :
                    'รายการนี้ไม่สามารถแก้ไขได้';
            }
        }

        function resetModal() {
            currentBooking = null;
            pendingEditFiles = [];
            syncEditAttachmentInput();
            renderAttachmentList(false);
            if (fieldMap.bookingId) fieldMap.bookingId.value = '';
            setDepartmentValue('');
            if (fieldMap.writeDate) fieldMap.writeDate.value = '';
            if (fieldMap.purpose) fieldMap.purpose.value = '';
            if (fieldMap.location) fieldMap.location.value = '';
            if (fieldMap.passengerCount) fieldMap.passengerCount.value = '1';
            if (fieldMap.passengerCountDisplay) fieldMap.passengerCountDisplay.textContent = '1 คน';
            if (passengerListText) passengerListText.textContent = '-';
            if (fieldMap.startDate) fieldMap.startDate.value = '';
            if (fieldMap.endDate) fieldMap.endDate.value = '';
            if (fieldMap.startTime) fieldMap.startTime.value = '';
            if (fieldMap.endTime) fieldMap.endTime.value = '';
            if (fieldMap.dayCount) fieldMap.dayCount.textContent = '-';
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
            closeCompanionModal();
            setEditAttachmentError('');
        }

        function renderPassengerList() {
            if (!passengerListText || !fieldMap.companionList) return;
            const names = [];
            fieldMap.companionList.querySelectorAll('input[type="checkbox"]:checked').forEach((box) => {
                const nameText = (box.dataset.name || '').trim();
                if (nameText !== '') {
                    names.push(nameText);
                }
            });
            const uniqueNames = Array.from(new Set(names.map((n) => n.trim()).filter(Boolean)));
            passengerListText.textContent = uniqueNames.length ? uniqueNames.join(', ') : 'ไม่มีผู้ร่วมเดินทาง';
        }

        function fillModal(data) {
            if (!data || !form) return;
            currentBooking = data;

            if (fieldMap.bookingId) fieldMap.bookingId.value = String(data.id || '');
            setDepartmentValue(data.department || '');
            if (fieldMap.writeDate) fieldMap.writeDate.value = data.writeDate || '';
            if (fieldMap.purpose) fieldMap.purpose.value = data.purpose || '';
            if (fieldMap.location) fieldMap.location.value = data.location || '';

            const startAt = data.startAt || '';
            const endAt = data.endAt || '';
            if (fieldMap.startDate) fieldMap.startDate.value = startAt ? startAt.split(' ')[0] : '';
            if (fieldMap.endDate) fieldMap.endDate.value = endAt ? endAt.split(' ')[0] : '';
            if (fieldMap.startTime) fieldMap.startTime.value = startAt ? startAt.split(' ')[1].slice(0, 5) : '';
            if (fieldMap.endTime) fieldMap.endTime.value = endAt ? endAt.split(' ')[1].slice(0, 5) : '';
            updateModalDayCount();

            const passengerValue = data.passengerCount || Math.max(1, (data.companionIds || []).length + 1);
            if (fieldMap.passengerCount) fieldMap.passengerCount.value = String(passengerValue);
            if (fieldMap.passengerCountDisplay) {
                fieldMap.passengerCountDisplay.textContent = passengerValue > 0 ? `${passengerValue} คน` : '-';
            }

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
            renderPassengerList();

            if (statusPill) {
                statusPill.textContent = data.statusLabel || 'ส่งเอกสารแล้ว';
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
            pendingEditFiles = [];
            syncEditAttachmentInput();
            renderAttachmentList(editable);
        }

        function updatePassengerCountFromCompanions() {
            if (!fieldMap.companionList || !fieldMap.passengerCount) return;
            const selected = fieldMap.companionList.querySelectorAll('input[type="checkbox"]:checked').length;
            const minPassengers = selected + 1;
            const currentValue = parseInt(fieldMap.passengerCount.value || '0', 10);
            if (!currentValue || currentValue < minPassengers) {
                fieldMap.passengerCount.value = String(minPassengers);
            }
            const passengerValue = parseInt(fieldMap.passengerCount.value || '0', 10);
            if (fieldMap.passengerCountDisplay) {
                fieldMap.passengerCountDisplay.textContent = passengerValue > 0 ? `${passengerValue} คน` : '-';
            }
            updateCompanionSummary();
            renderPassengerList();
        }

        if (fieldMap.companionList) {
            fieldMap.companionList.addEventListener('change', function(event) {
                if (event.target && event.target.matches('input[type="checkbox"]')) {
                    updatePassengerCountFromCompanions();
                }
            });
        }

        if (fieldMap.passengerCount && form) {
            fieldMap.passengerCount.addEventListener('input', function() {
                fieldMap.passengerCount.setCustomValidity('');
            });

            form.addEventListener('submit', function(event) {
                if (!fieldMap.companionList) return;
                const selected = fieldMap.companionList.querySelectorAll('input[type="checkbox"]:checked').length;
                const minPassengers = selected + 1;
                const currentValue = parseInt(fieldMap.passengerCount.value || '0', 10);
                if (currentValue < minPassengers) {
                    fieldMap.passengerCount.setCustomValidity(`จำนวนผู้เดินทางต้องไม่น้อยกว่า ${minPassengers} คน`);
                    event.preventDefault();
                    if (typeof form.reportValidity === 'function') {
                        form.reportValidity();
                    }
                } else {
                    fieldMap.passengerCount.setCustomValidity('');
                }
            });
        }

        if (fieldMap.companionSearch && fieldMap.companionList) {
            fieldMap.companionSearch.addEventListener('focus', function() {
                if (this.value.startsWith('จำนวน')) {
                    this.value = '';
                }
                openCompanionDropdown();
                filterCompanionDropdown(this.value);
            });

            fieldMap.companionSearch.addEventListener('click', function() {
                openCompanionDropdown();
            });

            fieldMap.companionSearch.addEventListener('input', function() {
                filterCompanionDropdown(this.value);
            });
        }

        if (companionModalTrigger && companionModalList && fieldMap.companionList) {
            companionModalTrigger.addEventListener('click', function(event) {
                event.preventDefault();
                companionModalList.innerHTML = '';
                const checkedBoxes = fieldMap.companionList.querySelectorAll('input[type="checkbox"]:checked');
                if (checkedBoxes.length > 0) {
                    const ul = document.createElement('ul');
                    checkedBoxes.forEach((checkbox) => {
                        const nameText = checkbox.dataset.name || checkbox.nextElementSibling?.innerText || '';
                        const li = document.createElement('li');
                        li.textContent = nameText;
                        ul.appendChild(li);
                    });
                    companionModalList.appendChild(ul);
                } else {
                    companionModalList.innerHTML = '<p style="text-align:center; color:#FF5050;">ยังไม่ได้เลือกรายชื่อผู้เดินทาง</p>';
                }
                openCompanionModal();
            });
        }

        if (companionModalClose) {
            companionModalClose.addEventListener('click', function(event) {
                event.preventDefault();
                closeCompanionModal();
            });
        }
        if (companionModal) {
            companionModal.addEventListener('click', function(event) {
                if (event.target === companionModal) {
                    closeCompanionModal();
                }
            });
        }

        if (fieldMap.startDate) {
            fieldMap.startDate.addEventListener('change', updateModalDayCount);
        }
        if (fieldMap.endDate) {
            fieldMap.endDate.addEventListener('change', updateModalDayCount);
        }

        if (editAttachmentsInput) {
            editAttachmentsInput.addEventListener('change', function() {
                addNewAttachments(this.files);
            });
        }

        if (editAttachmentButton && editAttachmentsInput) {
            editAttachmentButton.addEventListener('click', function(event) {
                event.preventDefault();
                editAttachmentsInput.click();
            });
        }

        openButtons.forEach((button) => {
            button.addEventListener('click', function() {
                const bookingId = parseInt(button.dataset.vehicleBookingId || '0', 10);
                const data = bookingMap[bookingId];
                if (!data || !modal) return;
                resetModal();
                fillModal(data);
                modal.classList.remove('hidden');
            });
        });

        closeButtons.forEach((button) => {
            button.addEventListener('click', function() {
                const targetId = button.getAttribute('data-vehicle-modal-close');
                if (!targetId) return;
                const targetModal = document.getElementById(targetId);
                if (targetModal) {
                    targetModal.classList.add('hidden');
                }
                closeCompanionModal();
            });
        });

        document.addEventListener('click', function(event) {
            if (!fieldMap.companionList || !fieldMap.companionSearch) return;
            const dropdownWrapper = fieldMap.companionSearch.closest('.go-with-dropdown');
            if (dropdownWrapper && !dropdownWrapper.contains(event.target)) {
                closeCompanionDropdown();
            }
        });

        if (modal) {
            modal.addEventListener('click', function(event) {
                if (event.target === modal) {
                    modal.classList.add('hidden');
                    closeCompanionModal();
                }
            });
        }
    });
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
// vscode-write-test Sat Feb  7 21:17:32 +07 2026

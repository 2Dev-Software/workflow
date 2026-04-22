<?php
require_once __DIR__ . '/../../helpers.php';

$dashboard_counts = (array) ($dashboard_counts ?? []);
$dashboard_shortcuts = (array) ($dashboard_shortcuts ?? []);
$dashboard_user = (array) ($dashboard_user ?? []);
$dashboard_calendar_events = (array) ($dashboard_calendar_events ?? []);
$dashboard_current_date_label = trim((string) ($dashboard_current_date_label ?? ''));
$dashboard_name = trim((string) ($dashboard_user['fName'] ?? ''));
$dashboard_position = trim((string) ($dashboard_user['position_name'] ?? ''));
$dashboard_role = trim((string) ($dashboard_user['role_name'] ?? ''));
$dashboard_role_ids = array_filter(array_map('intval', preg_split('/\s*,\s*/', trim((string) ($dashboard_user['roleID'] ?? ''))) ?: []));
$dashboard_is_admin = in_array(1, $dashboard_role_ids, true) || str_contains($dashboard_role, 'ผู้ดูแลระบบ');
$unread_external_circulars = (int) ($dashboard_counts['unread_external_circulars'] ?? $dashboard_counts['unread_circulars'] ?? 0);
$unread_internal_circulars = (int) ($dashboard_counts['unread_internal_circulars'] ?? 0);
$unread_memos = (int) ($dashboard_counts['unread_memos'] ?? 0);
$unread_orders = (int) ($dashboard_counts['unread_orders'] ?? 0);
$vehicle_notifications = (int) ($dashboard_counts['vehicle_notifications'] ?? $dashboard_counts['unread_vehicle_bookings'] ?? 0);
$repair_notifications = (int) ($dashboard_counts['repair_notifications'] ?? 0);

if ($dashboard_is_admin) {
    $vehicle_notifications = 0;
    $repair_notifications = 0;
}
$dashboard_notifications = array_values(array_filter([
    [
        'label' => 'หนังสือเวียน',
        'count' => $unread_external_circulars,
    ],
    [
        'label' => 'หนังสือเวียน (ภายใน)',
        'count' => $unread_internal_circulars,
    ],
    [
        'label' => 'บันทึกข้อความ',
        'count' => $unread_memos,
    ],
    [
        'label' => 'คำสั่งราชการ',
        'count' => $unread_orders,
    ],
    [
        'label' => 'จองยานพาหนะ',
        'count' => $vehicle_notifications,
        'message' => 'ที่ต้องดำเนินการ',
    ],
    [
        'label' => 'แจ้งเหตุซ่อมแซม',
        'count' => $repair_notifications,
        'message' => 'ที่ต้องดำเนินการ',
    ],
], static function (array $item): bool {
    return (int) ($item['count'] ?? 0) > 0;
}));

$visible_shortcuts = array_values(array_filter($dashboard_shortcuts, static function ($shortcut): bool {
    return !empty($shortcut['visible']);
}));
$dashboard_calendar_events_json = json_encode($dashboard_calendar_events, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

if ($dashboard_calendar_events_json === false) {
    $dashboard_calendar_events_json = '{}';
}

ob_start();
?>
<div class="dashboard-container">
    <div class="notification-system">
        <section>
          <div class="notification-list">
              <ul>
                  <?php if ($dashboard_notifications === []) : ?>
                      <li>ไม่พบรายการเอกสารที่ต้องดำเนินการ</li>
                  <?php else : ?>
                      <?php foreach ($dashboard_notifications as $notification) : ?>
                          <li>คุณมี <b><?= h((string) ($notification['label'] ?? '-')) ?></b> <?= h((string) ($notification['message'] ?? 'ที่ยังไม่อ่าน')) ?> <b><?= h((string) ((int) ($notification['count'] ?? 0))) ?></b> ฉบับ</li>
                      <?php endforeach; ?>
                  <?php endif; ?>
              </ul>
          </div>
            <a href="#news-paper">
                <img src="public/assets/img/icon/news-paper.png" alt="">
                <p>กดเพื่อดูข่าวประชาสัมพันธ์</p>
            </a>
        </section>
        <main class="profile-section">
            <?php
            $profile_picture_raw = trim((string) ($dashboard_user['picture'] ?? ''));
            $profile_picture = '';

            if ($profile_picture_raw !== '' && strtoupper($profile_picture_raw) !== 'EMPTY') {
                $profile_picture = $profile_picture_raw;
            }
            ?>
            <section>
                <div class="profile-image">
                    <?php if ($profile_picture !== '') : ?>
                        <img src="<?= h($profile_picture) ?>" alt="Profile image">
                    <?php else : ?>
                        <i class="fa-solid fa-user"></i>
                    <?php endif; ?>
                </div>
                <div class="proflie-text">
                    <p><b>ชื่อ : <?= h($dashboard_name !== '' ? $dashboard_name : '-') ?></b></p>
                    <p>ตำแหน่ง : <?= h($dashboard_position !== '' ? $dashboard_position : '-') ?></p>
                    <p>หน้าที่ : <?= h($dashboard_role !== '' ? $dashboard_role : '-') ?></p>
                </div>
            </section>
            <div class="profile-date">
                <i class="fa-solid fa-clock"></i>
                <p><?= h($dashboard_current_date_label !== '' ? $dashboard_current_date_label : '-') ?></p>
            </div>
        </main>
    </div>

    <main class="panel-system">
        <section>
            <div class="dashboard-header">
                <p><strong>แผนผังระบบสำนักงานอิเล็กทรอนิกส์</strong></p>
            </div>

            <div class="dashboard-content">
                <a href="outgoing-receive.php">
                    <div class="card-shortcut">
                        <img src="public/assets/img/icon/member.png" alt="">
                        <p><strong>ลงทะเบียนรับ</strong></p>
                    </div>
                </a>
                <a href="outgoing.php">
                    <div class="card-shortcut">
                        <img src="public/assets/img/icon/clipboard.png" alt="">
                        <p><strong>ออกเลขทะเบียนส่ง</strong></p>
                    </div>
                </a>
                <a href="memo.php">
                    <div class="card-shortcut">
                        <img src="public/assets/img/icon/memo.png" alt="">
                        <p><strong>บันทึกข้อความ</strong></p>
                    </div>
                </a>
                <a href="circular-compose.php">
                    <div class="card-shortcut">
                        <img src="public/assets/img/icon/envelope.png" alt="">
                        <p><strong>ส่งหนังสือเวียน</strong></p>
                    </div>
                </a>
                <a href="orders-create.php">
                    <div class="card-shortcut">
                        <img src="public/assets/img/icon/files.png" alt="">
                        <p><strong>คำสั่งราชการ</strong></p>
                    </div>
                </a>
                <a href="vehicle-reservation.php">
                    <div class="card-shortcut">
                        <img src="public/assets/img/icon/car.png" alt="">
                        <p><strong>การจองพาหนะ</strong></p>
                    </div>
                </a>
                <a href="room-booking.php">
                    <div class="card-shortcut">
                        <img src="public/assets/img/icon/building.png" alt="">
                        <p><strong>การจองสถานที่/ห้อง</strong></p>
                    </div>
                </a>
                <a href="repairs.php">
                    <div class="card-shortcut">
                        <img src="public/assets/img/icon/repair.png" alt="">
                        <p><strong>แจ้งเหตุซ่อมแซม</strong></p>
                    </div>
                </a>
                <a href="certificates.php">
                    <div class="card-shortcut">
                        <img src="public/assets/img/icon/certificate.png" alt="">
                        <p><strong>ออกเลขเกียรติบัตร</strong></p>
                    </div>
                </a>
                <a href="teacher-phone-directory.php">
                    <div class="card-shortcut">
                        <img src="public/assets/img/icon/phone.png" alt="">
                        <p><strong>สมุดโทรศัพท์</strong></p>
                    </div>
                </a>
                <a href="profile.php">
                    <div class="card-shortcut">
                        <img src="public/assets/img/icon/user.png" alt="">
                        <p><strong>โปรไฟล์</strong></p>
                    </div>
                </a>
                <a href="setting.php">
                    <div class="card-shortcut">
                        <img src="public/assets/img/icon/setting.png" alt="">
                        <p><strong>การตั้งค่า</strong></p>
                    </div>
                </a>
            </div>
        </section>

        <section>
            <div class="dashboard-header" id="news-paper">
                <img src="public/assets/img/icon/news-paper.png" alt="">
                <p><strong>ข่าวประชาสัมพันธ์ และตารางนัดหมาย</strong></p>
            </div>

            <aside class="container-notification-section dashboard">

                <div class="news-bar">
                    <div class="details-news-bar">
                        <ul>
                            <li>
                                <p>ยังไม่มีข่าวประชาสัมพันธ์</p>
                            </li>
                            <li>
                                <p>ยังไม่มีข่าวประชาสัมพันธ์</p>
                            </li>
                            <li>
                                <p>ยังไม่มีข่าวประชาสัมพันธ์</p>
                            </li>
                            <li>
                                <p>ยังไม่มีข่าวประชาสัมพันธ์</p>
                            </li>
                            <li>
                                <p>ยังไม่มีข่าวประชาสัมพันธ์</p>
                            </li>
                            <li>
                                <p>ยังไม่มีข่าวประชาสัมพันธ์</p>
                            </li>
                            <li>
                                <p>ยังไม่มีข่าวประชาสัมพันธ์</p>
                            </li>
                            <li>
                                <p>ยังไม่มีข่าวประชาสัมพันธ์</p>
                            </li>
                            <li>
                                <p>ยังไม่มีข่าวประชาสัมพันธ์</p>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="container-calendar">
                    <div class="calendar">
                        <div class="header-calendar">
                            <div class="month-year" id="month-year"></div>
                            <div class="interact-button-calendar">
                                <button id="prev-btn">
                                    <i class="fa-solid fa-chevron-left"></i>
                                </button>
                                <button id="next-btn">
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
                <textarea id="roomBookingEventsData" class="hidden" aria-hidden="true"><?= h($dashboard_calendar_events_json) ?></textarea>

            </aside>
        </section>
    </main>

    <div id="event-modal-overlay" class="modal-overlay hidden">
        <div class="modal-content">
            <header class="modal-header">
                <div class="modal-title">
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
                    ไม่มีรายการจองในวันนี้
                </div>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';

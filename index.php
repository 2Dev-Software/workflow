<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/src/Services/security/security-service.php';
require_once __DIR__ . '/src/Services/auth/login.php';
require_once __DIR__ . '/src/Services/system/exec-duty-announcement.php';
?>
<!DOCTYPE html>
<html lang="th">
<?php require_once __DIR__ . '/public/components/x-head.php'; ?>

<body>
    <?php require_once __DIR__ . '/public/components/layout/preloader.php'; ?>

    <div class="container-login-page">

        <div class="container-login-section">

            <header class="header-login">
                <div class="container-notification-toggle">
                    <button type="button" id="toggleNewsBtn">
                        ดูข่าวประชาสัมพันธ์ <i class="fa-solid fa-bullhorn"></i>
                    </button>
                </div>
                <div class="logo-login">
                    <img src="assets/img/favicon/deebuk-logo.png" alt="DB-logo">
                </div>
                <div class="text-header-login">
                    <h3>ระบบสำนักงานอิเล็กทรอนิกส์</h3>
                    <h3>โรงเรียนดีบุกพังงาวิทยายน</h3>
                </div>
            </header>

            <section class="form-login">
                <form action="<?= htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') ?>" method="POST" enctype="application/x-www-form-urlencoded">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                    <div class="input-id-login-group">
                        <label for="pID">เลขบัตรประชาชน</label>
                        <input type="text" name="pID" id="pID" placeholder="เลขบัตรประชาชน" inputmode="numeric" maxlength="13" pattern="\d{13}" required>
                    </div>

                    <div class="input-password-login-group">
                        <label for="password">รหัสผ่าน</label>
                        <input type="password" name="password" id="password-toggle" placeholder="รหัสผ่าน" required>
                    </div>

                    <label class="remember-me-group">
                        <input type="checkbox" name="remember-me" id="remember">
                        <span class="checkmark"></span>
                        <p>จดจำฉัน</p>
                    </label>

                    <div class="button-login-group">
                        <button type="submit" name="submit">เข้าสู่ระบบ</button>
                    </div>
                </form>
            </section>

            <footer class="footer-login">
                <p>ระบบสำนักงานอิเล็กทรอนิกส์ โรงเรียนดีบุกพังงาวิทยายน</p>
                <p>DB HUB V.1.0.0 Copyright 2DEV&Software All rights reserved</p>
                <p>Paperless office พ.ศ.2568</p>
            </footer>

        </div>

        <aside class="container-notification-section" id="notificationSection">

            <header class="announcement-bar">
                <i class="fa-solid fa-bullhorn"></i>
                <p><?= htmlspecialchars($exec_duty_announcement, ENT_QUOTES, 'UTF-8') ?></p>
                <div class="close-news-section">
                    <i class="fa-solid fa-xmark" id="closeNewsBtn"></i>
                </div>
            </header>

            <div class="news-bar">
                <div class="header-news-bar">
                    <p>ข่าวประชาสัมพันธ์</p>
                    <a href="#">ดูข่าวทั้งหมด</a>
                </div>

                <div class="details-news-bar">
                    <ul>
                        <li>
                            <p>การดำเนินการสอบแก้ตัว ภาคเรียนที่ 1 ปีการศึกษา 2568</p>
                        </li>
                        <li>
                            <p>ผลการพิจารณาการรับย้ายนักเรียน ประจำภาคเรียนที่ 2 ปีการศึกษา 2568</p>
                        </li>
                        <li>
                            <p>การดำเนินการสอบแก้ตัว ภาคเรียนที่ 1 ปีการศึกษา 2568</p>
                        </li>
                        <li>
                            <p>การดำเนินการสอบแก้ตัว ภาคเรียนที่ 1 ปีการศึกษา 2568</p>
                        </li>
                        <li>
                            <p>ผลการพิจารณาการรับย้ายนักเรียน ประจำภาคเรียนที่ 2 ปีการศึกษา 2568</p>
                        </li>
                        <li>
                            <p>การดำเนินการสอบแก้ตัว ภาคเรียนที่ 1 ปีการศึกษา 2568</p>
                        </li>
                        <li>
                            <p>การดำเนินการสอบแก้ตัว ภาคเรียนที่ 1 ปีการศึกษา 2568</p>
                        </li>
                        <li>
                            <p>ผลการพิจารณาการรับย้ายนักเรียน ประจำภาคเรียนที่ 2 ปีการศึกษา 2568</p>
                        </li>
                        <li>
                            <p>การดำเนินการสอบแก้ตัว ภาคเรียนที่ 1 ปีการศึกษา 2568</p>
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

        </aside>

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
                    ไม่มีรายการจองในวันนี้
                </div>
            </div>
        </div>
    </div>


    <?php require_once __DIR__ . '/public/components/x-scripts.php'; ?>
</body>

</html>

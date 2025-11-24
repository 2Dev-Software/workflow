<?php
require_once __DIR__ . '/src/Services/security/securityservice.php';
require_once __DIR__ . '/src/Services/auth/login.php';
?>
<!DOCTYPE html>
<html lang="th">
<?php require_once __DIR__ . '/public/components/x-head.php'; ?>

<body>
    <?php require_once __DIR__ . '/public/components/layout/preloader.php' ?>

    <div class="container-login-page">

        <div class="container-login-section">

            <header class="header-login">
                <div class="logo-login">
                    <img src="assets/img/favicon/deebuk-logo.png" alt="DB-logo">
                </div>
                <div class="text-header-login">
                    <h3>ระบบสำนักงานอิเล็กทรอนิกส์</h3>
                    <h3>โรงเรียนดีบุกพังงาวิทยายน</h3>
                </div>
            </header>

            <section class="form-login">
                <form action="" method="post">
                    <div class="input-id-login-group">
                        <label for="pID">เลขบัตรประชาชน</label>
                        <input type="text" name="pID" id="" placeholder="เลขบัตรประชาชน" autocomplete="username" inputmode="numeric" required>
                    </div>

                    <div class="input-password-login-group">
                        <label for="password">รหัสผ่าน</label>
                        <input type="password" name="password" id="password-toggle" placeholder="รหัสผ่าน" autocomplete="current-password" required>
                        <i class="fa-regular fa-eye-slash" id="eyeicon"></i>
                    </div>

                    <label class="remember-me-group">
                        <label for="remember-me">จดจำฉัน</label>
                        <input type="checkbox" name="remember-me" id="">
                        <span class="checkmark"></span>
                        <p>จดจำฉัน</p>
                    </label>

                    <div class="button-login-group">
                        <button type="submit">เข้าสู่ระบบ</button>
                    </div>
                </form>
            </section>

            <footer class="footer-login">
                <p>ระบบสำนักงานอิเล็กทรอนิกส์ โรงเรียนดีบุกพังงาวิทยายน</p>
                <p>DB HUB V.1.0.0 Copyright @T&T-2025 All rights reserved</p>
                <p>Paperless office พ.ศ.2568</p>
            </footer>

        </div>

        <div class="container-notification-section">

            <div class="announcement-bar">
                <i class="fa-solid fa-bullhorn"></i>
                <p>วันนี้ นายดลยวัฒน์ สันติพิทักษ์ ผู้อำนวยการโรงเรียนดีบุกพังงาวิทยายน ปฏิบัติราชการ</p>
            </div>

            <div class="news-bar">
                <div class="header-news-bar">
                    <p>ข่าวประชาสัมพันธ์</p>
                    <a href="#">ดูข่าวทั้งหมด</a>
                </div>
                
                <div class="details-news-bar">
                    <ul>
                        <li><p>การดำเนินการสอบแก้ตัว ภาคเรียนที่ 1 ปีการศึกษา 2568</p></li>
                        <li><p>การดำเนินการสอบแก้ตัว ภาคเรียนที่ 1 ปีการศึกษา 2568</p></li>
                        <li><p>การดำเนินการสอบแก้ตัว ภาคเรียนที่ 1 ปีการศึกษา 2568</p></li>
                        <li><p>การดำเนินการสอบแก้ตัว ภาคเรียนที่ 1 ปีการศึกษา 2568</p></li>
                        <li><p>การดำเนินการสอบแก้ตัว ภาคเรียนที่ 1 ปีการศึกษา 2568</p></li>
                        <li><p>การดำเนินการสอบแก้ตัว ภาคเรียนที่ 1 ปีการศึกษา 2568</p></li>
                        <li><p>การดำเนินการสอบแก้ตัว ภาคเรียนที่ 1 ปีการศึกษา 2568</p></li>
                        <li><p>การดำเนินการสอบแก้ตัว ภาคเรียนที่ 1 ปีการศึกษา 2568</p></li>
                        <li><p>การดำเนินการสอบแก้ตัว ภาคเรียนที่ 1 ปีการศึกษา 2568</p></li>
                    </ul>
                </div>
            </div>

            <div class="container-calendar">

            </div>

        </div>

    </div>

    <?php require_once __DIR__ . '/public/components/x-scripts.php'; ?>
</body>

</html>
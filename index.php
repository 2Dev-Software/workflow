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

            <div class="header-login">
                <div class="logo-login">
                    <img src="assets\img\favicon\deebuk-logo.png" alt="DB-logo">
                </div>
                <div class="text-header-login">
                    <h3>ระบบสำนักงานอิเล็กทรอนิกส์</h3>
                    <h3>โรงเรียนดีบุกพังงาวิทยายน</h3>
                </div>
            </div>

            <div class="form-login">
                <form action="" method="post">
                    <input type="text" name="" id="" placeholder="เลขบัตรประชาชน" required>
                    <input type="password" name="" id="" placeholder="รหัสผ่าน" required>
                    <input type="checkbox" name="" id="">
                    <p>จดจำฉัน</p>
                    <div class="button-login">
                        <input type="submit" value="เข้าสู่ระบบ">
                    </div>
                </form>
            </div>

            <div class="footer-login">
                <p>ระบบสำนักงานอิเล็กทรอนิกส์ โรงเรียนดีบุกพังงาวิทยายน
                    DB HUB V.1.0.0 Copyright @T&T-2025 All rights reserved</p>
                <p>Paperless office พ.ศ.2568</p>
            </div>

        </div>

        <div class="container-notification-section"></div>

    </div>

    <?php require_once __DIR__ . '/public/components/x-scripts.php'; ?>
</body>

</html>
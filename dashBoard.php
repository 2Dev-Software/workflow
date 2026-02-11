<?php
require_once __DIR__ . '/src/Services/auth/auth-guard.php';
require_once __DIR__ . '/app/modules/dashboard/metrics.php';

$current_pid = (string) ($_SESSION['pID'] ?? '');
$dashboard_counts = dashboard_counts($current_pid);
?>
<!DOCTYPE html>
<html lang="th">
<?php require_once __DIR__ . '/public/components/x-head.php'; ?>

<body>

    <?php require_once __DIR__ . '/public/components/layout/preloader.php'; ?>

    <?php require_once __DIR__ . '/public/components/partials/x-sidebar.php'; ?>

    <section class="main-section">

        <?php require_once __DIR__ . '/public/components/partials/x-navigation.php'; ?>

        <main class="content-wrapper">

            <!-- <div class="content-header">
                <h1>ยินดีต้อนรับ</h1>
                <p>หน้าหลัก</p>
            </div> -->

            <div class="dashboard-container">
                <!-- <div class="content waiting-circular">
                    <div class="content-header">
                        <p>หนังสือเวียนใหม่ที่รออ่าน</p>
                    </div>
                <div class="content-info">
                        <p><?php //(int) ($dashboard_counts['unread_circulars'] ?? 0) 
                            ?></p>
                        <i class="fa-book fa-solid"></i>
                    </div>
                </div>
                <div class="content waiting-official-order">
                    <div class="content-header">
                        <p>คำสั่งราชการใหม่ที่รออ่าน</p>
                    </div>
                <div class="content-info">
                        <p><?php //(int) ($dashboard_counts['unread_orders'] ?? 0) 
                            ?></p>
                        <i class="fa-book fa-solid"></i>
                    </div>
                </div>
                <div class="content waiting-manager">
                    <div class="content-header">
                        <p>หนังสือที่รอเสนอผู้บริหาร</p>
                    </div>
                <div class="content-info">
                        <p><?php //(int) ($dashboard_counts['pending_manager'] ?? 0) 
                            ?></p>
                        <i class="fa-book fa-solid"></i>
                    </div>
                </div>
                <div class="content waiting-approve">
                    <div class="content-header">
                        <p>หนังสือที่รอเซ็นอนุมัติ</p>
                    </div>
                <div class="content-info">
                        <p><?php //(int) ($dashboard_counts['pending_approvals'] ?? 0) 
                            ?></p>
                        <i class="fa-book fa-solid"></i>
                    </div>
                </div> -->

                <div class="dashboard-header">
                    <p><strong>แผนผังระบบสำนักงานอิเล็กทรอนิกส์</strong></p>
                </div>

                <div class="dashboard-content">
                    <a href="#">
                        <div class="card-shortcut">
                            <i class="fa-solid fa-id-card"></i>
                            <p><strong>ลงทะเบียนรับ</strong></p>
                        </div>
                    </a>
                    <a href="#">
                        <div class="card-shortcut">
                            <i class="fa-solid fa-list"></i>
                            <p><strong>บันทึกข้อความ</strong></p>
                        </div>
                    </a>
                    <a href="#">
                        <div class="card-shortcut">
                            <i class="fa-solid fa-paper-plane"></i>
                            <p><strong>ส่งหนังสือเวียน</strong></p>
                        </div>
                    </a>
                    <a href="#">
                        <div class="card-shortcut">
                            <i class="fa-solid fa-car"></i>
                            <p><strong>การจองพาหนะ</strong></p>
                        </div>
                    </a>
                    <a href="#">
                        <div class="card-shortcut">
                            <i class="fa-solid fa-building"></i>
                            <p><strong>การจองสถานที่/ห้อง</strong></p>
                        </div>
                    </a>
                    <a href="#">
                        <div class="card-shortcut">
                            <i class="fa-solid fa-user-tie"></i>
                            <p><strong>การปฎิบัติราชการของผู้บริหาร</strong></p>
                        </div>
                    </a>
                    <a href="#">
                        <div class="card-shortcut">
                            <i class="fa-solid fa-gear"></i>
                            <p><strong>การตั้งค่า</strong></p>
                        </div>
                    </a>
                    <a href="#">
                        <div class="card-shortcut">
                            <i class="fa-solid fa-file-lines"></i>
                            <p><strong>คำสั่งราชการ</strong></p>
                        </div>
                    </a>
                    <a href="#">
                        <div class="card-shortcut">
                            <i class="fa-solid fa-phone"></i>
                            <p><strong>สมุดโทรศัพท์</strong></p>
                        </div>
                    </a>
                    <a href="#">
                        <div class="card-shortcut">
                            <i class="fa-solid fa-circle-user"></i>
                            <p><strong>โปรไฟล์</strong></p>
                        </div>
                    </a>
                </div>

            </div>

        </main>

        <?php require_once __DIR__ . '/public/components/partials/x-footer.php'; ?>


    </section>


    <?php require_once __DIR__ . '/public/components/x-scripts.php'; ?>
</body>

</html>
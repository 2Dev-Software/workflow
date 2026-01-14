<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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

            <div class="content-header">
                <h1>ยินดีต้อนรับ</h1>
                <p>หน้าหลัก</p>
            </div>

            <div class="dashboard-container">
                <div class="content waiting-circular">
                    <div class="content-header">
                        <p>หนังสือเวียนใหม่ที่รออ่าน</p>
                    </div>
                    <div class="content-info">
                        <p>100</p>
                        <i class="fa-book fa-solid"></i>
                    </div>
                </div>
                <div class="content waiting-official-order">
                    <div class="content-header">
                        <p>คำสั่งราชการใหม่ที่รออ่าน</p>
                    </div>
                    <div class="content-info">
                        <p>30</p>
                        <i class="fa-book fa-solid"></i>
                    </div>
                </div>
                <div class="content waiting-manager">
                    <div class="content-header">
                        <p>หนังสือที่รอเสนอผู้บริหาร</p>
                    </div>
                    <div class="content-info">
                        <p>999+</p>
                        <i class="fa-book fa-solid"></i>
                    </div>
                </div>
                <div class="content waiting-approve">
                    <div class="content-header">
                        <p>หนังสือที่รอเซ็นอนุมัติ</p>
                    </div>
                    <div class="content-info">
                        <p>607</p>
                        <i class="fa-book fa-solid"></i>
                    </div>
                </div>
            </div>

        </main>

        <?php require_once __DIR__ . '/public/components/partials/x-footer.php'; ?>


    </section>


    <?php require_once __DIR__ . '/public/components/x-scripts.php'; ?>
</body>

</html>
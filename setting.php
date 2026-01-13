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
                <p>การตั้งค่า</p>
            </div>

            <div class="tabs-container setting-page">
                <div class="button-container">
                    <button class="tab-btn active" onclick="openTab('settingSystem', event)">การตั้งค่าระบบ</button>
                    <button class="tab-btn" onclick="openTab('settingDuty', event)">การปฏิบัติราชการของผู้บริหาร</button>
                </div>
            </div>

            <div class="content-area setting-page">

                <div id="settingSystem" class="tab-content active">

                    <div class="setting-year-container">
                        <p class="setting-title">ปีสารบรรณ</p>

                        <div class="custom-select-setting-wrapper js-year-generator">
                            <div class="custom-setting-select">
                                <div class="custom-setting-select-trigger">
                                    <p class="select-text">รอสักครู่...</p>
                                    <i class="fa-solid fa-chevron-down"></i>
                                </div>
                                <div class="custom-setting-options options-container">
                                </div>
                            </div>
                        </div>

                        <div class="button-container">
                            <button class="btn-save">บันทึก</button>
                        </div>
                    </div>

                    <div class="setting-year-container">
                        <label class="setting-title">ตั้งค่าสถานะของระบบ</label>

                        <div class="custom-select-setting-wrapper">
                            <div class="custom-setting-select">
                                <div class="custom-setting-select-trigger">
                                    <p class="select-text">กรุณาเลือกสถานะ</p>
                                    <i class="fa-solid fa-chevron-down"></i>
                                </div>

                                <div class="custom-setting-options options-container">
                                    <p class="custom-option" data-value="active">ใช้งานปกติ</p>
                                    <p class="custom-option" data-value="maintenance">ปิดปรับปรุง</p>
                                    <p class="custom-option" data-value="closed">ปิดระบบ</p>
                                </div>
                            </div>
                        </div>

                        <div class="button-container">
                            <button class="btn-save">บันทึก</button>
                        </div>
                    </div>

                </div>

                <div id="settingDuty" class="tab-content">
                    <div class="setting-duty-header">
                        <p>การปฏิบัติราชการของผู้บริหาราร</p>
                    </div>

                    <div class="duty-table-container">
                        <table class="duty-table">
                            <thead>
                                <tr>
                                    <th>ชื่อจริง-นามสกุล</th>
                                    <th>ตำแหน่ง</th>
                                    <th>สถานะ</th>
                                    <th>จัดการ</th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr>
                                    <td>นายดลยวัฒน์ สันติพิทักษ์</td>
                                    <td>ผู้อำนวยการโรงเรียน</td>
                                    <td>
                                        <p class="status-badge active">ปฏิบัติราชการ</p>
                                    </td>
                                    <td>
                                        <label class="action-check">
                                            <input type="checkbox" name="acting_duty" value="director" checked>
                                            <p>ปฏิบัติราชการ</p>
                                        </label>
                                    </td>
                                </tr>

                                <tr>
                                    <td>ดร.ยุทธนา สุวรรณวิสุทธิ์</td>
                                    <td>รองผู้อำนวยการกลุ่มบริหารงานวิชาการ</td>
                                    <td>-</td>
                                    <td>
                                        <label class="action-check">
                                            <input type="checkbox" name="acting_duty" value="1">
                                            <p>รักษาราชการแทน</p>
                                        </label>
                                    </td>
                                </tr>

                                <tr>
                                    <td>นางสาวศรัญญา ผ้วผดุง</td>
                                    <td>รองผู้อำนวยการกลุ่มบริหารงานบุคคลและงบประมาณ</td>
                                    <td>-</td>
                                    <td>
                                        <label class="action-check">
                                            <input type="checkbox" name="acting_duty" value="2">
                                            <p>รักษาราชการแทน</p>
                                        </label>
                                    </td>
                                </tr>

                                <tr>
                                    <td>นายสมชาย เจริญฤทธิ์</td>
                                    <td>รองผู้อำนวยการกลุ่มบริหารงานทั่วไป</td>
                                    <td>-</td>
                                    <td>
                                        <label class="action-check">
                                            <input type="checkbox" name="acting_duty" value="3">
                                            <p>รักษาราชการแทน</p>
                                        </label>
                                    </td>
                                </tr>

                                <tr>
                                    <td>นายไกรวิชญ์ อ่อนแก้ว</td>
                                    <td>รองผู้อำนวยการบริหารกิจการนักเรียน</td>
                                    <td>-</td>
                                    <td>
                                        <label class="action-check">
                                            <input type="checkbox" name="acting_duty" value="4">
                                            <p>รักษาราชการแทน</p>
                                        </label>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="button-container">
                        <button class="btn-save btn-save-duty">บันทึก</button>
                    </div>
                </div>
            </div>


        </main>

        <?php require_once __DIR__ . '/public/components/partials/x-footer.php'; ?>


    </section>


    <?php require_once __DIR__ . '/public/components/x-scripts.php'; ?>
</body>

</html>
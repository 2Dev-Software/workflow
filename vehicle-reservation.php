<?php

?>
<!DOCTYPE html>
<html lang="th">
<?php require_once __DIR__ . '/public/components/x-head.php'; ?>

<body>

    <?php require_once __DIR__ . '/public/components/layout/preloader.php'; ?>

    <?php //require_once __DIR__ . '/public/components/partials/x-sidebar.php'; 
    ?>

    <section class="main-section">

        <?php //require_once __DIR__ . '/public/components/partials/x-navigation.php'; 
        ?>

        <main class="content-wrapper">

            <div class="content-header">
                <h1>ยินดีต้อนรับ</h1>
                <p>การจองยานพาหนะ / บันทึกการจองยานพาหนะ</p>
            </div>

            <div class="vehicle-header">
                <p>บันทึกขอจองรถ ของ นายรัชพล ณ นคร</p>
            </div>

            <div class="vehicle-content">
                <form id="vehicleReservationForm">
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
                                    <span class="custom-option" data-value="สำนักงานเลขานุการ">สำนักงานเลขานุการ</span>
                                    <span class="custom-option" data-value="กองคลัง">กองคลัง</span>
                                    <span class="custom-option" data-value="กองช่าง">กองช่าง</span>
                                    <span class="custom-option" data-value="กองการศึกษา">กองการศึกษา</span>
                                    <span class="custom-option" data-value="กองสาธารณสุข">กองสาธารณสุข</span>
                                    <span class="custom-option" data-value="กองสวัสดิการสังคม">กองสวัสดิการสังคม</span>
                                </div>
                            </div>
                        </div>

                        <div class="vehicle-input-content">
                            <label for="writeDate">วันที่เขียน</label>
                            <input type="date" id="writeDate">
                        </div>
                    </div>

                    <div class="vehicle-row">
                        <div class="vehicle-input-content">
                            <label>ข้าพเจ้าพร้อมด้วย</label>
                            <div class="custom-select-wrapper" id="traveler-wrapper">
                                <input type="hidden" id="travelers" name="travelers" value="">

                                <div class="custom-select-trigger">
                                    <span class="select-value">เลือกผู้เดินทางร่วม</span>
                                    <i class="fa-solid fa-chevron-down arrow"></i>
                                </div>

                                <div class="custom-options">
                                    <span class="custom-option" data-value="สำนักงานเลขานุการ">สำนักงานเลขานุการ</span>
                                    <span class="custom-option" data-value="กองคลัง">กองคลัง</span>
                                    <span class="custom-option" data-value="กองช่าง">กองช่าง</span>
                                    <span class="custom-option" data-value="กองการศึกษา">กองการศึกษา</span>
                                </div>
                            </div>
                            <button class="show-member">
                                <p>แสดงผู้เดินทางทั้งหมด</p>
                            </button>
                        </div>
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

                    <div class="vehicle-row">
                        <div class="vehicle-input-content">
                            <label for="purpose">ขออนุญาตใช้รถเพื่อ</label>
                            <input type="text" id="purpose" placeholder="ระบุวัตถุประสงค์" required>
                        </div>

                        <div class="vehicle-input-content">
                            <label for="location">ณ (สถานที่)</label>
                            <input type="text" id="location" placeholder="ระบุสถานที่ปลายทาง" required>
                        </div>
                    </div>

                    <div class="vehicle-row">
                        <div class="vehicle-input-content with-unit">
                            <label for="passengerCount">มีคนนั่งจำนวน</label>
                            <div class="input-with-unit">
                                <input type="number" id="passengerCount" placeholder="จำนวน" min="1">
                            </div>
                        </div>

                        <div class="vehicle-input-content">
                            <label for="startDate">ในวันที่</label>
                            <input type="date" id="startDate">
                        </div>

                        <div class="vehicle-input-content">
                            <label for="endDate">ถึงวันที่</label>
                            <input type="date" id="endDate">
                        </div>

                        <div class="vehicle-input-content">
                            <label>จำนวนวัน</label>
                            <div class="calculated-field" id="dayCount">
                                <i class="fa-regular fa-calendar"></i>
                                <p>-</p>
                            </div>
                        </div>
                    </div>

                    <div class="vehicle-row">
                        <div class="vehicle-input-content">
                            <label for="startTime">เวลาเริ่มต้น</label>
                            <input type="time" id="startTime">
                        </div>

                        <div class="vehicle-input-content">
                            <label for="endTime">เวลาสิ้นสุด</label>
                            <input type="time" id="endTime">
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

                    <div class="vehicle-row">
                        <div class="vehicle-input-content">
                            <label>แนบเอกสาร</label>
                            <div>
                                <label class="file-upload" for="attachment">
                                    <i class="fa-solid fa-upload"></i>
                                    <span id="fileLabel">เลือกไฟล์</span>
                                </label>
                                <input type="file" id="attachment" class="file-input">
                                <span class="file-name"></span>
                            </div>
                        </div>
                    </div>

                    <div class="submit-section">
                        <button type="submit" class="btn-submit">บันทึกจองยานพาหนะ</button>
                    </div>
                </form>

        </main>

        <?php require_once __DIR__ . '/public/components/partials/x-footer.php'; ?>


    </section>


    <?php require_once __DIR__ . '/public/components/x-scripts.php'; ?>
</body>

</html>
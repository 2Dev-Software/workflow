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
                <p>หนังสือเวียน / ส่งหนังสือเวียน</p>
            </div>

            <div class="container-circular-notice-sending">
                <div class="form-group">
                    <label for="">หัวเรื่อง</label>
                    <input type="text" name="" id="" placeholder="กรุณากรอกหัวเรื่อง">
                </div>

                <div class="form-group">
                    <label for="">รายละเอียด</label>
                    <textarea name="" id="" rows="4" placeholder="กรุณากรอกรายละเอียด"></textarea>
                </div>

                <div class="form-group">
                    <label>อัปโหลดไฟล์เอกสาร</label>
                    <section class="upload-layout">
                        <input type="file" id="fileInput" multiple accept=".pdf,.png,.jpg,.jpeg" style="display: none;" />

                        <div class="upload-box" id="dropzone" onclick="document.getElementById('fileInput').click()">
                            <i class="fa-solid fa-upload"></i>
                            <p>ลากไฟล์มาวางที่นี่</p>
                        </div>

                        <div class="file-list" id="fileListContainer"></div>
                    </section>
                </div>

                <div class="row form-group">
                    <button class="btn btn-upload-small" onclick="document.getElementById('fileInput').click()">
                        <p>เพิ่มไฟล์</p>
                    </button>
                    <div class="file-hint">
                        <p>* แนบไฟล์ได้สูงสุด 5 ไฟล์ (รวม PNG และ PDF) *</p>
                    </div>
                </div>

                <div id="imagePreviewModal" class="modal-overlay-preview">
                    <span class="close-preview" id="closePreviewBtn">&times;</span>
                    <img class="preview-content" id="previewImage">
                    <div id="previewCaption"></div>
                </div>

                <div class="form-group">
                    <label for="">แนบลิ้งก์</label>
                    <input type="text" id="" placeholder="กรุณาแนบลิ้งก์ที่เกี่ยวข้อง" />
                </div>

                <div class="form-group sending">
                    <label for="">ผู้ส่ง :</label>
                    <p>นายพลพรพล ทองขาวจั๊วะทองขาวจั๊วะทองขาว</p>
                </div>

                <div class="form-group receive">
                    <label for="">ผู้รับ :</label>
                    <div class="dropdown-container">

                        <div class="search-input-wrapper">
                            <input type="text" id="mainInput" class="search-input"
                                placeholder="ค้นหา หรือ เลือกข้อมูล..." autocomplete="off">
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>

                        <div class="dropdown-content" id="dropdownContent">

                            <div class="dropdown-header">
                                <label class="select-all-box">
                                    <input type="checkbox" id="selectAll">เลือกทั้งหมด
                                </label>
                            </div>

                            <div class="dropdown-list">

                                <div class="dropdown-list">

                                    <div class="category-group">
                                        <div class="category-title">
                                            <label><input type="checkbox" class="category-checkbox">กลุ่มสาระฯ คณิตศาสตร์</label>
                                        </div>
                                        <div class="category-items">
                                            <label class="item">
                                                <input type="checkbox" class="item-checkbox" value="">นายสมชาย ใจดี (หัวหน้าหมวด)
                                            </label>
                                            <label class="item">
                                                <input type="checkbox" class="item-checkbox" value="">นางสาวสมหญิง รักคณิต
                                            </label>
                                            <label class="item">
                                                <input type="checkbox" class="item-checkbox" value="">นายพีระ พีระมิด
                                            </label>
                                            <label class="item">
                                                <input type="checkbox" class="item-checkbox" value="">นางวรารัตน์ เก่งเลข
                                            </label>
                                        </div>
                                    </div>

                                    <div class="category-group">
                                        <div class="category-title">
                                            <label><input type="checkbox" class="category-checkbox">กลุ่มสาระฯ วิทยาศาสตร์และเทคโนโลยี</label>
                                        </div>
                                        <div class="category-items">
                                            <label class="item">
                                                <input type="checkbox" class="item-checkbox" value="">ดร.วิชัย ไอน์สไตน์
                                            </label>
                                            <label class="item">
                                                <input type="checkbox" class="item-checkbox" value="">นายคอมพิวเตอร์ เซิร์ฟเวอร์
                                            </label>
                                            <label class="item">
                                                <input type="checkbox" class="item-checkbox" value="">นางสาวเคมี อินทรีย์
                                            </label>
                                            <label class="item">
                                                <input type="checkbox" class="item-checkbox" value="">นายชีวะ รักโลก
                                            </label>
                                            <label class="item">
                                                <input type="checkbox" class="item-checkbox" value="">นางสาวฟิสิกส์ แรงโน้มถ่วง
                                            </label>
                                        </div>
                                    </div>

                                    <div class="category-group">
                                        <div class="category-title">
                                            <label><input type="checkbox" class="category-checkbox">กลุ่มสาระฯ ภาษาไทย</label>
                                        </div>
                                        <div class="category-items">
                                            <label class="item">
                                                <input type="checkbox" class="item-checkbox" value="">นายรักชาติ ยิ่งชีพ
                                            </label>
                                            <label class="item">
                                                <input type="checkbox" class="item-checkbox" value="">นางวรรณคดี มีเสน่ห์
                                            </label>
                                            <label class="item">
                                                <input type="checkbox" class="item-checkbox" value="">นางสาวกานดา กาพย์กลอน
                                            </label>
                                        </div>
                                    </div>

                                    <div class="category-group">
                                        <div class="category-title">
                                            <label><input type="checkbox" class="category-checkbox">กลุ่มสาระฯ ภาษาต่างประเทศ</label>
                                        </div>
                                        <div class="category-items">
                                            <label class="item">
                                                <input type="checkbox" class="item-checkbox" value="">Mr. John Smith
                                            </label>
                                            <label class="item">
                                                <input type="checkbox" class="item-checkbox" value="">นางสาวปราณี อิงลิช
                                            </label>
                                            <label class="item">
                                                <input type="checkbox" class="item-checkbox" value="">เหล่าซือ หวัง เฉิน
                                            </label>
                                        </div>
                                    </div>

                                    <div class="category-group">
                                        <div class="category-title">
                                            <label><input type="checkbox" class="category-checkbox">กลุ่มบริหารงานทั่วไป</label>
                                        </div>
                                        <div class="category-items">
                                            <label class="item">
                                                <input type="checkbox" class="item-checkbox" value="">ผอ.อำนวย การศึกษา
                                            </label>
                                            <label class="item">
                                                <input type="checkbox" class="item-checkbox" value="">รองฯ ฝ่ายปกครอง
                                            </label>
                                            <label class="item">
                                                <input type="checkbox" class="item-checkbox" value="">เจ้าหน้าที่ธุรการ
                                            </label>
                                        </div>
                                    </div>

                                </div>

                            </div>
                        </div>
                    </div>
                    <div class="sent-notice-selected">
                        <button id="btnShowRecipients" type="button">
                            <p>แสดงผู้รับทั้งหมด</p>
                        </button>
                    </div>
                </div>

                <button id="btnSendNotice" class="sent-notice-btn">
                    <p>ส่งหนังสือเวียน</p>
                </button>

                <div id="confirmModal" class="modal-overlay-confirm">
                    <div class="confirm-box">
                        <div class="confirm-header">
                            <div class="icon-circle">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                            </div>
                        </div>
                        <div class="confirm-body">
                            <h3>ยืนยันการส่งหนังสือเวียน</h3>
                            <div class="confirm-actions">
                                <button id="btnConfirmYes" class="btn-yes">ยืนยัน</button>
                                <button id="btnConfirmNo" class="btn-no">ยกเลิก</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="recipientModal" class="modal-overlay-recipient">
                    <div class="modal-container">
                        <div class="modal-header">
                            <div class="modal-title">
                                <i class="fa-solid fa-users"></i>
                                <span>รายชื่อผู้รับหนังสือเวียน</span>
                            </div>
                            <button class="modal-close" id="closeModalBtn">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                        <div class="modal-body">
                            <table class="recipient-table">
                                <thead>
                                    <tr>
                                        <th>ลำดับ</th>
                                        <th>ชื่อจริง-นามสกุล</th>
                                        <th>กลุ่ม/ฝ่าย</th>
                                    </tr>
                                </thead>
                                <tbody id="recipientTableBody">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>


        </main>

        <?php require_once __DIR__ . '/public/components/partials/x-footer.php'; ?>


    </section>


    <?php require_once __DIR__ . '/public/components/x-scripts.php'; ?>
</body>

</html>
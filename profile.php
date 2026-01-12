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
                <p>โปรไฟล์</p>
            </div>

            <div class="profile-header-banner">
                <img src="assets/img/db-background.png" alt="db-background">
            </div>

            <div class="profile-header">
                <div class="profile-pic-wrapper" onclick="openImageModal()">
                    <div class="profile-pic" id="mainProfilePic">
                        <div class="profile-overlay" onclick="document.getElementById('profileFileInput').click()">
                            <i class="fa-solid fa-camera"></i>
                        </div>
                    </div>
                </div>
                <div class="user-name">นายรัชพลพรภร ณ ณ ณ DE VOUE ธูปทองทองทอง</div>
            </div>

            <div class="modal-overlay hidden" id="imageModal">
                <div class="modal-content upload-modal">
                    <div class="modal-body upload-body">
                        <div class="preview-container" id="previewContainer">
                            <img id="imagePreview" src="#" alt="Preview" class="hidden">
                            <p id="previewPlaceholder">ตัวอย่างรูปภาพ</p>
                        </div>

                        <input type="file" id="profileFileInput" hidden accept="image/png, image/jpeg" onchange="previewProfileImage(this)">

                        <div class="file-hint">รองรับไฟล์ .jpg, .png</div>

                        <div class="modal-button-content upload-actions">
                            <button class="btn-confirm" onclick="confirmImageChange()">ยืนยัน</button>
                            <button class="btn-confirm btn-cancel" onclick="closeImageModal()">ยกเลิก</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tabs-container">
                <div class="button-container">
                    <button class="tab-btn active" onclick="openTab('personal', event)">ข้อมูลส่วนบุคคล</button>
                    <button class="tab-btn" onclick="openTab('signature', event)">ลายเซ็น</button>
                    <button class="tab-btn" onclick="openTab('password', event)">เปลี่ยนรหัสผ่าน</button>
                </div>
            </div>

            <div class="content-area">

                <div id="personal" class="tab-content active">
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">กลุ่มสาระฯ :</div>
                            <input class="info-value" value="สังคมศึกษา ศาสนาและวัฒนธรรม" disabled />
                        </div>
                        <div class="info-item">
                            <div class="info-label">บทบาท :</div>
                            <input class="info-value" value="คนเก็บขยะหน้าโรงเรียน" disabled />
                        </div>
                        <div class="info-item">
                            <div class="info-label">ตำแหน่ง :</div>
                            <input class="info-value" value="ครู (หัวหน้ากลุ่มสาระฯ)" disabled />
                        </div>
                        <div class="info-item">
                            <div class="info-label">วิทยฐานะ :</div>
                            <input class="info-value" value="ชำนาญการพิเศษ" disabled />
                        </div>
                        <div class="info-item">
                            <div class="info-label">กลุ่มงาน/ฝ่าย :</div>
                            <input class="info-value" value="ฝ่ายวิชาการ" disabled />
                        </div>
                        <div class="info-item">
                            <div class="info-label">เบอร์โทรศัพท์ :</div>
                            <input class="tel-info-value" type="text" id="phoneInput" placeholder="กรอกเบอร์โทร" maxlength="10" />
                        </div>
                    </div>
                    <div class="warning-text">*หากข้อมูลส่วนบุคคลผิดพลาด กรุณาติดต่อผู้ดูแลระบบ*</div>
                </div>

                <div class="tel-modal" id="confirmModal">
                    <div class="modal-content">
                        <p>ยืนยันเบอร์โทรหรือไม่</p>
                        <p id="showPhone"></p>
                        <div class="modal-button-content">
                            <button id="confirmBtn">ยืนยัน</button>
                            <button class="cancel" id="cancelBtn">ยกเลิก</button>
                        </div>
                    </div>
                </div>

                <div id="signature" class="tab-content">
                    <div class="signature-content">
                        <div class="signature-box" id="mainSignatureBox">
                            <img id="mainSignatureImg" src="" alt="Signature" class="hidden">
                            <p id="noSignatureText">ไม่มีลายเซ็นในระบบ</p>
                        </div>
                        <div class="signature-field">
                            <button class="btn-upload" onclick="document.getElementById('signatureFileInput').click()">แนบลายเซ็น</button>
                            <div class="file-hint">ไฟล์นามสกุล .jpg , .png เท่านั้น</div>
                        </div>
                    </div>
                </div>

                <div class="modal-overlay hidden" id="signatureModal">
                    <div class="modal-content upload-modal">

                        <div class="modal-body upload-body">
                            <div class="preview-container rectangle-preview" id="signaturePreviewContainer">
                                <img id="signaturePreview" src="#" alt="Signature Preview" class="hidden">
                            </div>

                            <input type="file" id="signatureFileInput" hidden accept="image/png, image/jpeg" onchange="handleSignatureSelect(this)">

                            <div class="modal-button-content upload-actions">
                                <button class="btn-confirm" onclick="confirmSignatureChange()">ยืนยัน</button>
                                <button class="btn-confirm btn-cancel" onclick="closeSignatureModal()">ยกเลิก</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="password" class="tab-content">
                    <form class="password-form">
                        <div class="form-group">
                            <label class="form-label">รหัสผ่านเดิม</label>
                            <input type="password" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">รหัสผ่านใหม่</label>
                            <input type="password" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">ยืนยันรหัสผ่าน</label>
                            <input type="password" class="form-input">
                        </div>
                        <button type="button" class="btn-confirm">ยืนยัน</button>
                    </form>
                </div>

            </div>

        </main>

        <?php require_once __DIR__ . '/public/components/partials/x-footer.php'; ?>


    </section>


    <?php require_once __DIR__ . '/public/components/x-scripts.php'; ?>
</body>

</html>
<?php
require_once __DIR__ . '/src/Services/auth/auth-guard.php';
require_once __DIR__ . '/src/Services/security/security-service.php';
require_once __DIR__ . '/src/Services/teacher/teacher-signature-upload.php';
require_once __DIR__ . '/src/Services/teacher/teacher-password-change.php';
require_once __DIR__ . '/src/Services/teacher/teacher-profile.php';

$active_tab = $_GET['tab'] ?? 'personal';
$allowed_tabs = ['personal', 'signature', 'password'];
if (!in_array($active_tab, $allowed_tabs, true)) {
    $active_tab = 'personal';
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
                <div class="user-name"><?= htmlspecialchars($teacher['fName'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
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
                    <button class="tab-btn<?= $active_tab === 'personal' ? ' active' : '' ?>" onclick="openTab('personal', event)">ข้อมูลส่วนบุคคล</button>
                    <button class="tab-btn<?= $active_tab === 'signature' ? ' active' : '' ?>" onclick="openTab('signature', event)">ลายเซ็น</button>
                    <button class="tab-btn<?= $active_tab === 'password' ? ' active' : '' ?>" onclick="openTab('password', event)">เปลี่ยนรหัสผ่าน</button>
                </div>
            </div>

            <div class="content-area">

                <div id="personal" class="tab-content<?= $active_tab === 'personal' ? ' active' : '' ?>">
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">กลุ่มสาระฯ :</div>
                            <input class="info-value" value="<?= htmlspecialchars($teacher['department_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" disabled />
                        </div>
                        <div class="info-item">
                            <div class="info-label">หน้าที่ :</div>
                            <input class="info-value" value="<?= htmlspecialchars($teacher['role_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" disabled />
                        </div>
                        <div class="info-item">
                            <div class="info-label">ตำแหน่ง :</div>
                            <input class="info-value" value="<?= htmlspecialchars($teacher['position_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" disabled />
                        </div>
                        <div class="info-item">
                            <div class="info-label">วิทยฐานะ :</div>
                            <input class="info-value" value="<?= htmlspecialchars($teacher['level_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" disabled />
                        </div>
                        <div class="info-item">
                            <div class="info-label">กลุ่มงาน/ฝ่าย :</div>
                            <input class="info-value" value="<?= htmlspecialchars($teacher['faction_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" disabled />
                        </div>
                        <div class="info-item">
                            <div class="info-label">เบอร์โทรศัพท์ :</div>
                            <input class="tel-info-value" type="text" id="phoneInput" value="<?= htmlspecialchars($teacher['telephone'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="กรอกเบอร์โทรศัพท์" inputmode="numeric" pattern="\d{10}" maxlength="10" oninput="this.value=this.value.replace(/\D/g,'').slice(0,10)" />
                        </div>
                    </div>
                    <div class="warning-text">* หากข้อมูลส่วนบุคคลผิดพลาด กรุณาติดต่อผู้ดูแลระบบ *</div>
                </div>

                <div class="tel-modal" id="confirmModal">
                    <div class="modal-content">
                        <p><?= empty($teacher['telephone']) ? 'ต้องการเพิ่มหมายเลขโทรศัพท์ใช่หรือไม่' : 'ต้องการเปลี่ยนหมายเลขโทรศัพท์ใช่หรือไม่' ?></p>
                        <p id="showPhone"></p>
                        <div class="modal-button-content">
                            <button id="confirmBtn">ยืนยัน</button>
                            <button class="cancel" id="cancelBtn">ยกเลิก</button>
                        </div>
                    </div>
                </div>

                <div id="signature" class="tab-content<?= $active_tab === 'signature' ? ' active' : '' ?>">
                    <div class="signature-content">
                        <div class="signature-box" id="mainSignatureBox">
                            <?php $signature_path = $teacher['signature'] ?? ''; ?>
                            <img id="mainSignatureImg" src="<?= htmlspecialchars($signature_path, ENT_QUOTES, 'UTF-8') ?>" alt="Signature" class="<?= $signature_path !== '' ? '' : 'hidden' ?>">
                            <p id="noSignatureText"<?= $signature_path !== '' ? ' style="display:none;"' : '' ?>>ไม่มีลายเซ็นในระบบ</p>
                        </div>
                        <div class="signature-field">
                            <button class="btn-upload" onclick="document.getElementById('signatureFileInput').click()">
                                <?= $signature_path !== '' ? 'เปลี่ยนลายเซ็น' : 'แนบลายเซ็น' ?>
                            </button>
                            <div class="file-hint">รองรับไฟล์นามสกุล .JPG , .PNG เท่านั้น ขนาดไม่เกิน 2MB</div>
                        </div>
                    </div>
                </div>

                <div class="modal-overlay hidden" id="signatureModal">
                    <div class="modal-content upload-modal">
                        <form class="modal-body upload-body" id="signatureUploadForm" method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') ?>" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="signature_upload" value="1">
                            <div class="preview-container rectangle-preview" id="signaturePreviewContainer">
                                <img id="signaturePreview" src="#" alt="Signature Preview" class="hidden">
                            </div>

                            <input type="file" id="signatureFileInput" name="signature_file" hidden accept="image/png, image/jpeg" onchange="handleSignatureSelect(this)" required>

                            <div class="modal-button-content upload-actions">
                                <button type="submit" class="btn-confirm">ยืนยัน</button>
                                <button type="button" class="btn-confirm btn-cancel" onclick="closeSignatureModal()">ยกเลิก</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div id="password" class="tab-content<?= $active_tab === 'password' ? ' active' : '' ?>">
                    <form class="password-form" method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                        <div class="form-group">
                            <label class="form-label">รหัสผ่านเดิม</label>
                            <input type="password" class="form-input" name="current_password" autocomplete="current-password" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">รหัสผ่านใหม่</label>
                            <input type="password" class="form-input" name="new_password" autocomplete="new-password" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">ยืนยันรหัสผ่าน</label>
                            <input type="password" class="form-input" name="confirm_password" autocomplete="new-password" required>
                        </div>
                        <button type="submit" class="btn-confirm" name="change_password" value="1">ยืนยัน</button>
                    </form>
                </div>

            </div>

        </main>

        <?php require_once __DIR__ . '/public/components/partials/x-footer.php'; ?>


    </section>


    <?php require_once __DIR__ . '/public/components/x-scripts.php'; ?>
</body>

</html>

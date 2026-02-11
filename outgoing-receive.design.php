<?php

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
                <p>ลงทะเบียนรับหนังสือนอกระบบ</p>
            </div>

            <div class="content-outgoing">
                <form action="">

                    <div class="type-urgent">
                        <p><strong>ประเภท: </strong></p>
                        <div class="radio-group-urgent">
                            <input type="radio" name="urgency" id="">
                            <p class="urgency-status normal">ปกติ</p>
                            <input type="radio" name="urgency" id="">
                            <p class="urgency-status urgen">ด่วน</p>
                            <input type="radio" name="urgency" id="">
                            <p class="urgency-status very-urgen">ด่วนมาก</p>
                            <input type="radio" name="urgency" id="">
                            <p class="urgency-status extremly-urgen">ด่วนที่สุด</p>
                        </div>
                    </div>

                    <div class="form-group-row">
                        <div class="input-group">
                            <p><strong>เลขที่หนังสือ</strong></p>
                            <input type="text" name="" id="">
                        </div>
                        <div class="input-group">
                            <p><strong>ลงวันที่</strong></p>
                            <input type="date" name="" id="">
                        </div>
                    </div>

                    <div class="form-group-row">
                        <div class="input-group">
                            <p><strong>เรื่อง</strong></p>
                            <textarea name="" id="" rows="3"></textarea>
                        </div>
                    </div>

                    <div class="form-group-row">
                        <div class="input-group">
                            <p><strong>จาก</strong></p>
                            <input type="text" name="" id="">
                        </div>
                        <div class="input-group">
                            <p><strong>หนังสือของกลุ่ม</strong></p>
                            <div class="custom-select-wrapper">
                                <div class="custom-select-trigger">
                                    <p id="select-value">ทุกสถานะ</p>
                                    <i class="fa-solid fa-chevron-down"></i>
                                </div>

                                <div class="custom-options">
                                    <div class="custom-option" data-value="">ทุกสถานะ</div>
                                    <div class="custom-option" data-value="">พร้อมใช้งาน</div>
                                    <div class="custom-option" data-value="">ระงับชั่วคราว</div>
                                    <div class="custom-option" data-value="">กำลังซ่อม</div>
                                    <div class="custom-option" data-value="">ไม่พร้อมใช้งาน</div>
                                </div>

                                <select class="form-input" name="status">
                                    <option value="all">ทุกสถานะ</option>
                                    <option value="pending" <?= ($vehicle_approval_status ?? '') === 'pending' ? 'selected' : '' ?>>ทุกสถานะ</option>
                                    <option value="approved" <?= ($vehicle_approval_status ?? '') === 'approved' ? 'selected' : '' ?>>พร้อมใช้งาน</option>
                                    <option value="rejected" <?= ($vehicle_approval_status ?? '') === 'rejected' ? 'selected' : '' ?>>ระงับชั่วคราว</option>
                                    <option value="repairing" <?= ($vehicle_approval_status ?? '') === 'repairing' ? 'selected' : '' ?>>กำลังซ่อม</option>
                                    <option value="unavailable" <?= ($vehicle_approval_status ?? '') === 'unavailable' ? 'selected' : '' ?>>ไม่พร้อมใช้งาน</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group-row">
                        <div class="input-group">
                            <p><strong>เกษียณหนังสือ: </strong>เรียน ผู้อำนวยการโรงเรียนดีบุกพังงาวิทยายน</p>
                            <textarea name="memo_detail" id="memo_editor"></textarea>
                        </div>
                    </div>

                    <div class="form-group-row">
                        <div class="input-group">
                            <p><strong>เสนอ</strong></p>
                            <div class="go-with-dropdown">
                                <input type="text" id="searchInput" placeholder="ค้นหารายชื่อคุณครู" autocomplete="off"
                                    onkeyup="filterDropdown()" onclick="openDropdown()" />

                                <div id="myDropdown" class="go-with-dropdown-content">
                                    <label class="dropdown-item">
                                        <input type="checkbox"
                                            name="companionIds[]"
                                            value="<?= htmlspecialchars($teacher_item['id'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-name="<?= htmlspecialchars($teacher_item['name'], ENT_QUOTES, 'UTF-8') ?>">
                                        <p>หัวหน้า</p>
                                    </label>
                                    <label class="dropdown-item">
                                        <input type="checkbox"
                                            name="companionIds[]"
                                            value="<?= htmlspecialchars($teacher_item['id'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-name="<?= htmlspecialchars($teacher_item['name'], ENT_QUOTES, 'UTF-8') ?>">
                                        <p>รองผู้อำนวยการ</p>
                                    </label>
                                    <label class="dropdown-item">
                                        <input type="checkbox"
                                            name="companionIds[]"
                                            value="<?= htmlspecialchars($teacher_item['id'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-name="<?= htmlspecialchars($teacher_item['name'], ENT_QUOTES, 'UTF-8') ?>">
                                        <p>ผู้อำนวยการ</p>
                                    </label>
                                    <label class="dropdown-item">
                                        <input type="checkbox"
                                            name="companionIds[]"
                                            value="<?= htmlspecialchars($teacher_item['id'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-name="<?= htmlspecialchars($teacher_item['name'], ENT_QUOTES, 'UTF-8') ?>">
                                        <p>รักษาการ</p>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="form-group-row">
                        <div class="input-group">

                            <p><strong>หนังสือนำ</strong></p>
                            <!-- <div class="vehicle-row file-sec"> -->
                            <!-- <div class="vehicle-input-content"> -->
                            <!-- <label>แนบเอกสาร <strong>(แนบเอกสารได้สูงสุด 1 ไฟล์)</strong></label> -->

                            <div>
                                <button type="button" class="btn btn-upload-small"
                                    onclick="document.getElementById('cover_attachment').click()">
                                    <p>เพิ่มไฟล์</p>
                                </button>
                            </div>
                            <input type="file" id="cover_attachment" name="cover_attachments[]" class="file-input" multiple
                                accept=".pdf,image/png,image/jpeg" hidden>
                            <!-- </div> -->
                             
                            <div class="file-list" id="cover_attachmentList" aria-live="polite"></div>
                            <!-- </div> -->

                        </div>
                    </div>

                    <hr>

                    <div class="form-group-row">
                        <div class="input-group">

                            <p><strong>เอกสารแนบ</strong></p>
                            <!-- <div class="vehicle-row file-sec"> -->
                            <!-- <div class="vehicle-input-content"> -->
                            <!-- <label>แนบเอกสาร <strong>(แนบเอกสารได้สูงสุด 4 ไฟล์)</strong></label> -->
                            <div>
                                <button type="button" class="btn btn-upload-small"
                                    onclick="document.getElementById('attachment').click()">
                                    <p>เพิ่มไฟล์</p>
                                </button>
                            </div>
                            <input type="file" id="attachment" name="attachments[]" class="file-input" multiple
                                accept=".pdf,image/png,image/jpeg" hidden>
                            <p class="form-error hidden" id="attachmentError">แนบได้สูงสุด 4 ไฟล์</p>
                            <!-- </div> -->

                            <div class="file-list" id="attachmentList" aria-live="polite"></div>
                            <!-- </div> -->

                        </div>
                    </div>

                    <hr>

                    <div class="form-group-row">
                        <div class="input-group">
                            <button class="submit"><p>บันทึกเอกสาร</p></button>
                        </div>
                    </div>

                </form>

            </div>

        </main>

        <?php require_once __DIR__ . '/public/components/partials/x-footer.php'; ?>


    </section>


    <?php require_once __DIR__ . '/public/components/x-scripts.php'; ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js"></script>

    <script>
        tinymce.init({
            selector: '#memo_editor',
            height: 500,
            menubar: false,
            language: 'th_TH',

            plugins: 'searchreplace autolink directionality visualblocks visualchars image link media codesample table charmap pagebreak nonbreaking anchor insertdatetime advlist lists wordcount help charmap emoticons',

            toolbar: 'undo redo | fontfamily | fontsize | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | outdent indent |  numlist bullist | forecolor backcolor removeformat | pagebreak | charmap emoticons',

            font_family_formats: 'TH Sarabun New=Sarabun, sans-serif;',

            font_size_formats: '8pt 9pt 10pt 12pt 14pt 16pt 18pt 20pt 22pt 24pt 26pt 36pt 48pt 72pt',

            content_style: `
                @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&display=swap');
                
                body { 
                    font-family: 'Sarabun', sans-serif; 
                    font-size: 16pt; 
                    line-height: 1.5;
                    color: #000;
                    background-color: #fff;
                    padding: 0 20px;
                    margin: 0 auto;
                }
                p { 
                    margin-bottom: 0px;
                }
            `,

            nonbreaking_force_tab: true,
            promotion: false,
            branding: false
        });

        document.addEventListener('DOMContentLoaded', function() {

            function setupFileUpload(inputId, listId, maxFiles = 1) {
                const fileInput = document.getElementById(inputId);
                const fileList = document.getElementById(listId);
                const previewModal = document.getElementById('imagePreviewModal');
                const previewImage = document.getElementById('previewImage');
                const previewCaption = document.getElementById('previewCaption');
                const closePreviewBtn = document.getElementById('closePreviewBtn');

                const allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
                let selectedFiles = [];

                if (!fileInput || !fileList) return;

                const renderFiles = () => {
                    fileList.innerHTML = '';
                    
                    if (selectedFiles.length === 0) {
                        fileList.innerHTML = `
                            <div style="
                                background-color: #f0f4fa; 
                                border: 1px dashed #ced4da; 
                                border-radius: 6px; 
                                padding: 15px; 
                                text-align: center; 
                                color: #6c757d;
                                font-size: 14px;
                                margin-top: 10px;
                            ">
                                ยังไม่มีไฟล์แนบ
                            </div>
                        `;
                        return;
                    }

                    selectedFiles.forEach((file, index) => {
                        const wrapper = document.createElement('div');
                        wrapper.className = 'file-item-wrapper';

                        const deleteBtn = document.createElement('button');
                        deleteBtn.type = 'button';
                        deleteBtn.className = 'delete-btn';
                        deleteBtn.innerHTML = '<i class="fa-solid fa-trash"></i>';
                        deleteBtn.addEventListener('click', () => {
                            selectedFiles = selectedFiles.filter((_, i) => i !== index);
                            syncFiles();
                            renderFiles();
                        });

                        const banner = document.createElement('div');
                        banner.className = 'file-banner';

                        const info = document.createElement('div');
                        info.className = 'file-info';

                        const icon = document.createElement('div');
                        icon.className = 'file-icon';
                        icon.innerHTML = file.type === 'application/pdf' ?
                            '<i class="fa-solid fa-file-pdf"></i>' :
                            '<i class="fa-solid fa-image"></i>';

                        const text = document.createElement('div');
                        text.className = 'file-text';

                        const name = document.createElement('div');
                        name.className = 'file-name';
                        name.textContent = file.name;

                        const type = document.createElement('div');
                        type.className = 'file-type';
                        type.textContent = (file.size / 1024 / 1024).toFixed(2) + ' MB';

                        text.appendChild(name);
                        text.appendChild(type);

                        info.appendChild(icon);
                        info.appendChild(text);

                        const actions = document.createElement('div');
                        actions.className = 'file-actions';

                        const view = document.createElement('a');
                        view.href = '#';
                        view.innerHTML = '<i class="fa-solid fa-eye"></i>';
                        view.addEventListener('click', (e) => {
                            e.preventDefault();
                            if (file.type.startsWith('image/')) {
                                const reader = new FileReader();
                                reader.onload = () => {
                                    if (previewImage) previewImage.src = reader.result;
                                    if (previewCaption) previewCaption.textContent = file.name;
                                    previewModal?.classList.add('active');
                                };
                                reader.readAsDataURL(file);
                            } else {
                                const url = URL.createObjectURL(file);
                                window.open(url, '_blank', 'noopener');
                                setTimeout(() => URL.revokeObjectURL(url), 1000);
                            }
                        });

                        actions.appendChild(view);
                        banner.appendChild(info);
                        banner.appendChild(actions);

                        wrapper.appendChild(deleteBtn);
                        wrapper.appendChild(banner);
                        fileList.appendChild(wrapper);
                    });
                };

                const syncFiles = () => {
                    const dt = new DataTransfer();
                    selectedFiles.forEach((file) => dt.items.add(file));
                    fileInput.files = dt.files;
                };

                const addFiles = (files) => {
                    if (!files) return;
                    const existing = new Set(selectedFiles.map((file) => `${file.name}-${file.size}`));

                    Array.from(files).forEach((file) => {
                        const key = `${file.name}-${file.size}`;
                        if (existing.has(key)) return;
                        if (!allowedTypes.includes(file.type)) {
                            alert('รองรับเฉพาะไฟล์ PDF, JPG และ PNG');
                            return;
                        }
                        if (selectedFiles.length >= maxFiles) {
                            alert(`แนบไฟล์ได้สูงสุด ${maxFiles} ไฟล์`);
                            return;
                        }
                        selectedFiles.push(file);
                        existing.add(key);
                    });
                    syncFiles();
                    renderFiles();
                };

                fileInput.addEventListener('change', (e) => {
                    addFiles(e.target.files);
                });

                if (closePreviewBtn) {
                    closePreviewBtn.addEventListener('click', () => previewModal?.classList.remove('active'));
                }
                if (previewModal) {
                    previewModal.addEventListener('click', (e) => {
                        if (e.target === previewModal) previewModal.classList.remove('active');
                    });
                }

                renderFiles(); 
            }

            setupFileUpload('cover_attachment', 'cover_attachmentList', 1);
            setupFileUpload('attachment', 'attachmentList', 5);

            const circularType = document.getElementById('circular_type');
        });
        
    </script>
</body>

</html>
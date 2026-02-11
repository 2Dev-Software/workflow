<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../auth/csrf.php';

$values = $values ?? ['writeDate' => '', 'subject' => '', 'detail' => ''];
$current_user = (array) ($current_user ?? []);
$factions = (array) ($factions ?? []);

$selected_sender_fid = trim((string) ($values['sender_fid'] ?? ''));
if ($selected_sender_fid === '' && !empty($factions)) {
    $selected_sender_fid = (string) ($factions[0]['fID'] ?? '');
}

$signature_src = trim((string) ($current_user['signature'] ?? ''));
$current_name = trim((string) ($current_user['fName'] ?? ''));
$current_position = trim((string) ($current_user['position_name'] ?? ''));

ob_start();
?>
<div class="content-header">
    <h1>ยินดีต้อนรับ</h1>
    <p>บันทึกข้อความ</p>
</div>

<div class="content-memo">
    <div class="memo-header">
        <img src="assets/img/garuda-logo.png" alt="">
        <p>บันทึกข้อความ</p>
        <div></div>
    </div>

    <form method="POST" enctype="multipart/form-data" id="circularComposeForm">
        <?= csrf_field() ?>
        <input type="hidden" name="flow_mode" value="CHAIN">
        <input type="hidden" name="to_choice" value="DIRECTOR">

        <div class="memo-detail">
            <div class="form-group-row">
                <p><strong>ส่วนราชการ</strong></p>

                <div class="custom-select-wrapper">
                    <div class="custom-select-trigger">
                        <p class="select-value">
                            <?php
                            $selected_faction_name = '';
                            foreach ($factions as $faction) {
                                if ((string) ($faction['fID'] ?? '') === $selected_sender_fid) {
                                    $selected_faction_name = (string) ($faction['fname'] ?? '');
                                    break;
                                }
                            }
                            echo h($selected_faction_name !== '' ? $selected_faction_name : 'เลือกส่วนราชการ');
                            ?>
                        </p>
                        <i class="fa-solid fa-chevron-down"></i>
                    </div>

                    <div class="custom-options">
                        <?php foreach ($factions as $faction) : ?>
                            <?php $fid = (string) ($faction['fID'] ?? ''); ?>
                            <div class="custom-option<?= $fid === $selected_sender_fid ? ' selected' : '' ?>" data-value="<?= h($fid) ?>">
                                <?= h((string) ($faction['fname'] ?? '')) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <input type="hidden" name="sender_fid" value="<?= h($selected_sender_fid) ?>">
                </div>

                <p><strong>โรงเรียนดีบุกพังงาวิทยายน</strong></p>
            </div>

            <div class="form-group-row">
                <section>
                    <p><strong>ที่</strong></p>
                    <p>รวย 1101/101</p>
                </section>

                <section>
                    <p><strong>วันที่</strong></p>
                    <input type="date" name="writeDate" value="<?= h((string) ($values['writeDate'] ?? '')) ?>">
                </section>
            </div>

            <div class="form-group-row">
                <p><strong>เรื่อง</strong></p>
                <input type="text" name="subject" value="<?= h((string) ($values['subject'] ?? '')) ?>" required>
            </div>

            <div class="form-group-row">
                <p><strong>เรียน</strong></p>
                <p>ผู้อำนวยการโรงเรียนดีบุกพังงาวิทยายน</p>
            </div>

            <div class="content-editor">
                <p><strong>รายละเอียด:</strong></p>
                <textarea name="detail" id="memo_editor"><?= h((string) ($values['detail'] ?? '')) ?></textarea>
            </div>

            <div class="form-group file-sec">
                <label><strong>อัปโหลดไฟล์เอกสาร</strong> (ถ้ามี)</label>
                <section class="upload-layout">
                    <input type="file" id="fileInput" name="attachments[]" multiple accept="application/pdf,image/png,image/jpeg" style="display: none;" />

                    <div class="upload-box" id="dropzone">
                        <i class="fa-solid fa-upload"></i>
                        <p>ลากไฟล์มาวางที่นี่</p>
                    </div>

                    <div class="file-list" id="fileListContainer"></div>
                </section>
            </div>

            <div class="row form-group">
                <button class="btn btn-upload-small" type="button" id="btnAddFiles">
                    <p>เพิ่มไฟล์</p>
                </button>
            </div>

            <div class="form-group-row signature">
                <img src="<?= h($signature_src) ?>" alt="">
                <p>(<?= h($current_name !== '' ? $current_name : '-') ?>)</p>
                <p><?= h($current_position !== '' ? $current_position : '-') ?></p>
            </div>

            <div class="form-group-row submit">
                <button type="submit">บันทึกเอกสาร</button>
            </div>
        </div>
    </form>
</div>

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
    const fileInput = document.getElementById('fileInput');
    const fileList = document.getElementById('fileListContainer');
    const dropzone = document.getElementById('dropzone');
    const addFilesBtn = document.getElementById('btnAddFiles');

    const maxFiles = 5;
    const allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
    let selectedFiles = [];

    const renderFiles = () => {
        if (!fileList) return;
        fileList.innerHTML = '';
        if (selectedFiles.length === 0) return;

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
            icon.innerHTML = file.type === 'application/pdf'
                ? '<i class="fa-solid fa-file-pdf"></i>'
                : '<i class="fa-solid fa-image"></i>';

            const text = document.createElement('div');
            text.className = 'file-text';

            const name = document.createElement('div');
            name.className = 'file-name';
            name.textContent = file.name;

            const type = document.createElement('div');
            type.className = 'file-type';
            type.textContent = file.type || 'ไฟล์แนบ';

            text.appendChild(name);
            text.appendChild(type);

            info.appendChild(icon);
            info.appendChild(text);
            banner.appendChild(info);
            wrapper.appendChild(deleteBtn);
            wrapper.appendChild(banner);
            fileList.appendChild(wrapper);
        });
    };

    const syncFiles = () => {
        if (!fileInput) return;
        const dt = new DataTransfer();
        selectedFiles.forEach((file) => dt.items.add(file));
        fileInput.files = dt.files;
    };

    const addFiles = (files) => {
        if (!files) return;
        const existing = new Set(selectedFiles.map((file) => `${file.name}-${file.size}-${file.lastModified}`));
        Array.from(files).forEach((file) => {
            const key = `${file.name}-${file.size}-${file.lastModified}`;
            if (existing.has(key)) return;
            if (!allowedTypes.includes(file.type)) return;
            if (selectedFiles.length >= maxFiles) return;
            selectedFiles.push(file);
            existing.add(key);
        });
        syncFiles();
        renderFiles();
    };

    if (fileInput) {
        fileInput.addEventListener('change', (e) => {
            addFiles(e.target.files);
        });
    }

    if (dropzone) {
        dropzone.addEventListener('click', () => fileInput?.click());
        dropzone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropzone.classList.add('active');
        });
        dropzone.addEventListener('dragleave', () => dropzone.classList.remove('active'));
        dropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropzone.classList.remove('active');
            addFiles(e.dataTransfer?.files || []);
        });
    }

    addFilesBtn?.addEventListener('click', () => fileInput?.click());
});
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';

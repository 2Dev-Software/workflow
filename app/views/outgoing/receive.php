<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../auth/csrf.php';

$values = (array) ($values ?? []);
$values = array_merge([
    'extPriority' => 'ปกติ',
    'extBookNo' => '',
    'extIssuedDate' => '',
    'subject' => '',
    'extFromText' => '',
    'extGroupFID' => '',
    'linkURL' => '',
    'detail' => '',
    'reviewerPID' => '',
], $values);
$factions = (array) ($factions ?? []);
$reviewers = (array) ($reviewers ?? []);
$is_edit_mode = (bool) ($is_edit_mode ?? false);
$edit_circular_id = (int) ($edit_circular_id ?? 0);
$existing_attachments = (array) ($existing_attachments ?? []);
$has_reviewer_options = !empty($reviewers);

$selected_group_name = 'เลือกกลุ่ม/ฝ่าย';
foreach ($factions as $faction_item) {
    if ((string) ($faction_item['fID'] ?? '') === (string) ($values['extGroupFID'] ?? '')) {
        $selected_group_name = (string) ($faction_item['fName'] ?? $selected_group_name);
        break;
    }
}

$selected_reviewer_name = 'เลือกผู้พิจารณา';
foreach ($reviewers as $reviewer_item) {
    if ((string) ($reviewer_item['pID'] ?? '') === (string) ($values['reviewerPID'] ?? '')) {
        $selected_reviewer_name = (string) ($reviewer_item['label'] ?? $selected_reviewer_name);
        break;
    }
}

ob_start();
?>
<div class="content-header">
    <h1>ยินดีต้อนรับ</h1>
    <p>ลงทะเบียนรับหนังสือเวียนภายนอก</p>
</div>

<div class="content-outgoing">
    <form action="" method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <?php if ($is_edit_mode && $edit_circular_id > 0) : ?>
            <input type="hidden" name="edit_circular_id" value="<?= h((string) $edit_circular_id) ?>">
        <?php endif; ?>

        <?php if ($is_edit_mode) : ?>
            <div class="enterprise-panel">
                <p><strong>โหมดแก้ไข:</strong> เอกสารถูกดึงกลับแล้ว สามารถแก้ไขและส่งใหม่ก่อนผู้บริหารพิจารณา</p>
            </div>
        <?php endif; ?>

        <div class="type-urgent">
            <p><strong>ประเภท: </strong></p>
            <div class="radio-group-urgent">
                <input type="radio" name="extPriority" value="ปกติ" <?= (string) $values['extPriority'] === 'ปกติ' ? 'checked' : '' ?>>
                <p class="urgency-status normal">ปกติ</p>
                <input type="radio" name="extPriority" value="ด่วน" <?= (string) $values['extPriority'] === 'ด่วน' ? 'checked' : '' ?>>
                <p class="urgency-status urgen">ด่วน</p>
                <input type="radio" name="extPriority" value="ด่วนมาก" <?= (string) $values['extPriority'] === 'ด่วนมาก' ? 'checked' : '' ?>>
                <p class="urgency-status very-urgen">ด่วนมาก</p>
                <input type="radio" name="extPriority" value="ด่วนที่สุด" <?= (string) $values['extPriority'] === 'ด่วนที่สุด' ? 'checked' : '' ?>>
                <p class="urgency-status extremly-urgen">ด่วนที่สุด</p>
            </div>
        </div>

        <div class="form-group-row">
            <div class="input-group">
                <p><strong>เลขที่หนังสือ</strong></p>
                <input type="text" name="extBookNo" value="<?= h((string) $values['extBookNo']) ?>" required>
            </div>
            <div class="input-group">
                <p><strong>ลงวันที่</strong></p>
                <input type="date" name="extIssuedDate" value="<?= h((string) $values['extIssuedDate']) ?>" required>
            </div>
        </div>

        <div class="form-group-row">
            <div class="input-group">
                <p><strong>เรื่อง</strong></p>
                <textarea name="subject" rows="3" required><?= h((string) $values['subject']) ?></textarea>
            </div>
        </div>

        <div class="form-group-row">
            <div class="input-group">
                <p><strong>จาก</strong></p>
                <input type="text" name="extFromText" value="<?= h((string) $values['extFromText']) ?>" required>
            </div>
            <div class="input-group">
                <p><strong>หนังสือของกลุ่ม</strong></p>
                <div class="custom-select-wrapper">
                    <div class="custom-select-trigger">
                        <p class="select-value"><?= h($selected_group_name) ?></p>
                        <i class="fa-solid fa-chevron-down"></i>
                    </div>

                    <div class="custom-options">
                        <div class="custom-option<?= (string) $values['extGroupFID'] === '' ? ' selected' : '' ?>" data-value="">เลือกกลุ่ม/ฝ่าย</div>
                        <?php foreach ($factions as $faction_item) : ?>
                            <?php $fid = (int) ($faction_item['fID'] ?? 0); ?>
                            <?php if ($fid <= 0) { continue; } ?>
                            <div class="custom-option<?= (string) $values['extGroupFID'] === (string) $fid ? ' selected' : '' ?>" data-value="<?= h((string) $fid) ?>"><?= h((string) ($faction_item['fName'] ?? '')) ?></div>
                        <?php endforeach; ?>
                    </div>

                    <select class="form-input" name="extGroupFID">
                        <option value="" <?= (string) $values['extGroupFID'] === '' ? 'selected' : '' ?>>เลือกกลุ่ม/ฝ่าย</option>
                        <?php foreach ($factions as $faction_item) : ?>
                            <?php $fid = (int) ($faction_item['fID'] ?? 0); ?>
                            <?php if ($fid <= 0) { continue; } ?>
                            <option value="<?= h((string) $fid) ?>" <?= (string) $values['extGroupFID'] === (string) $fid ? 'selected' : '' ?>><?= h((string) ($faction_item['fName'] ?? '')) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-group-row">
            <div class="input-group">
                <p><strong>เกษียณหนังสือ: </strong>เรียน ผู้อำนวยการโรงเรียนดีบุกพังงาวิทยายน</p>
                <textarea name="detail" id="memo_editor"><?= h((string) $values['detail']) ?></textarea>
            </div>
        </div>

        <div class="form-group-row">
            <div class="input-group">
                <p><strong>แนบลิงก์ (ถ้ามี)</strong></p>
                <input type="url" name="linkURL" value="<?= h((string) $values['linkURL']) ?>" placeholder="https://example.com">
            </div>
        </div>

        <div class="form-group-row">
            <div class="input-group">
                <p><strong>เสนอ</strong></p>
                <div class="custom-select-wrapper">
                    <div class="custom-select-trigger">
                        <p class="select-value"><?= h($selected_reviewer_name) ?></p>
                        <i class="fa-solid fa-chevron-down"></i>
                    </div>

                    <div class="custom-options">
                        <div class="custom-option<?= (string) $values['reviewerPID'] === '' ? ' selected' : '' ?>" data-value="">เลือกผู้พิจารณา</div>
                        <?php foreach ($reviewers as $reviewer_item) : ?>
                            <?php $reviewer_pid = trim((string) ($reviewer_item['pID'] ?? '')); ?>
                            <?php if ($reviewer_pid === '') { continue; } ?>
                            <div class="custom-option<?= (string) $values['reviewerPID'] === $reviewer_pid ? ' selected' : '' ?>" data-value="<?= h($reviewer_pid) ?>"><?= h((string) ($reviewer_item['label'] ?? '')) ?></div>
                        <?php endforeach; ?>
                    </div>

                    <select class="form-input" name="reviewerPID" required>
                        <option value="" <?= (string) $values['reviewerPID'] === '' ? 'selected' : '' ?>>เลือกผู้พิจารณา</option>
                        <?php foreach ($reviewers as $reviewer_item) : ?>
                            <?php $reviewer_pid = trim((string) ($reviewer_item['pID'] ?? '')); ?>
                            <?php if ($reviewer_pid === '') { continue; } ?>
                            <option value="<?= h($reviewer_pid) ?>" <?= (string) $values['reviewerPID'] === $reviewer_pid ? 'selected' : '' ?>><?= h((string) ($reviewer_item['label'] ?? '')) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if (!$has_reviewer_options) : ?>
                    <p class="form-error" style="display:block;">ไม่พบผู้พิจารณาในระบบ กรุณาติดต่อผู้ดูแลระบบ</p>
                <?php endif; ?>
            </div>
        </div>

        <hr>

        <div class="form-group-row">
            <div class="input-group">
                <p><strong>หนังสือนำ</strong></p>
                <div>
                    <button
                        type="button"
                        class="btn btn-upload-small"
                        onclick="document.getElementById('cover_attachment').click()">
                        <p>เพิ่มไฟล์</p>
                    </button>
                </div>
                <input
                    type="file"
                    id="cover_attachment"
                    name="cover_attachments[]"
                    class="file-input"
                    multiple
                    accept=".pdf,image/png,image/jpeg"
                    hidden>

                <div class="file-list" id="cover_attachmentList" aria-live="polite"></div>
            </div>
        </div>

        <hr>

        <div class="form-group-row">
            <div class="input-group">
                <p><strong>เอกสารแนบ</strong></p>
                <div>
                    <button
                        type="button"
                        class="btn btn-upload-small"
                        onclick="document.getElementById('attachment').click()">
                        <p>เพิ่มไฟล์</p>
                    </button>
                </div>
                <input
                    type="file"
                    id="attachment"
                    name="attachments[]"
                    class="file-input"
                    multiple
                    accept=".pdf,image/png,image/jpeg"
                    hidden>
                <p class="form-error hidden" id="attachmentError">แนบได้สูงสุด 4 ไฟล์</p>

                <div class="file-list" id="attachmentList" aria-live="polite"></div>
            </div>
        </div>

        <?php if ($is_edit_mode && !empty($existing_attachments)) : ?>
            <hr>
            <div class="form-group-row">
                <div class="input-group">
                    <p><strong>ไฟล์แนบเดิม (เลือกเพื่อลบก่อนส่งใหม่)</strong></p>
                    <div class="enterprise-panel">
                        <?php foreach ($existing_attachments as $attachment) : ?>
                            <?php
                            $attachment_file_id = (int) ($attachment['fileID'] ?? 0);
                            $attachment_name = (string) ($attachment['fileName'] ?? '');
                            ?>
                            <label style="display:flex;align-items:center;gap:8px;padding:6px 0;">
                                <input type="checkbox" name="remove_file_ids[]" value="<?= h((string) $attachment_file_id) ?>">
                                <span><?= h($attachment_name !== '' ? $attachment_name : ('ไฟล์ #' . $attachment_file_id)) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <hr>

        <div class="form-group-row">
            <div class="input-group">
                <button class="submit" type="submit" <?= $has_reviewer_options ? '' : 'disabled' ?>>
                    <p><?= $is_edit_mode ? 'บันทึกแก้ไขและส่งใหม่' : 'บันทึกเอกสาร' ?></p>
                </button>
            </div>
        </div>
    </form>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js"></script>
<script>
  tinymce.init({
    selector: "#memo_editor",
    height: 500,
    menubar: false,
    language: "th_TH",
    plugins:
      "searchreplace autolink directionality visualblocks visualchars image link media codesample table charmap pagebreak nonbreaking anchor insertdatetime advlist lists wordcount help charmap emoticons",
    toolbar:
      "undo redo | fontfamily | fontsize | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | outdent indent |  numlist bullist | forecolor backcolor removeformat | pagebreak | charmap emoticons",
    font_family_formats: "TH Sarabun New=Sarabun, sans-serif;",
    font_size_formats:
      "8pt 9pt 10pt 12pt 14pt 16pt 18pt 20pt 22pt 24pt 26pt 36pt 48pt 72pt",
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
    branding: false,
  });

  document.addEventListener("DOMContentLoaded", function () {
    function setupFileUpload(inputId, listId, maxFiles = 1) {
      const fileInput = document.getElementById(inputId);
      const fileList = document.getElementById(listId);
      const previewModal = document.getElementById("imagePreviewModal");
      const previewImage = document.getElementById("previewImage");
      const previewCaption = document.getElementById("previewCaption");
      const closePreviewBtn = document.getElementById("closePreviewBtn");
      const allowedTypes = ["application/pdf", "image/jpeg", "image/png"];
      let selectedFiles = [];

      if (!fileInput || !fileList) return;

      const renderFiles = () => {
        fileList.innerHTML = "";

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
          const wrapper = document.createElement("div");
          wrapper.className = "file-item-wrapper";

          const deleteBtn = document.createElement("button");
          deleteBtn.type = "button";
          deleteBtn.className = "delete-btn";
          deleteBtn.innerHTML = '<i class="fa-solid fa-trash"></i>';
          deleteBtn.addEventListener("click", () => {
            selectedFiles = selectedFiles.filter((_, i) => i !== index);
            syncFiles();
            renderFiles();
          });

          const banner = document.createElement("div");
          banner.className = "file-banner";

          const info = document.createElement("div");
          info.className = "file-info";

          const icon = document.createElement("div");
          icon.className = "file-icon";
          icon.innerHTML =
            file.type === "application/pdf"
              ? '<i class="fa-solid fa-file-pdf"></i>'
              : '<i class="fa-solid fa-image"></i>';

          const text = document.createElement("div");
          text.className = "file-text";

          const name = document.createElement("div");
          name.className = "file-name";
          name.textContent = file.name;

          const type = document.createElement("div");
          type.className = "file-type";
          type.textContent = (file.size / 1024 / 1024).toFixed(2) + " MB";

          text.appendChild(name);
          text.appendChild(type);
          info.appendChild(icon);
          info.appendChild(text);

          const actions = document.createElement("div");
          actions.className = "file-actions";

          const view = document.createElement("a");
          view.href = "#";
          view.innerHTML = '<i class="fa-solid fa-eye"></i>';
          view.addEventListener("click", (e) => {
            e.preventDefault();
            if (file.type.startsWith("image/")) {
              const reader = new FileReader();
              reader.onload = () => {
                if (previewImage) previewImage.src = reader.result;
                if (previewCaption) previewCaption.textContent = file.name;
                previewModal?.classList.add("active");
              };
              reader.readAsDataURL(file);
            } else {
              const url = URL.createObjectURL(file);
              window.open(url, "_blank", "noopener");
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
            alert("รองรับเฉพาะไฟล์ PDF, JPG และ PNG");
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

      fileInput.addEventListener("change", (e) => {
        addFiles(e.target.files);
      });

      if (closePreviewBtn) {
        closePreviewBtn.addEventListener("click", () => previewModal?.classList.remove("active"));
      }
      if (previewModal) {
        previewModal.addEventListener("click", (e) => {
          if (e.target === previewModal) previewModal.classList.remove("active");
        });
      }

      renderFiles();
    }

    setupFileUpload("cover_attachment", "cover_attachmentList", 1);
    setupFileUpload("attachment", "attachmentList", 4);
  });
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';

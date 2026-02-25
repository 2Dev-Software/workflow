<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../auth/csrf.php';

$values = (array) ($values ?? []);
$display_order_no = trim((string) ($display_order_no ?? ''));
$issuer_name = trim((string) ($issuer_name ?? ''));
$faction_options = (array) ($faction_options ?? []);
$edit_order = $edit_order ?? null;
$edit_order_id = (int) ($edit_order_id ?? 0);
$is_edit_mode = $edit_order_id > 0 && !empty($edit_order);

$values = array_merge([
    'subject' => '',
    'effective_date' => '',
    'order_date' => '',
    'group_fid' => '',
], $values);

$page_title = $is_edit_mode ? 'แก้ไขข้อมูลออกเลขคำสั่งราชการ' : 'ออกเลขคำสั่งราชการ';
$page_subtitle = $is_edit_mode
    ? 'แก้ไขข้อมูลเลขคำสั่งที่ยังไม่ส่ง'
    : 'คำสั่งราชการ / ออกเลขคำสั่งราชการ';
$submit_label = $is_edit_mode ? 'บันทึกการแก้ไข' : 'บันทึกออกเลข';

ob_start();
?>
<div class="content-header">
    <h1><?= h($page_title) ?></h1>
    <p><?= h($page_subtitle) ?></p>
</div>

<div class="tabs-container setting-page">
    <div class="button-container vehicle">
        <button class="tab-btn <?= $is_track_active ? '' : 'active' ?>"
            onclick="openTab('orderReceive', event)">ออกเลขคำสั่ง</button>
        <button class="tab-btn <?= $is_track_active ? 'active' : '' ?>" onclick="openTab('orderMine', event)">เลขคำสั่งของฉัน</button>
    </div>
</div>

<div class="content-order create tab-content active" id="orderReceive">
    <div class="form-group row">
        <div class="input-group">
            <p><strong>คำสั่งที่</strong></p>
            <input type="text" disabled>
        </div>
        <div class="input-group">
            <p><strong>เรื่อง</strong></p>
            <input type="text" placeholder="ระบุหัวข้อคำสั่ง">
        </div>
    </div>

    <div class="form-group row">
        <div class="input-group">
            <p><strong>ทั้งนี้ตั้งแต่วันที่</strong></p>
            <input type="date" name="" id="">
        </div>
        <div class="input-group">
            <p><strong>สั่ง ณ วันที่</strong></p>
            <input type="date" name="" id="">
        </div>
    </div>

    <div class="form-group row">
        <div class="input-group">
            <p><strong>ผู้ออกเลขคำสั่ง</strong></p>
            <input type="text" disabled>
        </div>
        <div class="input-group">
            <p><strong>กลุ่ม</strong></p>
            <div class="custom-select-wrapper">
                <div class="custom-select-trigger">
                    <p class="select-value"><?= h($selected_reviewer_name) ?></p>
                    <i class="fa-solid fa-chevron-down"></i>
                </div>

                <div class="custom-options">
                    <div class="custom-option">A</div>
                    <div class="custom-option">B</div>
                    <div class="custom-option">C</div>
                    <div class="custom-option">D</div>
                    <div class="custom-option">E</div>
                </div>

                <select class="form-input" name="reviewerPID" required>
                    <option value="">A</option>
                    <option value="">B</option>
                    <option value="">C</option>
                    <option value="">D</option>
                    <option value="">E</option>
                </select>
            </div>
        </div>
    </div>

    <div class="form-group button">
        <div class="input-group">
            <button class="submit" type="submit">
                <p>บันทึกเอกสาร</p>
            </button>
        </div>
    </div>

</div>

<section class="enterprise-card tab-content <?= $is_track_active ? 'active' : '' ?>" id="orderMine">
    <div class="enterprise-card-header">
        <div class="enterprise-card-title-group">
            <h2 class="enterprise-card-title">ค้นหาและกรองรายการ</h2>
        </div>
    </div>

    <form method="GET" class="circular-my-filter-grid">
        <input type="hidden" name="tab" value="track">
        <div class="approval-filter-group">
            <div class="room-admin-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input class="form-input" type="search" name="q" value="<?= h($filter_query) ?>"
                    placeholder="ค้นหาชื่อผู้จอง/ห้อง/หัวข้อ" autocomplete="off">
            </div>
            <div class="room-admin-filter">
                <div class="custom-select-wrapper">
                    <div class="custom-select-trigger">
                        <p class="select-value">
                            <?php
                            $status_label = 'ทั้งหมด';

                            if ($filter_status === strtolower(INTERNAL_STATUS_SENT)) {
                                $status_label = 'ส่งแล้ว';
                            } elseif ($filter_status === strtolower(INTERNAL_STATUS_RECALLED)) {
                                $status_label = 'ดึงกลับ';
                            }
                            echo h($status_label);
                            ?>
                        </p>
                        <i class="fa-solid fa-chevron-down"></i>
                    </div>

                    <div class="custom-options">
                        <div class="custom-option" data-value="all">ทั้งหมด</div>
                        <div class="custom-option" data-value="<?= h(strtolower(INTERNAL_STATUS_SENT)) ?>">ส่งแล้ว</div>
                        <div class="custom-option" data-value="<?= h(strtolower(INTERNAL_STATUS_RECALLED)) ?>">ดึงกลับ</div>
                    </div>

                    <select class="form-input" name="status">
                        <option value="all" <?= $filter_status === 'all' ? 'selected' : '' ?>>ทั้งหมด</option>
                        <option value="<?= h(strtolower(INTERNAL_STATUS_SENT)) ?>" <?= $filter_status === strtolower(INTERNAL_STATUS_SENT) ? 'selected' : '' ?>>ส่งแล้ว</option>
                        <option value="<?= h(strtolower(INTERNAL_STATUS_RECALLED)) ?>" <?= $filter_status === strtolower(INTERNAL_STATUS_RECALLED) ? 'selected' : '' ?>>ดึงกลับ</option>
                    </select>
                </div>
            </div>
            <div class="room-admin-filter">
                <div class="custom-select-wrapper">
                    <div class="custom-select-trigger">
                        <p class="select-value"><?= h($filter_sort === 'oldest' ? 'เก่าไปใหม่' : 'ใหม่ไปเก่า') ?></p>
                        <i class="fa-solid fa-chevron-down"></i>
                    </div>

                    <div class="custom-options">
                        <div class="custom-option" data-value="newest">ใหม่ไปเก่า</div>
                        <div class="custom-option" data-value="oldest">เก่าไปใหม่</div>
                    </div>

                    <select class="form-input" name="sort">
                        <option value="newest" <?= $filter_sort === 'newest' ? 'selected' : '' ?>>ใหม่ไปเก่า</option>
                        <option value="oldest" <?= $filter_sort === 'oldest' ? 'selected' : '' ?>>เก่าไปใหม่</option>
                    </select>
                </div>
            </div>
        </div>
    </form>

    <div class="enterprise-card-header">
        <div class="enterprise-card-title-group">
            <h2 class="enterprise-card-title">รายการหนังสือเวียนของฉัน</h2>
        </div>
    </div>

    <div class="table-responsive circular-my-table-wrap">
        <table class="custom-table circular-my-table">
            <thead>
                <tr>
                    <th>เรื่อง</th>
                    <th>สถานะ</th>
                    <th>อ่านแล้ว/ทั้งหมด</th>
                    <th>วันที่ส่ง</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($sent_items)) : ?>
                    <tr>
                        <td colspan="5" class="enterprise-empty">ไม่มีรายการหนังสือเวียนตามเงื่อนไข</td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($sent_items as $item) : ?>
                        <?php
                        $circular_id = (int) ($item['circularID'] ?? 0);
                        $status_key = strtoupper(trim((string) ($item['status'] ?? '')));
                        $status_meta = $status_map[$status_key] ?? ['label' => ($status_key !== '' ? $status_key : '-'), 'pill' => 'pending'];
                        $item_type = strtoupper((string) ($item['circularType'] ?? ''));
                        $read_count = (int) ($item['readCount'] ?? 0);
                        $recipient_count = (int) ($item['recipientCount'] ?? 0);
                        $created_at = (string) ($item['createdAt'] ?? '');
                        $date_display = $format_thai_datetime($created_at);
                        $date_long_display = $format_thai_date_long($created_at);
                        $sender_faction_name = (string) ($item['senderFactionName'] ?? '');
                        $detail_row = (array) ($detail_map[$circular_id] ?? []);
                        $detail_text = trim((string) ($detail_row['detail'] ?? ''));
                        $detail_sender_name = trim((string) ($detail_row['senderName'] ?? ''));
                        $detail_sender_faction = trim((string) ($detail_row['senderFactionName'] ?? $sender_faction_name));
                        $attachments = (array) ($detail_row['files'] ?? []);
                        $files_json = json_encode($attachments, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                        if ($files_json === false) {
                            $files_json = '[]';
                        }
                        $consider_class = 'considering';

                        if (in_array($status_key, [INTERNAL_STATUS_RECALLED], true)) {
                            $consider_class = 'considered';
                        } elseif (in_array($status_key, [INTERNAL_STATUS_SENT, INTERNAL_STATUS_ARCHIVED], true)) {
                            $consider_class = 'success';
                        }
                        $stats_rows = [];
                        $has_any_read = $read_count > 0;

                        foreach ((array) ($read_stats_map[$circular_id] ?? []) as $stat) {
                            $is_read = (int) ($stat['isRead'] ?? 0) === 1;

                            if ($is_read) {
                                $has_any_read = true;
                            }
                            $stats_rows[] = [
                                'name' => (string) ($stat['fName'] ?? '-'),
                                'status' => $is_read ? 'อ่านแล้ว' : 'ยังไม่อ่าน',
                                'pill' => $is_read ? 'approved' : 'pending',
                                'readAt' => $is_read ? $format_thai_datetime((string) ($stat['readAt'] ?? '')) : '-',
                            ];
                        }
                        $stats_json = json_encode($stats_rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                        if ($stats_json === false) {
                            $stats_json = '[]';
                        }
                        ?>
                        <tr>
                            <td>
                                <div class="circular-my-subject"><?= h((string) ($item['subject'] ?? '-')) ?></div>
                                <?php if (!empty($item['senderFactionName'])) : ?>
                                    <div class="circular-my-meta">ในนาม <?= h((string) $item['senderFactionName']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-pill <?= h((string) ($status_meta['pill'] ?? 'pending')) ?>"><?= h((string) ($status_meta['label'] ?? '-')) ?></span>
                            </td>
                            <td><?= h((string) $read_count) ?>/<?= h((string) $recipient_count) ?></td>
                            <td><?= h($date_display) ?></td>
                            <td>
                                <div class="circular-my-actions">
                                    <?php if ($item_type === 'INTERNAL' && $status_key === INTERNAL_STATUS_SENT && !$has_any_read) : ?>
                                        <form method="POST">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="recall">
                                            <input type="hidden" name="circular_id" value="<?= h((string) $circular_id) ?>">
                                            <button type="submit" class="booking-action-btn secondary">
                                                <i class="fa-solid fa-rotate-left"></i>
                                                <span class="tooltip">ดึงกลับ</span>
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($item_type === 'INTERNAL' && $status_key === INTERNAL_STATUS_RECALLED) : ?>
                                        <form method="POST">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="resend">
                                            <input type="hidden" name="circular_id" value="<?= h((string) $circular_id) ?>">
                                            <button type="submit" class="booking-action-btn secondary">
                                                <i class="fa-solid fa-arrow-right-from-bracket"></i>
                                                <span class="tooltip">ส่งใหม่</span>
                                            </button>
                                        </form>
                                        <a class="c-button c-button--sm booking-action-btn secondary" href="circular-compose.php?edit=<?= h((string) $circular_id) ?>">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                            <span class="tooltip">แก้ไข</span>
                                        </a>
                                    <?php endif; ?>

                                    <button
                                        class="booking-action-btn secondary js-open-circular-modal"
                                        type="button"
                                        data-circular-id="<?= h((string) $circular_id) ?>"
                                        data-type="<?= h($item_type) ?>"
                                        data-subject="<?= h((string) ($item['subject'] ?? '-')) ?>"
                                        data-detail="<?= h($detail_text) ?>"
                                        data-sender-name="<?= h($detail_sender_name !== '' ? $detail_sender_name : $sender_name) ?>"
                                        data-sender-faction="<?= h($detail_sender_faction !== '' ? $detail_sender_faction : $sender_faction_display) ?>"
                                        data-bookno="<?= h('#' . (string) $circular_id) ?>"
                                        data-issued="<?= h($date_long_display) ?>"
                                        data-from="<?= h(($detail_sender_name !== '' ? $detail_sender_name : $sender_name) . (($detail_sender_faction !== '' ? $detail_sender_faction : $sender_faction_display) !== '' ? (' / ' . ($detail_sender_faction !== '' ? $detail_sender_faction : $sender_faction_display)) : '')) ?>"
                                        data-to="<?= h('ผู้รับทั้งหมด ' . (string) $recipient_count . ' คน') ?>"
                                        data-status="<?= h((string) ($status_meta['label'] ?? '-')) ?>"
                                        data-consider="<?= h($consider_class) ?>"
                                        data-received-time="<?= h($date_display) ?>"
                                        data-files="<?= h($files_json) ?>"
                                        data-read-stats="<?= h($stats_json) ?>">
                                        <i class="fa-solid fa-eye"></i>
                                        <span class="tooltip">ดูรายละเอียด</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php
    // $pagination_url = $build_url(['page' => null]);
    // component_render('pagination', [
    //     'page' => $page,
    //     'total_pages' => $total_pages,
    //     'base_url' => $pagination_url,
    //     'class' => 'u-mt-2',
    // ]);
    ?>
</section>

<script>
    document.addEventListener("DOMContentLoaded", function() {
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
                        file.type === "application/pdf" ?
                        '<i class="fa-solid fa-file-pdf"></i>' :
                        '<i class="fa-solid fa-image"></i>';

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

        setupFileUpload("cover_attachment_modal", "cover_attachmentList_modal", 1);
        setupFileUpload("attachment_modal", "attachmentList_modal", 4);

        setupFileUpload("cover_attachment_edit", "cover_attachmentList_edit", 1);
        setupFileUpload("attachment_edit", "attachmentList_edit", 4);

        const viewModal = document.getElementById('modalViewOverlay');
        const editModal = document.getElementById('modalEditOverlay');

        const closeViewBtn = document.getElementById('closeModalView');
        const closeEditBtn = document.getElementById('closeModalEdit');

        const openViewBtns = document.querySelectorAll('.js-open-view-modal');
        const openEditBtns = document.querySelectorAll('.js-open-edit-modal');

        openViewBtns.forEach((btn) => {
            btn.addEventListener('click', (event) => {
                event.preventDefault();

                if (viewModal) viewModal.style.display = 'flex';
            });
        });

        openEditBtns.forEach((btn) => {
            btn.addEventListener('click', (event) => {
                event.preventDefault();

                if (editModal) editModal.style.display = 'flex';
            });
        });

        closeViewBtn?.addEventListener('click', () => {
            if (viewModal) viewModal.style.display = 'none';
        });

        closeEditBtn?.addEventListener('click', () => {
            if (editModal) editModal.style.display = 'none';
        });

        window.addEventListener('click', (event) => {
            if (event.target === viewModal) {
                viewModal.style.display = 'none';
            }
            if (event.target === editModal) {
                editModal.style.display = 'none';
            }
        });
    });
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';

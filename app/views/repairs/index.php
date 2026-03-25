<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../auth/csrf.php';

$values = (array) ($values ?? []);
$requests = (array) ($requests ?? []);
$current_pid = (string) ($current_pid ?? '');
$page = (int) ($page ?? 1);
$total_pages = (int) ($total_pages ?? 1);
$total_count = (int) ($total_count ?? 0);
$page_count = count($requests);
$view_item = $view_item ?? null;
$view_attachments = (array) ($view_attachments ?? []);
$edit_item = $edit_item ?? null;
$edit_attachments = (array) ($edit_attachments ?? []);
$is_editing = $edit_item !== null;
$mode = (string) ($mode ?? 'report');
$base_url = (string) ($base_url ?? 'repairs.php');
$page_title = (string) ($page_title ?? 'แจ้งเหตุซ่อมแซม');
$page_subtitle = (string) ($page_subtitle ?? 'บันทึกและติดตามสถานะงานซ่อม');
$form_title = (string) ($form_title ?? 'แจ้งเหตุซ่อมแซม');
$form_subtitle = (string) ($form_subtitle ?? 'กรอกข้อมูลให้ครบถ้วนเพื่อส่งคำขอซ่อม');
$list_title = (string) ($list_title ?? 'รายการแจ้งซ่อม');
$list_subtitle = (string) ($list_subtitle ?? 'ติดตามสถานะงานซ่อมทั้งหมด');
$empty_title = (string) ($empty_title ?? 'ยังไม่มีรายการแจ้งซ่อม');
$empty_message = (string) ($empty_message ?? 'เมื่อมีการแจ้งซ่อม รายการจะแสดงที่หน้านี้');
$show_form = (bool) ($show_form ?? false);
$show_requester_column = (bool) ($show_requester_column ?? false);
$transition_actions = (array) ($transition_actions ?? []);
$filter_status = (string) ($filter_status ?? 'all');
$filter_sort = (string) ($filter_sort ?? 'newest');
$is_track_active = (bool) ($is_track_active ?? false);
$circular_id = (int) ($circular_id ?? 0);
$item = (array) ($item ?? []);
$item_type = (string) ($item_type ?? '');
$detail_text = (string) ($detail_text ?? '');
$detail_sender_name = (string) ($detail_sender_name ?? '');
$sender_name = (string) ($sender_name ?? '');
$detail_sender_faction = (string) ($detail_sender_faction ?? '');
$sender_faction_display = (string) ($sender_faction_display ?? '');
$date_long_display = (string) ($date_long_display ?? '');
$recipient_count = (int) ($recipient_count ?? 0);
$status_meta = (array) ($status_meta ?? ['label' => '-']);
$consider_class = (string) ($consider_class ?? '');
$date_display = (string) ($date_display ?? '');
$files_json = (string) ($files_json ?? '[]');
$stats_json = (string) ($stats_json ?? '[]');

$values = array_merge([
    'subject' => '',
    'location' => '',
    'equipment' => '',
    'detail' => '',
], $values);

$status_map = [
    REPAIR_STATUS_PENDING => ['label' => 'รอดำเนินการ', 'variant' => 'pending'],
    REPAIR_STATUS_IN_PROGRESS => ['label' => 'กำลังดำเนินการ', 'variant' => 'processing'],
    REPAIR_STATUS_COMPLETED => ['label' => 'เสร็จสิ้น', 'variant' => 'approved'],
    REPAIR_STATUS_REJECTED => ['label' => 'ไม่อนุมัติ', 'variant' => 'rejected'],
    REPAIR_STATUS_CANCELLED => ['label' => 'ยกเลิก', 'variant' => 'rejected'],
];

$headers = $show_requester_column
    ? ['หัวข้อ', 'สถานที่', 'อุปกรณ์', 'สถานะ', 'ผู้แจ้ง', 'วันที่แจ้ง', 'จัดการ']
    : ['หัวข้อ', 'สถานที่', 'อุปกรณ์', 'สถานะ', 'วันที่แจ้ง', 'จัดการ'];

$rows = [];

foreach ($requests as $req) {
    $status_key = (string) ($req['status'] ?? REPAIR_STATUS_PENDING);
    $status = $status_map[$status_key] ?? ['label' => $status_key, 'variant' => 'pending'];
    $is_owner = (string) ($req['requesterPID'] ?? '') === $current_pid;
    $can_edit = $mode === 'report' && $status_key === REPAIR_STATUS_PENDING && $is_owner;

    $row = [
        (string) ($req['subject'] ?? ''),
        (string) ($req['location'] ?? '-'),
        (string) ($req['equipment'] ?? '-'),
        [
            'component' => [
                'name' => 'status-pill',
                'params' => [
                    'label' => $status['label'],
                    'variant' => $status['variant'],
                ],
            ],
        ],
    ];

    if ($show_requester_column) {
        $row[] = (string) ($req['requesterName'] ?? '-');
    }

    $row[] = (string) ($req['createdAt'] ?? '');
    $row[] = [
        'component' => [
            'name' => 'repairs-action-group',
            'params' => [
                'repair_id' => (int) ($req['repairID'] ?? 0),
                'base_url' => $base_url,
                'view_label' => $mode === 'report' ? 'อ่าน' : 'ดูรายละเอียด',
                'can_edit' => $can_edit,
                'can_delete' => $can_edit,
            ],
        ],
    ];

    $rows[] = $row;
}

$detail_status = null;

if ($view_item) {
    $detail_key = (string) ($view_item['status'] ?? REPAIR_STATUS_PENDING);
    $detail_status = $status_map[$detail_key] ?? ['label' => $detail_key, 'variant' => 'pending'];
}

$edit_status = null;

if ($edit_item) {
    $edit_key = (string) ($edit_item['status'] ?? REPAIR_STATUS_PENDING);
    $edit_status = $status_map[$edit_key] ?? ['label' => $edit_key, 'variant' => 'pending'];
}

ob_start();
?>

<style>
    .form-group .upload-layout .upload-box {
        width: 100%;
        height: 200px;
    }

    .enterprise-card-title {
        margin: 20px 0 0 0;
    }

    .circular-my-table-wrap {
        margin-top: 8px;
    }

    .circular-my-table td {
        vertical-align: top;
    }

    .circular-my-table td:nth-child(n+2) {
        vertical-align: middle;
        text-align: center;
    }

    .circular-my-table th:nth-child(n+5) {
        text-align: center;
    }

    .content-modal .container-circular-notice-sending {
        box-shadow: none;
        padding: 0;
    }

    .content-modal .container-circular-notice-sending .sender-row {
        gap: 50px;
    }
</style>

<div class="content-header">
    <h1><?= h($page_title) ?></h1>
    <p><?= h($page_subtitle) ?></p>
</div>

<div class="tabs-container setting-page">
    <div class="button-container vehicle">
        <button class="tab-btn <?= $is_track_active ? '' : 'active' ?>"
            onclick="openTab('repairs', this, event)">แจ้งเหตุซ่อมแซม</button>
        <button class="tab-btn <?= $is_track_active ? 'active' : '' ?>"
            onclick="openTab('myRepair', this, event)">รายการของฉัน</button>
    </div>
</div>

<form method="POST" enctype="multipart/form-data" class="tab-content container-circular-notice-sending active" id="repairs">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="create">

    <div class="sender-row">
        <div class="form-group">
            <label for="">หัวข้อ</label>
            <input type="text" name="subject" value="<?= h($values['subject']) ?>" placeholder="ระบุหัวข้อที่ต้องการแจ้งซ่อม">
        </div>
        <div class="form-group">
            <label for="">สถานที่</label>
            <input type="text" name="location" value="<?= h($values['location']) ?>" placeholder="เช่น อาคาร 1 ห้อง 205">
        </div>
    </div>

    <div class="form-group">
        <label for="">อุปกรณ์</label>
        <input type="text" name="equipment" value="<?= h($values['equipment']) ?>" placeholder="เช่น โปรเจคเตอร์ / เครื่องปรับอากาศ">
    </div>

    <div class="form-group">
        <label for="">รายละเอียดเพิ่มเติม</label>
        <textarea name="detail" rows="4" placeholder="อธิบายอาการหรือปัญหาที่พบ"><?= h($values['detail']) ?></textarea>
    </div>

    <div class="form-group">
        <label>อัปโหลดไฟล์เอกสาร</label>
        <section class="upload-layout">
            <input type="file" id="fileInput" name="attachments[]" multiple accept="image/jpeg,image/png,image/webp,image/gif" style="display: none;" />

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

    <div class="form-group button">
        <div class="input-group">
            <button class="submit" type="submit">
                <p>ส่งแจ้งซ่อม</p>
            </button>
        </div>
    </div>

</form>

<section class="tab-content enterprise-card" id="myRepair">
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
                <input class="form-input" type="search" name="q" value=""
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
                    <th>หัวข้อ</th>
                    <th>สถานที่</th>
                    <th>สถานะ</th>
                    <th>วันที่แจ้ง</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <div class="circular-my-subject">sgfdsdfsdfsd</div>
                        <div class="circular-my-meta">ในนาม กลุ่มบริหารงานทั่วไป</div>
                    </td>

                    <td>หอประชุมการประเรียน</td>

                    <td>
                        <span class="status-pill rejected">ดึงกลับ</span>
                    </td>

                    <td>15 มี.ค. 2569 14:16 น.</td>

                    <td>
                        <div class="circular-my-actions">
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
                            <button
                                class="booking-action-btn secondary js-open-edit-modal"
                                type="button"
                                data-circular-id="<?= h((string) $circular_id) ?>">
                                <i class="fa-solid fa-pen-to-square"></i>
                                <span class="tooltip">แก้ไข</span>
                            </button>
                            <button type="submit" class="booking-action-btn danger" data-confirm="ยืนยันการลบข้อมูลรายการนี้ใช่หรือไม่" data-confirm-title="ยืนยันการลบข้อมูล" data-confirm-ok="ยืนยัน" data-confirm-cancel="ยกเลิก">
                                <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                <span class="tooltip danger">ลบข้อมูล</span>
                            </button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<div class="content-circular-notice-index circular-track-modal-host">
    <div class="modal-overlay-circular-notice-index outside-person" id="modalNoticeKeepOverlay">
        <div class="modal-content">

            <div class="header-modal">
                <div class="first-header">
                    <p>แสดงข้อความรายละเอียดหนังสือเวียน</p>
                </div>
                <div class="sec-header">
                    <i class="fa-solid fa-xmark" id="closeModalNoticeKeep"></i>
                </div>
            </div>

            <div class="content-modal">

                <form method="" class="container-circular-notice-sending" id="repairs">

                    <div class="sender-row">
                        <div class="form-group">
                            <label for="">หัวข้อ</label>
                            <input type="text" placeholder="ระบุหัวข้อที่ต้องการแจ้งซ่อม" disabled>
                        </div>
                        <div class="form-group">
                            <label for="">สถานที่</label>
                            <input type="text" placeholder="เช่น อาคาร 1 ห้อง 205" disabled>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="">อุปกรณ์</label>
                        <input type="text" placeholder="เช่น โปรเจคเตอร์ / เครื่องปรับอากาศ" disabled>
                    </div>

                    <div class="form-group">
                        <label for="">รายละเอียดเพิ่มเติม</label>
                        <textarea name="" rows="4" placeholder="อธิบายอาการหรือปัญหาที่พบ" disabled></textarea>
                    </div>

                    <div class="form-group">
                        <label>อัปโหลดไฟล์เอกสาร</label>
                        <section class="upload-layout">
                            <div class="file-list" id="fileListContainer">
                                <div class="file-item-wrapper">
                                    <div class="file-banner">
                                        <div class="file-info">
                                            <div class="file-icon"><i class="fa-solid fa-file-image" aria-hidden="true"></i></div>
                                            <div class="file-text"><span class="file-name">Screenshot_20260221_224247.png</span><span class="file-type">981.3 KB</span></div>
                                        </div>
                                        <div class="file-actions-group" style="display: flex; gap: 10px;">
                                            <div class="file-actions"><a href="#" target="_blank" rel="noopener"><i class="fa-solid fa-eye" aria-hidden="true"></i></a></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>

                </form>
            </div>

        </div>
    </div>
</div>

<div class="content-circular-notice-index circular-track-modal-host">
    <div class="modal-overlay-circular-notice-index outside-person" id="modalEditOverlay" style="display: none;">
        <div class="modal-content">
            <div class="header-modal">
                <div class="first-header">
                    <p>แก้ไขหนังสือเวียน</p>
                </div>
                <div class="sec-header">
                    <i class="fa-solid fa-xmark" id="closeModalEdit" style="cursor: pointer;"></i>
                </div>
            </div>

            <div class="content-modal">

                <form method="POST" class="container-circular-notice-sending" id="repairs">

                    <div class="sender-row">
                        <div class="form-group">
                            <label for="">หัวข้อ</label>
                            <input type="text" placeholder="ระบุหัวข้อที่ต้องการแจ้งซ่อม">
                        </div>
                        <div class="form-group">
                            <label for="">สถานที่</label>
                            <input type="text" placeholder="เช่น อาคาร 1 ห้อง 205">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="">อุปกรณ์</label>
                        <input type="text" placeholder="เช่น โปรเจคเตอร์ / เครื่องปรับอากาศ">
                    </div>

                    <div class="form-group">
                        <label for="">รายละเอียดเพิ่มเติม</label>
                        <textarea name="" rows="4" placeholder="อธิบายอาการหรือปัญหาที่พบ"></textarea>
                    </div>

                    <div class="form-group">
                        <label>อัปโหลดไฟล์เอกสาร</label>
                        <section class="upload-layout">
                            <input type="file" id="edit_fileInput" name="attachments[]" multiple accept="image/jpeg,image/png,image/webp,image/gif" style="display: none;" />

                            <div class="upload-box" id="edit_dropzone">
                                <i class="fa-solid fa-upload"></i>
                                <p>ลากไฟล์มาวางที่นี่</p>
                            </div>

                            <div class="file-list" id="edit_fileListContainer"></div>
                        </section>
                    </div>

                    <div class="row form-group">
                        <button class="btn btn-upload-small" type="button" id="edit_btnAddFiles">
                            <p>เพิ่มไฟล์</p>
                        </button>
                    </div>

                </form>

            </div>

            <div class="footer-modal">
                <form method="POST" id="">
                    <button>
                        <p>ยืนยัน</p>
                    </button>
                </form>
            </div>

        </div>
    </div>
</div>

<script>
    function openTab(tabId, btnElement, event) {
        event.preventDefault();

        document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));

        document.getElementById(tabId).classList.add('active');
        btnElement.classList.add('active');
    }

    document.addEventListener('DOMContentLoaded', () => {
    
    function setupFileUpload(prefix) {
        const fileInput = document.getElementById(prefix + 'fileInput');
        const fileList = document.getElementById(prefix + 'fileListContainer');
        const dropzone = document.getElementById(prefix + 'dropzone');
        const addFilesBtn = document.getElementById(prefix + 'btnAddFiles');

        if (!fileInput) return;

        const maxFiles = 999;
        const allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        let selectedFiles = [];

        const renderFiles = () => {
            if (!fileList) return;
            fileList.innerHTML = '';

            selectedFiles.forEach((file, index) => {
                const wrapper = document.createElement('div');
                wrapper.className = 'file-item-wrapper';

                const container = document.createElement('div');
                container.className = 'file-banner';

                const info = document.createElement('div');
                info.className = 'file-info';

                const iconWrap = document.createElement('div');
                iconWrap.className = 'file-icon';
                const mime = String(file.type || '').toLowerCase();
                iconWrap.innerHTML = '<i class="fa-solid fa-file-image"></i>';

                const text = document.createElement('div');
                text.className = 'file-text';

                const sizeKB = (file.size / 1024).toFixed(1);
                text.innerHTML = `<span class="file-name">${file.name}</span><span class="file-type">${sizeKB} KB</span>`;

                info.appendChild(iconWrap);
                info.appendChild(text);

                const actions = document.createElement('div');
                actions.className = 'file-actions-group';
                actions.style.display = 'flex';
                actions.style.gap = '10px';

                const viewAction = document.createElement('div');
                viewAction.className = 'file-actions';
                const viewLink = document.createElement('a');

                const fileUrl = URL.createObjectURL(file);
                viewLink.href = fileUrl;
                viewLink.target = '_blank';
                viewLink.rel = 'noopener';
                viewLink.innerHTML = '<i class="fa-solid fa-eye"></i>';
                viewAction.appendChild(viewLink);

                const deleteAction = document.createElement('div');
                deleteAction.className = 'file-actions';
                const deleteBtn = document.createElement('button');
                deleteBtn.type = 'button';
                deleteBtn.className = 'delete-btn';
                deleteBtn.style.border = 'none';
                deleteBtn.style.background = 'none';
                deleteBtn.style.cursor = 'pointer';
                deleteBtn.style.color = '#dc3545';
                deleteBtn.innerHTML = '<i class="fa-solid fa-trash"></i>';

                deleteBtn.onclick = () => {
                    URL.revokeObjectURL(fileUrl);
                    selectedFiles = selectedFiles.filter((_, i) => i !== index);
                    syncFiles();
                    renderFiles();
                };
                deleteAction.appendChild(deleteBtn);

                actions.appendChild(viewAction);
                actions.appendChild(deleteAction);

                container.appendChild(info);
                container.appendChild(actions);
                wrapper.appendChild(container);
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
            const existing = new Set(selectedFiles.map((f) => `${f.name}-${f.size}`));
            Array.from(files).forEach((file) => {
                const key = `${file.name}-${file.size}`;
                if (!existing.has(key) && allowedTypes.includes(file.type) && selectedFiles.length < maxFiles) {
                    selectedFiles.push(file);
                    existing.add(key);
                }
            });
            syncFiles();
            renderFiles();
        };

        fileInput.addEventListener('change', (e) => addFiles(e.target.files));
        if (dropzone) {
            dropzone.addEventListener('click', () => fileInput.click());
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
        if (addFilesBtn) {
            addFilesBtn.addEventListener('click', () => fileInput.click());
        }
    }

    setupFileUpload('');
    
    setupFileUpload('edit_');

});

    document.addEventListener('DOMContentLoaded', function() {

        const detailModal = document.getElementById('modalNoticeKeepOverlay');
        const closeDetailModalBtn = document.getElementById('closeModalNoticeKeep');
        const openDetailBtns = document.querySelectorAll('.js-open-circular-modal');

        const modalUrgency = document.getElementById('modalUrgency');
        const modalBookNo = document.getElementById('modalBookNo');
        const modalIssuedDate = document.getElementById('modalIssuedDate');
        const modalFromText = document.getElementById('modalFromText');
        const modalToText = document.getElementById('modalToText');
        const modalSubject = document.getElementById('modalSubject');
        const modalDetail = document.getElementById('modalDetail');
        const modalFileSection = document.getElementById('modalFileSection');
        const modalReceivedTime = document.getElementById('modalReceivedTime');
        const modalStatus = document.getElementById('modalStatus');
        const modalConsiderStatus = document.getElementById('modalConsiderStatus');
        const receiptStatusTableBody = document.getElementById('receiptStatusTableBody');

        const buildModalFileItem = (file, entityId) => {
            const container = document.createElement('div');
            container.className = 'file-banner';

            const info = document.createElement('div');
            info.className = 'file-info';

            const iconWrap = document.createElement('div');
            iconWrap.className = 'file-icon';
            const mime = String(file?.mimeType || '').toLowerCase();
            iconWrap.innerHTML = mime.includes('pdf') ? '<i class="fa-solid fa-file-pdf"></i>' : mime.includes('image') ? '<i class="fa-solid fa-file-image"></i>' : '<i class="fa-solid fa-file"></i>';

            const text = document.createElement('div');
            text.className = 'file-text';
            text.innerHTML = `<span class="file-name">${file?.fileName || '-'}</span><span class="file-type">${file?.mimeType || ''}</span>`;

            info.appendChild(iconWrap);
            info.appendChild(text);

            const fileUrl = `/public/api/file-download.php?module=circulars&entity_id=${encodeURIComponent(entityId)}&file_id=${encodeURIComponent(file?.fileID || '')}`;

            const viewAction = document.createElement('div');
            viewAction.className = 'file-actions';

            const viewLink = document.createElement('a');
            viewLink.href = `/public/api/file-download.php?module=circulars&entity_id=${encodeURIComponent(entityId)}&file_id=${encodeURIComponent(file?.fileID || '')}`;
            viewLink.target = '_blank';
            viewLink.rel = 'noopener';
            viewLink.innerHTML = '<i class="fa-solid fa-eye"></i>';

            viewAction.appendChild(viewLink);

            const downloadAction = document.createElement('div');
            downloadAction.className = 'file-actions';
            downloadAction.innerHTML = `<a href="${fileUrl}&download=1"><i class="fa-solid fa-download"></i></a>`;

            container.appendChild(info);
            container.appendChild(viewAction);
            container.appendChild(downloadAction);
            return container;
        };

        const renderModalFiles = (files, entityId) => {
            if (!modalFileSection) return;
            modalFileSection.innerHTML = '';
            if (!Array.isArray(files) || files.length === 0) {
                modalFileSection.innerHTML = '<div class="content-details-sec" style="margin: 0;"><p id="modalDetail">-</p></div>';
                return;
            }
            files.forEach((file) => modalFileSection.appendChild(buildModalFileItem(file, entityId)));
        };

        const renderReceiptRows = (stats) => {
            if (!receiptStatusTableBody) return;
            receiptStatusTableBody.innerHTML = '';
            if (!Array.isArray(stats) || stats.length === 0) {
                receiptStatusTableBody.innerHTML = '<tr><td colspan="3" class="enterprise-empty">ไม่พบข้อมูลผู้รับ</td></tr>';
                return;
            }
            stats.forEach((item) => {
                const row = document.createElement('tr');
                row.innerHTML = `<td>${item?.name || '-'}</td><td><span class="status-pill ${item?.pill || 'pending'}">${item?.status || 'ยังไม่อ่าน'}</span></td><td>${item?.readAt || '-'}</td>`;
                receiptStatusTableBody.appendChild(row);
            });
        };

        openDetailBtns.forEach((btn) => {
            btn.addEventListener('click', (event) => {
                event.preventDefault();
                const circularId = String(btn.getAttribute('data-circular-id') || '').trim();

                let stats = [];
                let files = [];
                try {
                    stats = JSON.parse(String(btn.getAttribute('data-read-stats') || '[]'));
                } catch (e) {}
                try {
                    files = JSON.parse(String(btn.getAttribute('data-files') || '[]'));
                } catch (e) {}

                if (modalUrgency) {
                    modalUrgency.className = 'urgency-status normal';
                    const urgencyLabel = modalUrgency.querySelector('p');
                    if (urgencyLabel) urgencyLabel.textContent = String(btn.getAttribute('data-type') || 'INTERNAL').toUpperCase() === 'EXTERNAL' ? 'ภายนอก' : 'ภายใน';
                }
                if (modalBookNo) modalBookNo.value = btn.getAttribute('data-bookno') || '-';
                if (modalIssuedDate) modalIssuedDate.value = btn.getAttribute('data-issued') || '-';
                if (modalFromText) modalFromText.value = btn.getAttribute('data-from') || '-';
                if (modalToText) modalToText.value = btn.getAttribute('data-to') || '-';
                if (modalSubject) modalSubject.textContent = btn.getAttribute('data-subject') || '-';
                if (modalDetail) modalDetail.textContent = btn.getAttribute('data-detail') || '-';
                if (modalReceivedTime) modalReceivedTime.value = btn.getAttribute('data-received-time') || '-';
                if (modalStatus) modalStatus.value = btn.getAttribute('data-status') || '-';
                if (modalConsiderStatus) {
                    modalConsiderStatus.className = `consider-status ${btn.getAttribute('data-consider') || 'considering'}`;
                    modalConsiderStatus.textContent = btn.getAttribute('data-status') || '-';
                }

                renderModalFiles(files, circularId);
                renderReceiptRows(stats);

                if (detailModal) detailModal.style.display = 'flex';
            });
        });

        closeDetailModalBtn?.addEventListener('click', () => {
            if (detailModal) detailModal.style.display = 'none';
        });
        detailModal?.addEventListener('click', (event) => {
            if (event.target === detailModal) detailModal.style.display = 'none';
        });

        const editModal = document.getElementById('modalEditOverlay');
        const closeEditModalBtn = document.getElementById('closeModalEdit');
        const openEditBtns = document.querySelectorAll('.js-open-edit-modal');
        const editTargetInput = document.getElementById('editTargetCircularId');

        openEditBtns.forEach((btn) => {
            btn.addEventListener('click', (event) => {
                event.preventDefault();
                const circularId = String(btn.getAttribute('data-circular-id') || '').trim();

                if (editTargetInput) editTargetInput.value = circularId;

                const subjectInput = document.getElementById('edit_subject');
                const detailInput = document.getElementById('edit_detail');
                if (subjectInput) subjectInput.value = String(btn.getAttribute('data-subject') || '').trim();
                if (detailInput) detailInput.value = String(btn.getAttribute('data-detail') || '').trim();

                if (editModal) editModal.style.display = 'flex';
            });
        });

        closeEditModalBtn?.addEventListener('click', () => {
            if (editModal) editModal.style.display = 'none';
        });
        editModal?.addEventListener('click', (event) => {
            if (event.target === editModal) editModal.style.display = 'none';
        });

    });
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';

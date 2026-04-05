<?php
require_once __DIR__ . '/../../helpers.php';

ob_start();
?>

<style>
    .file-hint p {
        color: var(--color-danger) !important;
    }

    .btn-upload-small p {
        color: var(--color-neutral-lightest) !important;
    }

    .form-group.row.label {
        margin: 0 0 10px;
        height: auto;
    }

    .delete-btn {
        background: none !important;
        border: none !important;
        color: var(--color-danger) !important;
        font-size: var(--font-size-title) !important;
        cursor: pointer !important;
        transition: transform 0.2s !important;
    }

    .delete-btn:hover {
        transform: scale(1.2) !important;
    }

    .upload-layout {
        flex-direction: column !important;
    }

    td:last-child {
        text-align: center;
    }
</style>

<div class="content-header">
    <h1>ยินดีต้อนรับ</h1>
    <p>ออกเลขเกียรติบัตร</p>
</div>

<div class="tabs-container setting-page">
    <div class="button-container">
        <button class="tab-btn <?= $is_track_active ? '' : 'active' ?>"
            onclick="openTab('certificate', event)">ออกเลขเกียรติบัตร</button>
        <button class="tab-btn <?= $is_track_active ? 'active' : '' ?>"
            onclick="openTab('certificateData', event)">ข้อมูลเกียรติบัตร</button>
        <button class="tab-btn <?= $is_track_active ? 'active' : '' ?>"
            onclick="openTab('certificateMine', event)">เกียรติบัตรของฉัน</button>
    </div>
</div>

<div class="content-order tab-content active" id="certificate">
    <div class="form-group row">
        <div class="input-group">
            <p><strong>จำนวนเกียรติบัตรทั้งหมด</strong></p>
            <input
                type="number"
                class="order-no-display"
                value="">
        </div>
        <div class="input-group">
            <p><strong>ผู้ขอ</strong></p>
            <input
                type="text"
                class="order-no-display"
                value=""
                disabled>
        </div>
    </div>

    <div class="form-group row">
        <div class="input-group">
            <p><strong>จากลำดับที่</strong></p>
            <input
                type="number"
                class="order-no-display"
                value=""
                disabled>
        </div>
        <div class="input-group">
            <p><strong>ถึงลำดับที่</strong></p>
            <input
                type="number"
                class="order-no-display"
                value=""
                disabled>
        </div>
    </div>

    <div class="form-group row">
        <div class="input-group">
            <p><strong>เรื่อง</strong></p>
            <input
                type="text"
                class="order-no-display"
                value="">
        </div>
        <div class="input-group">
            <p><strong>ในนามของ</strong></p>
            <div class="custom-select-wrapper">
                <div class="custom-select-trigger">
                    <p class="select-value">กลุ่มบริหารงานทั่วไป</p>
                    <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                </div>

                <div class="custom-options">
                    <div class="custom-option" data-value="5">กลุ่มบริหารกิจการนักเรียน</div>
                    <div class="custom-option selected" data-value="4">กลุ่มบริหารงานทั่วไป</div>
                    <div class="custom-option" data-value="3">กลุ่มบริหารงานบุคคลและงบประมาณ</div>
                    <div class="custom-option" data-value="2">กลุ่มบริหารงานวิชาการ</div>
                    <div class="custom-option" data-value="6">กลุ่มสนับสนุนการสอน</div>
                    <div class="custom-option" data-value="1">ฝ่ายบริหาร</div>
                </div>

                <input type="hidden" name="group_fid" value="4">
            </div>
        </div>
    </div>

    <div class="form-group row label">
        <div class="input-group">
            <p><strong>แนบเอกสาร</strong></p>
        </div>
    </div>

    <div class="form-group row">
        <section class="upload-layout">
            <input type="file" id="certiFile" name="cover_file" accept="application/pdf,image/png,image/jpeg" style="display: none;">
            <div class="row form-group">
                <button class="btn btn-upload-small" type="button" id="btnCertiAddFile">
                    <p>เพิ่มไฟล์</p>
                </button>
                <div class="file-hint">
                    <p>*อัปโหลดได้เฉพาะไฟล์ word, excel, pdf, zip, rar สูงสุดแนบไฟล์ได้ 1 ไฟล์ *</p>
                    <p></p>
                </div>
            </div>
            <div class="existing-file-section">
                <div class="file-list" id="certiFileContainer"></div>
            </div>
        </section>
    </div>

    <div class="form-group last button">
        <div class="input-group">
            <button
                class="submit"
                type="submit"
                data-confirm="ยืนยันการบันทึกออกเลขทะเบียนส่งใช่หรือไม่?"
                data-confirm-title="ยืนยันการบันทึก"
                data-confirm-ok="ยืนยัน"
                data-confirm-cancel="ยกเลิก">
                <p>บันทึกออกเลข</p>
            </button>
        </div>
    </div>

</div>

<section class="enterprise-card tab-content <?= $is_track_active ? 'active' : '' ?>" id="certificateData">
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
                    placeholder="ค้นหาเลขเกียรติบัตรของฉัน" autocomplete="off">
            </div>
            <div class="room-admin-filter">
                <div class="custom-select-wrapper">
                    <div class="custom-select-trigger">
                        <p class="select-value">
                            <?php
                            $status_label = 'ทั้งหมด';

                            if ($filter_status === 'waiting_attachment') {
                                $status_label = 'รอการแนบไฟล์';
                            } elseif ($filter_status === 'complete') {
                                $status_label = 'แนบไฟล์สำเร็จ';
                            }
                            echo h($status_label);
                            ?>
                        </p>
                        <i class="fa-solid fa-chevron-down"></i>
                    </div>

                    <div class="custom-options">
                        <div class="custom-option" data-value="all">ทั้งหมด</div>
                        <div class="custom-option" data-value="waiting_attachment">รอการแนบไฟล์</div>
                        <div class="custom-option" data-value="complete">แนบไฟล์สำเร็จ</div>
                    </div>

                    <select class="form-input" name="status">
                        <option value="all" <?= $filter_status === 'all' ? 'selected' : '' ?>>ทั้งหมด</option>
                        <option value="waiting_attachment" <?= $filter_status === 'waiting_attachment' ? 'selected' : '' ?>>รอการแนบไฟล์</option>
                        <option value="complete" <?= $filter_status === 'complete' ? 'selected' : '' ?>>แนบไฟล์สำเร็จ</option>
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

    <div class="enterprise-card-header order-mine-list-header">
        <div class="enterprise-card-title-group">
            <h2 class="enterprise-card-title">รายการเลขเกียรติบัตร</h2>
        </div>
    </div>

    <div class="table-responsive circular-my-table-wrap order-create">
        <script type="application/json" class="js-order-send-map">
            <?= (string) json_encode($send_modal_payload_map, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
        </script>
        <table class="custom-table circular-my-table">
            <thead>
                <tr>
                    <th>เรื่อง</th>
                    <th>จากสำดับที่</th>
                    <th>ถึงสำดับที่</th>
                    <th>สถานะ</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Open House SMTE 90</td>
                    <td>124</td>
                    <td>212</td>
                    <td><span class="status-pill approved">สำเร็จ</span></td>
                    <td>
                        <div class="circular-my-actions">
                            <button class="booking-action-btn secondary js-open-order-view-modal" type="button" data-outgoing-id="1" data-outgoing-priority-key="normal" title="ดูรายละเอียด" aria-label="ดูรายละเอียด">
                                <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                <span class="tooltip">ดูรายละเอียด</span>
                            </button>
                        </div>
                    </td>
                </tr>
            </tbody>

        </table>
    </div>
</section>

<div class="content-circular-notice-index circular-track-modal-host">
    <div class="modal-overlay-circular-notice-index outside-person" id="modalOrderViewOverlay">
        <div class="modal-content">
            <div class="header-modal">
                <div class="first-header">
                    <p id="modalOutgoingViewTitle">ดูรายละเอียดออกเลขเกียรติบัตร</p>
                </div>
                <div class="sec-header">
                    <i class="fa-solid fa-xmark" id="modalOrderViewCloseBtn"></i>
                </div>
            </div>

            <div class="content-modal">
                <div class="content-topic-sec">
                    <div class="more-details">
                        <p><strong>จำนวนเกียรติบัตรทั้งหมด</strong></p>
                        <input type="number" id="modalOutgoingViewNo" class="order-no-display" value="0" disabled>
                    </div>
                    <div class="more-details">
                        <p><strong>ผู้ขอ</strong></p>
                        <input type="text" id="modalOutgoingViewSubject" class="order-no-display" value="-" disabled>
                    </div>
                </div>

                <div class="content-topic-sec">
                    <div class="more-details">
                        <p><strong>จากลำดับที่</strong></p>
                        <input type="number" id="modalOutgoingViewEffectiveDate" class="order-no-display" value="0" disabled>
                    </div>
                    <div class="more-details">
                        <p><strong>ถึงลำดับที่</strong></p>
                        <input type="number" id="modalOutgoingViewIssuer" class="order-no-display" value="0" disabled>
                    </div>
                </div>

                <div class="content-topic-sec">
                    <div class="more-details">
                        <p><strong>เรื่อง</strong></p>
                        <input type="text" id="modalOutgoingViewNo" class="order-no-display" value="-" disabled>
                    </div>
                    <div class="more-details">
                        <p><strong>ในนามของ</strong></p>
                        <input type="text" id="modalOutgoingViewSubject" class="order-no-display" value="-" disabled>
                    </div>
                </div>

                <div class="vehicle-row file-sec">
                    <div class="vehicle-input-content">
                        <p><strong>ไฟล์หนังสือนำ</strong></p>
                    </div>

                    <div class="file-list" id="vehicleAttachmentList" aria-live="polite">
                        <div class="file-item-wrapper" data-file-id="148">
                            <div class="file-banner">
                                <div class="file-info">
                                    <div class="file-icon"><i class="fa-solid fa-file-image" aria-hidden="true"></i></div>
                                    <div class="file-text"><span class="file-name">Worksheet_5+-+Pre-campaign+insight+_page-0001.jpg</span><span class="file-type">image/jpeg • 971 KB</span></div>
                                </div>
                                <div class="file-actions"><a href="javascript:void(0)" class="action-btn" title="ดูตัวอย่าง"><i class="fa-solid fa-eye" aria-hidden="true"></i></a></div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="footer-modal">
                <!-- <button type="button" id="modalOrderViewCloseBtn">
                    <p>ปิดหน้าต่าง</p>
                </button> -->
            </div>

        </div>
    </div>
</div>

<section class="enterprise-card tab-content <?= $is_track_active ? 'active' : '' ?>" id="certificateMine">
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
                    placeholder="ค้นหาเลขเกียรติบัตรของฉัน" autocomplete="off">
            </div>
            <div class="room-admin-filter">
                <div class="custom-select-wrapper">
                    <div class="custom-select-trigger">
                        <p class="select-value">
                            <?php
                            $status_label = 'ทั้งหมด';

                            if ($filter_status === 'waiting_attachment') {
                                $status_label = 'รอการแนบไฟล์';
                            } elseif ($filter_status === 'complete') {
                                $status_label = 'แนบไฟล์สำเร็จ';
                            }
                            echo h($status_label);
                            ?>
                        </p>
                        <i class="fa-solid fa-chevron-down"></i>
                    </div>

                    <div class="custom-options">
                        <div class="custom-option" data-value="all">ทั้งหมด</div>
                        <div class="custom-option" data-value="waiting_attachment">รอการแนบไฟล์</div>
                        <div class="custom-option" data-value="complete">แนบไฟล์สำเร็จ</div>
                    </div>

                    <select class="form-input" name="status">
                        <option value="all" <?= $filter_status === 'all' ? 'selected' : '' ?>>ทั้งหมด</option>
                        <option value="waiting_attachment" <?= $filter_status === 'waiting_attachment' ? 'selected' : '' ?>>รอการแนบไฟล์</option>
                        <option value="complete" <?= $filter_status === 'complete' ? 'selected' : '' ?>>แนบไฟล์สำเร็จ</option>
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

    <div class="enterprise-card-header order-mine-list-header">
        <div class="enterprise-card-title-group">
            <h2 class="enterprise-card-title">รายการเลขเกียรติบัตรของฉัน</h2>
        </div>
    </div>

    <div class="table-responsive circular-my-table-wrap order-create">
        <script type="application/json" class="js-order-send-map">
            <?= (string) json_encode($send_modal_payload_map, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
        </script>
        <table class="custom-table circular-my-table">
            <thead>
                <tr>
                    <th>เรื่อง</th>
                    <th>จากสำดับที่</th>
                    <th>ถึงสำดับที่</th>
                    <th>สถานะ</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Open House SMTE 90</td>
                    <td>124</td>
                    <td>212</td>
                    <td><span class="status-pill pending">กำลังดำเนินการ</span></td>
                    <td>
                        <div class="circular-my-actions">
                            <button class="booking-action-btn secondary js-open-order-edit-modal" type="button" data-outgoing-id="1" data-outgoing-priority-key="normal" title="ดู/แนบไฟล์" aria-label="ดู/แนบไฟล์">
                                <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                                <span class="tooltip">ดู/แนบไฟล์</span>
                            </button>
                        </div>
                    </td>
                    </td>
                </tr>
            </tbody>

        </table>
    </div>
</section>

<div class="content-circular-notice-index circular-track-modal-host">
    <div class="modal-overlay-circular-notice-index outside-person" id="modalOrderEditOverlay">
        <div class="modal-content">
            <div class="header-modal">
                <div class="first-header">
                    <p id="modalOutgoingViewTitle">แก้ไขออกเลขเกียรติบัตร</p>
                </div>
                <div class="sec-header">
                    <i class="fa-solid fa-xmark" id="modalOrderViewCloseBtn"></i>
                </div>
            </div>

            <div class="content-modal">
                <div class="content-topic-sec">
                    <div class="more-details">
                        <p><strong>จำนวนเกียรติบัตรทั้งหมด</strong></p>
                        <input type="number" id="modalOutgoingViewNo" class="order-no-display" value="0" disabled>
                    </div>
                    <div class="more-details">
                        <p><strong>ผู้ขอ</strong></p>
                        <input type="text" id="modalOutgoingViewSubject" class="order-no-display" value="-" disabled>
                    </div>
                </div>

                <div class="content-topic-sec">
                    <div class="more-details">
                        <p><strong>จากลำดับที่</strong></p>
                        <input type="number" id="modalOutgoingViewEffectiveDate" class="order-no-display" value="0" disabled>
                    </div>
                    <div class="more-details">
                        <p><strong>ถึงลำดับที่</strong></p>
                        <input type="number" id="modalOutgoingViewIssuer" class="order-no-display" value="0" disabled>
                    </div>
                </div>

                <div class="content-topic-sec">
                    <div class="more-details">
                        <p><strong>เรื่อง</strong></p>
                        <input type="text" id="modalOutgoingViewNo" class="order-no-display" value="">
                    </div>
                    <div class="more-details">
                        <p><strong>ในนามของ</strong></p>
                        <div class="custom-select-wrapper">
                            <div class="custom-select-trigger">
                                <p class="select-value">กลุ่มบริหารงานทั่วไป</p>
                                <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                            </div>

                            <div class="custom-options">
                                <div class="custom-option" data-value="5">กลุ่มบริหารกิจการนักเรียน</div>
                                <div class="custom-option selected" data-value="4">กลุ่มบริหารงานทั่วไป</div>
                                <div class="custom-option" data-value="3">กลุ่มบริหารงานบุคคลและงบประมาณ</div>
                                <div class="custom-option" data-value="2">กลุ่มบริหารงานวิชาการ</div>
                                <div class="custom-option" data-value="6">กลุ่มสนับสนุนการสอน</div>
                                <div class="custom-option" data-value="1">ฝ่ายบริหาร</div>
                            </div>

                            <input type="hidden" name="group_fid" value="4">
                        </div>
                    </div>
                </div>

                <div class="form-group row label">
                    <div class="input-group">
                        <p><strong>อัปโหลดไฟล์หนังสือนำ</strong></p>
                    </div>
                </div>
                <div class="form-group row">
                    <section class="upload-layout">
                        <input type="file" id="coverFileInput_modal" name="cover_file" accept="application/pdf,image/png,image/jpeg" style="display: none;">

                        <div class="row form-group">
                            <button class="btn btn-upload-small" type="button" id="btnCoverAddFile_modal">
                                <p>เพิ่มไฟล์</p>
                            </button>
                            <div class="file-hint">
                                <p>* แนบไฟล์หนังสือนำได้ 1 ไฟล์ *</p>
                            </div>
                        </div>

                        <div class="existing-file-section">
                            <div class="file-list" id="coverFileListContainer_modal"></div>
                        </div>
                    </section>
                </div>

            </div>

            <div class="footer-modal">
                <button type="button" id="modalOrderViewCloseBtn">
                    <p>ยืนยัน</p>
                </button>
            </div>

        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        function setupFileUpload(inputId, listId, maxFiles = 1, options = {}) {
            const fileInput = document.getElementById(inputId);
            const fileList = document.getElementById(listId);
            const dropzone = options.dropzoneId ? document.getElementById(options.dropzoneId) : null;
            const addFilesBtn = options.addButtonId ? document.getElementById(options.addButtonId) : null;
            const form = fileInput ? fileInput.closest('form') : null;

            const allowedTypes = [
                // PDF
                'application/pdf',

                // Word
                'application/msword', // .doc
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // .docx

                // Excel
                'application/vnd.ms-excel', // .xls
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx

                // ZIP / RAR
                'application/zip',
                'application/x-zip-compressed',
                'application/x-rar-compressed',
                'application/vnd.rar'
            ];

            const allowedExtensions = [
                '.pdf', '.doc', '.docx', '.xls', '.xlsx', '.zip', '.rar'
            ];

            const isValid = (file) => {
                const ext = file.name.substring(file.name.lastIndexOf('.')).toLowerCase();
                return allowedTypes.includes(file.type) && allowedExtensions.includes(ext);
            };

            let selectedFiles = [];
            let existingFiles = [];
            let existingEntityId = '';
            let removedExistingFileIds = [];

            let removedFilesContainer = form ? form.querySelector('[data-remove-file-inputs]') : null;
            if (form && !removedFilesContainer) {
                removedFilesContainer = document.createElement('div');
                removedFilesContainer.setAttribute('data-remove-file-inputs', 'true');
                removedFilesContainer.style.display = 'none';
                form.appendChild(removedFilesContainer);
            }

            if (!fileInput) return null;

            const syncRemovedFileInputs = () => {
                if (!removedFilesContainer) return;
                removedFilesContainer.innerHTML = '';
                removedExistingFileIds.forEach((fileId) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'remove_file_ids[]';
                    input.value = String(fileId);
                    removedFilesContainer.appendChild(input);
                });
            };

            const buildFileIconMarkup = (mimeType) => {
                const normalizedMime = String(mimeType || '').toLowerCase();

                if (normalizedMime.includes('pdf')) {
                    return '<i class="fa-solid fa-file-pdf"></i>';
                }else if (normalizedMime.includes('word')) {
                    return '<i class="fa-solid fa-file-word"></i>';
                }else if (normalizedMime.includes('excel') || normalizedMime.includes('spreadsheet')) {
                    return '<i class="fa-solid fa-file-excel"></i>';
                }else if (normalizedMime.includes('zip')) {
                    return '<i class="fa-solid fa-file-zipper"></i>';
                }else {
                    return '<i class="fa-solid fa-file"></i>';
                }
            };

            const buildExistingFileUrl = (file) => {
                const fileId = String(file?.fileID || '').trim();
                if (existingEntityId === '' || fileId === '') {
                    return '';
                }
                return `/public/api/file-download.php?module=circulars&entity_id=${encodeURIComponent(existingEntityId)}&file_id=${encodeURIComponent(fileId)}`;
            };

            const renderFiles = () => {
                if (!fileList) return;
                fileList.innerHTML = '';

                existingFiles.forEach((file) => {
                    const fileId = String(file?.fileID || '').trim();
                    const fileUrl = buildExistingFileUrl(file);
                    const wrapper = document.createElement('div');
                    wrapper.className = 'file-item-wrapper';

                    const deleteBtn = document.createElement('button');
                    deleteBtn.type = 'button';
                    deleteBtn.className = 'delete-btn';
                    deleteBtn.innerHTML = '<i class="fa-solid fa-trash"></i>';
                    deleteBtn.addEventListener('click', () => {
                        if (fileId !== '' && !removedExistingFileIds.includes(fileId)) {
                            removedExistingFileIds.push(fileId);
                        }
                        existingFiles = existingFiles.filter((existingFile) => String(existingFile?.fileID || '').trim() !== fileId);
                        syncRemovedFileInputs();
                        renderFiles();
                    });

                    const banner = document.createElement('div');
                    banner.className = 'file-banner';

                    const info = document.createElement('div');
                    info.className = 'file-info';

                    const icon = document.createElement('div');
                    icon.className = 'file-icon';
                    icon.innerHTML = buildFileIconMarkup(file?.mimeType);

                    const text = document.createElement('div');
                    text.className = 'file-text';
                    text.innerHTML = `<div class="file-name">${String(file?.fileName || '-')}</div><div class="file-type">${String(file?.mimeType || 'ไฟล์แนบ')}</div>`;

                    info.appendChild(icon);
                    info.appendChild(text);
                    banner.appendChild(info);

                    if (fileUrl !== '') {
                        const actions = document.createElement('div');
                        actions.className = 'file-actions';

                        const view = document.createElement('a');
                        view.href = fileUrl;
                        view.target = '_blank';
                        view.rel = 'noopener';
                        view.innerHTML = '<i class="fa-solid fa-eye"></i>';
                        actions.appendChild(view);
                        banner.appendChild(actions);
                    }

                    wrapper.appendChild(deleteBtn);
                    wrapper.appendChild(banner);
                    fileList.appendChild(wrapper);
                });

                selectedFiles.forEach((file, index) => {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'file-item-wrapper new-file-item';

                    const deleteBtn = document.createElement('button');
                    deleteBtn.type = 'button';
                    deleteBtn.className = 'delete-btn';
                    deleteBtn.innerHTML = '<i class="fa-solid fa-trash-can"></i>';
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
                    icon.innerHTML = buildFileIconMarkup(file.type);

                    const text = document.createElement('div');
                    text.className = 'file-text';
                    text.innerHTML = `<div class="file-name">${file.name}</div><div class="file-type">${file.type || 'ไฟล์แนบ'}</div>`;

                    info.appendChild(icon);
                    info.appendChild(text);

                    const actions = document.createElement('div');
                    actions.className = 'file-actions';

                    const fileUrl = URL.createObjectURL(file);
                    const view = document.createElement('a');
                    view.href = fileUrl;
                    view.target = '_blank';
                    view.rel = 'noopener';
                    view.innerHTML = '<i class="fa-solid fa-eye"></i>';

                    view.addEventListener('click', () => {
                        setTimeout(() => URL.revokeObjectURL(fileUrl), 100);
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
                if (!files || files.length === 0) return;

                if (maxFiles === 1) {
                    const file = files[0];
                    if (isValid(file)) {
                        selectedFiles = [file];
                    } else {
                        alert('ประเภทไฟล์ไม่ได้รับอนุญาต');
                    }
                } else {
                    const existing = new Set(selectedFiles.map((f) => `${f.name}-${f.size}-${f.lastModified}`));
                    let currentTotal = existingFiles.length + selectedFiles.length;

                    Array.from(files).forEach((file) => {
                        const key = `${file.name}-${file.size}-${file.lastModified}`;
                        if (!existing.has(key) && isValid(file) && currentTotal < maxFiles) {
                            selectedFiles.push(file);
                            existing.add(key);
                            currentTotal++;
                        } else if (!isValid(file)) {
                            console.warn('ประเภทไฟล์ไม่ได้รับอนุญาต:', file.name);
                        } else if (currentTotal >= maxFiles) {
                            console.warn('เกินจำนวนไฟล์สูงสุดแล้ว');
                        }
                    });
                }
                syncFiles();
                renderFiles();
            };

            const setExistingFiles = (files, entityId) => {
                existingFiles = Array.isArray(files) ? files : [];
                existingEntityId = String(entityId || '').trim();
                selectedFiles = [];
                removedExistingFileIds = [];
                syncRemovedFileInputs();
                syncFiles();
                renderFiles();
            };

            fileInput.addEventListener('change', (e) => {
                addFiles(e.target.files);
                fileInput.value = '';
            });

            if (addFilesBtn) {
                addFilesBtn.addEventListener('click', () => fileInput.click());
            }

            renderFiles();

            return {
                setExistingFiles,
                reset: () => {
                    selectedFiles = [];
                    existingFiles = [];
                    removedExistingFileIds = [];
                    syncRemovedFileInputs();
                    syncFiles();
                    renderFiles();
                }
            };
        }

        window.__outgoingMainUpload = setupFileUpload(
            'certiFile',
            'certiFileContainer',
            1, {
                addButtonId: 'btnCertiAddFile'
            }
        );

        window.__outgoingMainCoverUpload = setupFileUpload(
            'coverFileInput_modal',
            'coverFileListContainer_modal',
            1, {
                addButtonId: 'btnCoverAddFile_modal'
            }
        );
    })

    document.addEventListener('DOMContentLoaded', () => {
        const editModal = document.getElementById('modalOrderEditOverlay');
        const viewModal = document.getElementById('modalOrderViewOverlay');
        const openOutgoingEditModal = (outgoingIdRaw, fallbackPriorityKey = 'normal') => {
            if (!editModal) {
                return;
            }
            editModal.style.display = 'flex';
            scheduleOutgoingPrioritySync(modalOutgoingEditUrgentRadios, resolveOutgoingPriorityKey(payload, fallbackPriorityKey));
        };

        const openOutgoingViewModal = (outgoingIdRaw, fallbackPriorityKey = 'normal') => {
            if (!viewModal) {
                return;
            }
            viewModal.style.display = 'flex';
            scheduleOutgoingPrioritySync(modalOutgoingViewUrgentRadios, resolveOutgoingPriorityKey(payload, fallbackPriorityKey));
        };
        document.addEventListener('click', (event) => {
            const targetBtn = event.target.closest('button');
            if (!targetBtn) return;

            if (targetBtn.classList.contains('js-open-order-edit-modal')) {
                openOutgoingEditModal(
                    targetBtn.getAttribute('data-outgoing-id'),
                    targetBtn.getAttribute('data-outgoing-priority-key') || 'normal'
                );
            }

            if (targetBtn.classList.contains('js-open-order-view-modal')) {
                openOutgoingViewModal(
                    targetBtn.getAttribute('data-outgoing-id'),
                    targetBtn.getAttribute('data-outgoing-priority-key') || 'normal'
                );
            }
        });
        const closeActions = [{
                btn: editModal?.querySelector('#closeModalOrderSend') ?? null,
                modal: editModal,
            },
            {
                btn: viewModal?.querySelector('#modalOrderViewCloseBtn') ?? null,
                modal: viewModal,
            }
        ];

        closeActions.forEach(action => {
            if (action.btn && action.modal) {
                action.btn.addEventListener('click', () => {
                    action.modal.style.display = 'none';
                });
            }
        });
        window.addEventListener('click', (event) => {
            if (event.target === editModal) {
                editModal.style.display = 'none';
            }
            if (event.target === viewModal) {
                viewModal.style.display = 'none';
            }
        });
    })
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';

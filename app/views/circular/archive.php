<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../auth/csrf.php';

$items = (array) ($items ?? []);
$box_key = (string) ($box_key ?? 'normal');
$filter_type = (string) ($filter_type ?? 'all');
$filter_read = (string) ($filter_read ?? 'all');
$filter_sort = (string) ($filter_sort ?? 'newest');
$filter_view = (string) ($filter_view ?? 'table1');
$filter_search = (string) ($filter_search ?? '');
$is_outside_view = (bool) ($is_outside_view ?? false);
$director_label = (string) ($director_label ?? 'ผอ./รักษาการ');

$type_external_checked = $filter_type === 'external' || $filter_type === 'all';
$type_internal_checked = $filter_type === 'internal' || $filter_type === 'all';
$read_checked = $filter_read === 'read' || $filter_read === 'all';
$unread_checked = $filter_read === 'unread' || $filter_read === 'all';

ob_start();
?>
<div class="content-header">
    <h1>ยินดีต้อนรับ</h1>
    <p>หนังสือเวียน / หนังสือเวียนที่จัดเก็บ</p>
</div>

<form id="circularFilterForm" method="GET">
    <input type="hidden" name="box" value="<?= h($box_key) ?>">
    <input type="hidden" name="archived" value="1">
    <input type="hidden" name="type" id="filterTypeInput" value="<?= h($filter_type) ?>">
    <input type="hidden" name="read" id="filterReadInput" value="<?= h($filter_read) ?>">
    <input type="hidden" name="sort" id="filterSortInput" value="<?= h($filter_sort) ?>">
    <input type="hidden" name="view" id="filterViewInput" value="<?= h($filter_view) ?>">
</form>
<input type="hidden" id="csrfToken" value="<?= h(csrf_token()) ?>">

<?php if (!$is_outside_view) : ?>
    <header class="header-circular-notice-keep">
        <div class="circular-notice-keep-control">
            <div class="page-selector">
                <p>แสดงตามประเภทหนังสือ</p>

                <div class="custom-select-wrapper" data-target="filterTypeInput">
                    <div class="custom-select-trigger">
                        <p class="select-value"><?= h($filter_type === 'internal' ? 'ภายใน' : ($filter_type === 'external' ? 'ภายนอก' : 'ทั้งหมด')) ?></p>
                        <i class="fa-solid fa-chevron-down"></i>
                    </div>

                    <div class="custom-options">
                        <div class="custom-option<?= h($filter_type === 'external' ? ' selected' : '') ?>" data-value="external">ภายนอก</div>
                        <div class="custom-option<?= h($filter_type === 'internal' ? ' selected' : '') ?>" data-value="internal">ภายใน</div>
                        <div class="custom-option<?= h($filter_type === 'all' ? ' selected' : '') ?>" data-value="all">ทั้งหมด</div>
                    </div>
                </div>
            </div>
            <div class="page-selector">
                <p>แสดงตามสถานะหนังสือ</p>

                <div class="custom-select-wrapper" data-target="filterReadInput">
                    <div class="custom-select-trigger">
                        <p class="select-value"><?= h($filter_read === 'read' ? 'อ่านแล้ว' : ($filter_read === 'unread' ? 'ยังไม่อ่าน' : 'ทั้งหมด')) ?></p>
                        <i class="fa-solid fa-chevron-down"></i>
                    </div>

                    <div class="custom-options">
                        <div class="custom-option<?= h($filter_read === 'read' ? ' selected' : '') ?>" data-value="read">อ่านแล้ว</div>
                        <div class="custom-option<?= h($filter_read === 'unread' ? ' selected' : '') ?>" data-value="unread">ยังไม่อ่าน</div>
                        <div class="custom-option<?= h($filter_read === 'all' ? ' selected' : '') ?>" data-value="all">ทั้งหมด</div>
                    </div>
                </div>
            </div>

            <div class="page-selector">
                <p>แสดงตาม</p>

                <div class="custom-select-wrapper" data-target="filterSortInput">
                    <div class="custom-select-trigger">
                        <p class="select-value"><?= h($filter_sort === 'oldest' ? 'เก่าไปใหม่' : 'ใหม่ไปเก่า') ?></p>
                        <i class="fa-solid fa-chevron-down"></i>
                    </div>

                    <div class="custom-options">
                        <div class="custom-option<?= h($filter_sort === 'newest' ? ' selected' : '') ?>" data-value="newest">ใหม่ไปเก่า</div>
                        <div class="custom-option<?= h($filter_sort === 'oldest' ? ' selected' : '') ?>" data-value="oldest">เก่าไปใหม่</div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <section class="content-circular-notice-keep" data-circular-notice>
        <div class="search-bar">
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="search-input" name="q" form="circularFilterForm" value="<?= h($filter_search) ?>" placeholder="ค้นหาข้อความด้วย...">
            </div>
        </div>

        <div class="table-circular-notice-keep">
            <table>
                <thead>
                    <tr>
                        <th>ประเภทหนังสือ</th>
                        <th>หัวเรื่อง</th>
                        <th>ผู้ส่ง</th>
                        <th>วันที่ส่ง</th>
                        <th>สถานะ</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)) : ?>
                        <tr>
                            <td colspan="6" class="booking-empty">ไม่มีรายการ</td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($items as $item) : ?>
                            <?php $file_json = (string) ($item['files_json'] ?? '[]'); ?>
                            <tr>
                                <td><?= h((string) ($item['type_label'] ?? '')) ?></td>
                                <td><?= h((string) ($item['subject'] ?? '')) ?></td>
                                <td><?= h((string) ($item['sender_name'] ?? '-')) ?></td>
                                <td><?= h((string) ($item['delivered_date'] ?? '-')) ?></td>
                                <td><span class="status-badge <?= h(($item['is_read'] ?? false) ? 'read' : 'unread') ?>"><?= h(($item['is_read'] ?? false) ? 'อ่านแล้ว' : 'ยังไม่อ่าน') ?></span></td>
                                <td>
                                    <button
                                        class="button-more-details js-open-circular-modal"
                                        type="button"
                                        data-circular-id="<?= h((string) (int) ($item['circular_id'] ?? 0)) ?>"
                                        data-inbox-id="<?= h((string) (int) ($item['inbox_id'] ?? 0)) ?>"
                                        data-subject="<?= h((string) ($item['subject'] ?? '')) ?>"
                                        data-sender="<?= h((string) ($item['sender_name'] ?? '-')) ?>"
                                        data-date="<?= h((string) ($item['delivered_date_long'] ?? $item['delivered_date'] ?? '-')) ?>"
                                        data-detail="<?= h((string) ($item['detail'] ?? '')) ?>"
                                        data-link="<?= h((string) ($item['link_url'] ?? '')) ?>"
                                        data-type="<?= h((string) ($item['type_label'] ?? '')) ?>"
                                        data-files="<?= h($file_json) ?>">
                                        <p>รายละเอียด</p>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="modal-overlay-circular-notice-keep" id="modalNoticeKeepOverlay">
            <div class="modal-content">
                <div class="header-modal">
                    <p id="modalTypeLabel">ประเภทหนังสือ</p>
                    <i class="fa-solid fa-xmark" id="closeModalNoticeKeep"></i>
                </div>

                <div class="content-modal">
                    <div class="content-topic-sec">
                        <p><strong>หัวเรื่อง :</strong></p>
                        <p id="modalSubject">-</p>
                    </div>
                    <div class="content-topic-sec">
                        <p><strong>ผู้ส่ง :</strong></p>
                        <p id="modalSender">-</p>
                    </div>
                    <div class="content-topic-sec">
                        <p><strong>วันที่ส่ง :</strong></p>
                        <p id="modalDate">-</p>
                    </div>

                    <div class="content-details-sec">
                        <p><strong>รายละเอียดเพิ่มเติม</strong></p>
                        <p id="modalDetail">-</p>
                    </div>
                    <div class="content-details-sec">
                        <p><strong>ลิ้งก์แนบจากระบบ</strong></p>
                        <a id="modalLink" href="#" target="_blank" rel="noopener">-</a>
                    </div>

                    <div class="content-file-sec">
                        <p>ไฟล์เอกสารแนบจากระบบ</p>
                        <div class="file-section" id="modalFileSection"></div>
                    </div>
                </div>

                <div class="footer-modal">
                    <form method="POST" id="modalArchiveForm">
                        <?= csrf_field() ?>
                        <input type="hidden" name="inbox_id" id="modalInboxId" value="">
                        <input type="hidden" name="action" value="unarchive">
                        <button type="submit"><p>ย้ายกลับ</p></button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <div class="button-circular-notice-keep"></div>
<?php else : ?>
    <div class="circular-notice-archive-notice-content">
        <header class="header-circular-notice-archive outside-person">
            <div class="circular-notice-archive-control outside-person">
                <div class="page-selector">
                    <p>แสดงตามประเภทหนังสือ</p>
                    <div class="checkbox-group">
                        <div>
                            <input type="checkbox" class="archive-control-checkbox" data-filter-type value="external"<?= $type_external_checked ? ' checked' : '' ?>>
                            <p>ภายนอก</p>
                        </div>
                        <div>
                            <input type="checkbox" class="archive-control-checkbox" data-filter-type value="internal"<?= $type_internal_checked ? ' checked' : '' ?>>
                            <p>ภายใน</p>
                        </div>
                    </div>
                </div>

                <div class="page-selector">
                    <p>แสดงตามสถานะหนังสือ</p>
                    <div class="checkbox-group">
                        <div>
                            <input type="checkbox" class="archive-control-checkbox" data-filter-read value="read"<?= $read_checked ? ' checked' : '' ?>>
                            <p>อ่านแล้ว</p>
                        </div>
                        <div>
                            <input type="checkbox" class="archive-control-checkbox" data-filter-read value="unread"<?= $unread_checked ? ' checked' : '' ?>>
                            <p>ยังไม่อ่าน</p>
                        </div>
                    </div>
                </div>

                <div class="page-selector">
                    <p>แสดงตาม</p>

                    <div class="custom-select-wrapper" data-target="filterSortInput">
                        <div class="custom-select-trigger">
                            <p class="select-value"><?= h($filter_sort === 'oldest' ? 'เก่าไปใหม่' : 'ใหม่ไปเก่า') ?></p>
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>

                        <div class="custom-options">
                            <div class="custom-option<?= h($filter_sort === 'newest' ? ' selected' : '') ?>" data-value="newest">ใหม่ไปเก่า</div>
                            <div class="custom-option<?= h($filter_sort === 'oldest' ? ' selected' : '') ?>" data-value="oldest">เก่าไปใหม่</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-change">
                <p>ตาราง</p>
                <div class="button-table">
                    <button class="<?= h($filter_view === 'table1' ? 'active' : '') ?>" type="button" data-view="table1">ตาราง 1</button>
                    <button class="<?= h($filter_view === 'table2' ? 'active' : '') ?>" type="button" data-view="table2">ตาราง 2</button>
                </div>
            </div>
        </header>

        <section class="content-circular-notice-archive outside-person" data-circular-notice>
            <div class="search-bar">
                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="search-input" name="q" form="circularFilterForm" value="<?= h($filter_search) ?>" placeholder="ค้นหาข้อความด้วย...">
                </div>
            </div>

            <form id="bulkActionForm" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="unarchive_selected">
                <div class="table-circular-notice-archive outside-person">
                    <table>
                        <thead>
                            <tr>
                                <th>
                                    <input type="checkbox" class="check-table checkall" id="checkAllCircular">
                                </th>
                                <th>ประเภทหนังสือ</th>
                                <th>หัวเรื่อง</th>
                                <th>ผู้ส่ง</th>
                                <th>วันที่ส่ง</th>
                                <th>สถานะ</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($items)) : ?>
                                <tr>
                                    <td colspan="7" class="booking-empty">ไม่มีรายการ</td>
                                </tr>
                            <?php else : ?>
                                <?php foreach ($items as $item) : ?>
                                    <?php $file_json = (string) ($item['files_json'] ?? '[]'); ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="check-table" name="selected_ids[]" value="<?= h((string) (int) ($item['inbox_id'] ?? 0)) ?>">
                                        </td>
                                        <td><?= h((string) ($item['type_label'] ?? '')) ?></td>
                                        <td><?= h((string) ($item['subject'] ?? '')) ?></td>
                                        <td><?= h((string) ($item['sender_name'] ?? '-')) ?></td>
                                        <td><?= h((string) ($item['delivered_date'] ?? '-')) ?></td>
                                        <td><span class="status-badge <?= h(($item['is_read'] ?? false) ? 'read' : 'unread') ?>"><?= h(($item['is_read'] ?? false) ? 'อ่านแล้ว' : 'ยังไม่อ่าน') ?></span></td>
                                        <td>
                                            <button
                                                class="button-more-details js-open-circular-modal"
                                                type="button"
                                                data-circular-id="<?= h((string) (int) ($item['circular_id'] ?? 0)) ?>"
                                                data-inbox-id="<?= h((string) (int) ($item['inbox_id'] ?? 0)) ?>"
                                                data-urgency="<?= h((string) ($item['ext_priority_label'] ?? 'ปกติ')) ?>"
                                                data-urgency-class="<?= h((string) ($item['urgency_class'] ?? 'normal')) ?>"
                                                data-bookno="<?= h((string) ($item['ext_book_no'] ?? '')) ?>"
                                                data-issued="<?= h((string) ($item['ext_issued_date'] ?? '-')) ?>"
                                                data-from="<?= h((string) ($item['ext_from_text'] ?? '')) ?>"
                                                data-to="<?= h($director_label) ?>"
                                                data-subject="<?= h((string) ($item['subject'] ?? '')) ?>"
                                                data-detail="<?= h((string) ($item['detail'] ?? '')) ?>"
                                                data-status="<?= h((string) ($item['status_label'] ?? '-')) ?>"
                                                data-consider="<?= h((string) ($item['consider_class'] ?? 'considering')) ?>"
                                                data-files="<?= h($file_json) ?>"
                                                data-received-time="<?= h((string) ($item['delivered_time'] ?? '-')) ?>">
                                                <p>รายละเอียด</p>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </form>

            <div class="modal-overlay-circular-notice-archive outside-person" id="modalNoticeKeepOverlay">
                <div class="modal-content">
                    <div class="header-modal">
                        <div class="first-header">
                            <p>แสดงข้อความรายละเอียดหนังสือเวียน</p>
                        </div>
                        <div class="sec-header">
                            <div class="consider-status considering" id="modalConsiderStatus">กำลังเสนอ</div>
                            <i class="fa-solid fa-xmark" id="closeModalNoticeKeep"></i>
                        </div>
                    </div>

                    <div class="content-modal">
                        <div class="content-topic-sec">
                            <p><strong>ความเร่งด่วน :</strong></p>
                            <button class="urgency-status normal" id="modalUrgency"><p>ปกติ</p></button>
                        </div>
                        <div class="content-topic-sec">
                            <div class="more-details">
                                <p><strong>เลขที่หนังสือ :</strong></p>
                                <input type="text" id="modalBookNo" placeholder="-" disabled>
                            </div>
                            <div class="more-details">
                                <p><strong>ลงวันที่ : </strong></p>
                                <input type="text" id="modalIssuedDate" placeholder="-" disabled>
                            </div>
                            <div class="more-details">
                                <p><strong>จาก : </strong></p>
                                <input type="text" id="modalFromText" placeholder="-" disabled>
                            </div>
                            <div class="more-details">
                                <p><strong>ถึง : </strong></p>
                                <input type="text" id="modalToText" placeholder="-" disabled>
                            </div>
                        </div>

                        <div class="content-details-sec">
                            <p><strong>หัวเรื่อง :</strong></p>
                            <p id="modalSubject">-</p>
                        </div>
                        <div class="content-details-sec">
                            <p><strong>รายละเอียดเพิ่มเติม</strong></p>
                            <p id="modalDetail">-</p>
                        </div>

                        <div class="content-file-sec">
                            <p>ไฟล์เอกสารแนบจากระบบ</p>
                            <div class="file-section" id="modalFileSection"></div>
                        </div>

                        <div class="content-time-and-considered-sec">
                            <div class="more-details">
                                <p><strong>รับหนังสือเข้าระบบ : </strong></p>
                                <input type="text" id="modalReceivedTime" placeholder="-" disabled>
                            </div>
                            <div class="more-details">
                                <p><strong>สถานะ : </strong></p>
                                <input type="text" id="modalStatus" placeholder="-" disabled>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div class="button-circular-notice-archive outside-person">
        <button class="button-keep" type="submit" form="bulkActionForm">
            <i class="fa-solid fa-file-import"></i>
            <p>ย้ายกลับ</p>
        </button>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';

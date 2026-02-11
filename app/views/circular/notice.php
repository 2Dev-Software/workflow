<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../auth/csrf.php';
require_once __DIR__ . '/../view.php';

$items = (array) ($items ?? []);
$box_key = (string) ($box_key ?? 'normal');
$archived = (bool) ($archived ?? false);
$filter_type = (string) ($filter_type ?? 'all');
$filter_read = (string) ($filter_read ?? 'all');
$filter_sort = (string) ($filter_sort ?? 'newest');
$filter_view = (string) ($filter_view ?? 'table1');
$filter_search = (string) ($filter_search ?? '');
$is_outside_view = (bool) ($is_outside_view ?? false);
$director_label = (string) ($director_label ?? 'ผอ./รักษาการ');

ob_start();
?>
<style>
    .circular-action-stack {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .circular-action-stack .button-more-details,
    .circular-action-stack .button-open-workflow {
        min-width: 92px;
    }

    .circular-action-stack .button-open-workflow {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        height: 32px;
        border: 1px solid rgba(var(--rgb-secondary), 0.4);
        border-radius: 8px;
        background: #fff;
        color: var(--color-secondary);
        text-decoration: none;
        font-size: 13px;
        font-weight: 700;
        padding: 0 10px;
        line-height: 1;
    }

    .circular-action-stack .button-open-workflow:hover {
        background: rgba(var(--rgb-secondary), 0.08);
    }

    .circular-sender-stack {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 2px;
        line-height: 1.25;
    }

    .circular-sender-name {
        font-weight: 700;
        color: var(--color-primary-dark);
    }

    .circular-sender-faction {
        font-size: 12px;
        color: #111111;
    }

    #modalSender {
        white-space: pre-line;
    }
</style>
<div class="content-header">
    <h1>ยินดีต้อนรับ</h1>
    <p>หนังสือเวียน / <?= h($archived ? 'หนังสือเวียนที่จัดเก็บ' : 'กล่องข้อความ') ?></p>
</div>

<form id="circularFilterForm" method="GET">
    <input type="hidden" name="box" value="<?= h($box_key) ?>">
    <?php if ($archived) : ?>
        <input type="hidden" name="archived" value="1">
    <?php endif; ?>
    <input type="hidden" name="type" id="filterTypeInput" value="<?= h($filter_type) ?>">
    <input type="hidden" name="read" id="filterReadInput" value="<?= h($filter_read) ?>">
    <input type="hidden" name="sort" id="filterSortInput" value="<?= h($filter_sort) ?>">
    <input type="hidden" name="view" id="filterViewInput" value="<?= h($filter_view) ?>">
</form>
<input type="hidden" id="csrfToken" value="<?= h(csrf_token()) ?>">

<header class="header-circular-notice-index<?= h($is_outside_view ? ' outside-person' : '') ?>">
    <div class="circular-notice-index-control">
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

    <?php if ($is_outside_view) : ?>
        <div class="table-change">
            <p>ตาราง</p>
            <div class="button-table">
                <button class="<?= h($filter_view === 'table1' ? 'active' : '') ?>" type="button" data-view="table1">ตาราง 1</button>
                <button class="<?= h($filter_view === 'table2' ? 'active' : '') ?>" type="button" data-view="table2">ตาราง 2</button>
            </div>
        </div>
    <?php endif; ?>
</header>

<section class="content-circular-notice-index" data-circular-notice>
    <div class="search-bar">
        <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="search-input" name="q" form="circularFilterForm" value="<?= h($filter_search) ?>" placeholder="ค้นหาข้อความด้วย...">
        </div>
    </div>

    <?php if (!$is_outside_view) : ?>
        <form id="bulkActionForm" method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="<?= h($archived ? 'unarchive_selected' : 'archive_selected') ?>">
            <div class="table-circular-notice-index">
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
                                <td colspan="7" class="enterprise-empty">ไม่มีรายการ</td>
                            </tr>
                        <?php else : ?>
                            <?php foreach ($items as $item) : ?>
                                <?php
                                $is_read = (bool) ($item['is_read'] ?? false);
                                $file_json = (string) ($item['files_json'] ?? '[]');
                                $sender_modal_text = trim((string) ($item['sender_name'] ?? '-'));
                                if (!empty($item['sender_faction_name'])) {
                                    $sender_modal_text .= "\n" . trim((string) $item['sender_faction_name']);
                                }
                                ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" class="check-table" name="selected_ids[]" value="<?= h((string) (int) ($item['inbox_id'] ?? 0)) ?>">
                                    </td>
                                    <td><?= h((string) ($item['type_label'] ?? '')) ?></td>
                                    <td><?= h((string) ($item['subject'] ?? '')) ?></td>
                                    <td>
                                        <div class="circular-sender-stack">
                                            <span class="circular-sender-name"><?= h((string) ($item['sender_name'] ?? '-')) ?></span>
                                            <?php if (!empty($item['sender_faction_name'])) : ?>
                                                <span class="circular-sender-faction"><?= h((string) $item['sender_faction_name']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><?= h((string) ($item['delivered_date'] ?? '-')) ?></td>
                                    <td><span class="status-badge <?= h($is_read ? 'read' : 'unread') ?>"><?= h($is_read ? 'อ่านแล้ว' : 'ยังไม่อ่าน') ?></span></td>
                                    <td>
                                        <div class="circular-action-stack">
                                            <button
                                                class="button-more-details js-open-circular-modal"
                                                type="button"
                                                data-circular-id="<?= h((string) (int) ($item['circular_id'] ?? 0)) ?>"
                                                data-inbox-id="<?= h((string) (int) ($item['inbox_id'] ?? 0)) ?>"
                                                data-subject="<?= h((string) ($item['subject'] ?? '')) ?>"
                                                data-sender="<?= h($sender_modal_text) ?>"
                                                data-date="<?= h((string) ($item['delivered_date_long'] ?? $item['delivered_date'] ?? '-')) ?>"
                                                data-detail="<?= h((string) ($item['detail'] ?? '')) ?>"
                                                data-link="<?= h((string) ($item['link_url'] ?? '')) ?>"
                                                data-type="<?= h((string) ($item['type_label'] ?? '')) ?>"
                                                data-files="<?= h($file_json) ?>">
                                                <p>รายละเอียด</p>
                                            </button>
                                            <a class="button-open-workflow" href="circular-view.php?inbox_id=<?= h((string) (int) ($item['inbox_id'] ?? 0)) ?>">อ่าน/ส่งต่อ</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </form>
    <?php else : ?>
        <div class="table-circular-notice-index outside-person">
            <table>
                <thead>
                    <tr>
                        <th>วันที่รับ</th>
                        <th>เลขที่ / เรื่อง</th>
                        <th>ความเร่งด่วน</th>
                        <th>สถานะปัจุบัน</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)) : ?>
                        <tr>
                            <td colspan="5" class="enterprise-empty">ไม่มีรายการ</td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($items as $item) : ?>
                            <?php
                            $file_json = (string) ($item['files_json'] ?? '[]');
                            $priority_label = (string) ($item['ext_priority_label'] ?? 'ปกติ');
                            ?>
                            <tr>
                                <td>
                                    <p><?= h((string) ($item['delivered_date'] ?? '-')) ?></p>
                                    <p><?= h((string) ($item['delivered_time'] ?? '-')) ?></p>
                                </td>
                                <td>
                                    <p><?= h((string) ($item['ext_book_no'] ?? '-')) ?></p>
                                    <p><?= h((string) ($item['subject'] ?? '')) ?></p>
                                </td>
                                <td><button class="urgency-status <?= h((string) ($item['urgency_class'] ?? 'normal')) ?>"><p><?= h($priority_label) ?></p></button></td>
                                <td><?= h((string) ($item['status_label'] ?? '-')) ?></td>
                                <td>
                                    <div class="circular-action-stack">
                                        <button
                                            class="button-more-details js-open-circular-modal"
                                            type="button"
                                            data-circular-id="<?= h((string) (int) ($item['circular_id'] ?? 0)) ?>"
                                            data-inbox-id="<?= h((string) (int) ($item['inbox_id'] ?? 0)) ?>"
                                            data-urgency="<?= h($priority_label) ?>"
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
                                        <a class="button-open-workflow" href="circular-view.php?inbox_id=<?= h((string) (int) ($item['inbox_id'] ?? 0)) ?>">อ่าน/ดำเนินการ</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php if (!$is_outside_view) : ?>
        <div class="modal-overlay-circular-notice-index" id="modalNoticeKeepOverlay">
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
                        <input type="hidden" name="action" value="<?= h($archived ? 'unarchive' : 'archive') ?>">
                        <button type="submit"><p><?= h($archived ? 'ย้ายกลับ' : 'จัดเก็บ') ?></p></button>
                    </form>
                </div>
            </div>
        </div>
    <?php else : ?>
        <div class="modal-overlay-circular-notice-index outside-person" id="modalNoticeKeepOverlay">
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
    <?php endif; ?>
</section>

<?php if (!$is_outside_view) : ?>
    <div class="button-circular-notice-index">
        <button class="button-keep" type="submit" form="bulkActionForm">
            <i class="fa-solid fa-file-import"></i>
            <p><?= h($archived ? 'ย้ายกลับ' : 'จัดเก็บ') ?></p>
        </button>
    </div>
<?php else : ?>
    <div class="button-circular-notice-index"></div>
<?php endif; ?>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';

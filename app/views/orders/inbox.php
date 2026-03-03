<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../auth/csrf.php';

$items = (array) ($items ?? []);
$archived = (bool) ($archived ?? false);
$page = (int) ($page ?? 1);
$total_pages = (int) ($total_pages ?? 1);
$search = trim((string) ($search ?? ''));
$status_filter = (string) ($status_filter ?? 'all');
$sort = (string) ($sort ?? 'newest');
$per_page = (string) ($per_page ?? '10');
$filtered_total = (int) ($filtered_total ?? 0);
$pagination_base_url = (string) ($pagination_base_url ?? ('orders-inbox.php?archived=' . ($archived ? '1' : '0')));

$status_options = [
    'all' => 'ทั้งหมด',
    'unread' => 'ยังไม่อ่าน',
    'read' => 'อ่านแล้ว',
];

$sort_options = [
    'newest' => 'ใหม่ไปเก่า',
    'oldest' => 'เก่าไปใหม่',
    'order_no' => 'เลขที่คำสั่ง',
    'unread_first' => 'ยังไม่อ่านก่อน',
];

$sort_label = $sort_options[$sort] ?? $sort_options['newest'];
$status_label = $status_options[$status_filter] ?? $status_options['all'];
$bulk_action = $archived ? 'unarchive_selected' : 'archive_selected';
$page_title = $archived ? 'คำสั่งราชการที่จัดเก็บ' : 'ยินดีต้อนรับ';
$page_subtitle = $archived ? 'รายการคำสั่งราชการที่จัดเก็บ' : 'คำสั่งราชการ / กล่องคำสั่งราชการ';
$bulk_action_url = 'orders-inbox.php?' . http_build_query([
    'archived' => $archived ? '1' : '0',
    'q' => $search,
    'status' => $status_filter,
    'sort' => $sort,
    'per_page' => $per_page,
    'page' => (string) $page,
]);

$thai_months = [
    1 => 'มกราคม',
    2 => 'กุมภาพันธ์',
    3 => 'มีนาคม',
    4 => 'เมษายน',
    5 => 'พฤษภาคม',
    6 => 'มิถุนายน',
    7 => 'กรกฎาคม',
    8 => 'สิงหาคม',
    9 => 'กันยายน',
    10 => 'ตุลาคม',
    11 => 'พฤศจิกายน',
    12 => 'ธันวาคม',
];

$format_thai_received_datetime = static function (?string $datetime_value) use ($thai_months): array {
    $text = trim((string) $datetime_value);
    if ($text === '' || $text === '-') {
        return ['date' => '-', 'time' => ''];
    }

    $timestamp = strtotime($text);
    if ($timestamp === false) {
        return ['date' => $text, 'time' => ''];
    }

    $day = (int) date('j', $timestamp);
    $month = (int) date('n', $timestamp);
    $year_be = (int) date('Y', $timestamp) + 543;
    $month_label = $thai_months[$month] ?? '';
    $date_label = $day . ' ' . $month_label . ' ' . $year_be;
    $time_label = date('H:i', $timestamp) . ' น.';

    return ['date' => trim($date_label), 'time' => $time_label];
};

ob_start();
?>

<div class="content-header">
    <h1><?= h($page_title) ?></h1>
    <p><?= h($page_subtitle) ?></p>
</div>

<form id="circularFilterForm" method="GET" action="orders-inbox.php">
    <input type="hidden" name="page" id="filterPageInput" value="1">
    <input type="hidden" name="per_page" id="filterPerPageInput" value="<?= h($per_page) ?>">
    <input type="hidden" name="archived" id="filterArchivedInput" value="<?= h($archived ? '1' : '0') ?>">
</form>

<header class="header-circular-notice-index">
    <div class="circular-notice-index-control">
        <div class="page-selector">
            <p>แสดงตามปีสารบรรณ</p>
            <div class="custom-select-wrapper">
                <div class="custom-select-trigger">
                    <p class="select-value"><?= h($status_label) ?></p>
                    <i class="fa-solid fa-chevron-down"></i>
                </div>

                <div class="custom-options">
                    <?php foreach ($status_options as $status_key => $label) : ?>
                        <div class="custom-option<?= $status_filter === $status_key ? ' selected' : '' ?>" data-value="<?= h($status_key) ?>"><?= h($label) ?></div>
                    <?php endforeach; ?>
                </div>

                <input type="hidden" name="status" id="filterReadInput" value="<?= h($status_filter) ?>" form="circularFilterForm">
            </div>
        </div>

        <div class="page-selector">
            <p>แสดงตามสถานะหนังสือ</p>
            <div class="custom-select-wrapper">
                <div class="custom-select-trigger">
                    <p class="select-value"><?= h($status_label) ?></p>
                    <i class="fa-solid fa-chevron-down"></i>
                </div>

                <div class="custom-options">
                    <?php foreach ($status_options as $status_key => $label) : ?>
                        <div class="custom-option<?= $status_filter === $status_key ? ' selected' : '' ?>" data-value="<?= h($status_key) ?>"><?= h($label) ?></div>
                    <?php endforeach; ?>
                </div>

                <input type="hidden" name="status" id="filterReadInput" value="<?= h($status_filter) ?>" form="circularFilterForm">
            </div>
        </div>

        <div class="page-selector">
            <p>แสดงตาม</p>
            <div class="custom-select-wrapper">
                <div class="custom-select-trigger">
                    <p class="select-value"><?= h($sort_label) ?></p>
                    <i class="fa-solid fa-chevron-down"></i>
                </div>

                <div class="custom-options">
                    <?php foreach ($sort_options as $sort_key => $label) : ?>
                        <div class="custom-option<?= $sort === $sort_key ? ' selected' : '' ?>" data-value="<?= h($sort_key) ?>"><?= h($label) ?></div>
                    <?php endforeach; ?>
                </div>

                <input type="hidden" name="sort" id="filterSortInput" value="<?= h($sort) ?>" form="circularFilterForm">
            </div>
        </div>
        
    </div>
</header>

<section class="content-circular-notice-index" data-orders-inbox>
    <div class="search-bar">
        <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input
                type="text"
                id="search-input"
                name="q"
                form="circularFilterForm"
                value="<?= h($search) ?>"
                placeholder="ค้นหาเลขที่คำสั่ง หรือ เรื่อง...">
        </div>
    </div>

    <form id="bulkActionForm" method="POST" action="<?= h($bulk_action_url) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="<?= h($bulk_action) ?>">

        <div class="table-circular-notice-index orders-inbox-table">
            <table>
                <thead>
                    <tr>
                        <th><input type="checkbox" class="check-table checkall" id="checkAllOrdersInbox"></th>
                        <th>เรื่อง / เลขที่คำสั่ง</th>
                        <th>ผู้ส่งคำสั่ง</th>
                        <th>วันที่รับ</th>
                        <th>สถานะ</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)) : ?>
                        <tr>
                            <td colspan="6" class="enterprise-empty">ไม่มีรายการ</td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($items as $item) : ?>
                            <?php
                            $inbox_id = (int) ($item['inboxID'] ?? 0);
                            $order_id = (int) ($item['orderID'] ?? 0);
                            $order_no = trim((string) ($item['orderNo'] ?? ''));
                            $subject = trim((string) ($item['subject'] ?? ''));
                            $sender_name = trim((string) ($item['senderName'] ?? ''));
                            $delivered_at = trim((string) ($item['deliveredAt'] ?? '-'));
                            $received_display = $format_thai_received_datetime($delivered_at);
                            $is_read = (int) ($item['isRead'] ?? 0) === 1;
                            $view_href = 'orders-view.php?inbox_id=' . $inbox_id;
                            ?>
                            <tr>
                                <td>
                                    <?php if ($inbox_id > 0) : ?>
                                        <input type="checkbox" class="check-table js-order-row-check" name="selected_ids[]" value="<?= h((string) $inbox_id) ?>">
                                    <?php endif; ?>
                                </td>
                                <td class="orders-inbox-topic-cell">
                                    <p class="orders-inbox-subject"><?= h($subject !== '' ? $subject : '-') ?></p>
                                    <p class="orders-inbox-order-no">เลขที่คำสั่ง <?= h($order_no !== '' ? $order_no : ('#' . $order_id)) ?></p>
                                </td>
                                <td><?= h($sender_name !== '' ? $sender_name : '-') ?></td>
                                <td class="orders-inbox-date-cell">
                                    <p class="orders-inbox-date"><?= h($received_display['date']) ?></p>
                                    <?php if ($received_display['time'] !== '') : ?>
                                        <p class="orders-inbox-time"><?= h($received_display['time']) ?></p>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge <?= h($is_read ? 'read' : 'unread') ?>"><?= h($is_read ? 'อ่านแล้ว' : 'ยังไม่อ่าน') ?></span>
                                </td>
                                <td>
                                    <button
                                        class="booking-action-btn secondary js-open-order-view-modal"
                                        type="button">
                                        <i class="fa-solid fa-eye"></i>
                                        <span class="tooltip">รายละเอียด</span>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </form>

    <?php if ($per_page !== 'all' && $total_pages > 1) : ?>
        <?php component_render('pagination', [
            'page' => $page,
            'total_pages' => $total_pages,
            'base_url' => $pagination_base_url,
            'class' => 'u-mt-2',
        ]); ?>
    <?php endif; ?>
</section>

<div class="button-circular-notice-index">
    <button class="button-keep" type="submit" form="bulkActionForm">
        <i class="fa-solid fa-file-import"></i>
        <p><?= h($archived ? 'ย้ายกลับ' : 'จัดเก็บ') ?></p>
    </button>
</div>

<div class="content-circular-notice-index circular-track-modal-host">
    <div class="modal-overlay-circular-notice-index outside-person" id="modalOrderViewOverlay">
        <div class="modal-content">
            <div class="header-modal">
                <div class="first-header">
                    <p>รายชื่อผู้รับเอกสาร</p>
                </div>
                <div class="sec-header">
                    <i class="fa-solid fa-xmark" id="closeModalOrderView"></i>
                </div>
            </div>
            <div class="content-modal">
                <div class="content-topic-sec">
                    <div class="more-details">
                        <p><strong>คำสั่งที่</strong></p>
                        <input type="text" id="modalOrderSendNo" class="order-no-display" value="-" disabled>
                    </div>
                    <div class="more-details">
                        <p><strong>เรื่อง</strong></p>
                        <input type="text" id="modalOrderSendSubject" class="order-no-display" value="-" disabled>
                    </div>
                </div>

                <div class="content-topic-sec">
                    <div class="more-details">
                        <p><strong>ทั้งนี้ตั้งแต่วันที่</strong></p>
                        <input type="date" id="modalOrderSendEffectiveDate" class="order-no-display" value="" disabled>
                    </div>
                    <div class="more-details">
                        <p><strong>สั่ง ณ วันที่</strong></p>
                        <input type="date" id="modalOrderSendDate" class="order-no-display" value="" disabled>
                    </div>
                </div>

                <div class="content-topic-sec">
                    <div class="more-details">
                        <p><strong>ผู้ออกเลขคำสั่ง</strong></p>
                        <input type="text" id="modalOrderSendIssuer" class="order-no-display" value="-" disabled>
                    </div>
                    <div class="more-details">
                        <p><strong>กลุ่ม</strong></p>
                        <input type="text" id="modalOrderSendGroup" class="order-no-display" value="-" disabled>
                    </div>
                </div>

                <div class="orders-send-modal-shell orders-send-card">
                    <div id="modalOrderSendFormSection">
                        <form method="POST" action="orders-create.php" class="orders-send-form" id="modalOrderSendForm">
                            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                            <input type="hidden" name="order_action" value="send">
                            <input type="hidden" name="send_order_id" id="modalOrderSendOrderId" value="">
                        </form>
                    </div>
                </div>

                <div class="content-file-sec">
                    <p><strong>ไฟล์เอกสารแนบจากระบบ</strong></p>
                    <div class="file-list" id="existingFileListContainer_modal">
                        <p class="existing-file-empty">ยังไม่มีไฟล์แนบ</p>
                        <!-- <div class="file-banner">
                            <div class="file-info">
                                <div class="file-icon"><i class="fa-solid fa-image" aria-hidden="true"></i></div>
                                <div class="file-text">
                                    <span class="file-name">Screenshot 2569-03-01 at 14.48.38.png</span>
                                    <span class="file-type">image/png</span>
                                </div>
                            </div>
                            <div class="file-actions">
                                <a href="public/api/file-download.php?module=orders&amp;entity_id=93&amp;file_id=121" target="_blank" rel="noopener">
                                    <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                </a>
                            </div>
                        </div> -->

                    </div>
                </div>



            </div>

        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const filterForm = document.getElementById('circularFilterForm');
        const pageInput = document.getElementById('filterPageInput');
        const sectionSelector = 'section[data-orders-inbox]';
        const loadingApi = window.App && window.App.loading ? window.App.loading : null;
        let isRequestInFlight = false;
        let requestToken = 0;
        let pendingRequest = null;
        let searchTimer = null;

        const getSection = () => document.querySelector(sectionSelector);

        const getSearchInput = () => document.getElementById('search-input');

        const buildRequestUrl = () => {
            if (!filterForm) {
                return '';
            }
            const formData = new FormData(filterForm);
            const params = new URLSearchParams();

            formData.forEach((value, key) => {
                params.set(key, String(value));
            });

            const query = params.toString();

            if (query === '') {
                return filterForm.action;
            }

            return filterForm.action + '?' + query;
        };

        const syncBulkCheckState = () => {
            const checkAll = document.getElementById('checkAllOrdersInbox');
            const rowChecks = Array.from(document.querySelectorAll('.js-order-row-check'));

            if (!checkAll) {
                return;
            }
            if (rowChecks.length === 0) {
                checkAll.checked = false;
                checkAll.indeterminate = false;
                return;
            }
            const checkedCount = rowChecks.filter((checkbox) => checkbox.checked).length;
            checkAll.checked = checkedCount > 0 && checkedCount === rowChecks.length;
            checkAll.indeterminate = checkedCount > 0 && checkedCount < rowChecks.length;
        };

        const bindBulkFormEvents = () => {
            const bulkForm = document.getElementById('bulkActionForm');
            const checkAll = document.getElementById('checkAllOrdersInbox');
            const rowChecks = Array.from(document.querySelectorAll('.js-order-row-check'));

            if (checkAll) {
                checkAll.addEventListener('change', () => {
                    rowChecks.forEach((checkbox) => {
                        checkbox.checked = checkAll.checked;
                    });
                    syncBulkCheckState();
                });
            }

            rowChecks.forEach((checkbox) => {
                checkbox.addEventListener('change', () => {
                    syncBulkCheckState();
                });
            });

            bulkForm?.addEventListener('submit', (event) => {
                const checkedCount = rowChecks.filter((checkbox) => checkbox.checked).length;
                if (checkedCount > 0) {
                    return;
                }
                event.preventDefault();
                if (window.AppAlerts && typeof window.AppAlerts.fire === 'function') {
                    window.AppAlerts.fire({
                        type: 'warning',
                        title: 'แจ้งเตือน',
                        message: 'กรุณาเลือกรายการก่อนดำเนินการ',
                    });
                } else {
                    window.alert('กรุณาเลือกรายการก่อนดำเนินการ');
                }
            });

            syncBulkCheckState();
        };

        const applyHtmlUpdate = (htmlText, requestUrl) => {
            const parser = new DOMParser();
            const nextDocument = parser.parseFromString(htmlText, 'text/html');

            const currentSection = getSection();
            const currentBulkForm = document.getElementById('bulkActionForm');
            const nextBulkForm = nextDocument.getElementById('bulkActionForm');

            if (!currentSection || !currentBulkForm || !nextBulkForm) {
                window.location.assign(requestUrl);
                return;
            }

            currentBulkForm.replaceWith(nextBulkForm);

            const currentPagination = currentSection.querySelector('.c-pagination');
            const nextPagination = nextDocument.querySelector(sectionSelector + ' .c-pagination');

            if (nextPagination && nextBulkForm.parentNode) {
                if (currentPagination) {
                    currentPagination.replaceWith(nextPagination);
                } else {
                    nextBulkForm.insertAdjacentElement('afterend', nextPagination);
                }
            } else if (!nextPagination && currentPagination) {
                currentPagination.remove();
            }

            window.history.replaceState({}, '', requestUrl);
            bindBulkFormEvents();
        };

        const submitFilter = async (options = {}) => {
            const {
                resetPage = false, requestUrl = ''
            } = options;

            if (!filterForm) {
                return;
            }

            if (resetPage && pageInput) {
                pageInput.value = '1';
            }

            const targetUrl = requestUrl !== '' ? requestUrl : buildRequestUrl();

            if (targetUrl === '' || typeof window.fetch !== 'function') {
                filterForm.submit();
                return;
            }
            if (isRequestInFlight) {
                pendingRequest = {
                    resetPage: resetPage,
                    requestUrl: requestUrl,
                };
                return;
            }

            isRequestInFlight = true;
            requestToken += 1;
            const currentToken = requestToken;

            const sectionNode = getSection();
            if (loadingApi) {
                loadingApi.startComponent(sectionNode);
            }

            try {
                const response = await window.fetch(targetUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    throw new Error('Failed to fetch inbox list');
                }

                const htmlText = await response.text();

                if (currentToken !== requestToken) {
                    return;
                }

                applyHtmlUpdate(htmlText, targetUrl);
            } catch (error) {
                window.location.assign(targetUrl);
            } finally {
                if (loadingApi) {
                    loadingApi.stopComponent(getSection());
                }
                if (currentToken === requestToken) {
                    isRequestInFlight = false;
                }

                if (pendingRequest !== null) {
                    const nextRequest = pendingRequest;
                    pendingRequest = null;
                    submitFilter(nextRequest);
                }
            }
        };

        document.querySelectorAll('.header-circular-notice-index .custom-select-wrapper .custom-option').forEach((option) => {
            option.addEventListener('click', () => {
                window.setTimeout(() => {
                    submitFilter({
                        resetPage: true,
                    });
                }, 0);
            });
        });

        document.addEventListener('click', (event) => {
            const paginationLink = event.target.closest(sectionSelector + ' .c-pagination a[href]');
            if (!paginationLink) {
                return;
            }
            event.preventDefault();

            const href = paginationLink.getAttribute('href') || '';
            if (href === '') {
                return;
            }

            const absoluteUrl = new URL(href, window.location.href);
            const nextPage = absoluteUrl.searchParams.get('page');

            if (pageInput) {
                pageInput.value = nextPage && nextPage !== '' ? nextPage : '1';
            }

            submitFilter({
                requestUrl: absoluteUrl.pathname + (absoluteUrl.search || ''),
            });
        });

        const searchInput = getSearchInput();
        if (searchInput) {
            searchInput.addEventListener('input', () => {
                if (searchTimer) {
                    window.clearTimeout(searchTimer);
                }
                searchTimer = window.setTimeout(() => {
                    submitFilter({
                        resetPage: true,
                    });
                }, 300);
            });

            searchInput.addEventListener('keydown', (event) => {
                if (event.key !== 'Enter') {
                    return;
                }
                event.preventDefault();
                if (searchTimer) {
                    window.clearTimeout(searchTimer);
                }
                submitFilter({
                    resetPage: true,
                });
            });
        }

        bindBulkFormEvents();
    });

    document.addEventListener('DOMContentLoaded', function() {
        if (window.__ordersCreateModalFallbackBound) {
            return;
        }
        window.__ordersCreateModalFallbackBound = true;

        const editModal = document.getElementById('modalOrderEditOverlay');
        const sendModal = document.getElementById('modalOrderSendOverlay');
        const viewModal = document.getElementById('modalOrderViewOverlay')
        const closeEdit = document.getElementById('closeModalOrderEdit');
        const closeSend = document.getElementById('closeModalOrderSend');
        const closeView = document.getElementById('closeModalOrderView')

        const setValue = (id, value) => {
            const el = document.getElementById(id);
            if (!el) return;
            el.value = value ?? '';
        };

        const parseSendPayload = (orderId) => {
            const mapEl = document.querySelector('#orderMine .js-order-send-map');
            if (!mapEl) return null;
            try {
                const parsed = JSON.parse(mapEl.textContent || '{}');
                if (!parsed || typeof parsed !== 'object') return null;
                return parsed[String(orderId)] || null;
            } catch (error) {
                return null;
            }
        };

        const openEditFallback = (trigger) => {
            if (!editModal || !trigger) return;
            setValue('modalOrderId', String(trigger.getAttribute('data-order-id') || '').trim());
            setValue('modalOrderNo', String(trigger.getAttribute('data-order-no') || '').trim() || '-');
            setValue('modalOrderSubject', String(trigger.getAttribute('data-order-subject') || '').trim());
            setValue('modalOrderEffectiveDate', String(trigger.getAttribute('data-order-effective-date-raw') || '').trim());
            setValue('modalOrderDate', String(trigger.getAttribute('data-order-date-raw') || '').trim());
            setValue('modalOrderIssuer', String(trigger.getAttribute('data-order-issuer') || '').trim() || '-');
            editModal.style.display = 'flex';
        };

        const openSendFallback = (trigger) => {
            if (!sendModal || !trigger) return;
            const orderId = String(trigger.getAttribute('data-order-id') || '').trim();
            const payload = parseSendPayload(orderId);
            if (payload && typeof payload === 'object') {
                setValue('modalOrderSendOrderId', orderId);
                setValue('modalOrderRecallOrderId', orderId);
                setValue('modalOrderSendNo', String(payload.orderNo || '').trim() || '-');
                setValue('modalOrderSendSubject', String(payload.subject || '').trim() || '-');
                setValue('modalOrderSendEffectiveDate', String(payload.effectiveDate || '').trim());
                setValue('modalOrderSendDate', String(payload.orderDate || '').trim());
                setValue('modalOrderSendIssuer', String(payload.issuerName || '').trim() || '-');
                setValue('modalOrderSendGroup', String(payload.groupName || '').trim() || '-');

                const status = String(payload.status || '').trim().toUpperCase();
                const isSent = status === 'SENT';
                const title = document.getElementById('modalOrderSendTitle');
                const formSection = document.getElementById('modalOrderSendFormSection');
                const trackSection = document.getElementById('modalOrderTrackSection');
                if (title) title.textContent = isSent ? 'ติดตามการส่งคำสั่งราชการ' : 'ส่งคำสั่งราชการ';
                if (formSection) formSection.style.display = isSent ? 'none' : '';
                if (trackSection) trackSection.style.display = isSent ? '' : 'none';
            }
            sendModal.style.display = 'flex';
        };

        closeEdit?.addEventListener('click', () => {
            if (editModal) editModal.style.display = 'none';
        });
        closeSend?.addEventListener('click', () => {
            if (sendModal) sendModal.style.display = 'none';
        });
        closeView?.addEventListener('click', () => {
            if (viewModal) viewModal.style.display = 'none';
        });

        window.addEventListener('click', (event) => {
            if (event.target === editModal) {
                editModal.style.display = 'none';
            }
            if (event.target === sendModal) {
                sendModal.style.display = 'none';
            }
            if (event.target === viewModal) {
                viewModal.style.display = 'none';
            }
        });

        document.addEventListener('click', (event) => {
            const target = event.target instanceof Element ? event.target : null;
            if (!target) return;

            const editTrigger = target.closest('.js-open-order-edit-modal');
            if (editTrigger) {
                window.setTimeout(() => {
                    if (editModal && editModal.style.display !== 'flex') {
                        openEditFallback(editTrigger);
                    }
                }, 0);
            }

            const sendTrigger = target.closest('.js-open-order-send-modal');
            if (sendTrigger) {
                window.setTimeout(() => {
                    if (sendModal && sendModal.style.display !== 'flex') {
                        openSendFallback(sendTrigger);
                    }
                }, 0);
            }
            const viewTrigger = target.closest('.js-open-order-view-modal');
            if (viewTrigger) {
                window.setTimeout(() => {
                    if (viewModal && viewModal.style.display !== 'flex') {
                        viewModal.style.display = 'flex';
                    }
                }, 0);
            }
        }, true);
    });

    document.addEventListener('DOMContentLoaded', function() {
        const modalOrderSendFileSection = document.getElementById('modalOrderSendFileSection');
        const modalExistingFileList = document.getElementById('modalExistingFileList');

        const escapeHtml = (unsafe) => {
            return (unsafe || '').toString()
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        };

        const renderOrderSendFiles = (orderId, files) => {
            if (!modalOrderSendFileSection) {
                return;
            }

            if (!Array.isArray(files) || files.length <= 0) {
                const emptyHtml = '<div class="file-banner"><div class="file-info"><div class="file-text"><span class="file-name">ไม่มีไฟล์แนบ</span></div></div></div>';
                modalOrderSendFileSection.innerHTML = emptyHtml;
                return;
            }

            const safeOrderId = encodeURIComponent(String(orderId || '').trim());
            const html = files.map((file) => {
                const fileId = encodeURIComponent(String(file?.fileID || ''));
                const fileName = escapeHtml(String(file?.fileName || '-'));
                const mimeType = escapeHtml(String(file?.mimeType || 'ไฟล์แนบ'));
                const viewHref = `public/api/file-download.php?module=orders&entity_id=${safeOrderId}&file_id=${fileId}`;
                const iconHtml = String(file?.mimeType || '').toLowerCase() === 'application/pdf' ?
                    '<i class="fa-solid fa-file-pdf"></i>' :
                    '<i class="fa-solid fa-image"></i>';

                return `<div class="file-banner">
                    <div class="file-info">
                        <div class="file-icon">${iconHtml}</div>
                        <div class="file-text">
                            <span class="file-name">${fileName}</span>
                            <span class="file-type">${mimeType}</span>
                        </div>
                    </div>
                    <div class="file-actions">
                        <a href="${viewHref}" target="_blank" rel="noopener">
                            <i class="fa-solid fa-eye"></i>
                        </a>
                    </div>
                </div>`;
            }).join('');

            modalOrderSendFileSection.innerHTML = html;
        };

        const renderExistingOrderFiles = (orderId, rawJson) => {
            if (!modalExistingFileList) {
                return;
            }

            let files = [];
            try {
                const parsed = JSON.parse(String(rawJson || '[]'));
                files = Array.isArray(parsed) ? parsed : [];
            } catch (error) {
                files = [];
            }

            if (files.length <= 0) {
                modalExistingFileList.innerHTML = '<p class="existing-file-empty">ยังไม่มีไฟล์แนบ</p>';
                return;
            }

            const safeOrderId = encodeURIComponent(String(orderId || '').trim());

            const rowsHtml = files.map((file) => {
                const fileId = encodeURIComponent(String(file.fileID || ''));
                const fileName = escapeHtml(String(file.fileName || '-'));
                const mimeType = escapeHtml(String(file.mimeType || 'ไฟล์แนบ'));
                const viewHref = `public/api/file-download.php?module=orders&entity_id=${safeOrderId}&file_id=${fileId}`;
                const iconHtml = String(file.mimeType || '').toLowerCase() === 'application/pdf' ?
                    '<i class="fa-solid fa-file-pdf" aria-hidden="true"></i>' :
                    '<i class="fa-solid fa-file-image" aria-hidden="true"></i>';

                return `<div class="file-item-wrapper" id="existing-file-${fileId}">
                    <button type="button" class="delete-btn js-delete-existing" data-file-id="${fileId}" title="ลบไฟล์">
                        <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                    </button>
                    <div class="file-banner">
                        <div class="file-info">
                            <div class="file-icon">${iconHtml}</div>
                            <div class="file-text">
                                <span class="file-name">${fileName}</span>
                                <span class="file-type">${mimeType}</span>
                            </div>
                        </div>
                        <div class="file-actions">
                            <a href="${viewHref}" target="_blank" rel="noopener" class="action-btn" title="ดูตัวอย่าง">
                                <i class="fa-solid fa-eye" aria-hidden="true"></i>
                            </a>
                        </div>
                    </div>
                </div>`;
            }).join('');

            modalExistingFileList.innerHTML = rowsHtml;

            const deleteBtns = modalExistingFileList.querySelectorAll('.js-delete-existing');
            deleteBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const fId = this.getAttribute('data-file-id');
                    const wrapper = document.getElementById(`existing-file-${fId}`);

                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'deleted_existing_files[]';
                    hiddenInput.value = decodeURIComponent(fId);
                    document.getElementById('modalOrderEditForm').appendChild(hiddenInput);

                    if (wrapper) wrapper.remove();

                    if (modalExistingFileList.querySelectorAll('.file-item-wrapper').length === 0) {
                        modalExistingFileList.innerHTML = '<p class="existing-file-empty">ยังไม่มีไฟล์แนบ</p>';
                    }
                });
            });
        };
    });
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';

<?php

declare(strict_types=1);

require_once __DIR__ . '/../views/view.php';
require_once __DIR__ . '/../../src/Services/auth/auth-guard.php';
require_once __DIR__ . '/../auth/csrf.php';
require_once __DIR__ . '/../rbac/current_user.php';
require_once __DIR__ . '/../modules/orders/repository.php';
require_once __DIR__ . '/../modules/system/system.php';
require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/../modules/audit/logger.php';

if (!function_exists('orders_inbox_index')) {
    function orders_inbox_index(): void
    {
        $current_user = current_user() ?? [];
        $current_pid = (string) ($current_user['pID'] ?? '');
        $archived = isset($_GET['archived']) && $_GET['archived'] === '1';
        $search = trim((string) ($_GET['q'] ?? ''));
        $status_filter = strtolower((string) ($_GET['status'] ?? 'all'));
        $allowed_filters = ['all', 'read', 'unread'];
        $sort = order_normalize_inbox_sort((string) ($_GET['sort'] ?? 'newest'));
        $per_page_raw = strtolower(trim((string) ($_GET['per_page'] ?? '10')));
        $allowed_per_page = ['10', '20', '50', 'all'];

        if (!in_array($status_filter, $allowed_filters, true)) {
            $status_filter = 'all';
        }

        if (!in_array($per_page_raw, $allowed_per_page, true)) {
            $per_page_raw = '10';
        }
        $page = (int) ($_GET['page'] ?? 1);
        $page = $page > 0 ? $page : 1;
        $per_page = $per_page_raw === 'all' ? 'all' : (int) $per_page_raw;

        $alert = null;
        $connection = db_connection();
        $has_inbox_table = db_table_exists($connection, 'dh_order_inboxes');
        $inbox_modal_payload_map = [];
        $current_thai_year = (int) date('Y') + 543;
        $max_thai_year = $current_thai_year + 1;
        $start_thai_year = 2568;
        $active_dh_year = system_get_dh_year();

        if ($active_dh_year < $start_thai_year || $active_dh_year > $max_thai_year) {
            $active_dh_year = $current_thai_year;
        }

        $dh_year_options = [];
        $year_floor = max($start_thai_year, $active_dh_year - 5);

        for ($year_value = $active_dh_year; $year_value >= $year_floor; $year_value--) {
            $dh_year_options[] = $year_value;
        }

        $selected_dh_year = (int) ($_GET['dh_year'] ?? 0);

        if (!in_array($selected_dh_year, $dh_year_options, true)) {
            $selected_dh_year = (int) ($dh_year_options[0] ?? $active_dh_year);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!csrf_validate($_POST['csrf_token'] ?? null)) {
                $alert = [
                    'type' => 'danger',
                    'title' => 'ไม่สามารถยืนยันความปลอดภัย',
                    'message' => 'กรุณาลองใหม่อีกครั้ง',
                ];
            } elseif (!$has_inbox_table) {
                $alert = system_not_ready_alert('ยังไม่พบตาราง dh_order_inboxes กรุณารัน migrations/004_create_orders.sql');
            } else {
                $is_ajax_request = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
                $action = $_POST['action'] ?? '';
                $inbox_id = (int) ($_POST['inbox_id'] ?? 0);
                $selected_ids = array_values(array_unique(array_filter(array_map('intval', (array) ($_POST['selected_ids'] ?? [])), static function (int $id): bool {
                    return $id > 0;
                })));

                if ($action === 'mark_read' && $inbox_id > 0) {
                    order_mark_read($inbox_id, $current_pid);

                    if (function_exists('audit_log')) {
                        audit_log('orders', 'READ', 'SUCCESS', 'dh_order_inboxes', $inbox_id);
                    }

                    if ($is_ajax_request) {
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode([
                            'ok' => true,
                            'inbox_id' => $inbox_id,
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        return;
                    }
                } elseif ($action === 'archive' && $inbox_id > 0) {
                    order_archive_inbox($inbox_id, $current_pid);

                    if (function_exists('audit_log')) {
                        audit_log('orders', 'ARCHIVE', 'SUCCESS', 'dh_order_inboxes', $inbox_id);
                    }
                    $alert = [
                        'type' => 'success',
                        'title' => 'จัดเก็บเรียบร้อย',
                        'message' => '',
                    ];
                } elseif ($action === 'unarchive' && $inbox_id > 0) {
                    order_unarchive_inbox($inbox_id, $current_pid);

                    if (function_exists('audit_log')) {
                        audit_log('orders', 'UNARCHIVE', 'SUCCESS', 'dh_order_inboxes', $inbox_id);
                    }
                    $alert = [
                        'type' => 'success',
                        'title' => 'ยกเลิกจัดเก็บแล้ว',
                        'message' => '',
                    ];
                } elseif ($action === 'archive_selected') {
                    if (empty($selected_ids)) {
                        $alert = [
                            'type' => 'danger',
                            'title' => 'กรุณาเลือกรายการ',
                            'message' => '',
                        ];
                    } else {
                        foreach ($selected_ids as $selected_id) {
                            order_archive_inbox($selected_id, $current_pid);
                            if (function_exists('audit_log')) {
                                audit_log('orders', 'ARCHIVE', 'SUCCESS', 'dh_order_inboxes', $selected_id);
                            }
                        }
                        $alert = [
                            'type' => 'success',
                            'title' => 'จัดเก็บเรียบร้อย',
                            'message' => 'จำนวน ' . count($selected_ids) . ' รายการ',
                        ];
                    }
                } elseif ($action === 'unarchive_selected') {
                    if (empty($selected_ids)) {
                        $alert = [
                            'type' => 'danger',
                            'title' => 'กรุณาเลือกรายการ',
                            'message' => '',
                        ];
                    } else {
                        foreach ($selected_ids as $selected_id) {
                            order_unarchive_inbox($selected_id, $current_pid);
                            if (function_exists('audit_log')) {
                                audit_log('orders', 'UNARCHIVE', 'SUCCESS', 'dh_order_inboxes', $selected_id);
                            }
                        }
                        $alert = [
                            'type' => 'success',
                            'title' => 'ยกเลิกจัดเก็บแล้ว',
                            'message' => 'จำนวน ' . count($selected_ids) . ' รายการ',
                        ];
                    }
                }
            }
        }

        $total_pages = 1;
        $filtered_total = 0;
        $summary = [
            'total' => 0,
            'read' => 0,
            'unread' => 0,
        ];

        if (!$has_inbox_table) {
            if ($alert === null) {
                $alert = system_not_ready_alert('ยังไม่พบตาราง dh_order_inboxes กรุณารัน migrations/004_create_orders.sql');
            }
            $items = [];
        } else {
            $summary = order_inbox_read_summary($current_pid, $archived, $search, $selected_dh_year);
            $filtered_total = order_count_inbox_filtered($current_pid, $archived, $search, $status_filter, $selected_dh_year);
            $per_page_limit = $per_page === 'all' ? max(1, $filtered_total) : (int) $per_page;
            $total_pages = $per_page === 'all' ? 1 : max(1, (int) ceil($filtered_total / $per_page_limit));

            if ($page > $total_pages) {
                $page = $total_pages;
            }
            if ($per_page === 'all') {
                $page = 1;
            }
            $offset = ($page - 1) * $per_page_limit;
            $items = order_list_inbox_page_filtered($current_pid, $archived, $search, $status_filter, $per_page_limit, $offset, $sort, $selected_dh_year);

            $parse_order_meta = static function (?string $detail_text): array {
                $text = trim((string) $detail_text);
                $meta = [
                    'effective_date' => '',
                    'order_date' => '',
                    'issuer_name' => '',
                    'group_name' => '',
                ];

                if ($text === '') {
                    return $meta;
                }

                if (preg_match('/^ทั้งนี้ตั้งแต่วันที่:\s*(.+)$/m', $text, $matches) === 1) {
                    $meta['effective_date'] = trim((string) ($matches[1] ?? ''));
                }
                if (preg_match('/^สั่ง ณ วันที่:\s*(.+)$/m', $text, $matches) === 1) {
                    $meta['order_date'] = trim((string) ($matches[1] ?? ''));
                }
                if (preg_match('/^ผู้ออกเลขคำสั่ง:\s*(.+)$/m', $text, $matches) === 1) {
                    $meta['issuer_name'] = trim((string) ($matches[1] ?? ''));
                }
                if (preg_match('/^กลุ่ม:\s*(.+)$/m', $text, $matches) === 1) {
                    $meta['group_name'] = trim((string) ($matches[1] ?? ''));
                }

                return $meta;
            };

            foreach ($items as $item) {
                $inbox_id = (int) ($item['inboxID'] ?? 0);
                $order_id = (int) ($item['orderID'] ?? 0);

                if ($inbox_id <= 0 || $order_id <= 0) {
                    continue;
                }

                $order = order_get($order_id) ?? [];
                $detail_text = trim((string) ($order['detail'] ?? ''));
                $parsed_meta = $parse_order_meta($detail_text);
                $attachments = order_get_attachments($order_id);
                $normalized_attachments = [];

                foreach ($attachments as $file) {
                    $normalized_attachments[] = [
                        'fileID' => (int) ($file['fileID'] ?? 0),
                        'fileName' => (string) ($file['fileName'] ?? ''),
                        'mimeType' => (string) ($file['mimeType'] ?? ''),
                    ];
                }

                $inbox_modal_payload_map[(string) $inbox_id] = [
                    'inboxID' => $inbox_id,
                    'orderID' => $order_id,
                    'orderNo' => trim((string) (($order['orderNo'] ?? '') !== '' ? ($order['orderNo'] ?? '') : ($item['orderNo'] ?? ''))),
                    'subject' => trim((string) (($order['subject'] ?? '') !== '' ? ($order['subject'] ?? '') : ($item['subject'] ?? ''))),
                    'effectiveDate' => trim((string) ($parsed_meta['effective_date'] ?? '')),
                    'orderDate' => trim((string) ($parsed_meta['order_date'] ?? '')),
                    'issuerName' => trim((string) ($parsed_meta['issuer_name'] ?? '')) !== ''
                        ? trim((string) ($parsed_meta['issuer_name'] ?? ''))
                        : trim((string) (($order['creatorName'] ?? '') !== '' ? ($order['creatorName'] ?? '') : ($item['senderName'] ?? ''))),
                    'groupName' => trim((string) ($parsed_meta['group_name'] ?? '')),
                    'attachments' => $normalized_attachments,
                ];
            }
        }

        $base_params = [
            'archived' => $archived ? '1' : '0',
            'sort' => $sort,
            'per_page' => $per_page_raw,
            'dh_year' => (string) $selected_dh_year,
        ];

        if ($search !== '') {
            $base_params['q'] = $search;
        }

        if ($status_filter !== 'all') {
            $base_params['status'] = $status_filter;
        }
        $pagination_base_url = 'orders-inbox.php';

        if (!empty($base_params)) {
            $pagination_base_url .= '?' . http_build_query($base_params);
        }

        view_render('orders/inbox', [
            'alert' => $alert,
            'items' => $items,
            'archived' => $archived,
            'page' => $page,
            'total_pages' => $total_pages,
            'search' => $search,
            'status_filter' => $status_filter,
            'sort' => $sort,
            'per_page' => $per_page_raw,
            'dh_year_options' => $dh_year_options,
            'selected_dh_year' => $selected_dh_year,
            'summary' => $summary,
            'filtered_total' => $filtered_total,
            'pagination_base_url' => $pagination_base_url,
            'inbox_modal_payload_map' => $inbox_modal_payload_map,
        ]);
    }
}

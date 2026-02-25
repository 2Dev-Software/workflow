<?php

declare(strict_types=1);

require_once __DIR__ . '/../views/view.php';
require_once __DIR__ . '/../../src/Services/auth/auth-guard.php';
require_once __DIR__ . '/../auth/csrf.php';
require_once __DIR__ . '/../rbac/current_user.php';
require_once __DIR__ . '/../modules/orders/repository.php';
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
                $action = $_POST['action'] ?? '';
                $inbox_id = (int) ($_POST['inbox_id'] ?? 0);

                if ($action === 'archive' && $inbox_id > 0) {
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
            $summary = order_inbox_read_summary($current_pid, $archived, $search);
            $filtered_total = order_count_inbox_filtered($current_pid, $archived, $search, $status_filter);
            $per_page_limit = $per_page === 'all' ? max(1, $filtered_total) : (int) $per_page;
            $total_pages = $per_page === 'all' ? 1 : max(1, (int) ceil($filtered_total / $per_page_limit));

            if ($page > $total_pages) {
                $page = $total_pages;
            }
            if ($per_page === 'all') {
                $page = 1;
            }
            $offset = ($page - 1) * $per_page_limit;
            $items = order_list_inbox_page_filtered($current_pid, $archived, $search, $status_filter, $per_page_limit, $offset, $sort);
        }

        $base_params = [
            'archived' => $archived ? '1' : '0',
            'sort' => $sort,
            'per_page' => $per_page_raw,
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
            'summary' => $summary,
            'filtered_total' => $filtered_total,
            'pagination_base_url' => $pagination_base_url,
        ]);
    }
}

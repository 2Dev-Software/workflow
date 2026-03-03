<?php

declare(strict_types=1);

require_once __DIR__ . '/../views/view.php';
require_once __DIR__ . '/../../src/Services/auth/auth-guard.php';
require_once __DIR__ . '/../auth/csrf.php';
require_once __DIR__ . '/../rbac/current_user.php';
require_once __DIR__ . '/../modules/orders/repository.php';
require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/../modules/audit/logger.php';

if (!function_exists('orders_archive_index')) {
    function orders_archive_index(): void
    {
        $current_user = current_user() ?? [];
        $current_pid = (string) ($current_user['pID'] ?? '');

        $search = trim((string) ($_GET['q'] ?? ''));
        $type_filter = strtolower(trim((string) ($_GET['type'] ?? 'all')));
        $status_filter = strtolower(trim((string) ($_GET['status'] ?? 'all')));
        $sort = order_normalize_inbox_sort((string) ($_GET['sort'] ?? 'newest'));

        $allowed_type_filters = ['all', 'order'];
        $allowed_status_filters = ['all', 'read', 'unread'];

        if (!in_array($type_filter, $allowed_type_filters, true)) {
            $type_filter = 'all';
        }

        if (!in_array($status_filter, $allowed_status_filters, true)) {
            $status_filter = 'all';
        }

        if (!in_array($sort, ['newest', 'oldest'], true)) {
            $sort = 'newest';
        }

        $page = (int) ($_GET['page'] ?? 1);
        $page = $page > 0 ? $page : 1;
        $per_page = 10;

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
                $action = (string) ($_POST['action'] ?? '');
                $inbox_id = (int) ($_POST['inbox_id'] ?? 0);

                if ($action === 'unarchive' && $inbox_id > 0) {
                    order_unarchive_inbox($inbox_id, $current_pid);

                    if (function_exists('audit_log')) {
                        audit_log('orders', 'UNARCHIVE', 'SUCCESS', 'dh_order_inboxes', $inbox_id);
                    }

                    $alert = [
                        'type' => 'success',
                        'title' => 'ย้ายกลับเรียบร้อย',
                        'message' => '',
                    ];
                }
            }
        }

        $total_pages = 1;
        $filtered_total = 0;

        if (!$has_inbox_table) {
            if ($alert === null) {
                $alert = system_not_ready_alert('ยังไม่พบตาราง dh_order_inboxes กรุณารัน migrations/004_create_orders.sql');
            }
            $items = [];
        } else {
            $filtered_total = order_count_inbox_filtered($current_pid, true, $search, $status_filter);
            $total_pages = max(1, (int) ceil($filtered_total / $per_page));

            if ($page > $total_pages) {
                $page = $total_pages;
            }

            $offset = ($page - 1) * $per_page;
            $items = order_list_inbox_page_filtered($current_pid, true, $search, $status_filter, $per_page, $offset, $sort);

            foreach ($items as $index => $item) {
                $order_id = (int) ($item['orderID'] ?? 0);
                $order_detail = '';

                if ($order_id > 0) {
                    $order = order_get($order_id);
                    $order_detail = trim((string) ($order['detail'] ?? ''));
                }

                $items[$index]['detail'] = $order_detail;
                $items[$index]['attachments'] = $order_id > 0 ? order_get_attachments($order_id) : [];
            }
        }

        $base_params = [];

        if ($search !== '') {
            $base_params['q'] = $search;
        }

        if ($type_filter !== 'all') {
            $base_params['type'] = $type_filter;
        }

        if ($status_filter !== 'all') {
            $base_params['status'] = $status_filter;
        }

        if ($sort !== 'newest') {
            $base_params['sort'] = $sort;
        }

        $pagination_base_url = 'orders-archive.php';

        if (!empty($base_params)) {
            $pagination_base_url .= '?' . http_build_query($base_params);
        }

        view_render('orders/archive', [
            'alert' => $alert,
            'items' => $items,
            'page' => $page,
            'total_pages' => $total_pages,
            'search' => $search,
            'type_filter' => $type_filter,
            'status_filter' => $status_filter,
            'sort' => $sort,
            'filtered_total' => $filtered_total,
            'pagination_base_url' => $pagination_base_url,
        ]);
    }
}

<?php

declare(strict_types=1);

require_once __DIR__ . '/../views/view.php';
require_once __DIR__ . '/../../src/Services/auth/auth-guard.php';
require_once __DIR__ . '/../rbac/current_user.php';
require_once __DIR__ . '/../modules/memos/repository.php';
require_once __DIR__ . '/../db/db.php';

if (!function_exists('memo_inbox_index')) {
    function memo_inbox_index(): void
    {
        $current_user = current_user() ?? [];
        $current_pid = (string) ($current_user['pID'] ?? '');

        $search = trim((string) ($_GET['q'] ?? ''));
        $status_filter = (string) ($_GET['status'] ?? 'all');
        $allowed = [
            'all',
            MEMO_STATUS_SUBMITTED,
            MEMO_STATUS_IN_REVIEW,
            MEMO_STATUS_RETURNED,
            MEMO_STATUS_APPROVED_UNSIGNED,
            MEMO_STATUS_SIGNED,
            MEMO_STATUS_REJECTED,
            MEMO_STATUS_CANCELLED,
        ];

        if (!in_array($status_filter, $allowed, true)) {
            $status_filter = 'all';
        }

        $page = (int) ($_GET['page'] ?? 1);
        $page = $page > 0 ? $page : 1;
        $per_page = 10;

        $alert = null;
        $connection = db_connection();
        $has_table = db_table_exists($connection, 'dh_memos');
        $has_routes = db_table_exists($connection, 'dh_memo_routes');

        $total_pages = 1;
        $filtered_total = 0;

        if (!$has_table || !$has_routes) {
            $alert = system_not_ready_alert('ยังไม่พบตาราง memo workflow กรุณารัน migrations/011_update_memos_workflow.sql');
            $items = [];
        } else {
            $filtered_total = memo_count_by_reviewer($current_pid, $status_filter, $search);
            $total_pages = max(1, (int) ceil($filtered_total / $per_page));

            if ($page > $total_pages) {
                $page = $total_pages;
            }
            $offset = ($page - 1) * $per_page;
            $items = memo_list_by_reviewer_page($current_pid, $status_filter, $search, $per_page, $offset);
        }

        $base_params = [];

        if ($search !== '') {
            $base_params['q'] = $search;
        }

        if ($status_filter !== 'all') {
            $base_params['status'] = $status_filter;
        }
        $pagination_base_url = 'memo-inbox.php';

        if (!empty($base_params)) {
            $pagination_base_url .= '?' . http_build_query($base_params);
        }

        view_render('memo/inbox', [
            'alert' => $alert,
            'items' => $items,
            'page' => $page,
            'total_pages' => $total_pages,
            'search' => $search,
            'status_filter' => $status_filter,
            'filtered_total' => $filtered_total,
            'pagination_base_url' => $pagination_base_url,
        ]);
    }
}

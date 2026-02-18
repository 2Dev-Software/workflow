<?php

declare(strict_types=1);

require_once __DIR__ . '/../views/view.php';
require_once __DIR__ . '/../../src/Services/auth/auth-guard.php';
require_once __DIR__ . '/../../src/Services/security/security-service.php';
require_once __DIR__ . '/../auth/csrf.php';
require_once __DIR__ . '/../modules/circulars/repository.php';
require_once __DIR__ . '/../modules/circulars/service.php';
require_once __DIR__ . '/../rbac/current_user.php';

if (!function_exists('circular_sent_index')) {
    function circular_sent_index(): void
    {
        $current_user = current_user() ?? [];
        $current_pid = (string) ($current_user['pID'] ?? '');
        $receipt_circular_id = isset($_GET['receipt']) ? (int) $_GET['receipt'] : 0;
        $receipt_stats = [];
        $receipt_subject = '';
        $receipt_sender_faction = '';

        $alert = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!csrf_validate($_POST['csrf_token'] ?? null)) {
                $alert = ['type' => 'danger', 'title' => 'ไม่สามารถยืนยันความปลอดภัย', 'message' => 'กรุณาลองใหม่อีกครั้ง'];
            } else {
                $action = (string) ($_POST['action'] ?? '');
                $circular_id = isset($_POST['circular_id']) ? (int) $_POST['circular_id'] : 0;

                if ($action === 'recall' && $circular_id > 0) {
                    $ok = circular_recall_internal($circular_id, $current_pid);
                    $alert = $ok
                        ? ['type' => 'success', 'title' => 'ดึงหนังสือกลับแล้ว', 'message' => '']
                        : ['type' => 'warning', 'title' => 'ไม่สามารถดึงกลับได้', 'message' => 'มีผู้รับอ่านแล้ว'];
                } elseif ($action === 'recall_external' && $circular_id > 0) {
                    $ok = circular_recall_external_before_review($circular_id, $current_pid);
                    $alert = $ok
                        ? ['type' => 'success', 'title' => 'ดึงหนังสือกลับแล้ว', 'message' => 'สามารถแก้ไขและส่งใหม่ได้']
                        : ['type' => 'warning', 'title' => 'ไม่สามารถดึงกลับได้', 'message' => 'เอกสารถูกพิจารณาแล้วหรือไม่ใช่สิทธิ์ของคุณ'];
                } elseif ($action === 'resend' && $circular_id > 0) {
                    $ok = circular_resend_internal($circular_id, $current_pid);
                    $alert = $ok
                        ? ['type' => 'success', 'title' => 'ส่งใหม่เรียบร้อย', 'message' => '']
                        : ['type' => 'warning', 'title' => 'ไม่สามารถส่งใหม่ได้', 'message' => ''];
                }
            }
        }

        $all_items = circular_list_sent($current_pid);

        $filter_query = trim((string) ($_GET['q'] ?? ''));
        $filter_type = strtolower(trim((string) ($_GET['type'] ?? 'all')));
        $filter_status = strtoupper(trim((string) ($_GET['status'] ?? 'all')));
        $filter_sort = strtolower(trim((string) ($_GET['sort'] ?? 'newest')));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $per_page = (int) ($_GET['per_page'] ?? 10);

        $allowed_types = ['all', 'internal', 'external'];

        if (!in_array($filter_type, $allowed_types, true)) {
            $filter_type = 'all';
        }

        $allowed_statuses = [
            'ALL',
            INTERNAL_STATUS_DRAFT,
            INTERNAL_STATUS_SENT,
            INTERNAL_STATUS_RECALLED,
            INTERNAL_STATUS_ARCHIVED,
            EXTERNAL_STATUS_SUBMITTED,
            EXTERNAL_STATUS_PENDING_REVIEW,
            EXTERNAL_STATUS_REVIEWED,
            EXTERNAL_STATUS_FORWARDED,
        ];

        if (!in_array($filter_status, $allowed_statuses, true)) {
            $filter_status = 'ALL';
        }

        if (!in_array($filter_sort, ['newest', 'oldest'], true)) {
            $filter_sort = 'newest';
        }

        $allowed_per_page = [10, 20, 50];

        if (!in_array($per_page, $allowed_per_page, true)) {
            $per_page = 10;
        }

        $summary_total = count($all_items);
        $summary_sent = 0;
        $summary_recalled = 0;
        $summary_read_complete = 0;

        foreach ($all_items as $item) {
            $status = strtoupper((string) ($item['status'] ?? ''));
            $read_count = (int) ($item['readCount'] ?? 0);
            $recipient_count = (int) ($item['recipientCount'] ?? 0);

            if ($status === INTERNAL_STATUS_SENT || $status === EXTERNAL_STATUS_FORWARDED) {
                $summary_sent++;
            }

            if ($status === INTERNAL_STATUS_RECALLED) {
                $summary_recalled++;
            }

            if ($recipient_count > 0 && $read_count >= $recipient_count) {
                $summary_read_complete++;
            }
        }

        $filtered_items = array_values(array_filter($all_items, static function (array $item) use ($filter_type, $filter_status, $filter_query): bool {
            $item_type = strtolower(trim((string) ($item['circularType'] ?? '')));
            $item_status = strtoupper(trim((string) ($item['status'] ?? '')));
            $subject = (string) ($item['subject'] ?? '');
            $circular_id = (string) (int) ($item['circularID'] ?? 0);
            $lower = static function (string $value): string {
                return function_exists('mb_strtolower') ? mb_strtolower($value) : strtolower($value);
            };

            if ($filter_type !== 'all' && $item_type !== $filter_type) {
                return false;
            }

            if ($filter_status !== 'ALL' && $item_status !== $filter_status) {
                return false;
            }

            if ($filter_query !== '') {
                $haystack = $lower(trim($subject . ' ' . $circular_id));
                $needle = $lower($filter_query);
                $position = function_exists('mb_strpos') ? mb_strpos($haystack, $needle) : strpos($haystack, $needle);

                if ($position === false) {
                    return false;
                }
            }

            return true;
        }));

        usort($filtered_items, static function (array $a, array $b) use ($filter_sort): int {
            $time_a = strtotime((string) ($a['createdAt'] ?? '')) ?: 0;
            $time_b = strtotime((string) ($b['createdAt'] ?? '')) ?: 0;

            if ($time_a === $time_b) {
                return ((int) ($b['circularID'] ?? 0)) <=> ((int) ($a['circularID'] ?? 0));
            }

            if ($filter_sort === 'oldest') {
                return $time_a <=> $time_b;
            }

            return $time_b <=> $time_a;
        });

        $filtered_total = count($filtered_items);
        $total_pages = max(1, (int) ceil($filtered_total / $per_page));

        if ($page > $total_pages) {
            $page = $total_pages;
        }

        $offset = ($page - 1) * $per_page;
        $sent_items = array_slice($filtered_items, $offset, $per_page);

        $query_params = [
            'q' => $filter_query,
            'type' => $filter_type,
            'status' => strtolower($filter_status),
            'sort' => $filter_sort,
            'per_page' => $per_page,
        ];

        if ($receipt_circular_id > 0) {
            foreach ($all_items as $sent_item) {
                if ((int) ($sent_item['circularID'] ?? 0) === $receipt_circular_id) {
                    $receipt_subject = (string) ($sent_item['subject'] ?? '');
                    $receipt_sender_faction = (string) ($sent_item['senderFactionName'] ?? '');
                    $receipt_stats = circular_get_read_stats($receipt_circular_id);
                    break;
                }
            }
        }

        view_render('circular/sent', [
            'alert' => $alert,
            'sent_items' => $sent_items,
            'receipt_circular_id' => $receipt_circular_id,
            'receipt_subject' => $receipt_subject,
            'receipt_sender_faction' => $receipt_sender_faction,
            'receipt_stats' => $receipt_stats,
            'filter_query' => $filter_query,
            'filter_type' => $filter_type,
            'filter_status' => strtolower($filter_status),
            'filter_sort' => $filter_sort,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => $total_pages,
            'filtered_total' => $filtered_total,
            'summary_total' => $summary_total,
            'summary_sent' => $summary_sent,
            'summary_recalled' => $summary_recalled,
            'summary_read_complete' => $summary_read_complete,
            'query_params' => $query_params,
        ]);
    }
}

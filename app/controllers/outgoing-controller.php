<?php

declare(strict_types=1);

require_once __DIR__ . '/../views/view.php';
require_once __DIR__ . '/../../src/Services/auth/auth-guard.php';
require_once __DIR__ . '/../auth/csrf.php';
require_once __DIR__ . '/../rbac/current_user.php';
require_once __DIR__ . '/../rbac/roles.php';
require_once __DIR__ . '/../modules/outgoing/service.php';
require_once __DIR__ . '/../modules/outgoing/repository.php';
require_once __DIR__ . '/../db/db.php';

if (!function_exists('outgoing_index')) {
    function outgoing_index(): void
    {
        $current_user = current_user() ?? [];
        $current_pid = (string) ($current_user['pID'] ?? '');
        $connection = db_connection();
        $can_manage = outgoing_user_can_manage($connection, $current_pid, $current_user);
        $search = trim((string) ($_GET['q'] ?? ''));
        $status_filter = strtoupper(trim((string) ($_GET['status'] ?? 'all')));
        $allowed_status_filters = [
            'ALL',
            OUTGOING_STATUS_WAITING_ATTACHMENT,
            OUTGOING_STATUS_COMPLETE,
        ];

        if (!in_array($status_filter, $allowed_status_filters, true)) {
            $status_filter = 'ALL';
        }

        if (!$can_manage) {
            http_response_code(403);
            require __DIR__ . '/../views/errors/403.php';

            return;
        }

        $alert = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!csrf_validate($_POST['csrf_token'] ?? null)) {
                $alert = [
                    'type' => 'danger',
                    'title' => 'ไม่สามารถยืนยันความปลอดภัย',
                    'message' => 'กรุณาลองใหม่อีกครั้ง',
                ];
            } else {
                $action = $_POST['action'] ?? '';
                $outgoing_id = isset($_POST['outgoing_id']) ? (int) $_POST['outgoing_id'] : 0;

                if ($action === 'attach' && $outgoing_id > 0) {
                    try {
                        outgoing_attach_files($outgoing_id, $current_pid, $_FILES['attachments'] ?? []);
                        $alert = [
                            'type' => 'success',
                            'title' => 'แนบไฟล์เรียบร้อย',
                            'message' => '',
                        ];
                    } catch (Throwable $e) {
                        $alert = [
                            'type' => 'danger',
                            'title' => 'เกิดข้อผิดพลาด',
                            'message' => $e->getMessage(),
                        ];
                    }
                }
            }
        }

        $outgoing_items = outgoing_list([
            'q' => $search,
            'status' => $status_filter,
        ]);
        $summary_counts = outgoing_count_by_status();
        $outgoing_ids = array_map(static function (array $item): int {
            return (int) ($item['outgoingID'] ?? 0);
        }, $outgoing_items);
        $attachments_map = outgoing_list_attachments_map($outgoing_ids);

        view_render('outgoing/index', [
            'alert' => $alert,
            'items' => $outgoing_items,
            'can_manage' => $can_manage,
            'search' => $search,
            'status_filter' => $status_filter,
            'summary_counts' => $summary_counts,
            'attachments_map' => $attachments_map,
        ]);
    }
}

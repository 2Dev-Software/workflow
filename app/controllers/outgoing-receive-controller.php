<?php

declare(strict_types=1);

require_once __DIR__ . '/../views/view.php';
require_once __DIR__ . '/../../src/Services/auth/auth-guard.php';
require_once __DIR__ . '/../auth/csrf.php';
require_once __DIR__ . '/../rbac/current_user.php';
require_once __DIR__ . '/../modules/outgoing/service.php';
require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/../modules/outgoing/receive-service.php';

if (!function_exists('outgoing_receive_index')) {
    function outgoing_receive_index(): void
    {
        $current_user = current_user() ?? [];
        $current_pid = (string) ($current_user['pID'] ?? '');
        $connection = db_connection();
        $can_manage = outgoing_user_can_manage($connection, $current_pid, $current_user);

        if (!$can_manage) {
            http_response_code(403);
            require __DIR__ . '/../views/errors/403.php';

            return;
        }

        $edit_circular_id = isset($_GET['edit']) ? (int) $_GET['edit'] : (int) ($_POST['edit_circular_id'] ?? 0);
        $state = outgoing_receive_build_state($current_pid, $edit_circular_id, $_SERVER['REQUEST_METHOD'] !== 'POST');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!csrf_validate($_POST['csrf_token'] ?? null)) {
                $state['alert'] = [
                    'type' => 'danger',
                    'title' => 'ไม่สามารถยืนยันความปลอดภัย',
                    'message' => 'กรุณาลองใหม่อีกครั้ง',
                ];
            } else {
                $attachments = outgoing_receive_merge_upload_sets(
                    $_FILES['cover_attachments'] ?? [],
                    $_FILES['attachments'] ?? []
                );
                $state = outgoing_receive_submit($state, $_POST, $attachments, $current_pid, $current_user);
            }
        }

        view_render('outgoing/receive', [
            'alert' => $state['alert'] ?? null,
            'values' => $state['values'] ?? outgoing_receive_default_values(),
            'factions' => $state['factions'] ?? [],
            'reviewers' => $state['reviewers'] ?? [],
            'is_edit_mode' => (bool) ($state['is_edit_mode'] ?? false),
            'edit_circular_id' => (int) ($state['edit_circular_id'] ?? 0),
            'editable_circular' => $state['editable_circular'] ?? null,
            'existing_attachments' => $state['existing_attachments'] ?? [],
        ]);
    }
}

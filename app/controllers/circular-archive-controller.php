<?php
declare(strict_types=1);

require_once __DIR__ . '/../views/view.php';
require_once __DIR__ . '/../../src/Services/auth/auth-guard.php';
require_once __DIR__ . '/../auth/csrf.php';
require_once __DIR__ . '/../rbac/current_user.php';
require_once __DIR__ . '/../rbac/roles.php';
require_once __DIR__ . '/../modules/circulars/repository.php';
require_once __DIR__ . '/../modules/system/system.php';

if (!function_exists('circular_archive_index')) {
    function circular_archive_index(): void
    {
        $current_user = current_user() ?? [];
        $current_pid = (string) ($current_user['pID'] ?? '');

        $connection = db_connection();
        $is_registry = rbac_user_has_role($connection, $current_pid, ROLE_REGISTRY);
        if (!$is_registry && (int) ($current_user['roleID'] ?? 0) === 2) {
            $is_registry = true;
        }

        $director_pid = system_get_current_director_pid();
        $is_director_box = $director_pid !== null && $director_pid === $current_pid;

        $box = $_GET['box'] ?? 'normal';
        $box_map = [
            'normal' => INBOX_TYPE_NORMAL,
            'director' => INBOX_TYPE_DIRECTOR,
            'clerk' => INBOX_TYPE_CLERK,
            'clerk_return' => INBOX_TYPE_CLERK_RETURN,
        ];
        $box_key = array_key_exists($box, $box_map) ? $box : 'normal';
        $inbox_type = $box_map[$box_key];

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
                $inbox_id = (int) ($_POST['inbox_id'] ?? 0);
                if ($action === 'unarchive' && $inbox_id > 0) {
                    circular_unarchive_inbox($inbox_id, $current_pid);
                    $alert = [
                        'type' => 'success',
                        'title' => 'ย้ายกลับเรียบร้อย',
                        'message' => '',
                    ];
                }
            }
        }

        $items = circular_get_inbox($current_pid, $inbox_type, true);

        view_render('circular/archive', [
            'alert' => $alert,
            'items' => $items,
            'box_key' => $box_key,
            'is_registry' => $is_registry,
            'is_director_box' => $is_director_box,
        ]);
    }
}

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
        $is_registry = rbac_user_has_role($connection, $current_pid, ROLE_REGISTRY);
        if (!$is_registry && (int) ($current_user['roleID'] ?? 0) === 2) {
            $is_registry = true;
        }
        $alert = null;
        if (!$is_registry) {
            $alert = [
                'type' => 'warning',
                'title' => 'สิทธิ์การใช้งานจำกัด',
                'message' => 'คุณสามารถดูรายการได้ แต่การออกเลข/แนบไฟล์ทำได้เฉพาะสารบรรณ',
            ];
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!csrf_validate($_POST['csrf_token'] ?? null)) {
                $alert = [
                    'type' => 'danger',
                    'title' => 'ไม่สามารถยืนยันความปลอดภัย',
                    'message' => 'กรุณาลองใหม่อีกครั้ง',
                ];
            } elseif (!$is_registry) {
                $alert = [
                    'type' => 'danger',
                    'title' => 'ไม่มีสิทธิ์แนบไฟล์',
                    'message' => 'การแนบไฟล์ทำได้เฉพาะสารบรรณ',
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

        $outgoing_items = outgoing_list();

        view_render('outgoing/index', [
            'alert' => $alert,
            'items' => $outgoing_items,
            'is_registry' => $is_registry,
        ]);
    }
}

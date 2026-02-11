<?php
declare(strict_types=1);

require_once __DIR__ . '/../views/view.php';
require_once __DIR__ . '/../../src/Services/auth/auth-guard.php';
require_once __DIR__ . '/../auth/csrf.php';
require_once __DIR__ . '/../rbac/current_user.php';
require_once __DIR__ . '/../rbac/roles.php';
require_once __DIR__ . '/../modules/outgoing/service.php';
require_once __DIR__ . '/../modules/system/system.php';
require_once __DIR__ . '/../db/db.php';

if (!function_exists('outgoing_create_index')) {
    function outgoing_create_index(): void
    {
        $current_user = current_user() ?? [];
        $current_pid = (string) ($current_user['pID'] ?? '');
        $connection = db_connection();
        $is_registry = rbac_user_has_role($connection, $current_pid, ROLE_REGISTRY);
        if (!$is_registry && (int) ($current_user['roleID'] ?? 0) === 2) {
            $is_registry = true;
        }

        $alert = null;
        $values = [
            'subject' => '',
            'detail' => '',
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $values['subject'] = trim((string) ($_POST['subject'] ?? ''));
            $values['detail'] = trim((string) ($_POST['detail'] ?? ''));

            if (!csrf_validate($_POST['csrf_token'] ?? null)) {
                $alert = [
                    'type' => 'danger',
                    'title' => 'ไม่สามารถยืนยันความปลอดภัย',
                    'message' => 'กรุณาลองใหม่อีกครั้ง',
                ];
            } elseif ($values['subject'] === '') {
                $alert = [
                    'type' => 'danger',
                    'title' => 'กรุณากรอกหัวข้อ',
                    'message' => '',
                ];
            } else {
                try {
                    $outgoingID = outgoing_create_draft([
                        'dh_year' => system_get_dh_year(),
                        'subject' => $values['subject'],
                        'detail' => $values['detail'],
                        'status' => OUTGOING_STATUS_WAITING_ATTACHMENT,
                        'createdByPID' => $current_pid,
                    ], $_FILES['attachments'] ?? []);
                    $alert = [
                        'type' => 'success',
                        'title' => 'ออกเลขหนังสือแล้ว',
                        'message' => 'เลขที่รายการ #' . $outgoingID,
                    ];
                    $values = ['subject' => '', 'detail' => ''];
                } catch (Throwable $e) {
                    $alert = [
                        'type' => 'danger',
                        'title' => 'เกิดข้อผิดพลาด',
                        'message' => $e->getMessage(),
                    ];
                }
            }
        }

        view_render('outgoing/create', [
            'alert' => $alert,
            'values' => $values,
        ]);
    }
}

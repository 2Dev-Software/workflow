<?php
declare(strict_types=1);

require_once __DIR__ . '/../views/view.php';
require_once __DIR__ . '/../../src/Services/auth/auth-guard.php';
require_once __DIR__ . '/../auth/csrf.php';
require_once __DIR__ . '/../rbac/current_user.php';
require_once __DIR__ . '/../modules/memos/repository.php';
require_once __DIR__ . '/../modules/system/system.php';
require_once __DIR__ . '/../services/uploads.php';
require_once __DIR__ . '/../db/db.php';

if (!function_exists('memo_index')) {
    function memo_index(): void
    {
        $current_user = current_user() ?? [];
        $current_pid = (string) ($current_user['pID'] ?? '');

        $alert = null;
        $values = [
            'subject' => '',
            'detail' => '',
        ];

        $connection = db_connection();
        $has_memo_table = db_table_exists($connection, 'dh_memos');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $values['subject'] = trim((string) ($_POST['subject'] ?? ''));
            $values['detail'] = trim((string) ($_POST['detail'] ?? ''));

            if (!csrf_validate($_POST['csrf_token'] ?? null)) {
                $alert = [
                    'type' => 'danger',
                    'title' => 'ไม่สามารถยืนยันความปลอดภัย',
                    'message' => 'กรุณาลองใหม่อีกครั้ง',
                ];
            } elseif (!$has_memo_table) {
                $alert = system_not_ready_alert('ยังไม่พบตาราง dh_memos กรุณารัน migrations/005_create_repairs_memos.sql');
            } elseif ($values['subject'] === '') {
                $alert = [
                    'type' => 'danger',
                    'title' => 'กรุณากรอกหัวข้อ',
                    'message' => '',
                ];
            } else {
                $memoID = memo_create_record([
                    'dh_year' => system_get_dh_year(),
                    'subject' => $values['subject'],
                    'detail' => $values['detail'],
                    'status' => 'DRAFT',
                    'createdByPID' => $current_pid,
                ]);

                if (!empty($_FILES['attachments'])) {
                    upload_store_files($_FILES['attachments'], MEMO_MODULE_NAME, MEMO_ENTITY_NAME, (string) $memoID, $current_pid, [
                        'max_files' => 5,
                    ]);
                }

                $alert = [
                    'type' => 'success',
                    'title' => 'บันทึกข้อความแล้ว',
                    'message' => '',
                ];
                $values = ['subject' => '', 'detail' => ''];
            }
        }

        if (!$has_memo_table && $alert === null) {
            $alert = system_not_ready_alert('ยังไม่พบตาราง dh_memos กรุณารัน migrations/005_create_repairs_memos.sql');
        }

        $memos = $has_memo_table ? memo_list_by_creator($current_pid) : [];

        view_render('memo/index', [
            'alert' => $alert,
            'values' => $values,
            'memos' => $memos,
        ]);
    }
}

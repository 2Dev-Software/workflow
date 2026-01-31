<?php
declare(strict_types=1);

require_once __DIR__ . '/../views/view.php';
require_once __DIR__ . '/../../src/Services/auth/auth-guard.php';
require_once __DIR__ . '/../auth/csrf.php';
require_once __DIR__ . '/../rbac/current_user.php';
require_once __DIR__ . '/../modules/orders/service.php';
require_once __DIR__ . '/../modules/system/system.php';
require_once __DIR__ . '/../db/db.php';

if (!function_exists('orders_create_index')) {
    function orders_create_index(): void
    {
        $current_user = current_user() ?? [];
        $current_pid = (string) ($current_user['pID'] ?? '');

        $alert = null;
        $values = [
            'subject' => '',
            'detail' => '',
        ];

        $connection = db_connection();
        $has_orders_table = db_table_exists($connection, 'dh_orders');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $values['subject'] = trim((string) ($_POST['subject'] ?? ''));
            $values['detail'] = trim((string) ($_POST['detail'] ?? ''));

            if (!csrf_validate($_POST['csrf_token'] ?? null)) {
                $alert = [
                    'type' => 'danger',
                    'title' => 'ไม่สามารถยืนยันความปลอดภัย',
                    'message' => 'กรุณาลองใหม่อีกครั้ง',
                ];
            } elseif (!$has_orders_table) {
                $alert = system_not_ready_alert('ยังไม่พบตาราง dh_orders กรุณารัน migrations/004_create_orders.sql');
            } elseif ($values['subject'] === '') {
                $alert = [
                    'type' => 'danger',
                    'title' => 'กรุณากรอกหัวข้อ',
                    'message' => '',
                ];
            } else {
                try {
                    $orderID = order_create_draft([
                        'dh_year' => system_get_dh_year(),
                        'subject' => $values['subject'],
                        'detail' => $values['detail'],
                        'status' => ORDER_STATUS_WAITING_ATTACHMENT,
                        'createdByPID' => $current_pid,
                    ], $_FILES['attachments'] ?? []);
                    $alert = [
                        'type' => 'success',
                        'title' => 'สร้างคำสั่งแล้ว',
                        'message' => 'เลขที่รายการ #' . $orderID,
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

        if (!$has_orders_table && $alert === null) {
            $alert = system_not_ready_alert('ยังไม่พบตาราง dh_orders กรุณารัน migrations/004_create_orders.sql');
        }

        view_render('orders/create', [
            'alert' => $alert,
            'values' => $values,
        ]);
    }
}

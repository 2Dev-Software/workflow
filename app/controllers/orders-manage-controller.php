<?php

declare(strict_types=1);

require_once __DIR__ . '/../views/view.php';
require_once __DIR__ . '/../../src/Services/auth/auth-guard.php';
require_once __DIR__ . '/../auth/csrf.php';
require_once __DIR__ . '/../rbac/current_user.php';
require_once __DIR__ . '/../modules/orders/service.php';
require_once __DIR__ . '/../modules/orders/repository.php';
require_once __DIR__ . '/../db/db.php';

if (!function_exists('orders_manage_index')) {
    function orders_manage_index(): void
    {
        $current_user = current_user() ?? [];
        $current_pid = (string) ($current_user['pID'] ?? '');
        $page = (int) ($_GET['page'] ?? 1);
        $page = $page > 0 ? $page : 1;
        $per_page = 10;

        $alert = null;

        $connection = db_connection();
        $has_orders_table = db_table_exists($connection, 'dh_orders');
        $has_inbox_table = db_table_exists($connection, 'dh_order_inboxes');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!csrf_validate($_POST['csrf_token'] ?? null)) {
                $alert = [
                    'type' => 'danger',
                    'title' => 'ไม่สามารถยืนยันความปลอดภัย',
                    'message' => 'กรุณาลองใหม่อีกครั้ง',
                ];
            } elseif (!$has_orders_table || !$has_inbox_table) {
                $alert = system_not_ready_alert('ยังไม่พบตารางคำสั่ง กรุณารัน migrations/004_create_orders.sql');
            } else {
                $action = $_POST['action'] ?? '';
                $order_id = (int) ($_POST['order_id'] ?? 0);

                if ($action === 'attach' && $order_id > 0) {
                    try {
                        order_attach_files($order_id, $current_pid, $_FILES['attachments'] ?? []);
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

        $total_pages = 1;

        if (!$has_orders_table || !$has_inbox_table) {
            if ($alert === null) {
                $alert = system_not_ready_alert('ยังไม่พบตารางคำสั่ง กรุณารัน migrations/004_create_orders.sql');
            }
            $orders = [];
        } else {
            $total_count = order_count_drafts($current_pid);
            $total_pages = max(1, (int) ceil($total_count / $per_page));

            if ($page > $total_pages) {
                $page = $total_pages;
            }
            $offset = ($page - 1) * $per_page;
            $orders = order_list_drafts_page($current_pid, $per_page, $offset);
        }

        view_render('orders/manage', [
            'alert' => $alert,
            'orders' => $orders,
            'page' => $page,
            'total_pages' => $total_pages,
        ]);
    }
}

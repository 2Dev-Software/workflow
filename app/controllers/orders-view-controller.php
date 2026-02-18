<?php

declare(strict_types=1);

require_once __DIR__ . '/../views/view.php';
require_once __DIR__ . '/../../src/Services/auth/auth-guard.php';
require_once __DIR__ . '/../auth/csrf.php';
require_once __DIR__ . '/../rbac/current_user.php';
require_once __DIR__ . '/../modules/orders/repository.php';
require_once __DIR__ . '/../db/db.php';

if (!function_exists('orders_view_index')) {
    function orders_view_index(): void
    {
        $current_user = current_user() ?? [];
        $current_pid = (string) ($current_user['pID'] ?? '');
        $inbox_id = (int) ($_GET['inbox_id'] ?? 0);

        if ($inbox_id <= 0) {
            header('Location: orders-inbox.php', true, 302);
            exit();
        }

        $alert = null;
        $item = null;
        $attachments = [];

        $connection = db_connection();
        $has_inbox_table = db_table_exists($connection, 'dh_order_inboxes');

        if (!$has_inbox_table) {
            $alert = system_not_ready_alert('ยังไม่พบตาราง dh_order_inboxes กรุณารัน migrations/004_create_orders.sql');
        } else {
            $item = order_get_inbox_item($inbox_id, $current_pid);

            if (!$item) {
                header('Location: orders-inbox.php', true, 302);
                exit();
            }

            if ((int) ($item['isRead'] ?? 0) === 0) {
                order_mark_read($inbox_id, $current_pid);
            }

            $attachments = order_get_attachments((int) ($item['orderID'] ?? 0));
        }

        view_render('orders/view', [
            'alert' => $alert,
            'item' => $item,
            'attachments' => $attachments,
        ]);
    }
}

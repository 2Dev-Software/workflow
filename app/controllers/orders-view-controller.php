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
        $timeline_events = [];

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
                $item = order_get_inbox_item($inbox_id, $current_pid) ?? $item;
            }

            $order_id = (int) ($item['orderID'] ?? 0);
            $attachments = order_get_attachments($order_id);
            $route_events = order_list_routes($order_id);
            $attachment_events = order_list_attachment_events($order_id);

            $route_label_map = [
                'CREATE' => 'สร้างคำสั่ง',
                'SEND' => 'ส่งคำสั่ง',
                'RECALL' => 'ดึงกลับคำสั่ง',
                'FORWARD' => 'ส่งต่อ',
                'ARCHIVE' => 'จัดเก็บ',
                'CANCEL' => 'ยกเลิก',
            ];

            foreach ($route_events as $route_event) {
                $code = (string) ($route_event['action'] ?? '');
                $actor_name = trim((string) ($route_event['fromName'] ?? ''));

                if ($actor_name === '') {
                    $actor_name = trim((string) ($route_event['toName'] ?? ''));
                }

                $timeline_events[] = [
                    'code' => $code,
                    'label' => $route_label_map[$code] ?? $code,
                    'actorName' => $actor_name !== '' ? $actor_name : '-',
                    'at' => (string) ($route_event['actionAt'] ?? '-'),
                    'note' => trim((string) ($route_event['note'] ?? '')),
                ];
            }

            foreach ($attachment_events as $attachment_event) {
                $timeline_events[] = [
                    'code' => 'ATTACH',
                    'label' => 'แนบไฟล์',
                    'actorName' => trim((string) ($attachment_event['attachedByName'] ?? '-')),
                    'at' => (string) ($attachment_event['attachedAt'] ?? '-'),
                    'note' => 'ไฟล์: ' . (string) ($attachment_event['fileName'] ?? '-'),
                ];
            }

            usort($timeline_events, static function (array $a, array $b): int {
                return strcmp((string) ($a['at'] ?? ''), (string) ($b['at'] ?? ''));
            });
        }

        view_render('orders/view', [
            'alert' => $alert,
            'item' => $item,
            'attachments' => $attachments,
            'timeline_events' => $timeline_events,
        ]);
    }
}

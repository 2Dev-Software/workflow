<?php
declare(strict_types=1);

require_once __DIR__ . '/../views/view.php';
require_once __DIR__ . '/../../src/Services/auth/auth-guard.php';
require_once __DIR__ . '/../auth/csrf.php';
require_once __DIR__ . '/../rbac/current_user.php';
require_once __DIR__ . '/../modules/orders/service.php';
require_once __DIR__ . '/../modules/orders/repository.php';
require_once __DIR__ . '/../modules/users/lists.php';
require_once __DIR__ . '/../db/db.php';

if (!function_exists('orders_send_index')) {
    function orders_send_index(): void
    {
        $current_user = current_user() ?? [];
        $current_pid = (string) ($current_user['pID'] ?? '');
        $order_id = (int) ($_GET['order_id'] ?? 0);

        if ($order_id <= 0) {
            header('Location: orders-manage.php', true, 302);
            exit();
        }

        $alert = null;
        $values = [
            'faction_ids' => [],
            'role_ids' => [],
            'person_ids' => [],
        ];

        $connection = db_connection();
        $has_orders_table = db_table_exists($connection, 'dh_orders');
        $has_recipients_table = db_table_exists($connection, 'dh_order_recipients');
        $has_inbox_table = db_table_exists($connection, 'dh_order_inboxes');
        $tables_ready = $has_orders_table && $has_recipients_table && $has_inbox_table;

        $order = null;
        if ($tables_ready) {
            $order = order_get($order_id);
        }

        if (!$tables_ready) {
            $alert = system_not_ready_alert('ยังไม่พบตารางคำสั่ง กรุณารัน migrations/004_create_orders.sql');
        } elseif (!$order || (string) ($order['createdByPID'] ?? '') !== $current_pid || ($order['status'] ?? '') !== ORDER_STATUS_COMPLETE) {
            header('Location: orders-manage.php', true, 302);
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $values['faction_ids'] = array_values((array) ($_POST['faction_ids'] ?? []));
            $values['role_ids'] = array_values((array) ($_POST['role_ids'] ?? []));
            $values['person_ids'] = array_values((array) ($_POST['person_ids'] ?? []));

            if (!csrf_validate($_POST['csrf_token'] ?? null)) {
                $alert = [
                    'type' => 'danger',
                    'title' => 'ไม่สามารถยืนยันความปลอดภัย',
                    'message' => 'กรุณาลองใหม่อีกครั้ง',
                ];
            } elseif (!$tables_ready) {
                $alert = system_not_ready_alert('ยังไม่พบตารางคำสั่ง กรุณารัน migrations/004_create_orders.sql');
            } else {
                try {
                    $targets = [];
                    foreach ($values['faction_ids'] as $fid) {
                        $targets[] = ['targetType' => 'UNIT', 'fID' => (int) $fid];
                    }
                    foreach ($values['role_ids'] as $rid) {
                        $targets[] = ['targetType' => 'ROLE', 'roleID' => (int) $rid];
                    }
                    foreach ($values['person_ids'] as $pid) {
                        $targets[] = ['targetType' => 'PERSON', 'pID' => (string) $pid];
                    }
                    $pids = order_resolve_recipients($values['faction_ids'], $values['role_ids'], $values['person_ids']);
                    order_send($order_id, $current_pid, ['pids' => $pids, 'targets' => $targets]);
                    $alert = [
                        'type' => 'success',
                        'title' => 'ส่งคำสั่งแล้ว',
                        'message' => '',
                    ];
                    $values = [
                        'faction_ids' => [],
                        'role_ids' => [],
                        'person_ids' => [],
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

        $factions = user_list_factions();
        $roles = user_list_roles();
        $teachers = user_list_teachers();

        view_render('orders/send', [
            'alert' => $alert,
            'order' => $order,
            'values' => $values,
            'factions' => $factions,
            'roles' => $roles,
            'teachers' => $teachers,
        ]);
    }
}

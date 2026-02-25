<?php

declare(strict_types=1);

require_once __DIR__ . '/../views/view.php';
require_once __DIR__ . '/../../src/Services/auth/auth-guard.php';
require_once __DIR__ . '/../auth/csrf.php';
require_once __DIR__ . '/../rbac/current_user.php';
require_once __DIR__ . '/../modules/orders/service.php';
require_once __DIR__ . '/../modules/system/system.php';
require_once __DIR__ . '/../modules/users/lists.php';
require_once __DIR__ . '/../db/db.php';

if (!function_exists('orders_issue_valid_date')) {
    function orders_issue_valid_date(string $value): bool
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1;
    }
}

if (!function_exists('orders_issue_build_detail')) {
    function orders_issue_build_detail(string $effective_date, string $order_date, string $issuer_name, string $group_name): string
    {
        $lines = [
            'ทั้งนี้ตั้งแต่วันที่: ' . $effective_date,
            'สั่ง ณ วันที่: ' . $order_date,
            'ผู้ออกเลขคำสั่ง: ' . ($issuer_name !== '' ? $issuer_name : '-'),
            'กลุ่ม: ' . ($group_name !== '' ? $group_name : '-'),
        ];

        return implode("\n", $lines);
    }
}

if (!function_exists('orders_issue_parse_detail')) {
    function orders_issue_parse_detail(?string $detail): array
    {
        $text = trim((string) $detail);
        $result = [
            'effective_date' => '',
            'order_date' => '',
            'issuer_name' => '',
            'group_name' => '',
        ];

        if ($text === '') {
            return $result;
        }

        if (preg_match('/^ทั้งนี้ตั้งแต่วันที่:\s*(.+)$/m', $text, $matches) === 1) {
            $date = trim((string) ($matches[1] ?? ''));

            if (orders_issue_valid_date($date)) {
                $result['effective_date'] = $date;
            }
        }

        if (preg_match('/^สั่ง ณ วันที่:\s*(.+)$/m', $text, $matches) === 1) {
            $date = trim((string) ($matches[1] ?? ''));

            if (orders_issue_valid_date($date)) {
                $result['order_date'] = $date;
            }
        }

        if (preg_match('/^ผู้ออกเลขคำสั่ง:\s*(.+)$/m', $text, $matches) === 1) {
            $value = trim((string) ($matches[1] ?? ''));
            $result['issuer_name'] = $value !== '-' ? $value : '';
        }

        if (preg_match('/^กลุ่ม:\s*(.+)$/m', $text, $matches) === 1) {
            $value = trim((string) ($matches[1] ?? ''));
            $result['group_name'] = $value !== '-' ? $value : '';
        }

        return $result;
    }
}

if (!function_exists('orders_issue_find_fid_by_name')) {
    function orders_issue_find_fid_by_name(string $group_name, array $faction_options): string
    {
        $target = trim($group_name);

        if ($target === '') {
            return '';
        }

        foreach ($faction_options as $fid => $name) {
            if (trim((string) $name) === $target) {
                return (string) $fid;
            }
        }

        return '';
    }
}

if (!function_exists('orders_create_index')) {
    function orders_create_index(): void
    {
        $current_user = current_user() ?? [];
        $current_pid = (string) ($current_user['pID'] ?? '');
        $issuer_name = trim((string) ($current_user['fName'] ?? ''));

        if ($issuer_name === '') {
            $issuer_name = $current_pid;
        }

        $today = date('Y-m-d');
        $edit_order_id = (int) ($_GET['edit'] ?? ($_POST['order_id'] ?? 0));

        $alert = null;
        $edit_order = null;
        $display_order_no = '';

        $connection = db_connection();
        $has_orders_table = db_table_exists($connection, 'dh_orders');
        $has_faction_table = db_table_exists($connection, 'faction');

        $faction_options = [];

        if ($has_faction_table) {
            foreach (user_list_factions() as $row) {
                $fid = (int) ($row['fID'] ?? 0);
                $name = trim((string) ($row['fName'] ?? ''));

                if ($fid <= 0 || $name === '') {
                    continue;
                }
                $faction_options[(string) $fid] = $name;
            }
        }

        $default_group_fid = '';
        $current_fid = (string) ((int) ($current_user['fID'] ?? 0));

        if ($current_fid !== '0' && isset($faction_options[$current_fid])) {
            $default_group_fid = $current_fid;
        } elseif (!empty($faction_options)) {
            $first_fid = array_key_first($faction_options);
            $default_group_fid = $first_fid !== null ? (string) $first_fid : '';
        }

        $values = [
            'subject' => '',
            'effective_date' => $today,
            'order_date' => $today,
            'group_fid' => $default_group_fid,
        ];

        if ($has_orders_table && $edit_order_id > 0) {
            $edit_order = order_get_for_owner($edit_order_id, $current_pid);

            if (!$edit_order || !in_array((string) ($edit_order['status'] ?? ''), [ORDER_STATUS_WAITING_ATTACHMENT, ORDER_STATUS_COMPLETE], true)) {
                header('Location: orders-create.php', true, 302);
                exit();
            }

            $parsed_meta = orders_issue_parse_detail((string) ($edit_order['detail'] ?? ''));

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $values['subject'] = trim((string) ($edit_order['subject'] ?? ''));
                $values['effective_date'] = $parsed_meta['effective_date'] !== '' ? $parsed_meta['effective_date'] : $today;
                $values['order_date'] = $parsed_meta['order_date'] !== '' ? $parsed_meta['order_date'] : $today;

                $parsed_group_fid = orders_issue_find_fid_by_name((string) $parsed_meta['group_name'], $faction_options);
                $values['group_fid'] = $parsed_group_fid !== '' ? $parsed_group_fid : $default_group_fid;
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $values['subject'] = trim((string) ($_POST['subject'] ?? ''));
            $values['effective_date'] = trim((string) ($_POST['effective_date'] ?? ''));
            $values['order_date'] = trim((string) ($_POST['order_date'] ?? ''));
            $values['group_fid'] = trim((string) ($_POST['group_fid'] ?? $default_group_fid));
            $post_order_id = (int) ($_POST['order_id'] ?? 0);
            $is_edit_submission = $post_order_id > 0 || $edit_order_id > 0;

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
                    'title' => 'กรุณากรอกเรื่อง',
                    'message' => '',
                ];
            } elseif (!orders_issue_valid_date($values['effective_date']) || !orders_issue_valid_date($values['order_date'])) {
                $alert = [
                    'type' => 'danger',
                    'title' => 'รูปแบบวันที่ไม่ถูกต้อง',
                    'message' => 'กรุณาเลือกวันที่ให้ครบถ้วน',
                ];
            } elseif ($values['group_fid'] === '' || !isset($faction_options[$values['group_fid']])) {
                $alert = [
                    'type' => 'danger',
                    'title' => 'กรุณาเลือกกลุ่ม',
                    'message' => '',
                ];
            } elseif ($is_edit_submission && !$edit_order) {
                $alert = [
                    'type' => 'danger',
                    'title' => 'ไม่พบคำสั่งที่ต้องการแก้ไข',
                    'message' => '',
                ];
            } else {
                $detail = orders_issue_build_detail(
                    $values['effective_date'],
                    $values['order_date'],
                    $issuer_name,
                    (string) ($faction_options[$values['group_fid']] ?? '')
                );

                try {
                    if ($is_edit_submission) {
                        $target_order_id = $post_order_id > 0 ? $post_order_id : $edit_order_id;
                        order_update_draft($target_order_id, $current_pid, [
                            'subject' => $values['subject'],
                            'detail' => $detail,
                        ]);
                        order_attach_files($target_order_id, $current_pid, $_FILES['attachments'] ?? []);

                        $edit_order = order_get_for_owner($target_order_id, $current_pid);
                        $alert = [
                            'type' => 'success',
                            'title' => 'บันทึกข้อมูลออกเลขแล้ว',
                            'message' => 'สามารถไปแนบไฟล์และส่งคำสั่งได้จากหน้าคำสั่งของฉัน',
                        ];
                    } else {
                        $orderID = order_create_draft([
                            'dh_year' => system_get_dh_year(),
                            'subject' => $values['subject'],
                            'detail' => $detail,
                            'status' => ORDER_STATUS_WAITING_ATTACHMENT,
                            'createdByPID' => $current_pid,
                        ], $_FILES['attachments'] ?? []);
                        $created_order = order_get_for_owner($orderID, $current_pid);
                        $created_order_no = trim((string) ($created_order['orderNo'] ?? ''));
                        $alert = [
                            'type' => 'success',
                            'title' => 'บันทึกออกเลขแล้ว',
                            'message' => $created_order_no !== '' ? ('เลขที่คำสั่ง ' . $created_order_no) : ('เลขที่รายการ #' . $orderID),
                        ];
                        $values = [
                            'subject' => '',
                            'effective_date' => $today,
                            'order_date' => $today,
                            'group_fid' => $default_group_fid,
                        ];
                    }
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

        $is_edit_mode = $edit_order_id > 0 && !empty($edit_order);

        if ($has_orders_table) {
            if ($is_edit_mode) {
                $display_order_no = trim((string) ($edit_order['orderNo'] ?? ''));
            } else {
                $display_order_no = order_preview_number(system_get_dh_year());
            }
        }

        view_render('orders/create', [
            'alert' => $alert,
            'values' => $values,
            'edit_order' => $edit_order,
            'edit_order_id' => $edit_order_id,
            'display_order_no' => $display_order_no,
            'issuer_name' => $issuer_name,
            'faction_options' => $faction_options,
        ]);
    }
}

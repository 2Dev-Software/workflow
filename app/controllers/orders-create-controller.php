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
        $sent_items = [];
        $status_map = [
            ORDER_STATUS_WAITING_ATTACHMENT => ['label' => 'รอการแนบไฟล์', 'pill' => 'pending'],
            ORDER_STATUS_COMPLETE => ['label' => 'แนบไฟล์สำเร็จ', 'pill' => 'primary'],
            ORDER_STATUS_SENT => ['label' => 'ส่งต่อคำสั่งสำเร็จ', 'pill' => 'approved'],
        ];
        $edit_modal_attachments_map = [];
        $send_modal_payload_map = [];
        $send_modal_values = [
            'faction_ids' => [],
            'role_ids' => [],
            'person_ids' => [],
        ];
        $send_modal_open_order_id = 0;
        $send_modal_summary = [
            'selected_sources' => 0,
            'unique_recipients' => 0,
        ];
        $active_tab = (string) ($_GET['tab'] ?? 'compose');
        $is_track_active = $active_tab === 'track';
        $has_track_filters = array_key_exists('q', $_GET) || array_key_exists('status', $_GET) || array_key_exists('sort', $_GET);

        if ($has_track_filters) {
            $is_track_active = true;
        }
        $filter_query = trim((string) ($_GET['q'] ?? ''));
        $filter_status = trim((string) ($_GET['status'] ?? 'all'));
        $filter_sort = trim((string) ($_GET['sort'] ?? 'newest'));

        if (!in_array($filter_status, ['all', 'waiting_attachment', 'complete'], true)) {
            $filter_status = 'all';
        }

        if (!in_array($filter_sort, ['newest', 'oldest'], true)) {
            $filter_sort = 'newest';
        }

        $connection = db_connection();
        $has_orders_table = db_table_exists($connection, 'dh_orders');
        $has_faction_table = db_table_exists($connection, 'faction');
        $has_recipients_table = db_table_exists($connection, 'dh_order_recipients');
        $has_inbox_table = db_table_exists($connection, 'dh_order_inboxes');
        $send_tables_ready = $has_orders_table && $has_recipients_table && $has_inbox_table;

        $faction_options = [];
        $send_picker_factions = [];
        $send_picker_roles = user_list_roles();
        $send_picker_teachers = user_list_teachers();
        if ($current_pid !== '') {
            $send_picker_teachers = array_values(array_filter(
                $send_picker_teachers,
                static function (array $teacher) use ($current_pid): bool {
                    return trim((string) ($teacher['pID'] ?? '')) !== $current_pid;
                }
            ));
        }
        $send_picker_faction_member_map = [];
        $send_picker_role_member_map = [];

        if ($has_faction_table) {
            $send_picker_factions = user_list_factions();

            foreach ($send_picker_factions as $row) {
                $fid = (int) ($row['fID'] ?? 0);
                $name = trim((string) ($row['fName'] ?? ''));

                if ($fid <= 0 || $name === '') {
                    continue;
                }
                $faction_options[(string) $fid] = $name;
            }
        }

        foreach ($send_picker_teachers as $teacher) {
            $pid = trim((string) ($teacher['pID'] ?? ''));
            $fid = (int) ($teacher['fID'] ?? 0);
            $rid = (int) ($teacher['roleID'] ?? 0);

            if ($pid === '') {
                continue;
            }

            if ($fid > 0) {
                $fid_key = (string) $fid;
                if (!isset($send_picker_faction_member_map[$fid_key])) {
                    $send_picker_faction_member_map[$fid_key] = [];
                }
                $send_picker_faction_member_map[$fid_key][] = $pid;
            }

            if ($rid > 0) {
                $rid_key = (string) $rid;
                if (!isset($send_picker_role_member_map[$rid_key])) {
                    $send_picker_role_member_map[$rid_key] = [];
                }
                $send_picker_role_member_map[$rid_key][] = $pid;
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
            $post_action = trim((string) ($_POST['order_action'] ?? ''));

            if ($post_action !== '') {
                $is_track_active = true;
                $send_modal_open_order_id = (int) ($_POST['send_order_id'] ?? 0);
                $send_modal_values['faction_ids'] = array_values((array) ($_POST['faction_ids'] ?? []));
                $send_modal_values['role_ids'] = array_values((array) ($_POST['role_ids'] ?? []));
                $send_modal_values['person_ids'] = array_values((array) ($_POST['person_ids'] ?? []));
                $send_modal_summary['selected_sources'] = count($send_modal_values['faction_ids']) + count($send_modal_values['role_ids']) + count($send_modal_values['person_ids']);

                if (!csrf_validate($_POST['csrf_token'] ?? null)) {
                    $alert = [
                        'type' => 'danger',
                        'title' => 'ไม่สามารถยืนยันความปลอดภัย',
                        'message' => 'กรุณาลองใหม่อีกครั้ง',
                    ];
                } elseif (!$send_tables_ready) {
                    $alert = system_not_ready_alert('ยังไม่พบตารางคำสั่ง กรุณารัน migrations/004_create_orders.sql');
                } elseif ($send_modal_open_order_id <= 0) {
                    $alert = [
                        'type' => 'danger',
                        'title' => 'ไม่พบคำสั่งที่ต้องการส่ง',
                        'message' => '',
                    ];
                } else {
                    $target_order = order_get_for_owner($send_modal_open_order_id, $current_pid);
                    $target_status = strtoupper(trim((string) ($target_order['status'] ?? '')));

                    if (!$target_order || !in_array($target_status, [ORDER_STATUS_COMPLETE, ORDER_STATUS_SENT], true)) {
                        $alert = [
                            'type' => 'danger',
                            'title' => 'ไม่พบคำสั่งหรือไม่มีสิทธิ์ดำเนินการ',
                            'message' => '',
                        ];
                    } else {
                        try {
                            if ($post_action === 'recall') {
                                order_recall($send_modal_open_order_id, $current_pid);
                                $alert = [
                                    'type' => 'success',
                                    'title' => 'ดึงคำสั่งกลับแล้ว',
                                    'message' => 'สามารถแก้ไขและส่งคำสั่งใหม่ได้',
                                ];
                                $send_modal_open_order_id = 0;
                            } elseif ($post_action === 'send') {
                                if ($target_status !== ORDER_STATUS_COMPLETE) {
                                    throw new RuntimeException('ส่งคำสั่งได้เฉพาะสถานะแนบไฟล์สำเร็จ');
                                }

                                $targets = [];

                                foreach ($send_modal_values['faction_ids'] as $fid) {
                                    $targets[] = ['targetType' => 'UNIT', 'fID' => (int) $fid];
                                }

                                foreach ($send_modal_values['role_ids'] as $rid) {
                                    $targets[] = ['targetType' => 'ROLE', 'roleID' => (int) $rid];
                                }

                                foreach ($send_modal_values['person_ids'] as $pid) {
                                    $targets[] = ['targetType' => 'PERSON', 'pID' => (string) $pid];
                                }

                                $pids = order_resolve_recipients(
                                    $send_modal_values['faction_ids'],
                                    $send_modal_values['role_ids'],
                                    $send_modal_values['person_ids']
                                );
                                $send_modal_summary['unique_recipients'] = count(array_values(array_unique(array_map('strval', $pids))));
                                order_send($send_modal_open_order_id, $current_pid, ['pids' => $pids, 'targets' => $targets]);
                                $alert = [
                                    'type' => 'success',
                                    'title' => 'ส่งคำสั่งแล้ว',
                                    'message' => '',
                                ];
                                $send_modal_values = [
                                    'faction_ids' => [],
                                    'role_ids' => [],
                                    'person_ids' => [],
                                ];
                                $send_modal_summary = [
                                    'selected_sources' => 0,
                                    'unique_recipients' => 0,
                                ];
                                $send_modal_open_order_id = 0;
                            } else {
                                throw new RuntimeException('ไม่รู้จักคำสั่งที่ส่งมา');
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
            } else {
                $submitted_values = [
                    'subject' => trim((string) ($_POST['subject'] ?? '')),
                    'effective_date' => trim((string) ($_POST['effective_date'] ?? '')),
                    'order_date' => trim((string) ($_POST['order_date'] ?? '')),
                    'group_fid' => trim((string) ($_POST['group_fid'] ?? $default_group_fid)),
                ];
                $post_order_id = (int) ($_POST['order_id'] ?? 0);
                $is_edit_submission = $post_order_id > 0 || $edit_order_id > 0;
                $from_track_modal = (int) ($_POST['from_track_modal'] ?? 0) === 1;

                if ($from_track_modal) {
                    $is_track_active = true;
                } else {
                    $values = $submitted_values;
                }

                if (!csrf_validate($_POST['csrf_token'] ?? null)) {
                    $alert = [
                        'type' => 'danger',
                        'title' => 'ไม่สามารถยืนยันความปลอดภัย',
                        'message' => 'กรุณาลองใหม่อีกครั้ง',
                    ];
                } elseif (!$has_orders_table) {
                    $alert = system_not_ready_alert('ยังไม่พบตาราง dh_orders กรุณารัน migrations/004_create_orders.sql');
                } elseif ($submitted_values['subject'] === '') {
                    $alert = [
                        'type' => 'danger',
                        'title' => 'กรุณากรอกเรื่อง',
                        'message' => '',
                    ];
                } elseif (!orders_issue_valid_date($submitted_values['effective_date']) || !orders_issue_valid_date($submitted_values['order_date'])) {
                    $alert = [
                        'type' => 'danger',
                        'title' => 'รูปแบบวันที่ไม่ถูกต้อง',
                        'message' => 'กรุณาเลือกวันที่ให้ครบถ้วน',
                    ];
                } elseif ($submitted_values['group_fid'] === '' || !isset($faction_options[$submitted_values['group_fid']])) {
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
                        $submitted_values['effective_date'],
                        $submitted_values['order_date'],
                        $issuer_name,
                        (string) ($faction_options[$submitted_values['group_fid']] ?? '')
                    );

                    try {
                        if ($is_edit_submission) {
                            $target_order_id = $post_order_id > 0 ? $post_order_id : $edit_order_id;
                            order_update_draft_with_attachments($target_order_id, $current_pid, [
                                'subject' => $submitted_values['subject'],
                                'detail' => $detail,
                            ], $_FILES['attachments'] ?? []);

                            $edit_order = order_get_for_owner($target_order_id, $current_pid);
                            $alert = [
                                'type' => 'success',
                                'title' => 'บันทึกข้อมูลออกเลขแล้ว',
                                'message' => 'สามารถจัดการการส่งได้จากหน้าเลขคำสั่งของฉัน',
                            ];
                        } else {
                            $orderID = order_create_draft([
                                'dh_year' => system_get_dh_year(),
                                'subject' => $submitted_values['subject'],
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
        }

        // Keep "ออกเลขคำสั่ง" as issue-only screen.
        // Modal edit submissions must not switch the page into edit-mode rendering.
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && (int) ($_POST['from_track_modal'] ?? 0) === 1) {
            $edit_order_id = 0;
            $edit_order = null;
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

            $owner_status_filter = match ($filter_status) {
                'waiting_attachment' => 'waiting',
                'complete' => 'complete',
                default => 'all',
            };

            $sent_items = order_list_drafts_page_filtered($current_pid, [
                'status' => $owner_status_filter,
                'q' => $filter_query,
                'sort' => $filter_sort,
            ], 200, 0);

            foreach ($sent_items as $item) {
                $order_id = (int) ($item['orderID'] ?? 0);
                $status_key = strtoupper(trim((string) ($item['status'] ?? '')));
                $parsed_meta = orders_issue_parse_detail((string) ($item['detail'] ?? ''));
                $attachments_for_modal = [];
                $normalized_attachments = [];

                if ($order_id > 0) {
                    $attachments_for_modal = order_get_attachments($order_id);

                    foreach ($attachments_for_modal as $file) {
                        $normalized_attachments[] = [
                            'fileID' => (int) ($file['fileID'] ?? 0),
                            'fileName' => (string) ($file['fileName'] ?? ''),
                            'mimeType' => (string) ($file['mimeType'] ?? ''),
                        ];
                    }
                }

                if ($order_id > 0 && in_array($status_key, [ORDER_STATUS_WAITING_ATTACHMENT, ORDER_STATUS_COMPLETE], true)) {
                    $edit_modal_attachments_map[(string) $order_id] = $normalized_attachments;
                }

                if ($order_id <= 0 || !in_array($status_key, [ORDER_STATUS_COMPLETE, ORDER_STATUS_SENT], true)) {
                    continue;
                }

                $read_stats = [];
                $read_done = 0;

                if ($status_key === ORDER_STATUS_SENT && $send_tables_ready) {
                    $raw_read_stats = order_get_read_stats($order_id, 'all');

                    foreach ($raw_read_stats as $row) {
                        $is_read = (int) ($row['isRead'] ?? 0) === 1;
                        if ($is_read) {
                            $read_done++;
                        }
                        $read_stats[] = [
                            'name' => trim((string) ($row['fName'] ?? '')) !== '' ? trim((string) ($row['fName'] ?? '')) : '-',
                            'isRead' => $is_read ? 1 : 0,
                            'readAt' => (string) ($row['readAt'] ?? ''),
                        ];
                    }
                }

                $send_modal_payload_map[(string) $order_id] = [
                    'orderID' => $order_id,
                    'orderNo' => trim((string) ($item['orderNo'] ?? '')),
                    'subject' => trim((string) ($item['subject'] ?? '')),
                    'effectiveDate' => trim((string) ($parsed_meta['effective_date'] ?? '')),
                    'orderDate' => trim((string) ($parsed_meta['order_date'] ?? '')),
                    'issuerName' => trim((string) ($parsed_meta['issuer_name'] ?? '')),
                    'groupName' => trim((string) ($parsed_meta['group_name'] ?? '')),
                    'attachments' => $normalized_attachments,
                    'status' => $status_key,
                    'readStats' => $read_stats,
                    'readDone' => $read_done,
                    'readTotal' => count($read_stats),
                    'canRecall' => $status_key === ORDER_STATUS_SENT ? ($read_done === 0) : false,
                ];
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
            'is_track_active' => $is_track_active,
            'filter_query' => $filter_query,
            'filter_status' => $filter_status,
            'filter_sort' => $filter_sort,
            'sent_items' => $sent_items,
            'status_map' => $status_map,
            'edit_modal_attachments_map' => $edit_modal_attachments_map,
            'send_modal_payload_map' => $send_modal_payload_map,
            'send_modal_values' => $send_modal_values,
            'send_modal_open_order_id' => $send_modal_open_order_id,
            'send_modal_summary' => $send_modal_summary,
            'send_picker_factions' => $send_picker_factions,
            'send_picker_roles' => $send_picker_roles,
            'send_picker_teachers' => $send_picker_teachers,
            'send_picker_faction_member_map' => $send_picker_faction_member_map,
            'send_picker_role_member_map' => $send_picker_role_member_map,
        ]);
    }
}

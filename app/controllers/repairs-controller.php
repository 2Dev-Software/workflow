<?php

declare(strict_types=1);

require_once __DIR__ . '/../views/view.php';
require_once __DIR__ . '/../../src/Services/auth/auth-guard.php';
require_once __DIR__ . '/../auth/csrf.php';
require_once __DIR__ . '/../rbac/current_user.php';
require_once __DIR__ . '/../rbac/roles.php';
require_once __DIR__ . '/../modules/repairs/repository.php';
require_once __DIR__ . '/../modules/system/system.php';
require_once __DIR__ . '/../services/uploads.php';
require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/../modules/audit/logger.php';
require_once __DIR__ . '/../config/state.php';

if (!function_exists('repairs_mode_config')) {
    function repairs_mode_config(string $mode): array {
        $configs = [
            'report' => [
                'base_url' => 'repairs.php',
                'title' => 'ยินดีต้อนรับ',
                'subtitle' => 'แจ้งเหตุซ่อมแซม',
                'form_title' => 'แจ้งเหตุซ่อมแซม',
                'form_subtitle' => 'กรอกข้อมูลให้ครบถ้วนเพื่อส่งคำขอซ่อม',
                'list_title' => 'รายการแจ้งซ่อมของฉัน',
                'list_subtitle' => 'ติดตามสถานะคำขอซ่อมที่คุณแจ้งไว้',
                'empty_title' => 'ยังไม่มีรายการแจ้งซ่อม',
                'empty_message' => 'เมื่อมีการแจ้งซ่อม รายการจะแสดงที่หน้านี้',
                'show_form' => true,
                'show_requester_column' => false,
                'statuses' => [],
            ],
            'approval' => [
                'base_url' => 'repairs-approval.php',
                'title' => 'อนุมัติการซ่อมแซม',
                'subtitle' => 'ตรวจสอบและพิจารณาคำขอซ่อมที่รอดำเนินการ',
                'form_title' => '',
                'form_subtitle' => '',
                'list_title' => 'รายการรออนุมัติการซ่อมแซม',
                'list_subtitle' => 'คำขอซ่อมที่รอเจ้าหน้าที่สถานที่ตรวจสอบ',
                'empty_title' => 'ยังไม่มีรายการรออนุมัติ',
                'empty_message' => 'เมื่อมีคำขอซ่อมใหม่ รายการจะปรากฏที่หน้านี้',
                'show_form' => false,
                'show_requester_column' => true,
                'statuses' => [REPAIR_STATUS_PENDING],
            ],
            'manage' => [
                'base_url' => 'repairs-management.php',
                'title' => 'ติดตามงานซ่อม',
                'subtitle' => 'ติดตามความคืบหน้าและปิดงานซ่อมทั้งหมดของระบบ',
                'form_title' => '',
                'form_subtitle' => '',
                'list_title' => 'รายการงานซ่อมทั้งหมด',
                'list_subtitle' => 'ตรวจสอบสถานะและอัปเดตการดำเนินงานซ่อม',
                'empty_title' => 'ยังไม่มีรายการงานซ่อม',
                'empty_message' => 'เมื่อมีการแจ้งซ่อม รายการจะปรากฏที่หน้านี้',
                'show_form' => false,
                'show_requester_column' => true,
                'statuses' => [],
            ],
        ];

        return $configs[$mode] ?? $configs['report'];
    }
}

if (!function_exists('repair_can_transition')) {
    function repair_can_transition(string $from_status, string $to_status): bool
    {
        $machines = workflow_state_machine();
        $repair_machine = (array) ($machines['repairs'] ?? []);
        $allowed_targets = (array) ($repair_machine[$from_status] ?? []);

        return in_array($to_status, $allowed_targets, true);
    }
}

if (!function_exists('repairs_transition_actions')) {
    function repairs_transition_actions(string $mode, ?array $repair): array
    {
        if (!$repair) {
            return [];
        }

        $current_status = (string) ($repair['status'] ?? '');

        if ($mode === 'approval') {
            if ($current_status !== REPAIR_STATUS_PENDING) {
                return [];
            }

            return [
                [
                    'target_status' => REPAIR_STATUS_IN_PROGRESS,
                    'label' => 'อนุมัติการซ่อมแซม',
                    'variant' => 'primary',
                    'confirm' => 'ยืนยันการอนุมัติคำขอซ่อมนี้ใช่หรือไม่?',
                    'confirm_title' => 'ยืนยันการอนุมัติ',
                ],
                [
                    'target_status' => REPAIR_STATUS_REJECTED,
                    'label' => 'ไม่อนุมัติ',
                    'variant' => 'danger',
                    'confirm' => 'ยืนยันการไม่อนุมัติคำขอซ่อมนี้ใช่หรือไม่?',
                    'confirm_title' => 'ยืนยันการไม่อนุมัติ',
                ],
            ];
        }

        if ($mode === 'manage') {
            $actions = [];

            if ($current_status === REPAIR_STATUS_PENDING) {
                $actions[] = [
                    'target_status' => REPAIR_STATUS_IN_PROGRESS,
                    'label' => 'รับดำเนินการ',
                    'variant' => 'primary',
                    'confirm' => 'ยืนยันการรับดำเนินการรายการนี้ใช่หรือไม่?',
                    'confirm_title' => 'ยืนยันการรับงาน',
                ];
                $actions[] = [
                    'target_status' => REPAIR_STATUS_REJECTED,
                    'label' => 'ไม่อนุมัติ',
                    'variant' => 'danger',
                    'confirm' => 'ยืนยันการไม่อนุมัติรายการนี้ใช่หรือไม่?',
                    'confirm_title' => 'ยืนยันการไม่อนุมัติ',
                ];
                $actions[] = [
                    'target_status' => REPAIR_STATUS_CANCELLED,
                    'label' => 'ยกเลิกรายการ',
                    'variant' => 'secondary',
                    'confirm' => 'ยืนยันการยกเลิกรายการนี้ใช่หรือไม่?',
                    'confirm_title' => 'ยืนยันการยกเลิก',
                ];
            } elseif ($current_status === REPAIR_STATUS_IN_PROGRESS) {
                $actions[] = [
                    'target_status' => REPAIR_STATUS_COMPLETED,
                    'label' => 'ปิดงานซ่อม',
                    'variant' => 'primary',
                    'confirm' => 'ยืนยันการปิดงานซ่อมรายการนี้ใช่หรือไม่?',
                    'confirm_title' => 'ยืนยันการปิดงาน',
                ];
                $actions[] = [
                    'target_status' => REPAIR_STATUS_CANCELLED,
                    'label' => 'ยกเลิกรายการ',
                    'variant' => 'secondary',
                    'confirm' => 'ยืนยันการยกเลิกรายการนี้ใช่หรือไม่?',
                    'confirm_title' => 'ยืนยันการยกเลิก',
                ];
            }

            return $actions;
        }

        return [];
    }
}

if (!function_exists('repairs_render_forbidden')) {
    function repairs_render_forbidden(): void
    {
        view_render('errors/403');
    }
}

if (!function_exists('repairs_index')) {
    function repairs_index(): void
    {
        repairs_handle_mode('report');
    }
}

if (!function_exists('repairs_approval_index')) {
    function repairs_approval_index(): void
    {
        repairs_handle_mode('approval');
    }
}

if (!function_exists('repairs_management_index')) {
    function repairs_management_index(): void
    {
        repairs_handle_mode('manage');
    }
}

if (!function_exists('repairs_handle_mode')) {
    function repairs_handle_mode(string $mode): void
    {
        $mode = in_array($mode, ['report', 'approval', 'manage'], true) ? $mode : 'report';
        $config = repairs_mode_config($mode);
        $current_user = current_user() ?? [];
        $current_pid = (string) ($current_user['pID'] ?? '');
        $connection = db_connection();
        $has_table = db_table_exists($connection, 'dh_repair_requests');
        $has_equipment_column = $has_table && db_column_exists($connection, 'dh_repair_requests', 'equipment');

        $is_admin = rbac_user_has_role($connection, $current_pid, ROLE_ADMIN)
            || (int) ($current_user['roleID'] ?? 0) === 1;
        $is_facility = $is_admin
            || rbac_user_has_role($connection, $current_pid, ROLE_FACILITY)
            || (int) ($current_user['roleID'] ?? 0) === 5;

        if (($mode === 'approval' && !$is_facility) || ($mode === 'manage' && !$is_admin)) {
            repairs_render_forbidden();

            return;
        }

        $alert = null;
        $values = [
            'subject' => '',
            'location' => '',
            'equipment' => '',
            'detail' => '',
        ];
        $view_id = (int) ($_GET['view_id'] ?? 0);
        $edit_id = $mode === 'report' ? (int) ($_GET['edit_id'] ?? 0) : 0;
        $view_item = null;
        $view_attachments = [];
        $edit_item = null;
        $edit_attachments = [];

        if ($edit_id > 0) {
            $view_id = 0;
        }

        $can_access_repair = static function (?array $repair) use ($mode, $current_pid, $is_facility, $is_admin): bool {
            if (!$repair) {
                return false;
            }

            if ($mode === 'manage') {
                return $is_admin;
            }

            if ($mode === 'approval') {
                return $is_facility;
            }

            return (string) ($repair['requesterPID'] ?? '') === $current_pid;
        };

        if ($view_id > 0 && $has_table) {
            $view_item = repair_get($view_id);

            if (!$can_access_repair($view_item)) {
                $alert = [
                    'type' => 'danger',
                    'title' => 'ไม่มีสิทธิ์เข้าถึง',
                    'message' => 'คุณไม่มีสิทธิ์ดูรายการนี้',
                ];
                $view_item = null;
            } else {
                $view_attachments = repair_get_attachments($view_id);
            }
        }

        if ($edit_id > 0 && $has_table) {
            $edit_item = repair_get($edit_id);

            if (!$can_access_repair($edit_item)) {
                $alert = [
                    'type' => 'danger',
                    'title' => 'ไม่มีสิทธิ์แก้ไข',
                    'message' => 'คุณไม่มีสิทธิ์แก้ไขรายการนี้',
                ];
                $edit_item = null;
            } elseif ((string) ($edit_item['status'] ?? '') !== REPAIR_STATUS_PENDING) {
                $alert = [
                    'type' => 'warning',
                    'title' => 'ไม่สามารถแก้ไขได้',
                    'message' => 'แก้ไขได้เฉพาะรายการที่มีสถานะรอดำเนินการเท่านั้น',
                ];
                $edit_item = null;
            } else {
                $values = [
                    'subject' => (string) ($edit_item['subject'] ?? ''),
                    'location' => (string) ($edit_item['location'] ?? ''),
                    'equipment' => (string) ($edit_item['equipment'] ?? ''),
                    'detail' => (string) ($edit_item['detail'] ?? ''),
                ];
                $edit_attachments = repair_get_attachments($edit_id);
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = (string) ($_POST['action'] ?? 'create');
            $repair_id = (int) ($_POST['repair_id'] ?? 0);
            $target_status = trim((string) ($_POST['target_status'] ?? ''));
            $values['subject'] = trim((string) ($_POST['subject'] ?? ''));
            $values['location'] = trim((string) ($_POST['location'] ?? ''));
            $values['equipment'] = trim((string) ($_POST['equipment'] ?? ''));
            $values['detail'] = trim((string) ($_POST['detail'] ?? ''));

            if (!csrf_validate($_POST['csrf_token'] ?? null)) {
                $alert = [
                    'type' => 'danger',
                    'title' => 'ไม่สามารถยืนยันความปลอดภัย',
                    'message' => 'กรุณาลองใหม่อีกครั้ง',
                ];
            } elseif (!$has_table || !$has_equipment_column) {
                $alert = system_not_ready_alert('ยังไม่พบโครงสร้าง repairs ล่าสุด กรุณารัน migrations/019_add_repair_equipment_column.sql');
            } elseif ($action === 'delete') {
                $target = $repair_id > 0 ? repair_get($repair_id) : null;

                if (!$can_access_repair($target)) {
                    $alert = [
                        'type' => 'danger',
                        'title' => 'ไม่มีสิทธิ์ลบ',
                        'message' => 'คุณไม่มีสิทธิ์ลบรายการนี้',
                    ];
                } elseif ((string) ($target['status'] ?? '') !== REPAIR_STATUS_PENDING) {
                    $alert = [
                        'type' => 'warning',
                        'title' => 'ไม่สามารถลบได้',
                        'message' => 'ลบได้เฉพาะรายการที่มีสถานะรอดำเนินการเท่านั้น',
                    ];
                } else {
                    repair_delete_record($repair_id);

                    if (function_exists('audit_log')) {
                        audit_log('repairs', 'DELETE', 'SUCCESS', REPAIR_ENTITY_NAME, $repair_id);
                    }
                    $alert = [
                        'type' => 'success',
                        'title' => 'ลบรายการแล้ว',
                        'message' => '',
                    ];
                    $view_id = 0;
                    $edit_id = 0;
                    $view_item = null;
                    $edit_item = null;
                }
            } elseif ($action === 'update') {
                $target = $repair_id > 0 ? repair_get($repair_id) : null;

                if (!$can_access_repair($target)) {
                    $alert = [
                        'type' => 'danger',
                        'title' => 'ไม่มีสิทธิ์แก้ไข',
                        'message' => 'คุณไม่มีสิทธิ์แก้ไขรายการนี้',
                    ];
                } elseif ((string) ($target['status'] ?? '') !== REPAIR_STATUS_PENDING) {
                    $alert = [
                        'type' => 'warning',
                        'title' => 'ไม่สามารถแก้ไขได้',
                        'message' => 'แก้ไขได้เฉพาะรายการที่มีสถานะรอดำเนินการเท่านั้น',
                    ];
                } elseif ($values['subject'] === '') {
                    $alert = [
                        'type' => 'danger',
                        'title' => 'กรุณากรอกหัวข้อ',
                        'message' => '',
                    ];
                } else {
                    repair_update_record($repair_id, [
                        'subject' => $values['subject'],
                        'detail' => $values['detail'],
                        'location' => $values['location'],
                        'equipment' => $values['equipment'],
                    ]);

                    if (function_exists('audit_log')) {
                        audit_log('repairs', 'UPDATE', 'SUCCESS', REPAIR_ENTITY_NAME, $repair_id);
                    }

                    try {
                        if (!empty($_FILES['attachments'])) {
                            upload_store_files($_FILES['attachments'], REPAIR_MODULE_NAME, REPAIR_ENTITY_NAME, (string) $repair_id, $current_pid, [
                                'max_files' => 0,
                                'allowed_mimes' => upload_allowed_image_mimes(),
                            ]);
                        }
                    } catch (RuntimeException $exception) {
                        $alert = [
                            'type' => 'danger',
                            'title' => 'แนบไฟล์ไม่สำเร็จ',
                            'message' => $exception->getMessage(),
                        ];
                    }

                    if ($alert === null || $alert['type'] === 'success') {
                        $alert = [
                            'type' => 'success',
                            'title' => 'แก้ไขรายการแล้ว',
                            'message' => '',
                        ];
                        $edit_id = 0;
                        $edit_item = null;
                    }
                }
            } elseif ($action === 'transition') {
                $target = $repair_id > 0 ? repair_get($repair_id) : null;
                $current_status = (string) ($target['status'] ?? '');

                if (!$can_access_repair($target)) {
                    $alert = [
                        'type' => 'danger',
                        'title' => 'ไม่มีสิทธิ์ดำเนินการ',
                        'message' => 'คุณไม่มีสิทธิ์เปลี่ยนสถานะรายการนี้',
                    ];
                } elseif ($target_status === '' || !repair_can_transition($current_status, $target_status)) {
                    $alert = [
                        'type' => 'warning',
                        'title' => 'ไม่สามารถเปลี่ยนสถานะได้',
                        'message' => 'สถานะที่เลือกไม่ถูกต้องสำหรับรายการนี้',
                    ];
                } elseif ($mode === 'approval' && !in_array($target_status, [REPAIR_STATUS_IN_PROGRESS, REPAIR_STATUS_REJECTED], true)) {
                    $alert = [
                        'type' => 'warning',
                        'title' => 'ไม่สามารถดำเนินการได้',
                        'message' => 'หน้าอนุมัติการซ่อมแซมรองรับเฉพาะการอนุมัติหรือไม่อนุมัติเท่านั้น',
                    ];
                } else {
                    $update_data = [
                        'status' => $target_status,
                    ];

                    if ($target_status === REPAIR_STATUS_IN_PROGRESS) {
                        $update_data['assignedToPID'] = $current_pid;
                        $update_data['resolvedAt'] = null;
                    } elseif (in_array($target_status, [REPAIR_STATUS_COMPLETED, REPAIR_STATUS_REJECTED, REPAIR_STATUS_CANCELLED], true)) {
                        $update_data['assignedToPID'] = (string) ($target['assignedToPID'] ?? '') !== '' ? (string) $target['assignedToPID'] : $current_pid;
                        $update_data['resolvedAt'] = date('Y-m-d H:i:s');
                    }

                    repair_update_record($repair_id, $update_data);

                    if (function_exists('audit_log')) {
                        audit_log('repairs', 'TRANSITION', 'SUCCESS', REPAIR_ENTITY_NAME, $repair_id, $target_status);
                    }

                    $alert = [
                        'type' => 'success',
                        'title' => 'อัปเดตสถานะแล้ว',
                        'message' => '',
                    ];

                    if ($mode === 'approval') {
                        $view_id = 0;
                        $view_item = null;
                        $view_attachments = [];
                    } else {
                        $view_id = $repair_id;
                        $view_item = repair_get($repair_id);
                        $view_attachments = repair_get_attachments($repair_id);
                    }
                }
            } elseif ($mode !== 'report') {
                $alert = [
                    'type' => 'warning',
                    'title' => 'ไม่สามารถทำรายการได้',
                    'message' => 'หน้านี้รองรับเฉพาะการดำเนินการตาม workflow',
                ];
            } elseif ($values['subject'] === '') {
                $alert = [
                    'type' => 'danger',
                    'title' => 'กรุณากรอกหัวข้อ',
                    'message' => '',
                ];
            } else {
                $repair_id = repair_create_record([
                    'dh_year' => system_get_dh_year(),
                    'requesterPID' => $current_pid,
                    'subject' => $values['subject'],
                    'detail' => $values['detail'],
                    'location' => $values['location'],
                    'equipment' => $values['equipment'],
                    'status' => REPAIR_STATUS_PENDING,
                ]);

                if (function_exists('audit_log')) {
                    audit_log('repairs', 'CREATE', 'SUCCESS', REPAIR_ENTITY_NAME, $repair_id);
                }

                try {
                    if (!empty($_FILES['attachments'])) {
                        upload_store_files($_FILES['attachments'], REPAIR_MODULE_NAME, REPAIR_ENTITY_NAME, (string) $repair_id, $current_pid, [
                            'max_files' => 0,
                            'allowed_mimes' => upload_allowed_image_mimes(),
                        ]);
                    }
                    $alert = [
                        'type' => 'success',
                        'title' => 'บันทึกแจ้งซ่อมแล้ว',
                        'message' => '',
                    ];
                    $values = ['subject' => '', 'location' => '', 'equipment' => '', 'detail' => ''];
                } catch (RuntimeException $exception) {
                    $alert = [
                        'type' => 'danger',
                        'title' => 'แนบไฟล์ไม่สำเร็จ',
                        'message' => $exception->getMessage(),
                    ];
                }
            }
        }

        if ((!$has_table || !$has_equipment_column) && $alert === null) {
            $alert = system_not_ready_alert('ยังไม่พบโครงสร้าง repairs ล่าสุด กรุณารัน migrations/019_add_repair_equipment_column.sql');
        }

        $page = (int) ($_GET['page'] ?? 1);
        $page = $page > 0 ? $page : 1;
        $per_page = 10;
        $total_pages = 1;
        $total_count = 0;
        $statuses = (array) ($config['statuses'] ?? []);

        if (!$has_table || !$has_equipment_column) {
            $requests = [];
        } elseif ($mode === 'report') {
            $total_count = repair_count_filtered($current_pid, []);
            $total_pages = max(1, (int) ceil($total_count / $per_page));
            $page = min($page, $total_pages);
            $offset = ($page - 1) * $per_page;
            $requests = repair_list_filtered_page($current_pid, [], $per_page, $offset);
        } else {
            $total_count = repair_count_filtered(null, $statuses);
            $total_pages = max(1, (int) ceil($total_count / $per_page));
            $page = min($page, $total_pages);
            $offset = ($page - 1) * $per_page;
            $requests = repair_list_filtered_page(null, $statuses, $per_page, $offset);
        }

        view_render('repairs/index', [
            'alert' => $alert,
            'values' => $values,
            'requests' => $requests,
            'current_pid' => $current_pid,
            'view_item' => $view_item,
            'view_attachments' => $view_attachments,
            'edit_item' => $edit_item,
            'edit_attachments' => $edit_attachments,
            'page' => $page,
            'total_pages' => $total_pages,
            'total_count' => $total_count,
            'mode' => $mode,
            'base_url' => (string) $config['base_url'],
            'page_title' => (string) $config['title'],
            'page_subtitle' => (string) $config['subtitle'],
            'form_title' => (string) $config['form_title'],
            'form_subtitle' => (string) $config['form_subtitle'],
            'list_title' => (string) $config['list_title'],
            'list_subtitle' => (string) $config['list_subtitle'],
            'empty_title' => (string) $config['empty_title'],
            'empty_message' => (string) $config['empty_message'],
            'show_form' => (bool) $config['show_form'],
            'show_requester_column' => (bool) $config['show_requester_column'],
            'transition_actions' => repairs_transition_actions($mode, $view_item),
        ]);
    }
}

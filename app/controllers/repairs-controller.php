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

if (!function_exists('repairs_index')) {
    function repairs_index(): void
    {
        $current_user = current_user() ?? [];
        $current_pid = (string) ($current_user['pID'] ?? '');
        $connection = db_connection();
        $has_table = db_table_exists($connection, 'dh_repair_requests');

        $is_facility = rbac_user_has_role($connection, $current_pid, ROLE_FACILITY)
            || rbac_user_has_role($connection, $current_pid, ROLE_ADMIN);
        if (!$is_facility && in_array((int) ($current_user['roleID'] ?? 0), [1, 5], true)) {
            $is_facility = true;
        }

        $alert = null;
        $values = [
            'subject' => '',
            'location' => '',
            'detail' => '',
        ];

        $view_id = (int) ($_GET['view_id'] ?? 0);
        $edit_id = (int) ($_GET['edit_id'] ?? 0);
        if ($edit_id > 0) {
            $view_id = 0;
        }
        $view_item = null;
        $view_attachments = [];
        $edit_item = null;
        $edit_attachments = [];

        $can_access_repair = static function (?array $repair, string $pid, bool $is_facility): bool {
            if (!$repair) {
                return false;
            }
            if ($is_facility) {
                return true;
            }
            return (string) ($repair['requesterPID'] ?? '') === $pid;
        };

        if ($view_id > 0 && $has_table) {
            $view_item = repair_get($view_id);
            if (!$can_access_repair($view_item, $current_pid, $is_facility)) {
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
            if (!$can_access_repair($edit_item, $current_pid, $is_facility)) {
                $alert = [
                    'type' => 'danger',
                    'title' => 'ไม่มีสิทธิ์แก้ไข',
                    'message' => 'คุณไม่มีสิทธิ์แก้ไขรายการนี้',
                ];
                $edit_item = null;
            } elseif (($edit_item['status'] ?? '') !== 'PENDING') {
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
                    'detail' => (string) ($edit_item['detail'] ?? ''),
                ];
                $edit_attachments = repair_get_attachments($edit_id);
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $values['subject'] = trim((string) ($_POST['subject'] ?? ''));
            $values['location'] = trim((string) ($_POST['location'] ?? ''));
            $values['detail'] = trim((string) ($_POST['detail'] ?? ''));
            $action = (string) ($_POST['action'] ?? 'create');
            $repair_id = (int) ($_POST['repair_id'] ?? 0);

            if (!csrf_validate($_POST['csrf_token'] ?? null)) {
                $alert = [
                    'type' => 'danger',
                    'title' => 'ไม่สามารถยืนยันความปลอดภัย',
                    'message' => 'กรุณาลองใหม่อีกครั้ง',
                ];
            } elseif (!$has_table) {
                $alert = system_not_ready_alert('ยังไม่พบตาราง dh_repair_requests กรุณารัน migrations/005_create_repairs_memos.sql');
            } elseif ($action === 'delete') {
                $target = $repair_id > 0 ? repair_get($repair_id) : null;
                if (!$can_access_repair($target, $current_pid, $is_facility)) {
                    $alert = [
                        'type' => 'danger',
                        'title' => 'ไม่มีสิทธิ์ลบ',
                        'message' => 'คุณไม่มีสิทธิ์ลบรายการนี้',
                    ];
                } elseif (($target['status'] ?? '') !== 'PENDING') {
                    $alert = [
                        'type' => 'warning',
                        'title' => 'ไม่สามารถลบได้',
                        'message' => 'ลบได้เฉพาะรายการที่มีสถานะรอดำเนินการเท่านั้น',
                    ];
                } else {
                    repair_delete_record($repair_id);
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
                if (!$can_access_repair($target, $current_pid, $is_facility)) {
                    $alert = [
                        'type' => 'danger',
                        'title' => 'ไม่มีสิทธิ์แก้ไข',
                        'message' => 'คุณไม่มีสิทธิ์แก้ไขรายการนี้',
                    ];
                } elseif (($target['status'] ?? '') !== 'PENDING') {
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
                    ]);

                    try {
                        $existing_files = repair_get_attachments($repair_id);
                        $normalized = upload_normalize_files($_FILES['attachments'] ?? []);
                        $upload_count = 0;
                        foreach ($normalized as $file) {
                            if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                                $upload_count++;
                            }
                        }
                        if (count($existing_files) + $upload_count > 5) {
                            throw new RuntimeException('แนบไฟล์ได้สูงสุด 5 ไฟล์');
                        }
                        if (!empty($_FILES['attachments'])) {
                            upload_store_files($_FILES['attachments'], REPAIR_MODULE_NAME, REPAIR_ENTITY_NAME, (string) $repair_id, $current_pid, [
                                'max_files' => 5,
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
                    'status' => 'PENDING',
                ]);

                try {
                    if (!empty($_FILES['attachments'])) {
                        upload_store_files($_FILES['attachments'], REPAIR_MODULE_NAME, REPAIR_ENTITY_NAME, (string) $repair_id, $current_pid, [
                            'max_files' => 5,
                        ]);
                    }
                    $alert = [
                        'type' => 'success',
                        'title' => 'บันทึกแจ้งซ่อมแล้ว',
                        'message' => '',
                    ];
                    $values = ['subject' => '', 'location' => '', 'detail' => ''];
                } catch (RuntimeException $exception) {
                    $alert = [
                        'type' => 'danger',
                        'title' => 'แนบไฟล์ไม่สำเร็จ',
                        'message' => $exception->getMessage(),
                    ];
                }
            }
        }

        if (!$has_table && $alert === null) {
            $alert = system_not_ready_alert('ยังไม่พบตาราง dh_repair_requests กรุณารัน migrations/005_create_repairs_memos.sql');
        }

        $page = (int) ($_GET['page'] ?? 1);
        $page = $page > 0 ? $page : 1;
        $per_page = 10;
        $total_pages = 1;
        $total_count = 0;

        if (!$has_table) {
            $requests = [];
        } elseif ($is_facility) {
            $total_count = repair_count_all();
            $total_pages = max(1, (int) ceil($total_count / $per_page));
            if ($page > $total_pages) {
                $page = $total_pages;
            }
            $offset = ($page - 1) * $per_page;
            $requests = repair_list_all_page($per_page, $offset);
        } else {
            $total_count = repair_count_by_requester($current_pid);
            $total_pages = max(1, (int) ceil($total_count / $per_page));
            if ($page > $total_pages) {
                $page = $total_pages;
            }
            $offset = ($page - 1) * $per_page;
            $requests = repair_list_by_requester_page($current_pid, $per_page, $offset);
        }

        view_render('repairs/index', [
            'alert' => $alert,
            'values' => $values,
            'requests' => $requests,
            'is_facility' => $is_facility,
            'current_pid' => $current_pid,
            'view_item' => $view_item,
            'view_attachments' => $view_attachments,
            'edit_item' => $edit_item,
            'edit_attachments' => $edit_attachments,
            'page' => $page,
            'total_pages' => $total_pages,
            'total_count' => $total_count,
        ]);
    }
}

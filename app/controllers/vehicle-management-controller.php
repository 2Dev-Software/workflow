<?php

declare(strict_types=1);

require_once __DIR__ . '/../views/view.php';
require_once __DIR__ . '/../../src/Services/auth/auth-guard.php';
require_once __DIR__ . '/../../src/Services/teacher/teacher-profile.php';
require_once __DIR__ . '/../auth/csrf.php';
require_once __DIR__ . '/../rbac/current_user.php';
require_once __DIR__ . '/../rbac/roles.php';
require_once __DIR__ . '/../modules/vehicle/management.php';
require_once __DIR__ . '/../modules/audit/logger.php';
require_once __DIR__ . '/../modules/system/positions.php';
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../db/db.php';

if (!function_exists('vehicle_management_index')) {
    function vehicle_management_index(): void
    {
        global $teacher;

        $current_user = current_user() ?? [];
        $current_pid = (string) ($current_user['pID'] ?? '');
        $connection = db_connection();

        $is_vehicle_officer = rbac_user_has_any_role($connection, $current_pid, [ROLE_ADMIN, ROLE_VEHICLE]);

        if (!$is_vehicle_officer && in_array((int) ($current_user['roleID'] ?? 0), [1, 3], true)) {
            $is_vehicle_officer = true;
        }

        if (!$is_vehicle_officer) {
            if (function_exists('audit_log')) {
                audit_log('vehicle', 'ACCESS', 'DENY', null, null, 'vehicle_management_access_denied');
            }
            header('Location: dashboard.php', true, 302);
            exit();
        }

        $status_classes = [
            'พร้อมใช้งาน' => 'available',
            'อยู่ระหว่างใช้งาน' => 'paused',
            'ส่งซ่อม' => 'maintenance',
            'ไม่พร้อมใช้งาน' => 'unavailable',
        ];
        $status_options = array_keys($status_classes);

        $alert = $_SESSION['vehicle_management_alert'] ?? null;
        unset($_SESSION['vehicle_management_alert']);
        $open_modal = (string) ($_SESSION['vehicle_management_open_modal'] ?? '');
        unset($_SESSION['vehicle_management_open_modal']);
        $form_values = (array) ($_SESSION['vehicle_management_form'] ?? []);
        unset($_SESSION['vehicle_management_form']);
        $edit_values = (array) ($_SESSION['vehicle_management_edit'] ?? []);
        unset($_SESSION['vehicle_management_edit']);

        $has_table = db_table_exists($connection, 'dh_vehicles');

        if (!$has_table) {
            $alert = system_not_ready_alert('ยังไม่พบตารางยานพาหนะ กรุณาตรวจสอบ schema dh_vehicles');
        }

        $set_alert = static function (string $type, string $title, string $message = ''): void {
            $_SESSION['vehicle_management_alert'] = [
                'type' => $type,
                'title' => $title,
                'message' => $message,
            ];
        };

        $normalize_text = static function ($value, int $max_len): string {
            $value = trim((string) $value);

            if ($value === '') {
                return '';
            }

            if (function_exists('mb_substr')) {
                return mb_substr($value, 0, $max_len);
            }

            return substr($value, 0, $max_len);
        };

        $normalize_capacity = static function ($value): int {
            $capacity = (int) $value;

            if ($capacity <= 0) {
                return 4;
            }

            if ($capacity > 99) {
                return 99;
            }

            return $capacity;
        };

        $resolve_single_role_id = static function (string $role_key, int $fallback_id) use ($connection): int {
            $role_ids = rbac_resolve_role_ids($connection, $role_key);

            foreach ($role_ids as $role_id) {
                $role_id = (int) $role_id;

                if ($role_id > 0) {
                    return $role_id;
                }
            }

            return $fallback_id;
        };

        $admin_role_id = $resolve_single_role_id(ROLE_ADMIN, 1);
        $vehicle_role_id = $resolve_single_role_id(ROLE_VEHICLE, 3);
        $general_role_id = $resolve_single_role_id(ROLE_GENERAL, 6);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!csrf_validate($_POST['csrf_token'] ?? null)) {
                if (function_exists('audit_log')) {
                    audit_log('security', 'CSRF_FAIL', 'DENY', null, null, 'vehicle_management');
                }
                $set_alert('danger', 'ไม่สามารถยืนยันความปลอดภัย', 'กรุณาลองใหม่อีกครั้ง');
            } else {
                $member_action = trim((string) ($_POST['member_action'] ?? ''));

                if ($member_action !== '') {
                    $member_pid = trim((string) ($_POST['member_pid'] ?? ''));

                    if ($member_pid === '' || !preg_match('/^\\d{13}$/', $member_pid)) {
                        if (function_exists('audit_log')) {
                            audit_log('vehicle', 'OFFICER_MANAGE', 'FAIL', 'teacher', null, 'invalid_member_pid', [
                                'member_action' => $member_action,
                                'memberPID' => $member_pid,
                            ]);
                        }
                        $set_alert('danger', 'ข้อมูลไม่ถูกต้อง', 'ไม่พบรหัสบุคลากรที่ต้องการ');
                    } else {
                        try {
                            if ($member_action === 'add') {
                                $_SESSION['vehicle_management_open_modal'] = 'vehicleMemberModal';
                                $sql = 'UPDATE teacher SET roleID = ?
                                    WHERE pID = ? AND status = 1 AND (roleID IS NULL OR roleID = 0 OR roleID = ?)';
                                $stmt = mysqli_prepare($connection, $sql);

                                if ($stmt === false) {
                                    error_log('Database Error: ' . mysqli_error($connection));

                                    if (function_exists('audit_log')) {
                                        audit_log('vehicle', 'ASSIGN_OFFICER', 'FAIL', 'teacher', $member_pid, 'prepare_failed', [
                                            'roleID' => $vehicle_role_id,
                                        ]);
                                    }
                                    $set_alert('danger', 'ระบบขัดข้อง', 'ไม่สามารถเพิ่มสมาชิกได้ในขณะนี้');
                                } else {
                                    mysqli_stmt_bind_param($stmt, 'isi', $vehicle_role_id, $member_pid, $general_role_id);
                                    mysqli_stmt_execute($stmt);
                                    $affected = mysqli_stmt_affected_rows($stmt);
                                    mysqli_stmt_close($stmt);

                                    if ($affected > 0) {
                                        if (function_exists('audit_log')) {
                                            audit_log('vehicle', 'ASSIGN_OFFICER', 'SUCCESS', 'teacher', $member_pid, null, [
                                                'roleID' => $vehicle_role_id,
                                            ]);
                                        }
                                        $set_alert('success', 'เพิ่มสมาชิกสำเร็จ', 'อัปเดตสิทธิ์เป็นเจ้าหน้าที่ยานพาหนะแล้ว');
                                    } else {
                                        if (function_exists('audit_log')) {
                                            audit_log('vehicle', 'ASSIGN_OFFICER', 'FAIL', 'teacher', $member_pid, 'no_rows_affected', [
                                                'roleID' => $vehicle_role_id,
                                            ]);
                                        }
                                        $set_alert('warning', 'ไม่สามารถเพิ่มสมาชิก', 'บุคลากรนี้อาจถูกเพิ่มแล้วหรือไม่อยู่ในระบบ');
                                    }
                                }
                            } elseif ($member_action === 'remove') {
                                $sql = 'UPDATE teacher SET roleID = ?
                                    WHERE pID = ? AND status = 1 AND roleID = ?';
                                $stmt = mysqli_prepare($connection, $sql);

                                if ($stmt === false) {
                                    error_log('Database Error: ' . mysqli_error($connection));

                                    if (function_exists('audit_log')) {
                                        audit_log('vehicle', 'REMOVE_OFFICER', 'FAIL', 'teacher', $member_pid, 'prepare_failed', [
                                            'roleID' => $general_role_id,
                                        ]);
                                    }
                                    $set_alert('danger', 'ระบบขัดข้อง', 'ไม่สามารถลบสมาชิกได้ในขณะนี้');
                                } else {
                                    mysqli_stmt_bind_param($stmt, 'isi', $general_role_id, $member_pid, $vehicle_role_id);
                                    mysqli_stmt_execute($stmt);
                                    $affected = mysqli_stmt_affected_rows($stmt);
                                    mysqli_stmt_close($stmt);

                                    if ($affected > 0) {
                                        if (function_exists('audit_log')) {
                                            audit_log('vehicle', 'REMOVE_OFFICER', 'SUCCESS', 'teacher', $member_pid, null, [
                                                'roleID' => $general_role_id,
                                            ]);
                                        }
                                        $set_alert('success', 'ลบสมาชิกสำเร็จ', 'สิทธิ์ถูกปรับกลับเป็นผู้ใช้งานทั่วไปแล้ว');
                                    } else {
                                        if (function_exists('audit_log')) {
                                            audit_log('vehicle', 'REMOVE_OFFICER', 'FAIL', 'teacher', $member_pid, 'no_rows_affected', [
                                                'roleID' => $general_role_id,
                                            ]);
                                        }
                                        $set_alert('warning', 'ไม่สามารถลบสมาชิก', 'ไม่พบสมาชิกในบทบาทเจ้าหน้าที่ยานพาหนะ');
                                    }
                                }
                            } else {
                                if (function_exists('audit_log')) {
                                    audit_log('vehicle', 'OFFICER_MANAGE', 'FAIL', 'teacher', $member_pid, 'invalid_member_action', [
                                        'member_action' => $member_action,
                                    ]);
                                }
                                $set_alert('danger', 'ข้อมูลไม่ถูกต้อง', 'ไม่พบคำสั่งที่ต้องการ');
                            }
                        } catch (Throwable $e) {
                            error_log('Vehicle member management error: ' . $e->getMessage());

                            if (function_exists('audit_log')) {
                                audit_log('vehicle', 'OFFICER_MANAGE', 'FAIL', 'teacher', $member_pid, $e->getMessage());
                            }
                            $set_alert('danger', 'ระบบขัดข้อง', 'ไม่สามารถดำเนินการได้ในขณะนี้');
                        }
                    }

                    header('Location: vehicle-management.php', true, 303);
                    exit();
                }

                if (!$has_table) {
                    $set_alert('warning', 'ระบบยังไม่พร้อมใช้งาน', 'ยังไม่พบตารางยานพาหนะ กรุณาตรวจสอบ schema dh_vehicles');
                } else {
                    $action = (string) ($_POST['vehicle_action'] ?? '');

                    if (!in_array($action, ['add', 'edit', 'delete'], true)) {
                        if (function_exists('audit_log')) {
                            audit_log('vehicle', 'MANAGE', 'FAIL', 'dh_vehicles', null, 'invalid_vehicle_action', [
                                'vehicle_action' => $action,
                            ]);
                        }
                        $set_alert('warning', 'คำสั่งไม่ถูกต้อง', 'กรุณาลองใหม่อีกครั้ง');
                    } else {
                        try {
                            if ($action === 'add' || $action === 'edit') {
                                $vehicle_type = $normalize_text($_POST['vehicle_type'] ?? '', 50);
                                $vehicle_plate = $normalize_text($_POST['vehicle_plate'] ?? '', 50);
                                $vehicle_brand = $normalize_text($_POST['vehicle_brand'] ?? '', 100);
                                $vehicle_model = $normalize_text($_POST['vehicle_model'] ?? '', 100);
                                $vehicle_color = $normalize_text($_POST['vehicle_color'] ?? '', 50);
                                $vehicle_capacity = $normalize_capacity($_POST['vehicle_capacity'] ?? 0);
                                $vehicle_status = $normalize_text($_POST['vehicle_status'] ?? '', 50);

                                if (!in_array($vehicle_status, $status_options, true)) {
                                    $vehicle_status = $status_options[0] ?? 'พร้อมใช้งาน';
                                }

                                $payload = [
                                    'vehicleType' => $vehicle_type,
                                    'vehicleBrand' => $vehicle_brand,
                                    'vehicleModel' => $vehicle_model,
                                    'vehiclePlate' => $vehicle_plate,
                                    'vehicleColor' => $vehicle_color,
                                    'vehicleCapacity' => $vehicle_capacity,
                                    'vehicleStatus' => $vehicle_status,
                                ];

                                if ($vehicle_type === '' || $vehicle_plate === '') {
                                    if (function_exists('audit_log')) {
                                        $audit_action = $action === 'add' ? 'CREATE' : 'UPDATE';
                                        $entity_id = $action === 'edit' ? (int) ($_POST['vehicle_id'] ?? 0) : null;
                                        audit_log('vehicle', $audit_action, 'FAIL', 'dh_vehicles', $entity_id, 'validation_failed', [
                                            'vehicleType' => $vehicle_type,
                                            'vehiclePlate' => $vehicle_plate,
                                        ]);
                                    }
                                    $set_alert('danger', 'ข้อมูลไม่ครบถ้วน', 'กรุณาระบุประเภทรถและทะเบียนรถ');
                                    $_SESSION['vehicle_management_open_modal'] = $action === 'add' ? 'vehicleAddModal' : 'vehicleEditModal';

                                    if ($action === 'add') {
                                        $_SESSION['vehicle_management_form'] = $payload;
                                    } else {
                                        $payload['vehicleID'] = (int) ($_POST['vehicle_id'] ?? 0);
                                        $_SESSION['vehicle_management_edit'] = $payload;
                                    }
                                    header('Location: vehicle-management.php', true, 303);
                                    exit();
                                }

                                if ($action === 'add') {
                                    $vehicle_id = vehicle_management_add($payload);
                                    audit_log('vehicle', 'CREATE', 'SUCCESS', 'dh_vehicles', $vehicle_id, null, $payload);
                                    $set_alert('success', 'บันทึกสำเร็จ', 'เพิ่มยานพาหนะเรียบร้อยแล้ว');
                                } else {
                                    $vehicle_id = (int) ($_POST['vehicle_id'] ?? 0);

                                    if ($vehicle_id <= 0) {
                                        if (function_exists('audit_log')) {
                                            audit_log('vehicle', 'UPDATE', 'FAIL', 'dh_vehicles', null, 'invalid_vehicle_id');
                                        }
                                        $set_alert('warning', 'ไม่พบรายการ', 'กรุณาเลือกยานพาหนะที่ต้องการแก้ไข');
                                    } else {
                                        vehicle_management_update($vehicle_id, $payload);
                                        audit_log('vehicle', 'UPDATE', 'SUCCESS', 'dh_vehicles', $vehicle_id, null, $payload);
                                        $set_alert('success', 'บันทึกสำเร็จ', 'อัปเดตข้อมูลยานพาหนะเรียบร้อยแล้ว');
                                    }
                                }
                            }

                            if ($action === 'delete') {
                                $vehicle_id = (int) ($_POST['vehicle_id'] ?? 0);

                                if ($vehicle_id <= 0) {
                                    if (function_exists('audit_log')) {
                                        audit_log('vehicle', 'DELETE', 'FAIL', 'dh_vehicles', null, 'invalid_vehicle_id');
                                    }
                                    $set_alert('warning', 'ไม่พบรายการ', 'กรุณาเลือกยานพาหนะที่ต้องการลบ');
                                } else {
                                    vehicle_management_delete($vehicle_id);
                                    audit_log('vehicle', 'DELETE', 'SUCCESS', 'dh_vehicles', $vehicle_id);
                                    $set_alert('success', 'ลบสำเร็จ', 'ลบยานพาหนะเรียบร้อยแล้ว');
                                }
                            }
                        } catch (Throwable $e) {
                            error_log('Vehicle management error: ' . $e->getMessage());

                            if (function_exists('audit_log')) {
                                audit_log('vehicle', 'MANAGE', 'FAIL', 'dh_vehicles', null, $e->getMessage());
                            }
                            $set_alert('danger', 'ระบบขัดข้อง', 'ไม่สามารถบันทึกข้อมูลได้ในขณะนี้');
                        }
                    }
                }
            }

            header('Location: vehicle-management.php', true, 303);
            exit();
        }

        $vehicles = $has_table ? vehicle_management_list() : [];
        $vehicle_staff_members = [];
        $vehicle_candidate_members = [];
        $vehicle_staff_count = 0;
        $vehicle_candidate_count = 0;

        $map_member = static function (array $row): array {
            $name = trim((string) ($row['fName'] ?? ''));

            if ($name === '') {
                $name = 'ไม่ระบุชื่อ';
            }

            return [
                'pID' => (string) ($row['pID'] ?? ''),
                'name' => $name,
                'position_name' => trim((string) ($row['position_name'] ?? '')),
                'role_name' => trim((string) ($row['role_name'] ?? '')),
                'department_name' => trim((string) ($row['department_name'] ?? '')),
                'telephone' => trim((string) ($row['telephone'] ?? '')),
            ];
        };

        try {
            $position = system_position_join($connection, 't', 'p');

            $staff_sql = 'SELECT t.pID, t.fName, t.positionID, t.roleID, t.telephone,
                ' . $position['name'] . ' AS position_name,
                r.roleName AS role_name,
                d.dName AS department_name
                FROM teacher AS t
                ' . $position['join'] . '
                LEFT JOIN dh_roles AS r ON t.roleID = r.roleID
                LEFT JOIN department AS d ON t.dID = d.dID
                WHERE t.status = 1 AND t.roleID = ?
                ORDER BY t.fName';

            $stmt = mysqli_prepare($connection, $staff_sql);

            if ($stmt === false) {
                throw new RuntimeException('Database prepare failed: ' . mysqli_error($connection));
            }
            mysqli_stmt_bind_param($stmt, 'i', $vehicle_role_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($result !== false) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $vehicle_staff_members[] = $map_member($row);
                }
            }
            mysqli_stmt_close($stmt);

            $candidate_sql = 'SELECT t.pID, t.fName, t.positionID, t.roleID, t.telephone,
                ' . $position['name'] . ' AS position_name,
                r.roleName AS role_name,
                d.dName AS department_name
                FROM teacher AS t
                ' . $position['join'] . '
                LEFT JOIN dh_roles AS r ON t.roleID = r.roleID
                LEFT JOIN department AS d ON t.dID = d.dID
                WHERE t.status = 1 AND (t.roleID IS NULL OR t.roleID = 0 OR t.roleID = ?)
                ORDER BY t.fName';

            $stmt = mysqli_prepare($connection, $candidate_sql);

            if ($stmt === false) {
                throw new RuntimeException('Database prepare failed: ' . mysqli_error($connection));
            }
            mysqli_stmt_bind_param($stmt, 'i', $general_role_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($result !== false) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $vehicle_candidate_members[] = $map_member($row);
                }
            }
            mysqli_stmt_close($stmt);
        } catch (Throwable $e) {
            error_log('Vehicle member data error: ' . $e->getMessage());

            if (empty($alert)) {
                $alert = [
                    'type' => 'danger',
                    'title' => 'ระบบขัดข้อง',
                    'message' => 'ไม่สามารถโหลดข้อมูลทีมผู้ดูแลยานพาหนะได้ในขณะนี้',
                ];
            }
        }

        $vehicle_staff_count = count($vehicle_staff_members);
        $vehicle_candidate_count = count($vehicle_candidate_members);

        view_render('vehicle/management', [
            'alert' => $alert,
            'vehicles' => $vehicles,
            'status_classes' => $status_classes,
            'status_options' => $status_options,
            'open_modal' => $open_modal,
            'form_values' => $form_values,
            'edit_values' => $edit_values,
            'vehicle_staff_members' => $vehicle_staff_members,
            'vehicle_candidate_members' => $vehicle_candidate_members,
            'vehicle_staff_count' => $vehicle_staff_count,
            'vehicle_candidate_count' => $vehicle_candidate_count,
            'teacher' => $teacher,
        ]);
    }
}

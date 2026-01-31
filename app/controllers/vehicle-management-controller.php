<?php
declare(strict_types=1);

require_once __DIR__ . '/../views/view.php';
require_once __DIR__ . '/../../src/Services/auth/auth-guard.php';
require_once __DIR__ . '/../auth/csrf.php';
require_once __DIR__ . '/../rbac/current_user.php';
require_once __DIR__ . '/../rbac/roles.php';
require_once __DIR__ . '/../modules/vehicle/management.php';
require_once __DIR__ . '/../modules/audit/logger.php';
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../db/db.php';

if (!function_exists('vehicle_management_index')) {
    function vehicle_management_index(): void
    {
        $current_user = current_user() ?? [];
        $current_pid = (string) ($current_user['pID'] ?? '');
        $connection = db_connection();

        $is_vehicle_officer = rbac_user_has_any_role($connection, $current_pid, [ROLE_ADMIN, ROLE_VEHICLE]);
        if (!$is_vehicle_officer && in_array((int) ($current_user['roleID'] ?? 0), [1, 3], true)) {
            $is_vehicle_officer = true;
        }

        if (!$is_vehicle_officer) {
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

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $has_table) {
            if (!csrf_validate($_POST['csrf_token'] ?? null)) {
                $set_alert('danger', 'ไม่สามารถยืนยันความปลอดภัย', 'กรุณาลองใหม่อีกครั้ง');
            } else {
                $action = (string) ($_POST['vehicle_action'] ?? '');
                if (!in_array($action, ['add', 'edit', 'delete'], true)) {
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
                                $set_alert('warning', 'ไม่พบรายการ', 'กรุณาเลือกยานพาหนะที่ต้องการลบ');
                            } else {
                                vehicle_management_delete($vehicle_id);
                                audit_log('vehicle', 'DELETE', 'SUCCESS', 'dh_vehicles', $vehicle_id);
                                $set_alert('success', 'ลบสำเร็จ', 'ลบยานพาหนะเรียบร้อยแล้ว');
                            }
                        }
                    } catch (Throwable $e) {
                        error_log('Vehicle management error: ' . $e->getMessage());
                        $set_alert('danger', 'ระบบขัดข้อง', 'ไม่สามารถบันทึกข้อมูลได้ในขณะนี้');
                    }
                }
            }

            header('Location: vehicle-management.php', true, 303);
            exit();
        }

        $vehicles = $has_table ? vehicle_management_list() : [];

        view_render('vehicle/management', [
            'alert' => $alert,
            'vehicles' => $vehicles,
            'status_classes' => $status_classes,
            'status_options' => $status_options,
            'open_modal' => $open_modal,
            'form_values' => $form_values,
            'edit_values' => $edit_values,
        ]);
    }
}

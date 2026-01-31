<?php
declare(strict_types=1);

require_once __DIR__ . '/../views/view.php';
require_once __DIR__ . '/../../src/Services/auth/auth-guard.php';
require_once __DIR__ . '/../../src/Services/security/security-service.php';
require_once __DIR__ . '/../auth/csrf.php';
require_once __DIR__ . '/../rbac/current_user.php';
require_once __DIR__ . '/../rbac/roles.php';
require_once __DIR__ . '/../modules/circulars/service.php';
require_once __DIR__ . '/../modules/users/lists.php';
require_once __DIR__ . '/../modules/system/system.php';

if (!function_exists('circular_compose_index')) {
    function circular_compose_index(): void
    {
        $current_user = current_user() ?? [];
        $current_pid = (string) ($current_user['pID'] ?? '');

        $connection = db_connection();
        $is_registry = rbac_user_has_role($connection, $current_pid, ROLE_REGISTRY);
        if (!$is_registry && (int) ($current_user['roleID'] ?? 0) === 2) {
            $is_registry = true;
        }

        $factions = user_list_factions();
        $roles = user_list_roles();
        $teachers = user_list_teachers();

        $values = [
            'circular_type' => 'INTERNAL',
            'subject' => '',
            'detail' => '',
            'linkURL' => '',
            'fromFID' => '',
            'faction_ids' => [],
            'role_ids' => [],
            'person_ids' => [],
            'extPriority' => 'ปกติ',
            'extBookNo' => '',
            'extIssuedDate' => '',
            'extFromText' => '',
            'extGroupFID' => '',
            'send_now' => '0',
        ];

        $alert = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $values['circular_type'] = (string) ($_POST['circular_type'] ?? 'INTERNAL');
            $values['subject'] = trim((string) ($_POST['subject'] ?? ''));
            $values['detail'] = trim((string) ($_POST['detail'] ?? ''));
            $values['linkURL'] = trim((string) ($_POST['linkURL'] ?? ''));
            $values['fromFID'] = (string) ($_POST['fromFID'] ?? '');
            $values['faction_ids'] = (array) ($_POST['faction_ids'] ?? []);
            $values['role_ids'] = (array) ($_POST['role_ids'] ?? []);
            $values['person_ids'] = (array) ($_POST['person_ids'] ?? []);
            $values['extPriority'] = (string) ($_POST['extPriority'] ?? 'ปกติ');
            $values['extBookNo'] = trim((string) ($_POST['extBookNo'] ?? ''));
            $values['extIssuedDate'] = trim((string) ($_POST['extIssuedDate'] ?? ''));
            $values['extFromText'] = trim((string) ($_POST['extFromText'] ?? ''));
            $values['extGroupFID'] = (string) ($_POST['extGroupFID'] ?? '');
            $values['send_now'] = isset($_POST['send_now']) ? '1' : '0';

            if (!csrf_validate($_POST['csrf_token'] ?? null)) {
                $alert = ['type' => 'danger', 'title' => 'ไม่สามารถยืนยันความปลอดภัย', 'message' => 'กรุณาลองใหม่อีกครั้ง'];
            } elseif ($values['subject'] === '') {
                $alert = ['type' => 'danger', 'title' => 'ข้อมูลไม่ครบถ้วน', 'message' => 'กรุณากรอกหัวข้อเรื่อง'];
            } else {
                try {
                    $dh_year = system_get_dh_year();
                    $files = $_FILES['attachments'] ?? [];

                    if ($values['circular_type'] === 'EXTERNAL') {
                        if (!$is_registry) {
                            throw new RuntimeException('ไม่มีสิทธิ์ส่งหนังสือภายนอก');
                        }

                        $extIssuedDate = $values['extIssuedDate'];
                        if ($extIssuedDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $extIssuedDate)) {
                            $parts = explode('-', $extIssuedDate);
                            $year = (int) $parts[0];
                            if ($year > 2500) {
                                $year -= 543;
                            }
                            $extIssuedDate = $year . '-' . $parts[1] . '-' . $parts[2];
                        }

                        $send_now = $values['send_now'] === '1';

                        $circularID = circular_create_external([
                            'dh_year' => $dh_year,
                            'circularType' => CIRCULAR_TYPE_EXTERNAL,
                            'subject' => $values['subject'],
                            'detail' => $values['detail'],
                            'linkURL' => $values['linkURL'] !== '' ? $values['linkURL'] : null,
                            'fromFID' => $values['fromFID'] !== '' ? (int) $values['fromFID'] : null,
                            'extPriority' => $values['extPriority'],
                            'extBookNo' => $values['extBookNo'] !== '' ? $values['extBookNo'] : null,
                            'extIssuedDate' => $extIssuedDate !== '' ? $extIssuedDate : null,
                            'extFromText' => $values['extFromText'] !== '' ? $values['extFromText'] : null,
                            'extGroupFID' => $values['extGroupFID'] !== '' ? (int) $values['extGroupFID'] : null,
                            'status' => $send_now ? CIRCULAR_STATUS_SENT : CIRCULAR_STATUS_DRAFT,
                            'createdByPID' => $current_pid,
                        ], $current_pid, $send_now, $files);

                        $alert = ['type' => 'success', 'title' => 'บันทึกหนังสือภายนอกแล้ว', 'message' => 'เลขที่รายการ #' . $circularID];
                    } else {
                        $targets = [];
                        foreach ((array) $values['faction_ids'] as $fid) {
                            $targets[] = ['targetType' => 'UNIT', 'fID' => (int) $fid];
                        }
                        foreach ((array) $values['role_ids'] as $rid) {
                            $targets[] = ['targetType' => 'ROLE', 'roleID' => (int) $rid];
                        }
                        foreach ((array) $values['person_ids'] as $pid) {
                            $targets[] = ['targetType' => 'PERSON', 'pID' => (string) $pid];
                        }

                        $pids = circular_resolve_person_ids((array) $values['faction_ids'], (array) $values['role_ids'], (array) $values['person_ids']);
                        if (empty($pids)) {
                            throw new RuntimeException('กรุณาเลือกผู้รับอย่างน้อย 1 คน');
                        }

                        $circularID = circular_create_internal([
                            'dh_year' => $dh_year,
                            'circularType' => CIRCULAR_TYPE_INTERNAL,
                            'subject' => $values['subject'],
                            'detail' => $values['detail'],
                            'linkURL' => $values['linkURL'] !== '' ? $values['linkURL'] : null,
                            'fromFID' => $values['fromFID'] !== '' ? (int) $values['fromFID'] : null,
                            'status' => CIRCULAR_STATUS_SENT,
                            'createdByPID' => $current_pid,
                        ], ['pids' => $pids, 'targets' => $targets], $files);

                        $alert = ['type' => 'success', 'title' => 'ส่งหนังสือเวียนแล้ว', 'message' => 'เลขที่รายการ #' . $circularID];
                    }
                } catch (Throwable $e) {
                    $alert = ['type' => 'danger', 'title' => 'เกิดข้อผิดพลาด', 'message' => $e->getMessage()];
                }
            }
        }

        view_render('circular/compose', [
            'alert' => $alert,
            'values' => $values,
            'factions' => $factions,
            'roles' => $roles,
            'teachers' => $teachers,
            'is_registry' => $is_registry,
        ]);
    }
}

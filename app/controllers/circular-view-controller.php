<?php
declare(strict_types=1);

require_once __DIR__ . '/../views/view.php';
require_once __DIR__ . '/../../src/Services/auth/auth-guard.php';
require_once __DIR__ . '/../../src/Services/security/security-service.php';
require_once __DIR__ . '/../auth/csrf.php';
require_once __DIR__ . '/../rbac/current_user.php';
require_once __DIR__ . '/../modules/circulars/service.php';
require_once __DIR__ . '/../modules/circulars/repository.php';
require_once __DIR__ . '/../modules/users/lists.php';
require_once __DIR__ . '/../modules/system/system.php';
require_once __DIR__ . '/../rbac/roles.php';

if (!function_exists('circular_view_index')) {
    function circular_view_index(): void
    {
        $current_user = current_user() ?? [];
        $current_pid = (string) ($current_user['pID'] ?? '');
        $position_ids = current_user_position_ids();
        $connection = db_connection();
        $is_registry = rbac_user_has_role($connection, $current_pid, ROLE_REGISTRY);
        if (!$is_registry && (int) ($current_user['roleID'] ?? 0) === 2) {
            $is_registry = true;
        }
        $is_deputy = in_array(2, $position_ids, true);
        $director_pid = system_get_current_director_pid();
        $is_director = $director_pid !== null && $director_pid === $current_pid;

        $inbox_id = isset($_GET['inbox_id']) ? (int) $_GET['inbox_id'] : 0;
        if ($inbox_id <= 0) {
            redirect_to('circular-notice.php');
        }

        $item = circular_get_inbox_item($inbox_id, $current_pid);
        if (!$item) {
            redirect_to('circular-notice.php');
        }

        if ((int) ($item['isRead'] ?? 0) === 0) {
            circular_mark_read($inbox_id, $current_pid);
        }

        $alert = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!csrf_validate($_POST['csrf_token'] ?? null)) {
                $alert = [
                    'type' => 'danger',
                    'title' => 'ไม่สามารถยืนยันความปลอดภัย',
                    'message' => 'กรุณาลองใหม่อีกครั้ง',
                ];
            } else {
                $action = (string) ($_POST['action'] ?? '');
                try {
                    if ($action === 'archive') {
                        circular_archive_inbox($inbox_id, $current_pid);
                        $alert = ['type' => 'success', 'title' => 'จัดเก็บเรียบร้อย', 'message' => ''];
                    }

                    if ($action === 'forward') {
                        $faction_ids = $_POST['faction_ids'] ?? [];
                        $role_ids = $_POST['role_ids'] ?? [];
                        $person_ids = $_POST['person_ids'] ?? [];

                        $targets = [];
                        foreach ((array) $faction_ids as $fid) {
                            $targets[] = ['targetType' => 'UNIT', 'fID' => (int) $fid];
                        }
                        foreach ((array) $role_ids as $rid) {
                            $targets[] = ['targetType' => 'ROLE', 'roleID' => (int) $rid];
                        }
                        foreach ((array) $person_ids as $pid) {
                            $targets[] = ['targetType' => 'PERSON', 'pID' => (string) $pid];
                        }

                        $pids = circular_resolve_person_ids((array) $faction_ids, (array) $role_ids, (array) $person_ids);
                        circular_forward((int) $item['circularID'], $current_pid, ['pids' => $pids, 'targets' => $targets]);
                        $alert = ['type' => 'success', 'title' => 'ส่งต่อเรียบร้อย', 'message' => ''];
                    }

                    if ($action === 'director_review' && $is_director) {
                        $comment = trim((string) ($_POST['comment'] ?? ''));
                        $new_fid = isset($_POST['extGroupFID']) ? (int) $_POST['extGroupFID'] : null;
                        circular_director_review((int) $item['circularID'], $current_pid, $comment !== '' ? $comment : null, $new_fid && $new_fid > 0 ? $new_fid : null);
                        $alert = ['type' => 'success', 'title' => 'ส่งกลับสารบรรณแล้ว', 'message' => ''];
                    }

                    if ($action === 'clerk_forward' && $is_registry) {
                        $fID = isset($_POST['extGroupFID']) ? (int) $_POST['extGroupFID'] : null;
                        circular_registry_forward_to_deputy((int) $item['circularID'], $current_pid, $fID && $fID > 0 ? $fID : null);
                        $alert = ['type' => 'success', 'title' => 'ส่งต่อรองผู้อำนวยการแล้ว', 'message' => ''];
                    }

                    if ($action === 'deputy_distribute' && $is_deputy) {
                        $faction_ids = $_POST['faction_ids'] ?? [];
                        $role_ids = $_POST['role_ids'] ?? [];
                        $person_ids = $_POST['person_ids'] ?? [];
                        $comment = trim((string) ($_POST['comment'] ?? ''));
                        $targets = [];
                        foreach ((array) $faction_ids as $fid) {
                            $targets[] = ['targetType' => 'UNIT', 'fID' => (int) $fid];
                        }
                        foreach ((array) $role_ids as $rid) {
                            $targets[] = ['targetType' => 'ROLE', 'roleID' => (int) $rid];
                        }
                        foreach ((array) $person_ids as $pid) {
                            $targets[] = ['targetType' => 'PERSON', 'pID' => (string) $pid];
                        }
                        $pids = circular_resolve_person_ids((array) $faction_ids, (array) $role_ids, (array) $person_ids);
                        circular_deputy_distribute((int) $item['circularID'], $current_pid, ['pids' => $pids, 'targets' => $targets], $comment !== '' ? $comment : null);
                        $alert = ['type' => 'success', 'title' => 'กระจายหนังสือเรียบร้อย', 'message' => ''];
                    }

                    if ($action === 'announce') {
                        circular_set_announcement((int) $item['circularID'], $current_pid);
                        $alert = ['type' => 'success', 'title' => 'ตั้งเป็นข่าวประชาสัมพันธ์แล้ว', 'message' => ''];
                    }
                } catch (Throwable $e) {
                    $message = trim((string) $e->getMessage());
                    if ($message === '') {
                        $message = 'โปรดลองอีกครั้ง';
                    }
                    $alert = ['type' => 'danger', 'title' => 'เกิดข้อผิดพลาด', 'message' => $message];
                }
            }
        }

        $attachments = circular_get_attachments((int) $item['circularID']);
        $factions = user_list_factions();
        $roles = user_list_roles();
        $teachers = user_list_teachers();

        view_render('circular/view', [
            'alert' => $alert,
            'item' => $item,
            'attachments' => $attachments,
            'factions' => $factions,
            'roles' => $roles,
            'teachers' => $teachers,
            'is_registry' => $is_registry,
            'is_deputy' => $is_deputy,
            'is_director' => $is_director,
            'position_ids' => $position_ids,
        ]);
    }
}

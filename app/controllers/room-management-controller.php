<?php
declare(strict_types=1);

require_once __DIR__ . '/../views/view.php';
require_once __DIR__ . '/../../src/Services/auth/auth-guard.php';
require_once __DIR__ . '/../../src/Services/security/security-service.php';
require_once __DIR__ . '/../auth/csrf.php';
require_once __DIR__ . '/../rbac/current_user.php';
require_once __DIR__ . '/../modules/audit/logger.php';

if (!function_exists('room_management_index')) {
    function room_management_index(): void
    {
        $current_user = current_user() ?? [];
        $current_role_id = (int) ($current_user['roleID'] ?? 0);
        if (!in_array($current_role_id, [1, 5], true)) {
            if (function_exists('audit_log')) {
                audit_log('room', 'MANAGEMENT_ACCESS', 'DENY', null, null, 'not_authorized_role', [
                    'roleID' => $current_role_id,
                ]);
            }
            header('Location: dashboard.php', true, 302);
            exit();
        }

        require __DIR__ . '/../../src/Services/room/room-management-member-actions.php';
        require __DIR__ . '/../../src/Services/room/room-management-room-actions.php';
        require __DIR__ . '/../../src/Services/room/room-management-data.php';

        $room_status_classes = [
            'พร้อมใช้งาน' => 'available',
            'ระงับชั่วคราว' => 'paused',
            'กำลังซ่อม' => 'maintenance',
            'ไม่พร้อมใช้งาน' => 'unavailable',
        ];
        $room_status_options = array_keys($room_status_classes);
        $room_management_rooms = $room_management_rooms ?? [];

        $room_staff_members = $room_staff_members ?? [];
        $room_candidate_members = $room_candidate_members ?? [];
        $room_staff_count = $room_staff_count ?? count($room_staff_members);
        $room_candidate_count = $room_candidate_count ?? count($room_candidate_members);

        $room_management_alert = $_SESSION['room_management_alert'] ?? null;
        unset($_SESSION['room_management_alert']);
        $room_management_open_modal = (string) ($_SESSION['room_management_open_modal'] ?? '');
        unset($_SESSION['room_management_open_modal']);

        view_render('room/management', [
            'room_status_classes' => $room_status_classes,
            'room_status_options' => $room_status_options,
            'room_management_rooms' => $room_management_rooms,
            'room_staff_members' => $room_staff_members,
            'room_candidate_members' => $room_candidate_members,
            'room_staff_count' => $room_staff_count,
            'room_candidate_count' => $room_candidate_count,
            'room_management_alert' => $room_management_alert,
            'room_management_open_modal' => $room_management_open_modal,
        ]);
    }
}

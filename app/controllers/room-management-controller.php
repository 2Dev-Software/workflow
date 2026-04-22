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
        $current_pid = trim((string) ($current_user['pID'] ?? ($_SESSION['pID'] ?? '')));
        $current_role_id = (int) ($current_user['roleID'] ?? 0);
        $connection = db_connection();
        $can_manage_rooms = in_array($current_role_id, [1], true)
            || ($current_pid !== '' && rbac_user_has_any_role($connection, $current_pid, [ROLE_ADMIN]));

        if (!$can_manage_rooms) {
            if (function_exists('audit_log')) {
                audit_log('room', 'MANAGEMENT_ACCESS', 'DENY', null, null, 'not_authorized_role', [
                    'pID' => $current_pid !== '' ? $current_pid : null,
                    'roleID' => $current_role_id,
                ]);
            }
            header('Location: dashboard.php', true, 302);
            exit();
        }

        $room_filter_query = trim((string) ($_GET['q'] ?? ''));
        $room_filter_status = strtolower(trim((string) ($_GET['status'] ?? 'all')));
        $allowed_room_filter_status = ['all', 'available', 'paused', 'maintenance', 'unavailable'];

        if (!in_array($room_filter_status, $allowed_room_filter_status, true)) {
            $room_filter_status = 'all';
        }

        $room_filter_room_raw = trim((string) ($_GET['room'] ?? 'all'));
        $room_filter_room = 'all';
        $room_filter_room_id = 0;

        if ($room_filter_room_raw !== '' && strtolower($room_filter_room_raw) !== 'all') {
            if (ctype_digit($room_filter_room_raw) && (int) $room_filter_room_raw > 0) {
                $room_filter_room = $room_filter_room_raw;
                $room_filter_room_id = (int) $room_filter_room_raw;
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $has_q = array_key_exists('q', $_GET);
            $has_status = array_key_exists('status', $_GET);
            $has_room = array_key_exists('room', $_GET);

            if (!$has_q || !$has_status || !$has_room) {
                header(
                    'Location: room-management.php?' . http_build_query([
                        'q' => $room_filter_query,
                        'status' => $room_filter_status,
                        'room' => $room_filter_room,
                    ]),
                    true,
                    302
                );
                exit();
            }
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

        $contains_filter_text = static function (string $haystack, string $needle): bool {
            if ($needle === '') {
                return true;
            }

            if (function_exists('mb_stripos')) {
                return mb_stripos($haystack, $needle, 0, 'UTF-8') !== false;
            }

            return stripos($haystack, $needle) !== false;
        };

        $room_management_rooms = array_values(array_filter(
            $room_management_rooms,
            static function (array $room) use (
                $room_filter_query,
                $room_filter_status,
                $room_filter_room_id,
                $room_status_classes,
                $contains_filter_text
            ): bool {
                $room_id = (int) ($room['roomID'] ?? 0);
                $room_name = trim((string) ($room['roomName'] ?? ''));
                $room_note = trim((string) ($room['roomNote'] ?? ''));
                $room_status_label = trim((string) ($room['roomStatus'] ?? ''));
                $room_status_key = $room_status_classes[$room_status_label] ?? 'paused';

                if ($room_filter_status !== 'all' && $room_status_key !== $room_filter_status) {
                    return false;
                }

                if ($room_filter_room_id > 0 && $room_id !== $room_filter_room_id) {
                    return false;
                }

                if ($room_filter_query !== '') {
                    $haystack = trim($room_name . ' ' . $room_note . ' ' . $room_status_label);

                    if (!$contains_filter_text($haystack, $room_filter_query)) {
                        return false;
                    }
                }

                return true;
            }
        ));

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
            'room_filter_query' => $room_filter_query,
            'room_filter_status' => $room_filter_status,
            'room_filter_room' => $room_filter_room,
        ]);
    }
}

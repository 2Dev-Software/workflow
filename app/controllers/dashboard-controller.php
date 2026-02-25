<?php

declare(strict_types=1);

require_once __DIR__ . '/../views/view.php';
require_once __DIR__ . '/../../src/Services/auth/auth-guard.php';
require_once __DIR__ . '/../rbac/current_user.php';
require_once __DIR__ . '/../rbac/roles.php';
require_once __DIR__ . '/../modules/system/system.php';
require_once __DIR__ . '/../modules/dashboard/metrics.php';

if (!function_exists('dashboard_resolve_access')) {
    function dashboard_resolve_access(array $current_user): array
    {
        $actor_pid = trim((string) ($current_user['pID'] ?? ($_SESSION['pID'] ?? '')));
        $role_id = (int) ($current_user['roleID'] ?? 0);
        $position_id = (int) ($current_user['positionID'] ?? 0);

        $connection = db_connection();

        $is_admin_user = $role_id === 1;
        $is_registry_user = $role_id === 2;
        $is_vehicle_user = $role_id === 3;
        $is_facility_user = $role_id === 5;

        if ($actor_pid !== '') {
            $is_admin_user = rbac_user_has_role($connection, $actor_pid, ROLE_ADMIN) || $is_admin_user;
            $is_registry_user = rbac_user_has_role($connection, $actor_pid, ROLE_REGISTRY) || $is_registry_user;
            $is_vehicle_user = rbac_user_has_role($connection, $actor_pid, ROLE_VEHICLE) || $is_vehicle_user;
            $is_facility_user = rbac_user_has_role($connection, $actor_pid, ROLE_FACILITY) || $is_facility_user;
        }

        $acting_pid = (string) (system_get_acting_director_pid() ?? '');
        $is_director_or_acting = $position_id === 1
            || ($acting_pid !== '' && $actor_pid !== '' && $acting_pid === $actor_pid);

        return [
            'is_admin_user' => $is_admin_user,
            'is_registry_user' => $is_registry_user,
            'is_vehicle_user' => $is_vehicle_user,
            'is_facility_user' => $is_facility_user,
            'is_director_or_acting' => $is_director_or_acting,
            'can_manage_external_circular' => $is_admin_user || $is_registry_user,
            'can_manage_room_module' => $is_admin_user || $is_facility_user,
            'can_manage_vehicle_module' => $is_admin_user || $is_vehicle_user,
            'can_access_settings' => $is_admin_user || $is_registry_user,
        ];
    }
}

if (!function_exists('dashboard_shortcuts')) {
    function dashboard_shortcuts(array $access): array
    {
        $director_review_url = 'outgoing-notice.php?box=director&type=external&read=all&sort=newest&view=table1';
        $vehicle_url = !empty($access['is_director_or_acting'])
            ? 'vehicle-reservation-approval.php'
            : 'vehicle-reservation.php';

        return [
            [
                'icon' => 'fa-id-card',
                'label' => 'ลงทะเบียนรับ',
                'href' => 'outgoing-receive.php',
                'visible' => !empty($access['can_manage_external_circular']),
            ],
            [
                'icon' => 'fa-list',
                'label' => 'บันทึกข้อความ',
                'href' => 'memo.php',
                'visible' => true,
            ],
            [
                'icon' => 'fa-paper-plane',
                'label' => 'ส่งหนังสือเวียน',
                'href' => 'circular-compose.php',
                'visible' => true,
            ],
            [
                'icon' => 'fa-car',
                'label' => 'การจองพาหนะ',
                'href' => $vehicle_url,
                'visible' => true,
            ],
            [
                'icon' => 'fa-building',
                'label' => 'การจองสถานที่/ห้อง',
                'href' => 'room-booking.php',
                'visible' => true,
            ],
            [
                'icon' => 'fa-user-tie',
                'label' => 'การปฎิบัติราชการของผู้บริหาร',
                'href' => $director_review_url,
                'visible' => !empty($access['is_director_or_acting']),
            ],
            [
                'icon' => 'fa-gear',
                'label' => 'การตั้งค่า',
                'href' => 'setting.php',
                'visible' => !empty($access['can_access_settings']),
            ],
            [
                'icon' => 'fa-file-lines',
                'label' => 'คำสั่งราชการ',
                'href' => 'orders-create.php',
                'visible' => true,
            ],
            [
                'icon' => 'fa-phone',
                'label' => 'สมุดโทรศัพท์',
                'href' => 'teacher-phone-directory.php',
                'visible' => true,
            ],
            [
                'icon' => 'fa-circle-user',
                'label' => 'โปรไฟล์',
                'href' => 'profile.php',
                'visible' => true,
            ],
        ];
    }
}

if (!function_exists('dashboard_index')) {
    function dashboard_index(): void
    {
        $current_user = current_user() ?? [];
        $current_pid = trim((string) ($current_user['pID'] ?? ($_SESSION['pID'] ?? '')));
        $access = dashboard_resolve_access($current_user);
        $shortcuts = dashboard_shortcuts($access);
        $counts = dashboard_counts($current_pid);

        view_render('dashboard/index', [
            'dashboard_counts' => $counts,
            'dashboard_shortcuts' => $shortcuts,
            'dashboard_access' => $access,
        ]);
    }
}

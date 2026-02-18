<?php

declare(strict_types=1);

require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/../config/constants.php';

if (!function_exists('rbac_role_definitions')) {
    function rbac_role_definitions(): array
    {
        static $definitions;

        if ($definitions === null) {
            $config_path = __DIR__ . '/../config/roles.php';
            $definitions = file_exists($config_path) ? require $config_path : [];
        }

        return $definitions;
    }
}

if (!function_exists('rbac_resolve_role_ids')) {
    function rbac_resolve_role_ids(mysqli $connection, string $role_key): array
    {
        $definitions = rbac_role_definitions();
        $role_key = strtoupper(trim($role_key));

        if (!isset($definitions[$role_key])) {
            return [];
        }

        $definition = $definitions[$role_key];
        $ids = [];

        $configured_id = (int) ($definition['id'] ?? 0);

        if ($configured_id > 0) {
            $ids[] = $configured_id;
        }

        if (!empty($ids)) {
            return array_values(array_unique($ids));
        }

        $names = array_values(array_filter(array_map('trim', (array) ($definition['names'] ?? []))));

        if (empty($names)) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($names), '?'));
        $sql = 'SELECT roleID FROM dh_roles WHERE roleName IN (' . $placeholders . ')';
        $stmt = mysqli_prepare($connection, $sql);

        if ($stmt === false) {
            error_log('Database Error (role lookup): ' . mysqli_error($connection));

            return [];
        }

        $types = str_repeat('s', count($names));
        $params = array_merge([$stmt, $types], $names);
        $refs = [];

        foreach ($params as $index => $value) {
            $refs[$index] = &$params[$index];
        }

        if (call_user_func_array('mysqli_stmt_bind_param', $refs) === false) {
            mysqli_stmt_close($stmt);

            return [];
        }

        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        while ($result && ($row = mysqli_fetch_assoc($result))) {
            $ids[] = (int) $row['roleID'];
        }
        mysqli_stmt_close($stmt);

        return array_values(array_unique(array_filter($ids)));
    }
}

if (!function_exists('rbac_get_user_role_ids')) {
    function rbac_get_user_role_ids(mysqli $connection, string $pID): array
    {
        $pID = trim($pID);

        if ($pID === '') {
            return [];
        }

        $role_ids = [];

        $legacy = db_fetch_one('SELECT roleID FROM teacher WHERE pID = ? AND status = 1 LIMIT 1', 's', $pID);

        if ($legacy && isset($legacy['roleID'])) {
            $role_ids[] = (int) $legacy['roleID'];
        }

        if (db_table_exists($connection, 'dh_user_roles')) {
            $rows = db_fetch_all('SELECT roleID FROM dh_user_roles WHERE pID = ?', 's', $pID);

            foreach ($rows as $row) {
                $role_ids[] = (int) ($row['roleID'] ?? 0);
            }
        }

        if (db_table_exists($connection, 'user_roles')) {
            $rows = db_fetch_all('SELECT role_id AS roleID FROM user_roles WHERE teacher_id = ?', 's', $pID);

            foreach ($rows as $row) {
                $role_ids[] = (int) ($row['roleID'] ?? 0);
            }
        }

        return array_values(array_unique(array_filter($role_ids)));
    }
}

if (!function_exists('rbac_get_user_position_ids')) {
    function rbac_get_user_position_ids(mysqli $connection, string $pID): array
    {
        $pID = trim($pID);

        if ($pID === '') {
            return [];
        }

        $position_ids = [];

        $legacy = db_fetch_one('SELECT positionID FROM teacher WHERE pID = ? AND status = 1 LIMIT 1', 's', $pID);

        if ($legacy && isset($legacy['positionID'])) {
            $position_ids[] = (int) $legacy['positionID'];
        }

        if (db_table_exists($connection, 'dh_user_positions')) {
            $rows = db_fetch_all('SELECT positionID FROM dh_user_positions WHERE pID = ?', 's', $pID);

            foreach ($rows as $row) {
                $position_ids[] = (int) ($row['positionID'] ?? 0);
            }
        }

        if (db_table_exists($connection, 'user_positions')) {
            $rows = db_fetch_all('SELECT position_id AS positionID FROM user_positions WHERE teacher_id = ?', 's', $pID);

            foreach ($rows as $row) {
                $position_ids[] = (int) ($row['positionID'] ?? 0);
            }
        }

        return array_values(array_unique(array_filter($position_ids)));
    }
}

if (!function_exists('rbac_user_has_role')) {
    function rbac_user_has_role(mysqli $connection, string $pID, string $role_key): bool
    {
        $role_ids = rbac_resolve_role_ids($connection, $role_key);

        if (empty($role_ids)) {
            return false;
        }

        $user_roles = rbac_get_user_role_ids($connection, $pID);

        if (empty($user_roles)) {
            return false;
        }

        return count(array_intersect($role_ids, $user_roles)) > 0;
    }
}

if (!function_exists('rbac_user_has_any_role')) {
    function rbac_user_has_any_role(mysqli $connection, string $pID, array $role_keys): bool
    {
        foreach ($role_keys as $role_key) {
            if (rbac_user_has_role($connection, $pID, (string) $role_key)) {
                return true;
            }
        }

        return false;
    }
}

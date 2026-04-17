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

if (!function_exists('rbac_parse_role_ids')) {
    function rbac_parse_role_ids(mixed $value): array
    {
        $ids = [];

        foreach ((array) $value as $item) {
            foreach (preg_split('/\s*,\s*/', trim((string) $item)) ?: [] as $part) {
                $part = trim($part);

                if ($part === '' || !ctype_digit($part)) {
                    continue;
                }

                $role_id = (int) $part;

                if ($role_id > 0) {
                    $ids[] = $role_id;
                }
            }
        }

        return array_values(array_unique($ids));
    }
}

if (!function_exists('rbac_format_role_ids')) {
    function rbac_format_role_ids(array $role_ids): string
    {
        return implode(',', rbac_parse_role_ids($role_ids));
    }
}

if (!function_exists('rbac_csv_role_condition')) {
    function rbac_csv_role_condition(string $column, int $role_count): string
    {
        $column = trim($column);

        if ($column === '' || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*(\.[A-Za-z_][A-Za-z0-9_]*)?$/', $column)) {
            throw new InvalidArgumentException('Invalid role column');
        }

        $role_count = max(1, $role_count);
        $conditions = array_fill(
            0,
            $role_count,
            'FIND_IN_SET(CAST(? AS CHAR), REPLACE(CAST(' . $column . ' AS CHAR), " ", "")) > 0'
        );

        return '(' . implode(' OR ', $conditions) . ')';
    }
}

if (!function_exists('rbac_role_names_select')) {
    function rbac_role_names_select(string $teacher_alias = 't'): string
    {
        $teacher_alias = trim($teacher_alias);

        if ($teacher_alias === '' || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $teacher_alias)) {
            throw new InvalidArgumentException('Invalid teacher alias');
        }

        return '(SELECT GROUP_CONCAT(r.roleName ORDER BY r.roleID SEPARATOR ", ")
            FROM dh_roles AS r
            WHERE FIND_IN_SET(CAST(r.roleID AS CHAR), REPLACE(CAST(' . $teacher_alias . '.roleID AS CHAR), " ", "")) > 0)';
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
            $role_ids = array_merge($role_ids, rbac_parse_role_ids($legacy['roleID']));
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

if (!function_exists('rbac_set_teacher_role_ids')) {
    function rbac_set_teacher_role_ids(mysqli $connection, string $pID, array $role_ids): bool
    {
        $pID = trim($pID);
        $role_value = rbac_format_role_ids($role_ids);

        if ($pID === '' || $role_value === '') {
            return false;
        }

        $stmt = mysqli_prepare($connection, 'UPDATE teacher SET roleID = ? WHERE pID = ? AND status = 1');

        if ($stmt === false) {
            error_log('Database Error (set teacher roles): ' . mysqli_error($connection));

            return false;
        }

        mysqli_stmt_bind_param($stmt, 'ss', $role_value, $pID);
        mysqli_stmt_execute($stmt);
        $affected = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);

        return $affected >= 0;
    }
}

if (!function_exists('rbac_add_teacher_role_id')) {
    function rbac_add_teacher_role_id(mysqli $connection, string $pID, int $role_id): bool
    {
        $role_id = max(0, $role_id);

        if ($role_id <= 0) {
            return false;
        }

        $role_ids = rbac_get_user_role_ids($connection, $pID);
        $role_ids[] = $role_id;

        return rbac_set_teacher_role_ids($connection, $pID, $role_ids);
    }
}

if (!function_exists('rbac_remove_teacher_role_id')) {
    function rbac_remove_teacher_role_id(mysqli $connection, string $pID, int $role_id, int $fallback_role_id = 6): bool
    {
        $role_ids = array_values(array_filter(
            rbac_get_user_role_ids($connection, $pID),
            static fn(int $current_role_id): bool => $current_role_id !== $role_id
        ));

        if ($role_ids === [] && $fallback_role_id > 0) {
            $role_ids[] = $fallback_role_id;
        }

        return rbac_set_teacher_role_ids($connection, $pID, $role_ids);
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
        $normalized_role_key = strtoupper(trim($role_key));
        $role_ids = rbac_resolve_role_ids($connection, $normalized_role_key);

        if (empty($role_ids)) {
            return false;
        }

        $user_roles = rbac_get_user_role_ids($connection, $pID);

        if (empty($user_roles)) {
            return false;
        }

        if (count(array_intersect($role_ids, $user_roles)) > 0) {
            return true;
        }

        if ($normalized_role_key === ROLE_ADMIN) {
            return false;
        }

        $admin_role_ids = rbac_resolve_role_ids($connection, ROLE_ADMIN);

        if (empty($admin_role_ids)) {
            return false;
        }

        return count(array_intersect($admin_role_ids, $user_roles)) > 0;
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

<?php
declare(strict_types=1);

require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/roles.php';
require_once __DIR__ . '/../modules/system/positions.php';

if (!function_exists('current_user_id')) {
    function current_user_id(): ?string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $pID = $_SESSION['pID'] ?? '';
        $pID = trim((string) $pID);

        return $pID !== '' ? $pID : null;
    }
}

if (!function_exists('current_user')) {
    function current_user(): ?array
    {
        $pID = current_user_id();
        if ($pID === null) {
            return null;
        }

        $connection = db_connection();
        $position = system_position_join($connection, 't', 'p');

        $sql = 'SELECT t.pID, t.fName, t.fID, t.dID, t.lID, t.oID, t.positionID, t.roleID, t.telephone, t.picture, t.signature, t.status,
            f.fName AS faction_name,
            d.dName AS department_name,
            l.lName AS level_name,
            ' . $position['name'] . ' AS position_name,
            r.roleName AS role_name
            FROM teacher AS t
            LEFT JOIN faction AS f ON t.fID = f.fID
            LEFT JOIN department AS d ON t.dID = d.dID
            LEFT JOIN level AS l ON t.lID = l.lID
            ' . $position['join'] . '
            LEFT JOIN dh_roles AS r ON t.roleID = r.roleID
            WHERE t.pID = ? AND t.status = 1
            LIMIT 1';

        return db_fetch_one($sql, 's', $pID);
    }
}

if (!function_exists('current_user_role_ids')) {
    function current_user_role_ids(): array
    {
        $pID = current_user_id();
        if ($pID === null) {
            return [];
        }

        $connection = db_connection();
        return rbac_get_user_role_ids($connection, $pID);
    }
}

if (!function_exists('current_user_position_ids')) {
    function current_user_position_ids(): array
    {
        $pID = current_user_id();
        if ($pID === null) {
            return [];
        }

        $connection = db_connection();
        return rbac_get_user_position_ids($connection, $pID);
    }
}

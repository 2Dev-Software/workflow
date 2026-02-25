<?php

declare(strict_types=1);

require_once __DIR__ . '/../../db/db.php';

if (!function_exists('user_list_factions')) {
    function user_list_factions(): array
    {
        return db_fetch_all('SELECT fID, fName FROM faction ORDER BY fName ASC');
    }
}

if (!function_exists('user_list_roles')) {
    function user_list_roles(): array
    {
        return db_fetch_all('SELECT roleID, roleName FROM dh_roles ORDER BY roleName ASC');
    }
}

if (!function_exists('user_list_teachers')) {
    function user_list_teachers(): array
    {
        return db_fetch_all(
            'SELECT t.pID, t.fName, t.fID, t.dID, t.positionID, t.roleID,
                    COALESCE(f.fName, "") AS factionName,
                    COALESCE(d.dName, "") AS departmentName
             FROM teacher AS t
             LEFT JOIN faction AS f ON f.fID = t.fID
             LEFT JOIN department AS d ON d.dID = t.dID
             WHERE t.status = 1
             ORDER BY t.fID ASC, t.fName ASC'
        );
    }
}

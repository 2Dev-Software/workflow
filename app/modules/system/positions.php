<?php
declare(strict_types=1);

require_once __DIR__ . '/../../db/db.php';

if (!function_exists('system_position_config')) {
    function system_position_config(mysqli $connection): array
    {
        $defaults = [
            'table' => 'dh_positions',
            'id' => 'positionID',
            'name' => 'positionName',
        ];

        if (db_table_exists($connection, 'dh_positions')) {
            return $defaults;
        }

        if (db_table_exists($connection, 'position')) {
            return [
                'table' => 'position',
                'id' => 'oID',
                'name' => 'oName',
            ];
        }

        return $defaults;
    }
}

if (!function_exists('system_position_join')) {
    function system_position_join(mysqli $connection, string $teacher_alias = 't', string $position_alias = 'p'): array
    {
        $config = system_position_config($connection);
        $join = 'LEFT JOIN ' . $config['table'] . ' AS ' . $position_alias
            . ' ON ' . $teacher_alias . '.positionID = ' . $position_alias . '.' . $config['id'];

        return [
            'join' => $join,
            'name' => $position_alias . '.' . $config['name'],
        ];
    }
}

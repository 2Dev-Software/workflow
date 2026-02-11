<?php
declare(strict_types=1);

require_once __DIR__ . '/../../db/db.php';

if (!function_exists('vehicle_management_supports_soft_delete')) {
    function vehicle_management_supports_soft_delete(): bool
    {
        static $cached = null;
        if ($cached !== null) {
            return (bool) $cached;
        }

        try {
            $connection = db_connection();
            $cached = db_column_exists($connection, 'dh_vehicles', 'deletedAt');
        } catch (Throwable $e) {
            $cached = false;
        }

        return (bool) $cached;
    }
}

if (!function_exists('vehicle_management_list')) {
    function vehicle_management_list(): array
    {
        $sql = 'SELECT vehicleID, vehicleType, vehicleBrand, vehicleModel, vehiclePlate, vehicleColor, vehicleCapacity, vehicleStatus, createdAt, updatedAt
            FROM dh_vehicles';
        if (vehicle_management_supports_soft_delete()) {
            $sql .= "\n            WHERE deletedAt IS NULL";
        }
        $sql .= '
            ORDER BY vehicleID DESC';

        return db_fetch_all($sql);
    }
}

if (!function_exists('vehicle_management_get')) {
    function vehicle_management_get(int $vehicle_id): ?array
    {
        if ($vehicle_id <= 0) {
            return null;
        }

        $sql = 'SELECT vehicleID, vehicleType, vehicleBrand, vehicleModel, vehiclePlate, vehicleColor, vehicleCapacity, vehicleStatus, createdAt, updatedAt
            FROM dh_vehicles
            WHERE vehicleID = ?';
        if (vehicle_management_supports_soft_delete()) {
            $sql .= ' AND deletedAt IS NULL';
        }
        $sql .= ' LIMIT 1';

        return db_fetch_one($sql, 'i', $vehicle_id);
    }
}

if (!function_exists('vehicle_management_add')) {
    function vehicle_management_add(array $data): int
    {
        $sql = 'INSERT INTO dh_vehicles (vehicleType, vehicleBrand, vehicleModel, vehiclePlate, vehicleColor, vehicleCapacity, vehicleStatus, updatedAt)
            VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)';
        db_execute(
            $sql,
            'sssssis',
            (string) ($data['vehicleType'] ?? ''),
            (string) ($data['vehicleBrand'] ?? ''),
            (string) ($data['vehicleModel'] ?? ''),
            (string) ($data['vehiclePlate'] ?? ''),
            (string) ($data['vehicleColor'] ?? ''),
            (int) ($data['vehicleCapacity'] ?? 0),
            (string) ($data['vehicleStatus'] ?? '')
        );

        return db_last_insert_id();
    }
}

if (!function_exists('vehicle_management_update')) {
    function vehicle_management_update(int $vehicle_id, array $data): void
    {
        if ($vehicle_id <= 0) {
            throw new InvalidArgumentException('Invalid vehicle id');
        }

        $sql = 'UPDATE dh_vehicles
            SET vehicleType = ?, vehicleBrand = ?, vehicleModel = ?, vehiclePlate = ?, vehicleColor = ?, vehicleCapacity = ?, vehicleStatus = ?, updatedAt = CURRENT_TIMESTAMP
            WHERE vehicleID = ?';
        if (vehicle_management_supports_soft_delete()) {
            $sql .= ' AND deletedAt IS NULL';
        }
        db_execute(
            $sql,
            'sssssisi',
            (string) ($data['vehicleType'] ?? ''),
            (string) ($data['vehicleBrand'] ?? ''),
            (string) ($data['vehicleModel'] ?? ''),
            (string) ($data['vehiclePlate'] ?? ''),
            (string) ($data['vehicleColor'] ?? ''),
            (int) ($data['vehicleCapacity'] ?? 0),
            (string) ($data['vehicleStatus'] ?? ''),
            $vehicle_id
        );
    }
}

if (!function_exists('vehicle_management_delete')) {
    function vehicle_management_delete(int $vehicle_id): void
    {
        if ($vehicle_id <= 0) {
            throw new InvalidArgumentException('Invalid vehicle id');
        }

        if (!vehicle_management_supports_soft_delete()) {
            $sql = 'DELETE FROM dh_vehicles WHERE vehicleID = ?';
            db_execute($sql, 'i', $vehicle_id);
            return;
        }

        $sql = 'UPDATE dh_vehicles
            SET deletedAt = CURRENT_TIMESTAMP, updatedAt = CURRENT_TIMESTAMP
            WHERE vehicleID = ? AND deletedAt IS NULL';
        db_execute($sql, 'i', $vehicle_id);
    }
}

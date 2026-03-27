<?php

declare(strict_types=1);

namespace Tests\PHPUnit\Infrastructure;

use Tests\Support\WorkflowTestCase;

final class DatabaseConnectionPolicyTest extends WorkflowTestCase
{
    public function testCriticalTablesAreAvailable(): void
    {
        $connection = $this->connection();
        $requiredTables = [
            'teacher',
            'thesystem',
            'dh_circulars',
            'dh_circular_inboxes',
            'dh_memos',
            'dh_orders',
            'dh_outgoing_letters',
            'dh_room_bookings',
            'dh_vehicle_bookings',
            'dh_repair_requests',
            'dh_files',
            'dh_file_refs',
        ];

        foreach ($requiredTables as $table) {
            $this->assertTrue(db_table_exists($connection, $table), 'Missing required table ' . $table);
        }
    }

    public function testDatabaseSessionUsesTheConfiguredUtf8mb4Policy(): void
    {
        $connection = $this->connection();
        $expectations = [
            'character_set_connection' => 'utf8mb4',
            'character_set_results' => 'utf8mb4',
            'character_set_client' => 'utf8mb4',
            'collation_connection' => 'utf8mb4_general_ci',
        ];

        foreach ($expectations as $name => $expected) {
            $result = mysqli_query($connection, 'SHOW VARIABLES LIKE "' . $name . '"');
            $row = $result ? mysqli_fetch_assoc($result) : null;
            $actual = trim((string) ($row['Value'] ?? ''));
            $this->assertSame($expected, $actual, 'Database session variable drifted for ' . $name);
        }
    }
}

<?php

declare(strict_types=1);

return [
    'ADMIN' => [
        'id' => isset($_ENV['ROLE_ADMIN_ID']) ? (int) $_ENV['ROLE_ADMIN_ID'] : 1,
        'names' => ['ผู้ดูแลระบบ', 'แอดมิน', 'Admin', 'Administrator', 'System Admin'],
    ],
    'REGISTRY' => [
        'id' => isset($_ENV['ROLE_REGISTRY_ID']) ? (int) $_ENV['ROLE_REGISTRY_ID'] : 2,
        'names' => ['เจ้าหน้าที่สารบรรณ', 'เจ้าหน้าที่สารบัญ', 'สารบรรณ', 'สารบัญ', 'Saraban', 'Registry Officer', 'Document Officer', 'Clerk'],
    ],
    'VEHICLE' => [
        'id' => isset($_ENV['ROLE_VEHICLE_ID']) ? (int) $_ENV['ROLE_VEHICLE_ID'] : 3,
        'names' => ['เจ้าหน้าที่ยานพาหนะ', 'Vehicle Officer', 'Transport Officer'],
    ],
    'LEAVE' => [
        'id' => isset($_ENV['ROLE_LEAVE_ID']) ? (int) $_ENV['ROLE_LEAVE_ID'] : 4,
        'names' => ['เจ้าหน้าที่วันลา', 'เจ้าหน้าที่ลา', 'Leave Officer'],
    ],
    'FACILITY' => [
        'id' => isset($_ENV['ROLE_FACILITY_ID']) ? (int) $_ENV['ROLE_FACILITY_ID'] : 5,
        'names' => ['เจ้าหน้าที่สถานที่', 'เจ้าหน้าที่อาคาร', 'Facility Officer', 'Building Officer'],
    ],
    'GENERAL' => [
        'id' => isset($_ENV['ROLE_GENERAL_ID']) ? (int) $_ENV['ROLE_GENERAL_ID'] : 6,
        'names' => ['บุคลากรทั่วไป', 'ครู', 'Staff', 'General Staff'],
    ],
];

<?php
declare(strict_types=1);

require_once __DIR__ . '/../../db/db.php';

if (!function_exists('dashboard_counts')) {
    function dashboard_counts(string $pID): array
    {
        $pID = trim($pID);
        if ($pID === '') {
            return [
                'unread_circulars' => 0,
                'unread_orders' => 0,
                'pending_manager' => 0,
                'pending_approvals' => 0,
            ];
        }

        $row = db_fetch_one('SELECT COUNT(*) AS total FROM dh_circular_inboxes WHERE pID = ? AND isRead = 0 AND isArchived = 0', 's', $pID);
        $unread_circulars = $row ? (int) $row['total'] : 0;

        $unread_orders = 0;
        if (db_table_exists(db_connection(), 'dh_order_inboxes')) {
            $row = db_fetch_one('SELECT COUNT(*) AS total FROM dh_order_inboxes WHERE pID = ? AND isRead = 0 AND isArchived = 0', 's', $pID);
            $unread_orders = $row ? (int) $row['total'] : 0;
        }

        $pending_room = db_fetch_one("SELECT COUNT(*) AS total FROM dh_room_bookings WHERE status = 'PENDING' AND deletedAt IS NULL");
        $pending_vehicle = db_fetch_one("SELECT COUNT(*) AS total FROM dh_vehicle_bookings WHERE status = 'PENDING' AND deletedAt IS NULL");
        $pending_manager = (int) ($pending_room['total'] ?? 0) + (int) ($pending_vehicle['total'] ?? 0);
        $pending_approvals = $pending_manager;

        return [
            'unread_circulars' => $unread_circulars,
            'unread_orders' => $unread_orders,
            'pending_manager' => $pending_manager,
            'pending_approvals' => $pending_approvals,
        ];
    }
}

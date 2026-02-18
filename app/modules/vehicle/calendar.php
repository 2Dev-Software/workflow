<?php

declare(strict_types=1);

require_once __DIR__ . '/../../db/db.php';

if (!function_exists('vehicle_booking_events')) {
    function vehicle_booking_events(int $year): array
    {
        $events = [];
        $sql = 'SELECT b.bookingID, b.startAt, b.endAt, b.status, b.requesterPID,
                v.vehiclePlate, v.vehicleType,
                t.fName AS requesterName
            FROM dh_vehicle_bookings AS b
            LEFT JOIN dh_vehicles AS v ON b.vehicleID = v.vehicleID
            LEFT JOIN teacher AS t ON b.requesterPID = t.pID
            WHERE b.deletedAt IS NULL AND b.dh_year = ?
            ORDER BY b.startAt ASC';

        $rows = db_fetch_all($sql, 'i', $year);

        foreach ($rows as $row) {
            $status = strtoupper((string) ($row['status'] ?? ''));

            if (!in_array($status, ['ASSIGNED', 'APPROVED', 'COMPLETED'], true)) {
                continue;
            }

            $startAt = (string) ($row['startAt'] ?? '');
            $endAt = (string) ($row['endAt'] ?? '');

            if ($startAt === '' || $endAt === '') {
                continue;
            }

            $startDate = DateTime::createFromFormat('Y-m-d H:i:s', $startAt) ?: DateTime::createFromFormat('Y-m-d H:i', $startAt);
            $endDate = DateTime::createFromFormat('Y-m-d H:i:s', $endAt) ?: DateTime::createFromFormat('Y-m-d H:i', $endAt);

            if (!$startDate || !$endDate) {
                continue;
            }

            $title = trim((string) ($row['vehiclePlate'] ?? ''));

            if ($title === '') {
                $title = trim((string) ($row['vehicleType'] ?? 'รถยนต์'));
            }

            $timeRange = $startDate->format('H:i') . '-' . $endDate->format('H:i');
            $detail = 'รายการจองรถ';
            $owner = (string) ($row['requesterName'] ?? '-');

            $event = [
                'bookingId' => (string) ($row['bookingID'] ?? ''),
                'type' => 'car',
                'title' => $title,
                'time' => $timeRange,
                'detail' => $detail,
                'owner' => $owner,
                'status' => $status,
            ];

            $cursor = clone $startDate;
            $endDateOnly = clone $endDate;
            $endDateOnly->setTime(0, 0, 0);
            $cursor->setTime(0, 0, 0);

            while ($cursor <= $endDateOnly) {
                $key = $cursor->format('Y-n-j');

                if (!isset($events[$key])) {
                    $events[$key] = [];
                }
                $events[$key][] = $event;
                $cursor->modify('+1 day');
            }
        }

        return $events;
    }
}

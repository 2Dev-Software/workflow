<?php

declare(strict_types=1);

it('builds room calendar events from approved bookings only', function (): void {
    /** @var \Tests\Support\WorkflowTestCase $this */
    $connection = $this->connection();
    $approved = room_booking_status_to_db($connection, 1);
    $conflict = room_booking_get_conflict_status_value($connection);

    expect($approved['type'] ?? null)->toBe($conflict['type'] ?? null);
    expect((string) ($approved['value'] ?? ''))->toBe((string) ($conflict['value'] ?? ''));

    $events = room_booking_build_events([
        [
            'roomBookingID' => '100',
            'roomID' => 'R1',
            'bookingTopic' => 'Pending should not appear',
            'requesterName' => 'Requester A',
            'attendeeCount' => '5',
            'startDate' => '2026-03-27',
            'endDate' => '2026-03-27',
            'startTime' => '09:00:00',
            'endTime' => '10:00:00',
            'status' => 0,
        ],
        [
            'roomBookingID' => '101',
            'roomID' => 'R1',
            'bookingTopic' => 'Approved event',
            'requesterName' => 'Requester B',
            'attendeeCount' => '10',
            'startDate' => '2026-03-27',
            'endDate' => '2026-03-27',
            'startTime' => '10:00:00',
            'endTime' => '11:00:00',
            'status' => 1,
        ],
        [
            'roomBookingID' => '102',
            'roomID' => 'R1',
            'bookingTopic' => 'Rejected should not appear',
            'requesterName' => 'Requester C',
            'attendeeCount' => '2',
            'startDate' => '2026-03-27',
            'endDate' => '2026-03-27',
            'startTime' => '13:00:00',
            'endTime' => '14:00:00',
            'status' => 2,
        ],
    ], ['R1' => 'ห้องประชุมใหญ่']);

    $dayEvents = $events['2026-3-27'] ?? [];

    expect($dayEvents)->toHaveCount(1);
    expect((string) ($dayEvents[0]['bookingId'] ?? ''))->toBe('101');
});

it('publishes approved vehicle bookings only', function (): void {
    /** @var \Tests\Support\WorkflowTestCase $this */
    $year = $this->currentDhYear();
    $events = vehicle_booking_events($year);

    foreach ($events as $dayEvents) {
        foreach ($dayEvents as $event) {
            expect((string) ($event['status'] ?? ''))->toBe('APPROVED');
            expect(trim((string) ($event['bookingId'] ?? '')))->not->toBe('');
        }
    }

    $approvedCount = (int) (db_fetch_one(
        'SELECT COUNT(*) AS total FROM dh_vehicle_bookings WHERE deletedAt IS NULL AND dh_year = ? AND UPPER(status) = "APPROVED"',
        'i',
        $year
    )['total'] ?? 0);

    if ($approvedCount === 0) {
        expect($events)->toBe([]);
    }
});

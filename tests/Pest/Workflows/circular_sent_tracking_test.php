<?php

declare(strict_types=1);

it('keeps circular sent counters consistent for an active sender', function (): void {
    /** @var \Tests\Support\WorkflowTestCase $this */
    $senderPid = $this->requireScalarValue(
        'SELECT createdByPID FROM dh_circulars WHERE deletedAt IS NULL AND createdByPID IS NOT NULL AND createdByPID <> "" ORDER BY circularID DESC LIMIT 1',
        'No circular sender data available'
    );

    $rows = circular_list_sent($senderPid);

    expect($rows)->toBeArray();

    foreach (array_slice($rows, 0, 20) as $row) {
        $recipientCount = (int) ($row['recipientCount'] ?? -1);
        $readCount = (int) ($row['readCount'] ?? -1);

        expect($recipientCount)->toBeGreaterThanOrEqual(0);
        expect($readCount)->toBeGreaterThanOrEqual(0);
        expect($readCount)->toBeLessThanOrEqual($recipientCount);
        expect(trim((string) ($row['status'] ?? '')))->not->toBe('');
        expect(trim((string) ($row['subject'] ?? '')))->not->toBe('');
    }
});

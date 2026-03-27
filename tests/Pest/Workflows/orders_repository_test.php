<?php

declare(strict_types=1);

it('keeps order numbering and inbox counters consistent', function (): void {
    /** @var \Tests\Support\WorkflowTestCase $this */
    $year = $this->currentDhYear();
    $preview = order_preview_number($year);

    expect($preview)->toMatch('/^\d+\/' . preg_quote((string) $year, '/') . '$/');

    $ownerPid = $this->requireScalarValue(
        'SELECT createdByPID FROM dh_orders WHERE deletedAt IS NULL AND createdByPID IS NOT NULL AND createdByPID <> "" ORDER BY orderID DESC LIMIT 1',
        'No order owner data available'
    );

    $draftRows = order_list_drafts_page_filtered($ownerPid, ['status' => 'all'], 50, 0);

    foreach (array_slice($draftRows, 0, 20) as $row) {
        $recipientCount = (int) ($row['recipientCount'] ?? -1);
        $readCount = (int) ($row['readCount'] ?? -1);

        expect($recipientCount)->toBeGreaterThanOrEqual(0);
        expect($readCount)->toBeGreaterThanOrEqual(0);
        expect($readCount)->toBeLessThanOrEqual($recipientCount);
    }

    $inboxPid = $this->requireScalarValue(
        'SELECT pID FROM dh_order_inboxes WHERE pID IS NOT NULL AND pID <> "" ORDER BY inboxID DESC LIMIT 1',
        'No order inbox data available'
    );

    $summary = order_inbox_read_summary($inboxPid, false, null, null);
    $total = order_count_inbox_filtered($inboxPid, false, null, null, null);

    expect(($summary['read'] ?? 0) + ($summary['unread'] ?? 0))->toBe($total);
});

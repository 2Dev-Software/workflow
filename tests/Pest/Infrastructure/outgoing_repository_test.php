<?php

declare(strict_types=1);

it('lists outgoing rows without collation failures', function (): void {
    $rows = outgoing_list([]);

    expect($rows)->toBeArray();

    foreach (array_slice($rows, 0, 10) as $row) {
        expect($row)->toHaveKeys(['outgoingID', 'attachmentCount']);
        expect((int) ($row['attachmentCount'] ?? -1))->toBeGreaterThanOrEqual(0);
    }

    $ids = array_values(array_filter(array_map(static fn (array $row): int => (int) ($row['outgoingID'] ?? 0), array_slice($rows, 0, 3))));

    if ($ids !== []) {
        expect(outgoing_list_attachments_map($ids))->toBeArray();
        expect(outgoing_get_attachments($ids[0]))->toBeArray();
    }
});

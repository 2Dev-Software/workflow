<?php

declare(strict_types=1);

namespace Tests\PHPUnit\Workflows;

use Tests\Support\WorkflowTestCase;

final class MemoWorkflowTest extends WorkflowTestCase
{
    public function testCreatorCountMatchesPagedListingForRecentCreator(): void
    {
        $creatorPid = $this->requireScalarValue(
            'SELECT createdByPID FROM dh_memos WHERE deletedAt IS NULL AND createdByPID IS NOT NULL AND createdByPID <> "" ORDER BY memoID DESC LIMIT 1',
            'No memo creator data available'
        );

        $rows = memo_list_by_creator_page($creatorPid, false, 'all', '', 1000, 0, 'newest', null);
        $count = memo_count_by_creator($creatorPid, false, 'all', '', null);

        $this->assertSame($count, count($rows));
    }

    public function testReviewerInboxKeepsAllowedStatusesAndOwnership(): void
    {
        $reviewerPid = $this->requireScalarValue(
            'SELECT toPID FROM dh_memos WHERE deletedAt IS NULL AND toPID IS NOT NULL AND toPID <> "" ORDER BY memoID DESC LIMIT 1',
            'No memo reviewer data available'
        );

        $rows = $this->requireRows(
            memo_list_by_reviewer_page($reviewerPid, 'all', '', 100, 0, null),
            'No memo reviewer inbox items available'
        );

        $allowedStatuses = [
            MEMO_STATUS_SUBMITTED,
            MEMO_STATUS_IN_REVIEW,
            MEMO_STATUS_RETURNED,
            MEMO_STATUS_APPROVED_UNSIGNED,
            MEMO_STATUS_SIGNED,
            MEMO_STATUS_REJECTED,
        ];

        foreach (array_slice($rows, 0, 20) as $row) {
            $memoId = (int) ($row['memoID'] ?? 0);
            $memo = $memoId > 0 ? memo_get($memoId) : null;

            $this->assertNotNull($memo, 'Memo reviewer detail lookup failed');
            $this->assertSame($reviewerPid, trim((string) ($memo['toPID'] ?? '')));
            $this->assertNotSame($reviewerPid, trim((string) ($memo['createdByPID'] ?? '')));
            $this->assertContains((string) ($memo['status'] ?? ''), $allowedStatuses);
        }
    }
}

<?php

declare(strict_types=1);

namespace Tests\PHPUnit\Workflows;

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 3) . '/app/modules/outgoing/priority.php';

final class OutgoingPriorityBackfillTest extends TestCase
{
    public function testResolvePriorityMetaUsesLegacySubjectWhenStoredTypeWasDefaulted(): void
    {
        $meta = outgoing_resolve_priority_meta(
            "ประเภท: ปกติ\nลงวันที่: 2026-04-02",
            'ขออนุมัติดำเนินการด่วนมาก',
            '',
            ''
        );

        $this->assertSame('high', $meta['priority_key']);
        $this->assertSame('ด่วนมาก', $meta['priority_label']);
        $this->assertSame('legacy_text', $meta['source']);
    }

    public function testResolvePriorityMetaFallsBackToDocumentContentWhenOutgoingDetailIsMissing(): void
    {
        $meta = outgoing_resolve_priority_meta(
            '',
            '',
            "ประเภท: ด่วนที่สุด\nลงวันที่: 2026-04-02",
            ''
        );

        $this->assertSame('highest', $meta['priority_key']);
        $this->assertSame('ด่วนที่สุด', $meta['priority_label']);
        $this->assertSame('document_explicit', $meta['source']);
    }

    public function testApplyPriorityToDetailPreservesLegacyTrailingContent(): void
    {
        $normalized = outgoing_apply_priority_to_detail('ประเภท: ปกติ พะ้พะ้ะ้', 'high');

        $this->assertSame("ประเภท: ด่วนมาก\nพะ้พะ้ะ้", $normalized);
    }
}

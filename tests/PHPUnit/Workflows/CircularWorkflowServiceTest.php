<?php

declare(strict_types=1);

namespace Tests\PHPUnit\Workflows;

require_once dirname(__DIR__, 3) . '/app/modules/circulars/service.php';

use Tests\Support\WorkflowTestCase;

final class CircularWorkflowServiceTest extends WorkflowTestCase
{
    /** @var list<int> */
    private array $createdCircularIds = [];

    /** @var list<string> */
    private array $temporaryTeacherPids = [];

    /** @var list<int> */
    private array $temporaryDutyLogIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        $_SERVER['REQUEST_METHOD'] = 'CLI';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit Circular Workflow Test';
        $_SERVER['REQUEST_URI'] = '/phpunit/circular-workflow';
        $_SERVER['SERVER_NAME'] = 'localhost';

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    protected function tearDown(): void
    {
        $this->cleanupCircularArtifacts();
        $this->cleanupDutyLogs();
        $this->cleanupTemporaryTeachers();

        if (session_status() === PHP_SESSION_ACTIVE) {
            unset($_SESSION['pID']);
        }

        parent::tearDown();
    }

    public function testInternalCreateTracksRecipientsRoutesAndDocuments(): void
    {
        $sender = $this->requireActiveTeacher('No active sender available');
        $recipients = $this->requireActiveTeachers(2, [$sender['pID']], 'Not enough recipients for internal circular test');

        $circularID = $this->createInternalCircular(
            (string) $sender['pID'],
            array_column($recipients, 'pID')
        );

        $circular = circular_get($circularID);
        $targets = circular_get_recipient_targets($circularID);
        $routes = $this->fetchRoutes($circularID);
        $document = $this->fetchDocumentForCircular($circularID);
        $documentRecipients = $this->fetchDocumentRecipientsForCircular($circularID);
        $sentItems = circular_list_sent((string) $sender['pID']);

        $this->assertNotNull($circular);
        $this->assertSame(INTERNAL_STATUS_SENT, (string) ($circular['status'] ?? ''));
        $this->assertSame((string) $sender['pID'], (string) ($circular['createdByPID'] ?? ''));
        $this->assertCount(2, $targets);
        $this->assertSame(['CREATE', 'SEND'], array_column($routes, 'action'));

        foreach ($recipients as $recipient) {
            $inbox = $this->fetchCircularInboxRow($circularID, (string) $recipient['pID'], INBOX_TYPE_NORMAL);
            $this->assertNotNull($inbox, 'Recipient inbox row should be created');
            $this->assertSame((string) $sender['pID'], (string) ($inbox['deliveredByPID'] ?? ''));
            $this->assertSame('0', (string) ($inbox['isRead'] ?? ''));
            $this->assertSame('0', (string) ($inbox['isArchived'] ?? ''));
        }

        $this->assertNotNull($document, 'Document tracking row should be synced');
        $this->assertCount(2, $documentRecipients);

        foreach ($documentRecipients as $documentRecipient) {
            $this->assertSame('UNREAD', (string) ($documentRecipient['inboxStatus'] ?? ''));
            $this->assertSame(INBOX_TYPE_NORMAL, (string) ($documentRecipient['inboxType'] ?? ''));
        }

        $sentItem = $this->findSentItem($sentItems, $circularID);
        $this->assertNotNull($sentItem, 'Sent list should include the created internal circular');
        $this->assertSame(2, (int) ($sentItem['recipientCount'] ?? -1));
        $this->assertSame(0, (int) ($sentItem['readCount'] ?? -1));
    }

    public function testInternalReadArchiveAndUnarchiveStayConsistentForRecipient(): void
    {
        $sender = $this->requireActiveTeacher('No active sender available');
        $recipient = $this->requireActiveTeacher('No active recipient available', [(string) $sender['pID']]);

        $circularID = $this->createInternalCircular((string) $sender['pID'], [(string) $recipient['pID']]);
        $inbox = $this->requireInboxRow(
            $this->fetchCircularInboxRow($circularID, (string) $recipient['pID'], INBOX_TYPE_NORMAL),
            'Recipient inbox row missing before read/archive flow'
        );

        $this->actAs((string) $recipient['pID']);
        circular_mark_read((int) $inbox['inboxID'], (string) $recipient['pID']);

        $updated = circular_get_inbox_item((int) $inbox['inboxID'], (string) $recipient['pID']);
        $readStats = circular_get_read_stats($circularID);

        $this->assertNotNull($updated);
        $this->assertSame('1', (string) ($updated['isRead'] ?? ''));
        $this->assertNotEmpty((string) ($updated['readAt'] ?? ''));
        $this->assertCount(1, $readStats);
        $this->assertSame('1', (string) ($readStats[0]['isRead'] ?? ''));

        circular_archive_inbox((int) $inbox['inboxID'], (string) $recipient['pID']);

        $archivedInbox = $this->requireInboxRow(
            $this->fetchCircularInboxRow($circularID, (string) $recipient['pID'], INBOX_TYPE_NORMAL),
            'Archived inbox row missing'
        );
        $archivedDocumentRecipients = $this->fetchDocumentRecipientsForCircular($circularID);

        $this->assertSame('1', (string) ($archivedInbox['isArchived'] ?? ''));
        $this->assertSame($circularID, (int) ($archivedInbox['circularID'] ?? 0));
        $this->assertCount(1, $archivedDocumentRecipients);
        $this->assertSame('ARCHIVED', (string) ($archivedDocumentRecipients[0]['inboxStatus'] ?? ''));

        circular_unarchive_inbox((int) $inbox['inboxID'], (string) $recipient['pID']);

        $restoredInbox = $this->requireInboxRow(
            $this->fetchCircularInboxRow($circularID, (string) $recipient['pID'], INBOX_TYPE_NORMAL),
            'Restored inbox row missing'
        );
        $restoredDocumentRecipients = $this->fetchDocumentRecipientsForCircular($circularID);

        $this->assertSame('0', (string) ($restoredInbox['isArchived'] ?? ''));
        $this->assertSame('1', (string) ($restoredInbox['isRead'] ?? ''));
        $this->assertCount(1, $restoredDocumentRecipients);
        $this->assertSame('READ', (string) ($restoredDocumentRecipients[0]['inboxStatus'] ?? ''));
    }

    public function testInternalRecallSucceedsBeforeAnyRecipientReads(): void
    {
        $sender = $this->requireActiveTeacher('No active sender available');
        $recipients = $this->requireActiveTeachers(2, [(string) $sender['pID']], 'Need at least two recipients for recall coverage');

        $circularID = $this->createInternalCircular((string) $sender['pID'], array_column($recipients, 'pID'));

        $this->actAs((string) $sender['pID']);
        $result = circular_recall_internal($circularID, (string) $sender['pID']);

        $circular = circular_get($circularID);
        $routes = $this->fetchRoutes($circularID);
        $documentRecipients = $this->fetchDocumentRecipientsForCircular($circularID);

        $this->assertTrue($result);
        $this->assertSame(INTERNAL_STATUS_RECALLED, (string) ($circular['status'] ?? ''));
        $this->assertContains('RECALL', array_column($routes, 'action'));

        foreach ($recipients as $recipient) {
            $inbox = $this->requireInboxRow(
                $this->fetchCircularInboxRow($circularID, (string) $recipient['pID'], INBOX_TYPE_NORMAL),
                'Inbox row missing after recall'
            );
            $this->assertSame('1', (string) ($inbox['isArchived'] ?? ''));
        }

        foreach ($documentRecipients as $documentRecipient) {
            $this->assertSame('ARCHIVED', (string) ($documentRecipient['inboxStatus'] ?? ''));
        }
    }

    public function testInternalRecallIsRejectedAfterAnyRecipientHasRead(): void
    {
        $sender = $this->requireActiveTeacher('No active sender available');
        $recipient = $this->requireActiveTeacher('No active recipient available', [(string) $sender['pID']]);

        $circularID = $this->createInternalCircular((string) $sender['pID'], [(string) $recipient['pID']]);
        $inbox = $this->requireInboxRow(
            $this->fetchCircularInboxRow($circularID, (string) $recipient['pID'], INBOX_TYPE_NORMAL),
            'Recipient inbox row missing before recall rejection test'
        );

        $this->actAs((string) $recipient['pID']);
        circular_mark_read((int) $inbox['inboxID'], (string) $recipient['pID']);

        $this->actAs((string) $sender['pID']);
        $result = circular_recall_internal($circularID, (string) $sender['pID']);
        $circular = circular_get($circularID);

        $this->assertFalse($result);
        $this->assertSame(INTERNAL_STATUS_SENT, (string) ($circular['status'] ?? ''));

        $currentInbox = $this->requireInboxRow(
            $this->fetchCircularInboxRow($circularID, (string) $recipient['pID'], INBOX_TYPE_NORMAL),
            'Inbox row missing after failed recall'
        );
        $this->assertSame('0', (string) ($currentInbox['isArchived'] ?? ''));
    }

    public function testInternalForwardAndResendRebuildRecipientsFromTargets(): void
    {
        $sender = $this->requireActiveTeacher('No active sender available');
        [$recipientA, $recipientB] = $this->requireActiveTeachers(
            2,
            [(string) $sender['pID']],
            'Need two recipients for forward/resend scenario'
        );

        $circularID = $this->createInternalCircular((string) $sender['pID'], [(string) $recipientA['pID']]);

        $this->actAs((string) $recipientA['pID']);
        circular_forward($circularID, (string) $recipientA['pID'], $this->personRecipients([(string) $recipientB['pID']]));

        $forwardedInbox = $this->requireInboxRow(
            $this->fetchCircularInboxRow($circularID, (string) $recipientB['pID'], INBOX_TYPE_NORMAL),
            'Forwarded recipient inbox row should exist'
        );
        $this->assertSame((string) $recipientA['pID'], (string) ($forwardedInbox['deliveredByPID'] ?? ''));

        $this->actAs((string) $sender['pID']);
        $recalled = circular_recall_internal($circularID, (string) $sender['pID']);
        $resent = circular_resend_internal($circularID, (string) $sender['pID']);

        $this->assertTrue($recalled);
        $this->assertTrue($resent);

        $routes = $this->fetchRoutes($circularID);
        $routeNotes = array_column($routes, 'note', 'routeID');
        $documentRecipients = $this->fetchDocumentRecipientsForCircular($circularID);

        $this->assertContains('FORWARD', array_column($routes, 'action'));
        $this->assertContains('SEND', array_column($routes, 'action'));
        $this->assertContains('RESEND', array_filter($routeNotes, static fn ($note): bool => $note !== null));

        $activeInboxes = db_fetch_all(
            'SELECT pID FROM dh_circular_inboxes WHERE circularID = ? AND isArchived = 0 ORDER BY pID ASC',
            'i',
            $circularID
        );

        $this->assertSame(
            [(string) $recipientA['pID'], (string) $recipientB['pID']],
            array_column($activeInboxes, 'pID')
        );
        $this->assertCount(2, $documentRecipients);
    }

    public function testInternalEditAndResendUpdatesContentAndRecipientsAfterRecall(): void
    {
        $sender = $this->requireActiveTeacher('No active sender available');
        [$recipientA, $recipientB] = $this->requireActiveTeachers(
            2,
            [(string) $sender['pID']],
            'Need recipients for edit and resend scenario'
        );

        $circularID = $this->createInternalCircular((string) $sender['pID'], [(string) $recipientA['pID']]);

        $this->actAs((string) $sender['pID']);
        $this->assertTrue(circular_recall_internal($circularID, (string) $sender['pID']));

        $updatedSubject = $this->uniqueText('Edited Internal Subject');
        $updatedDetail = $this->uniqueText('Edited Internal Detail');

        $result = circular_edit_and_resend_internal(
            $circularID,
            (string) $sender['pID'],
            [
                'subject' => $updatedSubject,
                'detail' => $updatedDetail,
                'linkURL' => 'https://example.com/internal/' . $circularID,
                'fromFID' => (int) $sender['fID'],
            ],
            $this->personRecipients([(string) $recipientB['pID']])
        );

        $circular = circular_get($circularID);
        $targets = circular_get_recipient_targets($circularID);
        $activeInboxes = db_fetch_all(
            'SELECT pID, deliveredByPID FROM dh_circular_inboxes WHERE circularID = ? AND isArchived = 0 ORDER BY pID ASC',
            'i',
            $circularID
        );
        $documentRecipients = $this->fetchDocumentRecipientsForCircular($circularID);

        $this->assertTrue($result);
        $this->assertSame(INTERNAL_STATUS_SENT, (string) ($circular['status'] ?? ''));
        $this->assertSame($updatedSubject, (string) ($circular['subject'] ?? ''));
        $this->assertSame($updatedDetail, (string) ($circular['detail'] ?? ''));
        $this->assertCount(1, $targets);
        $this->assertSame((string) $recipientB['pID'], (string) ($targets[0]['pID'] ?? ''));
        $this->assertSame([['pID' => (string) $recipientB['pID'], 'deliveredByPID' => (string) $sender['pID']]], $activeInboxes);
        $this->assertCount(1, $documentRecipients);
        $this->assertSame((string) $recipientB['pID'], (string) ($documentRecipients[0]['recipientPID'] ?? ''));
    }

    public function testExternalCreateToDirectorTracksRegistryAndPendingReview(): void
    {
        $registry = $this->createTemporaryRegistryUser();
        $director = $this->requireDirector();

        $this->actAs((string) $registry['pID']);
        $circularID = $this->createExternalCircular((string) $registry['pID'], true, (string) $director['pID']);

        $circular = circular_get($circularID);
        $routes = $this->fetchRoutes($circularID);
        $reviewerInbox = $this->fetchCircularInboxRow($circularID, (string) $director['pID'], INBOX_TYPE_SPECIAL_PRINCIPAL);
        $registryTrackingInbox = $this->fetchCircularInboxRow($circularID, (string) $registry['pID'], INBOX_TYPE_NORMAL);
        $documentRecipients = $this->fetchDocumentRecipientsForCircular($circularID);

        $this->assertContains((string) $registry['pID'], circular_registry_pids());
        $this->assertSame(EXTERNAL_STATUS_PENDING_REVIEW, (string) ($circular['status'] ?? ''));
        $this->assertSame(['CREATE', 'SEND'], array_column($routes, 'action'));
        $this->assertNotNull($reviewerInbox, 'Director reviewer inbox should exist');
        $this->assertNotNull($registryTrackingInbox, 'Registry tracking inbox should exist');
        $this->assertSame((string) $registry['pID'], (string) ($reviewerInbox['deliveredByPID'] ?? ''));

        $recipientTypes = [];

        foreach ($documentRecipients as $documentRecipient) {
            $recipientTypes[(string) $documentRecipient['recipientPID']] = (string) $documentRecipient['inboxType'];
        }

        $this->assertSame(INBOX_TYPE_SPECIAL_PRINCIPAL, $recipientTypes[(string) $director['pID']] ?? null);
        $this->assertSame(INBOX_TYPE_NORMAL, $recipientTypes[(string) $registry['pID']] ?? null);
    }

    public function testExternalDirectorReviewCreatesRegistryReturnInboxAndRegistryCanForwardToDeputy(): void
    {
        $registry = $this->createTemporaryRegistryUser();
        $director = $this->requireDirector();

        $this->actAs((string) $registry['pID']);
        $circularID = $this->createExternalCircular((string) $registry['pID'], true, (string) $director['pID']);

        $this->actAs((string) $director['pID']);
        circular_director_review($circularID, (string) $director['pID'], 'โปรดดำเนินการต่อ', 1);

        $afterReview = circular_get($circularID);
        $returnInbox = $this->fetchCircularInboxRow($circularID, (string) $registry['pID'], INBOX_TYPE_SARABAN_RETURN);

        $this->assertSame(EXTERNAL_STATUS_REVIEWED, (string) ($afterReview['status'] ?? ''));
        $this->assertNotNull($returnInbox, 'Registry should receive saraban return inbox after review');

        $this->actAs((string) $registry['pID']);
        $deputyPid = circular_registry_forward_to_deputy($circularID, (string) $registry['pID'], 1);

        $routes = $this->fetchRoutes($circularID);
        $forwarded = circular_get($circularID);
        $deputyInbox = $this->fetchCircularInboxRow($circularID, (string) $deputyPid, INBOX_TYPE_NORMAL);

        $this->assertNotNull($deputyPid);
        $this->assertSame(EXTERNAL_STATUS_FORWARDED, (string) ($forwarded['status'] ?? ''));
        $this->assertContains('RETURN', array_column($routes, 'action'));
        $this->assertContains('FORWARD', array_column($routes, 'action'));
        $this->assertNotNull($deputyInbox, 'Deputy inbox should exist after registry forward');
        $this->assertSame((string) $registry['pID'], (string) ($deputyInbox['deliveredByPID'] ?? ''));
    }

    public function testExternalDeputyDistributeDeliversToFinalRecipients(): void
    {
        $registry = $this->createTemporaryRegistryUser();
        $director = $this->requireDirector();

        $this->actAs((string) $registry['pID']);
        $circularID = $this->createExternalCircular((string) $registry['pID'], true, (string) $director['pID']);

        $this->actAs((string) $director['pID']);
        circular_director_review($circularID, (string) $director['pID'], 'ส่งต่อรองผอ.', 1);

        $this->actAs((string) $registry['pID']);
        $deputyPid = circular_registry_forward_to_deputy($circularID, (string) $registry['pID'], 1);
        $finalRecipients = $this->requireActiveTeachers(
            2,
            [(string) $registry['pID'], (string) $director['pID'], (string) $deputyPid],
            'Need final recipients for deputy distribution'
        );

        $this->actAs((string) $deputyPid);
        circular_deputy_distribute(
            $circularID,
            (string) $deputyPid,
            $this->personRecipients(array_column($finalRecipients, 'pID')),
            'แจ้งเวียนภายในกลุ่ม'
        );

        $circular = circular_get($circularID);
        $routes = $this->fetchRoutes($circularID);
        $documentRecipients = $this->fetchDocumentRecipientsForCircular($circularID);

        $this->assertSame(EXTERNAL_STATUS_FORWARDED, (string) ($circular['status'] ?? ''));
        $this->assertContains('APPROVE', array_column($routes, 'action'));

        foreach ($finalRecipients as $finalRecipient) {
            $inbox = $this->fetchCircularInboxRow($circularID, (string) $finalRecipient['pID'], INBOX_TYPE_NORMAL);
            $this->assertNotNull($inbox, 'Final recipient should receive inbox row');
            $this->assertSame((string) $deputyPid, (string) ($inbox['deliveredByPID'] ?? ''));
        }

        $finalRecipientPids = array_column($finalRecipients, 'pID');
        $matchingDocumentRecipients = array_values(array_filter(
            $documentRecipients,
            static fn (array $row): bool => in_array((string) ($row['recipientPID'] ?? ''), $finalRecipientPids, true)
        ));

        $this->assertCount(2, $matchingDocumentRecipients);
    }

    public function testExternalRecallBeforeReviewArchivesReviewerInboxAndAllowsEditResend(): void
    {
        $registry = $this->createTemporaryRegistryUser();
        $director = $this->requireDirector();

        $this->actAs((string) $registry['pID']);
        $circularID = $this->createExternalCircular((string) $registry['pID'], true, (string) $director['pID']);

        $recalled = circular_recall_external_before_review($circularID, (string) $registry['pID']);
        $recalledCircular = circular_get($circularID);
        $reviewerInbox = $this->requireInboxRow(
            $this->fetchCircularInboxRow($circularID, (string) $director['pID'], INBOX_TYPE_SPECIAL_PRINCIPAL),
            'Reviewer inbox should exist before edit and resend'
        );
        $documentRecipientsAfterRecall = $this->fetchDocumentRecipientsForCircular($circularID);

        $this->assertTrue($recalled);
        $this->assertSame(EXTERNAL_STATUS_SUBMITTED, (string) ($recalledCircular['status'] ?? ''));
        $this->assertSame('1', (string) ($reviewerInbox['isArchived'] ?? ''));

        $reviewerDocumentRecipients = array_values(array_filter(
            $documentRecipientsAfterRecall,
            static fn (array $row): bool => (string) ($row['recipientPID'] ?? '') === (string) $director['pID']
        ));
        $this->assertNotEmpty($reviewerDocumentRecipients);
        $this->assertSame('ARCHIVED', (string) ($reviewerDocumentRecipients[0]['inboxStatus'] ?? ''));

        $updatedSubject = $this->uniqueText('Edited External Subject');
        $result = circular_edit_and_resend_external(
            $circularID,
            (string) $registry['pID'],
            [
                'subject' => $updatedSubject,
                'detail' => $this->uniqueText('Edited external detail'),
                'linkURL' => 'https://example.com/external/' . $circularID,
                'extPriority' => 'ด่วน',
                'extBookNo' => $this->uniqueExtBookNo(),
                'extIssuedDate' => date('Y-m-d'),
                'extFromText' => 'สำนักงานเขตพื้นที่การศึกษา',
                'extGroupFID' => 1,
                'reviewerPID' => (string) $director['pID'],
            ]
        );

        $resentCircular = circular_get($circularID);
        $activeReviewerInbox = $this->requireInboxRow(
            $this->fetchLatestCircularInboxRow($circularID, (string) $director['pID'], INBOX_TYPE_SPECIAL_PRINCIPAL),
            'Reviewer inbox should be recreated after edit and resend'
        );
        $routes = $this->fetchRoutes($circularID);

        $this->assertTrue($result);
        $this->assertSame(EXTERNAL_STATUS_PENDING_REVIEW, (string) ($resentCircular['status'] ?? ''));
        $this->assertSame($updatedSubject, (string) ($resentCircular['subject'] ?? ''));
        $this->assertSame('0', (string) ($activeReviewerInbox['isArchived'] ?? ''));
        $this->assertContains('EDIT_RESEND', array_filter(array_column($routes, 'note'), static fn ($note): bool => $note !== null));
    }

    public function testExternalSendToActingDirectorUsesActingInboxType(): void
    {
        $registry = $this->createTemporaryRegistryUser();
        $actingDeputy = $this->requireDeputy('No deputy available for acting director scenario');
        $this->createTemporaryDutyLog((string) $actingDeputy['pID'], 2);

        $this->actAs((string) $registry['pID']);
        $circularID = $this->createExternalCircular((string) $registry['pID'], true, (string) $actingDeputy['pID']);

        $actingInbox = $this->fetchCircularInboxRow($circularID, (string) $actingDeputy['pID'], INBOX_TYPE_ACTING_PRINCIPAL);
        $documentRecipients = $this->fetchDocumentRecipientsForCircular($circularID);

        $this->assertNotNull($actingInbox, 'Acting director inbox should use acting_principal_inbox');

        $actingDocumentRecipients = array_values(array_filter(
            $documentRecipients,
            static fn (array $row): bool => (string) ($row['recipientPID'] ?? '') === (string) $actingDeputy['pID']
        ));

        $this->assertNotEmpty($actingDocumentRecipients);
        $this->assertSame(INBOX_TYPE_ACTING_PRINCIPAL, (string) ($actingDocumentRecipients[0]['inboxType'] ?? ''));
    }

    private function createInternalCircular(string $senderPid, array $recipientPids, ?string $subject = null): int
    {
        $sender = $this->requireTeacherByPid($senderPid, 'Sender row not found for internal circular');
        $subject = $subject ?? $this->uniqueText('Internal Circular');

        $this->actAs($senderPid);

        $circularID = circular_create_internal(
            [
                'dh_year' => $this->currentDhYear(),
                'circularType' => CIRCULAR_TYPE_INTERNAL,
                'subject' => $subject,
                'detail' => $this->uniqueText('Internal detail'),
                'linkURL' => 'https://example.com/internal/' . rawurlencode($subject),
                'fromFID' => (int) ($sender['fID'] ?? 0),
                'status' => INTERNAL_STATUS_SENT,
                'createdByPID' => $senderPid,
            ],
            $this->personRecipients($recipientPids)
        );

        $this->createdCircularIds[] = $circularID;

        return $circularID;
    }

    private function createExternalCircular(string $registryPid, bool $sendNow, ?string $reviewerPid = null, array $overrides = []): int
    {
        $registry = $this->requireTeacherByPid($registryPid, 'Registry row not found for external circular');
        $base = [
            'dh_year' => $this->currentDhYear(),
            'circularType' => CIRCULAR_TYPE_EXTERNAL,
            'subject' => $this->uniqueText('External Circular'),
            'detail' => $this->uniqueText('External detail'),
            'linkURL' => 'https://example.com/external/' . rawurlencode($registryPid),
            'fromFID' => (int) ($registry['fID'] ?? 0),
            'extPriority' => 'ปกติ',
            'extBookNo' => $this->uniqueExtBookNo(),
            'extIssuedDate' => date('Y-m-d'),
            'extFromText' => 'สำนักงานเขตพื้นที่การศึกษา',
            'extGroupFID' => 1,
            'status' => EXTERNAL_STATUS_SUBMITTED,
            'createdByPID' => $registryPid,
            'updatedByPID' => $registryPid,
            'registryNote' => 'PHPUnit seeded external circular',
        ];

        $circularID = circular_create_external(
            array_merge($base, $overrides),
            $registryPid,
            $sendNow,
            [],
            $reviewerPid
        );

        $this->createdCircularIds[] = $circularID;

        return $circularID;
    }

    /**
     * @param list<string> $pids
     * @return array{pids:list<string>,targets:list<array{targetType:string,fID:null,roleID:null,pID:string,isCc:int}>}
     */
    private function personRecipients(array $pids): array
    {
        $normalized = array_values(array_unique(array_map('strval', $pids)));
        $targets = [];

        foreach ($normalized as $pid) {
            $targets[] = [
                'targetType' => 'PERSON',
                'fID' => null,
                'roleID' => null,
                'pID' => $pid,
                'isCc' => 0,
            ];
        }

        return [
            'pids' => $normalized,
            'targets' => $targets,
        ];
    }

    private function actAs(string $pid): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['pID'] = $pid;
    }

    private function requireDirector(): array
    {
        $pid = trim((string) (system_get_director_pid() ?? ''));

        if ($pid === '') {
            $this->markTestSkipped('No active director configured');
        }

        return $this->requireTeacherByPid($pid, 'Configured director row missing');
    }

    private function requireDeputy(string $message, ?int $fId = null): array
    {
        $positionIds = system_position_deputy_ids($this->connection());

        if ($positionIds === []) {
            $this->markTestSkipped($message);
        }

        return $this->requireActiveTeacher(
            $message,
            [],
            null,
            $positionIds,
            $fId
        );
    }

    private function createTemporaryRegistryUser(): array
    {
        $template = $this->requireActiveTeacher('No teacher template available for temporary registry user');
        $pid = $this->generateUniquePid();
        $name = 'PHPUnit Registry ' . substr($pid, -4);

        db_query(
            'INSERT INTO teacher
                (pID, fName, fID, dID, lID, oID, positionID, roleID, telephone, picture, signature, passWord, LineID, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            'ssiiiiiisssssi',
            $pid,
            $name,
            (int) ($template['fID'] ?? 0),
            (int) ($template['dID'] ?? 0),
            (int) ($template['lID'] ?? 0),
            (int) ($template['oID'] ?? 0),
            8,
            2,
            (string) ($template['telephone'] ?? '0000000000'),
            (string) ($template['picture'] ?? ''),
            $template['signature'] ?? null,
            (string) ($template['passWord'] ?? ''),
            (string) ($template['LineID'] ?? ''),
            1
        );

        $this->temporaryTeacherPids[] = $pid;

        return $this->requireTeacherByPid($pid, 'Temporary registry row was not created');
    }

    private function createTemporaryDutyLog(string $pid, int $dutyStatus): int
    {
        $stmt = db_query(
            'INSERT INTO dh_exec_duty_logs (pID, dutyStatus) VALUES (?, ?)',
            'si',
            $pid,
            $dutyStatus
        );
        $dutyLogId = (int) db_last_insert_id();
        mysqli_stmt_close($stmt);

        $this->temporaryDutyLogIds[] = $dutyLogId;

        return $dutyLogId;
    }

    private function requireTeacherByPid(string $pid, string $message): array
    {
        $row = db_fetch_one('SELECT * FROM teacher WHERE pID = ? LIMIT 1', 's', $pid);

        if ($row === null) {
            $this->markTestSkipped($message);
        }

        return $row;
    }

    /**
     * @param list<string> $excludePids
     * @param list<int>|null $positionIds
     */
    private function requireActiveTeacher(
        string $message,
        array $excludePids = [],
        ?int $roleId = null,
        ?array $positionIds = null,
        ?int $fId = null
    ): array {
        $sql = 'SELECT * FROM teacher WHERE status = 1';
        $types = '';
        $params = [];

        if ($roleId !== null) {
            $sql .= ' AND roleID = ?';
            $types .= 'i';
            $params[] = $roleId;
        }

        if ($positionIds !== null && $positionIds !== []) {
            $placeholders = implode(', ', array_fill(0, count($positionIds), '?'));
            $sql .= ' AND positionID IN (' . $placeholders . ')';
            $types .= str_repeat('i', count($positionIds));
            foreach ($positionIds as $positionId) {
                $params[] = (int) $positionId;
            }
        }

        if ($fId !== null) {
            $sql .= ' AND fID = ?';
            $types .= 'i';
            $params[] = $fId;
        }

        if ($excludePids !== []) {
            $placeholders = implode(', ', array_fill(0, count($excludePids), '?'));
            $sql .= ' AND pID NOT IN (' . $placeholders . ')';
            $types .= str_repeat('s', count($excludePids));
            foreach ($excludePids as $excludePid) {
                $params[] = $excludePid;
            }
        }

        $sql .= ' ORDER BY pID ASC LIMIT 1';
        $row = db_fetch_one($sql, $types, ...$params);

        if ($row === null) {
            $this->markTestSkipped($message);
        }

        return $row;
    }

    /**
     * @param list<string> $excludePids
     * @return list<array<string, mixed>>
     */
    private function requireActiveTeachers(int $count, array $excludePids, string $message): array
    {
        $sql = 'SELECT * FROM teacher WHERE status = 1';
        $types = '';
        $params = [];

        if ($excludePids !== []) {
            $placeholders = implode(', ', array_fill(0, count($excludePids), '?'));
            $sql .= ' AND pID NOT IN (' . $placeholders . ')';
            $types .= str_repeat('s', count($excludePids));
            foreach ($excludePids as $excludePid) {
                $params[] = $excludePid;
            }
        }

        $sql .= ' ORDER BY pID ASC LIMIT ' . max(1, $count);
        $rows = db_fetch_all($sql, $types, ...$params);

        if (count($rows) < $count) {
            $this->markTestSkipped($message);
        }

        return array_slice($rows, 0, $count);
    }

    private function fetchCircularInboxRow(int $circularId, string $pid, string $inboxType): ?array
    {
        return db_fetch_one(
            'SELECT *
             FROM dh_circular_inboxes
             WHERE circularID = ? AND pID = ? AND inboxType = ?
             ORDER BY inboxID ASC
             LIMIT 1',
            'iss',
            $circularId,
            $pid,
            $inboxType
        );
    }

    private function fetchLatestCircularInboxRow(int $circularId, string $pid, string $inboxType): ?array
    {
        return db_fetch_one(
            'SELECT *
             FROM dh_circular_inboxes
             WHERE circularID = ? AND pID = ? AND inboxType = ?
             ORDER BY inboxID DESC
             LIMIT 1',
            'iss',
            $circularId,
            $pid,
            $inboxType
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchRoutes(int $circularId): array
    {
        return db_fetch_all(
            'SELECT routeID, action, fromPID, toPID, toFID, note
             FROM dh_circular_routes
             WHERE circularID = ?
             ORDER BY routeID ASC',
            'i',
            $circularId
        );
    }

    private function fetchDocumentForCircular(int $circularId): ?array
    {
        return db_fetch_one(
            'SELECT *
             FROM dh_documents
             WHERE documentNumber = ?
             LIMIT 1',
            's',
            circular_document_number($circularId)
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchDocumentRecipientsForCircular(int $circularId): array
    {
        return db_fetch_all(
            'SELECT dr.*
             FROM dh_document_recipients AS dr
             INNER JOIN dh_documents AS d ON d.id = dr.documentID
             WHERE d.documentNumber = ?
             ORDER BY dr.recipientPID ASC, dr.id ASC',
            's',
            circular_document_number($circularId)
        );
    }

    /**
     * @param list<array<string, mixed>> $sentItems
     */
    private function findSentItem(array $sentItems, int $circularId): ?array
    {
        foreach ($sentItems as $item) {
            if ((int) ($item['circularID'] ?? 0) === $circularId) {
                return $item;
            }
        }

        return null;
    }

    private function requireInboxRow(?array $row, string $message): array
    {
        if ($row === null) {
            $this->fail($message);
        }

        return $row;
    }

    private function cleanupCircularArtifacts(): void
    {
        if ($this->createdCircularIds === []) {
            return;
        }

        $connection = $this->connection();

        foreach (array_reverse(array_values(array_unique($this->createdCircularIds))) as $circularId) {
            $document = $this->fetchDocumentForCircular($circularId);

            if ($document !== null) {
                $documentId = (int) ($document['id'] ?? 0);

                if ($documentId > 0) {
                    if (db_table_exists($connection, 'dh_read_receipts')) {
                        db_execute('DELETE FROM dh_read_receipts WHERE documentID = ?', 'i', $documentId);
                    }

                    db_execute('DELETE FROM dh_document_recipients WHERE documentID = ?', 'i', $documentId);
                    db_execute('DELETE FROM dh_documents WHERE id = ?', 'i', $documentId);
                }
            }

            db_execute('DELETE FROM dh_circulars WHERE circularID = ?', 'i', $circularId);
        }

        $this->createdCircularIds = [];
    }

    private function cleanupDutyLogs(): void
    {
        foreach (array_reverse($this->temporaryDutyLogIds) as $dutyLogId) {
            db_execute('DELETE FROM dh_exec_duty_logs WHERE dutyLogID = ?', 'i', $dutyLogId);
        }

        $this->temporaryDutyLogIds = [];
    }

    private function cleanupTemporaryTeachers(): void
    {
        foreach (array_reverse($this->temporaryTeacherPids) as $pid) {
            db_execute('DELETE FROM teacher WHERE pID = ?', 's', $pid);
        }

        $this->temporaryTeacherPids = [];
    }

    private function uniqueText(string $prefix): string
    {
        return $prefix . ' ' . date('YmdHis') . ' ' . bin2hex(random_bytes(3));
    }

    private function uniqueExtBookNo(): string
    {
        return 'UT-CIR-' . $this->currentDhYear() . '-' . strtoupper(bin2hex(random_bytes(4)));
    }

    private function generateUniquePid(): string
    {
        do {
            $pid = '9' . str_pad((string) random_int(0, 999999999999), 12, '0', STR_PAD_LEFT);
            $exists = db_fetch_one('SELECT pID FROM teacher WHERE pID = ? LIMIT 1', 's', $pid);
        } while ($exists !== null);

        return $pid;
    }
}

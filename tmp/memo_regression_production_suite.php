<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/db/db.php';
require_once __DIR__ . '/../app/modules/memos/service.php';
require_once __DIR__ . '/../app/modules/memos/repository.php';
require_once __DIR__ . '/../app/modules/system/system.php';
require_once __DIR__ . '/../app/services/document-service.php';

app_bootstrap();

function now_token(string $prefix): string
{
    return $prefix . '_' . date('Ymd_His') . '_' . substr(bin2hex(random_bytes(4)), 0, 8);
}

function fail(string $message): never
{
    throw new RuntimeException($message);
}

function assert_true(bool $condition, string $message): void
{
    if (!$condition) {
        fail($message);
    }
}

function memo_or_fail(int $memoID): array
{
    $memo = memo_get($memoID);
    if (!$memo) {
        fail('ไม่พบ memoID=' . $memoID);
    }
    return $memo;
}

function route_actions(int $memoID): array
{
    $routes = memo_list_routes($memoID);
    $actions = [];
    foreach ($routes as $route) {
        $action = trim((string) ($route['action'] ?? ''));
        if ($action !== '') {
            $actions[] = $action;
        }
    }
    return $actions;
}

function route_count(int $memoID): int
{
    return count(memo_list_routes($memoID));
}

function create_direct_draft(string $creatorPID, string $approverPID, int $year, string $subject, string $detail): int
{
    $memoID = memo_create_draft([
        'dh_year' => $year,
        'writeDate' => date('Y-m-d'),
        'subject' => $subject,
        'detail' => $detail,
        'toType' => 'PERSON',
        'toPID' => $approverPID,
        'flowMode' => 'DIRECT',
        'createdByPID' => $creatorPID,
    ]);

    assert_true($memoID > 0, 'create_direct_draft ไม่สำเร็จ');
    return $memoID;
}

function expect_failure(callable $fn, string $messageOnSuccess): string
{
    try {
        $fn();
    } catch (Throwable $e) {
        return $e->getMessage();
    }
    fail($messageOnSuccess);
}

function add_case(array &$suite, string $caseName, callable $fn): void
{
    try {
        $detail = $fn();
        $suite['cases'][] = [
            'case' => $caseName,
            'status' => 'PASS',
            'detail' => is_string($detail) ? $detail : '',
        ];
    } catch (Throwable $e) {
        $suite['cases'][] = [
            'case' => $caseName,
            'status' => 'FAIL',
            'detail' => $e->getMessage(),
        ];
        $suite['pass'] = false;
    }
}

function add_suite(array &$report, string $suiteName, callable $suiteRunner): void
{
    $suite = [
        'suite' => $suiteName,
        'pass' => true,
        'cases' => [],
    ];
    $suiteRunner($suite);
    $report['suites'][] = $suite;
}

function pick_actors(): array
{
    $rows = db_fetch_all(
        'SELECT pID, fName, roleID
         FROM teacher
         WHERE status = 1 AND pID REGEXP "^[0-9]{1,13}$"
         ORDER BY pID ASC
         LIMIT 200'
    );

    if (count($rows) < 3) {
        fail('จำนวนผู้ใช้งาน active ไม่พอสำหรับ regression (ต้องการ >= 3)');
    }

    $creator = trim((string) ($rows[0]['pID'] ?? ''));
    $approver = '';
    $outsider = '';
    $admin = '';

    foreach ($rows as $row) {
        $pid = trim((string) ($row['pID'] ?? ''));
        if ($pid === '' || $pid === $creator) {
            continue;
        }
        if ($approver === '') {
            $approver = $pid;
            continue;
        }
        if ($outsider === '') {
            $outsider = $pid;
        }
        if ((int) ($row['roleID'] ?? 0) === 1 && $pid !== $creator && $pid !== $approver) {
            $admin = $pid;
        }
        if ($approver !== '' && $outsider !== '' && $admin !== '') {
            break;
        }
    }

    if ($approver === '' || $outsider === '') {
        fail('ไม่สามารถหา approver/outsider ที่ไม่ซ้ำกันได้');
    }

    return [
        'creator' => $creator,
        'approver' => $approver,
        'outsider' => $outsider,
        'admin' => $admin,
    ];
}

function ensure_tables_exist(): void
{
    $connection = db_connection();
    $required = [
        'dh_memos',
        'dh_memo_routes',
        'dh_documents',
        'dh_document_recipients',
    ];
    foreach ($required as $table) {
        if (!db_table_exists($connection, $table)) {
            fail('ไม่พบตารางที่จำเป็น: ' . $table);
        }
    }
}

$startedAt = date('Y-m-d H:i:s');
$token = now_token('MEMO_PROD_REG');
$year = system_get_dh_year();
$actors = pick_actors();
ensure_tables_exist();

$creatorPID = $actors['creator'];
$approverPID = $actors['approver'];
$outsiderPID = $actors['outsider'];
$adminPID = $actors['admin'];

$report = [
    'started_at' => $startedAt,
    'token' => $token,
    'year' => $year,
    'actors' => [
        'creator' => $creatorPID,
        'approver' => $approverPID,
        'outsider' => $outsiderPID,
        'admin' => $adminPID !== '' ? $adminPID : '-',
    ],
    'suites' => [],
];

// Suite 1: Core 6 flows (baseline)
add_suite($report, 'Core 6 Flows', static function (array &$suite) use ($creatorPID, $approverPID, $year, $token): void {
    $memoID = 0;

    add_case($suite, 'F1 Create Draft', static function () use (&$memoID, $creatorPID, $approverPID, $year, $token): string {
        $memoID = create_direct_draft($creatorPID, $approverPID, $year, 'Core6 Subject ' . $token, 'Core6 Detail');
        $memo = memo_or_fail($memoID);
        assert_true((string) ($memo['status'] ?? '') === MEMO_STATUS_DRAFT, 'สถานะไม่ใช่ DRAFT');
        return 'memoID=' . $memoID;
    });

    add_case($suite, 'F2 Submit', static function () use (&$memoID, $creatorPID, $approverPID): string {
        memo_submit($memoID, $creatorPID);
        $memo = memo_or_fail($memoID);
        assert_true((string) ($memo['status'] ?? '') === MEMO_STATUS_SUBMITTED, 'สถานะไม่ใช่ SUBMITTED');
        assert_true((string) ($memo['toPID'] ?? '') === $approverPID, 'toPID ไม่ตรง approver');
        assert_true(trim((string) ($memo['memoNo'] ?? '')) !== '', 'memoNo ยังว่าง');
        return 'memoNo=' . (string) $memo['memoNo'];
    });

    add_case($suite, 'F3 Open/Review', static function () use (&$memoID, $approverPID): string {
        memo_mark_in_review($memoID, $approverPID);
        $memo = memo_or_fail($memoID);
        assert_true((string) ($memo['status'] ?? '') === MEMO_STATUS_IN_REVIEW, 'สถานะไม่ใช่ IN_REVIEW');
        assert_true(trim((string) ($memo['firstReadAt'] ?? '')) !== '', 'firstReadAt ยังว่าง');
        return 'firstReadAt=' . (string) $memo['firstReadAt'];
    });

    add_case($suite, 'F4 Return + Resubmit', static function () use (&$memoID, $creatorPID, $approverPID, $token): string {
        memo_return($memoID, $approverPID, 'Core6 return ' . $token);
        $returned = memo_or_fail($memoID);
        assert_true((string) ($returned['status'] ?? '') === MEMO_STATUS_RETURNED, 'สถานะหลัง return ไม่ใช่ RETURNED');

        memo_submit($memoID, $creatorPID);
        $resubmitted = memo_or_fail($memoID);
        assert_true((string) ($resubmitted['status'] ?? '') === MEMO_STATUS_SUBMITTED, 'สถานะหลัง resubmit ไม่ใช่ SUBMITTED');
        assert_true((string) ($resubmitted['toPID'] ?? '') === $approverPID, 'toPID หลัง resubmit ไม่ตรง approver');
        return 'RETURNED -> SUBMITTED';
    });

    add_case($suite, 'F5 Approve/Sign', static function () use (&$memoID, $approverPID, $token): string {
        memo_mark_in_review($memoID, $approverPID);
        memo_director_approve($memoID, $approverPID, 'Core6 approve ' . $token);
        $memo = memo_or_fail($memoID);
        assert_true((string) ($memo['status'] ?? '') === MEMO_STATUS_SIGNED, 'สถานะไม่ใช่ SIGNED');
        return 'approvedBy=' . (string) ($memo['approvedByPID'] ?? '-');
    });

    add_case($suite, 'F6 Archive/Unarchive', static function () use (&$memoID, $creatorPID): string {
        memo_set_archived($memoID, $creatorPID, true);
        $memoA = memo_or_fail($memoID);
        assert_true((int) ($memoA['isArchived'] ?? 0) === 1, 'archive ไม่สำเร็จ');

        memo_set_archived($memoID, $creatorPID, false);
        $memoB = memo_or_fail($memoID);
        assert_true((int) ($memoB['isArchived'] ?? 0) === 0, 'unarchive ไม่สำเร็จ');
        return 'isArchived 1 -> 0';
    });
});

// Suite 2: Permission matrix
add_suite($report, 'Permission Matrix', static function (array &$suite) use ($creatorPID, $approverPID, $outsiderPID, $adminPID, $year, $token): void {
    $memoID = 0;

    add_case($suite, 'P1 Creator update draft = allow', static function () use (&$memoID, $creatorPID, $approverPID, $year, $token): string {
        $memoID = create_direct_draft($creatorPID, $approverPID, $year, 'Perm Subject ' . $token, 'Perm detail');
        memo_update_draft($memoID, $creatorPID, [
            'subject' => 'Perm Subject Edited ' . $token,
            'detail' => 'Edited',
            'toType' => 'PERSON',
            'toPID' => $approverPID,
        ]);
        $memo = memo_or_fail($memoID);
        assert_true(str_contains((string) ($memo['subject'] ?? ''), 'Edited'), 'แก้ไข subject ไม่สำเร็จ');
        return 'memoID=' . $memoID;
    });

    add_case($suite, 'P2 Outsider update draft = deny', static function () use (&$memoID, $outsiderPID): string {
        $msg = expect_failure(
            static fn () => memo_update_draft($memoID, $outsiderPID, ['subject' => 'X', 'detail' => 'X']),
            'outsider update draft ไม่ควรสำเร็จ'
        );
        return $msg;
    });

    add_case($suite, 'P3 Outsider submit = deny', static function () use (&$memoID, $outsiderPID): string {
        $msg = expect_failure(
            static fn () => memo_submit($memoID, $outsiderPID),
            'outsider submit ไม่ควรสำเร็จ'
        );
        return $msg;
    });

    add_case($suite, 'P4 Creator submit = allow', static function () use (&$memoID, $creatorPID): string {
        memo_submit($memoID, $creatorPID);
        $memo = memo_or_fail($memoID);
        assert_true((string) ($memo['status'] ?? '') === MEMO_STATUS_SUBMITTED, 'submit โดย creator ไม่สำเร็จ');
        return 'status=' . (string) $memo['status'];
    });

    add_case($suite, 'P5 Outsider return = deny', static function () use (&$memoID, $outsiderPID, $token): string {
        $msg = expect_failure(
            static fn () => memo_return($memoID, $outsiderPID, 'outsider return ' . $token),
            'outsider return ไม่ควรสำเร็จ'
        );
        return $msg;
    });

    add_case($suite, 'P6 Approver return = allow', static function () use (&$memoID, $approverPID, $token): string {
        memo_return($memoID, $approverPID, 'approver return ' . $token);
        $memo = memo_or_fail($memoID);
        assert_true((string) ($memo['status'] ?? '') === MEMO_STATUS_RETURNED, 'return โดย approver ไม่สำเร็จ');
        return 'status=' . (string) $memo['status'];
    });

    add_case($suite, 'P7 Approver cancel = deny', static function () use (&$memoID, $approverPID): string {
        $msg = expect_failure(
            static fn () => memo_cancel($memoID, $approverPID),
            'approver cancel ไม่ควรสำเร็จ'
        );
        return $msg;
    });

    add_case($suite, 'P8 Creator cancel = allow', static function () use (&$memoID, $creatorPID): string {
        memo_cancel($memoID, $creatorPID);
        $memo = memo_or_fail($memoID);
        assert_true((string) ($memo['status'] ?? '') === MEMO_STATUS_CANCELLED, 'cancel โดย creator ไม่สำเร็จ');
        return 'status=' . (string) $memo['status'];
    });

    add_case($suite, 'P9 Outsider archive = deny', static function () use (&$memoID, $outsiderPID): string {
        $msg = expect_failure(
            static fn () => memo_set_archived($memoID, $outsiderPID, true),
            'outsider archive ไม่ควรสำเร็จ'
        );
        return $msg;
    });

    if ($adminPID !== '') {
        add_case($suite, 'P10 Admin action bypass = deny (service layer)', static function () use (&$memoID, $adminPID): string {
            $msg = expect_failure(
                static fn () => memo_set_archived($memoID, $adminPID, true),
                'admin service bypass ไม่ควรสำเร็จ'
            );
            return $msg;
        });
    }
});

// Suite 3: Direct flow (approve-unsigned, reject)
add_suite($report, 'Direct Flow Paths', static function (array &$suite) use ($creatorPID, $approverPID, $year, $token): void {
    $memoApproveUnsignedID = 0;

    add_case($suite, 'D1 Approve unsigned path', static function () use (&$memoApproveUnsignedID, $creatorPID, $approverPID, $year, $token): string {
        $memoApproveUnsignedID = create_direct_draft($creatorPID, $approverPID, $year, 'Direct AU ' . $token, 'Direct approve unsigned');
        memo_submit($memoApproveUnsignedID, $creatorPID);
        memo_mark_in_review($memoApproveUnsignedID, $approverPID);
        memo_approve_unsigned($memoApproveUnsignedID, $approverPID, 'approve unsigned ' . $token);
        $memo = memo_or_fail($memoApproveUnsignedID);
        assert_true((string) ($memo['status'] ?? '') === MEMO_STATUS_APPROVED_UNSIGNED, 'ไม่เข้าสถานะ APPROVED_UNSIGNED');
        return 'status=' . (string) $memo['status'];
    });

    add_case($suite, 'D2 Recall from approved-unsigned and submit again', static function () use (&$memoApproveUnsignedID, $creatorPID, $approverPID): string {
        memo_recall($memoApproveUnsignedID, $creatorPID);
        $afterRecall = memo_or_fail($memoApproveUnsignedID);
        assert_true((string) ($afterRecall['status'] ?? '') === MEMO_STATUS_DRAFT, 'recall ไม่ได้กลับ DRAFT');

        memo_update_draft($memoApproveUnsignedID, $creatorPID, [
            'subject' => (string) ($afterRecall['subject'] ?? ''),
            'detail' => (string) ($afterRecall['detail'] ?? ''),
            'toType' => 'PERSON',
            'toPID' => $approverPID,
        ]);
        memo_submit($memoApproveUnsignedID, $creatorPID);
        memo_mark_in_review($memoApproveUnsignedID, $approverPID);
        memo_director_approve($memoApproveUnsignedID, $approverPID, 'final approve after recall');
        $memo = memo_or_fail($memoApproveUnsignedID);
        assert_true((string) ($memo['status'] ?? '') === MEMO_STATUS_SIGNED, 'final approve ไม่เป็น SIGNED');
        return 'status=' . (string) $memo['status'];
    });

    add_case($suite, 'D3 Reject path', static function () use ($creatorPID, $approverPID, $year, $token): string {
        $memoRejectID = create_direct_draft($creatorPID, $approverPID, $year, 'Direct Reject ' . $token, 'Direct reject path');
        memo_submit($memoRejectID, $creatorPID);
        memo_mark_in_review($memoRejectID, $approverPID);
        memo_reject($memoRejectID, $approverPID, 'reject ' . $token);
        $memo = memo_or_fail($memoRejectID);
        assert_true((string) ($memo['status'] ?? '') === MEMO_STATUS_REJECTED, 'reject ไม่เข้าสถานะ REJECTED');
        return 'memoID=' . $memoRejectID;
    });
});

// Suite 4: Guard conditions / invalid transitions
add_suite($report, 'Guard Conditions', static function (array &$suite) use ($creatorPID, $approverPID, $year, $token): void {
    $memoID = create_direct_draft($creatorPID, $approverPID, $year, 'Guard ' . $token, 'Guard test');

    add_case($suite, 'G1 Archive while draft = deny', static function () use ($memoID, $creatorPID): string {
        $msg = expect_failure(
            static fn () => memo_set_archived($memoID, $creatorPID, true),
            'archive while draft ไม่ควรสำเร็จ'
        );
        return $msg;
    });

    add_case($suite, 'G2 Recall while draft = deny', static function () use ($memoID, $creatorPID): string {
        $msg = expect_failure(
            static fn () => memo_recall($memoID, $creatorPID),
            'recall while draft ไม่ควรสำเร็จ'
        );
        return $msg;
    });

    add_case($suite, 'G3 Forward in DIRECT mode = deny', static function () use ($memoID, $creatorPID, $approverPID): string {
        memo_submit($memoID, $creatorPID);
        $msg = expect_failure(
            static fn () => memo_forward($memoID, $approverPID, 'forward direct'),
            'forward direct mode ไม่ควรสำเร็จ'
        );
        return $msg;
    });

    add_case($suite, 'G4 Return with empty note = deny', static function () use ($memoID, $approverPID): string {
        $msg = expect_failure(
            static fn () => memo_return($memoID, $approverPID, ''),
            'return ว่าง note ไม่ควรสำเร็จ'
        );
        return $msg;
    });

    add_case($suite, 'G5 Cancel after signed = deny', static function () use ($memoID, $approverPID, $creatorPID): string {
        memo_mark_in_review($memoID, $approverPID);
        memo_director_approve($memoID, $approverPID, 'guard approve');
        $msg = expect_failure(
            static fn () => memo_cancel($memoID, $creatorPID),
            'cancel หลัง signed ไม่ควรสำเร็จ'
        );
        return $msg;
    });

    add_case($suite, 'G6 Double submit = deny', static function () use ($memoID, $creatorPID): string {
        $msg = expect_failure(
            static fn () => memo_submit($memoID, $creatorPID),
            'submit ซ้ำหลัง signed ไม่ควรสำเร็จ'
        );
        return $msg;
    });
});

// Suite 5: Data integrity + route/audit traces
add_suite($report, 'Data Integrity & Audit Trace', static function (array &$suite) use ($creatorPID, $approverPID, $year, $token): void {
    $memoID = create_direct_draft($creatorPID, $approverPID, $year, 'Integrity ' . $token, 'Integrity detail');
    memo_submit($memoID, $creatorPID);
    $memoSubmitted = memo_or_fail($memoID);

    add_case($suite, 'I1 Document row created on submit', static function () use ($memoSubmitted): string {
        $memoNo = trim((string) ($memoSubmitted['memoNo'] ?? ''));
        assert_true($memoNo !== '', 'memoNo ว่าง');
        $documentID = document_get_id('MEMO', $memoNo);
        assert_true($documentID !== null && $documentID > 0, 'ไม่พบ documentID จาก memoNo');
        $row = db_fetch_one('SELECT subject, status FROM dh_documents WHERE id = ? LIMIT 1', 'i', (int) $documentID);
        assert_true((string) ($row['subject'] ?? '') === (string) ($memoSubmitted['subject'] ?? ''), 'subject ใน dh_documents ไม่ตรง');
        assert_true((string) ($row['status'] ?? '') === MEMO_STATUS_SUBMITTED, 'status ใน dh_documents ไม่ใช่ SUBMITTED');
        return 'documentID=' . $documentID;
    });

    add_case($suite, 'I2 Recipient unread created', static function () use ($memoSubmitted, $approverPID): string {
        $documentID = document_get_id('MEMO', (string) ($memoSubmitted['memoNo'] ?? ''));
        assert_true($documentID !== null && $documentID > 0, 'ไม่พบ documentID');
        $recipient = db_fetch_one(
            'SELECT inboxStatus FROM dh_document_recipients WHERE documentID = ? AND recipientPID = ? LIMIT 1',
            'is',
            (int) $documentID,
            $approverPID
        );
        assert_true((string) ($recipient['inboxStatus'] ?? '') === 'UNREAD', 'recipient ไม่เป็น UNREAD หลัง submit');
        return 'inboxStatus=UNREAD';
    });

    add_case($suite, 'I3 Read marker + receipt on open', static function () use ($memoID, $approverPID): string {
        $memo = memo_or_fail($memoID);
        $documentID = document_get_id('MEMO', (string) ($memo['memoNo'] ?? ''));
        assert_true($documentID !== null && $documentID > 0, 'ไม่พบ documentID');

        memo_mark_in_review($memoID, $approverPID);
        $recipient = db_fetch_one(
            'SELECT inboxStatus, readAt FROM dh_document_recipients WHERE documentID = ? AND recipientPID = ? LIMIT 1',
            'is',
            (int) $documentID,
            $approverPID
        );
        assert_true((string) ($recipient['inboxStatus'] ?? '') === 'READ', 'recipient ไม่เป็น READ หลัง open');
        assert_true(trim((string) ($recipient['readAt'] ?? '')) !== '', 'readAt ยังว่าง');

        $connection = db_connection();
        if (db_table_exists($connection, 'dh_read_receipts')) {
            $receipt = db_fetch_one(
                'SELECT receiptID FROM dh_read_receipts WHERE documentID = ? AND recipientPID = ? LIMIT 1',
                'is',
                (int) $documentID,
                $approverPID
            );
            assert_true(!empty($receipt['receiptID']), 'ไม่มี read receipt');
        }
        return 'recipient READ + receipt ok';
    });

    add_case($suite, 'I4 Final approve syncs signed status', static function () use ($memoID, $approverPID): string {
        memo_director_approve($memoID, $approverPID, 'integrity approve');
        $memo = memo_or_fail($memoID);
        assert_true((string) ($memo['status'] ?? '') === MEMO_STATUS_SIGNED, 'memo ไม่เป็น SIGNED');

        $documentID = document_get_id('MEMO', (string) ($memo['memoNo'] ?? ''));
        assert_true($documentID !== null && $documentID > 0, 'ไม่พบ documentID');
        $doc = db_fetch_one('SELECT status FROM dh_documents WHERE id = ? LIMIT 1', 'i', (int) $documentID);
        assert_true((string) ($doc['status'] ?? '') === MEMO_STATUS_SIGNED, 'dh_documents ไม่ sync เป็น SIGNED');
        return 'document status=SIGNED';
    });

    add_case($suite, 'I5 Route trace contains critical actions', static function () use ($memoID): string {
        $actions = route_actions($memoID);
        $must = ['CREATE', 'SUBMIT', 'OPEN', 'DIRECTOR_APPROVE'];
        foreach ($must as $action) {
            assert_true(in_array($action, $actions, true), 'route ขาด action: ' . $action);
        }
        return implode(' -> ', $actions);
    });
});

// Suite 6: Sequence uniqueness + idempotency
add_suite($report, 'Sequence & Idempotency', static function (array &$suite) use ($creatorPID, $approverPID, $year, $token): void {
    add_case($suite, 'S1 Unique memoNo across batch submit', static function () use ($creatorPID, $approverPID, $year, $token): string {
        $memoNos = [];
        for ($i = 1; $i <= 5; $i++) {
            $memoID = create_direct_draft(
                $creatorPID,
                $approverPID,
                $year,
                'Seq Batch ' . $token . ' #' . $i,
                'Seq detail ' . $i
            );
            memo_submit($memoID, $creatorPID);
            $memo = memo_or_fail($memoID);
            $memoNo = trim((string) ($memo['memoNo'] ?? ''));
            assert_true($memoNo !== '', 'memoNo ว่างในรอบ #' . $i);
            $memoNos[] = $memoNo;
        }
        $unique = array_values(array_unique($memoNos));
        assert_true(count($unique) === count($memoNos), 'memoNo ซ้ำใน batch submit');
        return implode(', ', $memoNos);
    });

    add_case($suite, 'S2 Double submit keeps route stable', static function () use ($creatorPID, $approverPID, $year, $token): string {
        $memoID = create_direct_draft($creatorPID, $approverPID, $year, 'Idem Submit ' . $token, 'Idem submit');
        memo_submit($memoID, $creatorPID);
        $before = route_count($memoID);
        $msg = expect_failure(
            static fn () => memo_submit($memoID, $creatorPID),
            'submit ซ้ำไม่ควรสำเร็จ'
        );
        $after = route_count($memoID);
        assert_true($before === $after, 'route count เปลี่ยนหลัง submit ที่ fail');
        return 'routeCount=' . $after . ', failMsg=' . $msg;
    });

    add_case($suite, 'S3 Archive idempotent (second archive no extra route)', static function () use ($creatorPID, $approverPID, $year, $token): string {
        $memoID = create_direct_draft($creatorPID, $approverPID, $year, 'Idem Archive ' . $token, 'Idem archive');
        memo_submit($memoID, $creatorPID);
        memo_mark_in_review($memoID, $approverPID);
        memo_director_approve($memoID, $approverPID, 'idem archive approve');

        $before = route_count($memoID);
        memo_set_archived($memoID, $creatorPID, true);
        $afterFirst = route_count($memoID);
        memo_set_archived($memoID, $creatorPID, true);
        $afterSecond = route_count($memoID);

        assert_true($afterFirst === $before + 1, 'archive ครั้งแรกไม่เพิ่ม route ตามคาด');
        assert_true($afterSecond === $afterFirst, 'archive ครั้งที่สองเพิ่ม route ทั้งที่ state เดิม');
        return 'before=' . $before . ', afterFirst=' . $afterFirst . ', afterSecond=' . $afterSecond;
    });
});

$endedAt = date('Y-m-d H:i:s');
$overallPass = true;
$totalCases = 0;
$totalPass = 0;
$totalFail = 0;

foreach ($report['suites'] as $suite) {
    if (($suite['pass'] ?? false) !== true) {
        $overallPass = false;
    }
    foreach ((array) ($suite['cases'] ?? []) as $case) {
        $totalCases++;
        if ((string) ($case['status'] ?? '') === 'PASS') {
            $totalPass++;
        } else {
            $totalFail++;
        }
    }
}

echo "Memo Production Regression Suite\n";
echo "Started: " . $startedAt . "\n";
echo "Ended:   " . $endedAt . "\n";
echo "Token:   " . $token . "\n";
echo "Actors:  creator=" . $creatorPID . ", approver=" . $approverPID . ", outsider=" . $outsiderPID . ", admin=" . ($adminPID !== '' ? $adminPID : '-') . "\n";
echo "================================================================\n";

foreach ($report['suites'] as $suite) {
    echo '[' . ((bool) ($suite['pass'] ?? false) ? 'PASS' : 'FAIL') . '] SUITE: ' . (string) ($suite['suite'] ?? '-') . "\n";
    foreach ((array) ($suite['cases'] ?? []) as $case) {
        echo '  - [' . (string) ($case['status'] ?? 'FAIL') . '] ' . (string) ($case['case'] ?? '-') . ' :: ' . (string) ($case['detail'] ?? '') . "\n";
    }
    echo "----------------------------------------------------------------\n";
}

echo 'TOTAL CASES: ' . $totalCases . "\n";
echo 'PASS: ' . $totalPass . "\n";
echo 'FAIL: ' . $totalFail . "\n";
echo 'OVERALL: ' . ($overallPass ? 'PASS' : 'FAIL') . "\n";

exit($overallPass ? 0 : 1);


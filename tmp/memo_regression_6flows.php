<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/db/db.php';
require_once __DIR__ . '/../app/modules/memos/service.php';
require_once __DIR__ . '/../app/modules/memos/repository.php';
require_once __DIR__ . '/../app/modules/system/system.php';

app_bootstrap();

function assert_or_fail(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function get_memo_or_fail(int $memoID): array
{
    $memo = memo_get($memoID);
    if (!$memo) {
        throw new RuntimeException('ไม่พบ memoID=' . $memoID);
    }
    return $memo;
}

function record_flow(array &$results, int $flowNo, string $name, callable $fn): void
{
    $label = 'FLOW ' . $flowNo . ' - ' . $name;
    try {
        $detail = $fn();
        $results[] = [
            'flow' => $flowNo,
            'name' => $name,
            'status' => 'PASS',
            'detail' => is_string($detail) ? $detail : '',
        ];
    } catch (Throwable $e) {
        $results[] = [
            'flow' => $flowNo,
            'name' => $name,
            'status' => 'FAIL',
            'detail' => $e->getMessage(),
        ];
    }
}

$actors = db_fetch_all(
    'SELECT pID, fName
     FROM teacher
     WHERE status = 1
       AND pID REGEXP "^[0-9]{1,13}$"
     ORDER BY pID ASC
     LIMIT 50'
);

if (count($actors) < 2) {
    fwrite(STDERR, "FAIL: ต้องมีผู้ใช้ active อย่างน้อย 2 คน\n");
    exit(1);
}

$creatorPID = trim((string) ($actors[0]['pID'] ?? ''));
$approverPID = '';
foreach ($actors as $actor) {
    $candidate = trim((string) ($actor['pID'] ?? ''));
    if ($candidate !== '' && $candidate !== $creatorPID) {
        $approverPID = $candidate;
        break;
    }
}

if ($creatorPID === '' || $approverPID === '') {
    fwrite(STDERR, "FAIL: ไม่สามารถหา creator/approver ที่ไม่ซ้ำกันได้\n");
    exit(1);
}

$year = system_get_dh_year();
$token = 'REG6_' . date('Ymd_His') . '_' . substr(bin2hex(random_bytes(3)), 0, 6);
$memoID = 0;
$results = [];

record_flow($results, 1, 'Create Draft', static function () use (&$memoID, $creatorPID, $approverPID, $year, $token): string {
    $memoID = memo_create_draft([
        'dh_year' => $year,
        'writeDate' => date('Y-m-d'),
        'subject' => 'Memo Regression ' . $token,
        'detail' => 'Flow 1 create draft',
        'toType' => 'PERSON',
        'toPID' => $approverPID,
        'flowMode' => 'DIRECT',
        'createdByPID' => $creatorPID,
    ]);

    assert_or_fail($memoID > 0, 'สร้าง draft ไม่สำเร็จ');
    $memo = get_memo_or_fail($memoID);
    assert_or_fail((string) ($memo['status'] ?? '') === MEMO_STATUS_DRAFT, 'สถานะไม่ใช่ DRAFT');
    assert_or_fail((string) ($memo['createdByPID'] ?? '') === $creatorPID, 'createdByPID ไม่ตรง creator');
    return 'memoID=' . $memoID . ', status=' . (string) ($memo['status'] ?? '');
});

record_flow($results, 2, 'Submit', static function () use (&$memoID, $creatorPID, $approverPID): string {
    assert_or_fail($memoID > 0, 'memoID ไม่พร้อมสำหรับ submit');
    memo_submit($memoID, $creatorPID);
    $memo = get_memo_or_fail($memoID);
    assert_or_fail((string) ($memo['status'] ?? '') === MEMO_STATUS_SUBMITTED, 'สถานะไม่ใช่ SUBMITTED');
    assert_or_fail(trim((string) ($memo['memoNo'] ?? '')) !== '', 'memoNo ยังว่างหลัง submit');
    assert_or_fail((string) ($memo['toPID'] ?? '') === $approverPID, 'toPID ไม่ตรง approver');
    return 'status=' . (string) ($memo['status'] ?? '') . ', memoNo=' . (string) ($memo['memoNo'] ?? '');
});

record_flow($results, 3, 'Approver Open/Review', static function () use (&$memoID, $approverPID): string {
    assert_or_fail($memoID > 0, 'memoID ไม่พร้อมสำหรับ review');
    memo_mark_in_review($memoID, $approverPID);
    $memo = get_memo_or_fail($memoID);
    assert_or_fail((string) ($memo['status'] ?? '') === MEMO_STATUS_IN_REVIEW, 'สถานะไม่ใช่ IN_REVIEW');
    assert_or_fail(trim((string) ($memo['firstReadAt'] ?? '')) !== '', 'firstReadAt ไม่ถูกบันทึก');
    return 'status=' . (string) ($memo['status'] ?? '') . ', firstReadAt=' . (string) ($memo['firstReadAt'] ?? '');
});

record_flow($results, 4, 'Return and Resubmit', static function () use (&$memoID, $creatorPID, $approverPID, $token): string {
    assert_or_fail($memoID > 0, 'memoID ไม่พร้อมสำหรับ return/resubmit');

    memo_return($memoID, $approverPID, 'Regression return ' . $token);
    $memoAfterReturn = get_memo_or_fail($memoID);
    assert_or_fail((string) ($memoAfterReturn['status'] ?? '') === MEMO_STATUS_RETURNED, 'หลัง return สถานะไม่ใช่ RETURNED');

    memo_submit($memoID, $creatorPID);
    $memoAfterResubmit = get_memo_or_fail($memoID);
    assert_or_fail((string) ($memoAfterResubmit['status'] ?? '') === MEMO_STATUS_SUBMITTED, 'หลัง resubmit สถานะไม่ใช่ SUBMITTED');
    assert_or_fail((string) ($memoAfterResubmit['toPID'] ?? '') === $approverPID, 'หลัง resubmit toPID ไม่ตรง approver');

    return 'RETURNED -> SUBMITTED สำเร็จ';
});

record_flow($results, 5, 'Approve/Sign', static function () use (&$memoID, $approverPID, $creatorPID, $token): string {
    assert_or_fail($memoID > 0, 'memoID ไม่พร้อมสำหรับ approve/sign');

    memo_mark_in_review($memoID, $approverPID);
    memo_director_approve($memoID, $approverPID, 'Regression approve ' . $token);

    $memo = get_memo_or_fail($memoID);
    assert_or_fail((string) ($memo['status'] ?? '') === MEMO_STATUS_SIGNED, 'สถานะไม่ใช่ SIGNED');
    assert_or_fail((string) ($memo['approvedByPID'] ?? '') === $approverPID, 'approvedByPID ไม่ตรง approver');
    assert_or_fail((string) ($memo['toPID'] ?? '') === $creatorPID, 'หลังอนุมัติ toPID ไม่กลับไป creator');

    return 'status=' . (string) ($memo['status'] ?? '') . ', approvedBy=' . (string) ($memo['approvedByPID'] ?? '');
});

record_flow($results, 6, 'Archive/Unarchive', static function () use (&$memoID, $creatorPID): string {
    assert_or_fail($memoID > 0, 'memoID ไม่พร้อมสำหรับ archive/unarchive');

    memo_set_archived($memoID, $creatorPID, true);
    $memoArchived = get_memo_or_fail($memoID);
    assert_or_fail((int) ($memoArchived['isArchived'] ?? 0) === 1, 'archive ไม่สำเร็จ (isArchived != 1)');

    memo_set_archived($memoID, $creatorPID, false);
    $memoUnarchived = get_memo_or_fail($memoID);
    assert_or_fail((int) ($memoUnarchived['isArchived'] ?? 0) === 0, 'unarchive ไม่สำเร็จ (isArchived != 0)');

    return 'archive=1 -> unarchive=0 สำเร็จ';
});

$allPass = true;
foreach ($results as $row) {
    if (($row['status'] ?? 'FAIL') !== 'PASS') {
        $allPass = false;
        break;
    }
}

$routeActions = [];
if ($memoID > 0) {
    $routes = memo_list_routes($memoID);
    foreach ($routes as $route) {
        $action = trim((string) ($route['action'] ?? ''));
        if ($action !== '') {
            $routeActions[] = $action;
        }
    }
}

echo "Memo Regression 6 Flows\n";
echo "Creator PID: " . $creatorPID . "\n";
echo "Approver PID: " . $approverPID . "\n";
echo "Memo ID: " . ($memoID > 0 ? (string) $memoID : '-') . "\n";
echo "Token: " . $token . "\n";
echo "--------------------------------------------------\n";
foreach ($results as $row) {
    echo sprintf(
        "[%s] FLOW %d - %s :: %s\n",
        (string) ($row['status'] ?? 'FAIL'),
        (int) ($row['flow'] ?? 0),
        (string) ($row['name'] ?? '-'),
        (string) ($row['detail'] ?? '')
    );
}
echo "--------------------------------------------------\n";
echo "Route Actions: " . (!empty($routeActions) ? implode(' -> ', $routeActions) : '-') . "\n";
echo "OVERALL: " . ($allPass ? 'PASS' : 'FAIL') . "\n";

exit($allPass ? 0 : 1);


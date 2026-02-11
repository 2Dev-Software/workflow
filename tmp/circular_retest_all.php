<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../app/modules/circulars/service.php';
require_once __DIR__ . '/../app/modules/circulars/repository.php';
require_once __DIR__ . '/../app/rbac/roles.php';
require_once __DIR__ . '/../app/modules/system/system.php';
require_once __DIR__ . '/../app/config/constants.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$results = [];
$errors = [];
$createdCircularIds = [];
$modifiedRoles = [];
$insertedDutyLogIds = [];
$connection = db_connection();

function t_assert(bool $condition, string $name, array $context = []): void
{
    global $results, $errors;
    $results[] = [
        'name' => $name,
        'ok' => $condition,
        'context' => $context,
    ];

    if (!$condition) {
        $errors[] = [
            'name' => $name,
            'context' => $context,
        ];
    }
}

function find_inbox_row(int $circularId, string $pid): ?array
{
    return db_fetch_one(
        'SELECT inboxID, circularID, pID, inboxType, isRead, readAt, isArchived, archivedAt
         FROM dh_circular_inboxes
         WHERE circularID = ? AND pID = ?
         ORDER BY inboxID DESC LIMIT 1',
        'is',
        $circularId,
        $pid
    );
}

function count_inboxes(int $circularId): int
{
    $row = db_fetch_one('SELECT COUNT(*) AS c FROM dh_circular_inboxes WHERE circularID = ?', 'i', $circularId);
    return (int) ($row['c'] ?? 0);
}

function has_route(int $circularId, string $action, ?string $note = null): bool
{
    if ($note === null) {
        $row = db_fetch_one(
            'SELECT routeID FROM dh_circular_routes WHERE circularID = ? AND action = ? ORDER BY routeID DESC LIMIT 1',
            'is',
            $circularId,
            $action
        );
    } else {
        $row = db_fetch_one(
            'SELECT routeID FROM dh_circular_routes WHERE circularID = ? AND action = ? AND note = ? ORDER BY routeID DESC LIMIT 1',
            'iss',
            $circularId,
            $action,
            $note
        );
    }

    return $row !== null;
}

function cleanup_circular(int $circularId): void
{
    $docNumber = 'CIR-' . $circularId;

    $documentRows = db_fetch_all('SELECT id FROM dh_documents WHERE documentNumber = ?', 's', $docNumber);
    foreach ($documentRows as $documentRow) {
        $documentId = (int) ($documentRow['id'] ?? 0);
        if ($documentId > 0) {
            $stmt = db_query('DELETE FROM dh_document_recipients WHERE documentID = ?', 'i', $documentId);
            mysqli_stmt_close($stmt);
            $stmt = db_query('DELETE FROM dh_documents WHERE id = ?', 'i', $documentId);
            mysqli_stmt_close($stmt);
        }
    }

    $announcementRows = db_fetch_all('SELECT announcementID FROM dh_circular_announcements WHERE circularID = ?', 'i', $circularId);
    foreach ($announcementRows as $announcementRow) {
        $announcementId = (int) ($announcementRow['announcementID'] ?? 0);
        if ($announcementId > 0) {
            $stmt = db_query('DELETE FROM dh_circular_announcements WHERE announcementID = ?', 'i', $announcementId);
            mysqli_stmt_close($stmt);
        }
    }

    $fileRows = db_fetch_all(
        'SELECT f.fileID
         FROM dh_file_refs r
         INNER JOIN dh_files f ON f.fileID = r.fileID
         WHERE r.moduleName = ? AND r.entityName = ? AND r.entityID = ?',
        'sss',
        CIRCULAR_MODULE_NAME,
        CIRCULAR_ENTITY_NAME,
        (string) $circularId
    );

    foreach ($fileRows as $fileRow) {
        $fileId = (int) ($fileRow['fileID'] ?? 0);
        if ($fileId > 0) {
            $stmt = db_query('DELETE FROM dh_file_refs WHERE fileID = ?', 'i', $fileId);
            mysqli_stmt_close($stmt);
            $stmt = db_query('DELETE FROM dh_files WHERE fileID = ?', 'i', $fileId);
            mysqli_stmt_close($stmt);
        }
    }

    $stmt = db_query('DELETE FROM dh_circular_inboxes WHERE circularID = ?', 'i', $circularId);
    mysqli_stmt_close($stmt);

    $stmt = db_query('DELETE FROM dh_circular_recipients WHERE circularID = ?', 'i', $circularId);
    mysqli_stmt_close($stmt);

    $stmt = db_query('DELETE FROM dh_circular_routes WHERE circularID = ?', 'i', $circularId);
    mysqli_stmt_close($stmt);

    $stmt = db_query('DELETE FROM dh_circulars WHERE circularID = ?', 'i', $circularId);
    mysqli_stmt_close($stmt);
}

function render_compose_for_user(string $pid): string
{
    // auth-guard.php relies on $connection being available in include scope.
    $connection = db_connection();

    $_SESSION['pID'] = $pid;
    $_GET = [];
    $_POST = [];
    $_FILES = [];
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/circular-compose.php';
    $_SERVER['SCRIPT_NAME'] = '/circular-compose.php';
    $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';

    require_once __DIR__ . '/../app/controllers/circular-compose-controller.php';

    ob_start();
    circular_compose_index();
    return (string) ob_get_clean();
}

try {
    $allTeachers = db_fetch_all('SELECT pID, fName, roleID, positionID, fID FROM teacher WHERE status = 1 ORDER BY pID ASC');
    t_assert(count($allTeachers) >= 6, 'TC01 มีผู้ใช้เพียงพอสำหรับทดสอบ flow', ['count' => count($allTeachers)]);

    $directorPid = system_get_director_pid();
    t_assert($directorPid !== null && $directorPid !== '', 'TC02 พบผู้อำนวยการในระบบ', ['directorPid' => $directorPid]);

    $sender = null;
    $recipientA = null;
    $recipientB = null;
    $recipientC = null;
    $registryPid = null;

    foreach ($allTeachers as $teacher) {
        $pid = (string) ($teacher['pID'] ?? '');
        if ($pid === '' || $pid === (string) $directorPid || !ctype_digit($pid)) {
            continue;
        }

        if ($sender === null && (int) ($teacher['positionID'] ?? 0) === 0 && (int) ($teacher['fID'] ?? 0) > 0) {
            $sender = $teacher;
            continue;
        }

        if ($sender !== null && $pid === (string) ($sender['pID'] ?? '')) {
            continue;
        }

        if ($recipientA === null) {
            $recipientA = $teacher;
            continue;
        }

        if ($recipientB === null && $pid !== (string) ($recipientA['pID'] ?? '')) {
            $recipientB = $teacher;
            continue;
        }

        if ($recipientC === null && $pid !== (string) ($recipientA['pID'] ?? '') && $pid !== (string) ($recipientB['pID'] ?? '')) {
            $recipientC = $teacher;
            continue;
        }

        if ($registryPid === null) {
            $registryPid = $pid;
        }
    }

    if ($registryPid === null && $sender !== null) {
        $registryPid = (string) ($sender['pID'] ?? '');
    }

    t_assert($sender !== null, 'TC03 เลือกผู้ส่ง (numeric pID) ได้', ['sender' => $sender['pID'] ?? null]);
    t_assert($recipientA !== null && $recipientB !== null && $recipientC !== null, 'TC04 เลือกผู้รับทดสอบครบ 3 คน', [
        'recipientA' => $recipientA['pID'] ?? null,
        'recipientB' => $recipientB['pID'] ?? null,
        'recipientC' => $recipientC['pID'] ?? null,
    ]);
    t_assert($registryPid !== null && $registryPid !== '', 'TC05 เลือกผู้ใช้สำหรับบทบาทสารบรรณได้', ['registryPid' => $registryPid]);

    $senderPid = (string) ($sender['pID'] ?? '');
    $recipientAPid = (string) ($recipientA['pID'] ?? '');
    $recipientBPid = (string) ($recipientB['pID'] ?? '');
    $recipientCPid = (string) ($recipientC['pID'] ?? '');

    $composeHtml = render_compose_for_user($senderPid);

    t_assert(strpos($composeHtml, 'value="' . $senderPid . '"') === false, 'TC06 หน้า compose ไม่แสดงผู้ส่งเป็นตัวเลือกผู้รับ', ['senderPid' => $senderPid]);

    $nonNumericRows = db_fetch_all('SELECT pID FROM teacher WHERE status = 1 AND pID NOT REGEXP ? LIMIT 20', 's', '^[0-9]+$');
    $nonNumericSample = [];
    foreach ($nonNumericRows as $row) {
        $pid = (string) ($row['pID'] ?? '');
        if ($pid === '') {
            continue;
        }
        $nonNumericSample[] = $pid;
        if (count($nonNumericSample) >= 6) {
            break;
        }
    }

    $containsNonNumeric = false;
    foreach ($nonNumericSample as $nonPid) {
        if (strpos($composeHtml, 'value="' . $nonPid . '"') !== false) {
            $containsNonNumeric = true;
            break;
        }
    }
    t_assert(!$containsNonNumeric, 'TC07 หน้า compose กรองผู้รับเฉพาะ pID ตัวเลขทั้งหมด', ['samples' => $nonNumericSample]);

    $senderFid = (int) ($sender['fID'] ?? 0);
    if ($senderFid > 0) {
        t_assert(
            strpos($composeHtml, 'name="fromFID" value="' . (string) $senderFid . '"') !== false,
            'TC08 compose ใช้ fromFID จาก fID ของผู้ส่ง',
            ['senderFid' => $senderFid]
        );
    } else {
        t_assert(
            strpos($composeHtml, 'name="fromFID" value=""') !== false,
            'TC08 compose fromFID ว่างเมื่อผู้ส่งไม่มี fID',
            []
        );
    }

    $factionId = (int) ($sender['fID'] ?? 0);
    t_assert($factionId > 0, 'TC09 ผู้ส่งทดสอบมี fID สำหรับสร้าง internal circular', ['fID' => $factionId]);

    // ยกระดับ role ผู้ทดสอบเป็นสารบรรณชั่วคราว
    $origRegistryRole = db_fetch_one('SELECT roleID FROM teacher WHERE pID = ? LIMIT 1', 's', $registryPid);
    $origRegistryRoleId = (int) ($origRegistryRole['roleID'] ?? 0);
    $modifiedRoles[$registryPid] = $origRegistryRoleId;
    $stmt = db_query('UPDATE teacher SET roleID = 2 WHERE pID = ?', 's', $registryPid);
    mysqli_stmt_close($stmt);

    t_assert(rbac_user_has_role($connection, $registryPid, ROLE_REGISTRY), 'TC10 RBAC สารบรรณทำงานได้', ['registryPid' => $registryPid]);

    // ปรับให้ทดสอบเส้นทางผอ.ปกติ (ไม่รักษาการ)
    if ($directorPid !== null && $directorPid !== '') {
        $stmt = db_query('INSERT INTO dh_exec_duty_logs (pID, dutyStatus, end_at) VALUES (?, 1, NULL)', 's', $directorPid);
        $dutyLogId = db_last_insert_id();
        mysqli_stmt_close($stmt);
        if ($dutyLogId > 0) {
            $insertedDutyLogIds[] = $dutyLogId;
        }
    }
    t_assert(system_get_current_director_pid() === $directorPid, 'TC11 current director เป็นผอ.ปกติ', [
        'directorPid' => $directorPid,
        'currentDirector' => system_get_current_director_pid(),
    ]);

    $dhYear = system_get_dh_year();
    $prefix = 'CIR_RETEST_' . date('Ymd_His') . '_' . substr(bin2hex(random_bytes(3)), 0, 6);

    // INTERNAL FLOW #1
    $internal1Id = circular_create_internal([
        'dh_year' => $dhYear,
        'circularType' => CIRCULAR_TYPE_INTERNAL,
        'subject' => $prefix . '_IN_1',
        'detail' => 'internal flow test #1',
        'linkURL' => null,
        'fromFID' => $factionId,
        'status' => INTERNAL_STATUS_SENT,
        'createdByPID' => $senderPid,
    ], [
        'pids' => [$senderPid, $recipientAPid, $recipientBPid],
        'targets' => [
            ['targetType' => 'PERSON', 'pID' => $recipientAPid],
            ['targetType' => 'PERSON', 'pID' => $recipientBPid],
        ],
    ]);
    $createdCircularIds[] = $internal1Id;

    $internal1 = circular_get($internal1Id);
    t_assert($internal1 !== null, 'TC12 สร้างหนังสือเวียนภายในสำเร็จ', ['circularID' => $internal1Id]);
    t_assert((string) ($internal1['status'] ?? '') === INTERNAL_STATUS_SENT, 'TC13 สถานะเริ่มต้น INTERNAL_SENT', ['status' => $internal1['status'] ?? null]);
    t_assert((int) ($internal1['fromFID'] ?? 0) === $factionId, 'TC14 บันทึก fromFID ถูกต้อง', ['fromFID' => $internal1['fromFID'] ?? null]);
    t_assert(count_inboxes($internal1Id) === 2, 'TC15 ผู้รับได้ inbox ครบ 2 คน', ['count' => count_inboxes($internal1Id)]);
    t_assert(count(circular_get_read_stats($internal1Id)) === 2, 'TC16 read receipt มี 2 แถวตามผู้รับ', ['count' => count(circular_get_read_stats($internal1Id))]);

    $recall1 = circular_recall_internal($internal1Id, $senderPid);
    t_assert($recall1 === true, 'TC17 ดึงกลับได้เมื่อยังไม่มีคนอ่าน', []);
    t_assert((string) (circular_get($internal1Id)['status'] ?? '') === INTERNAL_STATUS_RECALLED, 'TC18 ดึงกลับแล้วสถานะเป็น INTERNAL_RECALLED', []);
    t_assert(has_route($internal1Id, 'RECALL'), 'TC19 route RECALL ถูกบันทึก', []);

    $resend1 = circular_resend_internal($internal1Id, $senderPid);
    t_assert($resend1 === true, 'TC20 ส่งใหม่ได้หลังดึงกลับ', []);
    t_assert((string) (circular_get($internal1Id)['status'] ?? '') === INTERNAL_STATUS_SENT, 'TC21 ส่งใหม่แล้วกลับเป็น INTERNAL_SENT', []);
    t_assert(has_route($internal1Id, 'SEND', 'RESEND'), 'TC22 route SEND/RESEND ถูกบันทึก', []);

    $inboxA1 = find_inbox_row($internal1Id, $recipientAPid);
    if ($inboxA1 !== null) {
        circular_mark_read((int) $inboxA1['inboxID'], $recipientAPid);
    }
    $failedRecall = circular_recall_internal($internal1Id, $senderPid);
    t_assert($failedRecall === false, 'TC23 ดึงกลับไม่ได้เมื่อมีคนอ่านแล้ว', []);
    t_assert((string) (circular_get($internal1Id)['status'] ?? '') === INTERNAL_STATUS_SENT, 'TC24 ดึงกลับไม่สำเร็จ สถานะไม่เปลี่ยน', []);

    $unauthRecall = circular_recall_internal($internal1Id, $recipientBPid);
    t_assert($unauthRecall === false, 'TC25 ผู้ไม่ใช่เจ้าของดึงกลับไม่ได้', []);

    // INTERNAL FLOW #2 edit+resend
    $internal2Id = circular_create_internal([
        'dh_year' => $dhYear,
        'circularType' => CIRCULAR_TYPE_INTERNAL,
        'subject' => $prefix . '_IN_2_ORIG',
        'detail' => 'internal flow test #2 original',
        'linkURL' => null,
        'fromFID' => $factionId,
        'status' => INTERNAL_STATUS_SENT,
        'createdByPID' => $senderPid,
    ], [
        'pids' => [$senderPid, $recipientAPid, $recipientBPid],
        'targets' => [
            ['targetType' => 'PERSON', 'pID' => $recipientAPid],
            ['targetType' => 'PERSON', 'pID' => $recipientBPid],
        ],
    ]);
    $createdCircularIds[] = $internal2Id;

    t_assert(circular_recall_internal($internal2Id, $senderPid) === true, 'TC26 internal #2 ดึงกลับเพื่อแก้ไข', []);
    t_assert(circular_resend_internal($internal2Id, $recipientAPid) === false, 'TC27 non-owner ส่งใหม่ไม่ได้', []);

    $editResend = circular_edit_and_resend_internal(
        $internal2Id,
        $senderPid,
        [
            'subject' => $prefix . '_IN_2_EDITED',
            'detail' => 'edited details',
            'linkURL' => 'https://example.com/retest',
            'fromFID' => $factionId,
        ],
        [
            'pids' => [$senderPid, $recipientAPid, $recipientCPid],
            'targets' => [
                ['targetType' => 'PERSON', 'pID' => $recipientAPid],
                ['targetType' => 'PERSON', 'pID' => $recipientCPid],
            ],
        ],
        [],
        []
    );

    t_assert($editResend === true, 'TC28 edit+resend สำเร็จ', []);
    $internal2 = circular_get($internal2Id);
    t_assert((string) ($internal2['subject'] ?? '') === $prefix . '_IN_2_EDITED', 'TC29 subject ถูกแก้ไขแล้ว', ['subject' => $internal2['subject'] ?? null]);
    t_assert((string) ($internal2['status'] ?? '') === INTERNAL_STATUS_SENT, 'TC30 edit+resend แล้วสถานะ INTERNAL_SENT', ['status' => $internal2['status'] ?? null]);
    t_assert(has_route($internal2Id, 'SEND', 'EDIT_RESEND'), 'TC31 route SEND/EDIT_RESEND ถูกบันทึก', []);

    t_assert(find_inbox_row($internal2Id, $recipientAPid) !== null, 'TC32 ผู้รับ A ยังอยู่หลัง edit+resend', []);
    t_assert(find_inbox_row($internal2Id, $recipientBPid) === null, 'TC33 ผู้รับ B ถูกถอดออกหลัง edit+resend', []);
    t_assert(find_inbox_row($internal2Id, $recipientCPid) !== null, 'TC34 ผู้รับ C ถูกเพิ่มหลัง edit+resend', []);

    $inboxA2 = find_inbox_row($internal2Id, $recipientAPid);
    if ($inboxA2 !== null) {
        $inboxA2Id = (int) ($inboxA2['inboxID'] ?? 0);
        circular_archive_inbox($inboxA2Id, $recipientAPid);
        t_assert((int) (find_inbox_row($internal2Id, $recipientAPid)['isArchived'] ?? 0) === 1, 'TC35 archive inbox สำเร็จ', []);
        circular_unarchive_inbox($inboxA2Id, $recipientAPid);
        t_assert((int) (find_inbox_row($internal2Id, $recipientAPid)['isArchived'] ?? 0) === 0, 'TC36 unarchive inbox สำเร็จ', []);
        circular_mark_read($inboxA2Id, $recipientAPid);
    }

    $stats2 = circular_get_read_stats($internal2Id);
    $readMap = [];
    foreach ($stats2 as $s) {
        $readMap[(string) ($s['pID'] ?? '')] = (int) ($s['isRead'] ?? 0);
    }
    t_assert(($readMap[$recipientAPid] ?? 0) === 1, 'TC37 read receipt รายบุคคล A = อ่านแล้ว', []);
    t_assert(($readMap[$recipientCPid] ?? 1) === 0, 'TC38 read receipt รายบุคคล C = ยังไม่อ่าน', []);

    // forward flow
    circular_forward($internal2Id, $recipientAPid, [
        'pids' => [$recipientBPid],
        'targets' => [
            ['targetType' => 'PERSON', 'pID' => $recipientBPid],
        ],
    ]);
    t_assert(find_inbox_row($internal2Id, $recipientBPid) !== null, 'TC39 ผู้รับใหม่จากการส่งต่อได้รับ inbox', []);
    t_assert(has_route($internal2Id, 'FORWARD'), 'TC40 route FORWARD ถูกบันทึก', []);

    // announcement flow
    circular_set_announcement($internal1Id, $senderPid);
    $ann = db_fetch_one('SELECT announcementID, isActive FROM dh_circular_announcements WHERE circularID = ? ORDER BY announcementID DESC LIMIT 1', 'i', $internal1Id);
    t_assert($ann !== null && (int) ($ann['isActive'] ?? 0) === 1, 'TC41 ตั้งประกาศข่าวประชาสัมพันธ์ได้', $ann ?? []);
    if ($ann !== null) {
        circular_remove_announcement((int) $ann['announcementID'], $senderPid);
        $ann2 = db_fetch_one('SELECT isActive FROM dh_circular_announcements WHERE announcementID = ? LIMIT 1', 'i', (int) $ann['announcementID']);
        t_assert((int) ($ann2['isActive'] ?? 1) === 0, 'TC42 ยกเลิกประกาศข่าวประชาสัมพันธ์ได้', $ann2 ?? []);
    }

    // External route (ยังมีในระบบ)
    $externalId = circular_create_external([
        'dh_year' => $dhYear,
        'circularType' => CIRCULAR_TYPE_EXTERNAL,
        'subject' => $prefix . '_EX',
        'detail' => 'external flow retest',
        'linkURL' => null,
        'fromFID' => null,
        'extPriority' => 'ด่วน',
        'extBookNo' => 'RT-' . substr($prefix, -6),
        'extIssuedDate' => date('Y-m-d'),
        'extFromText' => 'หน่วยงานภายนอก',
        'extGroupFID' => $factionId,
        'status' => EXTERNAL_STATUS_SUBMITTED,
        'createdByPID' => $registryPid,
        'registryNote' => 'retest',
    ], $registryPid, true, []);
    $createdCircularIds[] = $externalId;

    t_assert((string) (circular_get($externalId)['status'] ?? '') === EXTERNAL_STATUS_PENDING_REVIEW, 'TC43 external create+send = pending review', []);

    $directorInbox = db_fetch_one('SELECT pID, inboxType FROM dh_circular_inboxes WHERE circularID = ? AND pID = ? ORDER BY inboxID DESC LIMIT 1', 'is', $externalId, (string) $directorPid);
    t_assert($directorInbox !== null, 'TC44 ผอ.ได้รับ inbox หนังสือภายนอก', $directorInbox ?? []);
    t_assert((string) ($directorInbox['inboxType'] ?? '') === INBOX_TYPE_SPECIAL_PRINCIPAL, 'TC45 inbox type ผอ. = special_principal_inbox', $directorInbox ?? []);

    circular_director_review($externalId, (string) $directorPid, 'เห็นควร', $factionId);
    t_assert((string) (circular_get($externalId)['status'] ?? '') === EXTERNAL_STATUS_REVIEWED, 'TC46 ผอ.พิจารณาแล้วสถานะ reviewed', []);

    $registryReturn = db_fetch_one('SELECT inboxType FROM dh_circular_inboxes WHERE circularID = ? AND pID = ? AND inboxType = ? ORDER BY inboxID DESC LIMIT 1', 'iss', $externalId, $registryPid, INBOX_TYPE_SARABAN_RETURN);
    t_assert($registryReturn !== null, 'TC47 สารบรรณได้รับกล่อง return', $registryReturn ?? []);

    $deputyPid = circular_registry_forward_to_deputy($externalId, $registryPid, null);
    t_assert($deputyPid !== null && $deputyPid !== '', 'TC48 สารบรรณส่งต่อรองได้', ['deputyPid' => $deputyPid]);
    t_assert((string) (circular_get($externalId)['status'] ?? '') === EXTERNAL_STATUS_FORWARDED, 'TC49 สถานะหลัง clerk forward = forwarded', []);

    if ($deputyPid !== null && $deputyPid !== '') {
        circular_deputy_distribute($externalId, $deputyPid, [
            'pids' => [$recipientBPid],
            'targets' => [
                ['targetType' => 'PERSON', 'pID' => $recipientBPid],
            ],
        ], 'distributed');

        t_assert(find_inbox_row($externalId, $recipientBPid) !== null, 'TC50 ผู้รับปลายทางได้รับ external ที่รองกระจาย', []);
        t_assert(has_route($externalId, 'APPROVE'), 'TC51 route APPROVE จากรองถูกบันทึก', []);
    }

    // Acting director path test
    $actingPid = '1820500004103';
    $stmt = db_query('INSERT INTO dh_exec_duty_logs (pID, dutyStatus, end_at) VALUES (?, 2, NULL)', 's', $actingPid);
    $actingLogId = db_last_insert_id();
    mysqli_stmt_close($stmt);
    if ($actingLogId > 0) {
        $insertedDutyLogIds[] = $actingLogId;
    }

    t_assert(system_get_current_director_pid() === $actingPid, 'TC52 current director สลับเป็นรักษาการเมื่อ dutyStatus=2', [
        'currentDirector' => system_get_current_director_pid(),
        'expected' => $actingPid,
    ]);

    $externalActingId = circular_create_external([
        'dh_year' => $dhYear,
        'circularType' => CIRCULAR_TYPE_EXTERNAL,
        'subject' => $prefix . '_EX_ACTING',
        'detail' => 'external acting test',
        'linkURL' => null,
        'fromFID' => null,
        'extPriority' => 'ปกติ',
        'extBookNo' => 'ACT-' . substr($prefix, -6),
        'extIssuedDate' => date('Y-m-d'),
        'extFromText' => 'acting route source',
        'extGroupFID' => $factionId,
        'status' => EXTERNAL_STATUS_SUBMITTED,
        'createdByPID' => $registryPid,
        'registryNote' => 'acting path',
    ], $registryPid, true, []);
    $createdCircularIds[] = $externalActingId;

    $actingInbox = db_fetch_one('SELECT pID, inboxType FROM dh_circular_inboxes WHERE circularID = ? ORDER BY inboxID DESC LIMIT 1', 'i', $externalActingId);
    t_assert((string) ($actingInbox['pID'] ?? '') === $actingPid, 'TC53 external route เข้า inbox รองรักษาการ', $actingInbox ?? []);
    t_assert((string) ($actingInbox['inboxType'] ?? '') === INBOX_TYPE_ACTING_PRINCIPAL, 'TC54 inbox type เป็น acting_principal_inbox', $actingInbox ?? []);

    // Audit logs
    $auditRows = db_fetch_all(
        'SELECT actionName FROM dh_logs WHERE moduleName = ? AND entityName = ? AND entityID IN (?, ?, ?, ?) ORDER BY logID DESC',
        'ssiiii',
        'circulars',
        'dh_circulars',
        $internal1Id,
        $internal2Id,
        $externalId,
        $externalActingId
    );

    $actions = array_values(array_unique(array_map(static function (array $row): string {
        return (string) ($row['actionName'] ?? '');
    }, $auditRows)));

    t_assert(in_array('CREATE_INTERNAL', $actions, true), 'TC55 dh_logs มี CREATE_INTERNAL', ['actions' => $actions]);
    t_assert(in_array('RECALL', $actions, true), 'TC56 dh_logs มี RECALL', ['actions' => $actions]);
    t_assert(in_array('RESEND', $actions, true) || in_array('EDIT_RESEND_INTERNAL', $actions, true), 'TC57 dh_logs มี RESEND/EDIT_RESEND_INTERNAL', ['actions' => $actions]);
    t_assert(in_array('FORWARD', $actions, true), 'TC58 dh_logs มี FORWARD', ['actions' => $actions]);
    t_assert(in_array('CREATE_EXTERNAL', $actions, true), 'TC59 dh_logs มี CREATE_EXTERNAL', ['actions' => $actions]);
    t_assert(in_array('DIRECTOR_REVIEW', $actions, true), 'TC60 dh_logs มี DIRECTOR_REVIEW', ['actions' => $actions]);
    t_assert(in_array('CLERK_FORWARD', $actions, true), 'TC61 dh_logs มี CLERK_FORWARD', ['actions' => $actions]);
    t_assert(in_array('DEPUTY_DISTRIBUTE', $actions, true), 'TC62 dh_logs มี DEPUTY_DISTRIBUTE', ['actions' => $actions]);

    $remainingTestRows = db_fetch_one('SELECT COUNT(*) AS c FROM dh_circulars WHERE subject LIKE ?', 's', $prefix . '%');
    t_assert((int) ($remainingTestRows['c'] ?? 0) >= 4, 'TC63 มีข้อมูลทดสอบถูกสร้างครบก่อน cleanup', ['count' => (int) ($remainingTestRows['c'] ?? 0)]);

} catch (Throwable $e) {
    $errors[] = [
        'name' => 'EXCEPTION',
        'context' => [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ],
    ];
} finally {
    foreach (array_reverse(array_values(array_unique($createdCircularIds))) as $circularId) {
        try {
            cleanup_circular((int) $circularId);
        } catch (Throwable $cleanupError) {
            $errors[] = [
                'name' => 'CLEANUP_CIRCULAR_FAILED',
                'context' => [
                    'circularID' => $circularId,
                    'message' => $cleanupError->getMessage(),
                ],
            ];
        }
    }

    foreach ($modifiedRoles as $pid => $roleId) {
        try {
            $stmt = db_query('UPDATE teacher SET roleID = ? WHERE pID = ?', 'is', $roleId, (string) $pid);
            mysqli_stmt_close($stmt);
        } catch (Throwable $restoreError) {
            $errors[] = [
                'name' => 'RESTORE_ROLE_FAILED',
                'context' => [
                    'pID' => $pid,
                    'roleID' => $roleId,
                    'message' => $restoreError->getMessage(),
                ],
            ];
        }
    }

    foreach ($insertedDutyLogIds as $dutyLogId) {
        try {
            $stmt = db_query('DELETE FROM dh_exec_duty_logs WHERE dutyLogID = ?', 'i', (int) $dutyLogId);
            mysqli_stmt_close($stmt);
        } catch (Throwable $cleanupDutyError) {
            $errors[] = [
                'name' => 'CLEANUP_DUTY_LOG_FAILED',
                'context' => [
                    'dutyLogID' => $dutyLogId,
                    'message' => $cleanupDutyError->getMessage(),
                ],
            ];
        }
    }
}

$passCount = 0;
$failCount = 0;
foreach ($results as $result) {
    if (!empty($result['ok'])) {
        $passCount++;
    } else {
        $failCount++;
    }
}

echo "CIRCULAR_RETEST_SUMMARY\n";
echo "TOTAL_ASSERTIONS=" . count($results) . "\n";
echo "PASSED=" . $passCount . "\n";
echo "FAILED=" . $failCount . "\n";

echo "\nASSERTIONS\n";
foreach ($results as $result) {
    $status = !empty($result['ok']) ? 'PASS' : 'FAIL';
    echo '[' . $status . '] ' . $result['name'];
    if (!empty($result['context'])) {
        echo ' | ' . json_encode($result['context'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    echo "\n";
}

if (!empty($errors)) {
    echo "\nERRORS\n";
    foreach ($errors as $error) {
        echo '[ERROR] ' . ($error['name'] ?? 'UNKNOWN') . ' | ' . json_encode($error['context'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
    }
}

exit(($failCount === 0 && empty($errors)) ? 0 : 1);

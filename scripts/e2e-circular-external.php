<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/config/constants.php';
require_once __DIR__ . '/../app/db/db.php';
require_once __DIR__ . '/../app/modules/system/system.php';
require_once __DIR__ . '/../app/modules/circulars/repository.php';
require_once __DIR__ . '/../app/modules/circulars/service.php';

/**
 * E2E sanity for external circular flow:
 * registry -> director -> registry -> deputy -> recipients
 */

$results = [];
$created_circular_ids = [];
$role_restore = null;

$add_result = static function (string $id, string $title, bool $ok, string $detail = '') use (&$results): void {
    $results[] = [
        'id' => $id,
        'title' => $title,
        'ok' => $ok,
        'detail' => $detail,
    ];
};

$must = static function (bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};

try {
    $registry_pid = '1829900159722';
    $registry_row = db_fetch_one(
        'SELECT pID, roleID, fName FROM teacher WHERE pID = ? AND status = 1 LIMIT 1',
        's',
        $registry_pid
    );
    $must($registry_row !== null, 'ไม่พบผู้ทดสอบสารบรรณ (registry test user)');

    $original_role_id = (int) ($registry_row['roleID'] ?? 0);

    if ($original_role_id !== 2) {
        db_execute('UPDATE teacher SET roleID = 2 WHERE pID = ?', 's', $registry_pid);
        $role_restore = ['pID' => $registry_pid, 'roleID' => $original_role_id];
    }

    $director_pid = (string) (system_get_current_director_pid() ?? '');
    $must($director_pid !== '', 'ไม่พบผู้อำนวยการปัจจุบัน');

    $acting_pid = (string) (system_get_acting_director_pid() ?? '');
    $director_inbox_type = ($acting_pid !== '' && $acting_pid === $director_pid)
        ? INBOX_TYPE_ACTING_PRINCIPAL
        : INBOX_TYPE_SPECIAL_PRINCIPAL;

    $deputy_pid = (string) (circular_find_deputy_by_fid(1) ?? '');
    $must($deputy_pid !== '', 'ไม่พบรองผู้อำนวยการ (fID=1)');

    $recipient_row = db_fetch_one(
        'SELECT pID, fName
         FROM teacher
         WHERE status = 1
           AND pID REGEXP "^[0-9]+$"
           AND pID NOT IN (?, ?, ?)
         ORDER BY fName ASC
         LIMIT 1',
        'sss',
        $registry_pid,
        $director_pid,
        $deputy_pid
    );
    $must($recipient_row !== null, 'ไม่พบผู้รับปลายทางสำหรับทดสอบ');
    $recipient_pid = (string) ($recipient_row['pID'] ?? '');

    $token = 'E2E-' . date('YmdHis') . '-' . (string) random_int(100, 999);
    $ext_book_no = 'EXT-' . $token;

    // CASE 1: Registry create + send to director.
    $circular_id = circular_create_external([
        'dh_year' => system_get_dh_year(),
        'circularType' => CIRCULAR_TYPE_EXTERNAL,
        'subject' => 'E2E External Circular ' . $token,
        'detail' => 'E2E create and send',
        'linkURL' => null,
        'fromFID' => 1,
        'extPriority' => 'ปกติ',
        'extBookNo' => $ext_book_no,
        'extIssuedDate' => date('Y-m-d'),
        'extFromText' => 'หน่วยงานทดสอบ E2E',
        'extGroupFID' => 1,
        'status' => EXTERNAL_STATUS_SUBMITTED,
        'createdByPID' => $registry_pid,
        'registryNote' => 'E2E CREATE',
    ], $registry_pid, true, [], $director_pid);
    $created_circular_ids[] = $circular_id;

    $created = circular_get($circular_id);
    $add_result(
        'C1',
        'สร้างและส่งเสนอผู้บริหาร',
        $created !== null && (string) ($created['status'] ?? '') === EXTERNAL_STATUS_PENDING_REVIEW,
        'status=' . (string) ($created['status'] ?? '')
    );

    $director_inbox = db_fetch_one(
        'SELECT inboxID, inboxType, isArchived
         FROM dh_circular_inboxes
         WHERE circularID = ? AND pID = ?
         ORDER BY inboxID DESC LIMIT 1',
        'is',
        $circular_id,
        $director_pid
    );
    $add_result(
        'C2',
        'ผู้บริหารได้รับใน inbox เฉพาะ',
        $director_inbox !== null
            && (string) ($director_inbox['inboxType'] ?? '') === $director_inbox_type
            && (int) ($director_inbox['isArchived'] ?? 1) === 0,
        'inboxType=' . (string) ($director_inbox['inboxType'] ?? '-')
    );

    // CASE 2: Registry recall before review.
    $recalled = circular_recall_external_before_review($circular_id, $registry_pid);
    $after_recall = circular_get($circular_id);
    $add_result(
        'C3',
        'สารบรรณดึงกลับก่อนพิจารณา',
        $recalled && $after_recall !== null && (string) ($after_recall['status'] ?? '') === EXTERNAL_STATUS_SUBMITTED,
        'status=' . (string) ($after_recall['status'] ?? '')
    );

    $active_director_inbox_count = db_fetch_one(
        'SELECT COUNT(*) AS total
         FROM dh_circular_inboxes
         WHERE circularID = ? AND inboxType IN (?, ?) AND isArchived = 0',
        'iss',
        $circular_id,
        INBOX_TYPE_SPECIAL_PRINCIPAL,
        INBOX_TYPE_ACTING_PRINCIPAL
    );
    $add_result(
        'C4',
        'ดึงกลับแล้ว inbox ผู้บริหารถูก archive',
        (int) ($active_director_inbox_count['total'] ?? 0) === 0
    );

    // CASE 3: Edit + resend.
    $edited = circular_edit_and_resend_external($circular_id, $registry_pid, [
        'subject' => 'E2E Edited Subject ' . $token,
        'detail' => 'E2E edit and resend',
        'linkURL' => 'https://example.org/e2e',
        'extPriority' => 'ด่วน',
        'extBookNo' => $ext_book_no . '-R1',
        'extIssuedDate' => date('Y-m-d'),
        'extFromText' => 'หน่วยงานทดสอบ E2E (แก้ไข)',
        'extGroupFID' => 1,
        'reviewerPID' => $director_pid,
    ], [], []);

    $after_edit = circular_get($circular_id);
    $add_result(
        'C5',
        'แก้ไขและส่งใหม่สำเร็จ',
        $edited
            && $after_edit !== null
            && (string) ($after_edit['status'] ?? '') === EXTERNAL_STATUS_PENDING_REVIEW
            && (string) ($after_edit['subject'] ?? '') === 'E2E Edited Subject ' . $token
            && (string) ($after_edit['extPriority'] ?? '') === 'ด่วน'
    );

    // CASE 4: Director review + return to registry.
    circular_director_review($circular_id, $director_pid, 'E2E Director reviewed', 1);
    $after_review = circular_get($circular_id);
    $add_result(
        'C6',
        'ผอ.พิจารณาแล้วส่งกลับสารบรรณ',
        $after_review !== null
            && (string) ($after_review['status'] ?? '') === EXTERNAL_STATUS_REVIEWED
            && (int) ($after_review['extGroupFID'] ?? 0) === 1
    );

    $registry_return_inbox = db_fetch_one(
        'SELECT COUNT(*) AS total
         FROM dh_circular_inboxes
         WHERE circularID = ? AND pID = ? AND inboxType = ? AND isArchived = 0',
        'iss',
        $circular_id,
        $registry_pid,
        INBOX_TYPE_SARABAN_RETURN
    );
    $add_result(
        'C7',
        'สารบรรณได้รับใน inbox หนังสือกลับจาก ผอ.',
        (int) ($registry_return_inbox['total'] ?? 0) > 0
    );

    // CASE 5: Registry forwards to deputy.
    $resolved_deputy_pid = (string) (circular_registry_forward_to_deputy($circular_id, $registry_pid, 1) ?? '');
    $after_forward = circular_get($circular_id);
    $add_result(
        'C8',
        'สารบรรณส่งต่อรองผู้อำนวยการ',
        $resolved_deputy_pid !== ''
            && $after_forward !== null
            && (string) ($after_forward['status'] ?? '') === EXTERNAL_STATUS_FORWARDED
    );

    $deputy_inbox = db_fetch_one(
        'SELECT COUNT(*) AS total
         FROM dh_circular_inboxes
         WHERE circularID = ? AND pID = ? AND inboxType = ? AND isArchived = 0',
        'iss',
        $circular_id,
        $resolved_deputy_pid,
        INBOX_TYPE_NORMAL
    );
    $add_result(
        'C9',
        'รองได้รับใน inbox ปกติ',
        (int) ($deputy_inbox['total'] ?? 0) > 0
    );

    // CASE 6: Deputy distributes to recipient.
    circular_deputy_distribute($circular_id, $resolved_deputy_pid, [
        'pids' => [$recipient_pid],
        'targets' => [
            ['targetType' => 'PERSON', 'pID' => $recipient_pid],
        ],
    ], 'E2E Deputy distribute');

    $recipient_inbox = db_fetch_one(
        'SELECT COUNT(*) AS total
         FROM dh_circular_inboxes
         WHERE circularID = ? AND pID = ? AND inboxType = ? AND isArchived = 0',
        'iss',
        $circular_id,
        $recipient_pid,
        INBOX_TYPE_NORMAL
    );
    $add_result(
        'C10',
        'ผู้รับปลายทางได้รับเอกสาร',
        (int) ($recipient_inbox['total'] ?? 0) > 0
    );

    // Negative checks.
    $recalled_after_flow = circular_recall_external_before_review($circular_id, $registry_pid);
    $add_result(
        'C11',
        'ห้ามดึงกลับหลังออกจากสถานะรอพิจารณา',
        $recalled_after_flow === false
    );

    $edit_after_flow = circular_edit_and_resend_external($circular_id, $registry_pid, [
        'subject' => 'invalid',
        'detail' => 'invalid',
        'linkURL' => null,
        'extPriority' => 'ปกติ',
        'extBookNo' => 'invalid-' . $token,
        'extIssuedDate' => date('Y-m-d'),
        'extFromText' => 'invalid',
        'extGroupFID' => 1,
        'reviewerPID' => $director_pid,
    ], [], []);
    $add_result(
        'C12',
        'ห้ามแก้ไขส่งใหม่เมื่อไม่ใช่สถานะรับเข้าแล้ว',
        $edit_after_flow === false
    );
} catch (Throwable $e) {
    $add_result('FATAL', 'เกิดข้อผิดพลาดระหว่างรัน E2E', false, $e->getMessage());
} finally {
    if (is_array($role_restore)) {
        db_execute(
            'UPDATE teacher SET roleID = ? WHERE pID = ?',
            'is',
            (int) $role_restore['roleID'],
            (string) $role_restore['pID']
        );
    }

    if (!empty($created_circular_ids)) {
        $ids = array_values(array_unique(array_filter(array_map('intval', $created_circular_ids), static function (int $id): bool {
            return $id > 0;
        })));

        if (!empty($ids)) {
            $placeholders = implode(', ', array_fill(0, count($ids), '?'));
            $types = str_repeat('i', count($ids));

            $stmt = db_query(
                'UPDATE dh_circulars SET deletedAt = NOW() WHERE circularID IN (' . $placeholders . ')',
                $types,
                ...$ids
            );
            mysqli_stmt_close($stmt);
        }
    }
}

$passed = 0;
$failed = 0;

foreach ($results as $row) {
    if ($row['ok']) {
        $passed++;
    } else {
        $failed++;
    }
}

echo "External Circular E2E Result\n";
echo "===========================\n";

foreach ($results as $row) {
    $icon = $row['ok'] ? '[PASS]' : '[FAIL]';
    echo $icon . ' ' . $row['id'] . ' ' . $row['title'];

    if ($row['detail'] !== '') {
        echo ' | ' . $row['detail'];
    }
    echo "\n";
}

echo "---------------------------\n";
echo 'Passed: ' . $passed . "\n";
echo 'Failed: ' . $failed . "\n";

exit($failed > 0 ? 1 : 0);

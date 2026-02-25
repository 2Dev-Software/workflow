<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/config/constants.php';
require_once __DIR__ . '/../app/db/db.php';
require_once __DIR__ . '/../app/modules/system/system.php';
require_once __DIR__ . '/../app/modules/circulars/repository.php';
require_once __DIR__ . '/../app/modules/circulars/service.php';

$base_url = rtrim((string) ($_ENV['APP_URL'] ?? 'http://127.0.0.1:8000'), '/');
$results = [];
$created_circular_ids = [];
$role_restore = null;

$add_result = static function (
    string $id,
    string $role,
    string $flow,
    string $url,
    bool $ok,
    string $detail = '',
    int $circular_id = 0
) use (&$results): void {
    $results[] = [
        'id' => $id,
        'role' => $role,
        'flow' => $flow,
        'url' => $url,
        'ok' => $ok,
        'detail' => $detail,
        'circular_id' => $circular_id,
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
    $must($registry_row !== null, 'ไม่พบผู้ใช้ทดสอบสารบรรณ');

    $original_role_id = (int) ($registry_row['roleID'] ?? 0);

    if ($original_role_id !== 2) {
        db_execute('UPDATE teacher SET roleID = 2 WHERE pID = ?', 's', $registry_pid);
        $role_restore = ['pID' => $registry_pid, 'roleID' => $original_role_id];
    }

    $director_pid = (string) (system_get_current_director_pid() ?? '');
    $must($director_pid !== '', 'ไม่พบ ผอ./รักษาการ ปัจจุบัน');
    $acting_pid = (string) (system_get_acting_director_pid() ?? '');
    $director_inbox_type = ($acting_pid !== '' && $acting_pid === $director_pid)
        ? INBOX_TYPE_ACTING_PRINCIPAL
        : INBOX_TYPE_SPECIAL_PRINCIPAL;

    $deputy_pid = (string) (circular_find_deputy_by_fid(1) ?? '');
    $must($deputy_pid !== '', 'ไม่พบรองผู้อำนวยการสำหรับทดสอบ');

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

    $token = 'UAT-' . date('YmdHis') . '-' . random_int(100, 999);
    $ext_book_no = 'EXT-' . $token;

    $circular_id = circular_create_external([
        'dh_year' => system_get_dh_year(),
        'circularType' => CIRCULAR_TYPE_EXTERNAL,
        'subject' => 'UAT External Circular ' . $token,
        'detail' => 'UAT role matrix',
        'linkURL' => null,
        'fromFID' => 1,
        'extPriority' => 'ปกติ',
        'extBookNo' => $ext_book_no,
        'extIssuedDate' => date('Y-m-d'),
        'extFromText' => 'หน่วยงานทดสอบ UAT',
        'extGroupFID' => 1,
        'status' => EXTERNAL_STATUS_SUBMITTED,
        'createdByPID' => $registry_pid,
        'registryNote' => 'UAT CREATE',
    ], $registry_pid, true, [], $director_pid);
    $created_circular_ids[] = $circular_id;

    $created = circular_get($circular_id);
    $add_result(
        'R01',
        'สารบรรณ',
        'สร้างและส่งเสนอผู้บริหาร',
        $base_url . '/outgoing-receive.php',
        $created !== null && (string) ($created['status'] ?? '') === EXTERNAL_STATUS_PENDING_REVIEW,
        'status=' . (string) ($created['status'] ?? ''),
        $circular_id
    );

    $registry_tracking = db_fetch_one(
        'SELECT COUNT(*) AS total
         FROM dh_circular_inboxes
         WHERE circularID = ? AND pID = ? AND inboxType = ?',
        'iss',
        $circular_id,
        $registry_pid,
        INBOX_TYPE_NORMAL
    );
    $add_result(
        'R02',
        'สารบรรณ',
        'กล่องกำลังเสนอมีรายการติดตาม',
        $base_url . '/outgoing-notice.php?box=clerk&type=external&read=all&sort=newest&view=table1',
        (int) ($registry_tracking['total'] ?? 0) > 0,
        'tracking=' . (string) ((int) ($registry_tracking['total'] ?? 0)),
        $circular_id
    );

    $director_inbox = db_fetch_one(
        'SELECT inboxID, inboxType, isArchived
         FROM dh_circular_inboxes
         WHERE circularID = ? AND pID = ?
         ORDER BY inboxID DESC
         LIMIT 1',
        'is',
        $circular_id,
        $director_pid
    );
    $director_inbox_id = (int) ($director_inbox['inboxID'] ?? 0);
    $add_result(
        'D01',
        'ผอ./รักษาการ',
        'ได้รับหนังสือในกล่องพิจารณา',
        $base_url . '/outgoing-notice.php?box=director&type=external&read=all&sort=newest&view=table1',
        $director_inbox !== null
            && (string) ($director_inbox['inboxType'] ?? '') === $director_inbox_type
            && (int) ($director_inbox['isArchived'] ?? 1) === 0,
        'inboxType=' . (string) ($director_inbox['inboxType'] ?? '-'),
        $circular_id
    );

    $recalled = circular_recall_external_before_review($circular_id, $registry_pid);
    $after_recall = circular_get($circular_id);
    $add_result(
        'R03',
        'สารบรรณ',
        'ดึงกลับก่อน ผอ.พิจารณา',
        $base_url . '/outgoing-receive.php?edit=' . $circular_id,
        $recalled && $after_recall !== null && (string) ($after_recall['status'] ?? '') === EXTERNAL_STATUS_SUBMITTED,
        'status=' . (string) ($after_recall['status'] ?? ''),
        $circular_id
    );

    $edited = circular_edit_and_resend_external($circular_id, $registry_pid, [
        'subject' => 'UAT External Circular Edited ' . $token,
        'detail' => 'UAT edit and resend',
        'linkURL' => 'https://example.org/uat',
        'extPriority' => 'ด่วน',
        'extBookNo' => $ext_book_no . '-R1',
        'extIssuedDate' => date('Y-m-d'),
        'extFromText' => 'หน่วยงานทดสอบ UAT (แก้ไข)',
        'extGroupFID' => 1,
        'reviewerPID' => $director_pid,
    ], [], []);
    $after_edit = circular_get($circular_id);
    $add_result(
        'R04',
        'สารบรรณ',
        'แก้ไขและส่งใหม่',
        $base_url . '/outgoing-receive.php?edit=' . $circular_id,
        $edited
            && $after_edit !== null
            && (string) ($after_edit['status'] ?? '') === EXTERNAL_STATUS_PENDING_REVIEW,
        'status=' . (string) ($after_edit['status'] ?? ''),
        $circular_id
    );

    circular_director_review($circular_id, $director_pid, 'UAT reviewed', 1);
    $after_review = circular_get($circular_id);
    $add_result(
        'D02',
        'ผอ./รักษาการ',
        'พิจารณาและส่งกลับสารบรรณ',
        $base_url . '/outgoing-view.php?inbox_id=' . $director_inbox_id,
        $after_review !== null && (string) ($after_review['status'] ?? '') === EXTERNAL_STATUS_REVIEWED,
        'status=' . (string) ($after_review['status'] ?? ''),
        $circular_id
    );

    $registry_return = db_fetch_one(
        'SELECT inboxID
         FROM dh_circular_inboxes
         WHERE circularID = ? AND pID = ? AND inboxType = ? AND isArchived = 0
         ORDER BY inboxID DESC
         LIMIT 1',
        'iss',
        $circular_id,
        $registry_pid,
        INBOX_TYPE_SARABAN_RETURN
    );
    $registry_return_inbox_id = (int) ($registry_return['inboxID'] ?? 0);
    $add_result(
        'R05',
        'สารบรรณ',
        'ได้รับกลับในกล่องพิจารณาแล้ว',
        $base_url . '/outgoing-notice.php?box=clerk_return&type=external&read=all&sort=newest&view=table1',
        $registry_return_inbox_id > 0,
        'inboxID=' . (string) $registry_return_inbox_id,
        $circular_id
    );

    $resolved_deputy_pid = (string) (circular_registry_forward_to_deputy($circular_id, $registry_pid, 1) ?? '');
    $after_forward = circular_get($circular_id);
    $add_result(
        'R06',
        'สารบรรณ',
        'ส่งต่อรองผู้อำนวยการ',
        $base_url . '/outgoing-view.php?inbox_id=' . $registry_return_inbox_id,
        $resolved_deputy_pid !== '' && $after_forward !== null && (string) ($after_forward['status'] ?? '') === EXTERNAL_STATUS_FORWARDED,
        'deputy=' . $resolved_deputy_pid,
        $circular_id
    );

    $deputy_inbox = db_fetch_one(
        'SELECT inboxID
         FROM dh_circular_inboxes
         WHERE circularID = ? AND pID = ? AND inboxType = ? AND isArchived = 0
         ORDER BY inboxID DESC
         LIMIT 1',
        'iss',
        $circular_id,
        $resolved_deputy_pid,
        INBOX_TYPE_NORMAL
    );
    $deputy_inbox_id = (int) ($deputy_inbox['inboxID'] ?? 0);
    $add_result(
        'V01',
        'รองผู้อำนวยการ',
        'ได้รับเรื่องเพื่อกระจายต่อ',
        $base_url . '/outgoing-view.php?inbox_id=' . $deputy_inbox_id,
        $deputy_inbox_id > 0,
        'inboxID=' . (string) $deputy_inbox_id,
        $circular_id
    );

    circular_deputy_distribute($circular_id, $resolved_deputy_pid, [
        'pids' => [$recipient_pid],
        'targets' => [
            ['targetType' => 'PERSON', 'pID' => $recipient_pid],
        ],
    ], 'UAT distribute');
    $recipient_inbox = db_fetch_one(
        'SELECT inboxID
         FROM dh_circular_inboxes
         WHERE circularID = ? AND pID = ? AND inboxType = ? AND isArchived = 0
         ORDER BY inboxID DESC
         LIMIT 1',
        'iss',
        $circular_id,
        $recipient_pid,
        INBOX_TYPE_NORMAL
    );
    $recipient_inbox_id = (int) ($recipient_inbox['inboxID'] ?? 0);
    $add_result(
        'V02',
        'รองผู้อำนวยการ',
        'กระจายหนังสือไปผู้รับปลายทาง',
        $base_url . '/outgoing-view.php?inbox_id=' . $deputy_inbox_id,
        $recipient_inbox_id > 0,
        'recipient_inbox=' . (string) $recipient_inbox_id,
        $circular_id
    );

    $recalled_after_flow = circular_recall_external_before_review($circular_id, $registry_pid);
    $add_result(
        'R07',
        'สารบรรณ',
        'ห้ามดึงกลับหลังพ้นขั้นรอพิจารณา',
        $base_url . '/outgoing-notice.php?box=clerk&type=external&read=all&sort=newest&view=table1',
        $recalled_after_flow === false,
        'recalled=' . ($recalled_after_flow ? 'true' : 'false'),
        $circular_id
    );

    $edit_after_flow = circular_edit_and_resend_external($circular_id, $registry_pid, [
        'subject' => 'invalid',
        'detail' => 'invalid',
        'linkURL' => null,
        'extPriority' => 'ปกติ',
        'extBookNo' => 'INVALID-' . $token,
        'extIssuedDate' => date('Y-m-d'),
        'extFromText' => 'invalid',
        'extGroupFID' => 1,
        'reviewerPID' => $director_pid,
    ], [], []);
    $add_result(
        'R08',
        'สารบรรณ',
        'ห้ามแก้ไขส่งใหม่เมื่อไม่ใช่สถานะรับเข้าแล้ว',
        $base_url . '/outgoing-receive.php?edit=' . $circular_id,
        $edit_after_flow === false,
        'edited=' . ($edit_after_flow ? 'true' : 'false'),
        $circular_id
    );

    $add_result(
        'G01',
        'ผู้รับปลายทาง',
        'ผู้รับเห็นรายการใน inbox ปกติ',
        $base_url . '/outgoing-notice.php?box=normal&type=external&read=all&sort=newest',
        $recipient_inbox_id > 0,
        'inboxID=' . (string) $recipient_inbox_id,
        $circular_id
    );
} catch (Throwable $e) {
    $add_result('FATAL', 'ระบบ', 'เกิดข้อผิดพลาดระหว่าง UAT', '-', false, $e->getMessage(), 0);
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

$role_summary = [];
$passed = 0;
$failed = 0;

foreach ($results as $row) {
    $role = (string) $row['role'];

    if (!isset($role_summary[$role])) {
        $role_summary[$role] = ['pass' => 0, 'fail' => 0, 'total' => 0];
    }
    $role_summary[$role]['total']++;

    if (!empty($row['ok'])) {
        $passed++;
        $role_summary[$role]['pass']++;
    } else {
        $failed++;
        $role_summary[$role]['fail']++;
    }
}

echo "UAT External Circular (Role-by-Role)\n";
echo "===================================\n";
echo "id\trole\tflow\tstatus\turl\tcircularID\tdetail\n";

foreach ($results as $row) {
    echo implode("\t", [
        (string) $row['id'],
        (string) $row['role'],
        (string) $row['flow'],
        !empty($row['ok']) ? 'PASS' : 'FAIL',
        (string) $row['url'],
        (string) ((int) ($row['circular_id'] ?? 0)),
        trim((string) ($row['detail'] ?? '')),
    ]) . "\n";
}

echo "-----------------------------------\n";
echo "Summary\tpass={$passed}\tfail={$failed}\ttotal=" . count($results) . "\n";
echo "RoleSummary\n";
echo "role\tpass\tfail\ttotal\n";

foreach ($role_summary as $role => $summary) {
    echo implode("\t", [
        $role,
        (string) $summary['pass'],
        (string) $summary['fail'],
        (string) $summary['total'],
    ]) . "\n";
}

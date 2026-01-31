<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/../system/system.php';
require_once __DIR__ . '/../audit/logger.php';
require_once __DIR__ . '/../../rbac/roles.php';
require_once __DIR__ . '/../../services/uploads.php';

if (!function_exists('circular_resolve_person_ids')) {
    function circular_resolve_person_ids(array $factionIds, array $roleIds, array $personIds): array
    {
        $connection = db_connection();
        $pids = [];

        $personIds = array_values(array_filter(array_map('trim', $personIds)));
        foreach ($personIds as $pid) {
            $pids[] = $pid;
        }

        $factionIds = array_values(array_filter(array_map('intval', $factionIds)));
        if (!empty($factionIds)) {
            $placeholders = implode(', ', array_fill(0, count($factionIds), '?'));
            $types = str_repeat('i', count($factionIds));
            $sql = 'SELECT pID FROM teacher WHERE status = 1 AND fID IN (' . $placeholders . ')';
            $stmt = db_query($sql, $types, ...$factionIds);
            $result = mysqli_stmt_get_result($stmt);
            while ($result && ($row = mysqli_fetch_assoc($result))) {
                $pids[] = (string) $row['pID'];
            }
            mysqli_stmt_close($stmt);
        }

        $roleIds = array_values(array_filter(array_map('intval', $roleIds)));
        if (!empty($roleIds)) {
            $placeholders = implode(', ', array_fill(0, count($roleIds), '?'));
            $types = str_repeat('i', count($roleIds));
            $sql = 'SELECT pID FROM teacher WHERE status = 1 AND roleID IN (' . $placeholders . ')';
            $stmt = db_query($sql, $types, ...$roleIds);
            $result = mysqli_stmt_get_result($stmt);
            while ($result && ($row = mysqli_fetch_assoc($result))) {
                $pids[] = (string) $row['pID'];
            }
            mysqli_stmt_close($stmt);
        }

        $pids = array_values(array_unique(array_filter($pids)));

        return $pids;
    }
}

if (!function_exists('circular_registry_pids')) {
    function circular_registry_pids(): array
    {
        $connection = db_connection();
        $registry_ids = rbac_resolve_role_ids($connection, ROLE_REGISTRY);
        if (empty($registry_ids)) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($registry_ids), '?'));
        $types = str_repeat('i', count($registry_ids));
        $sql = 'SELECT pID FROM teacher WHERE status = 1 AND roleID IN (' . $placeholders . ')';
        $stmt = db_query($sql, $types, ...$registry_ids);
        $result = mysqli_stmt_get_result($stmt);
        $pids = [];
        while ($result && ($row = mysqli_fetch_assoc($result))) {
            $pids[] = (string) $row['pID'];
        }
        mysqli_stmt_close($stmt);

        return array_values(array_unique($pids));
    }
}

if (!function_exists('circular_find_deputy_by_fid')) {
    function circular_find_deputy_by_fid(?int $fID): ?string
    {
        if ($fID === null || $fID <= 0) {
            $row = db_fetch_one('SELECT pID FROM teacher WHERE positionID = 2 AND status = 1 ORDER BY pID ASC LIMIT 1');
            return $row ? (string) $row['pID'] : null;
        }

        $row = db_fetch_one('SELECT pID FROM teacher WHERE positionID = 2 AND fID = ? AND status = 1 ORDER BY pID ASC LIMIT 1', 'i', $fID);
        if ($row && !empty($row['pID'])) {
            return (string) $row['pID'];
        }

        $row = db_fetch_one('SELECT pID FROM teacher WHERE positionID = 2 AND status = 1 ORDER BY pID ASC LIMIT 1');
        return $row ? (string) $row['pID'] : null;
    }
}

if (!function_exists('circular_create_internal')) {
    function circular_create_internal(array $data, array $recipients, array $files = []): int
    {
        $connection = db_connection();
        $sender = (string) $data['createdByPID'];

        db_begin();
        try {
            $circularID = circular_create_record($data);
            circular_add_route($circularID, 'CREATE', $sender, null, null, null);

            if (!empty($recipients['targets'])) {
                circular_add_recipients($circularID, $recipients['targets']);
            }

            $recipientPIDs = array_filter(array_unique(array_diff($recipients['pids'], [$sender])));
            if (!empty($recipientPIDs)) {
                circular_add_inboxes($circularID, $recipientPIDs, INBOX_TYPE_NORMAL, $sender);
                circular_add_route($circularID, 'SEND', $sender, null, null, null);
                circular_update_record($circularID, [
                    'status' => CIRCULAR_STATUS_SENT,
                    'updatedByPID' => $sender,
                ]);
            }

            if (!empty($files)) {
                upload_store_files($files, CIRCULAR_MODULE_NAME, CIRCULAR_ENTITY_NAME, (string) $circularID, $sender, [
                    'max_files' => 5,
                ]);
            }

            db_commit();
            audit_log('circulars', 'CREATE_INTERNAL', 'SUCCESS', 'dh_circulars', $circularID, null, ['type' => 'internal']);

            return $circularID;
        } catch (Throwable $e) {
            db_rollback();
            error_log('Circular internal create failed: ' . $e->getMessage());
            audit_log('circulars', 'CREATE_INTERNAL', 'FAIL', 'dh_circulars', null, $e->getMessage());
            throw $e;
        }
    }
}

if (!function_exists('circular_create_external')) {
    function circular_create_external(array $data, string $registryPID, bool $sendNow, array $files = []): int
    {
        db_begin();
        try {
            $circularID = circular_create_record($data);
            circular_add_route($circularID, 'CREATE', $registryPID, null, null, null);

            if (!empty($files)) {
                upload_store_files($files, CIRCULAR_MODULE_NAME, CIRCULAR_ENTITY_NAME, (string) $circularID, $registryPID, [
                    'max_files' => 5,
                ]);
            }

            if ($sendNow) {
                $directorPID = system_get_current_director_pid();
                if ($directorPID) {
                    circular_add_inboxes($circularID, [$directorPID], INBOX_TYPE_DIRECTOR, $registryPID);
                }
                circular_update_record($circularID, [
                    'status' => CIRCULAR_STATUS_SENT,
                    'updatedByPID' => $registryPID,
                ]);
                circular_add_route($circularID, 'SEND', $registryPID, $directorPID, null, null);
            }

            db_commit();
            audit_log('circulars', 'CREATE_EXTERNAL', 'SUCCESS', 'dh_circulars', $circularID, null, ['send' => $sendNow]);

            return $circularID;
        } catch (Throwable $e) {
            db_rollback();
            error_log('Circular external create failed: ' . $e->getMessage());
            audit_log('circulars', 'CREATE_EXTERNAL', 'FAIL', 'dh_circulars', null, $e->getMessage());
            throw $e;
        }
    }
}

if (!function_exists('circular_director_review')) {
    function circular_director_review(int $circularID, string $directorPID, ?string $comment, ?int $newFID): void
    {
        db_begin();
        try {
            $update = [
                'status' => CIRCULAR_STATUS_RETURNED,
                'updatedByPID' => $directorPID,
            ];
            if ($newFID !== null && $newFID > 0) {
                $update['extGroupFID'] = $newFID;
            }
            circular_update_record($circularID, $update);
            circular_add_route($circularID, 'RETURN', $directorPID, null, $newFID, $comment);

            $registryPIDs = circular_registry_pids();
            if (!empty($registryPIDs)) {
                circular_add_inboxes($circularID, $registryPIDs, INBOX_TYPE_CLERK_RETURN, $directorPID);
            }

            db_commit();
            audit_log('circulars', 'DIRECTOR_REVIEW', 'SUCCESS', 'dh_circulars', $circularID, null, ['note' => $comment]);
        } catch (Throwable $e) {
            db_rollback();
            error_log('Director review failed: ' . $e->getMessage());
            audit_log('circulars', 'DIRECTOR_REVIEW', 'FAIL', 'dh_circulars', $circularID, $e->getMessage());
            throw $e;
        }
    }
}

if (!function_exists('circular_registry_forward_to_deputy')) {
    function circular_registry_forward_to_deputy(int $circularID, string $registryPID, ?int $fID): ?string
    {
        db_begin();
        try {
            $deputyPID = circular_find_deputy_by_fid($fID);
            if (!$deputyPID) {
                throw new RuntimeException('ไม่พบรองผู้อำนวยการตามฝ่ายที่ระบุ');
            }
            circular_add_inboxes($circularID, [$deputyPID], INBOX_TYPE_NORMAL, $registryPID);
            circular_update_record($circularID, [
                'status' => CIRCULAR_STATUS_FORWARDED,
                'updatedByPID' => $registryPID,
            ]);
            circular_add_route($circularID, 'FORWARD', $registryPID, $deputyPID, $fID, null);
            db_commit();
            audit_log('circulars', 'CLERK_FORWARD', 'SUCCESS', 'dh_circulars', $circularID, null, ['deputy' => $deputyPID]);

            return $deputyPID;
        } catch (Throwable $e) {
            db_rollback();
            error_log('Registry forward failed: ' . $e->getMessage());
            audit_log('circulars', 'CLERK_FORWARD', 'FAIL', 'dh_circulars', $circularID, $e->getMessage());
            throw $e;
        }
    }
}

if (!function_exists('circular_deputy_distribute')) {
    function circular_deputy_distribute(int $circularID, string $deputyPID, array $recipients, ?string $note = null): void
    {
        db_begin();
        try {
            if (!empty($recipients['targets'])) {
                circular_add_recipients($circularID, $recipients['targets']);
            }

            $recipientPIDs = array_filter(array_unique(array_diff($recipients['pids'], [$deputyPID])));
            if (empty($recipientPIDs)) {
                throw new RuntimeException('กรุณาเลือกผู้รับอย่างน้อย 1 คน');
            }
            if (!empty($recipientPIDs)) {
                circular_add_inboxes($circularID, $recipientPIDs, INBOX_TYPE_NORMAL, $deputyPID);
            }
            circular_update_record($circularID, [
                'status' => CIRCULAR_STATUS_APPROVED,
                'updatedByPID' => $deputyPID,
            ]);
            circular_add_route($circularID, 'APPROVE', $deputyPID, null, null, $note);

            db_commit();
            audit_log('circulars', 'DEPUTY_DISTRIBUTE', 'SUCCESS', 'dh_circulars', $circularID);
        } catch (Throwable $e) {
            db_rollback();
            error_log('Deputy distribute failed: ' . $e->getMessage());
            audit_log('circulars', 'DEPUTY_DISTRIBUTE', 'FAIL', 'dh_circulars', $circularID, $e->getMessage());
            throw $e;
        }
    }
}

if (!function_exists('circular_recall_internal')) {
    function circular_recall_internal(int $circularID, string $senderPID): bool
    {
        $owner = db_fetch_one('SELECT createdByPID, circularType FROM dh_circulars WHERE circularID = ? LIMIT 1', 'i', $circularID);
        if (!$owner || (string) ($owner['createdByPID'] ?? '') !== $senderPID || (string) ($owner['circularType'] ?? '') !== CIRCULAR_TYPE_INTERNAL) {
            return false;
        }

        $row = db_fetch_one('SELECT COUNT(*) AS readCount FROM dh_circular_inboxes WHERE circularID = ? AND isRead = 1', 'i', $circularID);
        $readCount = $row ? (int) $row['readCount'] : 0;
        if ($readCount > 0) {
            return false;
        }

        db_begin();
        try {
            circular_update_record($circularID, [
                'status' => CIRCULAR_STATUS_RECALLED,
                'updatedByPID' => $senderPID,
            ]);
            $stmt = db_query('UPDATE dh_circular_inboxes SET isArchived = 1, archivedAt = NOW() WHERE circularID = ?', 'i', $circularID);
            mysqli_stmt_close($stmt);
            circular_add_route($circularID, 'RECALL', $senderPID, null, null, null);
            db_commit();
            audit_log('circulars', 'RECALL', 'SUCCESS', 'dh_circulars', $circularID);
            return true;
        } catch (Throwable $e) {
            db_rollback();
            error_log('Recall failed: ' . $e->getMessage());
            audit_log('circulars', 'RECALL', 'FAIL', 'dh_circulars', $circularID, $e->getMessage());
            throw $e;
        }
    }
}

if (!function_exists('circular_forward')) {
    function circular_forward(int $circularID, string $fromPID, array $recipients): void
    {
        db_begin();
        try {
            if (!empty($recipients['targets'])) {
                circular_add_recipients($circularID, $recipients['targets']);
            }
            $recipientPIDs = array_filter(array_unique(array_diff($recipients['pids'], [$fromPID])));
            if (empty($recipientPIDs)) {
                throw new RuntimeException('กรุณาเลือกผู้รับอย่างน้อย 1 คน');
            }
            if (!empty($recipientPIDs)) {
                circular_add_inboxes($circularID, $recipientPIDs, INBOX_TYPE_NORMAL, $fromPID);
            }
            circular_add_route($circularID, 'FORWARD', $fromPID, null, null, null);
            db_commit();
            audit_log('circulars', 'FORWARD', 'SUCCESS', 'dh_circulars', $circularID);
        } catch (Throwable $e) {
            db_rollback();
            error_log('Forward failed: ' . $e->getMessage());
            audit_log('circulars', 'FORWARD', 'FAIL', 'dh_circulars', $circularID, $e->getMessage());
            throw $e;
        }
    }
}

if (!function_exists('circular_resend_internal')) {
    function circular_resend_internal(int $circularID, string $senderPID): bool
    {
        $circular = circular_get($circularID);
        if (!$circular || (string) ($circular['createdByPID'] ?? '') !== $senderPID) {
            return false;
        }
        if ((string) ($circular['circularType'] ?? '') !== CIRCULAR_TYPE_INTERNAL) {
            return false;
        }

        $targets = circular_get_recipient_targets($circularID);
        if (empty($targets)) {
            return false;
        }

        $factions = [];
        $roles = [];
        $persons = [];
        foreach ($targets as $target) {
            $type = (string) ($target['targetType'] ?? '');
            if ($type === 'UNIT' && !empty($target['fID'])) {
                $factions[] = (int) $target['fID'];
            } elseif ($type === 'ROLE' && !empty($target['roleID'])) {
                $roles[] = (int) $target['roleID'];
            } elseif ($type === 'PERSON' && !empty($target['pID'])) {
                $persons[] = (string) $target['pID'];
            }
        }

        $recipientPIDs = circular_resolve_person_ids($factions, $roles, $persons);
        if (empty($recipientPIDs)) {
            return false;
        }

        db_begin();
        try {
            $stmt = db_query('DELETE FROM dh_circular_inboxes WHERE circularID = ?', 'i', $circularID);
            mysqli_stmt_close($stmt);
            circular_add_inboxes($circularID, $recipientPIDs, INBOX_TYPE_NORMAL, $senderPID);
            circular_update_record($circularID, [
                'status' => CIRCULAR_STATUS_SENT,
                'updatedByPID' => $senderPID,
            ]);
            circular_add_route($circularID, 'SEND', $senderPID, null, null, 'RESEND');
            db_commit();
            audit_log('circulars', 'RESEND', 'SUCCESS', 'dh_circulars', $circularID);
            return true;
        } catch (Throwable $e) {
            db_rollback();
            error_log('Resend failed: ' . $e->getMessage());
            audit_log('circulars', 'RESEND', 'FAIL', 'dh_circulars', $circularID, $e->getMessage());
            throw $e;
        }
    }
}

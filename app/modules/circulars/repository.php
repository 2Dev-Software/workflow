<?php
declare(strict_types=1);

require_once __DIR__ . '/../../db/db.php';

const CIRCULAR_MODULE_NAME = 'circulars';
const CIRCULAR_ENTITY_NAME = 'dh_circulars';

if (!function_exists('circular_create_record')) {
    function circular_create_record(array $data): int
    {
        $stmt = db_query(
            'INSERT INTO dh_circulars
                (dh_year, circularType, subject, detail, linkURL, fromFID, extPriority, extBookNo, extIssuedDate, extFromText, extGroupFID, status, createdByPID, updatedByPID)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            'issssisssssiss',
            (int) $data['dh_year'],
            (string) $data['circularType'],
            (string) $data['subject'],
            $data['detail'] ?? null,
            $data['linkURL'] ?? null,
            $data['fromFID'] ?? null,
            $data['extPriority'] ?? null,
            $data['extBookNo'] ?? null,
            $data['extIssuedDate'] ?? null,
            $data['extFromText'] ?? null,
            $data['extGroupFID'] ?? null,
            (string) $data['status'],
            (string) $data['createdByPID'],
            $data['updatedByPID'] ?? null
        );
        $id = db_last_insert_id();
        mysqli_stmt_close($stmt);

        return $id;
    }
}

if (!function_exists('circular_update_record')) {
    function circular_update_record(int $circularID, array $data): void
    {
        $fields = [];
        $params = [];
        $types = '';

        foreach ($data as $field => $value) {
            $fields[] = $field . ' = ?';
            if (is_int($value)) {
                $types .= 'i';
            } else {
                $types .= 's';
            }
            $params[] = $value;
        }

        if (empty($fields)) {
            return;
        }

        $types .= 'i';
        $params[] = $circularID;

        $sql = 'UPDATE dh_circulars SET ' . implode(', ', $fields) . ' WHERE circularID = ?';
        $stmt = db_query($sql, $types, ...$params);
        mysqli_stmt_close($stmt);
    }
}

if (!function_exists('circular_add_route')) {
    function circular_add_route(int $circularID, string $action, ?string $fromPID, ?string $toPID, ?int $toFID, ?string $note): void
    {
        $stmt = db_query(
            'INSERT INTO dh_circular_routes (circularID, action, fromPID, toPID, toFID, note) VALUES (?, ?, ?, ?, ?, ?)',
            'isssis',
            $circularID,
            $action,
            $fromPID,
            $toPID,
            $toFID,
            $note
        );
        mysqli_stmt_close($stmt);
    }
}

if (!function_exists('circular_add_recipients')) {
    function circular_add_recipients(int $circularID, array $targets): void
    {
        foreach ($targets as $target) {
            $stmt = db_query(
                'INSERT INTO dh_circular_recipients (circularID, targetType, fID, roleID, pID, isCc) VALUES (?, ?, ?, ?, ?, ?)',
                'isiisi',
                $circularID,
                (string) $target['targetType'],
                $target['fID'] ?? null,
                $target['roleID'] ?? null,
                $target['pID'] ?? null,
                $target['isCc'] ?? 0
            );
            mysqli_stmt_close($stmt);
        }
    }
}

if (!function_exists('circular_add_inboxes')) {
    function circular_add_inboxes(int $circularID, array $recipientPIDs, string $inboxType, ?string $deliveredByPID): void
    {
        $recipientPIDs = array_values(array_unique(array_filter(array_map('trim', $recipientPIDs))));
        if (empty($recipientPIDs)) {
            return;
        }

        foreach ($recipientPIDs as $pid) {
            $stmt = db_query(
                'INSERT INTO dh_circular_inboxes (circularID, pID, inboxType, deliveredByPID) VALUES (?, ?, ?, ?)',
                'isss',
                $circularID,
                $pid,
                $inboxType,
                $deliveredByPID
            );
            mysqli_stmt_close($stmt);
        }
    }
}

if (!function_exists('circular_get_inbox')) {
    function circular_get_inbox(string $pID, string $inboxType = 'NORMAL', bool $archived = false): array
    {
        $archivedFlag = $archived ? 1 : 0;
        $sql = 'SELECT i.inboxID, i.isRead, i.readAt, i.isArchived, i.deliveredAt,
                c.circularID, c.circularType, c.subject, c.status, c.createdAt,
                t.fName AS senderName
            FROM dh_circular_inboxes AS i
            INNER JOIN dh_circulars AS c ON i.circularID = c.circularID
            LEFT JOIN teacher AS t ON c.createdByPID = t.pID
            WHERE i.pID = ? AND i.inboxType = ? AND i.isArchived = ?
            ORDER BY i.deliveredAt DESC, i.inboxID DESC';

        return db_fetch_all($sql, 'ssi', $pID, $inboxType, $archivedFlag);
    }
}

if (!function_exists('circular_get_inbox_item')) {
    function circular_get_inbox_item(int $inboxID, string $pID): ?array
    {
        $sql = 'SELECT i.inboxID, i.circularID, i.isRead, i.readAt, i.inboxType,
                c.circularType, c.subject, c.detail, c.linkURL, c.fromFID, c.extPriority, c.extBookNo, c.extIssuedDate,
                c.extFromText, c.extGroupFID, c.status, c.createdByPID, c.createdAt,
                t.fName AS senderName
            FROM dh_circular_inboxes AS i
            INNER JOIN dh_circulars AS c ON i.circularID = c.circularID
            LEFT JOIN teacher AS t ON c.createdByPID = t.pID
            WHERE i.inboxID = ? AND i.pID = ?
            LIMIT 1';

        return db_fetch_one($sql, 'is', $inboxID, $pID);
    }
}

if (!function_exists('circular_mark_read')) {
    function circular_mark_read(int $inboxID, string $pID): void
    {
        $stmt = db_query(
            'UPDATE dh_circular_inboxes SET isRead = 1, readAt = NOW() WHERE inboxID = ? AND pID = ? AND isRead = 0',
            'is',
            $inboxID,
            $pID
        );
        mysqli_stmt_close($stmt);
    }
}

if (!function_exists('circular_archive_inbox')) {
    function circular_archive_inbox(int $inboxID, string $pID): void
    {
        $stmt = db_query(
            'UPDATE dh_circular_inboxes SET isArchived = 1, archivedAt = NOW() WHERE inboxID = ? AND pID = ?',
            'is',
            $inboxID,
            $pID
        );
        mysqli_stmt_close($stmt);
    }
}

if (!function_exists('circular_unarchive_inbox')) {
    function circular_unarchive_inbox(int $inboxID, string $pID): void
    {
        $stmt = db_query(
            'UPDATE dh_circular_inboxes SET isArchived = 0, archivedAt = NULL WHERE inboxID = ? AND pID = ?',
            'is',
            $inboxID,
            $pID
        );
        mysqli_stmt_close($stmt);
    }
}

if (!function_exists('circular_get')) {
    function circular_get(int $circularID): ?array
    {
        $sql = 'SELECT c.*, t.fName AS senderName
            FROM dh_circulars AS c
            LEFT JOIN teacher AS t ON c.createdByPID = t.pID
            WHERE c.circularID = ?
            LIMIT 1';
        return db_fetch_one($sql, 'i', $circularID);
    }
}

if (!function_exists('circular_list_sent')) {
    function circular_list_sent(string $pID): array
    {
        $sql = 'SELECT c.circularID, c.circularType, c.subject, c.status, c.createdAt,
                (SELECT COUNT(*) FROM dh_circular_inboxes WHERE circularID = c.circularID) AS recipientCount,
                (SELECT COUNT(*) FROM dh_circular_inboxes WHERE circularID = c.circularID AND isRead = 1) AS readCount
            FROM dh_circulars AS c
            WHERE c.createdByPID = ? AND c.deletedAt IS NULL
            ORDER BY c.createdAt DESC, c.circularID DESC';
        return db_fetch_all($sql, 's', $pID);
    }
}

if (!function_exists('circular_get_read_stats')) {
    function circular_get_read_stats(int $circularID): array
    {
        $sql = 'SELECT i.pID, i.isRead, i.readAt, t.fName
            FROM dh_circular_inboxes AS i
            INNER JOIN teacher AS t ON i.pID = t.pID
            WHERE i.circularID = ?
            ORDER BY t.fName ASC';
        return db_fetch_all($sql, 'i', $circularID);
    }
}

if (!function_exists('circular_get_recipient_targets')) {
    function circular_get_recipient_targets(int $circularID): array
    {
        $sql = 'SELECT targetType, fID, roleID, pID, isCc FROM dh_circular_recipients WHERE circularID = ?';
        return db_fetch_all($sql, 'i', $circularID);
    }
}

if (!function_exists('circular_get_attachments')) {
    function circular_get_attachments(int $circularID): array
    {
        $sql = 'SELECT f.fileID, f.fileName, f.filePath, f.mimeType, f.fileSize
            FROM dh_file_refs AS r
            INNER JOIN dh_files AS f ON r.fileID = f.fileID
            WHERE r.moduleName = ? AND r.entityName = ? AND r.entityID = ? AND f.deletedAt IS NULL
            ORDER BY r.refID ASC';
        return db_fetch_all($sql, 'sss', CIRCULAR_MODULE_NAME, CIRCULAR_ENTITY_NAME, (string) $circularID);
    }
}

if (!function_exists('circular_get_announcements')) {
    function circular_get_announcements(int $limit = 10): array
    {
        if (!db_table_exists(db_connection(), 'dh_circular_announcements')) {
            return [];
        }

        $sql = 'SELECT a.announcementID, a.selectedAt, c.circularID, c.subject
            FROM dh_circular_announcements AS a
            INNER JOIN dh_circulars AS c ON a.circularID = c.circularID
            WHERE a.isActive = 1 AND c.deletedAt IS NULL
            ORDER BY a.selectedAt DESC
            LIMIT ' . (int) $limit;
        return db_fetch_all($sql);
    }
}

if (!function_exists('circular_set_announcement')) {
    function circular_set_announcement(int $circularID, string $selectedByPID): void
    {
        if (!db_table_exists(db_connection(), 'dh_circular_announcements')) {
            return;
        }

        $stmt = db_query(
            'INSERT INTO dh_circular_announcements (circularID, selectedByPID, isActive) VALUES (?, ?, 1)',
            'is',
            $circularID,
            $selectedByPID
        );
        mysqli_stmt_close($stmt);
    }
}

if (!function_exists('circular_remove_announcement')) {
    function circular_remove_announcement(int $announcementID, string $selectedByPID): void
    {
        if (!db_table_exists(db_connection(), 'dh_circular_announcements')) {
            return;
        }

        $stmt = db_query(
            'UPDATE dh_circular_announcements SET isActive = 0 WHERE announcementID = ?',
            'i',
            $announcementID
        );
        mysqli_stmt_close($stmt);
    }
}

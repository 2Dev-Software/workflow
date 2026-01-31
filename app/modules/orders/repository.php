<?php
declare(strict_types=1);

require_once __DIR__ . '/../../db/db.php';

const ORDER_MODULE_NAME = 'orders';
const ORDER_ENTITY_NAME = 'dh_orders';

if (!function_exists('order_prepare_search')) {
    function order_prepare_search(?string $term): array
    {
        $term = trim((string) $term);
        if ($term === '') {
            return ['', ''];
        }

        $max_len = 120;
        if (function_exists('mb_substr')) {
            $term = (string) mb_substr($term, 0, $max_len);
        } else {
            $term = (string) substr($term, 0, $max_len);
        }

        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);
        return [$term, '%' . $escaped . '%'];
    }
}

if (!function_exists('order_build_inbox_filters')) {
    function order_build_inbox_filters(string $pID, bool $archived, ?string $search, ?string $read_filter, bool $include_read_filter = true): array
    {
        $archivedFlag = $archived ? 1 : 0;
        $conditions = ['i.pID = ?', 'i.isArchived = ?'];
        $types = 'si';
        $params = [$pID, $archivedFlag];

        [$term, $like] = order_prepare_search($search);
        if ($term !== '') {
            $conditions[] = '(o.orderNo LIKE ? ESCAPE \'\\\\\' OR o.subject LIKE ? ESCAPE \'\\\\\' OR t.fName LIKE ? ESCAPE \'\\\\\')';
            $types .= 'sss';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if ($include_read_filter) {
            if ($read_filter === 'read') {
                $conditions[] = 'i.isRead = 1';
            } elseif ($read_filter === 'unread') {
                $conditions[] = 'i.isRead = 0';
            }
        }

        return [
            'where' => implode(' AND ', $conditions),
            'types' => $types,
            'params' => $params,
        ];
    }
}

if (!function_exists('order_create_record')) {
    function order_create_record(array $data): int
    {
        $stmt = db_query(
            'INSERT INTO dh_orders (dh_year, orderNo, orderSeq, subject, detail, status, createdByPID, updatedByPID)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            'isisssss',
            (int) $data['dh_year'],
            (string) $data['orderNo'],
            (int) $data['orderSeq'],
            (string) $data['subject'],
            $data['detail'] ?? null,
            (string) $data['status'],
            (string) $data['createdByPID'],
            $data['updatedByPID'] ?? null
        );
        $id = db_last_insert_id();
        mysqli_stmt_close($stmt);

        return $id;
    }
}

if (!function_exists('order_update_record')) {
    function order_update_record(int $orderID, array $data): void
    {
        $fields = [];
        $params = [];
        $types = '';

        foreach ($data as $field => $value) {
            $fields[] = $field . ' = ?';
            $types .= is_int($value) ? 'i' : 's';
            $params[] = $value;
        }

        if (empty($fields)) {
            return;
        }

        $types .= 'i';
        $params[] = $orderID;

        $sql = 'UPDATE dh_orders SET ' . implode(', ', $fields) . ' WHERE orderID = ?';
        $stmt = db_query($sql, $types, ...$params);
        mysqli_stmt_close($stmt);
    }
}

if (!function_exists('order_add_route')) {
    function order_add_route(int $orderID, string $action, ?string $fromPID, ?string $toPID, ?string $note): void
    {
        $stmt = db_query(
            'INSERT INTO dh_order_routes (orderID, action, fromPID, toPID, note) VALUES (?, ?, ?, ?, ?)',
            'issss',
            $orderID,
            $action,
            $fromPID,
            $toPID,
            $note
        );
        mysqli_stmt_close($stmt);
    }
}

if (!function_exists('order_add_recipients')) {
    function order_add_recipients(int $orderID, array $targets): void
    {
        foreach ($targets as $target) {
            $stmt = db_query(
                'INSERT INTO dh_order_recipients (orderID, targetType, fID, roleID, pID, isCc) VALUES (?, ?, ?, ?, ?, ?)',
                'isiisi',
                $orderID,
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

if (!function_exists('order_add_inboxes')) {
    function order_add_inboxes(int $orderID, array $recipientPIDs, ?string $deliveredByPID): void
    {
        $recipientPIDs = array_values(array_unique(array_filter(array_map('trim', $recipientPIDs))));
        if (empty($recipientPIDs)) {
            return;
        }

        foreach ($recipientPIDs as $pid) {
            $stmt = db_query(
                'INSERT INTO dh_order_inboxes (orderID, pID, deliveredByPID) VALUES (?, ?, ?)',
                'iss',
                $orderID,
                $pid,
                $deliveredByPID
            );
            mysqli_stmt_close($stmt);
        }
    }
}

if (!function_exists('order_get')) {
    function order_get(int $orderID): ?array
    {
        $sql = 'SELECT o.*, t.fName AS creatorName
            FROM dh_orders AS o
            LEFT JOIN teacher AS t ON o.createdByPID = t.pID
            WHERE o.orderID = ?
            LIMIT 1';
        return db_fetch_one($sql, 'i', $orderID);
    }
}

if (!function_exists('order_list_drafts')) {
    function order_list_drafts(string $pID): array
    {
        $sql = 'SELECT o.orderID, o.orderNo, o.subject, o.status, o.createdAt,
                (SELECT COUNT(*) FROM dh_order_inboxes WHERE orderID = o.orderID) AS recipientCount,
                (SELECT COUNT(*) FROM dh_order_inboxes WHERE orderID = o.orderID AND isRead = 1) AS readCount
            FROM dh_orders AS o
            WHERE o.createdByPID = ? AND o.deletedAt IS NULL
            ORDER BY o.createdAt DESC, o.orderID DESC';
        return db_fetch_all($sql, 's', $pID);
    }
}

if (!function_exists('order_list_inbox')) {
    function order_list_inbox(string $pID, bool $archived = false): array
    {
        $archivedFlag = $archived ? 1 : 0;
        $sql = 'SELECT i.inboxID, i.isRead, i.readAt, i.isArchived, i.deliveredAt,
                o.orderID, o.orderNo, o.subject, o.status, o.createdAt,
                t.fName AS senderName
            FROM dh_order_inboxes AS i
            INNER JOIN dh_orders AS o ON i.orderID = o.orderID
            LEFT JOIN teacher AS t ON o.createdByPID = t.pID
            WHERE i.pID = ? AND i.isArchived = ?
            ORDER BY i.deliveredAt DESC, i.inboxID DESC';
        return db_fetch_all($sql, 'si', $pID, $archivedFlag);
    }
}

if (!function_exists('order_count_inbox')) {
    function order_count_inbox(string $pID, bool $archived = false): int
    {
        $archivedFlag = $archived ? 1 : 0;
        $row = db_fetch_one('SELECT COUNT(*) AS total FROM dh_order_inboxes WHERE pID = ? AND isArchived = ?', 'si', $pID, $archivedFlag);
        return (int) ($row['total'] ?? 0);
    }
}

if (!function_exists('order_list_inbox_page')) {
    function order_list_inbox_page(string $pID, bool $archived, int $limit, int $offset): array
    {
        $archivedFlag = $archived ? 1 : 0;
        $limit = max(1, $limit);
        $offset = max(0, $offset);
        $sql = 'SELECT i.inboxID, i.isRead, i.readAt, i.isArchived, i.deliveredAt,
                o.orderID, o.orderNo, o.subject, o.status, o.createdAt,
                t.fName AS senderName
            FROM dh_order_inboxes AS i
            INNER JOIN dh_orders AS o ON i.orderID = o.orderID
            LEFT JOIN teacher AS t ON o.createdByPID = t.pID
            WHERE i.pID = ? AND i.isArchived = ?
            ORDER BY i.deliveredAt DESC, i.inboxID DESC
            LIMIT ? OFFSET ?';
        return db_fetch_all($sql, 'siii', $pID, $archivedFlag, $limit, $offset);
    }
}

if (!function_exists('order_count_inbox_filtered')) {
    function order_count_inbox_filtered(string $pID, bool $archived, ?string $search, ?string $read_filter): int
    {
        $filter = order_build_inbox_filters($pID, $archived, $search, $read_filter, true);
        $sql = 'SELECT COUNT(*) AS total
            FROM dh_order_inboxes AS i
            INNER JOIN dh_orders AS o ON i.orderID = o.orderID
            LEFT JOIN teacher AS t ON o.createdByPID = t.pID
            WHERE ' . $filter['where'];
        $row = db_fetch_one($sql, $filter['types'], ...$filter['params']);
        return (int) ($row['total'] ?? 0);
    }
}

if (!function_exists('order_list_inbox_page_filtered')) {
    function order_list_inbox_page_filtered(string $pID, bool $archived, ?string $search, ?string $read_filter, int $limit, int $offset): array
    {
        $limit = max(1, $limit);
        $offset = max(0, $offset);
        $filter = order_build_inbox_filters($pID, $archived, $search, $read_filter, true);
        $sql = 'SELECT i.inboxID, i.isRead, i.readAt, i.isArchived, i.deliveredAt,
                o.orderID, o.orderNo, o.subject, o.status, o.createdAt,
                t.fName AS senderName
            FROM dh_order_inboxes AS i
            INNER JOIN dh_orders AS o ON i.orderID = o.orderID
            LEFT JOIN teacher AS t ON o.createdByPID = t.pID
            WHERE ' . $filter['where'] . '
            ORDER BY i.deliveredAt DESC, i.inboxID DESC
            LIMIT ? OFFSET ?';
        $types = $filter['types'] . 'ii';
        $params = array_merge($filter['params'], [$limit, $offset]);

        return db_fetch_all($sql, $types, ...$params);
    }
}

if (!function_exists('order_inbox_read_summary')) {
    function order_inbox_read_summary(string $pID, bool $archived, ?string $search): array
    {
        $filter = order_build_inbox_filters($pID, $archived, $search, null, false);
        $sql = 'SELECT COUNT(*) AS total,
                SUM(CASE WHEN i.isRead = 1 THEN 1 ELSE 0 END) AS readCount,
                SUM(CASE WHEN i.isRead = 0 THEN 1 ELSE 0 END) AS unreadCount
            FROM dh_order_inboxes AS i
            INNER JOIN dh_orders AS o ON i.orderID = o.orderID
            LEFT JOIN teacher AS t ON o.createdByPID = t.pID
            WHERE ' . $filter['where'];
        $row = db_fetch_one($sql, $filter['types'], ...$filter['params']);

        return [
            'total' => (int) ($row['total'] ?? 0),
            'read' => (int) ($row['readCount'] ?? 0),
            'unread' => (int) ($row['unreadCount'] ?? 0),
        ];
    }
}

if (!function_exists('order_get_inbox_item')) {
    function order_get_inbox_item(int $inboxID, string $pID): ?array
    {
        $sql = 'SELECT i.inboxID, i.orderID, i.isRead, i.readAt,
                o.orderNo, o.subject, o.detail, o.status, o.createdByPID, o.createdAt,
                t.fName AS senderName
            FROM dh_order_inboxes AS i
            INNER JOIN dh_orders AS o ON i.orderID = o.orderID
            LEFT JOIN teacher AS t ON o.createdByPID = t.pID
            WHERE i.inboxID = ? AND i.pID = ?
            LIMIT 1';
        return db_fetch_one($sql, 'is', $inboxID, $pID);
    }
}

if (!function_exists('order_mark_read')) {
    function order_mark_read(int $inboxID, string $pID): void
    {
        $stmt = db_query('UPDATE dh_order_inboxes SET isRead = 1, readAt = NOW() WHERE inboxID = ? AND pID = ? AND isRead = 0', 'is', $inboxID, $pID);
        mysqli_stmt_close($stmt);
    }
}

if (!function_exists('order_archive_inbox')) {
    function order_archive_inbox(int $inboxID, string $pID): void
    {
        $stmt = db_query('UPDATE dh_order_inboxes SET isArchived = 1, archivedAt = NOW() WHERE inboxID = ? AND pID = ?', 'is', $inboxID, $pID);
        mysqli_stmt_close($stmt);
    }
}

if (!function_exists('order_get_attachments')) {
    function order_get_attachments(int $orderID): array
    {
        $sql = 'SELECT f.fileID, f.fileName, f.filePath, f.mimeType, f.fileSize
            FROM dh_file_refs AS r
            INNER JOIN dh_files AS f ON r.fileID = f.fileID
            WHERE r.moduleName = ? AND r.entityName = ? AND r.entityID = ? AND f.deletedAt IS NULL
            ORDER BY r.refID ASC';
        return db_fetch_all($sql, 'sss', ORDER_MODULE_NAME, ORDER_ENTITY_NAME, (string) $orderID);
    }
}

if (!function_exists('order_get_read_stats')) {
    function order_get_read_stats(int $orderID): array
    {
        $sql = 'SELECT i.pID, i.isRead, i.readAt, t.fName
            FROM dh_order_inboxes AS i
            INNER JOIN teacher AS t ON i.pID = t.pID
            WHERE i.orderID = ?
            ORDER BY t.fName ASC';
        return db_fetch_all($sql, 'i', $orderID);
    }
}

if (!function_exists('order_count_drafts')) {
    function order_count_drafts(string $pID): int
    {
        $row = db_fetch_one('SELECT COUNT(*) AS total FROM dh_orders WHERE createdByPID = ? AND deletedAt IS NULL', 's', $pID);
        return (int) ($row['total'] ?? 0);
    }
}

if (!function_exists('order_list_drafts_page')) {
    function order_list_drafts_page(string $pID, int $limit, int $offset): array
    {
        $limit = max(1, $limit);
        $offset = max(0, $offset);
        $sql = 'SELECT o.orderID, o.orderNo, o.subject, o.status, o.createdAt,
                (SELECT COUNT(*) FROM dh_order_inboxes WHERE orderID = o.orderID) AS recipientCount,
                (SELECT COUNT(*) FROM dh_order_inboxes WHERE orderID = o.orderID AND isRead = 1) AS readCount
            FROM dh_orders AS o
            WHERE o.createdByPID = ? AND o.deletedAt IS NULL
            ORDER BY o.createdAt DESC, o.orderID DESC
            LIMIT ? OFFSET ?';
        return db_fetch_all($sql, 'sii', $pID, $limit, $offset);
    }
}

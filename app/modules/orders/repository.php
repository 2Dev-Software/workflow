<?php

declare(strict_types=1);

require_once __DIR__ . '/../../db/db.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../services/document-service.php';
require_once __DIR__ . '/../audit/logger.php';

if (!function_exists('order_doc_number')) {
    function order_doc_number(array $order): string
    {
        $orderNo = trim((string) ($order['orderNo'] ?? ''));

        if ($orderNo !== '') {
            return $orderNo;
        }
        $orderID = (int) ($order['orderID'] ?? 0);

        return $orderID > 0 ? 'ORDER-' . $orderID : '';
    }
}

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

if (!function_exists('order_normalize_date')) {
    function order_normalize_date(?string $date): ?string
    {
        $date = trim((string) $date);

        if ($date === '') {
            return null;
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return null;
        }

        return $date;
    }
}

if (!function_exists('order_normalize_owner_status')) {
    function order_normalize_owner_status(?string $status): string
    {
        $status = strtolower(trim((string) $status));
        $allowed = ['all', 'waiting', 'complete', 'sent'];

        return in_array($status, $allowed, true) ? $status : 'all';
    }
}

if (!function_exists('order_normalize_owner_sort')) {
    function order_normalize_owner_sort(?string $sort): string
    {
        $sort = strtolower(trim((string) $sort));
        $allowed = ['newest', 'oldest', 'order_no'];

        return in_array($sort, $allowed, true) ? $sort : 'newest';
    }
}

if (!function_exists('order_normalize_inbox_sort')) {
    function order_normalize_inbox_sort(?string $sort): string
    {
        $sort = strtolower(trim((string) $sort));
        $allowed = ['newest', 'oldest', 'order_no', 'unread_first'];

        return in_array($sort, $allowed, true) ? $sort : 'newest';
    }
}

if (!function_exists('order_build_owner_filters')) {
    function order_build_owner_filters(string $ownerPID, array $filters = [], bool $include_status = true): array
    {
        $conditions = ['o.createdByPID = ?', 'o.deletedAt IS NULL'];
        $types = 's';
        $params = [$ownerPID];

        $status = order_normalize_owner_status((string) ($filters['status'] ?? 'all'));

        if ($include_status && $status !== 'all') {
            $status_map = [
                'waiting' => ORDER_STATUS_WAITING_ATTACHMENT,
                'complete' => ORDER_STATUS_COMPLETE,
                'sent' => ORDER_STATUS_SENT,
            ];
            $mapped_status = $status_map[$status] ?? '';

            if ($mapped_status !== '') {
                $conditions[] = 'o.status = ?';
                $types .= 's';
                $params[] = $mapped_status;
            }
        }

        [$term, $like] = order_prepare_search((string) ($filters['q'] ?? ''));

        if ($term !== '') {
            $conditions[] = '(o.orderNo LIKE ? ESCAPE \'\\\\\' OR o.subject LIKE ? ESCAPE \'\\\\\')';
            $types .= 'ss';
            $params[] = $like;
            $params[] = $like;
        }

        $date_from = order_normalize_date((string) ($filters['date_from'] ?? ''));
        $date_to = order_normalize_date((string) ($filters['date_to'] ?? ''));

        if ($date_from !== null) {
            $conditions[] = 'DATE(o.createdAt) >= ?';
            $types .= 's';
            $params[] = $date_from;
        }

        if ($date_to !== null) {
            $conditions[] = 'DATE(o.createdAt) <= ?';
            $types .= 's';
            $params[] = $date_to;
        }

        return [
            'where' => implode(' AND ', $conditions),
            'types' => $types,
            'params' => $params,
            'sort' => order_normalize_owner_sort((string) ($filters['sort'] ?? 'newest')),
        ];
    }
}

if (!function_exists('order_owner_order_by')) {
    function order_owner_order_by(string $sort): string
    {
        return match ($sort) {
            'oldest' => 'o.createdAt ASC, o.orderID ASC',
            'order_no' => 'o.dh_year ASC, o.orderSeq ASC, o.orderID ASC',
            default => 'o.createdAt DESC, o.orderID DESC',
        };
    }
}

if (!function_exists('order_inbox_order_by')) {
    function order_inbox_order_by(string $sort): string
    {
        return match ($sort) {
            'oldest' => 'i.deliveredAt ASC, i.inboxID ASC',
            'order_no' => 'o.dh_year ASC, o.orderSeq ASC, i.inboxID DESC',
            'unread_first' => 'i.isRead ASC, i.deliveredAt DESC, i.inboxID DESC',
            default => 'i.deliveredAt DESC, i.inboxID DESC',
        };
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
                'INSERT INTO dh_order_inboxes (orderID, pID, deliveredByPID)
                 SELECT ?, ?, ?
                 WHERE NOT EXISTS (
                    SELECT 1 FROM dh_order_inboxes WHERE orderID = ? AND pID = ?
                 )',
                'issis',
                $orderID,
                $pid,
                $deliveredByPID,
                $orderID,
                $pid
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
            WHERE o.orderID = ? AND o.deletedAt IS NULL
            LIMIT 1';

        return db_fetch_one($sql, 'i', $orderID);
    }
}

if (!function_exists('order_get_for_owner')) {
    function order_get_for_owner(int $orderID, string $ownerPID): ?array
    {
        $sql = 'SELECT o.*, t.fName AS creatorName
            FROM dh_orders AS o
            LEFT JOIN teacher AS t ON o.createdByPID = t.pID
            WHERE o.orderID = ? AND o.createdByPID = ? AND o.deletedAt IS NULL
            LIMIT 1';

        return db_fetch_one($sql, 'is', $orderID, $ownerPID);
    }
}

if (!function_exists('order_has_any_read')) {
    function order_has_any_read(int $orderID): bool
    {
        $row = db_fetch_one('SELECT COUNT(*) AS total FROM dh_order_inboxes WHERE orderID = ? AND isRead = 1', 'i', $orderID);

        return (int) ($row['total'] ?? 0) > 0;
    }
}

if (!function_exists('order_clear_delivery')) {
    function order_clear_delivery(int $orderID): void
    {
        $stmt = db_query('DELETE FROM dh_order_inboxes WHERE orderID = ?', 'i', $orderID);
        mysqli_stmt_close($stmt);

        $stmt = db_query('DELETE FROM dh_order_recipients WHERE orderID = ?', 'i', $orderID);
        mysqli_stmt_close($stmt);
    }
}

if (!function_exists('order_list_drafts')) {
    function order_list_drafts(string $pID): array
    {
        return order_list_drafts_page_filtered($pID, [
            'status' => 'all',
            'q' => '',
            'date_from' => '',
            'date_to' => '',
            'sort' => 'newest',
        ], 10000, 0);
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
    function order_list_inbox_page(string $pID, bool $archived, int $limit, int $offset, string $sort = 'newest'): array
    {
        $archivedFlag = $archived ? 1 : 0;
        $limit = max(1, $limit);
        $offset = max(0, $offset);
        $order_by = order_inbox_order_by(order_normalize_inbox_sort($sort));
        $sql = 'SELECT i.inboxID, i.isRead, i.readAt, i.isArchived, i.deliveredAt,
                o.orderID, o.orderNo, o.subject, o.status, o.createdAt,
                t.fName AS senderName
            FROM dh_order_inboxes AS i
            INNER JOIN dh_orders AS o ON i.orderID = o.orderID
            LEFT JOIN teacher AS t ON o.createdByPID = t.pID
            WHERE i.pID = ? AND i.isArchived = ?
            ORDER BY ' . $order_by . '
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
    function order_list_inbox_page_filtered(
        string $pID,
        bool $archived,
        ?string $search,
        ?string $read_filter,
        int $limit,
        int $offset,
        string $sort = 'newest'
    ): array
    {
        $limit = max(1, $limit);
        $offset = max(0, $offset);
        $order_by = order_inbox_order_by(order_normalize_inbox_sort($sort));
        $filter = order_build_inbox_filters($pID, $archived, $search, $read_filter, true);
        $sql = 'SELECT i.inboxID, i.isRead, i.readAt, i.isArchived, i.deliveredAt,
                o.orderID, o.orderNo, o.subject, o.status, o.createdAt,
                t.fName AS senderName
            FROM dh_order_inboxes AS i
            INNER JOIN dh_orders AS o ON i.orderID = o.orderID
            LEFT JOIN teacher AS t ON o.createdByPID = t.pID
            WHERE ' . $filter['where'] . '
            ORDER BY ' . $order_by . '
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
        $sql = 'SELECT i.inboxID, i.orderID, i.isRead, i.readAt, i.deliveredAt,
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

        $row = db_fetch_one('SELECT orderID FROM dh_order_inboxes WHERE inboxID = ? AND pID = ? LIMIT 1', 'is', $inboxID, $pID);
        $orderID = (int) ($row['orderID'] ?? 0);

        if ($orderID > 0) {
            $order = order_get($orderID);

            if ($order) {
                $documentNumber = order_doc_number($order);

                if ($documentNumber !== '') {
                    $documentID = document_upsert([
                        'documentType' => 'ORDER',
                        'documentNumber' => $documentNumber,
                        'subject' => (string) ($order['subject'] ?? ''),
                        'content' => (string) ($order['detail'] ?? ''),
                        'status' => (string) ($order['status'] ?? ''),
                        'senderName' => (string) ($order['creatorName'] ?? ''),
                        'createdByPID' => (string) ($order['createdByPID'] ?? ''),
                        'updatedByPID' => $order['updatedByPID'] ?? null,
                    ]);

                    if ($documentID) {
                        document_mark_read($documentID, $pID);
                        document_record_read_receipt($documentID, $pID);
                    }

                    if (function_exists('audit_log')) {
                        audit_log('orders', 'READ', 'SUCCESS', 'dh_orders', $orderID, null, [
                            'inbox_id' => $inboxID,
                            'request_id' => app_request_id(),
                        ]);
                    }
                }
            }
        }
    }
}

if (!function_exists('order_archive_inbox')) {
    function order_archive_inbox(int $inboxID, string $pID): void
    {
        $stmt = db_query('UPDATE dh_order_inboxes SET isArchived = 1, archivedAt = NOW() WHERE inboxID = ? AND pID = ?', 'is', $inboxID, $pID);
        mysqli_stmt_close($stmt);

        $row = db_fetch_one('SELECT orderID FROM dh_order_inboxes WHERE inboxID = ? AND pID = ? LIMIT 1', 'is', $inboxID, $pID);
        $orderID = (int) ($row['orderID'] ?? 0);

        if ($orderID > 0) {
            $order = order_get($orderID);

            if ($order) {
                $documentNumber = order_doc_number($order);

                if ($documentNumber !== '') {
                    $documentID = document_upsert([
                        'documentType' => 'ORDER',
                        'documentNumber' => $documentNumber,
                        'subject' => (string) ($order['subject'] ?? ''),
                        'content' => (string) ($order['detail'] ?? ''),
                        'status' => (string) ($order['status'] ?? ''),
                        'senderName' => (string) ($order['creatorName'] ?? ''),
                        'createdByPID' => (string) ($order['createdByPID'] ?? ''),
                        'updatedByPID' => $order['updatedByPID'] ?? null,
                    ]);

                    if ($documentID) {
                        document_set_recipient_status($documentID, $pID, 'normal_inbox', 'ARCHIVED');
                    }
                }
            }
        }
    }
}

if (!function_exists('order_unarchive_inbox')) {
    function order_unarchive_inbox(int $inboxID, string $pID): void
    {
        $stmt = db_query('UPDATE dh_order_inboxes SET isArchived = 0, archivedAt = NULL WHERE inboxID = ? AND pID = ?', 'is', $inboxID, $pID);
        mysqli_stmt_close($stmt);

        $row = db_fetch_one('SELECT orderID FROM dh_order_inboxes WHERE inboxID = ? AND pID = ? LIMIT 1', 'is', $inboxID, $pID);
        $orderID = (int) ($row['orderID'] ?? 0);

        if ($orderID <= 0) {
            return;
        }

        $order = order_get($orderID);

        if (!$order) {
            return;
        }

        $documentNumber = order_doc_number($order);

        if ($documentNumber === '') {
            return;
        }

        $documentID = document_upsert([
            'documentType' => 'ORDER',
            'documentNumber' => $documentNumber,
            'subject' => (string) ($order['subject'] ?? ''),
            'content' => (string) ($order['detail'] ?? ''),
            'status' => (string) ($order['status'] ?? ''),
            'senderName' => (string) ($order['creatorName'] ?? ''),
            'createdByPID' => (string) ($order['createdByPID'] ?? ''),
            'updatedByPID' => $order['updatedByPID'] ?? null,
        ]);

        if ($documentID) {
            document_set_recipient_status($documentID, $pID, 'normal_inbox', 'READ');
        }
    }
}

if (!function_exists('order_get_attachments')) {
    function order_get_attachments(int $orderID): array
    {
        $sql = 'SELECT f.fileID, f.fileName, f.filePath, f.mimeType, f.fileSize,
                r.attachedAt, r.attachedByPID,
                t.fName AS attachedByName
            FROM dh_file_refs AS r
            INNER JOIN dh_files AS f ON r.fileID = f.fileID
            LEFT JOIN teacher AS t ON r.attachedByPID = t.pID
            WHERE r.moduleName = ? AND r.entityName = ? AND r.entityID = ? AND f.deletedAt IS NULL
            ORDER BY r.refID ASC';

        return db_fetch_all($sql, 'sss', ORDER_MODULE_NAME, ORDER_ENTITY_NAME, (string) $orderID);
    }
}

if (!function_exists('order_get_read_stats')) {
    function order_get_read_stats(int $orderID, string $filter = 'all'): array
    {
        $filter = strtolower(trim($filter));
        $conditions = ['i.orderID = ?'];
        $types = 'i';
        $params = [$orderID];

        if ($filter === 'unread') {
            $conditions[] = 'i.isRead = 0';
        } elseif ($filter === 'read') {
            $conditions[] = 'i.isRead = 1';
        }

        $sql = 'SELECT i.pID, i.isRead, i.readAt, t.fName, t.roleID
            FROM dh_order_inboxes AS i
            INNER JOIN teacher AS t ON i.pID = t.pID
            WHERE ' . implode(' AND ', $conditions) . '
            ORDER BY t.fName ASC';

        return db_fetch_all($sql, $types, ...$params);
    }
}

if (!function_exists('order_count_drafts')) {
    function order_count_drafts(string $pID): int
    {
        return order_count_drafts_filtered($pID, []);
    }
}

if (!function_exists('order_list_drafts_page')) {
    function order_list_drafts_page(string $pID, int $limit, int $offset): array
    {
        return order_list_drafts_page_filtered($pID, [], $limit, $offset);
    }
}

if (!function_exists('order_count_drafts_filtered')) {
    function order_count_drafts_filtered(string $pID, array $filters = []): int
    {
        $filter = order_build_owner_filters($pID, $filters, true);
        $sql = 'SELECT COUNT(*) AS total
            FROM dh_orders AS o
            WHERE ' . $filter['where'];
        $row = db_fetch_one($sql, $filter['types'], ...$filter['params']);

        return (int) ($row['total'] ?? 0);
    }
}

if (!function_exists('order_list_drafts_page_filtered')) {
    function order_list_drafts_page_filtered(string $pID, array $filters = [], int $limit = 10, int $offset = 0): array
    {
        $limit = max(1, $limit);
        $offset = max(0, $offset);
        $filter = order_build_owner_filters($pID, $filters, true);
        $order_by = order_owner_order_by((string) ($filter['sort'] ?? 'newest'));
        $sql = 'SELECT o.orderID, o.orderNo, o.subject, o.status, o.createdAt,
                (SELECT COUNT(*) FROM dh_order_inboxes WHERE orderID = o.orderID) AS recipientCount,
                (SELECT COUNT(*) FROM dh_order_inboxes WHERE orderID = o.orderID AND isRead = 1) AS readCount
            FROM dh_orders AS o
            WHERE ' . $filter['where'] . '
            ORDER BY ' . $order_by . '
            LIMIT ? OFFSET ?';
        $types = $filter['types'] . 'ii';
        $params = array_merge($filter['params'], [$limit, $offset]);

        return db_fetch_all($sql, $types, ...$params);
    }
}

if (!function_exists('order_count_drafts_by_status')) {
    function order_count_drafts_by_status(string $pID, array $filters = []): array
    {
        $filter = order_build_owner_filters($pID, $filters, false);
        $sql = 'SELECT o.status, COUNT(*) AS total
            FROM dh_orders AS o
            WHERE ' . $filter['where'] . '
            GROUP BY o.status';
        $rows = db_fetch_all($sql, $filter['types'], ...$filter['params']);
        $counts = [
            'all' => 0,
            'waiting' => 0,
            'complete' => 0,
            'sent' => 0,
        ];

        foreach ($rows as $row) {
            $status = (string) ($row['status'] ?? '');
            $total = (int) ($row['total'] ?? 0);
            $counts['all'] += $total;

            if ($status === ORDER_STATUS_WAITING_ATTACHMENT) {
                $counts['waiting'] += $total;
            } elseif ($status === ORDER_STATUS_COMPLETE) {
                $counts['complete'] += $total;
            } elseif ($status === ORDER_STATUS_SENT) {
                $counts['sent'] += $total;
            }
        }

        return $counts;
    }
}

if (!function_exists('order_list_routes')) {
    function order_list_routes(int $orderID): array
    {
        return db_fetch_all(
            'SELECT r.routeID, r.action, r.fromPID, r.toPID, r.note, r.actionAt,
                    from_t.fName AS fromName,
                    to_t.fName AS toName
             FROM dh_order_routes AS r
             LEFT JOIN teacher AS from_t ON r.fromPID = from_t.pID
             LEFT JOIN teacher AS to_t ON r.toPID = to_t.pID
             WHERE r.orderID = ?
             ORDER BY r.actionAt ASC, r.routeID ASC',
            'i',
            $orderID
        );
    }
}

if (!function_exists('order_list_attachment_events')) {
    function order_list_attachment_events(int $orderID): array
    {
        return db_fetch_all(
            'SELECT r.refID, r.attachedAt, r.attachedByPID,
                    f.fileID, f.fileName, f.mimeType, f.fileSize,
                    t.fName AS attachedByName
             FROM dh_file_refs AS r
             INNER JOIN dh_files AS f ON r.fileID = f.fileID
             LEFT JOIN teacher AS t ON r.attachedByPID = t.pID
             WHERE r.moduleName = ? AND r.entityName = ? AND r.entityID = ? AND f.deletedAt IS NULL
             ORDER BY r.attachedAt ASC, r.refID ASC',
            'sss',
            ORDER_MODULE_NAME,
            ORDER_ENTITY_NAME,
            (string) $orderID
        );
    }
}

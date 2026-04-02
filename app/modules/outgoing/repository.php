<?php

declare(strict_types=1);

require_once __DIR__ . '/../../db/db.php';

const OUTGOING_MODULE_NAME = 'outgoing';
const OUTGOING_ENTITY_NAME = 'dh_outgoing_letters';

if (!function_exists('outgoing_create_record')) {
    function outgoing_create_record(array $data): int
    {
        $stmt = db_query(
            'INSERT INTO dh_outgoing_letters (dh_year, outgoingNo, outgoingSeq, subject, detail, status, createdByPID, updatedByPID)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            'isisssss',
            (int) $data['dh_year'],
            (string) $data['outgoingNo'],
            (int) $data['outgoingSeq'],
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

if (!function_exists('outgoing_update_record')) {
    function outgoing_update_record(int $outgoingID, array $data): void
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
        $params[] = $outgoingID;

        $sql = 'UPDATE dh_outgoing_letters SET ' . implode(', ', $fields) . ' WHERE outgoingID = ?';
        $stmt = db_query($sql, $types, ...$params);
        mysqli_stmt_close($stmt);
    }
}

if (!function_exists('outgoing_get')) {
    function outgoing_get(int $outgoingID): ?array
    {
        $sql = 'SELECT o.*, t.fName AS creatorName
            FROM dh_outgoing_letters AS o
            LEFT JOIN teacher AS t ON o.createdByPID = t.pID
            WHERE o.outgoingID = ?
            LIMIT 1';

        return db_fetch_one($sql, 'i', $outgoingID);
    }
}

if (!function_exists('outgoing_list')) {
    function outgoing_list(array $filters = []): array
    {
        $search = trim((string) ($filters['q'] ?? ''));
        $status = strtoupper(trim((string) ($filters['status'] ?? 'all')));
        $created_by_pid = trim((string) ($filters['created_by_pid'] ?? ''));
        $sort = strtolower(trim((string) ($filters['sort'] ?? 'newest')));
        $params = [];
        $types = '';

        $sql = 'SELECT
                o.outgoingID,
                o.dh_year,
                o.outgoingNo,
                o.outgoingSeq,
                o.subject,
                o.status,
                o.createdAt,
                o.updatedAt,
                t.fName AS creatorName,
                COUNT(f.fileID) AS attachmentCount
            FROM dh_outgoing_letters AS o
            LEFT JOIN teacher AS t ON o.createdByPID = t.pID
            LEFT JOIN dh_file_refs AS r
                ON r.moduleName = (\'' . OUTGOING_MODULE_NAME . '\' COLLATE utf8mb4_general_ci)
                AND r.entityName = (\'' . OUTGOING_ENTITY_NAME . '\' COLLATE utf8mb4_general_ci)
                AND CAST(r.entityID AS UNSIGNED) = o.outgoingID
            LEFT JOIN dh_files AS f
                ON r.fileID = f.fileID
                AND f.deletedAt IS NULL
            WHERE o.deletedAt IS NULL';

        if ($search !== '') {
            $like = '%' . $search . '%';
            $sql .= ' AND (o.outgoingNo LIKE ? OR o.subject LIKE ?)';
            $types .= 'ss';
            $params[] = $like;
            $params[] = $like;
        }

        if ($status !== '' && $status !== 'ALL') {
            $sql .= ' AND o.status = ?';
            $types .= 's';
            $params[] = $status;
        }

        if ($created_by_pid !== '') {
            $sql .= ' AND o.createdByPID = ?';
            $types .= 's';
            $params[] = $created_by_pid;
        }

        $sort_direction = $sort === 'oldest' ? 'ASC' : 'DESC';

        $sql .= ' GROUP BY
                o.outgoingID,
                o.dh_year,
                o.outgoingNo,
                o.outgoingSeq,
                o.subject,
                o.status,
                o.createdAt,
                o.updatedAt,
                t.fName
            ORDER BY o.createdAt ' . $sort_direction . ', o.outgoingID ' . $sort_direction;

        return db_fetch_all($sql, $types, ...$params);
    }
}

if (!function_exists('outgoing_count_by_status')) {
    function outgoing_count_by_status(): array
    {
        $counts = [
            'all' => 0,
            OUTGOING_STATUS_WAITING_ATTACHMENT => 0,
            OUTGOING_STATUS_COMPLETE => 0,
        ];

        $rows = db_fetch_all(
            'SELECT status, COUNT(*) AS total
             FROM dh_outgoing_letters
             WHERE deletedAt IS NULL
             GROUP BY status'
        );

        foreach ($rows as $row) {
            $status = (string) ($row['status'] ?? '');
            $total = (int) ($row['total'] ?? 0);

            $counts['all'] += $total;

            if (isset($counts[$status])) {
                $counts[$status] = $total;
            }
        }

        return $counts;
    }
}

if (!function_exists('outgoing_get_attachments')) {
    function outgoing_get_attachments(int $outgoingID): array
    {
        $sql = 'SELECT f.fileID, f.fileName, f.filePath, f.mimeType, f.fileSize
            FROM dh_file_refs AS r
            INNER JOIN dh_files AS f ON r.fileID = f.fileID
            WHERE r.moduleName = (\'' . OUTGOING_MODULE_NAME . '\' COLLATE utf8mb4_general_ci)
              AND r.entityName = (\'' . OUTGOING_ENTITY_NAME . '\' COLLATE utf8mb4_general_ci)
              AND CAST(r.entityID AS UNSIGNED) = ?
              AND f.deletedAt IS NULL
            ORDER BY r.refID ASC';

        return db_fetch_all($sql, 'i', $outgoingID);
    }
}

if (!function_exists('outgoing_list_attachments_map')) {
    /**
     * @param array<int, int> $outgoingIDs
     * @return array<string, array<int, array<string, mixed>>>
     */
    function outgoing_list_attachments_map(array $outgoingIDs): array
    {
        $outgoingIDs = array_values(array_unique(array_filter(array_map('intval', $outgoingIDs), static function (int $id): bool {
            return $id > 0;
        })));

        if (empty($outgoingIDs)) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($outgoingIDs), '?'));
        $types = str_repeat('i', count($outgoingIDs));
        $params = [];

        foreach ($outgoingIDs as $outgoingID) {
            $params[] = $outgoingID;
        }

        $sql = 'SELECT r.entityID, f.fileID, f.fileName, f.filePath, f.mimeType, f.fileSize
            FROM dh_file_refs AS r
            INNER JOIN dh_files AS f ON r.fileID = f.fileID
            WHERE r.moduleName = (\'' . OUTGOING_MODULE_NAME . '\' COLLATE utf8mb4_general_ci)
              AND r.entityName = (\'' . OUTGOING_ENTITY_NAME . '\' COLLATE utf8mb4_general_ci)
              AND CAST(r.entityID AS UNSIGNED) IN (' . $placeholders . ')
              AND f.deletedAt IS NULL
            ORDER BY r.entityID ASC, r.refID ASC';

        $rows = db_fetch_all($sql, $types, ...$params);
        $map = [];

        foreach ($rows as $row) {
            $entityID = (string) ($row['entityID'] ?? '');

            if ($entityID === '') {
                continue;
            }

            if (!isset($map[$entityID])) {
                $map[$entityID] = [];
            }

            $map[$entityID][] = [
                'fileID' => (int) ($row['fileID'] ?? 0),
                'fileName' => (string) ($row['fileName'] ?? ''),
                'filePath' => (string) ($row['filePath'] ?? ''),
                'mimeType' => (string) ($row['mimeType'] ?? ''),
                'fileSize' => (int) ($row['fileSize'] ?? 0),
            ];
        }

        return $map;
    }
}

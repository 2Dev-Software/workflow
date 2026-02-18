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
    function outgoing_list(): array
    {
        $sql = 'SELECT outgoingID, outgoingNo, subject, status, createdAt
            FROM dh_outgoing_letters
            WHERE deletedAt IS NULL
            ORDER BY createdAt DESC, outgoingID DESC';

        return db_fetch_all($sql);
    }
}

if (!function_exists('outgoing_get_attachments')) {
    function outgoing_get_attachments(int $outgoingID): array
    {
        $sql = 'SELECT f.fileID, f.fileName, f.filePath, f.mimeType, f.fileSize
            FROM dh_file_refs AS r
            INNER JOIN dh_files AS f ON r.fileID = f.fileID
            WHERE r.moduleName = ? AND r.entityName = ? AND r.entityID = ? AND f.deletedAt IS NULL
            ORDER BY r.refID ASC';

        return db_fetch_all($sql, 'sss', OUTGOING_MODULE_NAME, OUTGOING_ENTITY_NAME, (string) $outgoingID);
    }
}

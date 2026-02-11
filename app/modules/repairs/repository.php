<?php
declare(strict_types=1);

require_once __DIR__ . '/../../db/db.php';

const REPAIR_MODULE_NAME = 'repairs';
const REPAIR_ENTITY_NAME = 'dh_repair_requests';

if (!function_exists('repair_create_record')) {
    function repair_create_record(array $data): int
    {
        $stmt = db_query(
            'INSERT INTO dh_repair_requests (dh_year, requesterPID, subject, detail, location, status, assignedToPID)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            'issssss',
            (int) $data['dh_year'],
            (string) $data['requesterPID'],
            (string) $data['subject'],
            $data['detail'] ?? null,
            $data['location'] ?? null,
            (string) $data['status'],
            $data['assignedToPID'] ?? null
        );
        $id = db_last_insert_id();
        mysqli_stmt_close($stmt);

        return $id;
    }
}

if (!function_exists('repair_list_by_requester')) {
    function repair_list_by_requester(string $pID): array
    {
        $sql = 'SELECT repairID, requesterPID, subject, location, status, createdAt
            FROM dh_repair_requests
            WHERE requesterPID = ? AND deletedAt IS NULL
            ORDER BY createdAt DESC, repairID DESC';
        return db_fetch_all($sql, 's', $pID);
    }
}

if (!function_exists('repair_list_all')) {
    function repair_list_all(): array
    {
        $sql = 'SELECT r.repairID, r.requesterPID, r.subject, r.location, r.status, r.createdAt, t.fName AS requesterName
            FROM dh_repair_requests AS r
            LEFT JOIN teacher AS t ON r.requesterPID = t.pID
            WHERE r.deletedAt IS NULL
            ORDER BY r.createdAt DESC, r.repairID DESC';
        return db_fetch_all($sql);
    }
}

if (!function_exists('repair_count_by_requester')) {
    function repair_count_by_requester(string $pID): int
    {
        $row = db_fetch_one('SELECT COUNT(*) AS total FROM dh_repair_requests WHERE requesterPID = ? AND deletedAt IS NULL', 's', $pID);
        return (int) ($row['total'] ?? 0);
    }
}

if (!function_exists('repair_list_by_requester_page')) {
    function repair_list_by_requester_page(string $pID, int $limit, int $offset): array
    {
        $limit = max(1, $limit);
        $offset = max(0, $offset);
        $sql = 'SELECT repairID, requesterPID, subject, location, status, createdAt
            FROM dh_repair_requests
            WHERE requesterPID = ? AND deletedAt IS NULL
            ORDER BY createdAt DESC, repairID DESC
            LIMIT ? OFFSET ?';
        return db_fetch_all($sql, 'sii', $pID, $limit, $offset);
    }
}

if (!function_exists('repair_count_all')) {
    function repair_count_all(): int
    {
        $row = db_fetch_one('SELECT COUNT(*) AS total FROM dh_repair_requests WHERE deletedAt IS NULL');
        return (int) ($row['total'] ?? 0);
    }
}

if (!function_exists('repair_list_all_page')) {
    function repair_list_all_page(int $limit, int $offset): array
    {
        $limit = max(1, $limit);
        $offset = max(0, $offset);
        $sql = 'SELECT r.repairID, r.requesterPID, r.subject, r.location, r.status, r.createdAt, t.fName AS requesterName
            FROM dh_repair_requests AS r
            LEFT JOIN teacher AS t ON r.requesterPID = t.pID
            WHERE r.deletedAt IS NULL
            ORDER BY r.createdAt DESC, r.repairID DESC
            LIMIT ? OFFSET ?';
        return db_fetch_all($sql, 'ii', $limit, $offset);
    }
}

if (!function_exists('repair_update_record')) {
    function repair_update_record(int $repairID, array $data): void
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
        $params[] = $repairID;

        $sql = 'UPDATE dh_repair_requests SET ' . implode(', ', $fields) . ' WHERE repairID = ?';
        $stmt = db_query($sql, $types, ...$params);
        mysqli_stmt_close($stmt);
    }
}

if (!function_exists('repair_delete_record')) {
    function repair_delete_record(int $repairID): void
    {
        $stmt = db_query('UPDATE dh_repair_requests SET deletedAt = NOW() WHERE repairID = ?', 'i', $repairID);
        mysqli_stmt_close($stmt);
    }
}

if (!function_exists('repair_get')) {
    function repair_get(int $repairID): ?array
    {
        $sql = 'SELECT r.*, t.fName AS requesterName
            FROM dh_repair_requests AS r
            LEFT JOIN teacher AS t ON r.requesterPID = t.pID
            WHERE r.repairID = ?
            LIMIT 1';
        return db_fetch_one($sql, 'i', $repairID);
    }
}

if (!function_exists('repair_get_attachments')) {
    function repair_get_attachments(int $repairID): array
    {
        $sql = 'SELECT f.fileID, f.fileName, f.filePath, f.mimeType, f.fileSize
            FROM dh_file_refs AS r
            INNER JOIN dh_files AS f ON r.fileID = f.fileID
            WHERE r.moduleName = ? AND r.entityName = ? AND r.entityID = ? AND f.deletedAt IS NULL
            ORDER BY r.refID ASC';
        return db_fetch_all($sql, 'sss', REPAIR_MODULE_NAME, REPAIR_ENTITY_NAME, (string) $repairID);
    }
}

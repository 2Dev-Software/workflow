<?php

declare(strict_types=1);

require_once __DIR__ . '/../../db/db.php';

const REPAIR_MODULE_NAME = 'repairs';
const REPAIR_ENTITY_NAME = 'dh_repair_requests';

if (!function_exists('repair_build_filters')) {
    function repair_build_filters(?string $requester_pid = null, array $statuses = [], string $alias = ''): array
    {
        $prefix = $alias !== '' ? ($alias . '.') : '';
        $conditions = [$prefix . 'deletedAt IS NULL'];
        $types = '';
        $params = [];

        if ($requester_pid !== null && $requester_pid !== '') {
            $conditions[] = $prefix . 'requesterPID = ?';
            $types .= 's';
            $params[] = $requester_pid;
        }

        $statuses = array_values(array_filter(array_map('strval', $statuses), static function (string $status): bool {
            return $status !== '';
        }));

        if (!empty($statuses)) {
            $conditions[] = $prefix . 'status IN (' . implode(', ', array_fill(0, count($statuses), '?')) . ')';
            $types .= str_repeat('s', count($statuses));
            array_push($params, ...$statuses);
        }

        return [
            'sql' => implode(' AND ', $conditions),
            'types' => $types,
            'params' => $params,
        ];
    }
}

if (!function_exists('repair_create_record')) {
    function repair_create_record(array $data): int
    {
        $stmt = db_query(
            'INSERT INTO dh_repair_requests (dh_year, requesterPID, subject, detail, location, equipment, status, assignedToPID)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            'isssssss',
            (int) $data['dh_year'],
            (string) $data['requesterPID'],
            (string) $data['subject'],
            $data['detail'] ?? null,
            $data['location'] ?? null,
            $data['equipment'] ?? null,
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
        return repair_list_filtered_page($pID, [], 1000, 0);
    }
}

if (!function_exists('repair_list_all')) {
    function repair_list_all(): array
    {
        return repair_list_filtered_page(null, [], 1000, 0);
    }
}

if (!function_exists('repair_count_by_requester')) {
    function repair_count_by_requester(string $pID): int
    {
        return repair_count_filtered($pID, []);
    }
}

if (!function_exists('repair_list_by_requester_page')) {
    function repair_list_by_requester_page(string $pID, int $limit, int $offset): array
    {
        return repair_list_filtered_page($pID, [], $limit, $offset);
    }
}

if (!function_exists('repair_count_all')) {
    function repair_count_all(): int
    {
        return repair_count_filtered(null, []);
    }
}

if (!function_exists('repair_list_all_page')) {
    function repair_list_all_page(int $limit, int $offset): array
    {
        return repair_list_filtered_page(null, [], $limit, $offset);
    }
}

if (!function_exists('repair_count_filtered')) {
    function repair_count_filtered(?string $requester_pid = null, array $statuses = []): int
    {
        $filters = repair_build_filters($requester_pid, $statuses);
        $sql = 'SELECT COUNT(*) AS total FROM dh_repair_requests WHERE ' . $filters['sql'];
        $row = db_fetch_one($sql, $filters['types'], ...$filters['params']);

        return (int) ($row['total'] ?? 0);
    }
}

if (!function_exists('repair_list_filtered_page')) {
    function repair_list_filtered_page(?string $requester_pid, array $statuses, int $limit, int $offset): array
    {
        $limit = max(1, $limit);
        $offset = max(0, $offset);
        $filters = repair_build_filters($requester_pid, $statuses, 'r');
        $sql = 'SELECT r.repairID, r.requesterPID, r.subject, r.detail, r.location, r.equipment, r.status, r.assignedToPID, r.resolvedAt, r.createdAt, r.updatedAt,
                requester.fName AS requesterName,
                assigned.fName AS assignedToName
            FROM dh_repair_requests AS r
            LEFT JOIN teacher AS requester ON r.requesterPID = requester.pID
            LEFT JOIN teacher AS assigned ON r.assignedToPID = assigned.pID
            WHERE ' . $filters['sql'] . '
            ORDER BY r.createdAt DESC, r.repairID DESC
            LIMIT ? OFFSET ?';

        $types = $filters['types'] . 'ii';
        $params = $filters['params'];
        $params[] = $limit;
        $params[] = $offset;

        return db_fetch_all($sql, $types, ...$params);
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
        $sql = 'SELECT r.*, requester.fName AS requesterName, assigned.fName AS assignedToName
            FROM dh_repair_requests AS r
            LEFT JOIN teacher AS requester ON r.requesterPID = requester.pID
            LEFT JOIN teacher AS assigned ON r.assignedToPID = assigned.pID
            WHERE r.repairID = ? AND r.deletedAt IS NULL
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

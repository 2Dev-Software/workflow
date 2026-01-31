<?php
declare(strict_types=1);

require_once __DIR__ . '/../../db/db.php';

const MEMO_MODULE_NAME = 'memos';
const MEMO_ENTITY_NAME = 'dh_memos';

if (!function_exists('memo_create_record')) {
    function memo_create_record(array $data): int
    {
        $stmt = db_query(
            'INSERT INTO dh_memos (dh_year, subject, detail, status, createdByPID, approvedByPID)
             VALUES (?, ?, ?, ?, ?, ?)',
            'isssss',
            (int) $data['dh_year'],
            (string) $data['subject'],
            $data['detail'] ?? null,
            (string) $data['status'],
            (string) $data['createdByPID'],
            $data['approvedByPID'] ?? null
        );
        $id = db_last_insert_id();
        mysqli_stmt_close($stmt);

        return $id;
    }
}

if (!function_exists('memo_list_by_creator')) {
    function memo_list_by_creator(string $pID): array
    {
        $sql = 'SELECT memoID, subject, status, createdAt
            FROM dh_memos
            WHERE createdByPID = ? AND deletedAt IS NULL
            ORDER BY createdAt DESC, memoID DESC';
        return db_fetch_all($sql, 's', $pID);
    }
}

if (!function_exists('memo_get')) {
    function memo_get(int $memoID): ?array
    {
        $sql = 'SELECT m.*, t.fName AS creatorName
            FROM dh_memos AS m
            LEFT JOIN teacher AS t ON m.createdByPID = t.pID
            WHERE m.memoID = ?
            LIMIT 1';
        return db_fetch_one($sql, 'i', $memoID);
    }
}

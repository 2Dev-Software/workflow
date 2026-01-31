<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/../config/state.php';

if (!function_exists('document_inbox_list')) {
    function document_inbox_list(string $pid, array $filters = [], int $page = 1, int $page_size = 10): array
    {
        $connection = db_connection();
        $page = max(1, $page);
        $page_size = min(50, max(1, $page_size));
        $offset = ($page - 1) * $page_size;

        if (db_table_exists($connection, 'dh_documents') && db_table_exists($connection, 'dh_document_recipients')) {
            $status = trim((string) ($filters['status'] ?? ''));
            $search = trim((string) ($filters['q'] ?? ''));

            $where = 'r.recipientPID = ?';
            $params = [$pid];
            $types = 's';

            if ($status !== '') {
                $where .= ' AND r.inboxStatus = ?';
                $types .= 's';
                $params[] = $status;
            }

            if ($search !== '') {
                $where .= ' AND (d.subject LIKE ? OR d.documentNumber LIKE ?)';
                $types .= 'ss';
                $params[] = '%' . $search . '%';
                $params[] = '%' . $search . '%';
            }

            $count_sql = "SELECT COUNT(*) AS total FROM dh_document_recipients r JOIN dh_documents d ON d.id = r.documentID WHERE {$where}";
            $count_row = db_fetch_one($count_sql, $types, ...$params);
            $total = (int) ($count_row['total'] ?? 0);

            $sql = "SELECT d.id, d.documentType, d.subject, d.documentNumber, d.createdAt, r.readAt, r.inboxStatus, d.senderName
                    FROM dh_document_recipients r
                    JOIN dh_documents d ON d.id = r.documentID
                    WHERE {$where}
                    ORDER BY r.createdAt DESC
                    LIMIT ? OFFSET ?";

            $rows = db_fetch_all($sql, $types . 'ii', ...array_merge($params, [$page_size, $offset]));

            return [
                'items' => $rows,
                'total' => $total,
                'page' => $page,
                'page_size' => $page_size,
            ];
        }

        // Fallback mock data until schema is migrated.
        $items = [
            [
                'id' => 1,
                'documentType' => 'INTERNAL',
                'subject' => 'แจ้งกำหนดการประชุมฝ่ายบริหาร',
                'documentNumber' => 'ศธ.01234/2569',
                'createdAt' => date('Y-m-d H:i:s', strtotime('-2 hours')),
                'readAt' => null,
                'inboxStatus' => 'UNREAD',
                'senderName' => 'งานสารบรรณ',
            ],
            [
                'id' => 2,
                'documentType' => 'EXTERNAL',
                'subject' => 'หนังสือด่วนจากเขตพื้นที่การศึกษา',
                'documentNumber' => 'ศธ.04056/2569',
                'createdAt' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'readAt' => date('Y-m-d H:i:s', strtotime('-20 hours')),
                'inboxStatus' => 'READ',
                'senderName' => 'สำนักงานเขตพื้นที่',
            ],
        ];

        return [
            'items' => $items,
            'total' => count($items),
            'page' => 1,
            'page_size' => $page_size,
        ];
    }
}

if (!function_exists('document_inbox_counts')) {
    function document_inbox_counts(string $pid): array
    {
        $connection = db_connection();
        if (db_table_exists($connection, 'dh_document_recipients')) {
            $row = db_fetch_one('SELECT SUM(inboxStatus = "UNREAD") AS unread, COUNT(*) AS total FROM dh_document_recipients WHERE recipientPID = ?', 's', $pid);
            return [
                'unread' => (int) ($row['unread'] ?? 0),
                'total' => (int) ($row['total'] ?? 0),
            ];
        }

        return ['unread' => 1, 'total' => 2];
    }
}

if (!function_exists('document_next_sequence')) {
    function document_next_sequence(string $sequence_key): ?int
    {
        $connection = db_connection();
        if (!db_table_exists($connection, 'dh_sequences')) {
            return null;
        }

        $sequence_key = trim($sequence_key);
        if ($sequence_key === '') {
            return null;
        }

        try {
            db_begin();
            $row = db_fetch_one('SELECT currentValue FROM dh_sequences WHERE seqKey = ? FOR UPDATE', 's', $sequence_key);
            if (!$row) {
                db_execute('INSERT INTO dh_sequences (seqKey, currentValue) VALUES (?, ?)', 'si', $sequence_key, 1);
                db_commit();
                return 1;
            }

            $next = (int) ($row['currentValue'] ?? 0) + 1;
            db_execute('UPDATE dh_sequences SET currentValue = ? WHERE seqKey = ?', 'is', $next, $sequence_key);
            db_commit();
            return $next;
        } catch (Throwable $e) {
            db_rollback();
            error_log('Sequence generation failed: ' . $e->getMessage());
            return null;
        }
    }
}

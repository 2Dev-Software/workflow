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

if (!function_exists('document_upsert')) {
    function document_upsert(array $data): ?int
    {
        $connection = db_connection();
        if (!db_table_exists($connection, 'dh_documents')) {
            return null;
        }

        $documentType = (string) ($data['documentType'] ?? '');
        $documentNumber = (string) ($data['documentNumber'] ?? '');
        $subject = (string) ($data['subject'] ?? '');
        $content = $data['content'] ?? null;
        $status = (string) ($data['status'] ?? '');
        $senderName = $data['senderName'] ?? null;
        $createdByPID = (string) ($data['createdByPID'] ?? '');
        $updatedByPID = $data['updatedByPID'] ?? null;

        if ($documentType === '' || $subject === '' || $createdByPID === '') {
            return null;
        }

        $row = db_fetch_one('SELECT id FROM dh_documents WHERE documentType = ? AND documentNumber = ? LIMIT 1', 'ss', $documentType, $documentNumber);
        if ($row && isset($row['id'])) {
            $documentID = (int) $row['id'];
            $update = [
                'subject' => $subject,
                'content' => $content,
                'status' => $status,
                'senderName' => $senderName,
                'updatedByPID' => $updatedByPID,
            ];
            $fields = [];
            $params = [];
            $types = '';
            foreach ($update as $field => $value) {
                $fields[] = $field . ' = ?';
                $types .= $value === null ? 's' : (is_int($value) ? 'i' : 's');
                $params[] = $value;
            }
            if (!empty($fields)) {
                $types .= 'i';
                $params[] = $documentID;
                $sql = 'UPDATE dh_documents SET ' . implode(', ', $fields) . ' WHERE id = ?';
                $stmt = db_query($sql, $types, ...$params);
                mysqli_stmt_close($stmt);
            }
            return $documentID;
        }

        $stmt = db_query(
            'INSERT INTO dh_documents (documentType, documentNumber, subject, content, status, senderName, createdByPID, updatedByPID)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            'ssssssss',
            $documentType,
            $documentNumber,
            $subject,
            $content,
            $status,
            $senderName,
            $createdByPID,
            $updatedByPID
        );
        $documentID = db_last_insert_id();
        mysqli_stmt_close($stmt);

        return $documentID;
    }
}

if (!function_exists('document_get_id')) {
    function document_get_id(string $documentType, string $documentNumber): ?int
    {
        $connection = db_connection();
        if (!db_table_exists($connection, 'dh_documents')) {
            return null;
        }

        $documentType = trim($documentType);
        $documentNumber = trim($documentNumber);
        if ($documentType === '' || $documentNumber === '') {
            return null;
        }

        $row = db_fetch_one(
            'SELECT id FROM dh_documents WHERE documentType = ? AND documentNumber = ? LIMIT 1',
            'ss',
            $documentType,
            $documentNumber
        );
        if (!$row || !isset($row['id'])) {
            return null;
        }

        return (int) $row['id'];
    }
}

if (!function_exists('document_add_recipients')) {
    function document_add_recipients(int $documentID, array $recipientPIDs, string $inboxType = 'normal_inbox'): void
    {
        $connection = db_connection();
        if (!db_table_exists($connection, 'dh_document_recipients')) {
            return;
        }

        $recipientPIDs = array_values(array_unique(array_filter(array_map('trim', $recipientPIDs))));
        if (empty($recipientPIDs)) {
            return;
        }

        foreach ($recipientPIDs as $pid) {
            $stmt = db_query(
                'INSERT INTO dh_document_recipients (documentID, recipientPID, inboxType, inboxStatus, receivedAt)
                 VALUES (?, ?, ?, "UNREAD", NOW())
                 ON DUPLICATE KEY UPDATE inboxStatus = "UNREAD", receivedAt = NOW()',
                'iss',
                $documentID,
                $pid,
                $inboxType
            );
            mysqli_stmt_close($stmt);
        }
    }
}

if (!function_exists('document_set_recipient_status')) {
    function document_set_recipient_status(int $documentID, string $recipientPID, string $inboxType, string $status): void
    {
        $connection = db_connection();
        if (!db_table_exists($connection, 'dh_document_recipients')) {
            return;
        }

        $allowed = ['UNREAD', 'READ', 'ARCHIVED'];
        $status = strtoupper(trim($status));
        if (!in_array($status, $allowed, true)) {
            return;
        }

        $readAt = null;
        if ($status === 'READ') {
            $readAt = date('Y-m-d H:i:s');
        }

        $stmt = db_query(
            'UPDATE dh_document_recipients SET inboxStatus = ?, readAt = ? WHERE documentID = ? AND recipientPID = ? AND inboxType = ?',
            'ssiss',
            $status,
            $readAt,
            $documentID,
            $recipientPID,
            $inboxType
        );
        mysqli_stmt_close($stmt);
    }
}

if (!function_exists('document_mark_read')) {
    function document_mark_read(int $documentID, string $recipientPID): void
    {
        $connection = db_connection();
        if (!db_table_exists($connection, 'dh_document_recipients')) {
            return;
        }

        $stmt = db_query(
            'UPDATE dh_document_recipients SET inboxStatus = "READ", readAt = NOW() WHERE documentID = ? AND recipientPID = ? AND inboxStatus <> "READ"',
            'is',
            $documentID,
            $recipientPID
        );
        mysqli_stmt_close($stmt);
    }
}

if (!function_exists('document_record_read_receipt')) {
    function document_record_read_receipt(int $documentID, string $recipientPID): void
    {
        $connection = db_connection();
        if (!db_table_exists($connection, 'dh_read_receipts')) {
            return;
        }

        $existing = db_fetch_one('SELECT receiptID FROM dh_read_receipts WHERE documentID = ? AND recipientPID = ? LIMIT 1', 'is', $documentID, $recipientPID);
        if ($existing) {
            return;
        }

        $readAt = date('Y-m-d H:i:s');
        $requestID = app_request_id();
        if (strlen($requestID) > 26) {
            $requestID = substr($requestID, 0, 26);
        }
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        $userAgent = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
        $hashSeed = $documentID . '|' . $recipientPID . '|' . $readAt . '|' . $requestID . '|' . $ip . '|' . $userAgent;
        $receiptHash = hash('sha256', $hashSeed);

        $stmt = db_query(
            'INSERT INTO dh_read_receipts (documentID, recipientPID, readAt, requestID, ipAddress, userAgent, receiptHash)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            'issssss',
            $documentID,
            $recipientPID,
            $readAt,
            $requestID,
            $ip,
            $userAgent,
            $receiptHash
        );
        mysqli_stmt_close($stmt);
    }
}

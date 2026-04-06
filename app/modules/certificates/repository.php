<?php

declare(strict_types=1);

require_once __DIR__ . '/../../db/db.php';

const CERTIFICATE_MODULE_NAME = 'certificates';
const CERTIFICATE_ENTITY_NAME = 'dh_certificates';
const CERTIFICATE_STATUS_WAITING_ATTACHMENT = 'WAITING_ATTACHMENT';
const CERTIFICATE_STATUS_COMPLETE = 'COMPLETE';

if (!function_exists('certificates_ensure_schema')) {
    function certificates_ensure_schema(mysqli $connection): void
    {
        static $ensured = false;

        if ($ensured) {
            return;
        }

        if (!db_table_exists($connection, CERTIFICATE_ENTITY_NAME)) {
            $sql = <<<'SQL'
CREATE TABLE `dh_certificates` (
  `certificateID` bigint(20) NOT NULL AUTO_INCREMENT,
  `dh_year` int(4) NOT NULL,
  `certificateFromNo` varchar(50) NOT NULL,
  `certificateToNo` varchar(50) NOT NULL,
  `certificateFromSeq` int(11) NOT NULL,
  `certificateToSeq` int(11) NOT NULL,
  `totalCertificates` int(11) NOT NULL,
  `subject` varchar(300) NOT NULL,
  `groupFID` int(3) DEFAULT NULL,
  `status` enum('WAITING_ATTACHMENT','COMPLETE') NOT NULL DEFAULT 'WAITING_ATTACHMENT',
  `createdByPID` varchar(13) NOT NULL,
  `updatedByPID` varchar(13) DEFAULT NULL,
  `deletedAt` datetime DEFAULT NULL,
  `createdAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `updatedAt` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
  PRIMARY KEY (`certificateID`),
  UNIQUE KEY `uq_certificate_from_seq` (`dh_year`,`certificateFromSeq`),
  UNIQUE KEY `uq_certificate_to_seq` (`dh_year`,`certificateToSeq`),
  KEY `idx_certificate_year_created` (`dh_year`,`createdAt`),
  KEY `idx_certificate_creator` (`createdByPID`,`createdAt`),
  KEY `fk_certificate_group` (`groupFID`),
  KEY `fk_certificate_updated` (`updatedByPID`),
  CONSTRAINT `fk_certificate_created` FOREIGN KEY (`createdByPID`) REFERENCES `teacher` (`pID`) ON UPDATE CASCADE,
  CONSTRAINT `fk_certificate_group` FOREIGN KEY (`groupFID`) REFERENCES `faction` (`fID`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_certificate_updated` FOREIGN KEY (`updatedByPID`) REFERENCES `teacher` (`pID`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
SQL;

            if (mysqli_query($connection, $sql) === false) {
                error_log('Database Error (create dh_certificates): ' . mysqli_error($connection));
                throw new RuntimeException('ไม่สามารถเตรียมตารางเกียรติบัตรได้');
            }
        }

        mysqli_query(
            $connection,
            'ALTER TABLE dh_certificates
             MODIFY status ENUM(\'ISSUED\', \'WAITING_ATTACHMENT\', \'COMPLETE\') NOT NULL DEFAULT \'WAITING_ATTACHMENT\''
        );

        mysqli_query(
            $connection,
            'UPDATE dh_certificates AS c
             LEFT JOIN (
                SELECT
                    CAST(r.entityID AS UNSIGNED) AS certificateID,
                    COUNT(f.fileID) AS attachmentCount
                FROM dh_file_refs AS r
                INNER JOIN dh_files AS f ON r.fileID = f.fileID AND f.deletedAt IS NULL
                WHERE r.moduleName = \'' . CERTIFICATE_MODULE_NAME . '\'
                  AND r.entityName = \'' . CERTIFICATE_ENTITY_NAME . '\'
                GROUP BY CAST(r.entityID AS UNSIGNED)
             ) AS a ON a.certificateID = c.certificateID
             SET c.status = CASE
                WHEN COALESCE(a.attachmentCount, 0) > 0 THEN \'' . CERTIFICATE_STATUS_COMPLETE . '\'
                ELSE \'' . CERTIFICATE_STATUS_WAITING_ATTACHMENT . '\'
             END
             WHERE c.status IN (\'ISSUED\', \'' . CERTIFICATE_STATUS_WAITING_ATTACHMENT . '\', \'' . CERTIFICATE_STATUS_COMPLETE . '\')
                OR c.status IS NULL'
        );

        mysqli_query(
            $connection,
            'ALTER TABLE dh_certificates
             MODIFY status ENUM(\'' . CERTIFICATE_STATUS_WAITING_ATTACHMENT . '\', \'' . CERTIFICATE_STATUS_COMPLETE . '\') NOT NULL DEFAULT \'' . CERTIFICATE_STATUS_WAITING_ATTACHMENT . '\''
        );

        mysqli_query(
            $connection,
            'UPDATE dh_certificates
             SET
                certificateFromNo = REPLACE(REPLACE(REPLACE(certificateFromNo, " - ", "-"), " -", "-"), "- ", "-"),
                certificateToNo = REPLACE(REPLACE(REPLACE(certificateToNo, " - ", "-"), " -", "-"), "- ", "-")
             WHERE certificateFromNo LIKE "% - %" OR certificateFromNo LIKE "% -%" OR certificateFromNo LIKE "%- %"
                OR certificateToNo LIKE "% - %" OR certificateToNo LIKE "% -%" OR certificateToNo LIKE "%- %"'
        );

        mysqli_query(
            $connection,
            'UPDATE dh_certificates
             SET
                certificateFromNo = CONCAT("ด.บ.", dh_year, "-", LPAD(certificateFromSeq, 5, "0")),
                certificateToNo = CONCAT("ด.บ.", dh_year, "-", LPAD(certificateToSeq, 5, "0"))
             WHERE certificateFromNo <> CONCAT("ด.บ.", dh_year, "-", LPAD(certificateFromSeq, 5, "0"))
                OR certificateToNo <> CONCAT("ด.บ.", dh_year, "-", LPAD(certificateToSeq, 5, "0"))'
        );

        $ensured = true;
    }
}

if (!function_exists('certificate_create_record')) {
    function certificate_create_record(array $data): int
    {
        $stmt = db_query(
            'INSERT INTO dh_certificates (
                dh_year,
                certificateFromNo,
                certificateToNo,
                certificateFromSeq,
                certificateToSeq,
                totalCertificates,
                subject,
                groupFID,
                status,
                createdByPID,
                updatedByPID
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            'issiiisisss',
            (int) $data['dh_year'],
            (string) $data['certificateFromNo'],
            (string) $data['certificateToNo'],
            (int) $data['certificateFromSeq'],
            (int) $data['certificateToSeq'],
            (int) $data['totalCertificates'],
            (string) $data['subject'],
            isset($data['groupFID']) ? (int) $data['groupFID'] : null,
            (string) $data['status'],
            (string) $data['createdByPID'],
            $data['updatedByPID'] ?? null
        );
        $id = db_last_insert_id();
        mysqli_stmt_close($stmt);

        return $id;
    }
}

if (!function_exists('certificate_update_record')) {
    function certificate_update_record(int $certificateID, array $data): void
    {
        $fields = [];
        $params = [];
        $types = '';

        foreach ($data as $field => $value) {
            $fields[] = $field . ' = ?';
            $types .= is_int($value) ? 'i' : 's';
            $params[] = $value;
        }

        if ($fields === []) {
            return;
        }

        $types .= 'i';
        $params[] = $certificateID;

        $stmt = db_query(
            'UPDATE dh_certificates SET ' . implode(', ', $fields) . ' WHERE certificateID = ?',
            $types,
            ...$params
        );
        mysqli_stmt_close($stmt);
    }
}

if (!function_exists('certificate_get')) {
    function certificate_get(int $certificateID): ?array
    {
        $sql = 'SELECT
                c.*,
                t.fName AS creatorName,
                f.fName AS groupName,
                COUNT(df.fileID) AS attachmentCount
            FROM dh_certificates AS c
            LEFT JOIN teacher AS t ON c.createdByPID = t.pID
            LEFT JOIN faction AS f ON c.groupFID = f.fID
            LEFT JOIN dh_file_refs AS r
                ON r.moduleName = (\'' . CERTIFICATE_MODULE_NAME . '\' COLLATE utf8mb4_general_ci)
                AND r.entityName = (\'' . CERTIFICATE_ENTITY_NAME . '\' COLLATE utf8mb4_general_ci)
                AND CAST(r.entityID AS UNSIGNED) = c.certificateID
            LEFT JOIN dh_files AS df
                ON r.fileID = df.fileID
                AND df.deletedAt IS NULL
            WHERE c.certificateID = ?
            GROUP BY
                c.certificateID,
                c.dh_year,
                c.certificateFromNo,
                c.certificateToNo,
                c.certificateFromSeq,
                c.certificateToSeq,
                c.totalCertificates,
                c.subject,
                c.groupFID,
                c.status,
                c.createdByPID,
                c.updatedByPID,
                c.deletedAt,
                c.createdAt,
                c.updatedAt,
                t.fName,
                f.fName
            LIMIT 1';

        return db_fetch_one($sql, 'i', $certificateID);
    }
}

if (!function_exists('certificate_get_for_owner')) {
    function certificate_get_for_owner(int $certificateID, string $ownerPID): ?array
    {
        $sql = 'SELECT
                c.*,
                t.fName AS creatorName,
                f.fName AS groupName
            FROM dh_certificates AS c
            LEFT JOIN teacher AS t ON c.createdByPID = t.pID
            LEFT JOIN faction AS f ON c.groupFID = f.fID
            WHERE c.certificateID = ?
              AND c.createdByPID = ?
              AND c.deletedAt IS NULL
            LIMIT 1';

        return db_fetch_one($sql, 'is', $certificateID, $ownerPID);
    }
}

if (!function_exists('certificate_list')) {
    function certificate_list(array $filters = []): array
    {
        $search = trim((string) ($filters['q'] ?? ''));
        $status = strtoupper(trim((string) ($filters['status'] ?? 'all')));
        $created_by_pid = trim((string) ($filters['created_by_pid'] ?? ''));
        $sort = strtolower(trim((string) ($filters['sort'] ?? 'newest')));
        $params = [];
        $types = '';

        $sql = 'SELECT
                c.certificateID,
                c.dh_year,
                c.certificateFromNo,
                c.certificateToNo,
                c.certificateFromSeq,
                c.certificateToSeq,
                c.totalCertificates,
                c.subject,
                c.groupFID,
                c.status,
                c.createdByPID,
                c.updatedByPID,
                c.createdAt,
                c.updatedAt,
                t.fName AS creatorName,
                f.fName AS groupName,
                COUNT(df.fileID) AS attachmentCount
            FROM dh_certificates AS c
            LEFT JOIN teacher AS t ON c.createdByPID = t.pID
            LEFT JOIN faction AS f ON c.groupFID = f.fID
            LEFT JOIN dh_file_refs AS r
                ON r.moduleName = (\'' . CERTIFICATE_MODULE_NAME . '\' COLLATE utf8mb4_general_ci)
                AND r.entityName = (\'' . CERTIFICATE_ENTITY_NAME . '\' COLLATE utf8mb4_general_ci)
                AND CAST(r.entityID AS UNSIGNED) = c.certificateID
            LEFT JOIN dh_files AS df
                ON r.fileID = df.fileID
                AND df.deletedAt IS NULL
            WHERE c.deletedAt IS NULL';

        if ($search !== '') {
            $like = '%' . $search . '%';
            $sql .= ' AND (
                c.subject LIKE ?
                OR c.certificateFromNo LIKE ?
                OR c.certificateToNo LIKE ?
                OR t.fName LIKE ?
                OR f.fName LIKE ?
            )';
            $types .= 'sssss';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if ($status !== '' && $status !== 'ALL') {
            $sql .= ' AND c.status = ?';
            $types .= 's';
            $params[] = $status;
        }

        if ($created_by_pid !== '') {
            $sql .= ' AND c.createdByPID = ?';
            $types .= 's';
            $params[] = $created_by_pid;
        }

        $sort_direction = $sort === 'oldest' ? 'ASC' : 'DESC';

        $sql .= ' GROUP BY
                c.certificateID,
                c.dh_year,
                c.certificateFromNo,
                c.certificateToNo,
                c.certificateFromSeq,
                c.certificateToSeq,
                c.totalCertificates,
                c.subject,
                c.groupFID,
                c.status,
                c.createdByPID,
                c.updatedByPID,
                c.createdAt,
                c.updatedAt,
                t.fName,
                f.fName
            ORDER BY c.createdAt ' . $sort_direction . ', c.certificateID ' . $sort_direction;

        return db_fetch_all($sql, $types, ...$params);
    }
}

if (!function_exists('certificate_get_attachments')) {
    function certificate_get_attachments(int $certificateID): array
    {
        $sql = 'SELECT f.fileID, f.fileName, f.filePath, f.mimeType, f.fileSize
            FROM dh_file_refs AS r
            INNER JOIN dh_files AS f ON r.fileID = f.fileID
            WHERE r.moduleName = (\'' . CERTIFICATE_MODULE_NAME . '\' COLLATE utf8mb4_general_ci)
              AND r.entityName = (\'' . CERTIFICATE_ENTITY_NAME . '\' COLLATE utf8mb4_general_ci)
              AND CAST(r.entityID AS UNSIGNED) = ?
              AND f.deletedAt IS NULL
            ORDER BY r.refID ASC';

        return db_fetch_all($sql, 'i', $certificateID);
    }
}

if (!function_exists('certificate_soft_delete_attachments')) {
    function certificate_soft_delete_attachments(int $certificateID, array $fileIDs): int
    {
        $normalized = array_values(array_unique(array_filter(array_map(static function ($value): int {
            return (int) $value;
        }, $fileIDs), static function (int $fileID): bool {
            return $fileID > 0;
        })));

        if ($normalized === []) {
            return 0;
        }

        $placeholders = implode(', ', array_fill(0, count($normalized), '?'));
        $types = 'sss' . str_repeat('i', count($normalized));
        $params = array_merge([CERTIFICATE_MODULE_NAME, CERTIFICATE_ENTITY_NAME, (string) $certificateID], $normalized);
        $sql = 'UPDATE dh_files AS f
            INNER JOIN dh_file_refs AS r ON r.fileID = f.fileID
            SET f.deletedAt = NOW()
            WHERE r.moduleName = ?
              AND r.entityName = ?
              AND r.entityID = ?
              AND f.deletedAt IS NULL
              AND f.fileID IN (' . $placeholders . ')';
        $stmt = db_query($sql, $types, ...$params);
        $affected = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);

        return max(0, (int) $affected);
    }
}

if (!function_exists('certificate_list_attachments_map')) {
    /**
     * @param array<int, int> $certificateIDs
     * @return array<string, array<int, array<string, mixed>>>
     */
    function certificate_list_attachments_map(array $certificateIDs): array
    {
        $certificateIDs = array_values(array_unique(array_filter(array_map('intval', $certificateIDs), static function (int $id): bool {
            return $id > 0;
        })));

        if ($certificateIDs === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($certificateIDs), '?'));
        $types = str_repeat('i', count($certificateIDs));
        $params = $certificateIDs;

        $sql = 'SELECT r.entityID, f.fileID, f.fileName, f.filePath, f.mimeType, f.fileSize
            FROM dh_file_refs AS r
            INNER JOIN dh_files AS f ON r.fileID = f.fileID
            WHERE r.moduleName = (\'' . CERTIFICATE_MODULE_NAME . '\' COLLATE utf8mb4_general_ci)
              AND r.entityName = (\'' . CERTIFICATE_ENTITY_NAME . '\' COLLATE utf8mb4_general_ci)
              AND CAST(r.entityID AS UNSIGNED) IN (' . $placeholders . ')
              AND f.deletedAt IS NULL
            ORDER BY r.refID ASC';

        $rows = db_fetch_all($sql, $types, ...$params);
        $map = [];

        foreach ($rows as $row) {
            $entity_id = trim((string) ($row['entityID'] ?? ''));

            if ($entity_id === '') {
                continue;
            }

            if (!isset($map[$entity_id])) {
                $map[$entity_id] = [];
            }

            $map[$entity_id][] = [
                'fileID' => (int) ($row['fileID'] ?? 0),
                'fileName' => trim((string) ($row['fileName'] ?? '')),
                'filePath' => trim((string) ($row['filePath'] ?? '')),
                'mimeType' => trim((string) ($row['mimeType'] ?? '')),
                'fileSize' => (int) ($row['fileSize'] ?? 0),
            ];
        }

        return $map;
    }
}

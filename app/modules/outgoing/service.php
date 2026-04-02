<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/../system/system.php';
require_once __DIR__ . '/../audit/logger.php';
require_once __DIR__ . '/../../services/uploads.php';
require_once __DIR__ . '/../../services/document-service.php';
require_once __DIR__ . '/../../rbac/roles.php';

if (!function_exists('outgoing_user_can_manage')) {
    function outgoing_user_can_manage(mysqli $connection, string $pID, array $current_user = []): bool
    {
        $pID = trim($pID);

        if ($pID === '') {
            return false;
        }

        if (rbac_user_has_role($connection, $pID, ROLE_ADMIN) || rbac_user_has_role($connection, $pID, ROLE_REGISTRY)) {
            return true;
        }

        // Legacy fallback (single role column on teacher)
        $legacy_role = (int) ($current_user['roleID'] ?? 0);

        return in_array($legacy_role, [1, 2], true);
    }
}

if (!function_exists('outgoing_document_number')) {
    function outgoing_document_number(array $outgoing): string
    {
        $number = trim((string) ($outgoing['outgoingNo'] ?? ''));

        if ($number !== '') {
            return $number;
        }
        $outgoingID = (int) ($outgoing['outgoingID'] ?? 0);

        return $outgoingID > 0 ? 'OUT-' . $outgoingID : '';
    }
}

if (!function_exists('outgoing_sync_document')) {
    function outgoing_sync_document(int $outgoingID): ?int
    {
        $outgoing = outgoing_get($outgoingID);

        if (!$outgoing) {
            return null;
        }

        $documentNumber = outgoing_document_number($outgoing);

        if ($documentNumber === '') {
            return null;
        }

        return document_upsert([
            'documentType' => 'OUTGOING',
            'documentNumber' => $documentNumber,
            'subject' => (string) ($outgoing['subject'] ?? ''),
            'content' => (string) ($outgoing['detail'] ?? ''),
            'status' => (string) ($outgoing['status'] ?? ''),
            'senderName' => (string) ($outgoing['creatorName'] ?? ''),
            'createdByPID' => (string) ($outgoing['createdByPID'] ?? ''),
            'updatedByPID' => $outgoing['updatedByPID'] ?? null,
        ]);
    }
}

if (!function_exists('outgoing_prefix')) {
    function outgoing_prefix(): string
    {
        $prefix = $_ENV['OUTGOING_PREFIX'] ?? 'ศธ';
        $code = $_ENV['OUTGOING_CODE'] ?? '04320.05';
        $prefix = trim((string) $prefix);
        $code = trim((string) $code);

        if ($prefix === '') {
            return $code;
        }

        if ($code === '') {
            return $prefix;
        }

        return $prefix . ' ' . $code;
    }
}

if (!function_exists('outgoing_generate_number')) {
    function outgoing_generate_number(int $year): array
    {
        $row = db_fetch_one('SELECT outgoingSeq FROM dh_outgoing_letters WHERE dh_year = ? ORDER BY outgoingSeq DESC LIMIT 1 FOR UPDATE', 'i', $year);
        $seq = $row ? ((int) $row['outgoingSeq'] + 1) : 1;
        $number = outgoing_prefix() . '/' . $seq;

        return [$number, $seq];
    }
}

if (!function_exists('outgoing_preview_number')) {
    function outgoing_preview_number(int $year): string
    {
        $row = db_fetch_one('SELECT outgoingSeq FROM dh_outgoing_letters WHERE dh_year = ? ORDER BY outgoingSeq DESC LIMIT 1', 'i', $year);
        $seq = $row ? ((int) $row['outgoingSeq'] + 1) : 1;
        $number = outgoing_prefix() . '/' . $seq;

        return $number;
    }
}

if (!function_exists('outgoing_audit_payload')) {
    function outgoing_audit_payload(array $payload): array
    {
        return array_filter($payload, static function ($value): bool {
            return $value !== null && $value !== '' && $value !== [];
        });
    }
}

if (!function_exists('outgoing_create_draft')) {
    function outgoing_create_draft(array $data, array $files = []): int
    {
        $normalized_files = array_values(array_filter(
            upload_normalize_files($files),
            static function (array $file): bool {
                return (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
            }
        ));
        $audit_payload = outgoing_audit_payload([
            'dhYear' => (int) ($data['dh_year'] ?? 0),
            'subject' => trim((string) ($data['subject'] ?? '')),
            'requestedStatus' => trim((string) ($data['status'] ?? '')),
            'createdByPID' => trim((string) ($data['createdByPID'] ?? '')),
            'incomingAttachmentCount' => count($normalized_files),
        ]);

        db_begin();

        try {
            [$outgoingNo, $seq] = outgoing_generate_number((int) $data['dh_year']);
            $data['outgoingNo'] = $outgoingNo;
            $data['outgoingSeq'] = $seq;
            $outgoingID = outgoing_create_record($data);

            if (!empty($normalized_files)) {
                upload_store_files($files, OUTGOING_MODULE_NAME, OUTGOING_ENTITY_NAME, (string) $outgoingID, (string) $data['createdByPID'], [
                    'max_files' => 5,
                ]);
                outgoing_update_record($outgoingID, [
                    'status' => OUTGOING_STATUS_COMPLETE,
                    'updatedByPID' => $data['createdByPID'],
                ]);
            }

            outgoing_sync_document($outgoingID);
            $created_outgoing = outgoing_get($outgoingID) ?? [];
            $stored_attachments = outgoing_get_attachments($outgoingID);

            db_commit();
            audit_log('outgoing', 'CREATE', 'SUCCESS', 'dh_outgoing_letters', $outgoingID, null, outgoing_audit_payload(array_merge($audit_payload, [
                'outgoingNo' => outgoing_document_number($created_outgoing),
                'outgoingSeq' => (int) ($created_outgoing['outgoingSeq'] ?? $seq),
                'finalStatus' => trim((string) ($created_outgoing['status'] ?? ($data['status'] ?? ''))),
                'storedAttachmentCount' => count($stored_attachments),
            ])));

            return $outgoingID;
        } catch (Throwable $e) {
            db_rollback();
            error_log('Outgoing create failed: ' . $e->getMessage());
            audit_log('outgoing', 'CREATE', 'FAIL', 'dh_outgoing_letters', null, $e->getMessage(), outgoing_audit_payload(array_merge($audit_payload, [
                'outgoingNo' => trim((string) ($data['outgoingNo'] ?? '')),
                'outgoingSeq' => (int) ($data['outgoingSeq'] ?? 0),
            ])));
            throw $e;
        }
    }
}

if (!function_exists('outgoing_attach_files')) {
    function outgoing_attach_files(int $outgoingID, string $actorPID, array $files): void
    {
        $outgoing = outgoing_get($outgoingID);

        if (!$outgoing) {
            audit_log('outgoing', 'ATTACH', 'FAIL', 'dh_outgoing_letters', $outgoingID > 0 ? $outgoingID : null, 'not_found', outgoing_audit_payload([
                'actorPID' => trim($actorPID),
            ]));
            throw new RuntimeException('ไม่พบรายการหนังสือออก');
        }

        $status = (string) ($outgoing['status'] ?? '');
        $existing_count = count(outgoing_get_attachments($outgoingID));

        if ($status !== OUTGOING_STATUS_WAITING_ATTACHMENT) {
            audit_log('outgoing', 'ATTACH', 'FAIL', 'dh_outgoing_letters', $outgoingID, 'invalid_status_for_attach', outgoing_audit_payload([
                'actorPID' => trim($actorPID),
                'currentStatus' => $status,
                'existingAttachmentCount' => $existing_count,
            ]));
            throw new RuntimeException('รายการนี้ไม่อยู่ในสถานะรอแนบไฟล์');
        }

        $normalized_files = array_values(array_filter(
            upload_normalize_files($files),
            static function (array $file): bool {
                return (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
            }
        ));
        $incoming_attachment_count = count($normalized_files);
        $audit_payload = outgoing_audit_payload([
            'actorPID' => trim($actorPID),
            'outgoingNo' => outgoing_document_number($outgoing),
            'currentStatus' => $status,
            'existingAttachmentCount' => $existing_count,
            'incomingAttachmentCount' => $incoming_attachment_count,
        ]);

        if (empty($normalized_files)) {
            audit_log('outgoing', 'ATTACH', 'FAIL', 'dh_outgoing_letters', $outgoingID, 'missing_attachments', $audit_payload);
            throw new RuntimeException('กรุณาเลือกไฟล์อย่างน้อย 1 ไฟล์');
        }

        if (($existing_count + count($normalized_files)) > 5) {
            audit_log('outgoing', 'ATTACH', 'FAIL', 'dh_outgoing_letters', $outgoingID, 'attachment_limit_exceeded', outgoing_audit_payload(array_merge($audit_payload, [
                'maxFiles' => 5,
            ])));
            throw new RuntimeException('แนบไฟล์ได้สูงสุด 5 ไฟล์');
        }

        db_begin();

        try {
            upload_store_files($files, OUTGOING_MODULE_NAME, OUTGOING_ENTITY_NAME, (string) $outgoingID, $actorPID, [
                'max_files' => 5,
            ]);
            outgoing_update_record($outgoingID, [
                'status' => OUTGOING_STATUS_COMPLETE,
                'updatedByPID' => $actorPID,
            ]);
            outgoing_sync_document($outgoingID);
            $updated_outgoing = outgoing_get($outgoingID) ?? $outgoing;
            $stored_attachments = outgoing_get_attachments($outgoingID);
            db_commit();
            audit_log('outgoing', 'ATTACH', 'SUCCESS', 'dh_outgoing_letters', $outgoingID, null, outgoing_audit_payload(array_merge($audit_payload, [
                'finalStatus' => trim((string) ($updated_outgoing['status'] ?? '')),
                'storedAttachmentCount' => count($stored_attachments),
            ])));
        } catch (Throwable $e) {
            db_rollback();
            error_log('Outgoing attach failed: ' . $e->getMessage());
            audit_log('outgoing', 'ATTACH', 'FAIL', 'dh_outgoing_letters', $outgoingID, $e->getMessage(), $audit_payload);
            throw $e;
        }
    }
}

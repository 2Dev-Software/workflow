<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/../system/system.php';
require_once __DIR__ . '/../audit/logger.php';
require_once __DIR__ . '/../../services/uploads.php';
require_once __DIR__ . '/../../services/document-service.php';

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
        $prefix = $_ENV['OUTGOING_PREFIX'] ?? 'ศธ.';
        $code = $_ENV['OUTGOING_CODE'] ?? '01234';
        $prefix = trim((string) $prefix);
        $code = trim((string) $code);
        return $code !== '' ? $prefix . $code : $prefix;
    }
}

if (!function_exists('outgoing_generate_number')) {
    function outgoing_generate_number(int $year): array
    {
        $row = db_fetch_one('SELECT outgoingSeq FROM dh_outgoing_letters WHERE dh_year = ? ORDER BY outgoingSeq DESC LIMIT 1 FOR UPDATE', 'i', $year);
        $seq = $row ? ((int) $row['outgoingSeq'] + 1) : 1;
        $number = outgoing_prefix() . '/' . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
        return [$number, $seq];
    }
}

if (!function_exists('outgoing_create_draft')) {
    function outgoing_create_draft(array $data, array $files = []): int
    {
        db_begin();
        try {
            [$outgoingNo, $seq] = outgoing_generate_number((int) $data['dh_year']);
            $data['outgoingNo'] = $outgoingNo;
            $data['outgoingSeq'] = $seq;
            $outgoingID = outgoing_create_record($data);

            if (!empty($files)) {
                upload_store_files($files, OUTGOING_MODULE_NAME, OUTGOING_ENTITY_NAME, (string) $outgoingID, (string) $data['createdByPID'], [
                    'max_files' => 5,
                ]);
                outgoing_update_record($outgoingID, [
                    'status' => OUTGOING_STATUS_COMPLETE,
                    'updatedByPID' => $data['createdByPID'],
                ]);
            }

            outgoing_sync_document($outgoingID);

            db_commit();
            audit_log('outgoing', 'CREATE', 'SUCCESS', 'dh_outgoing_letters', $outgoingID);

            return $outgoingID;
        } catch (Throwable $e) {
            db_rollback();
            error_log('Outgoing create failed: ' . $e->getMessage());
            audit_log('outgoing', 'CREATE', 'FAIL', 'dh_outgoing_letters', null, $e->getMessage());
            throw $e;
        }
    }
}

if (!function_exists('outgoing_attach_files')) {
    function outgoing_attach_files(int $outgoingID, string $actorPID, array $files): void
    {
        if (empty($files)) {
            return;
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
            db_commit();
            audit_log('outgoing', 'ATTACH', 'SUCCESS', 'dh_outgoing_letters', $outgoingID);
        } catch (Throwable $e) {
            db_rollback();
            error_log('Outgoing attach failed: ' . $e->getMessage());
            audit_log('outgoing', 'ATTACH', 'FAIL', 'dh_outgoing_letters', $outgoingID, $e->getMessage());
            throw $e;
        }
    }
}

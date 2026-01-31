<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/../system/system.php';
require_once __DIR__ . '/../audit/logger.php';
require_once __DIR__ . '/../../services/uploads.php';

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

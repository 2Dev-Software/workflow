<?php

declare(strict_types=1);

require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/../../services/uploads.php';
require_once __DIR__ . '/../system/system.php';
require_once __DIR__ . '/../audit/logger.php';
require_once __DIR__ . '/../../db/db.php';

if (!function_exists('repair_form_defaults')) {
    function repair_form_defaults(): array
    {
        return [
            'subject' => '',
            'location' => '',
            'equipment' => '',
            'detail' => '',
        ];
    }
}

if (!function_exists('repair_normalize_form_data')) {
    function repair_normalize_form_data(array $input): array
    {
        return [
            'subject' => trim((string) ($input['subject'] ?? '')),
            'location' => trim((string) ($input['location'] ?? '')),
            'equipment' => trim((string) ($input['equipment'] ?? '')),
            'detail' => trim((string) ($input['detail'] ?? '')),
        ];
    }
}

if (!function_exists('repair_validate_create_data')) {
    function repair_validate_create_data(array $data): void
    {
        if (trim((string) ($data['subject'] ?? '')) === '') {
            throw new RuntimeException('กรุณากรอกหัวข้อ');
        }

        if (trim((string) ($data['location'] ?? '')) === '') {
            throw new RuntimeException('กรุณากรอกสถานที่');
        }

        if (trim((string) ($data['detail'] ?? '')) === '') {
            throw new RuntimeException('กรุณากรอกรายละเอียดเพิ่มเติม');
        }
    }
}

if (!function_exists('repair_has_uploads')) {
    function repair_has_uploads(array $files): bool
    {
        foreach (upload_normalize_files($files) as $file) {
            if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('repair_create_request')) {
    function repair_create_request(array $input, array $files, string $actor_pid): int
    {
        $data = repair_normalize_form_data($input);
        repair_validate_create_data($data);

        db_begin();

        try {
            $repair_id = repair_create_record([
                'dh_year' => system_get_dh_year(),
                'requesterPID' => $actor_pid,
                'subject' => $data['subject'],
                'detail' => $data['detail'],
                'location' => $data['location'],
                'equipment' => $data['equipment'] !== '' ? $data['equipment'] : null,
                'status' => REPAIR_STATUS_PENDING,
                'assignedToPID' => null,
            ]);

            if (repair_has_uploads($files)) {
                upload_store_files($files, REPAIR_MODULE_NAME, REPAIR_ENTITY_NAME, (string) $repair_id, $actor_pid, [
                    'max_files' => 0,
                    'allowed_mimes' => upload_allowed_image_mimes(),
                ]);
            }

            db_commit();

            if (function_exists('audit_log')) {
                audit_log('repairs', 'CREATE', 'SUCCESS', REPAIR_ENTITY_NAME, $repair_id);
            }

            return $repair_id;
        } catch (Throwable $exception) {
            db_rollback();

            if (function_exists('audit_log')) {
                audit_log('repairs', 'CREATE', 'FAIL', REPAIR_ENTITY_NAME, null, $exception->getMessage());
            }

            throw $exception;
        }
    }
}

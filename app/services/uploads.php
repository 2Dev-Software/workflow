<?php

declare(strict_types=1);

require_once __DIR__ . '/../db/db.php';

if (!function_exists('upload_normalize_files')) {
    function upload_normalize_files(array $files): array
    {
        if (isset($files['name']) && isset($files['tmp_name'])) {
            $files = ['file' => $files];
        }

        $normalized = [];

        foreach ($files as $field => $data) {
            if (!is_array($data) || !isset($data['name'])) {
                continue;
            }

            if (is_array($data['name'])) {
                $count = count($data['name']);

                for ($i = 0; $i < $count; $i++) {
                    $normalized[] = [
                        'field' => $field,
                        'name' => $data['name'][$i] ?? '',
                        'type' => $data['type'][$i] ?? '',
                        'tmp_name' => $data['tmp_name'][$i] ?? '',
                        'error' => $data['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                        'size' => $data['size'][$i] ?? 0,
                    ];
                }
            } else {
                $normalized[] = [
                    'field' => $field,
                    'name' => $data['name'] ?? '',
                    'type' => $data['type'] ?? '',
                    'tmp_name' => $data['tmp_name'] ?? '',
                    'error' => $data['error'] ?? UPLOAD_ERR_NO_FILE,
                    'size' => $data['size'] ?? 0,
                ];
            }
        }

        return $normalized;
    }
}

if (!function_exists('upload_allowed_mimes')) {
    function upload_allowed_mimes(): array
    {
        return [
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
        ];
    }
}

if (!function_exists('upload_scan_for_viruses')) {
    function upload_scan_for_viruses(string $path): bool
    {
        // Placeholder for antivirus integration. Always true for now.
        return true;
    }
}

if (!function_exists('upload_store_files')) {
    function upload_store_files(
        array $files,
        string $module,
        string $entityName,
        string $entityId,
        string $uploaderPID,
        array $options = []
    ): array {
        $module = trim($module);
        $entityName = trim($entityName);
        $entityId = trim($entityId);
        $uploaderPID = trim($uploaderPID);

        if ($module === '' || $entityName === '' || $entityId === '' || $uploaderPID === '') {
            return [];
        }

        $max_files = (int) ($options['max_files'] ?? 5);
        $max_size = (int) ($options['max_size'] ?? (5 * 1024 * 1024));
        $base_dir = (string) ($options['base_dir'] ?? (__DIR__ . '/../../storage/uploads'));

        $allowed = upload_allowed_mimes();
        $normalized = upload_normalize_files($files);
        $normalized = array_values(array_filter($normalized, static function (array $file): bool {
            return (int) $file['error'] !== UPLOAD_ERR_NO_FILE;
        }));

        if (empty($normalized)) {
            return [];
        }

        if ($max_files > 0 && count($normalized) > $max_files) {
            throw new RuntimeException('แนบไฟล์ได้สูงสุด ' . $max_files . ' ไฟล์');
        }

        $connection = db_connection();
        $stored = [];

        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        foreach ($normalized as $file) {
            $error = (int) $file['error'];

            if ($error !== UPLOAD_ERR_OK) {
                throw new RuntimeException('อัปโหลดไฟล์ไม่สำเร็จ');
            }

            $tmp = (string) $file['tmp_name'];
            $size = (int) $file['size'];

            if ($size <= 0 || $size > $max_size) {
                throw new RuntimeException('ขนาดไฟล์เกินกำหนด');
            }

            $mime = $finfo ? finfo_file($finfo, $tmp) : (string) $file['type'];
            $mime = $mime ?: (string) $file['type'];

            if (!isset($allowed[$mime])) {
                throw new RuntimeException('ชนิดไฟล์ไม่รองรับ');
            }

            if (!upload_scan_for_viruses($tmp)) {
                throw new RuntimeException('ไฟล์ไม่ผ่านการตรวจสอบความปลอดภัย');
            }

            $extension = $allowed[$mime];
            $hash = hash_file('sha256', $tmp);
            $filename = bin2hex(random_bytes(16)) . '.' . $extension;

            $date_path = date('Y/m');
            $module_path = rtrim($base_dir, '/\\') . '/' . $module . '/' . $date_path;

            if (!is_dir($module_path) && !mkdir($module_path, 0775, true) && !is_dir($module_path)) {
                throw new RuntimeException('ไม่สามารถสร้างโฟลเดอร์อัปโหลดได้');
            }

            $target_path = $module_path . '/' . $filename;

            if (!move_uploaded_file($tmp, $target_path)) {
                throw new RuntimeException('บันทึกไฟล์ไม่สำเร็จ');
            }

            $relative_path = 'storage/uploads/' . $module . '/' . $date_path . '/' . $filename;

            $stmt = db_query(
                'INSERT INTO dh_files (fileName, filePath, mimeType, fileSize, checksumSHA256, storageProvider, uploadedByPID)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                'sssisss',
                (string) $file['name'],
                $relative_path,
                $mime,
                $size,
                $hash,
                'local',
                $uploaderPID
            );
            $file_id = db_last_insert_id();
            mysqli_stmt_close($stmt);

            $ref_stmt = db_query(
                'INSERT INTO dh_file_refs (fileID, moduleName, entityName, entityID, attachedByPID)
                 VALUES (?, ?, ?, ?, ?)',
                'issss',
                $file_id,
                $module,
                $entityName,
                $entityId,
                $uploaderPID
            );
            mysqli_stmt_close($ref_stmt);

            $stored[] = [
                'fileID' => $file_id,
                'fileName' => (string) $file['name'],
                'filePath' => $relative_path,
                'mimeType' => $mime,
                'fileSize' => $size,
            ];
        }

        // finfo objects are freed automatically (PHP 8.5+), no explicit close needed.

        return $stored;
    }
}

<?php

declare(strict_types=1);

require_once __DIR__ . '/../views/view.php';
require_once __DIR__ . '/../modules/orders/repository.php';
require_once __DIR__ . '/../modules/audit/logger.php';

if (!function_exists('orders_sharing_extract_token')) {
    function orders_sharing_extract_token(): string
    {
        foreach (['token', 'code', 'share'] as $key) {
            $value = trim((string) ($_GET[$key] ?? ''));

            if ($value !== '') {
                return $value;
            }
        }

        $query = (string) ($_SERVER['QUERY_STRING'] ?? '');
        $query = trim($query);

        if ($query !== '' && strpos($query, '=') === false) {
            return trim(rawurldecode($query));
        }

        if (!empty($_GET)) {
            $first_key = (string) array_key_first($_GET);
            $first_value = (string) ($_GET[$first_key] ?? '');

            if ($first_value === '') {
                return trim(rawurldecode($first_key));
            }
        }

        return '';
    }
}

if (!function_exists('orders_sharing_is_valid_token')) {
    function orders_sharing_is_valid_token(string $token): bool
    {
        return preg_match('/^[a-fA-F0-9]{32,128}$/', $token) === 1;
    }
}

if (!function_exists('orders_sharing_abort')) {
    function orders_sharing_abort(int $status): void
    {
        http_response_code($status);
        view_render('orders/sharing', [
            'item' => null,
            'attachments' => [],
            'share_token' => '',
        ]);
        exit();
    }
}

if (!function_exists('orders_sharing_safe_filename')) {
    function orders_sharing_safe_filename(string $fileName, string $fallback = 'attachment'): string
    {
        $fileName = trim(str_replace(["\r", "\n", '/', '\\'], '', $fileName));

        return $fileName !== '' ? $fileName : $fallback;
    }
}

if (!function_exists('orders_sharing_resolve_file_path')) {
    function orders_sharing_resolve_file_path(array $file): ?string
    {
        $file_path = trim((string) ($file['filePath'] ?? ''));

        if ($file_path === '') {
            return null;
        }

        $base_storage = realpath(__DIR__ . '/../../storage/uploads');
        $base_assets = realpath(__DIR__ . '/../../assets/uploads');
        $target_path = realpath(__DIR__ . '/../../' . $file_path);

        if (!$target_path || !is_file($target_path)) {
            return null;
        }

        $valid_base = static function (?string $base) use ($target_path): bool {
            if (!$base) {
                return false;
            }

            return $target_path === $base || strpos($target_path, $base . DIRECTORY_SEPARATOR) === 0;
        };

        if (!$valid_base($base_storage) && !$valid_base($base_assets)) {
            return null;
        }

        return $target_path;
    }
}

if (!function_exists('orders_sharing_send_all_files')) {
    function orders_sharing_send_all_files(array $item, string $share_token): void
    {
        $order_id = (int) ($item['orderID'] ?? 0);
        $attachments = $order_id > 0 ? order_get_attachments($order_id) : [];

        if ($attachments === []) {
            audit_log('orders', 'SHARE_ATTACHMENT_DOWNLOAD_ALL', 'FAIL', 'dh_orders', $order_id, 'no_attachment', [
                'shareToken' => $share_token,
            ], 'GET', 404);
            http_response_code(404);
            exit();
        }

        if (!class_exists('ZipArchive')) {
            audit_log('orders', 'SHARE_ATTACHMENT_DOWNLOAD_ALL', 'FAIL', 'dh_orders', $order_id, 'zip_extension_missing', [
                'shareToken' => $share_token,
            ], 'GET', 500);
            http_response_code(500);
            exit();
        }

        $zip_path = tempnam(sys_get_temp_dir(), 'order-share-');

        if ($zip_path === false) {
            audit_log('orders', 'SHARE_ATTACHMENT_DOWNLOAD_ALL', 'FAIL', 'dh_orders', $order_id, 'temp_file_failed', [
                'shareToken' => $share_token,
            ], 'GET', 500);
            http_response_code(500);
            exit();
        }

        $zip = new ZipArchive();

        if ($zip->open($zip_path, ZipArchive::OVERWRITE) !== true) {
            @unlink($zip_path);
            audit_log('orders', 'SHARE_ATTACHMENT_DOWNLOAD_ALL', 'FAIL', 'dh_orders', $order_id, 'zip_open_failed', [
                'shareToken' => $share_token,
            ], 'GET', 500);
            http_response_code(500);
            exit();
        }

        $added = 0;
        $used_names = [];

        foreach ($attachments as $file) {
            $target_path = orders_sharing_resolve_file_path((array) $file);

            if ($target_path === null) {
                continue;
            }

            $file_id = (int) ($file['fileID'] ?? 0);
            $base_name = orders_sharing_safe_filename((string) ($file['fileName'] ?? ''), 'attachment-' . $file_id);
            $zip_name = $base_name;
            $suffix = 2;

            while (isset($used_names[$zip_name])) {
                $extension = pathinfo($base_name, PATHINFO_EXTENSION);
                $name_only = $extension !== '' ? substr($base_name, 0, -1 * (strlen($extension) + 1)) : $base_name;
                $zip_name = $name_only . '-' . $suffix . ($extension !== '' ? '.' . $extension : '');
                $suffix++;
            }

            $used_names[$zip_name] = true;

            if ($zip->addFile($target_path, $zip_name)) {
                $added++;
            }
        }

        $zip->close();

        if ($added === 0) {
            @unlink($zip_path);
            audit_log('orders', 'SHARE_ATTACHMENT_DOWNLOAD_ALL', 'FAIL', 'dh_orders', $order_id, 'no_file_on_disk', [
                'shareToken' => $share_token,
            ], 'GET', 404);
            http_response_code(404);
            exit();
        }

        $document_number = orders_sharing_safe_filename((string) ($item['orderNo'] ?? ''), 'order-' . $order_id);
        $archive_name = 'orders-' . $document_number . '.zip';

        audit_log('orders', 'SHARE_ATTACHMENT_DOWNLOAD_ALL', 'SUCCESS', 'dh_orders', $order_id, null, [
            'shareToken' => $share_token,
            'fileCount' => $added,
        ], 'GET', 200);

        header('Content-Type: application/zip');
        header('Content-Length: ' . (string) filesize($zip_path));
        header('X-Content-Type-Options: nosniff');
        header('Content-Disposition: attachment; filename="' . $archive_name . '"');

        readfile($zip_path);
        @unlink($zip_path);
        exit();
    }
}

if (!function_exists('orders_sharing_index')) {
    function orders_sharing_index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            orders_sharing_abort(405);
        }

        $share_token = orders_sharing_extract_token();

        if (!orders_sharing_is_valid_token($share_token)) {
            orders_sharing_abort(404);
        }

        $item = order_get_by_share_token($share_token);

        if (!$item) {
            orders_sharing_abort(404);
        }

        $order_id = (int) ($item['orderID'] ?? 0);
        $attachments = $order_id > 0 ? order_get_attachments($order_id) : [];

        audit_log('orders', 'SHARE_LINK_VIEW', 'SUCCESS', 'dh_orders', $order_id, null, [
            'shareToken' => $share_token,
        ], 'GET', 200);

        view_render('orders/sharing', [
            'item' => $item,
            'attachments' => $attachments,
            'share_token' => $share_token,
        ]);
    }
}

if (!function_exists('orders_sharing_file')) {
    function orders_sharing_file(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            exit();
        }

        $share_token = orders_sharing_extract_token();
        $file_id = filter_input(INPUT_GET, 'file_id', FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        $download_all = isset($_GET['all']) && $_GET['all'] === '1';
        $download = isset($_GET['download']) && $_GET['download'] === '1';

        if (!orders_sharing_is_valid_token($share_token) || (!$download_all && !$file_id)) {
            http_response_code(404);
            exit();
        }

        $item = order_get_by_share_token($share_token);

        if (!$item) {
            http_response_code(404);
            exit();
        }

        if ($download_all) {
            orders_sharing_send_all_files($item, $share_token);
        }

        $order_id = (int) ($item['orderID'] ?? 0);
        $file = $order_id > 0 ? order_get_attachment_for_share($order_id, (int) $file_id) : null;

        if (!$file) {
            audit_log('orders', 'SHARE_ATTACHMENT_VIEW', 'FAIL', 'dh_orders', $order_id, 'file_reference_not_found', [
                'shareToken' => $share_token,
                'fileID' => $file_id,
            ], 'GET', 404);
            http_response_code(404);
            exit();
        }

        $target_path = orders_sharing_resolve_file_path($file);

        if ($target_path === null) {
            audit_log('orders', 'SHARE_ATTACHMENT_VIEW', 'FAIL', 'dh_orders', $order_id, 'file_missing_or_invalid_path', [
                'shareToken' => $share_token,
                'fileID' => $file_id,
            ], 'GET', 404);
            http_response_code(404);
            exit();
        }

        $file_name = orders_sharing_safe_filename((string) ($file['fileName'] ?? ''), 'attachment');
        $mime_type = trim((string) ($file['mimeType'] ?? 'application/octet-stream'));
        $mime_type = $mime_type !== '' ? $mime_type : 'application/octet-stream';
        $action = $download ? 'SHARE_ATTACHMENT_DOWNLOAD' : 'SHARE_ATTACHMENT_VIEW';

        audit_log('orders', $action, 'SUCCESS', 'dh_orders', $order_id, null, [
            'shareToken' => $share_token,
            'fileID' => (int) $file_id,
            'mimeType' => $mime_type,
            'fileSize' => (int) ($file['fileSize'] ?? 0),
        ], 'GET', 200);

        header('Content-Type: ' . $mime_type);
        header('Content-Length: ' . (string) filesize($target_path));
        header('X-Content-Type-Options: nosniff');
        header('Content-Disposition: ' . ($download ? 'attachment' : 'inline') . '; filename="' . $file_name . '"');

        readfile($target_path);
        exit();
    }
}

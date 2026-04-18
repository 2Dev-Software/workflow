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
        $download = isset($_GET['download']) && $_GET['download'] === '1';

        if (!orders_sharing_is_valid_token($share_token) || !$file_id) {
            http_response_code(404);
            exit();
        }

        $item = order_get_by_share_token($share_token);

        if (!$item) {
            http_response_code(404);
            exit();
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

        $file_path = trim((string) ($file['filePath'] ?? ''));

        if ($file_path === '') {
            http_response_code(404);
            exit();
        }

        $base_storage = realpath(__DIR__ . '/../../storage/uploads');
        $base_assets = realpath(__DIR__ . '/../../assets/uploads');
        $target_path = realpath(__DIR__ . '/../../' . $file_path);
        $valid = false;

        if ($target_path && $base_storage && strpos($target_path, $base_storage) === 0) {
            $valid = true;
        }

        if ($target_path && $base_assets && strpos($target_path, $base_assets) === 0) {
            $valid = true;
        }

        if (!$valid || !is_file($target_path)) {
            audit_log('orders', 'SHARE_ATTACHMENT_VIEW', $valid ? 'FAIL' : 'DENY', 'dh_orders', $order_id, $valid ? 'file_missing_on_disk' : 'invalid_file_path', [
                'shareToken' => $share_token,
                'fileID' => $file_id,
            ], 'GET', 404);
            http_response_code(404);
            exit();
        }

        $file_name = str_replace(["\r", "\n"], '', (string) ($file['fileName'] ?? 'attachment'));
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

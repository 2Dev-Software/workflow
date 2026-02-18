<?php

declare(strict_types=1);

require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../security/session.php';
require_once __DIR__ . '/../services/document-service.php';
require_once __DIR__ . '/../views/view.php';

if (!function_exists('inbox_index')) {
    function inbox_index(): void
    {
        app_session_start();
        $pid = (string) ($_SESSION['pID'] ?? '');

        $filters = [
            'status' => (string) ($_GET['status'] ?? ''),
            'q' => (string) ($_GET['q'] ?? ''),
        ];
        $page = (int) ($_GET['page'] ?? 1);
        $page_size = 10;

        $result = document_inbox_list($pid, $filters, $page, $page_size);

        view_render('inbox/index', [
            'items' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'page_size' => $result['page_size'],
            'filters' => $filters,
        ]);
    }
}

<?php

declare(strict_types=1);

require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../security/session.php';
require_once __DIR__ . '/../services/document-service.php';
require_once __DIR__ . '/../views/view.php';

if (!function_exists('dashboard_index')) {
    function dashboard_index(): void
    {
        app_session_start();
        $pid = (string) ($_SESSION['pID'] ?? '');
        $counts = document_inbox_counts($pid);

        view_render('dashboard/index', [
            'counts' => $counts,
            'user_name' => (string) ($_SESSION['user_name'] ?? ''),
        ]);
    }
}

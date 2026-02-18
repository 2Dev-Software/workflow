<?php

declare(strict_types=1);

require_once __DIR__ . '/../views/view.php';
require_once __DIR__ . '/../../src/Services/auth/auth-guard.php';

if (!function_exists('certificates_index')) {
    function certificates_index(): void
    {
        view_render('certificates/index', []);
    }
}

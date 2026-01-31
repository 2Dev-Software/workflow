<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth/csrf.php';

if (!function_exists('csrf_meta_tag')) {
    function csrf_meta_tag(): string
    {
        $token = csrf_token();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
}

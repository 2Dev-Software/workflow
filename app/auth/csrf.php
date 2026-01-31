<?php
declare(strict_types=1);

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        $token = csrf_token();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
}

if (!function_exists('csrf_validate')) {
    function csrf_validate(?string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $session_token = $_SESSION['csrf_token'] ?? '';
        if ($token === null || $token === '' || $session_token === '') {
            return false;
        }

        return hash_equals((string) $session_token, (string) $token);
    }
}

if (!function_exists('csrf_require')) {
    function csrf_require(?string $token, ?callable $on_fail = null): void
    {
        if (csrf_validate($token)) {
            return;
        }

        if ($on_fail !== null) {
            $on_fail();
            exit();
        }

        http_response_code(403);
        echo 'Invalid CSRF token';
        exit();
    }
}

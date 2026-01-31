<?php
declare(strict_types=1);

require_once __DIR__ . '/require_login.php';
require_once __DIR__ . '/../rbac/roles.php';

if (!function_exists('render_forbidden')) {
    function render_forbidden(string $message = 'Access denied'): void
    {
        http_response_code(403);
        $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
        $is_ajax = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
        $is_api = strpos((string) ($_SERVER['REQUEST_URI'] ?? ''), '/public/api/') !== false;

        if ($is_api || $is_ajax || strpos($accept, 'application/json') !== false) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error' => 'forbidden',
                'message' => $message,
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }

        echo '<h3>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</h3>';
        exit();
    }
}

if (!function_exists('require_role')) {
    function require_role(string $role_key): void
    {
        require_login();

        $pID = (string) ($_SESSION['pID'] ?? '');
        if ($pID === '') {
            redirect_to_login();
        }

        $connection = db_connection();
        if (!rbac_user_has_role($connection, $pID, $role_key)) {
            render_forbidden('Unauthorized');
        }
    }
}

if (!function_exists('require_any_role')) {
    function require_any_role(array $role_keys): void
    {
        require_login();

        $pID = (string) ($_SESSION['pID'] ?? '');
        if ($pID === '') {
            redirect_to_login();
        }

        $connection = db_connection();
        if (!rbac_user_has_any_role($connection, $pID, $role_keys)) {
            render_forbidden('Unauthorized');
        }
    }
}

<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../security/session.php';
require_once __DIR__ . '/../rbac/roles.php';

if (!function_exists('middleware_auth')) {
    function middleware_auth(): callable
    {
        return static function (): bool {
            app_session_start();
            if (empty($_SESSION['pID'])) {
                redirect_to('/login');
                return false;
            }
            return true;
        };
    }
}

if (!function_exists('middleware_role')) {
    function middleware_role(array $role_keys): callable
    {
        return static function () use ($role_keys): bool {
            app_session_start();
            $pid = (string) ($_SESSION['pID'] ?? '');
            if ($pid === '') {
                redirect_to('/login');
                return false;
            }

            $connection = db_connection();
            if (!rbac_user_has_any_role($connection, $pid, $role_keys)) {
                http_response_code(403);
                if (request_wants_json()) {
                    json_error('สิทธิ์ไม่เพียงพอ', [], 403);
                }

                require_once __DIR__ . '/../views/errors/403.php';
                return false;
            }

            return true;
        };
    }
}

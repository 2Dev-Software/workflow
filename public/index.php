<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/router.php';
require_once __DIR__ . '/../app/middleware/auth-middleware.php';
require_once __DIR__ . '/../app/controllers/auth-controller.php';
require_once __DIR__ . '/../app/controllers/dashboard-controller.php';
require_once __DIR__ . '/../app/controllers/inbox-controller.php';
require_once __DIR__ . '/../app/controllers/health-controller.php';

app_bootstrap();

$router = new Router();

$router->get('/', function (): void {
    app_session_start();

    if (!empty($_SESSION['pID'])) {
        redirect_to('/dashboard');
    }
    redirect_to('/login');
});

$router->get('/login', 'auth_show_login');
$router->post('/login', 'auth_handle_login');
$router->get('/logout', 'auth_logout');

$router->get('/dashboard', 'dashboard_index', [middleware_auth()]);
$router->get('/inbox', 'inbox_index', [middleware_auth()]);
$router->get('/health', 'health_index', [middleware_auth(), middleware_role(['ADMIN'])]);

$router->setNotFound(function (): void {
    require __DIR__ . '/../app/views/errors/404.php';
});

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);

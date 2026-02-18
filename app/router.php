<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

class Router
{
    private array $routes = [];
    private $notFoundHandler;

    public function get(string $path, callable $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, callable $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    public function add(string $method, string $path, callable $handler, array $middleware = []): void
    {
        $method = strtoupper($method);
        $path = $this->normalizePath($path);
        $this->routes[$method][$path] = [
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    public function setNotFound(callable $handler): void
    {
        $this->notFoundHandler = $handler;
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = (string) (parse_url($uri, PHP_URL_PATH) ?? '/');
        $base = app_base_path();

        if ($base !== '' && strpos($path, $base) === 0) {
            $path = substr($path, strlen($base));
        }
        $path = $this->normalizePath($path === '' ? '/' : $path);

        $method = strtoupper($method);
        $route = $this->routes[$method][$path] ?? null;

        if ($route === null) {
            $this->handleNotFound();

            return;
        }

        foreach ($route['middleware'] as $middleware) {
            $result = $middleware();

            if ($result === false) {
                return;
            }
        }

        call_user_func($route['handler']);
    }

    private function handleNotFound(): void
    {
        if ($this->notFoundHandler !== null) {
            call_user_func($this->notFoundHandler);

            return;
        }

        http_response_code(404);
        echo 'Not Found';
    }

    private function normalizePath(string $path): string
    {
        $path = '/' . trim($path, '/');

        return $path === '//' ? '/' : $path;
    }
}

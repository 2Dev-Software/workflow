<?php

declare(strict_types=1);

require_once __DIR__ . '/../helpers.php';

if (!function_exists('view_render')) {
    function view_render(string $template, array $data = []): void
    {
        $template = trim($template, '/');
        $path = __DIR__ . '/' . $template . '.php';

        if (!file_exists($path)) {
            http_response_code(500);
            echo 'View not found';

            return;
        }

        extract($data, EXTR_SKIP);
        require $path;
    }
}

if (!function_exists('component_attr')) {
    function component_attr(array $attrs = []): string
    {
        $pairs = [];

        foreach ($attrs as $key => $value) {
            if ($value === null || $value === false) {
                continue;
            }

            if ($value === true) {
                $pairs[] = h((string) $key);
                continue;
            }
            $pairs[] = h((string) $key) . '="' . h((string) $value) . '"';
        }

        return $pairs ? ' ' . implode(' ', $pairs) : '';
    }
}

if (!function_exists('component_render')) {
    function component_render(string $name, array $params = [], bool $echo = true): string
    {
        $name = trim($name, '/');
        $path = __DIR__ . '/components/' . $name . '.php';

        if (!file_exists($path)) {
            return '';
        }

        $params = $params ?? [];
        ob_start();
        require $path;
        $output = (string) ob_get_clean();

        if ($echo) {
            echo $output;
        }

        return $output;
    }
}

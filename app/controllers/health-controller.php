<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../security/session.php';
require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/../views/view.php';

if (!function_exists('health_index')) {
    function health_index(): void
    {
        $checks = [];

        $checks['db_connection'] = false;
        try {
            $connection = db_connection();
            $checks['db_connection'] = $connection instanceof mysqli;
        } catch (Throwable $e) {
            $checks['db_connection'] = false;
        }

        $checks['migrations_table'] = db_table_exists(db_connection(), 'dh_migrations');
        $version = null;
        if ($checks['migrations_table']) {
            $row = db_fetch_one('SELECT MAX(version) AS version FROM dh_migrations');
            $version = $row ? (int) ($row['version'] ?? 0) : null;
        }

        $session_path = session_save_path() ?: sys_get_temp_dir();
        $checks['session_path'] = [
            'path' => $session_path,
            'writable' => is_writable($session_path),
        ];

        $upload_root = rtrim((string) app_env('UPLOAD_ROOT', __DIR__ . '/../../storage/uploads'), '/');
        $checks['upload_root'] = [
            'path' => $upload_root,
            'writable' => is_writable($upload_root),
        ];

        $checks['max_upload_size'] = ini_get('upload_max_filesize');
        $checks['max_post_size'] = ini_get('post_max_size');
        $checks['timezone'] = date_default_timezone_get();

        $required_extensions = ['mysqli', 'openssl', 'mbstring', 'json', 'fileinfo'];
        $extensions = [];
        foreach ($required_extensions as $ext) {
            $extensions[$ext] = extension_loaded($ext);
        }
        $checks['extensions'] = $extensions;

        view_render('health/index', [
            'checks' => $checks,
            'migration_version' => $version,
        ]);
    }
}

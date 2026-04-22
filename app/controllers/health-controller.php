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
        $checks['db_driver'] = 'mysqli';

        $required_extensions = ['mysqli', 'openssl', 'mbstring', 'json', 'fileinfo', 'gd', 'dom', 'zip', 'pdo_mysql'];
        $extensions = [];

        foreach ($required_extensions as $ext) {
            $extensions[$ext] = extension_loaded($ext);
        }
        $checks['extensions'] = $extensions;

        $vendor_autoload = __DIR__ . '/../../vendor/autoload.php';
        $checks['vendor_autoload'] = is_file($vendor_autoload);

        if ($checks['vendor_autoload']) {
            require_once $vendor_autoload;
        }

        $pdf_temp_root = rtrim((string) sys_get_temp_dir(), DIRECTORY_SEPARATOR);
        $pdf_temp_probe = $pdf_temp_root !== ''
            ? $pdf_temp_root . DIRECTORY_SEPARATOR . 'workflow-health-pdf'
            : '';

        $pdf_temp_ready = $pdf_temp_probe !== ''
            && (is_dir($pdf_temp_probe) || @mkdir($pdf_temp_probe, 0777, true))
            && is_writable($pdf_temp_probe);

        $checks['pdf_runtime'] = [
            'mpdf_class' => class_exists(\Mpdf\Mpdf::class),
            'config_variables_class' => class_exists(\Mpdf\Config\ConfigVariables::class),
            'font_variables_class' => class_exists(\Mpdf\Config\FontVariables::class),
            'mb_regex_encoding' => function_exists('mb_regex_encoding'),
            'imagecreatetruecolor' => function_exists('imagecreatetruecolor'),
            'finfo_open' => function_exists('finfo_open'),
            'temp_dir' => [
                'path' => $pdf_temp_probe !== '' ? $pdf_temp_probe : sys_get_temp_dir(),
                'writable' => $pdf_temp_ready,
            ],
        ];

        view_render('health/index', [
            'checks' => $checks,
            'migration_version' => $version,
        ]);
    }
}

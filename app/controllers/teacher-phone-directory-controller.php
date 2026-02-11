<?php
declare(strict_types=1);

require_once __DIR__ . '/../views/view.php';
require_once __DIR__ . '/../../src/Services/auth/auth-guard.php';
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../db/connection.php';

if (!function_exists('teacher_phone_directory_index')) {
    function teacher_phone_directory_index(): void
    {
        $connection = db_connection();
        $teacher_directory_page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, [
            'options' => ['default' => 1, 'min_range' => 1],
        ]);
        $teacher_directory_per_page_param = $_GET['per_page'] ?? '10';
        if ($teacher_directory_per_page_param === 'all') {
            $teacher_directory_per_page = 'all';
        } else {
            $teacher_directory_per_page = filter_var($teacher_directory_per_page_param, FILTER_VALIDATE_INT, [
                'options' => ['default' => 10, 'min_range' => 1],
            ]);
        }
        $teacher_directory_allowed_per_page = [10, 20, 50, 'all'];
        if (!in_array($teacher_directory_per_page, $teacher_directory_allowed_per_page, true)) {
            $teacher_directory_per_page = 10;
        }

        $teacher_directory_query = trim((string) ($_GET['q'] ?? ''));
        $teacher_directory_display_per_page = $teacher_directory_per_page === 'all'
            ? 'ทั้งหมด'
            : (string) $teacher_directory_per_page;

        require_once __DIR__ . '/../../src/Services/teacher/teacher-directory.php';

        view_render('teacher-phone-directory/index', [
            'teacher_directory' => $teacher_directory ?? [],
            'teacher_directory_total' => $teacher_directory_total ?? 0,
            'teacher_directory_total_pages' => $teacher_directory_total_pages ?? 0,
            'teacher_directory_page' => $teacher_directory_page,
            'teacher_directory_per_page' => $teacher_directory_per_page,
            'teacher_directory_display_per_page' => $teacher_directory_display_per_page,
            'teacher_directory_query' => $teacher_directory_query,
        ]);
    }
}

<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['pID'])) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'method_not_allowed']);
    exit();
}

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

require_once __DIR__ . '/../../src/Services/teacher/teacher-directory.php';

$teacher_directory_payload = array_map(
    static function (array $row): array {
        return [
            'fName' => (string) ($row['fName'] ?? ''),
            'department_name' => (string) ($row['department_name'] ?? ''),
            'telephone' => (string) ($row['telephone'] ?? ''),
        ];
    },
    $teacher_directory
);

echo json_encode(
    [
        'data' => $teacher_directory_payload,
        'meta' => [
            'page' => $teacher_directory_page,
            'per_page' => $teacher_directory_per_page,
            'total' => $teacher_directory_total,
            'total_pages' => $teacher_directory_total_pages,
            'query' => $teacher_directory_query,
        ],
    ],
    JSON_UNESCAPED_UNICODE
);

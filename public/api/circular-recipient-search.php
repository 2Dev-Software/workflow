<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/rbac/current_user.php';
require_once __DIR__ . '/../../app/modules/users/lists.php';

if (!function_exists('circular_recipient_search_normalize')) {
    function circular_recipient_search_normalize(string $value): string
    {
        $value = mb_strtolower(trim($value), 'UTF-8');
        $value = preg_replace('/\s+/u', '', $value) ?? '';
        $value = preg_replace('/[^0-9a-zก-๙]/u', '', $value) ?? '';
        return $value;
    }
}

header('Content-Type: application/json; charset=UTF-8');

if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'ok' => false,
        'message' => 'Method Not Allowed',
        'pids' => [],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$current_user = current_user();
$current_pid = trim((string) ($current_user['pID'] ?? ''));
if ($current_pid === '') {
    http_response_code(401);
    echo json_encode([
        'ok' => false,
        'message' => 'Unauthorized',
        'pids' => [],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$query = trim((string) ($_GET['q'] ?? ''));
$normalized_query = circular_recipient_search_normalize($query);

$teachers = array_values(array_filter(user_list_teachers(), static function (array $teacher) use ($current_pid): bool {
    $pid = trim((string) ($teacher['pID'] ?? ''));
    if ($pid === '' || $pid === $current_pid) {
        return false;
    }
    return ctype_digit($pid);
}));

$matched_pids = [];
if ($normalized_query !== '') {
    foreach ($teachers as $teacher) {
        $pid = trim((string) ($teacher['pID'] ?? ''));
        if ($pid === '') {
            continue;
        }

        $search_text = circular_recipient_search_normalize(
            implode(' ', [
                (string) ($teacher['fName'] ?? ''),
                (string) ($teacher['factionName'] ?? ''),
                (string) ($teacher['departmentName'] ?? ''),
                $pid,
            ])
        );

        if ($search_text !== '' && strpos($search_text, $normalized_query) !== false) {
            $matched_pids[] = $pid;
        }
    }
}

echo json_encode([
    'ok' => true,
    'q' => $query,
    'total' => count($matched_pids),
    'pids' => array_values(array_unique($matched_pids)),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

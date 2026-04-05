<?php

declare(strict_types=1);

require_once __DIR__ . '/../views/view.php';
require_once __DIR__ . '/../../src/Services/auth/auth-guard.php';
require_once __DIR__ . '/../auth/csrf.php';
require_once __DIR__ . '/../rbac/current_user.php';
require_once __DIR__ . '/../modules/memos/service.php';
require_once __DIR__ . '/../modules/memos/repository.php';
require_once __DIR__ . '/../modules/system/system.php';
require_once __DIR__ . '/../db/db.php';

if (!function_exists('memo_inbox_list_sender_factions')) {
    function memo_inbox_list_sender_factions(mysqli $connection): array
    {
        if (!db_table_exists($connection, 'faction')) {
            return [];
        }

        $rows = db_fetch_all(
            'SELECT fID, fname
             FROM faction
             WHERE fID <> 1
               AND fname NOT LIKE ?
             ORDER BY fID ASC',
            's',
            '%ฝ่ายบริหาร%'
        );

        $items = [];

        foreach ($rows as $row) {
            $fid = (int) ($row['fID'] ?? 0);
            $name = trim((string) ($row['fname'] ?? ''));

            if ($fid <= 0 || $name === '') {
                continue;
            }

            $items[] = [
                'fID' => $fid,
                'fname' => $name,
            ];
        }

        return $items;
    }
}

if (!function_exists('memo_inbox_resolve_chain_reviewer_pids')) {
    function memo_inbox_resolve_chain_reviewer_pids(array $item): array
    {
        $created_by_pid = trim((string) ($item['createdByPID'] ?? ''));
        $flow_stage = strtoupper(trim((string) ($item['flowStage'] ?? '')));
        $to_pid = trim((string) ($item['toPID'] ?? ''));

        $chain = [
            'HEAD' => trim((string) ($item['headPID'] ?? '')),
            'DEPUTY' => trim((string) ($item['deputyPID'] ?? '')),
            'DIRECTOR' => trim((string) ($item['directorPID'] ?? '')),
        ];

        if ($created_by_pid !== '') {
            try {
                $resolved = memo_resolve_chain_approvers($created_by_pid);

                if ($chain['HEAD'] === '') {
                    $chain['HEAD'] = trim((string) ($resolved['headPID'] ?? ''));
                }

                if ($chain['DEPUTY'] === '') {
                    $chain['DEPUTY'] = trim((string) ($resolved['deputyPID'] ?? ''));
                }

                if ($chain['DIRECTOR'] === '') {
                    $chain['DIRECTOR'] = trim((string) ($resolved['directorPID'] ?? ''));
                }
            } catch (Throwable $ignored) {
            }
        }

        if (in_array($flow_stage, ['HEAD', 'DEPUTY', 'DIRECTOR'], true) && $to_pid !== '') {
            $chain[$flow_stage] = $to_pid;
        }

        if ($chain['DIRECTOR'] === '') {
            $chain['DIRECTOR'] = trim((string) (system_get_current_director_pid() ?? ''));
        }

        return $chain;
    }
}

if (!function_exists('memo_inbox_resolve_current_reviewer_role')) {
    function memo_inbox_resolve_current_reviewer_role(array $item, string $current_pid, array $chain): string
    {
        $current_pid = trim($current_pid);

        if ($current_pid === '') {
            return '';
        }

        foreach (['HEAD', 'DEPUTY', 'DIRECTOR'] as $stage) {
            if ($current_pid === trim((string) ($chain[$stage] ?? ''))) {
                return $stage;
            }
        }

        $flow_stage = strtoupper(trim((string) ($item['flowStage'] ?? '')));
        $to_pid = trim((string) ($item['toPID'] ?? ''));

        if ($current_pid === $to_pid && in_array($flow_stage, ['HEAD', 'DEPUTY', 'DIRECTOR'], true)) {
            return $flow_stage;
        }

        return '';
    }
}

if (!function_exists('memo_inbox_fetch_teacher_profiles')) {
    function memo_inbox_fetch_teacher_profiles(mysqli $connection, array $pids): array
    {
        $pids = array_values(array_unique(array_filter(array_map(static function ($value): string {
            return trim((string) $value);
        }, $pids), static function (string $value): bool {
            return $value !== '';
        })));

        if ($pids === []) {
            return [];
        }

        $position = system_position_join($connection, 't', 'p');
        $placeholders = implode(', ', array_fill(0, count($pids), '?'));
        $types = str_repeat('s', count($pids));
        $rows = db_fetch_all(
            'SELECT t.pID,
                    COALESCE(t.fName, "") AS name,
                    COALESCE(t.signature, "") AS signature,
                    COALESCE(' . $position['name'] . ', "") AS positionName
             FROM teacher AS t
             ' . $position['join'] . '
             WHERE t.pID IN (' . $placeholders . ')',
            $types,
            ...$pids
        );

        $profiles = [];

        foreach ($rows as $row) {
            $pid = trim((string) ($row['pID'] ?? ''));

            if ($pid === '') {
                continue;
            }

            $profiles[$pid] = [
                'name' => trim((string) ($row['name'] ?? '')),
                'signature' => trim((string) ($row['signature'] ?? '')),
                'positionName' => trim((string) ($row['positionName'] ?? '')),
            ];
        }

        return $profiles;
    }
}

if (!function_exists('memo_inbox_latest_note_by_actor')) {
    function memo_inbox_latest_note_by_actor(array $routes, string $actor_pid): string
    {
        $actor_pid = trim($actor_pid);

        if ($actor_pid === '') {
            return '';
        }

        $latest_note = '';

        foreach ($routes as $route) {
            if (trim((string) ($route['actorPID'] ?? '')) !== $actor_pid) {
                continue;
            }

            $note = trim((string) ($route['note'] ?? ''));

            if ($note !== '') {
                $latest_note = $note;
            }
        }

        return $latest_note;
    }
}

if (!function_exists('memo_inbox_enrich_items')) {
    function memo_inbox_enrich_items(mysqli $connection, array $items, string $current_pid): array
    {
        if ($items === []) {
            return [];
        }

        $chain_map = [];
        $profile_pids = [];

        foreach ($items as $index => $item) {
            $memo_id = (int) ($item['memoID'] ?? 0);
            $chain = memo_inbox_resolve_chain_reviewer_pids($item);
            $chain_map[$memo_id] = $chain;

            $items[$index]['reviewerRole'] = memo_inbox_resolve_current_reviewer_role($item, $current_pid, $chain);
            $profile_pids[] = trim((string) ($item['createdByPID'] ?? ''));
            $profile_pids[] = $chain['HEAD'] ?? '';
            $profile_pids[] = $chain['DEPUTY'] ?? '';
            $profile_pids[] = $chain['DIRECTOR'] ?? '';
        }

        $teacher_profiles = memo_inbox_fetch_teacher_profiles($connection, $profile_pids);

        foreach ($items as $index => $item) {
            $memo_id = (int) ($item['memoID'] ?? 0);
            $chain = $chain_map[$memo_id] ?? ['HEAD' => '', 'DEPUTY' => '', 'DIRECTOR' => ''];
            $routes = $memo_id > 0 ? memo_list_routes($memo_id) : [];
            $creator_pid = trim((string) ($item['createdByPID'] ?? ''));
            $creator_profile = $teacher_profiles[$creator_pid] ?? [];

            $items[$index]['creatorSignature'] = trim((string) ($creator_profile['signature'] ?? ($item['creatorSignature'] ?? '')));
            $items[$index]['creatorName'] = trim((string) ($creator_profile['name'] ?? ($item['creatorName'] ?? '')));
            $items[$index]['creatorPositionName'] = trim((string) ($creator_profile['positionName'] ?? ($item['creatorPositionName'] ?? '')));
            $items[$index]['headResolvedPID'] = $chain['HEAD'] ?? '';
            $items[$index]['deputyResolvedPID'] = $chain['DEPUTY'] ?? '';
            $items[$index]['directorResolvedPID'] = $chain['DIRECTOR'] ?? '';

            foreach ([
                'head' => 'HEAD',
                'deputy' => 'DEPUTY',
                'director' => 'DIRECTOR',
            ] as $prefix => $stage) {
                $stage_pid = trim((string) ($chain[$stage] ?? ''));
                $profile = $teacher_profiles[$stage_pid] ?? [];

                $items[$index][$prefix . 'Name'] = trim((string) ($profile['name'] ?? ''));
                $items[$index][$prefix . 'Signature'] = trim((string) ($profile['signature'] ?? ''));
                $items[$index][$prefix . 'PositionName'] = trim((string) ($profile['positionName'] ?? ''));
                $items[$index][$prefix . 'Note'] = memo_inbox_latest_note_by_actor($routes, $stage_pid);
            }
        }

        return $items;
    }
}

if (!function_exists('memo_inbox_index')) {
    function memo_inbox_index(): void
    {
        $current_user = current_user() ?? [];
        $current_pid = (string) ($current_user['pID'] ?? '');

        $search = trim((string) ($_GET['q'] ?? ''));
        $status_filter = (string) ($_GET['status'] ?? 'all');
        $allowed = [
            'all',
            MEMO_STATUS_SUBMITTED,
            MEMO_STATUS_IN_REVIEW,
            MEMO_STATUS_RETURNED,
            MEMO_STATUS_APPROVED_UNSIGNED,
            MEMO_STATUS_SIGNED,
            MEMO_STATUS_REJECTED,
            MEMO_STATUS_CANCELLED,
        ];

        if (!in_array($status_filter, $allowed, true)) {
            $status_filter = 'all';
        }

        $page = (int) ($_GET['page'] ?? 1);
        $page = $page > 0 ? $page : 1;
        $per_page = 10;

        $alert = null;
        $connection = db_connection();
        $has_table = db_table_exists($connection, 'dh_memos');
        $has_routes = db_table_exists($connection, 'dh_memo_routes');
        $current_thai_year = (int) date('Y') + 543;
        $active_dh_year = system_get_dh_year();
        $dh_year_options = [];
        $factions = memo_inbox_list_sender_factions($connection);
        $deputy_candidates = memo_list_deputy_candidates($current_pid);

        if ($active_dh_year < 2568 || $active_dh_year > ($current_thai_year + 1)) {
            $active_dh_year = $current_thai_year;
        }
        $selected_dh_year = (int) ($_GET['dh_year'] ?? 0);

        $total_pages = 1;
        $filtered_total = 0;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $post_action = trim((string) ($_POST['action'] ?? ''));

            if (!csrf_validate($_POST['csrf_token'] ?? null)) {
                $alert = [
                    'type' => 'danger',
                    'title' => 'ไม่สามารถยืนยันความปลอดภัย',
                    'message' => 'กรุณาลองใหม่อีกครั้ง',
                ];
            } elseif (in_array($post_action, ['forward', 'return', 'director_approve', 'director_reject'], true)) {
                try {
                    $memo_id = (int) ($_POST['memo_id'] ?? 0);
                    $note = trim((string) ($_POST['note'] ?? ''));
                    $target_pid = trim((string) ($_POST['target_pid'] ?? ''));

                    if ($memo_id <= 0) {
                        throw new RuntimeException('ไม่พบบันทึกข้อความ');
                    }

                    if ($post_action === 'forward') {
                        memo_forward($memo_id, $current_pid, $note, $target_pid);
                        $alert = [
                            'type' => 'success',
                            'title' => 'ส่งต่อรายการเรียบร้อย',
                            'message' => '',
                        ];
                    } elseif ($post_action === 'return') {
                        memo_return($memo_id, $current_pid, $note);
                        $alert = [
                            'type' => 'success',
                            'title' => 'ตีกลับแก้ไขแล้ว',
                            'message' => '',
                        ];
                    } elseif ($post_action === 'director_approve') {
                        memo_director_approve($memo_id, $current_pid, $note);
                        $alert = [
                            'type' => 'success',
                            'title' => 'ผู้อำนวยการอนุมัติเรียบร้อย',
                            'message' => '',
                        ];
                    } else {
                        memo_director_reject($memo_id, $current_pid, $note);
                        $alert = [
                            'type' => 'success',
                            'title' => 'ผู้อำนวยการไม่อนุมัติรายการแล้ว',
                            'message' => '',
                        ];
                    }
                } catch (Throwable $e) {
                    $alert = [
                        'type' => 'danger',
                        'title' => 'เกิดข้อผิดพลาด',
                        'message' => $e->getMessage(),
                    ];
                }
            }
        }

        if (!$has_table || !$has_routes) {
            $alert = system_not_ready_alert('ยังไม่พบตาราง memo workflow กรุณารัน migrations/011_update_memos_workflow.sql');
            $items = [];
        } else {
            $dh_year_options = memo_list_reviewer_years($current_pid);

            if (!in_array($active_dh_year, $dh_year_options, true)) {
                array_unshift($dh_year_options, $active_dh_year);
            }

            $dh_year_options = array_values(array_unique(array_filter($dh_year_options, static function (int $year): bool {
                return $year >= 2568;
            })));
            rsort($dh_year_options);

            if (!in_array($selected_dh_year, $dh_year_options, true)) {
                $selected_dh_year = (int) ($dh_year_options[0] ?? $active_dh_year);
            }

            $filtered_total = memo_count_by_reviewer($current_pid, $status_filter, $search, $selected_dh_year);
            $total_pages = max(1, (int) ceil($filtered_total / $per_page));

            if ($page > $total_pages) {
                $page = $total_pages;
            }
            $offset = ($page - 1) * $per_page;
            $items = memo_list_by_reviewer_page($current_pid, $status_filter, $search, $per_page, $offset, $selected_dh_year);
            $items = memo_inbox_enrich_items($connection, $items, $current_pid);
        }

        $base_params = [];

        if ($search !== '') {
            $base_params['q'] = $search;
        }

        if ($status_filter !== 'all') {
            $base_params['status'] = $status_filter;
        }

        if ($selected_dh_year > 0) {
            $base_params['dh_year'] = (string) $selected_dh_year;
        }
        $pagination_base_url = 'memo-inbox.php';

        if (!empty($base_params)) {
            $pagination_base_url .= '?' . http_build_query($base_params);
        }

        view_render('memo/inbox', [
            'alert' => $alert,
            'items' => $items,
            'page' => $page,
            'total_pages' => $total_pages,
            'search' => $search,
            'status_filter' => $status_filter,
            'dh_year_options' => $dh_year_options,
            'selected_dh_year' => $selected_dh_year,
            'filtered_total' => $filtered_total,
            'pagination_base_url' => $pagination_base_url,
            'current_user' => $current_user,
            'factions' => $factions,
            'deputy_candidates' => $deputy_candidates,
        ]);
    }
}

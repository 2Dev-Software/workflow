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
require_once __DIR__ . '/../modules/audit/logger.php';

if (!function_exists('memo_build_approver_options')) {
    function memo_build_approver_options(mysqli $connection): array
    {
        $options = [
            'DIRECTOR' => 'ผอ./รักษาการ',
        ];

        $has_positions = db_table_exists($connection, 'dh_positions');
        $sql = $has_positions
            ? 'SELECT t.pID, t.fName, p.positionName
                FROM teacher AS t
                LEFT JOIN dh_positions AS p ON t.positionID = p.positionID
                WHERE t.status = 1
                ORDER BY t.positionID ASC, t.fName ASC'
            : 'SELECT t.pID, t.fName, NULL AS positionName
                FROM teacher AS t
                WHERE t.status = 1
                ORDER BY t.positionID ASC, t.fName ASC';

        $rows = db_fetch_all($sql);
        foreach ($rows as $row) {
            $pid = trim((string) ($row['pID'] ?? ''));
            if ($pid === '') {
                continue;
            }
            $name = trim((string) ($row['fName'] ?? ''));
            $pos = trim((string) ($row['positionName'] ?? ''));
            $label = $name !== '' ? $name : $pid;
            if ($pos !== '') {
                $label .= ' (' . $pos . ')';
            }
            $options['PERSON:' . $pid] = $label;
        }

        return $options;
    }
}

if (!function_exists('memo_list_sender_factions')) {
    function memo_list_sender_factions(mysqli $connection): array
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

if (!function_exists('memo_index')) {
    function memo_index(): void
    {
        $current_user = current_user() ?? [];
        $current_pid = (string) ($current_user['pID'] ?? '');

        $search = trim((string) ($_GET['q'] ?? ''));
        $status_filter = (string) ($_GET['status'] ?? 'all');
        $allowed_status = [
            'all',
            MEMO_STATUS_DRAFT,
            MEMO_STATUS_SUBMITTED,
            MEMO_STATUS_IN_REVIEW,
            MEMO_STATUS_RETURNED,
            MEMO_STATUS_APPROVED_UNSIGNED,
            MEMO_STATUS_SIGNED,
            MEMO_STATUS_REJECTED,
            MEMO_STATUS_CANCELLED,
        ];
        if (!in_array($status_filter, $allowed_status, true)) {
            $status_filter = 'all';
        }

        $page = (int) ($_GET['page'] ?? 1);
        $page = $page > 0 ? $page : 1;
        $per_page = 10;

        $alert = null;
        $default_sender_fid = (string) (int) ($current_user['fID'] ?? 0);
        if ($default_sender_fid === '1' || $default_sender_fid === '0') {
            $default_sender_fid = '';
        }
        $values = [
            'writeDate' => (string) date('Y-m-d'),
            'to_choice' => 'DIRECTOR',
            'sender_fid' => $default_sender_fid,
            'subject' => '',
            'detail' => '',
        ];

        $connection = db_connection();
        $has_memo_table = db_table_exists($connection, 'dh_memos');
        $has_route_table = db_table_exists($connection, 'dh_memo_routes');

        $approver_options = memo_build_approver_options($connection);
        $factions = memo_list_sender_factions($connection);
        if ($values['sender_fid'] === '' && !empty($factions)) {
            $values['sender_fid'] = (string) ($factions[0]['fID'] ?? '');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $values['writeDate'] = trim((string) ($_POST['writeDate'] ?? '')) ?: (string) date('Y-m-d');
            $values['to_choice'] = trim((string) ($_POST['to_choice'] ?? 'DIRECTOR')) ?: 'DIRECTOR';
            $values['sender_fid'] = trim((string) ($_POST['sender_fid'] ?? $values['sender_fid']));
            $values['subject'] = trim((string) ($_POST['subject'] ?? ''));
            $values['detail'] = trim((string) ($_POST['detail'] ?? ''));

            if (!csrf_validate($_POST['csrf_token'] ?? null)) {
                $alert = [
                    'type' => 'danger',
                    'title' => 'ไม่สามารถยืนยันความปลอดภัย',
                    'message' => 'กรุณาลองใหม่อีกครั้ง',
                ];
            } elseif (!$has_memo_table || !$has_route_table) {
                $alert = system_not_ready_alert('ยังไม่พบตาราง memo workflow กรุณารัน migrations/011_update_memos_workflow.sql');
            } elseif ($values['subject'] === '') {
                $alert = [
                    'type' => 'danger',
                    'title' => 'กรุณากรอกหัวข้อ',
                    'message' => '',
                ];
            } else {
                $toType = null;
                $toPID = null;
                if ($values['to_choice'] === 'DIRECTOR') {
                    $toType = 'DIRECTOR';
                } elseif (str_starts_with($values['to_choice'], 'PERSON:')) {
                    $pid = trim(substr($values['to_choice'], 7));
                    if ($pid !== '' && preg_match('/^\\d{1,13}$/', $pid)) {
                        $toType = 'PERSON';
                        $toPID = $pid;
                    }
                }

                try {
                    $flow_mode = trim((string) ($_POST['flow_mode'] ?? 'CHAIN'));
                    $flow_mode = strtoupper($flow_mode) === 'DIRECT' ? 'DIRECT' : 'CHAIN';

                    $memoID = memo_create_draft([
                        'dh_year' => system_get_dh_year(),
                        'writeDate' => $values['writeDate'] !== '' ? $values['writeDate'] : null,
                        'subject' => $values['subject'],
                        'detail' => $values['detail'],
                        'toType' => $toType,
                        'toPID' => $toPID,
                        'flowMode' => $flow_mode,
                        'createdByPID' => $current_pid,
                    ], $_FILES['attachments'] ?? []);

                    $alert = [
                        'type' => 'success',
                        'title' => 'สร้างบันทึกข้อความแล้ว',
                        'message' => 'เลขที่รายการ #' . $memoID,
                    ];
                    $values = [
                        'writeDate' => (string) date('Y-m-d'),
                        'to_choice' => 'DIRECTOR',
                        'sender_fid' => $values['sender_fid'],
                        'subject' => '',
                        'detail' => '',
                    ];
                } catch (Throwable $e) {
                    $alert = [
                        'type' => 'danger',
                        'title' => 'เกิดข้อผิดพลาด',
                        'message' => $e->getMessage(),
                    ];
                }
            }
        }

        if ((!$has_memo_table || !$has_route_table) && $alert === null) {
            $alert = system_not_ready_alert('ยังไม่พบตาราง memo workflow กรุณารัน migrations/011_update_memos_workflow.sql');
        }

        $total_pages = 1;
        $filtered_total = 0;
        if (!$has_memo_table || !$has_route_table) {
            $memos = [];
        } else {
            $filtered_total = memo_count_by_creator($current_pid, false, $status_filter, $search);
            $total_pages = max(1, (int) ceil($filtered_total / $per_page));
            if ($page > $total_pages) {
                $page = $total_pages;
            }
            $offset = ($page - 1) * $per_page;
            $memos = memo_list_by_creator_page($current_pid, false, $status_filter, $search, $per_page, $offset);
        }

        $base_params = [];
        if ($search !== '') {
            $base_params['q'] = $search;
        }
        if ($status_filter !== 'all') {
            $base_params['status'] = $status_filter;
        }
        $pagination_base_url = 'memo.php';
        if (!empty($base_params)) {
            $pagination_base_url .= '?' . http_build_query($base_params);
        }

        view_render('memo/index', [
            'alert' => $alert,
            'values' => $values,
            'memos' => $memos,
            'approver_options' => $approver_options,
            'factions' => $factions,
            'current_user' => $current_user,
            'dh_year' => system_get_dh_year(),
            'page' => $page,
            'total_pages' => $total_pages,
            'search' => $search,
            'status_filter' => $status_filter,
            'filtered_total' => $filtered_total,
            'pagination_base_url' => $pagination_base_url,
        ]);
    }
}

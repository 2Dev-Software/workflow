<?php

declare(strict_types=1);

require_once __DIR__ . '/../views/view.php';
require_once __DIR__ . '/../../src/Services/auth/auth-guard.php';
require_once __DIR__ . '/../auth/csrf.php';
require_once __DIR__ . '/../rbac/current_user.php';
require_once __DIR__ . '/../rbac/roles.php';
require_once __DIR__ . '/../modules/outgoing/service.php';
require_once __DIR__ . '/../modules/outgoing/repository.php';
require_once __DIR__ . '/../modules/users/lists.php';
require_once __DIR__ . '/../db/db.php';

if (!function_exists('outgoing_issue_valid_date')) {
    function outgoing_issue_valid_date(string $value): bool
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1;
    }
}

if (!function_exists('outgoing_normalize_person_ids')) {
    function outgoing_normalize_person_ids(array $values): array
    {
        $normalized = [];
        $seen = [];

        foreach ($values as $value) {
            $pid = trim((string) $value);

            if ($pid === '' || isset($seen[$pid])) {
                continue;
            }

            $seen[$pid] = true;
            $normalized[] = $pid;
        }

        return $normalized;
    }
}

if (!function_exists('outgoing_resolve_owner_names')) {
    function outgoing_resolve_owner_names(array $person_ids): array
    {
        $person_ids = outgoing_normalize_person_ids($person_ids);

        if ($person_ids === []) {
            return [];
        }

        $teachers = user_list_teachers();
        $teacher_names = [];

        foreach ($teachers as $teacher) {
            $pid = trim((string) ($teacher['pID'] ?? ''));

            if ($pid === '') {
                continue;
            }

            $teacher_names[$pid] = trim((string) ($teacher['fName'] ?? '')) ?: $pid;
        }

        $owner_names = [];

        foreach ($person_ids as $pid) {
            $owner_names[] = $teacher_names[$pid] ?? $pid;
        }

        return $owner_names;
    }
}

if (!function_exists('outgoing_build_detail')) {
    function outgoing_build_detail(string $effective_date, string $issuer_name, array $owner_names): string
    {
        $lines = [
            'ลงวันที่: ' . $effective_date,
            'ผู้ออกเลข: ' . ($issuer_name !== '' ? $issuer_name : '-'),
            'เจ้าของเรื่อง: ' . (!empty($owner_names) ? implode(', ', $owner_names) : '-'),
        ];

        return implode("\n", $lines);
    }
}

if (!function_exists('outgoing_index')) {
    function outgoing_index(): void
    {
        $current_user = current_user() ?? [];
        $current_pid = (string) ($current_user['pID'] ?? '');
        $issuer_name = trim((string) ($current_user['fName'] ?? ''));
        if ($issuer_name === '') {
            $issuer_name = $current_pid;
        }

        $connection = db_connection();
        $can_manage = outgoing_user_can_manage($connection, $current_pid, $current_user);
        $search = trim((string) ($_GET['q'] ?? ''));
        $filter_status = strtolower(trim((string) ($_GET['status'] ?? 'all')));
        $filter_sort = strtolower(trim((string) ($_GET['sort'] ?? 'newest')));
        $active_tab = trim((string) ($_GET['tab'] ?? 'compose'));
        $is_track_active = $active_tab === 'track';
        $has_track_filters = array_key_exists('q', $_GET) || array_key_exists('status', $_GET) || array_key_exists('sort', $_GET);

        if ($has_track_filters) {
            $is_track_active = true;
        }

        if (!in_array($filter_status, ['all', 'waiting_attachment', 'complete'], true)) {
            $filter_status = 'all';
        }

        if (!in_array($filter_sort, ['newest', 'oldest'], true)) {
            $filter_sort = 'newest';
        }

        if (!$can_manage) {
            http_response_code(403);
            require __DIR__ . '/../views/errors/403.php';

            return;
        }

        $alert = null;
        $form_values = [
            'subject' => '',
            'effective_date' => date('Y-m-d'),
            'person_ids' => [],
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = trim((string) ($_POST['action'] ?? ''));
            $outgoing_id = isset($_POST['outgoing_id']) ? (int) $_POST['outgoing_id'] : 0;
            $is_create_request = $action === 'create'
                || ($action === '' && $outgoing_id <= 0 && (
                    array_key_exists('subject', $_POST)
                    || array_key_exists('effective_date', $_POST)
                    || array_key_exists('person_ids', $_POST)
                ));

            if ($is_create_request) {
                $form_values['subject'] = trim((string) ($_POST['subject'] ?? ''));
                $form_values['effective_date'] = trim((string) ($_POST['effective_date'] ?? ''));
                $form_values['person_ids'] = outgoing_normalize_person_ids((array) ($_POST['person_ids'] ?? []));
            }

            if (!csrf_validate($_POST['csrf_token'] ?? null)) {
                $alert = [
                    'type' => 'danger',
                    'title' => 'ไม่สามารถยืนยันความปลอดภัย',
                    'message' => 'กรุณาลองใหม่อีกครั้ง',
                ];
            } else {
                if ($is_create_request) {
                    if ($form_values['subject'] === '') {
                        $alert = [
                            'type' => 'danger',
                            'title' => 'กรุณากรอกเรื่อง',
                            'message' => '',
                        ];
                    } elseif (!outgoing_issue_valid_date($form_values['effective_date'])) {
                        $alert = [
                            'type' => 'danger',
                            'title' => 'วันที่ไม่ถูกต้อง',
                            'message' => 'กรุณาเลือกวันที่ให้ถูกต้อง',
                        ];
                    } elseif ($form_values['person_ids'] === []) {
                        $alert = [
                            'type' => 'danger',
                            'title' => 'กรุณาเลือกเจ้าของเรื่อง',
                            'message' => 'อย่างน้อย 1 รายการ',
                        ];
                    } else {
                        try {
                            $owner_names = outgoing_resolve_owner_names($form_values['person_ids']);
                            $outgoing_id = outgoing_create_draft([
                                'dh_year' => system_get_dh_year(),
                                'subject' => $form_values['subject'],
                                'detail' => outgoing_build_detail($form_values['effective_date'], $issuer_name, $owner_names),
                                'status' => OUTGOING_STATUS_WAITING_ATTACHMENT,
                                'createdByPID' => $current_pid,
                            ]);

                            $created_outgoing = outgoing_get($outgoing_id);
                            $created_number = outgoing_document_number($created_outgoing ?? []);

                            $alert = [
                                'type' => 'success',
                                'title' => 'บันทึกออกเลขเรียบร้อย',
                                'message' => $created_number !== '' ? 'เลขทะเบียน ' . $created_number : '',
                            ];

                            $form_values = [
                                'subject' => '',
                                'effective_date' => date('Y-m-d'),
                                'person_ids' => [],
                            ];
                        } catch (Throwable $e) {
                            $alert = [
                                'type' => 'danger',
                                'title' => 'เกิดข้อผิดพลาด',
                                'message' => $e->getMessage(),
                            ];
                        }
                    }
                } elseif ($action === 'attach' && $outgoing_id > 0) {
                    try {
                        outgoing_attach_files($outgoing_id, $current_pid, $_FILES['attachments'] ?? []);
                        $alert = [
                            'type' => 'success',
                            'title' => 'แนบไฟล์เรียบร้อย',
                            'message' => '',
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
        }

        $active_dh_year = system_get_dh_year();
        $preview_outgoing_no = outgoing_preview_number($active_dh_year);
        $track_status_map = [
            OUTGOING_STATUS_WAITING_ATTACHMENT => ['label' => 'รอการแนบไฟล์', 'pill' => 'pending'],
            OUTGOING_STATUS_COMPLETE => ['label' => 'แนบไฟล์สำเร็จ', 'pill' => 'outgoing-complete'],
        ];
        $status_filter_for_query = match ($filter_status) {
            'waiting_attachment' => OUTGOING_STATUS_WAITING_ATTACHMENT,
            'complete' => OUTGOING_STATUS_COMPLETE,
            default => 'all',
        };
        $outgoing_items = outgoing_list([
            'q' => $search,
            'status' => $status_filter_for_query,
            'created_by_pid' => $current_pid,
            'sort' => $filter_sort,
        ]);
        $summary_counts = outgoing_count_by_status();
        $outgoing_ids = array_map(static function (array $item): int {
            return (int) ($item['outgoingID'] ?? 0);
        }, $outgoing_items);
        $attachments_map = outgoing_list_attachments_map($outgoing_ids);

        view_render('outgoing/index', [
            'alert' => $alert,
            'items' => $outgoing_items,
            'can_manage' => $can_manage,
            'search' => $search,
            'status_filter' => $filter_status,
            'filter_query' => $search,
            'filter_sort' => $filter_sort,
            'is_track_active' => $is_track_active,
            'active_dh_year' => $active_dh_year,
            'preview_outgoing_no' => $preview_outgoing_no,
            'issuer_name' => $issuer_name,
            'form_values' => $form_values,
            'track_status_map' => $track_status_map,
            'send_modal_payload_map' => [],
            'summary_counts' => $summary_counts,
            'attachments_map' => $attachments_map,
        ]);
    }
}

<?php

declare(strict_types=1);

require_once __DIR__ . '/../views/view.php';
require_once __DIR__ . '/../../src/Services/auth/auth-guard.php';
require_once __DIR__ . '/../../src/Services/security/security-service.php';
require_once __DIR__ . '/../auth/csrf.php';
require_once __DIR__ . '/../rbac/current_user.php';
require_once __DIR__ . '/../modules/circulars/service.php';
require_once __DIR__ . '/../modules/circulars/repository.php';
require_once __DIR__ . '/../modules/users/lists.php';
require_once __DIR__ . '/../modules/system/system.php';

if (!function_exists('circular_compose_uploaded_files_count')) {
    function circular_compose_uploaded_files_count(array $files): int
    {
        if (!isset($files['error'])) {
            return 0;
        }

        if (is_array($files['error'])) {
            $count = 0;

            foreach ($files['error'] as $error) {
                if ((int) $error !== UPLOAD_ERR_NO_FILE) {
                    $count++;
                }
            }

            return $count;
        }

        return ((int) $files['error'] !== UPLOAD_ERR_NO_FILE) ? 1 : 0;
    }
}

if (!function_exists('circular_compose_normalize_search')) {
    function circular_compose_normalize_search(string $value): string
    {
        $value = mb_strtolower(trim($value), 'UTF-8');
        $value = preg_replace('/\s+/u', '', $value) ?? '';
        $value = preg_replace('/[^0-9a-zก-๙]/u', '', $value) ?? '';

        return $value;
    }
}

if (!function_exists('circular_compose_index')) {
    function circular_compose_index(): void
    {
        $current_user = current_user() ?? [];
        $current_pid = (string) ($current_user['pID'] ?? '');
        $receipt_circular_id = isset($_GET['receipt']) ? (int) $_GET['receipt'] : 0;
        $receipt_stats = [];
        $receipt_subject = '';
        $receipt_sender_faction = '';
        $active_tab = trim((string) ($_GET['tab'] ?? '')) === 'track' ? 'track' : 'compose';

        $factions = user_list_factions();
        $teachers = array_values(array_filter(user_list_teachers(), static function (array $teacher) use ($current_pid): bool {
            $pid = trim((string) ($teacher['pID'] ?? ''));

            if ($pid === '' || $pid === $current_pid) {
                return false;
            }

            return ctype_digit($pid);
        }));

        $ajax_mode = trim((string) ($_GET['ajax'] ?? ''));

        if ($_SERVER['REQUEST_METHOD'] === 'GET' && $ajax_mode === 'recipient_search') {
            $query = trim((string) ($_GET['q'] ?? ''));
            $normalized_query = circular_compose_normalize_search($query);
            $matched_pids = [];

            if ($normalized_query !== '') {
                foreach ($teachers as $teacher) {
                    $pid = trim((string) ($teacher['pID'] ?? ''));

                    if ($pid === '') {
                        continue;
                    }

                    $search_text = circular_compose_normalize_search(
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

            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode([
                'ok' => true,
                'q' => $query,
                'total' => count($matched_pids),
                'pids' => array_values(array_unique($matched_pids)),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        $sender_from_fid = (int) ($current_user['fID'] ?? 0);

        $values = [
            'subject' => '',
            'detail' => '',
            'linkURL' => '',
            'fromFID' => $sender_from_fid > 0 ? (string) $sender_from_fid : '',
            'faction_ids' => [],
            'person_ids' => [],
        ];

        $alert = null;
        $is_edit_mode = false;
        $existing_attachments = [];
        $edit_circular_id = isset($_GET['edit']) ? (int) $_GET['edit'] : (int) ($_POST['edit_circular_id'] ?? 0);

        $editable_circular = null;

        if ($edit_circular_id > 0) {
            $candidate = circular_get($edit_circular_id);

            if (
                $candidate &&
                (string) ($candidate['createdByPID'] ?? '') === $current_pid &&
                (string) ($candidate['circularType'] ?? '') === CIRCULAR_TYPE_INTERNAL &&
                (string) ($candidate['status'] ?? '') === INTERNAL_STATUS_RECALLED
            ) {
                $is_edit_mode = true;
                $editable_circular = $candidate;
                $existing_attachments = circular_get_attachments($edit_circular_id);

                if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                    $values['subject'] = (string) ($candidate['subject'] ?? '');
                    $values['detail'] = (string) ($candidate['detail'] ?? '');
                    $values['linkURL'] = (string) ($candidate['linkURL'] ?? '');
                    $values['fromFID'] = $sender_from_fid > 0 ? (string) $sender_from_fid : '';

                    $targets = circular_get_recipient_targets($edit_circular_id);
                    $faction_ids = [];
                    $person_ids = [];
                    $role_ids = [];

                    foreach ($targets as $target) {
                        $type = (string) ($target['targetType'] ?? '');

                        if ($type === 'UNIT' && !empty($target['fID'])) {
                            $faction_ids[] = (int) $target['fID'];
                            continue;
                        }

                        if ($type === 'PERSON' && !empty($target['pID'])) {
                            $person_ids[] = (string) $target['pID'];
                            continue;
                        }

                        if ($type === 'ROLE' && !empty($target['roleID'])) {
                            $role_ids[] = (int) $target['roleID'];
                        }
                    }

                    if (!empty($role_ids)) {
                        $role_people = circular_resolve_person_ids([], $role_ids, []);
                        $person_ids = array_merge($person_ids, $role_people);
                    }

                    $values['faction_ids'] = array_values(array_unique(array_filter(array_map('intval', $faction_ids), static function (int $fid): bool {
                        return $fid > 0;
                    })));
                    $values['person_ids'] = array_values(array_unique(array_filter(array_map('strval', $person_ids), static function (string $pid): bool {
                        return trim($pid) !== '';
                    })));
                }
            } else {
                $alert = [
                    'type' => 'warning',
                    'title' => 'ไม่สามารถแก้ไขรายการนี้ได้',
                    'message' => 'รายการต้องเป็นหนังสือเวียนภายในที่ดึงกลับแล้ว และเป็นของคุณเท่านั้น',
                ];
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $post_action = trim((string) ($_POST['action'] ?? ''));

            if ($post_action !== '') {
                $active_tab = 'track';

                if (!csrf_validate($_POST['csrf_token'] ?? null)) {
                    $alert = ['type' => 'danger', 'title' => 'ไม่สามารถยืนยันความปลอดภัย', 'message' => 'กรุณาลองใหม่อีกครั้ง'];
                } else {
                    $circular_id = isset($_POST['circular_id']) ? (int) $_POST['circular_id'] : 0;

                    if ($post_action === 'recall' && $circular_id > 0) {
                        $ok = circular_recall_internal($circular_id, $current_pid);
                        $alert = $ok
                            ? ['type' => 'success', 'title' => 'ดึงหนังสือกลับแล้ว', 'message' => '']
                            : ['type' => 'warning', 'title' => 'ไม่สามารถดึงกลับได้', 'message' => 'มีผู้รับอ่านแล้ว'];
                    } elseif ($post_action === 'recall_external' && $circular_id > 0) {
                        $ok = circular_recall_external_before_review($circular_id, $current_pid);
                        $alert = $ok
                            ? ['type' => 'success', 'title' => 'ดึงหนังสือกลับแล้ว', 'message' => 'สามารถแก้ไขและส่งใหม่ได้']
                            : ['type' => 'warning', 'title' => 'ไม่สามารถดึงกลับได้', 'message' => 'เอกสารถูกพิจารณาแล้วหรือไม่ใช่สิทธิ์ของคุณ'];
                    } elseif ($post_action === 'resend' && $circular_id > 0) {
                        $ok = circular_resend_internal($circular_id, $current_pid);
                        $alert = $ok
                            ? ['type' => 'success', 'title' => 'ส่งใหม่เรียบร้อย', 'message' => '']
                            : ['type' => 'warning', 'title' => 'ไม่สามารถส่งใหม่ได้', 'message' => ''];
                    }
                }
            } else {
                $values['subject'] = trim((string) ($_POST['subject'] ?? ''));
                $values['detail'] = trim((string) ($_POST['detail'] ?? ''));
                $values['linkURL'] = trim((string) ($_POST['linkURL'] ?? ''));
                $values['fromFID'] = $sender_from_fid > 0 ? (string) $sender_from_fid : '';
                $values['faction_ids'] = (array) ($_POST['faction_ids'] ?? []);
                $values['person_ids'] = (array) ($_POST['person_ids'] ?? []);

                $allowed_factions = [];

                foreach ($factions as $faction) {
                    $fid = (int) ($faction['fID'] ?? 0);

                    if ($fid > 0) {
                        $allowed_factions[$fid] = true;
                    }
                }
                $allowed_teachers = [];

                foreach ($teachers as $teacher) {
                    $pid = trim((string) ($teacher['pID'] ?? ''));

                    if ($pid !== '') {
                        $allowed_teachers[$pid] = true;
                    }
                }

                $values['faction_ids'] = array_values(array_unique(array_filter(array_map(static function ($value): int {
                    return (int) $value;
                }, (array) $values['faction_ids']), static function (int $fid) use ($allowed_factions): bool {
                    return $fid > 0 && isset($allowed_factions[$fid]);
                })));

                $values['person_ids'] = array_values(array_unique(array_filter(array_map(static function ($value): string {
                    return trim((string) $value);
                }, (array) $values['person_ids']), static function (string $pid) use ($allowed_teachers): bool {
                    return $pid !== '' && isset($allowed_teachers[$pid]);
                })));

                $sender_from_fid_valid = $sender_from_fid > 0 && isset($allowed_factions[$sender_from_fid]);
                $values['fromFID'] = $sender_from_fid_valid ? (string) $sender_from_fid : '';

                if (!csrf_validate($_POST['csrf_token'] ?? null)) {
                    $alert = ['type' => 'danger', 'title' => 'ไม่สามารถยืนยันความปลอดภัย', 'message' => 'กรุณาลองใหม่อีกครั้ง'];
                } elseif ($edit_circular_id > 0 && !$is_edit_mode) {
                    $alert = ['type' => 'danger', 'title' => 'ไม่สามารถแก้ไขรายการนี้ได้', 'message' => 'สิทธิ์ไม่ถูกต้องหรือสถานะรายการไม่รองรับ'];
                } elseif ($values['subject'] === '') {
                    $alert = ['type' => 'danger', 'title' => 'ข้อมูลไม่ครบถ้วน', 'message' => 'กรุณากรอกหัวข้อเรื่อง'];
                } else {
                    try {
                        $dh_year = system_get_dh_year();
                        $files = $_FILES['attachments'] ?? [];
                        $targets = [];

                        foreach ((array) $values['faction_ids'] as $fid) {
                            $fid_int = (int) $fid;

                            if ($fid_int <= 0) {
                                continue;
                            }
                            $targets[] = ['targetType' => 'UNIT', 'fID' => $fid_int];
                        }

                        $pids = circular_resolve_person_ids((array) $values['faction_ids'], [], (array) $values['person_ids']);

                        if (empty($pids)) {
                            throw new RuntimeException('กรุณาเลือกผู้รับอย่างน้อย 1 รายการ');
                        }

                        if ($is_edit_mode && $edit_circular_id > 0) {
                            $allowed_file_ids = [];

                            foreach ($existing_attachments as $attachment) {
                                $file_id = (int) ($attachment['fileID'] ?? 0);

                                if ($file_id > 0) {
                                    $allowed_file_ids[$file_id] = true;
                                }
                            }

                            $remove_file_ids = array_values(array_unique(array_filter(array_map(static function ($value): int {
                                return (int) $value;
                            }, (array) ($_POST['remove_file_ids'] ?? [])), static function (int $file_id) use ($allowed_file_ids): bool {
                                return $file_id > 0 && isset($allowed_file_ids[$file_id]);
                            })));

                            $remaining_files_count = max(0, count($existing_attachments) - count($remove_file_ids));
                            $uploading_files_count = circular_compose_uploaded_files_count((array) $files);

                            if (($remaining_files_count + $uploading_files_count) > 5) {
                                throw new RuntimeException('แนบไฟล์รวมได้สูงสุด 5 ไฟล์');
                            }

                            circular_edit_and_resend_internal(
                                $edit_circular_id,
                                $current_pid,
                                [
                                    'subject' => $values['subject'],
                                    'detail' => $values['detail'],
                                    'linkURL' => $values['linkURL'],
                                    'fromFID' => (int) $values['fromFID'],
                                ],
                                ['pids' => $pids, 'targets' => $targets],
                                (array) $files,
                                $remove_file_ids
                            );

                            $editable_circular = circular_get($edit_circular_id);
                            $existing_attachments = circular_get_attachments($edit_circular_id);
                            $alert = ['type' => 'success', 'title' => 'บันทึกแก้ไขและส่งใหม่แล้ว', 'message' => 'เลขที่รายการ #' . $edit_circular_id];
                        } else {
                            $circularID = circular_create_internal([
                                'dh_year' => $dh_year,
                                'circularType' => CIRCULAR_TYPE_INTERNAL,
                                'subject' => $values['subject'],
                                'detail' => $values['detail'],
                                'linkURL' => $values['linkURL'] !== '' ? $values['linkURL'] : null,
                                'fromFID' => (int) $values['fromFID'],
                                'status' => INTERNAL_STATUS_SENT,
                                'createdByPID' => $current_pid,
                            ], ['pids' => $pids, 'targets' => $targets], (array) $files);

                            $alert = ['type' => 'success', 'title' => 'ส่งหนังสือเวียนแล้ว', 'message' => 'เลขที่รายการ #' . $circularID];

                            $values['subject'] = '';
                            $values['detail'] = '';
                            $values['linkURL'] = '';
                            $values['faction_ids'] = [];
                            $values['person_ids'] = [];
                            $values['fromFID'] = $sender_from_fid > 0 ? (string) $sender_from_fid : '';
                        }
                    } catch (Throwable $e) {
                        $alert = ['type' => 'danger', 'title' => 'เกิดข้อผิดพลาด', 'message' => $e->getMessage()];
                    }
                }
            }
        }

        $filter_query = trim((string) ($_GET['q'] ?? ''));
        $filter_status = strtoupper(trim((string) ($_GET['status'] ?? 'ALL')));
        $filter_sort = strtolower(trim((string) ($_GET['sort'] ?? 'newest')));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $per_page = (int) ($_GET['per_page'] ?? 10);

        $allowed_statuses = [
            'ALL',
            INTERNAL_STATUS_SENT,
            INTERNAL_STATUS_RECALLED,
            EXTERNAL_STATUS_SUBMITTED,
            EXTERNAL_STATUS_PENDING_REVIEW,
            EXTERNAL_STATUS_REVIEWED,
            EXTERNAL_STATUS_FORWARDED,
        ];

        if (!in_array($filter_status, $allowed_statuses, true)) {
            $filter_status = 'ALL';
        }

        if (!in_array($filter_sort, ['newest', 'oldest'], true)) {
            $filter_sort = 'newest';
        }

        if (!in_array($per_page, [10, 20, 50], true)) {
            $per_page = 10;
        }

        if ($filter_query !== '' || $filter_status !== 'ALL' || $filter_sort !== 'newest' || isset($_GET['page'])) {
            $active_tab = 'track';
        }

        $all_items = circular_list_sent($current_pid);

        if ($receipt_circular_id > 0) {
            foreach ($all_items as $sent_item) {
                if ((int) ($sent_item['circularID'] ?? 0) === $receipt_circular_id) {
                    $receipt_subject = (string) ($sent_item['subject'] ?? '');
                    $receipt_sender_faction = (string) ($sent_item['senderFactionName'] ?? '');
                    $receipt_stats = circular_get_read_stats($receipt_circular_id);
                    break;
                }
            }
        }
        $filtered_items = array_values(array_filter($all_items, static function (array $item) use ($filter_status, $filter_query): bool {
            $item_status = strtoupper(trim((string) ($item['status'] ?? '')));
            $subject = (string) ($item['subject'] ?? '');
            $circular_id = (string) (int) ($item['circularID'] ?? 0);

            if ($filter_status !== 'ALL' && $item_status !== $filter_status) {
                return false;
            }

            if ($filter_query !== '') {
                $haystack = (function_exists('mb_strtolower') ? mb_strtolower($subject . ' ' . $circular_id, 'UTF-8') : strtolower($subject . ' ' . $circular_id));
                $needle = (function_exists('mb_strtolower') ? mb_strtolower($filter_query, 'UTF-8') : strtolower($filter_query));
                $position = function_exists('mb_strpos') ? mb_strpos($haystack, $needle, 0, 'UTF-8') : strpos($haystack, $needle);

                if ($position === false) {
                    return false;
                }
            }

            return true;
        }));

        usort($filtered_items, static function (array $a, array $b) use ($filter_sort): int {
            $time_a = strtotime((string) ($a['createdAt'] ?? '')) ?: 0;
            $time_b = strtotime((string) ($b['createdAt'] ?? '')) ?: 0;

            if ($time_a === $time_b) {
                return ((int) ($b['circularID'] ?? 0)) <=> ((int) ($a['circularID'] ?? 0));
            }

            return $filter_sort === 'oldest' ? ($time_a <=> $time_b) : ($time_b <=> $time_a);
        });

        $filtered_total = count($filtered_items);
        $total_pages = max(1, (int) ceil($filtered_total / $per_page));

        if ($page > $total_pages) {
            $page = $total_pages;
        }
        $offset = ($page - 1) * $per_page;
        $sent_items = array_slice($filtered_items, $offset, $per_page);
        $read_stats_map = [];
        $detail_map = [];

        foreach ($sent_items as $sent_item) {
            $circular_id = (int) ($sent_item['circularID'] ?? 0);

            if ($circular_id <= 0) {
                continue;
            }
            $read_stats_map[$circular_id] = circular_get_read_stats($circular_id);

            $circular_row = circular_get($circular_id);
            $attachments = circular_get_attachments($circular_id);
            $detail_map[$circular_id] = [
                'detail' => (string) (($circular_row['detail'] ?? $sent_item['detail'] ?? '')),
                'senderName' => (string) (($circular_row['senderName'] ?? '')),
                'senderFactionName' => (string) (($circular_row['senderFactionName'] ?? $sent_item['senderFactionName'] ?? '')),
                'files' => is_array($attachments) ? $attachments : [],
            ];
        }
        $query_params = [
            'tab' => 'track',
            'q' => $filter_query,
            'status' => strtolower($filter_status),
            'sort' => $filter_sort,
            'per_page' => $per_page,
        ];

        view_render('circular/compose', [
            'alert' => $alert,
            'values' => $values,
            'factions' => $factions,
            'teachers' => $teachers,
            'is_edit_mode' => $is_edit_mode,
            'edit_circular_id' => $edit_circular_id,
            'editable_circular' => $editable_circular,
            'existing_attachments' => $existing_attachments,
            'sent_items' => $sent_items,
            'receipt_circular_id' => $receipt_circular_id,
            'receipt_subject' => $receipt_subject,
            'receipt_sender_faction' => $receipt_sender_faction,
            'receipt_stats' => $receipt_stats,
            'filter_query' => $filter_query,
            'filter_status' => strtolower($filter_status),
            'filter_sort' => $filter_sort,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => $total_pages,
            'filtered_total' => $filtered_total,
            'query_params' => $query_params,
            'active_tab' => $active_tab,
            'read_stats_map' => $read_stats_map,
            'detail_map' => $detail_map,
        ]);
    }
}

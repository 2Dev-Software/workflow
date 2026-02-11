<?php
declare(strict_types=1);

require_once __DIR__ . '/../views/view.php';
require_once __DIR__ . '/../../src/Services/auth/auth-guard.php';
require_once __DIR__ . '/../../src/Services/security/security-service.php';
require_once __DIR__ . '/../auth/csrf.php';
require_once __DIR__ . '/../rbac/current_user.php';
require_once __DIR__ . '/../modules/circulars/service.php';
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

if (!function_exists('circular_compose_index')) {
    function circular_compose_index(): void
    {
        $current_user = current_user() ?? [];
        $current_pid = (string) ($current_user['pID'] ?? '');

        $factions = user_list_factions();
        $teachers = array_values(array_filter(user_list_teachers(), static function (array $teacher) use ($current_pid): bool {
            $pid = trim((string) ($teacher['pID'] ?? ''));
            if ($pid === '' || $pid === $current_pid) {
                return false;
            }

            return ctype_digit($pid);
        }));

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

        view_render('circular/compose', [
            'alert' => $alert,
            'values' => $values,
            'factions' => $factions,
            'teachers' => $teachers,
            'is_edit_mode' => $is_edit_mode,
            'edit_circular_id' => $edit_circular_id,
            'editable_circular' => $editable_circular,
            'existing_attachments' => $existing_attachments,
        ]);
    }
}

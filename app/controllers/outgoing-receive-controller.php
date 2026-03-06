<?php

declare(strict_types=1);

require_once __DIR__ . '/../views/view.php';
require_once __DIR__ . '/../../src/Services/auth/auth-guard.php';
require_once __DIR__ . '/../auth/csrf.php';
require_once __DIR__ . '/../rbac/current_user.php';
require_once __DIR__ . '/../modules/outgoing/service.php';
require_once __DIR__ . '/../modules/circulars/service.php';
require_once __DIR__ . '/../modules/users/lists.php';
require_once __DIR__ . '/../modules/system/system.php';
require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/outgoing-shared-controller.php';

if (!function_exists('outgoing_receive_index')) {
    function outgoing_receive_index(): void
    {
        $current_user = current_user() ?? [];
        $current_pid = (string) ($current_user['pID'] ?? '');
        $connection = db_connection();
        $can_manage = outgoing_user_can_manage($connection, $current_pid, $current_user);

        if (!$can_manage) {
            http_response_code(403);
            require __DIR__ . '/../views/errors/403.php';

            return;
        }

        $factions = user_list_factions();
        $allowed_faction_ids = [];

        foreach ($factions as $faction) {
            $fid = (int) ($faction['fID'] ?? 0);

            if ($fid > 0) {
                $allowed_faction_ids[$fid] = true;
            }
        }

        $reviewers = outgoing_receive_get_reviewers();
        $reviewer_ids = [];

        foreach ($reviewers as $reviewer) {
            $pid = trim((string) ($reviewer['pID'] ?? ''));

            if ($pid !== '') {
                $reviewer_ids[$pid] = true;
            }
        }

        $alert = null;
        $values = outgoing_receive_default_values();
        $is_edit_mode = false;
        $edit_circular_id = isset($_GET['edit']) ? (int) $_GET['edit'] : (int) ($_POST['edit_circular_id'] ?? 0);
        $editable_circular = null;
        $existing_attachments = [];

        if ($edit_circular_id > 0) {
            $candidate = circular_get($edit_circular_id);

            if (
                $candidate
                && (string) ($candidate['createdByPID'] ?? '') === $current_pid
                && (string) ($candidate['circularType'] ?? '') === CIRCULAR_TYPE_EXTERNAL
                && (string) ($candidate['status'] ?? '') === EXTERNAL_STATUS_SUBMITTED
            ) {
                $is_edit_mode = true;
                $editable_circular = $candidate;
                $existing_attachments = circular_get_attachments($edit_circular_id);

                if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                    $values['extPriority'] = (string) ($candidate['extPriority'] ?? 'ปกติ');
                    $values['extBookNo'] = (string) ($candidate['extBookNo'] ?? '');
                    $values['extIssuedDate'] = (string) ($candidate['extIssuedDate'] ?? '');
                    $values['subject'] = (string) ($candidate['subject'] ?? '');
                    $values['extFromText'] = (string) ($candidate['extFromText'] ?? '');
                    $candidate_fid = (int) ($candidate['extGroupFID'] ?? 0);
                    $values['extGroupFID'] = ($candidate_fid > 0 && isset($allowed_faction_ids[$candidate_fid])) ? (string) $candidate_fid : '';
                    $values['linkURL'] = (string) ($candidate['linkURL'] ?? '');
                    $values['detail'] = (string) ($candidate['detail'] ?? '');

                    $last_reviewer_pid = circular_external_last_reviewer_pid($edit_circular_id);

                    if ($last_reviewer_pid !== null && isset($reviewer_ids[$last_reviewer_pid])) {
                        $values['reviewerPID'] = $last_reviewer_pid;
                    } else {
                        $current_director_pid = (string) (system_get_current_director_pid() ?? '');

                        if ($current_director_pid !== '' && isset($reviewer_ids[$current_director_pid])) {
                            $values['reviewerPID'] = $current_director_pid;
                        }
                    }
                }
            } else {
                $alert = [
                    'type' => 'warning',
                    'title' => 'ไม่สามารถแก้ไขรายการนี้ได้',
                    'message' => 'ต้องเป็นหนังสือเวียนภายนอกสถานะรับเข้าแล้ว และเป็นรายการของคุณเท่านั้น',
                ];
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $values['extPriority'] = trim((string) ($_POST['extPriority'] ?? 'ปกติ'));
            $values['extBookNo'] = trim((string) ($_POST['extBookNo'] ?? ''));
            $values['extIssuedDate'] = trim((string) ($_POST['extIssuedDate'] ?? ''));
            $values['subject'] = trim((string) ($_POST['subject'] ?? ''));
            $values['extFromText'] = trim((string) ($_POST['extFromText'] ?? ''));
            $values['extGroupFID'] = trim((string) ($_POST['extGroupFID'] ?? ''));
            $values['linkURL'] = trim((string) ($_POST['linkURL'] ?? ''));
            $values['detail'] = trim((string) ($_POST['detail'] ?? ($_POST['memo_detail'] ?? '')));
            $values['reviewerPID'] = trim((string) ($_POST['reviewerPID'] ?? ''));

            $ext_group_fid_int = (int) $values['extGroupFID'];

            if ($ext_group_fid_int <= 0 || !isset($allowed_faction_ids[$ext_group_fid_int])) {
                $values['extGroupFID'] = '';
            } else {
                $values['extGroupFID'] = (string) $ext_group_fid_int;
            }

            $attachments = outgoing_merge_upload_sets(
                $_FILES['cover_attachments'] ?? [],
                $_FILES['attachments'] ?? []
            );

            if (!csrf_validate($_POST['csrf_token'] ?? null)) {
                $alert = [
                    'type' => 'danger',
                    'title' => 'ไม่สามารถยืนยันความปลอดภัย',
                    'message' => 'กรุณาลองใหม่อีกครั้ง',
                ];
            } elseif ($edit_circular_id > 0 && !$is_edit_mode) {
                $alert = [
                    'type' => 'danger',
                    'title' => 'ไม่สามารถแก้ไขรายการนี้ได้',
                    'message' => 'สิทธิ์ไม่ถูกต้องหรือสถานะรายการไม่รองรับ',
                ];
            } elseif (!in_array($values['extPriority'], ['ปกติ', 'ด่วน', 'ด่วนมาก', 'ด่วนที่สุด'], true)) {
                $alert = [
                    'type' => 'danger',
                    'title' => 'ข้อมูลไม่ถูกต้อง',
                    'message' => 'กรุณาเลือกประเภทความเร่งด่วน',
                ];
            } elseif ($values['extBookNo'] === '' || $values['subject'] === '' || $values['extFromText'] === '' || $values['extIssuedDate'] === '') {
                $alert = [
                    'type' => 'danger',
                    'title' => 'ข้อมูลไม่ครบถ้วน',
                    'message' => 'กรุณากรอก เลขที่หนังสือ ลงวันที่ เรื่อง และจาก',
                ];
            } elseif (strtotime($values['extIssuedDate']) === false) {
                $alert = [
                    'type' => 'danger',
                    'title' => 'ข้อมูลไม่ถูกต้อง',
                    'message' => 'รูปแบบวันที่ไม่ถูกต้อง',
                ];
            } elseif ($values['reviewerPID'] === '' || !isset($reviewer_ids[$values['reviewerPID']])) {
                $alert = [
                    'type' => 'danger',
                    'title' => 'ข้อมูลไม่ครบถ้วน',
                    'message' => 'กรุณาเลือกผู้พิจารณา (ผอ./รอง/รักษาการ)',
                ];
            } else {
                try {
                    $dh_year = system_get_dh_year();

                    if ($is_edit_mode && $edit_circular_id > 0) {
                        $existing = db_fetch_one(
                            'SELECT circularID FROM dh_circulars WHERE dh_year = ? AND extBookNo = ? AND deletedAt IS NULL AND circularID <> ? LIMIT 1',
                            'isi',
                            $dh_year,
                            $values['extBookNo'],
                            $edit_circular_id
                        );
                    } else {
                        $existing = db_fetch_one(
                            'SELECT circularID FROM dh_circulars WHERE dh_year = ? AND extBookNo = ? AND deletedAt IS NULL LIMIT 1',
                            'is',
                            $dh_year,
                            $values['extBookNo']
                        );
                    }

                    if ($existing) {
                        throw new RuntimeException('เลขที่หนังสือนี้ถูกใช้งานแล้วในปีสารบรรณปัจจุบัน');
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
                        $uploading_files_count = outgoing_uploaded_files_count($attachments);

                        if (($remaining_files_count + $uploading_files_count) > 5) {
                            throw new RuntimeException('แนบไฟล์รวมได้สูงสุด 5 ไฟล์');
                        }

                        $updated = circular_edit_and_resend_external(
                            $edit_circular_id,
                            $current_pid,
                            [
                                'subject' => $values['subject'],
                                'detail' => $values['detail'],
                                'linkURL' => $values['linkURL'],
                                'extPriority' => $values['extPriority'],
                                'extBookNo' => $values['extBookNo'],
                                'extIssuedDate' => $values['extIssuedDate'],
                                'extFromText' => $values['extFromText'],
                                'extGroupFID' => $values['extGroupFID'] !== '' ? (int) $values['extGroupFID'] : null,
                                'reviewerPID' => $values['reviewerPID'],
                            ],
                            $attachments,
                            $remove_file_ids
                        );

                        if (!$updated) {
                            throw new RuntimeException('ไม่สามารถแก้ไขและส่งใหม่ได้ในสถานะปัจจุบัน');
                        }

                        $alert = [
                            'type' => 'success',
                            'title' => 'แก้ไขและส่งใหม่เรียบร้อย',
                            'message' => 'เลขที่รายการ #' . $edit_circular_id,
                        ];

                        $is_edit_mode = false;
                        $editable_circular = null;
                        $existing_attachments = [];
                        $edit_circular_id = 0;
                        $values = outgoing_receive_default_values();
                    } else {
                        $circular_id = circular_create_external([
                            'dh_year' => $dh_year,
                            'circularType' => CIRCULAR_TYPE_EXTERNAL,
                            'subject' => $values['subject'],
                            'detail' => $values['detail'] !== '' ? $values['detail'] : null,
                            'linkURL' => $values['linkURL'] !== '' ? $values['linkURL'] : null,
                            'fromFID' => !empty($current_user['fID']) ? (int) $current_user['fID'] : null,
                            'extPriority' => $values['extPriority'],
                            'extBookNo' => $values['extBookNo'],
                            'extIssuedDate' => $values['extIssuedDate'],
                            'extFromText' => $values['extFromText'],
                            'extGroupFID' => $values['extGroupFID'] !== '' ? (int) $values['extGroupFID'] : null,
                            'status' => EXTERNAL_STATUS_SUBMITTED,
                            'createdByPID' => $current_pid,
                            'registryNote' => null,
                        ], $current_pid, true, $attachments, $values['reviewerPID']);

                        $alert = [
                            'type' => 'success',
                            'title' => 'ลงทะเบียนรับหนังสือเรียบร้อย',
                            'message' => 'เลขที่รายการ #' . $circular_id,
                        ];
                        $values = outgoing_receive_default_values();
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

        view_render('outgoing/receive', [
            'alert' => $alert,
            'values' => $values,
            'factions' => $factions,
            'reviewers' => $reviewers,
            'is_edit_mode' => $is_edit_mode,
            'edit_circular_id' => $edit_circular_id,
            'editable_circular' => $editable_circular,
            'existing_attachments' => $existing_attachments,
        ]);
    }
}

<?php

declare(strict_types=1);

require_once __DIR__ . '/../views/view.php';
require_once __DIR__ . '/../../src/Services/auth/auth-guard.php';
require_once __DIR__ . '/../auth/csrf.php';
require_once __DIR__ . '/../rbac/current_user.php';
require_once __DIR__ . '/../modules/audit/logger.php';
require_once __DIR__ . '/../modules/certificates/service.php';
require_once __DIR__ . '/../modules/certificates/repository.php';
require_once __DIR__ . '/../modules/system/system.php';
require_once __DIR__ . '/../db/db.php';

if (!function_exists('certificates_list_group_options')) {
    function certificates_list_group_options(mysqli $connection): array
    {
        if (!db_table_exists($connection, 'faction')) {
            return [];
        }

        $rows = db_fetch_all('SELECT fID, fName FROM faction ORDER BY fID ASC');
        $items = [];

        foreach ($rows as $row) {
            $fid = (int) ($row['fID'] ?? 0);
            $name = trim((string) ($row['fName'] ?? ''));

            if ($fid <= 0 || $name === '') {
                continue;
            }

            $items[] = [
                'fID' => $fid,
                'fName' => $name,
            ];
        }

        return $items;
    }
}

if (!function_exists('certificates_find_group_name')) {
    function certificates_find_group_name(array $groups, string $group_fid): string
    {
        foreach ($groups as $group) {
            if ((string) ($group['fID'] ?? '') === $group_fid) {
                return trim((string) ($group['fName'] ?? ''));
            }
        }

        return '';
    }
}

if (!function_exists('certificates_redirect_target')) {
    function certificates_redirect_target(string $active_tab, string $search, string $filter_status, string $filter_sort): string
    {
        $params = ['tab' => $active_tab];

        if ($active_tab === 'data' || $active_tab === 'mine') {
            $params['q'] = $search;
            $params['status'] = $filter_status;
            $params['sort'] = $filter_sort;
        }

        return 'certificates.php?' . http_build_query($params);
    }
}

if (!function_exists('certificates_index')) {
    function certificates_index(): void
    {
        $connection = db_connection();
        certificates_ensure_schema($connection);

        $current_user = current_user() ?? [];
        $current_pid = trim((string) ($current_user['pID'] ?? ''));

        if ($current_pid === '') {
            http_response_code(403);
            require __DIR__ . '/../views/errors/403.php';

            return;
        }

        $current_name = trim((string) ($current_user['fName'] ?? ''));
        $current_fid = (int) ($current_user['fID'] ?? 0);
        $dh_year = system_get_dh_year();
        $groups = certificates_list_group_options($connection);

        $active_tab = trim((string) ($_GET['tab'] ?? 'compose'));

        if (!in_array($active_tab, ['compose', 'data', 'mine'], true)) {
            $active_tab = 'compose';
        }

        $search = trim((string) ($_GET['q'] ?? ''));
        $filter_status = strtolower(trim((string) ($_GET['status'] ?? 'all')));
        $filter_sort = strtolower(trim((string) ($_GET['sort'] ?? 'newest')));

        if (!in_array($filter_status, ['all', 'waiting_attachment', 'complete'], true)) {
            $filter_status = 'all';
        }

        if (!in_array($filter_sort, ['newest', 'oldest'], true)) {
            $filter_sort = 'newest';
        }

        $default_group_fid = '';

        foreach ($groups as $group) {
            if ((int) ($group['fID'] ?? 0) === $current_fid) {
                $default_group_fid = (string) $current_fid;
                break;
            }
        }

        if ($default_group_fid === '' && $groups !== []) {
            $default_group_fid = (string) ($groups[0]['fID'] ?? '');
        }

        $default_form_values = [
            'total_certificates' => '',
            'subject' => '',
            'group_fid' => $default_group_fid,
        ];
        $form_values = array_merge($default_form_values, (array) (flash_get('certificates_form_values', []) ?: []));
        $alert = flash_get('certificates_alert');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = trim((string) ($_POST['action'] ?? ''));

            if ($action === 'attach') {
                $active_tab = 'mine';
            }

            $redirect_target = certificates_redirect_target($active_tab, $search, $filter_status, $filter_sort);

            if (!csrf_validate($_POST['csrf_token'] ?? null)) {
                audit_log('security', 'CSRF_FAIL', 'DENY', CERTIFICATE_ENTITY_NAME, null, 'certificates_controller');
                $alert = [
                    'type' => 'danger',
                    'title' => 'ไม่สามารถยืนยันความปลอดภัย',
                    'message' => 'กรุณาลองใหม่อีกครั้ง',
                ];
            } elseif ($action === 'create' || $action === '') {
                $form_values['total_certificates'] = trim((string) ($_POST['total_certificates'] ?? ''));
                $form_values['subject'] = trim((string) ($_POST['subject'] ?? ''));
                $form_values['group_fid'] = trim((string) ($_POST['group_fid'] ?? $default_group_fid));
                $total_certificates = certificate_normalize_total($form_values['total_certificates']);
                $group_name = certificates_find_group_name($groups, $form_values['group_fid']);

                if ($total_certificates <= 0) {
                    audit_log('certificates', 'CREATE', 'FAIL', CERTIFICATE_ENTITY_NAME, null, 'missing_total_certificates', [
                        'dhYear' => $dh_year,
                        'createdByPID' => $current_pid,
                    ]);
                    $alert = [
                        'type' => 'danger',
                        'title' => 'กรุณาระบุจำนวนเกียรติบัตร',
                        'message' => '',
                    ];
                } elseif ($form_values['subject'] === '') {
                    audit_log('certificates', 'CREATE', 'FAIL', CERTIFICATE_ENTITY_NAME, null, 'missing_subject', [
                        'dhYear' => $dh_year,
                        'createdByPID' => $current_pid,
                        'totalCertificates' => $total_certificates,
                    ]);
                    $alert = [
                        'type' => 'danger',
                        'title' => 'กรุณากรอกเรื่อง',
                        'message' => '',
                    ];
                } elseif ($group_name === '') {
                    audit_log('certificates', 'CREATE', 'FAIL', CERTIFICATE_ENTITY_NAME, null, 'invalid_group', [
                        'dhYear' => $dh_year,
                        'createdByPID' => $current_pid,
                        'totalCertificates' => $total_certificates,
                    ]);
                    $alert = [
                        'type' => 'danger',
                        'title' => 'กรุณาเลือกในนามของ',
                        'message' => '',
                    ];
                } else {
                    try {
                        $certificate_id = certificate_create_issue([
                            'dh_year' => $dh_year,
                            'totalCertificates' => $total_certificates,
                            'subject' => $form_values['subject'],
                            'groupFID' => (int) $form_values['group_fid'],
                            'createdByPID' => $current_pid,
                        ]);

                        $created_certificate = certificate_get($certificate_id) ?? [];
                        $alert = [
                            'type' => 'success',
                            'title' => 'ออกเลขเกียรติบัตรเรียบร้อย',
                            'message' => trim((string) ($created_certificate['certificateFromNo'] ?? '')) !== ''
                                ? trim((string) ($created_certificate['certificateFromNo'] ?? '')) . ' ถึง ' . trim((string) ($created_certificate['certificateToNo'] ?? ''))
                                : '',
                        ];
                        $form_values = $default_form_values;
                    } catch (Throwable $e) {
                        $alert = [
                            'type' => 'danger',
                            'title' => 'เกิดข้อผิดพลาด',
                            'message' => $e->getMessage(),
                        ];
                    }
                }
            } elseif ($action === 'attach') {
                $certificate_id = isset($_POST['certificate_id']) ? (int) $_POST['certificate_id'] : 0;
                $remove_file_ids = (array) ($_POST['remove_file_ids'] ?? []);

                if ($certificate_id <= 0) {
                    audit_log('certificates', 'ATTACH', 'FAIL', CERTIFICATE_ENTITY_NAME, null, 'invalid_certificate_id', [
                        'createdByPID' => $current_pid,
                    ]);
                    $alert = [
                        'type' => 'danger',
                        'title' => 'ข้อมูลไม่ถูกต้อง',
                        'message' => 'ไม่พบรายการที่ต้องการแก้ไข',
                    ];
                } else {
                    try {
                        certificate_update_attachments($certificate_id, $current_pid, $_FILES['attachments'] ?? [], $remove_file_ids);
                        $updated_certificate = certificate_get($certificate_id) ?? [];
                        $updated_status = trim((string) ($updated_certificate['status'] ?? ''));
                        $updated_label = $updated_status === CERTIFICATE_STATUS_COMPLETE ? 'แนบไฟล์สำเร็จ' : 'รอการแนบไฟล์';

                        $alert = [
                            'type' => 'success',
                            'title' => 'อัปเดตไฟล์เกียรติบัตรเรียบร้อย',
                            'message' => $updated_label,
                        ];
                    } catch (Throwable $e) {
                        $alert = [
                            'type' => 'danger',
                            'title' => 'เกิดข้อผิดพลาด',
                            'message' => $e->getMessage(),
                        ];
                    }
                }
            } else {
                audit_log('certificates', 'ACTION', 'FAIL', CERTIFICATE_ENTITY_NAME, null, 'invalid_action', [
                    'requestedAction' => $action,
                    'createdByPID' => $current_pid,
                ]);
                $alert = [
                    'type' => 'danger',
                    'title' => 'ข้อมูลไม่ถูกต้อง',
                    'message' => 'ไม่พบคำสั่งที่ต้องการ',
                ];
            }

            if ($alert !== null) {
                flash_set('certificates_alert', $alert);
            }

            if (($action === 'create' || $action === '') && (($alert['type'] ?? '') !== 'success')) {
                flash_set('certificates_form_values', $form_values);
            }

            redirect_to($redirect_target, 303);
        }

        if ($active_tab === 'data' || $active_tab === 'mine' || $search !== '' || $filter_status !== 'all' || $filter_sort !== 'newest') {
            audit_log('certificates', 'SEARCH', 'SUCCESS', CERTIFICATE_ENTITY_NAME, null, null, array_filter([
                'tab' => $active_tab,
                'query' => $search !== '' ? $search : null,
                'statusFilter' => $filter_status,
                'sort' => $filter_sort,
                'createdByPID' => $active_tab === 'mine' ? $current_pid : null,
            ], static function ($value): bool {
                return $value !== null && $value !== '';
            }));
        } else {
            audit_log('certificates', 'VIEW', 'SUCCESS', CERTIFICATE_ENTITY_NAME, null, null, [
                'tab' => $active_tab,
            ]);
        }

        $status_filter_for_query = match ($filter_status) {
            'waiting_attachment' => CERTIFICATE_STATUS_WAITING_ATTACHMENT,
            'complete' => CERTIFICATE_STATUS_COMPLETE,
            default => 'all',
        };
        $certificate_items = certificate_list([
            'q' => $search,
            'status' => $status_filter_for_query,
            'sort' => $filter_sort,
        ]);
        $my_certificate_items = certificate_list([
            'q' => $search,
            'status' => $status_filter_for_query,
            'sort' => $filter_sort,
            'created_by_pid' => $current_pid,
        ]);

        $all_ids = array_values(array_unique(array_merge(
            array_map(static fn(array $item): int => (int) ($item['certificateID'] ?? 0), $certificate_items),
            array_map(static fn(array $item): int => (int) ($item['certificateID'] ?? 0), $my_certificate_items)
        )));
        $attachments_map = certificate_list_attachments_map($all_ids);
        $status_map = [
            CERTIFICATE_STATUS_WAITING_ATTACHMENT => ['label' => 'รอการแนบไฟล์', 'pill' => 'pending'],
            CERTIFICATE_STATUS_COMPLETE => ['label' => 'แนบไฟล์สำเร็จ', 'pill' => 'approved'],
        ];
        $view_modal_payload_map = certificate_build_modal_payload_map(array_merge($certificate_items, $my_certificate_items), $attachments_map, $status_map);
        $preview_base = certificate_preview_range($dh_year, 1);

        view_render('certificates/index', [
            'active_tab' => $active_tab,
            'filter_status' => $filter_status,
            'filter_sort' => $filter_sort,
            'search' => $search,
            'alert' => $alert,
            'dh_year' => $dh_year,
            'current_user_name' => $current_name !== '' ? $current_name : $current_pid,
            'groups' => $groups,
            'form_values' => $form_values,
            'certificate_items' => $certificate_items,
            'my_certificate_items' => $my_certificate_items,
            'certificate_status_map' => $status_map,
            'view_modal_payload_map' => $view_modal_payload_map,
            'preview_base' => $preview_base,
        ]);
    }
}

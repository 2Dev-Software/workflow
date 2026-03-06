<?php

declare(strict_types=1);

require_once __DIR__ . '/../modules/system/system.php';
require_once __DIR__ . '/../db/db.php';

if (!function_exists('outgoing_merge_upload_sets')) {
    /**
     * Merge multiple $_FILES input sets into a single multi-file set.
     *
     * @param array<int, array<string, mixed>> $file_sets
     */
    function outgoing_merge_upload_sets(array ...$file_sets): array
    {
        $merged = [
            'name' => [],
            'type' => [],
            'tmp_name' => [],
            'error' => [],
            'size' => [],
        ];

        foreach ($file_sets as $set) {
            if (!is_array($set) || !isset($set['name'])) {
                continue;
            }

            $names = $set['name'] ?? [];
            $types = $set['type'] ?? [];
            $tmp_names = $set['tmp_name'] ?? [];
            $errors = $set['error'] ?? [];
            $sizes = $set['size'] ?? [];

            if (!is_array($names)) {
                $merged['name'][] = (string) $names;
                $merged['type'][] = (string) $types;
                $merged['tmp_name'][] = (string) $tmp_names;
                $merged['error'][] = (int) $errors;
                $merged['size'][] = (int) $sizes;
                continue;
            }

            foreach ($names as $index => $name) {
                $merged['name'][] = (string) ($names[$index] ?? '');
                $merged['type'][] = (string) ($types[$index] ?? '');
                $merged['tmp_name'][] = (string) ($tmp_names[$index] ?? '');
                $merged['error'][] = (int) ($errors[$index] ?? UPLOAD_ERR_NO_FILE);
                $merged['size'][] = (int) ($sizes[$index] ?? 0);
            }
        }

        return $merged;
    }
}

if (!function_exists('outgoing_uploaded_files_count')) {
    function outgoing_uploaded_files_count(array $files): int
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

if (!function_exists('outgoing_receive_default_values')) {
    function outgoing_receive_default_values(): array
    {
        return [
            'extPriority' => 'ปกติ',
            'extBookNo' => '',
            'extIssuedDate' => '',
            'subject' => '',
            'extFromText' => '',
            'extGroupFID' => '',
            'linkURL' => '',
            'detail' => '',
            'reviewerPID' => '',
        ];
    }
}

if (!function_exists('outgoing_receive_get_reviewers')) {
    function outgoing_receive_get_reviewers(): array
    {
        $connection = db_connection();
        $reviewers = [];
        $seen = [];

        $current_director_pid = (string) (system_get_current_director_pid() ?? '');
        $acting_pid = (string) (system_get_acting_director_pid() ?? '');
        $director_pid = (string) (system_get_director_pid() ?? '');

        if ($current_director_pid !== '') {
            $director_row = db_fetch_one(
                'SELECT pID, fName, positionID FROM teacher WHERE pID = ? AND status = 1 LIMIT 1',
                's',
                $current_director_pid
            );

            if ($director_row) {
                $label = trim((string) ($director_row['fName'] ?? ''));

                if ($current_director_pid === $acting_pid) {
                    $label .= ' (รองรักษาราชการแทน)';
                } elseif ($current_director_pid === $director_pid) {
                    $label .= ' (ผู้อำนวยการ)';
                }

                $reviewers[] = [
                    'pID' => (string) ($director_row['pID'] ?? ''),
                    'label' => trim($label),
                ];
                $seen[(string) ($director_row['pID'] ?? '')] = true;
            }
        }

        $deputy_position_ids = system_position_deputy_ids($connection);

        if (!empty($deputy_position_ids)) {
            $placeholders = implode(', ', array_fill(0, count($deputy_position_ids), '?'));
            $types = str_repeat('i', count($deputy_position_ids));
            $sql = 'SELECT pID, fName FROM teacher WHERE status = 1 AND positionID IN (' . $placeholders . ') ORDER BY fName ASC';
            $deputies = db_fetch_all($sql, $types, ...$deputy_position_ids);

            foreach ($deputies as $deputy) {
                $pid = trim((string) ($deputy['pID'] ?? ''));

                if ($pid === '' || isset($seen[$pid])) {
                    continue;
                }

                $label = trim((string) ($deputy['fName'] ?? ''));

                if ($pid === $acting_pid) {
                    $label .= ' (รองรักษาราชการแทน)';
                } else {
                    $label .= ' (รองผู้อำนวยการ)';
                }

                $reviewers[] = [
                    'pID' => $pid,
                    'label' => trim($label),
                ];
                $seen[$pid] = true;
            }
        }

        return $reviewers;
    }
}

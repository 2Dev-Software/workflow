<?php

declare(strict_types=1);

require_once __DIR__ . '/../views/view.php';
require_once __DIR__ . '/../../src/Services/auth/auth-guard.php';
require_once __DIR__ . '/../../src/Services/security/security-service.php';
require_once __DIR__ . '/../../src/Services/system/system-year-save.php';
require_once __DIR__ . '/../../src/Services/system/system-status-save.php';
require_once __DIR__ . '/../../src/Services/system/exec-duty-save.php';
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/../modules/system/system.php';

if (!function_exists('setting_index')) {
    function setting_index(): void
    {
        $connection = db_connection();
        $teacher_directory_filter_did = 12;
        $teacher_directory_per_page = 'all';
        $teacher_directory_query = '';
        $teacher_directory_order_by = 'position';
        require_once __DIR__ . '/../../src/Services/teacher/teacher-directory.php';

        $currentThaiYear = (int) date('Y') + 543;
        $maxThaiYear = $currentThaiYear + 1;
        $startThaiYear = 2568;
        $dh_year_value = system_get_dh_year();

        if ($dh_year_value < $startThaiYear || $dh_year_value > $maxThaiYear) {
            $dh_year_value = $currentThaiYear;
        }

        $exec_duty_status_labels = [
            0 => '-',
            1 => 'ปฏิบัติราชการ',
            2 => 'รักษาราชการแทน',
        ];
        $exec_duty = system_get_exec_duty();
        $exec_duty_current_pid = (string) ($exec_duty['pID'] ?? '');
        $exec_duty_current_status = (int) ($exec_duty['dutyStatus'] ?? 0);
        $director_pid = system_get_director_pid() ?? '';

        $setting_alert = $_SESSION['setting_alert'] ?? null;
        unset($_SESSION['setting_alert']);

        $system_status_options = [
            1 => 'เปิดใช้งาน ระบบสำนักงานอิเล็กทรอนิกส์',
            2 => 'ปิดปรับปรุง ระบบสำนักงานอิเล็กทรอนิกส์',
            3 => 'ปิดระบบชั่วคราว ระบบสำนักงานอิเล็กทรอนิกส์',
        ];
        $dh_status_value = system_get_dh_status();
        $system_status_label = $system_status_options[$dh_status_value] ?? 'กรุณาเลือกสถานะ';

        $active_tab = $_GET['tab'] ?? 'settingSystem';
        $allowed_tabs = ['settingSystem', 'settingDuty'];

        if (!in_array($active_tab, $allowed_tabs, true)) {
            $active_tab = 'settingSystem';
        }

        view_render('setting/index', [
            'alert' => $setting_alert,
            'active_tab' => $active_tab,
            'teacher_directory' => $teacher_directory ?? [],
            'currentThaiYear' => $currentThaiYear,
            'startThaiYear' => $startThaiYear,
            'maxThaiYear' => $maxThaiYear,
            'dh_year' => $dh_year_value,
            'dh_status' => $dh_status_value,
            'system_status_label' => $system_status_label,
            'system_status_options' => $system_status_options,
            'exec_duty_status_labels' => $exec_duty_status_labels,
            'exec_duty_current_pid' => $exec_duty_current_pid,
            'exec_duty_current_status' => $exec_duty_current_status,
            'director_pid' => $director_pid,
        ]);
    }
}

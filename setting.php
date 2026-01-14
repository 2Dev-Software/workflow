<?php
require_once __DIR__ . '/src/Services/auth/auth-guard.php';
require_once __DIR__ . '/src/Services/security/security-service.php';
require_once __DIR__ . '/src/Services/system/system-year-save.php';
require_once __DIR__ . '/src/Services/system/system-status-save.php';
require_once __DIR__ . '/src/Services/system/exec-duty-save.php';
require_once __DIR__ . '/src/Services/system/system-year.php';
require_once __DIR__ . '/src/Services/system/system-status.php';
$teacher_directory_filter_did = 12;
$teacher_directory_per_page = 'all';
$teacher_directory_query = '';
$teacher_directory_order_by = 'positionID';
require_once __DIR__ . '/src/Services/teacher/teacher-directory.php';
require_once __DIR__ . '/src/Services/system/exec-duty-current.php';

$currentThaiYear = (int) date('Y') + 544;
$startThaiYear = 2568;
$dh_year = $dh_year !== '' ? (int) $dh_year : $currentThaiYear;
if ($dh_year < $startThaiYear || $dh_year > $currentThaiYear) {
    $dh_year = $currentThaiYear;
}

$exec_duty_status_labels = [
    0 => '-',
    1 => 'ปฏิบัติราชการ',
    2 => 'รักษาราชการแทน',
];
$exec_duty_current_pid = $exec_duty_current_pid ?? '';
$exec_duty_current_status = (int) ($exec_duty_current_status ?? 0);

$setting_alert = $_SESSION['setting_alert'] ?? null;
unset($_SESSION['setting_alert']);

$system_status_options = [
    1 => 'เปิดใช้งาน ระบบสำนักงานอิเล็กทรอนิกส์',
    2 => 'ปิดปรับปรุง ระบบสำนักงานอิเล็กทรอนิกส์',
    3 => 'ปิดระบบชั่วคราว ระบบสำนักงานอิเล็กทรอนิกส์',
];
$dh_status = isset($dh_status) ? (int) $dh_status : 1;
$system_status_label = $system_status_options[$dh_status] ?? 'กรุณาเลือกสถานะ';

$active_tab = $_GET['tab'] ?? 'settingSystem';
$allowed_tabs = ['settingSystem', 'settingDuty'];
if (!in_array($active_tab, $allowed_tabs, true)) {
    $active_tab = 'settingSystem';
}
?>
<!DOCTYPE html>
<html lang="th">
<?php require_once __DIR__ . '/public/components/x-head.php'; ?>

<body>

    <?php require_once __DIR__ . '/public/components/layout/preloader.php'; ?>
    <?php if (!empty($setting_alert)) : ?>
        <?php $alert = $setting_alert; ?>
        <?php require __DIR__ . '/public/components/x-alert.php'; ?>
    <?php endif; ?>

    <?php require_once __DIR__ . '/public/components/partials/x-sidebar.php'; ?>

    <section class="main-section">

        <?php require_once __DIR__ . '/public/components/partials/x-navigation.php'; ?>

        <main class="content-wrapper">

            <div class="content-header">
                <h1>ยินดีต้อนรับ</h1>
                <p>การตั้งค่า</p>
            </div>

            <div class="tabs-container setting-page">
                <div class="button-container">
                    <button class="tab-btn<?= $active_tab === 'settingSystem' ? ' active' : '' ?>" onclick="openTab('settingSystem', event)">การตั้งค่าระบบ</button>
                    <button class="tab-btn<?= $active_tab === 'settingDuty' ? ' active' : '' ?>" onclick="openTab('settingDuty', event)">การปฏิบัติราชการของผู้บริหาร</button>
                </div>
            </div>

            <div class="content-area setting-page">

                <div id="settingSystem" class="tab-content<?= $active_tab === 'settingSystem' ? ' active' : '' ?>">

                    <div class="setting-year-container">
                        <form class="setting-year-form" method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="dh_year" id="dhYearInput" value="<?= htmlspecialchars((string) $dh_year, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="dh_year_save" value="1">

                            <p class="setting-title">ปีสารบรรณ</p>

                            <div class="custom-select-setting-wrapper">
                                <div class="custom-setting-select">
                                    <div class="custom-setting-select-trigger">
                                        <p class="select-text">ปีสารบรรณ <?= htmlspecialchars((string) $dh_year, ENT_QUOTES, 'UTF-8') ?></p>
                                        <i class="fa-solid fa-chevron-down"></i>
                                    </div>
                                    <div class="custom-setting-options options-container">
                                        <?php for ($i = $startThaiYear; $i <= $currentThaiYear; $i++) : ?>
                                            <p class="custom-option<?= $dh_year === $i ? ' selected' : '' ?>" data-value="<?= $i ?>">ปีสารบรรณ <?= $i ?></p>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="button-container">
                                <button type="submit" class="btn-save" data-submit="true">บันทึก</button>
                            </div>
                        </form>
                    </div>

                    <div class="setting-year-container">
                        <form class="setting-status-form" method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="dh_status" id="dhStatusInput" value="<?= htmlspecialchars((string) $dh_status, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="dh_status_save" value="1">

                            <label class="setting-title">ตั้งค่าสถานะของระบบ</label>

                            <div class="custom-select-setting-wrapper">
                                <div class="custom-setting-select">
                                    <div class="custom-setting-select-trigger">
                                        <p class="select-text"><?= htmlspecialchars($system_status_label, ENT_QUOTES, 'UTF-8') ?></p>
                                        <i class="fa-solid fa-chevron-down"></i>
                                    </div>

                                    <div class="custom-setting-options options-container">
                                        <?php foreach ($system_status_options as $value => $label) : ?>
                                            <p class="custom-option<?= $dh_status === $value ? ' selected' : '' ?>" data-value="<?= $value ?>">
                                                <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                                            </p>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="button-container">
                                <button type="submit" class="btn-save" data-submit="true">บันทึก</button>
                            </div>
                        </form>
                    </div>

                </div>

                <div id="settingDuty" class="tab-content<?= $active_tab === 'settingDuty' ? ' active' : '' ?>">
                    <div class="setting-duty-header">
                        <p>การปฏิบัติราชการของผู้บริหาร</p>
                    </div>

                    <form class="setting-duty-form" method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="exec_duty_save" value="1">

                        <div class="duty-table-container">
                            <table class="duty-table">
                                <thead>
                                    <tr>
                                        <th>ชื่อจริง-นามสกุล</th>
                                        <th>ตำแหน่ง</th>
                                        <th>สถานะ</th>
                                        <th>จัดการ</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php if (empty($teacher_directory)) : ?>
                                        <tr>
                                            <td colspan="4" style="text-align:center; padding: 20px;">ไม่พบข้อมูล</td>
                                        </tr>
                                    <?php else : ?>
                                        <?php foreach ($teacher_directory as $teacher_item) : ?>
                                            <?php
                                            $teacher_pid = (string) ($teacher_item['pID'] ?? '');
                                            $teacher_position_id = (int) ($teacher_item['positionID'] ?? 0);
                                            $position_display = (string) ($teacher_item['position_name'] ?? '');
                                            if ($teacher_position_id === 1 && $position_display !== '' && strpos($position_display, 'ดีบุกพังงาวิทยายน') === false) {
                                                $position_display .= 'ดีบุกพังงาวิทยายน';
                                            }
                                            $is_current = $teacher_pid !== '' && $teacher_pid === $exec_duty_current_pid;
                                            $current_status = $is_current ? $exec_duty_current_status : 0;
                                            $status_label = $exec_duty_status_labels[$current_status] ?? $exec_duty_status_labels[0];
                                            $status_active = $current_status === 1 || $current_status === 2;
                                            $action_label = $teacher_position_id === 1 ? $exec_duty_status_labels[1] : $exec_duty_status_labels[2];
                                            ?>
                                            <tr>
                                                <td><?= htmlspecialchars($teacher_item['fName'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                                <td><?= htmlspecialchars($position_display, ENT_QUOTES, 'UTF-8') ?></td>
                                                <td>
                                                    <span class="status-badge<?= $status_active ? ' active' : '' ?>">
                                                        <?= htmlspecialchars($status_label, ENT_QUOTES, 'UTF-8') ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <label class="action-check<?= $status_active ? ' active' : '' ?>">
                                                        <input
                                                            type="radio"
                                                            name="exec_duty_pid"
                                                            value="<?= htmlspecialchars($teacher_pid, ENT_QUOTES, 'UTF-8') ?>"
                                                            <?= $status_active ? 'checked' : '' ?>>
                                                        <p><?= htmlspecialchars($action_label, ENT_QUOTES, 'UTF-8') ?></p>
                                                    </label>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="button-container">
                            <button type="submit" class="btn-save btn-save-duty" data-submit="true">บันทึก</button>
                        </div>
                    </form>
                </div>
            </div>


        </main>

        <?php require_once __DIR__ . '/public/components/partials/x-footer.php'; ?>


    </section>


    <?php require_once __DIR__ . '/public/components/x-scripts.php'; ?>
</body>

</html>

<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../auth/csrf.php';

$teacher_directory = (array) ($teacher_directory ?? []);
$currentThaiYear = (int) ($currentThaiYear ?? 0);
$startThaiYear = (int) ($startThaiYear ?? 0);
$maxThaiYear = (int) ($maxThaiYear ?? $currentThaiYear);
$dh_year = (int) ($dh_year ?? 0);
$dh_status = (int) ($dh_status ?? 1);
$system_status_label = (string) ($system_status_label ?? '');
$system_status_options = (array) ($system_status_options ?? []);
$exec_duty_status_labels = (array) ($exec_duty_status_labels ?? []);
$exec_duty_current_pid = (string) ($exec_duty_current_pid ?? '');
$exec_duty_current_status = (int) ($exec_duty_current_status ?? 0);
$active_tab = (string) ($active_tab ?? 'settingSystem');

ob_start();
?>
<div class="content-header">
    <h1>ยินดีต้อนรับ</h1>
    <p>การตั้งค่า</p>
</div>

<div class="tabs-container setting-page">
    <div class="button-container">
        <button class="tab-btn<?= $active_tab === 'settingSystem' ? ' active' : '' ?>" data-tab-target="settingSystem">การตั้งค่าระบบ</button>
        <button class="tab-btn<?= $active_tab === 'settingDuty' ? ' active' : '' ?>" data-tab-target="settingDuty">การปฏิบัติราชการของผู้บริหาร</button>
    </div>
</div>

<div class="content-area setting-page">
    <div id="settingSystem" class="tab-content<?= $active_tab === 'settingSystem' ? ' active' : '' ?>">
        <div class="setting-year-container">
            <form class="setting-year-form" method="POST" action="<?= h($_SERVER['PHP_SELF'] ?? '') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="dh_year" id="dhYearInput" value="<?= h((string) $dh_year) ?>">
                <input type="hidden" name="dh_year_save" value="1">

                <p class="setting-title">ปีสารบรรณ</p>

                <div class="custom-select-setting-wrapper">
                    <div class="custom-setting-select">
                        <div class="custom-setting-select-trigger">
                            <p class="select-text">ปีสารบรรณ <?= h((string) $dh_year) ?></p>
                            <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                        </div>
                        <div class="custom-setting-options options-container">
                            <?php for ($i = $startThaiYear; $i <= $maxThaiYear; $i++) : ?>
                                <p class="custom-option<?= $dh_year === $i ? ' selected' : '' ?>" data-value="<?= h((string) $i) ?>">ปีสารบรรณ <?= h((string) $i) ?></p>
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
            <form class="setting-status-form" method="POST" action="<?= h($_SERVER['PHP_SELF'] ?? '') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="dh_status" id="dhStatusInput" value="<?= h((string) $dh_status) ?>">
                <input type="hidden" name="dh_status_save" value="1">

                <label class="setting-title">ตั้งค่าสถานะของระบบ</label>

                <div class="custom-select-setting-wrapper">
                    <div class="custom-setting-select">
                        <div class="custom-setting-select-trigger">
                            <p class="select-text"><?= h($system_status_label) ?></p>
                            <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                        </div>

                        <div class="custom-setting-options options-container">
                            <?php foreach ($system_status_options as $value => $label) : ?>
                                <p class="custom-option<?= $dh_status === (int) $value ? ' selected' : '' ?>" data-value="<?= h((string) $value) ?>">
                                    <?= h((string) $label) ?>
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

        <form class="setting-duty-form" method="POST" action="<?= h($_SERVER['PHP_SELF'] ?? '') ?>">
            <?= csrf_field() ?>
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
                                <td colspan="4" class="enterprise-empty">ไม่พบข้อมูล</td>
                            </tr>
                        <?php else : ?>
                            <?php foreach ($teacher_directory as $teacher_item) : ?>
                                <?php
                                $teacher_pid = (string) ($teacher_item['pID'] ?? '');
                                $director_pid = (string) ($director_pid ?? '');
                                $position_display = (string) ($teacher_item['position_name'] ?? '');
                                $is_director = $teacher_pid !== '' && $teacher_pid === $director_pid;

                                if ($is_director && $position_display !== '' && strpos($position_display, 'ดีบุกพังงาวิทยายน') === false) {
                                    $position_display .= 'ดีบุกพังงาวิทยายน';
                                }
                                $is_current = $teacher_pid !== '' && $teacher_pid === $exec_duty_current_pid;
                                $current_status = $is_current ? $exec_duty_current_status : 0;
                                $status_label = $exec_duty_status_labels[$current_status] ?? ($exec_duty_status_labels[0] ?? '-');
                                $status_active = $current_status === 1 || $current_status === 2;
                                $action_label = $is_director
                                    ? ($exec_duty_status_labels[1] ?? 'ปฏิบัติราชการ')
                                    : ($exec_duty_status_labels[2] ?? 'รักษาราชการแทน');
                                ?>
                                <tr>
                                    <td><?= h($teacher_item['fName'] ?? '') ?></td>
                                    <td><?= h($position_display) ?></td>
                                    <td>
                                        <span class="status-badge<?= $status_active ? ' active' : '' ?>">
                                            <?= h($status_label) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <label class="action-check<?= $status_active ? ' active' : '' ?>">
                                            <input
                                                type="radio"
                                                name="exec_duty_pid"
                                                value="<?= h($teacher_pid) ?>"
                                                <?= $status_active ? 'checked' : '' ?>>
                                            <p><?= h($action_label) ?></p>
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
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';

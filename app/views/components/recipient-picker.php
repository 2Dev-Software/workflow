<?php
$params = $params ?? [];
$factions = (array) ($params['factions'] ?? []);
$roles = (array) ($params['roles'] ?? []);
$teachers = (array) ($params['teachers'] ?? []);
$selected_faction_ids = array_map('strval', (array) ($params['selected_faction_ids'] ?? []));
$selected_role_ids = array_map('strval', (array) ($params['selected_role_ids'] ?? []));
$selected_person_ids = array_map('strval', (array) ($params['selected_person_ids'] ?? []));
$faction_member_map = (array) ($params['faction_member_map'] ?? []);
$role_member_map = (array) ($params['role_member_map'] ?? []);

$is_selected = static function (string $value, array $selected): bool {
    return in_array($value, $selected, true);
};
$to_lower = static function (string $value): string {
    return function_exists('mb_strtolower')
        ? (string) mb_strtolower($value, 'UTF-8')
        : strtolower($value);
};
?>
<div class="recipient-picker" data-recipient-picker>
    <div class="recipient-picker-grid">
        <section class="recipient-picker-section" data-recipient-group="faction">
            <header class="recipient-picker-header">
                <h3>เลือกตามกลุ่ม/ฝ่าย</h3>
                <input type="search" class="form-input c-input recipient-picker-search" placeholder="ค้นหากลุ่ม/ฝ่าย" data-recipient-search>
            </header>
            <div class="recipient-picker-list" data-recipient-list>
                <?php if (empty($factions)) : ?>
                    <p class="recipient-picker-empty">ไม่พบข้อมูลกลุ่ม/ฝ่าย</p>
                <?php else : ?>
                    <?php foreach ($factions as $faction) : ?>
                        <?php
                        $fid = (string) ((int) ($faction['fID'] ?? 0));
                        $name = trim((string) ($faction['fName'] ?? ''));
                        $member_ids = array_values(array_filter(array_map('strval', (array) ($faction_member_map[$fid] ?? []))));
                        $member_attr = implode(',', $member_ids);
                        ?>
                        <?php if ($fid === '0' || $name === '') {
                            continue;
                        } ?>
                        <label class="recipient-picker-item" data-recipient-item data-search="<?= h($to_lower($name)) ?>">
                            <input
                                type="checkbox"
                                name="faction_ids[]"
                                value="<?= h($fid) ?>"
                                data-recipient-option="faction"
                                data-member-pids="<?= h($member_attr) ?>"
                                <?= $is_selected($fid, $selected_faction_ids) ? 'checked' : '' ?>>
                            <span><?= h($name) ?></span>
                        </label>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <section class="recipient-picker-section" data-recipient-group="role">
            <header class="recipient-picker-header">
                <h3>เลือกตามบทบาท</h3>
                <input type="search" class="form-input c-input recipient-picker-search" placeholder="ค้นหาบทบาท" data-recipient-search>
            </header>
            <div class="recipient-picker-list" data-recipient-list>
                <?php if (empty($roles)) : ?>
                    <p class="recipient-picker-empty">ไม่พบบทบาท</p>
                <?php else : ?>
                    <?php foreach ($roles as $role) : ?>
                        <?php
                        $rid = (string) ((int) ($role['roleID'] ?? 0));
                        $name = trim((string) ($role['roleName'] ?? ''));
                        $member_ids = array_values(array_filter(array_map('strval', (array) ($role_member_map[$rid] ?? []))));
                        $member_attr = implode(',', $member_ids);
                        ?>
                        <?php if ($rid === '0' || $name === '') {
                            continue;
                        } ?>
                        <label class="recipient-picker-item" data-recipient-item data-search="<?= h($to_lower($name)) ?>">
                            <input
                                type="checkbox"
                                name="role_ids[]"
                                value="<?= h($rid) ?>"
                                data-recipient-option="role"
                                data-member-pids="<?= h($member_attr) ?>"
                                <?= $is_selected($rid, $selected_role_ids) ? 'checked' : '' ?>>
                            <span><?= h($name) ?></span>
                        </label>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <section class="recipient-picker-section" data-recipient-group="person">
            <header class="recipient-picker-header">
                <h3>เลือกตามบุคคล</h3>
                <input type="search" class="form-input c-input recipient-picker-search" placeholder="ค้นหาชื่อบุคลากร" data-recipient-search>
            </header>
            <div class="recipient-picker-list" data-recipient-list>
                <?php if (empty($teachers)) : ?>
                    <p class="recipient-picker-empty">ไม่พบบุคลากร</p>
                <?php else : ?>
                    <?php foreach ($teachers as $teacher) : ?>
                        <?php
                        $pid = trim((string) ($teacher['pID'] ?? ''));
                        $name = trim((string) ($teacher['fName'] ?? ''));
                        $faction_name = trim((string) ($teacher['factionName'] ?? ''));
                        $search_text = $to_lower(trim($name . ' ' . $faction_name . ' ' . $pid));
                        ?>
                        <?php if ($pid === '' || $name === '') {
                            continue;
                        } ?>
                        <label class="recipient-picker-item" data-recipient-item data-search="<?= h($search_text) ?>">
                            <input
                                type="checkbox"
                                name="person_ids[]"
                                value="<?= h($pid) ?>"
                                data-recipient-option="person"
                                data-member-pids="<?= h($pid) ?>"
                                <?= $is_selected($pid, $selected_person_ids) ? 'checked' : '' ?>>
                            <span><?= h($name) ?></span>
                            <?php if ($faction_name !== '') : ?>
                                <small><?= h($faction_name) ?></small>
                            <?php endif; ?>
                        </label>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>

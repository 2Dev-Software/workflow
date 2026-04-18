<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../auth/csrf.php';

$base_url = (string) ($base_url ?? 'repairs-management.php');
$page_title = (string) ($page_title ?? 'ยินดีต้อนรับ');
$page_subtitle = (string) ($page_subtitle ?? 'แจ้งเหตุซ่อมแซม / จัดการงานซ่อม');
$repair_staff_members = (array) ($repair_staff_members ?? []);
$repair_candidate_members = (array) ($repair_candidate_members ?? []);
$repair_staff_count = (int) ($repair_staff_count ?? count($repair_staff_members));
$repair_candidate_count = (int) ($repair_candidate_count ?? count($repair_candidate_members));

ob_start();
?>
<div class="content-header">
    <h1><?= h($page_title) ?></h1>
    <p><?= h($page_subtitle) ?></p>
</div>

<section class="booking-card booking-list-card room-admin-card repair-management-card">
    <div class="booking-card-header">
        <div class="booking-card-title-group">
            <h2 class="booking-card-title">ทีมผู้ดูแลงานซ่อมแซม</h2>
        </div>
        <div>
            <div class="room-admin-member-count">ทั้งหมด <?= h((string) $repair_staff_count) ?> คน</div>
            <button type="button" class="btn-outline" data-repair-modal-open="repairMemberModal">เพิ่มสมาชิก</button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="custom-table booking-table room-admin-member-table">
            <thead>
                <tr>
                    <th>ชื่อ-สกุล</th>
                    <th>ตำแหน่ง</th>
                    <th>บทบาท</th>
                    <th>สถานะ</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($repair_staff_members)) : ?>
                    <tr>
                        <td colspan="5" class="booking-empty">ยังไม่มีเจ้าหน้าที่ซ่อมแซม</td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($repair_staff_members as $member) : ?>
                        <?php
                        $member_pid = trim((string) ($member['pID'] ?? ''));
                        $member_name = trim((string) ($member['name'] ?? ''));
                        $member_position = trim((string) ($member['position_name'] ?? ''));
                        $member_department = trim((string) ($member['department_name'] ?? ''));
                        ?>
                        <tr>
                            <td><strong><?= h($member_name !== '' ? $member_name : 'ไม่ระบุชื่อ') ?></strong></td>
                            <td>
                                <div class="room-admin-member-position"><?= h($member_position !== '' ? $member_position : 'เจ้าหน้าที่ซ่อมแซม') ?></div>
                                <?php if ($member_department !== '') : ?>
                                    <div class="room-admin-member-subtext"><?= h($member_department) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><span class="room-admin-member-tag">เจ้าหน้าที่ซ่อมแซม</span></td>
                            <td><span class="member-status-pill">กำลังปฏิบัติหน้าที่</span></td>
                            <td>
                                <div class="booking-action-group">
                                    <form class="booking-action-form" data-member-remove-form method="POST" action="<?= h($base_url) ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="member_action" value="remove">
                                        <input type="hidden" name="member_pid" value="<?= h($member_pid) ?>">
                                        <button
                                            type="submit"
                                            class="booking-action-btn danger"
                                            data-confirm="ยืนยันการนำบุคลากรนี้ออกจากเจ้าหน้าที่ซ่อมแซมใช่หรือไม่?"
                                            data-confirm-title="ยืนยันการลบสมาชิก"
                                            data-confirm-ok="ยืนยัน"
                                            data-confirm-cancel="ยกเลิก">
                                            <i class="fa-solid fa-trash"></i>
                                            <span class="tooltip danger">นำออกจากบทบาท</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<div id="repairMemberModal" class="modal-overlay hidden">
    <div class="modal-content room-admin-modal room-admin-member-modal">
        <header class="modal-header">
            <div class="modal-title">
                <span>เพิ่มสมาชิกทีมผู้ดูแล</span>
            </div>
            <div class="close-modal-btn" data-repair-modal-close="repairMemberModal">
                <i class="fa-solid fa-xmark"></i>
            </div>
        </header>

        <div class="modal-body room-admin-modal-body">
            <form class="room-admin-search-form" data-member-search-form>
                <div class="room-admin-search">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <input class="form-input" type="search" placeholder="ค้นหาบุคลากร" autocomplete="off" data-member-search>
                </div>
            </form>

            <div class="room-admin-member-count" data-member-count>
                ทั้งหมด <?= h((string) $repair_candidate_count) ?> คน
            </div>

            <div class="table-responsive">
                <table class="custom-table booking-table room-admin-member-table">
                    <thead>
                        <tr>
                            <th>ชื่อ-สกุล</th>
                            <th>กลุ่มสาระฯ</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $candidate_seen = [];

                        foreach ($repair_candidate_members as $candidate) :
                            $candidate_pid = trim((string) ($candidate['pID'] ?? ''));

                            if ($candidate_pid === '' || isset($candidate_seen[$candidate_pid])) {
                                continue;
                            }

                            $candidate_seen[$candidate_pid] = true;
                            $candidate_name = trim((string) ($candidate['name'] ?? ''));
                            $candidate_name = $candidate_name !== '' ? $candidate_name : 'ไม่ระบุชื่อ';
                            $candidate_department = trim((string) ($candidate['department_name'] ?? ''));
                            $candidate_department = $candidate_department !== '' ? $candidate_department : '-';
                            $candidate_position = trim((string) ($candidate['position_name'] ?? ''));
                            $candidate_tel = trim((string) ($candidate['telephone'] ?? ''));
                            $member_search = trim(implode(' ', array_filter([
                                $candidate_pid,
                                $candidate_name,
                                $candidate_department,
                                $candidate_position,
                                $candidate_tel,
                            ])));
                        ?>
                            <tr data-member-row data-member-search="<?= h($member_search) ?>">
                                <td>
                                    <strong><?= h($candidate_name) ?></strong>
                                    <?php if ($candidate_position !== '') : ?>
                                        <div class="detail-subtext"><?= h($candidate_position) ?></div>
                                    <?php endif; ?>
                                    <?php if ($candidate_tel !== '') : ?>
                                        <div class="detail-subtext">โทร <?= h($candidate_tel) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><span><?= h($candidate_department) ?></span></td>
                                <td>
                                    <div class="booking-action-group">
                                        <form class="booking-action-form" method="POST" action="<?= h($base_url) ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="member_action" value="add">
                                            <input type="hidden" name="member_pid" value="<?= h($candidate_pid) ?>">
                                            <button
                                                type="submit"
                                                class="booking-action-btn add"
                                                data-member-add-btn
                                                data-confirm="ยืนยันการเพิ่มบุคลากรนี้เป็นเจ้าหน้าที่ซ่อมแซมใช่หรือไม่?"
                                                data-confirm-title="ยืนยันการเพิ่มสมาชิก"
                                                data-confirm-ok="ยืนยัน"
                                                data-confirm-cancel="ยกเลิก">
                                                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                                                <span class="tooltip">เพิ่มสมาชิก</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <tr class="booking-empty <?= empty($repair_candidate_members) ? '' : 'hidden' ?>" data-member-empty>
                            <td colspan="3">ไม่พบบุคลากรที่สามารถเพิ่มได้</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const repairModal = document.getElementById('repairMemberModal');

        if (!repairModal) return;

        const openButtons = document.querySelectorAll('[data-repair-modal-open="repairMemberModal"]');
        const closeButtons = repairModal.querySelectorAll('.close-modal-btn, [data-repair-modal-close]');
        const searchInput = repairModal.querySelector('[data-member-search]');
        const memberRows = Array.from(repairModal.querySelectorAll('[data-member-row]'));
        const emptyRow = repairModal.querySelector('[data-member-empty]');
        const memberCount = repairModal.querySelector('[data-member-count]');

        openButtons.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                repairModal.classList.remove('hidden');
            });
        });

        const closeModal = () => {
            repairModal.classList.add('hidden');
        };

        closeButtons.forEach(btn => {
            btn.addEventListener('click', closeModal);
        });

        repairModal.addEventListener('click', function(event) {
            if (event.target === repairModal) {
                closeModal();
            }
        });

        const filterMembers = () => {
            const query = (searchInput ? searchInput.value : '').trim().toLowerCase();
            let visibleCount = 0;

            memberRows.forEach(row => {
                const haystack = (row.getAttribute('data-member-search') || '').toLowerCase();
                const matched = query === '' || haystack.includes(query);
                row.classList.toggle('hidden', !matched);

                if (matched) {
                    visibleCount += 1;
                }
            });

            if (emptyRow) {
                emptyRow.classList.toggle('hidden', visibleCount > 0);
            }

            if (memberCount) {
                memberCount.textContent = 'ทั้งหมด ' + visibleCount + ' คน';
            }
        };

        if (searchInput) {
            searchInput.addEventListener('input', filterMembers);
            filterMembers();
        }
    });
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';

<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../auth/csrf.php';

$items = (array) ($items ?? []);
$can_manage = (bool) ($can_manage ?? ($is_registry ?? false));
$search = trim((string) ($search ?? ''));
$status_filter = strtoupper(trim((string) ($status_filter ?? 'ALL')));
$summary_counts = (array) ($summary_counts ?? []);
$attachments_map = (array) ($attachments_map ?? []);

$summary_total = (int) ($summary_counts['all'] ?? count($items));
$summary_waiting = (int) ($summary_counts[OUTGOING_STATUS_WAITING_ATTACHMENT] ?? 0);
$summary_complete = (int) ($summary_counts[OUTGOING_STATUS_COMPLETE] ?? 0);

$status_options = [
    'ALL' => 'ทั้งหมด',
    OUTGOING_STATUS_WAITING_ATTACHMENT => 'รอแนบไฟล์',
    OUTGOING_STATUS_COMPLETE => 'สมบูรณ์',
];

$thai_months = [
    1 => 'มกราคม',
    2 => 'กุมภาพันธ์',
    3 => 'มีนาคม',
    4 => 'เมษายน',
    5 => 'พฤษภาคม',
    6 => 'มิถุนายน',
    7 => 'กรกฎาคม',
    8 => 'สิงหาคม',
    9 => 'กันยายน',
    10 => 'ตุลาคม',
    11 => 'พฤศจิกายน',
    12 => 'ธันวาคม',
];

$format_thai_datetime_parts = static function (?string $datetime) use ($thai_months): array {
    $datetime = trim((string) $datetime);

    if ($datetime === '' || strpos($datetime, '0000-00-00') === 0) {
        return ['date' => '-', 'time' => '-'];
    }

    $date_obj = DateTime::createFromFormat('Y-m-d H:i:s', $datetime);

    if ($date_obj === false) {
        $date_obj = DateTime::createFromFormat('Y-m-d H:i', $datetime);
    }

    if ($date_obj === false) {
        return ['date' => $datetime, 'time' => '-'];
    }

    $day = (int) $date_obj->format('j');
    $month = (int) $date_obj->format('n');
    $year = (int) $date_obj->format('Y') + 543;
    $month_label = $thai_months[$month] ?? '';

    return [
        'date' => trim($day . ' ' . $month_label . ' ' . $year),
        'time' => $date_obj->format('H:i') . ' น.',
    ];
};

ob_start();
?>
<div class="content-header">
    <h1>ยินดีต้อนรับ</h1>
    <p>หนังสือออกภายนอก / ทะเบียนหนังสือออก</p>
</div>

<div class="content-area room-admin-page outgoing-register-page">
    <section class="booking-card booking-list-card room-admin-card">
        <div class="booking-card-header">
            <div class="booking-card-title-group">
                <h2 class="booking-card-title">ทะเบียนหนังสือออก</h2>
                <p class="booking-card-subtitle">รายการหนังสือส่งออกทั้งหมดของระบบ</p>
            </div>
            <div>
                <div class="room-admin-member-count">ทั้งหมด <?= h((string) $summary_total) ?> รายการ</div>
                <div class="room-admin-member-count">รอแนบไฟล์ <?= h((string) $summary_waiting) ?> รายการ</div>
                <div class="room-admin-member-count">สมบูรณ์ <?= h((string) $summary_complete) ?> รายการ</div>
            </div>
        </div>

        <div class="booking-card-header">
            <form class="room-admin-actions" method="get" action="outgoing.php">
                <div class="room-admin-search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input
                        class="form-input"
                        type="search"
                        name="q"
                        value="<?= h($search) ?>"
                        placeholder="ค้นหาเลขที่หนังสือออก หรือ เรื่อง"
                        autocomplete="off">
                </div>
                <div class="room-admin-filter">
                    <select class="form-input" name="status">
                        <?php foreach ($status_options as $value => $label) : ?>
                            <option value="<?= h($value) ?>" <?= $status_filter === $value ? 'selected' : '' ?>>
                                <?= h($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php component_render('button', [
                    'label' => 'ค้นหา',
                    'variant' => 'secondary',
                    'type' => 'submit',
                    'class' => 'c-button--sm',
                ]); ?>
                <?php component_render('button', [
                    'label' => 'ออกเลขหนังสือภายนอก',
                    'variant' => 'primary',
                    'href' => 'outgoing-create.php',
                ]); ?>
            </form>
        </div>

        <div class="table-responsive">
            <table class="custom-table booking-table room-admin-table">
                <thead>
                    <tr>
                        <th>เลขที่หนังสือออก</th>
                        <th>เรื่อง</th>
                        <th>ผู้ลงรายการ</th>
                        <th>วันที่ออกเลข</th>
                        <th>สถานะ</th>
                        <th>ไฟล์แนบ</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)) : ?>
                        <tr class="booking-empty">
                            <td colspan="7">ไม่พบรายการหนังสือออก</td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($items as $item) : ?>
                            <?php
                            $outgoing_id = (int) ($item['outgoingID'] ?? 0);
                            $outgoing_no = trim((string) ($item['outgoingNo'] ?? ''));
                            $subject = trim((string) ($item['subject'] ?? ''));
                            $creator_name = trim((string) ($item['creatorName'] ?? ''));
                            $status = (string) ($item['status'] ?? '');
                            $is_waiting = $status === OUTGOING_STATUS_WAITING_ATTACHMENT;
                            $status_label = $is_waiting ? 'รอแนบไฟล์' : 'สมบูรณ์';
                            $status_variant = $is_waiting ? 'pending' : 'approved';
                            $attachment_count = (int) ($item['attachmentCount'] ?? 0);
                            $attachments = (array) ($attachments_map[(string) $outgoing_id] ?? []);
                            $display_datetime = (string) ($item['updatedAt'] ?? '');

                            if ($display_datetime === '' || strpos($display_datetime, '0000-00-00') === 0) {
                                $display_datetime = (string) ($item['createdAt'] ?? '');
                            }

                            $datetime_parts = $format_thai_datetime_parts($display_datetime);
                            $year = trim((string) ($item['dh_year'] ?? ''));
                            $seq = (int) ($item['outgoingSeq'] ?? 0);
                            ?>
                            <tr>
                                <td>
                                    <div class="room-admin-room-name">
                                        <?= h($outgoing_no !== '' ? $outgoing_no : ('OUT-' . $outgoing_id)) ?>
                                    </div>
                                    <span class="detail-subtext">
                                        <?= h($seq > 0 && $year !== '' ? ('ลำดับ ' . $seq . ' / ปี ' . $year) : 'ยังไม่มีข้อมูลลำดับ') ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="room-admin-room-name">
                                        <?= h($subject !== '' ? $subject : 'ไม่ระบุเรื่อง') ?>
                                    </div>
                                </td>
                                <td><?= h($creator_name !== '' ? $creator_name : '-') ?></td>
                                <td>
                                    <div class="room-admin-room-name">
                                        <?= h($datetime_parts['date']) ?>
                                    </div>
                                    <span class="detail-subtext"><?= h($datetime_parts['time']) ?></span>
                                </td>
                                <td>
                                    <?php component_render('status-pill', [
                                        'label' => $status_label,
                                        'variant' => $status_variant,
                                    ]); ?>
                                </td>
                                <td>
                                    <?php if (empty($attachments)) : ?>
                                        <span class="detail-subtext">
                                            <?= h($attachment_count > 0 ? ('พบไฟล์ ' . $attachment_count . ' รายการ') : 'ยังไม่แนบไฟล์') ?>
                                        </span>
                                    <?php else : ?>
                                        <div class="room-admin-room-name"><?= h((string) count($attachments)) ?> ไฟล์</div>
                                        <?php foreach ($attachments as $attachment) : ?>
                                            <?php
                                            $file_id = (int) ($attachment['fileID'] ?? 0);
                                            $file_name = trim((string) ($attachment['fileName'] ?? ''));

                                            if ($file_id <= 0 || $file_name === '') {
                                                continue;
                                            }
                                            ?>
                                            <div>
                                                <a
                                                    class="c-link"
                                                    href="public/api/file-download.php?module=outgoing&entity_id=<?= h((string) $outgoing_id) ?>&file_id=<?= h((string) $file_id) ?>"
                                                    target="_blank"
                                                    rel="noopener"><?= h($file_name) ?></a>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </td>
                                <td class="room-admin-actions-cell">
                                    <?php component_render('outgoing-attach-form', [
                                        'outgoing_id' => $outgoing_id,
                                        'enabled' => $is_waiting && $can_manage,
                                        'locked' => !$can_manage,
                                    ]); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';

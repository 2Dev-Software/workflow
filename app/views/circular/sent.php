<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../auth/csrf.php';

$sent_items = (array) ($sent_items ?? []);
$receipt_circular_id = (int) ($receipt_circular_id ?? 0);
$receipt_subject = (string) ($receipt_subject ?? '');
$receipt_sender_faction = (string) ($receipt_sender_faction ?? '');
$receipt_stats = (array) ($receipt_stats ?? []);

$filter_query = (string) ($filter_query ?? '');
$filter_type = (string) ($filter_type ?? 'all');
$filter_status = (string) ($filter_status ?? 'all');
$filter_sort = (string) ($filter_sort ?? 'newest');
$page = (int) ($page ?? 1);
$per_page = (int) ($per_page ?? 10);
$total_pages = (int) ($total_pages ?? 1);
$filtered_total = (int) ($filtered_total ?? count($sent_items));

$summary_total = (int) ($summary_total ?? 0);
$summary_sent = (int) ($summary_sent ?? 0);
$summary_recalled = (int) ($summary_recalled ?? 0);
$summary_read_complete = (int) ($summary_read_complete ?? 0);

$query_params = (array) ($query_params ?? []);

$status_map = [
    INTERNAL_STATUS_DRAFT => ['label' => 'ร่าง', 'pill' => 'pending'],
    INTERNAL_STATUS_SENT => ['label' => 'ส่งแล้ว', 'pill' => 'approved'],
    INTERNAL_STATUS_RECALLED => ['label' => 'ดึงกลับ', 'pill' => 'rejected'],
    INTERNAL_STATUS_ARCHIVED => ['label' => 'จัดเก็บ', 'pill' => 'approved'],
    EXTERNAL_STATUS_SUBMITTED => ['label' => 'รับเข้าแล้ว', 'pill' => 'pending'],
    EXTERNAL_STATUS_PENDING_REVIEW => ['label' => 'รอพิจารณา', 'pill' => 'pending'],
    EXTERNAL_STATUS_REVIEWED => ['label' => 'พิจารณาแล้ว', 'pill' => 'approved'],
    EXTERNAL_STATUS_FORWARDED => ['label' => 'ส่งแล้ว', 'pill' => 'approved'],
];

$type_map = [
    'INTERNAL' => 'ภายใน',
    'EXTERNAL' => 'ภายนอก',
];

$thai_months = [
    1 => 'ม.ค.',
    2 => 'ก.พ.',
    3 => 'มี.ค.',
    4 => 'เม.ย.',
    5 => 'พ.ค.',
    6 => 'มิ.ย.',
    7 => 'ก.ค.',
    8 => 'ส.ค.',
    9 => 'ก.ย.',
    10 => 'ต.ค.',
    11 => 'พ.ย.',
    12 => 'ธ.ค.',
];

$format_thai_datetime = static function (?string $date_value) use ($thai_months): string {
    if ($date_value === null || trim($date_value) === '') {
        return '-';
    }

    $timestamp = strtotime($date_value);
    if ($timestamp === false) {
        return $date_value;
    }

    $day = (int) date('j', $timestamp);
    $month = (int) date('n', $timestamp);
    $year = (int) date('Y', $timestamp) + 543;
    $month_label = $thai_months[$month] ?? '';

    return $day . ' ' . $month_label . ' ' . $year . ' ' . date('H:i', $timestamp) . ' น.';
};

$build_url = static function (array $override = []) use ($query_params): string {
    $params = array_merge($query_params, $override);
    foreach ($params as $key => $value) {
        if ($value === null || $value === '') {
            unset($params[$key]);
        }
    }

    $query = http_build_query($params);
    return 'circular-sent.php' . ($query !== '' ? ('?' . $query) : '');
};

$receipt_total = count($receipt_stats);
$receipt_read = 0;
foreach ($receipt_stats as $stat) {
    if ((int) ($stat['isRead'] ?? 0) === 1) {
        $receipt_read++;
    }
}
$receipt_unread = max(0, $receipt_total - $receipt_read);

ob_start();
?>
<div class="content-header">
    <h1>หนังสือเวียนของฉัน</h1>
    <p>ติดตามการส่ง ดึงกลับ และสถานะการอ่านของผู้รับ</p>
</div>

<style>
    .circular-my-summary-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
    }

    .circular-my-summary-card {
        border: 1px solid rgba(var(--rgb-secondary), 0.16);
        border-radius: 12px;
        padding: 12px 14px;
        background: linear-gradient(180deg, #fbfdff 0%, #f3f7ff 100%);
    }

    .circular-my-summary-card p {
        margin: 0;
        font-size: var(--font-size-desc-2);
        color: var(--color-neutral-medium);
        font-weight: 600;
    }

    .circular-my-summary-card h3 {
        margin: 4px 0 0;
        font-size: 24px;
        line-height: 1.1;
        color: var(--color-secondary);
    }

    .circular-my-filter-grid {
        display: grid;
        grid-template-columns: 1.5fr 0.7fr 0.8fr 0.8fr 0.6fr auto auto;
        gap: 10px;
        align-items: end;
    }

    .circular-my-filter-field {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .circular-my-filter-field label {
        margin: 0;
        font-size: var(--font-size-desc-3);
        color: var(--color-neutral-medium);
        font-weight: 700;
    }

    .circular-my-table-wrap {
        margin-top: 8px;
    }

    .circular-my-table td {
        vertical-align: top;
    }

    .circular-my-subject {
        min-width: 260px;
        max-width: 380px;
        font-weight: 700;
        color: var(--color-secondary);
        line-height: 1.45;
        word-break: break-word;
    }

    .circular-my-meta {
        color: var(--color-neutral-medium);
        font-size: var(--font-size-desc-2);
        margin-top: 2px;
    }

    .circular-my-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        min-width: 220px;
    }

    .circular-my-actions form {
        margin: 0;
    }

    .circular-my-actions .btn,
    .circular-my-actions .c-button {
        height: 34px;
        padding: 0 12px;
        min-width: auto;
    }

    .circular-my-receipt-summary {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 8px;
    }

    .circular-my-receipt-chip {
        display: inline-flex;
        align-items: center;
        padding: 6px 12px;
        border-radius: 999px;
        font-size: var(--font-size-desc-2);
        font-weight: 700;
        border: 1px solid rgba(var(--rgb-secondary), 0.18);
        background-color: var(--color-neutral-lightest);
        color: var(--color-secondary);
    }

    .circular-my-receipt-chip.is-read {
        border-color: var(--color-success);
        color: var(--color-success);
        background-color: rgba(var(--rgb-success), 0.12);
    }

    .circular-my-receipt-chip.is-unread {
        border-color: var(--color-warning);
        color: var(--color-warning);
        background-color: rgba(var(--rgb-warning), 0.14);
    }

    .circular-my-receipt-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 10px;
    }

    .circular-my-receipt-subtitle {
        margin: 6px 0 0;
        color: var(--color-neutral-medium);
        font-size: var(--font-size-desc-2);
    }

    @media (max-width: 1280px) {
        .circular-my-filter-grid {
            grid-template-columns: 1fr 1fr 1fr 1fr;
        }

        .circular-my-filter-grid .circular-my-filter-actions {
            grid-column: span 2;
        }
    }

    @media (max-width: 900px) {
        .circular-my-summary-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .circular-my-filter-grid {
            grid-template-columns: 1fr;
        }

        .circular-my-filter-grid .circular-my-filter-actions {
            grid-column: span 1;
        }
    }
</style>

<section class="enterprise-card">
    <div class="enterprise-card-header">
        <div class="enterprise-card-title-group">
            <h2 class="enterprise-card-title">สรุปภาพรวม</h2>
            <p class="enterprise-card-subtitle">ข้อมูลหนังสือเวียนที่คุณเป็นผู้ส่ง</p>
        </div>
    </div>
    <div class="circular-my-summary-grid">
        <div class="circular-my-summary-card">
            <p>ทั้งหมด</p>
            <h3><?= h((string) $summary_total) ?></h3>
        </div>
        <div class="circular-my-summary-card">
            <p>ส่งแล้ว</p>
            <h3><?= h((string) $summary_sent) ?></h3>
        </div>
        <div class="circular-my-summary-card">
            <p>ดึงกลับ</p>
            <h3><?= h((string) $summary_recalled) ?></h3>
        </div>
        <div class="circular-my-summary-card">
            <p>อ่านครบทุกผู้รับ</p>
            <h3><?= h((string) $summary_read_complete) ?></h3>
        </div>
    </div>
</section>

<section class="enterprise-card">
    <div class="enterprise-card-header">
        <div class="enterprise-card-title-group">
            <h2 class="enterprise-card-title">ค้นหาและกรองรายการ</h2>
            <p class="enterprise-card-subtitle">ปรับมุมมองให้ตรงรายการที่ต้องการติดตาม</p>
        </div>
    </div>

    <form method="GET" class="circular-my-filter-grid">
        <div class="circular-my-filter-field">
            <label for="circularQ">ค้นหา</label>
            <input class="form-input" id="circularQ" type="text" name="q" value="<?= h($filter_query) ?>" placeholder="ค้นหาจากเลขที่หรือหัวเรื่อง">
        </div>

        <div class="circular-my-filter-field">
            <label for="circularType">ประเภท</label>
            <select class="form-input" id="circularType" name="type">
                <option value="all"<?= $filter_type === 'all' ? ' selected' : '' ?>>ทั้งหมด</option>
                <option value="internal"<?= $filter_type === 'internal' ? ' selected' : '' ?>>ภายใน</option>
                <option value="external"<?= $filter_type === 'external' ? ' selected' : '' ?>>ภายนอก</option>
            </select>
        </div>

        <div class="circular-my-filter-field">
            <label for="circularStatus">สถานะ</label>
            <select class="form-input" id="circularStatus" name="status">
                <option value="all"<?= $filter_status === 'all' ? ' selected' : '' ?>>ทั้งหมด</option>
                <option value="<?= h(strtolower(INTERNAL_STATUS_SENT)) ?>"<?= $filter_status === strtolower(INTERNAL_STATUS_SENT) ? ' selected' : '' ?>>ส่งแล้ว</option>
                <option value="<?= h(strtolower(INTERNAL_STATUS_RECALLED)) ?>"<?= $filter_status === strtolower(INTERNAL_STATUS_RECALLED) ? ' selected' : '' ?>>ดึงกลับ</option>
                <option value="<?= h(strtolower(INTERNAL_STATUS_ARCHIVED)) ?>"<?= $filter_status === strtolower(INTERNAL_STATUS_ARCHIVED) ? ' selected' : '' ?>>จัดเก็บ</option>
            </select>
        </div>

        <div class="circular-my-filter-field">
            <label for="circularSort">เรียงตาม</label>
            <select class="form-input" id="circularSort" name="sort">
                <option value="newest"<?= $filter_sort === 'newest' ? ' selected' : '' ?>>ใหม่ไปเก่า</option>
                <option value="oldest"<?= $filter_sort === 'oldest' ? ' selected' : '' ?>>เก่าไปใหม่</option>
            </select>
        </div>

        <div class="circular-my-filter-field">
            <label for="circularPerPage">จำนวน/หน้า</label>
            <select class="form-input" id="circularPerPage" name="per_page">
                <option value="10"<?= $per_page === 10 ? ' selected' : '' ?>>10</option>
                <option value="20"<?= $per_page === 20 ? ' selected' : '' ?>>20</option>
                <option value="50"<?= $per_page === 50 ? ' selected' : '' ?>>50</option>
            </select>
        </div>

        <div class="circular-my-filter-actions">
            <button type="submit" class="btn">ค้นหา</button>
        </div>
        <div class="circular-my-filter-actions">
            <a class="c-button c-button--sm btn-outline" href="circular-sent.php">ล้างตัวกรอง</a>
        </div>
    </form>
</section>

<section class="enterprise-card">
    <div class="enterprise-card-header">
        <div class="enterprise-card-title-group">
            <h2 class="enterprise-card-title">รายการหนังสือเวียนของฉัน</h2>
            <p class="enterprise-card-subtitle">พบ <?= h((string) $filtered_total) ?> รายการ</p>
        </div>
    </div>

    <div class="table-responsive circular-my-table-wrap">
        <table class="custom-table circular-my-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>เรื่อง</th>
                    <th>ประเภท</th>
                    <th>สถานะ</th>
                    <th>อ่านแล้ว/ทั้งหมด</th>
                    <th>วันที่ส่ง</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($sent_items)) : ?>
                    <tr>
                        <td colspan="7" class="enterprise-empty">ไม่มีรายการหนังสือเวียนตามเงื่อนไข</td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($sent_items as $item) : ?>
                        <?php
                        $circular_id = (int) ($item['circularID'] ?? 0);
                        $status_key = strtoupper(trim((string) ($item['status'] ?? '')));
                        $status_meta = $status_map[$status_key] ?? ['label' => $status_key !== '' ? $status_key : '-', 'pill' => 'pending'];
                        $item_type = strtoupper((string) ($item['circularType'] ?? ''));
                        $type_label = $type_map[$item_type] ?? $item_type;
                        $read_count = (int) ($item['readCount'] ?? 0);
                        $recipient_count = (int) ($item['recipientCount'] ?? 0);
                        $date_display = $format_thai_datetime((string) ($item['createdAt'] ?? ''));

                        $receipt_url = $build_url([
                            'receipt' => $circular_id,
                            'page' => $page,
                        ]);
                        ?>
                        <tr>
                            <td><?= h((string) $circular_id) ?></td>
                            <td>
                                <div class="circular-my-subject"><?= h((string) ($item['subject'] ?? '-')) ?></div>
                                <?php if (!empty($item['senderFactionName'])) : ?>
                                    <div class="circular-my-meta">ในนาม <?= h((string) $item['senderFactionName']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?= h((string) $type_label) ?></td>
                            <td>
                                <span class="status-pill <?= h((string) ($status_meta['pill'] ?? 'pending')) ?>">
                                    <?= h((string) ($status_meta['label'] ?? '-')) ?>
                                </span>
                            </td>
                            <td><?= h((string) $read_count) ?>/<?= h((string) $recipient_count) ?></td>
                            <td><?= h($date_display) ?></td>
                            <td>
                                <div class="circular-my-actions">
                                    <?php if ($item_type === 'INTERNAL' && $status_key === INTERNAL_STATUS_SENT) : ?>
                                        <form method="POST">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="recall">
                                            <input type="hidden" name="circular_id" value="<?= h((string) $circular_id) ?>">
                                            <button type="submit" class="btn">ดึงกลับ</button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($item_type === 'INTERNAL' && $status_key === INTERNAL_STATUS_RECALLED) : ?>
                                        <form method="POST">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="resend">
                                            <input type="hidden" name="circular_id" value="<?= h((string) $circular_id) ?>">
                                            <button type="submit" class="btn">ส่งใหม่</button>
                                        </form>
                                        <a class="c-button c-button--sm btn-outline" href="circular-compose.php?edit=<?= h((string) $circular_id) ?>">แก้ไข</a>
                                    <?php endif; ?>

                                    <a class="c-button c-button--sm btn-outline" href="<?= h($receipt_url) ?>">สถานะการอ่าน</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php
    $pagination_url = $build_url(['page' => null, 'receipt' => null]);
    component_render('pagination', [
        'page' => $page,
        'total_pages' => $total_pages,
        'base_url' => $pagination_url,
        'class' => 'u-mt-2',
    ]);
    ?>
</section>

<?php if ($receipt_circular_id > 0) : ?>
    <section class="enterprise-card">
        <div class="circular-my-receipt-header">
            <div>
                <h2 class="enterprise-card-title">สถานะการอ่านรายบุคคล</h2>
                <p class="circular-my-receipt-subtitle">
                    เรื่อง: <?= h($receipt_subject !== '' ? $receipt_subject : ('#' . $receipt_circular_id)) ?>
                    <?php if ($receipt_sender_faction !== '') : ?>
                        / <?= h($receipt_sender_faction) ?>
                    <?php endif; ?>
                </p>
                <div class="circular-my-receipt-summary">
                    <span class="circular-my-receipt-chip">ผู้รับทั้งหมด <?= h((string) $receipt_total) ?> คน</span>
                    <span class="circular-my-receipt-chip is-read">อ่านแล้ว <?= h((string) $receipt_read) ?> คน</span>
                    <span class="circular-my-receipt-chip is-unread">ยังไม่อ่าน <?= h((string) $receipt_unread) ?> คน</span>
                </div>
            </div>
            <a class="c-button c-button--sm btn-outline" href="<?= h($build_url(['receipt' => null])) ?>">ปิดมุมมองนี้</a>
        </div>

        <div class="table-responsive u-mt-1">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>ชื่อผู้รับ</th>
                        <th>สถานะ</th>
                        <th>เวลาอ่านล่าสุด</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($receipt_stats)) : ?>
                        <tr>
                            <td colspan="3" class="enterprise-empty">ไม่พบข้อมูลผู้รับ</td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($receipt_stats as $stat) : ?>
                            <?php
                            $is_read = (int) ($stat['isRead'] ?? 0) === 1;
                            $read_at_display = $format_thai_datetime((string) ($stat['readAt'] ?? ''));
                            ?>
                            <tr>
                                <td><?= h((string) ($stat['fName'] ?? '-')) ?></td>
                                <td>
                                    <span class="status-pill <?= h($is_read ? 'approved' : 'pending') ?>">
                                        <?= h($is_read ? 'อ่านแล้ว' : 'ยังไม่อ่าน') ?>
                                    </span>
                                </td>
                                <td><?= h($is_read ? $read_at_display : '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';

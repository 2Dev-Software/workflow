<?php
$title = 'กล่องหนังสือเข้า';
$subtitle = 'ติดตามสถานะอ่านแล้ว/ยังไม่อ่าน';
$filters = $filters ?? [];
$page = (int) ($page ?? 1);
$page_size = (int) ($page_size ?? 10);
$total = (int) ($total ?? 0);
$total_pages = max(1, (int) ceil($total / $page_size));

ob_start();
?>
<section class="card">
    <div class="card__header">
        <h3>รายการหนังสือเข้า</h3>
        <div class="card__actions">
            <form class="filters" method="get" action="<?= h(app_url('/inbox')) ?>">
                <div class="input-group">
                    <input type="search" name="q" placeholder="ค้นหาเลขที่/เรื่อง" value="<?= h($filters['q'] ?? '') ?>">
                    <select name="status">
                        <option value="">ทั้งหมด</option>
                        <option value="UNREAD" <?= ($filters['status'] ?? '') === 'UNREAD' ? 'selected' : '' ?>>ยังไม่อ่าน</option>
                        <option value="READ" <?= ($filters['status'] ?? '') === 'READ' ? 'selected' : '' ?>>อ่านแล้ว</option>
                    </select>
                    <button class="btn btn--ghost" type="submit">ค้นหา</button>
                </div>
            </form>
            <button class="btn btn--primary" type="button">ส่งหนังสือใหม่</button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>ประเภท</th>
                    <th>เลขที่</th>
                    <th>เรื่อง</th>
                    <th>ผู้ส่ง</th>
                    <th>วันที่ส่ง</th>
                    <th>สถานะ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)) : ?>
                    <tr><td colspan="6" class="text-center">ยังไม่มีหนังสือเข้า</td></tr>
                <?php else : ?>
                    <?php foreach ($items as $item) : ?>
                        <tr class="<?= ($item['readAt'] ?? null) ? '' : 'is-unread' ?>">
                            <td><?= h($item['documentType'] ?? '') ?></td>
                            <td><?= h($item['documentNumber'] ?? '') ?></td>
                            <td>
                                <div class="subject">
                                    <span><?= h($item['subject'] ?? '') ?></span>
                                    <small>อัปเดตล่าสุด <?= h($item['createdAt'] ?? '') ?></small>
                                </div>
                            </td>
                            <td><?= h($item['senderName'] ?? '') ?></td>
                            <td><?= h($item['createdAt'] ?? '') ?></td>
                            <td>
                                <?php if (!empty($item['readAt'])) : ?>
                                    <span class="badge badge--success">อ่านแล้ว</span>
                                <?php else : ?>
                                    <span class="badge badge--danger">ยังไม่อ่าน</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="pagination">
        <span>หน้า <?= h((string) $page) ?> จาก <?= h((string) $total_pages) ?></span>
        <div class="pagination__actions">
            <?php $prev = max(1, $page - 1); ?>
            <?php $next = min($total_pages, $page + 1); ?>
            <a class="btn btn--ghost" href="<?= h(app_url('/inbox?page=' . $prev)) ?>">ก่อนหน้า</a>
            <a class="btn btn--ghost" href="<?= h(app_url('/inbox?page=' . $next)) ?>">ถัดไป</a>
        </div>
    </div>
</section>

<section class="grid grid--2">
    <div class="card">
        <div class="card__header">
            <h3>ตัวอย่างพรีวิวหนังสือ</h3>
            <span class="badge badge--neutral">เลือกจากรายการ</span>
        </div>
        <div class="preview">
            <p class="preview__title">แจ้งกำหนดการประชุมฝ่ายบริหาร</p>
            <p class="preview__meta">ส่งโดย งานสารบรรณ • วันนี้ 10:30</p>
            <p class="preview__body">ขอเชิญประชุมฝ่ายบริหารวันศุกร์ที่ 30 มกราคม 2569 เวลา 09:00 น.</p>
        </div>
    </div>
    <div class="card">
        <div class="card__header">
            <h3>สรุปสถานะการอ่าน</h3>
        </div>
        <div class="stat-list">
            <div class="stat-item">
                <span>ยังไม่อ่าน</span>
                <strong>5</strong>
            </div>
            <div class="stat-item">
                <span>อ่านแล้ว</span>
                <strong>18</strong>
            </div>
            <div class="stat-item">
                <span>รอเสนอผู้บริหาร</span>
                <strong>3</strong>
            </div>
        </div>
    </div>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';

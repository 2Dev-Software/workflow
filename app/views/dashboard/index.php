<?php
$title = 'แดชบอร์ด';
$subtitle = 'ภาพรวมงานค้างและกล่องหนังสือเข้า';

ob_start();
?>
<section class="grid grid--3">
    <div class="card">
        <div class="card__header">
            <h3>หนังสือเข้าทั้งหมด</h3>
            <span class="badge badge--neutral"><?= h((string) ($counts['total'] ?? 0)) ?></span>
        </div>
        <p class="card__value"><?= h((string) ($counts['total'] ?? 0)) ?></p>
        <p class="card__meta">อัปเดตล่าสุดวันนี้</p>
    </div>
    <div class="card">
        <div class="card__header">
            <h3>ยังไม่อ่าน</h3>
            <span class="badge badge--danger"><?= h((string) ($counts['unread'] ?? 0)) ?></span>
        </div>
        <p class="card__value"><?= h((string) ($counts['unread'] ?? 0)) ?></p>
        <p class="card__meta">ต้องติดตามภายใน 3 วัน</p>
    </div>
    <div class="card">
        <div class="card__header">
            <h3>งานรอเสนอ</h3>
            <span class="badge badge--warning">5</span>
        </div>
        <p class="card__value">5</p>
        <p class="card__meta">สำหรับผู้บริหาร</p>
    </div>
</section>

<section class="card card--accent">
    <div class="card__header">
        <h3>ทางลัดงานประจำ</h3>
        <div class="chip-group">
            <span class="chip">หนังสือเวียน</span>
            <span class="chip">คำสั่ง</span>
            <span class="chip">หนังสือออก</span>
        </div>
    </div>
    <div class="quick-actions">
        <a class="btn btn--ghost" href="<?= h(app_url('/inbox')) ?>">ไปที่กล่องหนังสือเข้า</a>
        <button class="btn btn--primary" type="button">สร้างหนังสือใหม่</button>
    </div>
</section>

<section class="grid grid--2">
    <div class="card">
        <div class="card__header">
            <h3>ติดตามการอ่านล่าสุด</h3>
            <a class="link" href="<?= h(app_url('/inbox')) ?>">ดูทั้งหมด</a>
        </div>
        <ul class="list">
            <li><span>แจ้งประชุมฝ่ายบริหาร</span><span class="badge badge--success">อ่านแล้ว</span></li>
            <li><span>หนังสือด่วนเขตพื้นที่</span><span class="badge badge--danger">ยังไม่อ่าน</span></li>
            <li><span>คำสั่งแต่งตั้งคณะทำงาน</span><span class="badge badge--warning">รอส่งต่อ</span></li>
        </ul>
    </div>
    <div class="card">
        <div class="card__header">
            <h3>ปฏิทินการจองวันนี้</h3>
            <a class="link" href="<?= h(app_url('/booking')) ?>">ดูปฏิทิน</a>
        </div>
        <div class="calendar-preview">
            <div class="calendar-row">
                <span>ห้องประชุม 1</span>
                <span>09:00 - 11:00</span>
            </div>
            <div class="calendar-row">
                <span>รถตู้ 1</span>
                <span>13:00 - 16:00</span>
            </div>
        </div>
    </div>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';

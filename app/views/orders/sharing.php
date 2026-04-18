<?php
require_once __DIR__ . '/../../helpers.php';

$item = $item ?? null;
$attachments = (array) ($attachments ?? []);
$share_token = trim((string) ($share_token ?? ''));

$format_size = static function (int $bytes): string {
    if ($bytes <= 0) {
        return '-';
    }

    $units = ['B', 'KB', 'MB', 'GB'];
    $value = (float) $bytes;
    $index = 0;

    while ($value >= 1024 && $index < (count($units) - 1)) {
        $value /= 1024;
        $index++;
    }

    return number_format($value, $index === 0 ? 0 : 2) . ' ' . $units[$index];
};

$format_thai_datetime = static function (?string $datetime): string {
    $datetime = trim((string) $datetime);

    if ($datetime === '' || $datetime === '0000-00-00 00:00:00') {
        return '-';
    }

    try {
        $date = new DateTime($datetime);
    } catch (Throwable $e) {
        return $datetime;
    }

    $months = [
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
    $month = (int) $date->format('n');
    $year = (int) $date->format('Y') + 543;

    return (int) $date->format('j') . ' ' . ($months[$month] ?? '') . ' ' . $year . ' เวลา ' . $date->format('H:i') . ' น.';
};

$detail_lines = [];

if ($item) {
    $detail_text = trim((string) ($item['detail'] ?? ''));
    $detail_lines = array_values(array_filter(array_map('trim', preg_split('/\R/u', $detail_text) ?: []), static function (string $line): bool {
        return $line !== '';
    }));
}
?>
<!DOCTYPE html>
<html lang="th">
<?php require __DIR__ . '/../../../public/components/x-head.php'; ?>

<body class="orders-sharing-public">
    <main class="orders-sharing-shell">
        <div class="content-header">
            <h1>คำสั่งราชการที่ 1/2569</h1>
        </div>

        <main class="content-sharing-card">
            <div class="header">
                <p>แต่งตั้งรักษาการแทนผู้อำนวยการโรงเรียน</p>
            </div>
            <div class="sharing-row">
                <div class="group">
                    <label for="">ทั้งนี้ตั้งแต่วันที่</label>
                    <p>วันที่ 17 มีนาคม พ.ศ. 2568</p>
                </div>
                <div class="group">
                    <label for="">สั้ง ณ วันที่</label>
                    <p>วันที่ 17 มีนาคม พ.ศ. 2568</p>
                </div>
            </div>
            <div class="sharing-row">
                <div class="group">
                    <label for="">ผู้ออกคำสั่ง</label>
                    <p>นางสาวทิพยรัต์ บุญมณี</p>
                </div>
                <div class="group">
                    <label for="">กลุ่ม/ฝ่าย</label>
                    <p>กลุ่มบริหารงานทั่วไป</p>
                </div>
            </div>
            <div class="sharing-row">
                <div class="group">
                    <label for="">วันที่สร้างรายการ</label>
                    <p>วันที่ 3 มีนาคม พ.ศ. 2569 เวลา 16:35 น.</p>
                </div>
            </div>

            <hr>

            <section class="sharing-table">
                <div class="header">
                    <p>ไฟล์เอกสารแนบจากระบบ</p>
                    <a href="#">ดาวน์โหลดไฟล์ทั้งหมด</a>
                </div>
                <div class="table-responsive">
                    <table class="custom-table booking-table">
                        <thead>
                            <tr>
                                <th>ชื่อไฟล์</th>
                                <th>จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>WorkSheet_5+-=Pre-campaign+insight+_page-0001.jpg</td>
                                <td>
                                    <a class="booking-action-btn secondary" href="<?= h($file_href) ?>" target="_blank" rel="noopener">
                                        <i class="fa-solid fa-eye"></i>
                                        <span class="tooltip">ดูไฟล์</span>
                                    </a>
                                    <a class="booking-action-btn secondary" href="<?= h($file_href . '&download=1') ?>">
                                        <i class="fa-solid fa-download"></i>
                                        <span class="tooltip">ดาวน์โหลด</span>
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td>Worksheet_6-+Pre-campaign+.pdf</td>
                                <td>
                                    <a class="booking-action-btn secondary" href="<?= h($file_href) ?>" target="_blank" rel="noopener">
                                        <i class="fa-solid fa-eye"></i>
                                        <span class="tooltip">ดูไฟล์</span>
                                    </a>
                                    <a class="booking-action-btn secondary" href="<?= h($file_href . '&download=1') ?>">
                                        <i class="fa-solid fa-download"></i>
                                        <span class="tooltip">ดาวน์โหลด</span>
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

        </main>


    </main>
</body>

</html>
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
    <style>
        .orders-sharing-public {
            background: #f4f7fb;
            min-height: 100vh;
        }

        .orders-sharing-shell {
            width: min(1080px, calc(100% - 32px));
            margin: 0 auto;
            padding: 40px 0;
        }

        .orders-sharing-shell .content-header {
            margin-bottom: 18px;
        }

        .orders-sharing-shell .enterprise-card {
            background: #fff;
        }

        .orders-sharing-notice {
            color: #5d6b8a;
            margin: 4px 0 0;
        }
    </style>

    <main class="orders-sharing-shell">
        <div class="content-header">
            <h1>คำสั่งราชการ</h1>
            <p>ลิงก์สาธารณะสำหรับแชร์คำสั่งราชการ</p>
        </div>

        <section class="enterprise-card orders-view-card" data-orders-sharing>
            <?php if (!$item) : ?>
                <div class="enterprise-card-header">
                    <div class="enterprise-card-title-group">
                        <h2 class="enterprise-card-title">ไม่พบคำสั่งราชการ</h2>
                        <p class="enterprise-card-subtitle">ลิงก์นี้ไม่ถูกต้อง หรือรายการถูกยกเลิกการใช้งานแล้ว</p>
                    </div>
                </div>
            <?php else : ?>
                <div class="enterprise-card-header">
                    <div class="enterprise-card-title-group">
                        <h2 class="enterprise-card-title"><?= h((string) ($item['subject'] ?? 'คำสั่งราชการ')) ?></h2>
                        <p class="enterprise-card-subtitle">ข้อมูลคำสั่งราชการจากระบบสำนักงานอิเล็กทรอนิกส์ โรงเรียนดีบุกพังงาวิทยายน</p>
                    </div>
                </div>

                <div class="enterprise-info">
                    <div class="enterprise-info-row">
                        <span class="enterprise-info-label">เลขที่คำสั่ง</span>
                        <span class="enterprise-info-value"><?= h((string) ($item['orderNo'] ?? '-')) ?></span>
                    </div>
                    <div class="enterprise-info-row">
                        <span class="enterprise-info-label">เรื่อง</span>
                        <span class="enterprise-info-value"><?= h((string) ($item['subject'] ?? '-')) ?></span>
                    </div>
                    <div class="enterprise-info-row">
                        <span class="enterprise-info-label">ผู้ออกคำสั่ง</span>
                        <span class="enterprise-info-value"><?= h((string) ($item['creatorName'] ?? '-')) ?></span>
                    </div>
                    <div class="enterprise-info-row">
                        <span class="enterprise-info-label">กลุ่ม/ฝ่าย</span>
                        <span class="enterprise-info-value"><?= h((string) ($item['creatorFactionName'] ?? '-')) ?></span>
                    </div>
                    <div class="enterprise-info-row">
                        <span class="enterprise-info-label">วันที่สร้างรายการ</span>
                        <span class="enterprise-info-value"><?= h($format_thai_datetime((string) ($item['createdAt'] ?? ''))) ?></span>
                    </div>
                </div>

                <div class="enterprise-divider"></div>

                <div class="enterprise-panel">
                    <p><strong>รายละเอียด</strong></p>
                    <?php if ($detail_lines === []) : ?>
                        <p>-</p>
                    <?php else : ?>
                        <?php foreach ($detail_lines as $line) : ?>
                            <p><?= h($line) ?></p>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="enterprise-divider"></div>

                <div class="enterprise-panel">
                    <p><strong>ไฟล์แนบ</strong></p>
                    <?php if ($attachments === []) : ?>
                        <p class="orders-sharing-notice">ไม่มีไฟล์แนบ</p>
                    <?php else : ?>
                        <div class="table-responsive">
                            <table class="custom-table booking-table">
                                <thead>
                                    <tr>
                                        <th>ชื่อไฟล์</th>
                                        <th>ประเภท</th>
                                        <th>ขนาด</th>
                                        <th>จัดการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($attachments as $file) : ?>
                                        <?php
                                        $file_id = (int) ($file['fileID'] ?? 0);
                                        $file_href = 'orders-sharing-file.php?token=' . rawurlencode($share_token) . '&file_id=' . rawurlencode((string) $file_id);
                                        ?>
                                        <tr>
                                            <td><?= h((string) ($file['fileName'] ?? '-')) ?></td>
                                            <td><?= h((string) ($file['mimeType'] ?? '-')) ?></td>
                                            <td><?= h($format_size((int) ($file['fileSize'] ?? 0))) ?></td>
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
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>

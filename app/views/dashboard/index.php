<?php
require_once __DIR__ . '/../../helpers.php';

$dashboard_counts = (array) ($dashboard_counts ?? []);
$dashboard_shortcuts = (array) ($dashboard_shortcuts ?? []);

$visible_shortcuts = array_values(array_filter($dashboard_shortcuts, static function ($shortcut): bool {
    return !empty($shortcut['visible']);
}));

ob_start();
?>
<div class="dashboard-container">
    <!-- <div class="content waiting-circular">
        <div class="content-header">
            <p>หนังสือเวียนใหม่ที่รออ่าน</p>
        </div>
        <div class="content-info">
            <p><?= h((string) ((int) ($dashboard_counts['unread_circulars'] ?? 0))) ?></p>
            <i class="fa-book fa-solid"></i>
        </div>
    </div>
    <div class="content waiting-official-order">
        <div class="content-header">
            <p>คำสั่งราชการใหม่ที่รออ่าน</p>
        </div>
        <div class="content-info">
            <p><?= h((string) ((int) ($dashboard_counts['unread_orders'] ?? 0))) ?></p>
            <i class="fa-book fa-solid"></i>
        </div>
    </div>
    <div class="content waiting-manager">
        <div class="content-header">
            <p>หนังสือที่รอเสนอผู้บริหาร</p>
        </div>
        <div class="content-info">
            <p><?= h((string) ((int) ($dashboard_counts['pending_manager'] ?? 0))) ?></p>
            <i class="fa-book fa-solid"></i>
        </div>
    </div>
    <div class="content waiting-approve">
        <div class="content-header">
            <p>หนังสือที่รอเซ็นอนุมัติ</p>
        </div>
        <div class="content-info">
            <p><?= h((string) ((int) ($dashboard_counts['pending_approvals'] ?? 0))) ?></p>
            <i class="fa-book fa-solid"></i>
        </div>
    </div> -->

    <div class="notification-system">
        <div class="notification-list">
            <ul>
                <li><i class="fa-solid fa-caret-right"></i>คุณมี <b>หนังสือเวียน</b> ที่ยังไม่อ่าน <b>9999</b> ฉบับ</li>
                <li><i class="fa-solid fa-caret-right"></i>คุณมี <b>หนังสือเวียน (ภายใน)</b> ที่ยังไม่อ่าน <b>9999</b> ฉบับ</li>
                <li><i class="fa-solid fa-caret-right"></i>คุณมี <b>บันทึกข้อความ</b> ที่ยังไม่อ่าน <b>9999</b>ฉบับ</li>
                <li><i class="fa-solid fa-caret-right"></i>คุณมี <b>คำสั่งราชการ</b> ที่ยังไม่อ่าน <b>9999</b> ฉบับ</li>
                <li><i class="fa-solid fa-caret-right"></i>คุณมี <b>จองยานพาหนะ</b> ที่ยังไม่อ่าน <b>9999</b> ฉบับ</li>
            </ul>
        </div>
        <main class="profile-section">
            <?php
            $profile_picture_raw = trim((string) ($teacher['picture'] ?? ''));
            $profile_picture = '';

            if ($profile_picture_raw !== '' && strtoupper($profile_picture_raw) !== 'EMPTY') {
                $profile_picture = $profile_picture_raw;
            }
            ?>
            <section>
                <div class="profile-image">
                    <? //php if ($profile_picture !== ''): 
                    ?>
                    <img src="/assets/img/profile/1819900163142/profile_20260210_203425_572e17b6cfe7.jpg" alt="Profile image">
                    <? //php else: 
                    ?>
                    <!-- <i class="fa-solid fa-user"></i> -->
                    <? //php endif; 
                    ?>
                </div>
                <div class="proflie-text">
                    <p><b>ชื่อ : นางทิพรัตน์ บุณมณี</b></p>
                    <p>ตำแหน่ง : ผู้อำนวยการโรงเรียน</p>
                    <p>หน้าที่ : เจ้าหน้าที่ธุรการ</p>
                    <!-- <p><b>ชื่อ : <?= htmlspecialchars($teacher['fName'] ?? '', ENT_QUOTES, 'UTF-8') ?></b></p>
                    <p>ตำแหน่ง : <?= htmlspecialchars($teacher['position_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                    <p>หน้าที่ : <?= htmlspecialchars($teacher['role_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></p> -->
                </div>
            </section>
            <div class="profile-date">
                <i class="fa-solid fa-clock"></i>
                <p>วันที่ 19 มิถุนายน พ.ศ.2569</p>
            </div>
        </main>
    </div>


    <div class="dashboard-header">
        <p><strong>แผนผังระบบสำนักงานอิเล็กทรอนิกส์</strong></p>
    </div>

    <!-- <div class="dashboard-content">
        <?php foreach ($visible_shortcuts as $shortcut) : ?>
            <?php
            $shortcut_href = trim((string) ($shortcut['href'] ?? '#'));
            $shortcut_icon = trim((string) ($shortcut['icon'] ?? 'fa-link'));
            $shortcut_label = trim((string) ($shortcut['label'] ?? ''));
            ?>
            <a href="<?= h($shortcut_href !== '' ? $shortcut_href : '#') ?>">
                <div class="card-shortcut">
                    <i class="fa-solid <?= h($shortcut_icon) ?>"></i>
                    <p><strong><?= h($shortcut_label) ?></strong></p>
                </div>
            </a>
        <?php endforeach; ?>
    </div> -->

    <!-- <div class="dashboard-content">
        <?php foreach ($visible_shortcuts as $shortcut) : ?>
            <?php
            $shortcut_href = trim((string) ($shortcut['href'] ?? '#'));
            $shortcut_label = trim((string) ($shortcut['label'] ?? ''));
            $shortcut_image = trim((string) ($shortcut['image'] ?? ''));
            ?>
            <a href="<?= h($shortcut_href !== '' ? $shortcut_href : '#') ?>">
                <div class="card-shortcut">
                    <img src="<?= h($shortcut_image) ?>" alt="">
                    <p><strong><?= h($shortcut_label) ?></strong></p>
                </div>
            </a>
        <?php endforeach; ?>
    </div> -->

    <div class="dashboard-content">
        <a href="outgoing-receive.php">
            <div class="card-shortcut">
                <img src="/public/assets/img/icon/member.png" alt="">
                <p><strong>ลงทะเบียนรับ</strong></p>
            </div>
        </a>
        <a href="outgoing-receive.php">
            <div class="card-shortcut">
                <img src="/public/assets/img/icon/clipboard.png" alt="">
                <p><strong>ออกเลขทะเบียนส่ง</strong></p>
            </div>
        </a>
        <a href="memo.php">
            <div class="card-shortcut">
                <img src="/public/assets/img/icon/memo.png" alt="">
                <p><strong>บันทึกข้อความ</strong></p>
            </div>
        </a>
        <a href="circular-compose.php">
            <div class="card-shortcut">
                <img src="/public/assets/img/icon/envelope.png" alt="">
                <p><strong>ส่งหนังสือเวียน</strong></p>
            </div>
        </a>
        <a href="orders-create.php">
            <div class="card-shortcut">
                <img src="/public/assets/img/icon/files.png" alt="">
                <p><strong>คำสั่งราชการ</strong></p>
            </div>
        </a>
        <a href="vehicle-reservation.php">
            <div class="card-shortcut">
                <img src="/public/assets/img/icon/car.png" alt="">
                <p><strong>การจองพาหนะ</strong></p>
            </div>
        </a>
        <a href="room-booking.php">
            <div class="card-shortcut">
                <img src="/public/assets/img/icon/building.png" alt="">
                <p><strong>การจองสถานที่/ห้อง</strong></p>
            </div>
        </a>
        <a href="repairs.php">
            <div class="card-shortcut">
                <img src="/public/assets/img/icon/repair.png" alt="">
                <p><strong>แจ้งเหตุซ่อมแซม</strong></p>
            </div>
        </a>
        <a href="certificates.php">
            <div class="card-shortcut">
                <img src="/public/assets/img/icon/certificate.png" alt="">
                <p><strong>ออกเลขเกียรติบัตร</strong></p>
            </div>
        </a>
        <a href="teacher-phone-directory.php">
            <div class="card-shortcut">
                <img src="/public/assets/img/icon/phone.png" alt="">
                <p><strong>สมุดโทรศัพท์</strong></p>
            </div>
        </a>
        <a href="profile.php">
            <div class="card-shortcut">
                <img src="/public/assets/img/icon/user.png" alt="">
                <p><strong>โปรไฟล์</strong></p>
            </div>
        </a>
        <a href="setting.php">
            <div class="card-shortcut">
                <img src="/public/assets/img/icon/setting.png" alt="">
                <p><strong>การตั้งค่า</strong></p>
            </div>
        </a>
    </div>

</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';

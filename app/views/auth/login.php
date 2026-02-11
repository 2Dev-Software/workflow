<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../security/csrf.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= csrf_meta_tag() ?>
    <title>เข้าสู่ระบบ</title>
    <link rel="stylesheet" href="<?= h(app_url('/assets/fonts/thsarabunnew.css')) ?>">
    <link rel="stylesheet" href="<?= h(app_url('/assets/css/tokens.css')) ?>">
    <link rel="stylesheet" href="<?= h(app_url('/assets/css/base.css')) ?>">
    <link rel="stylesheet" href="<?= h(app_url('/assets/css/components.css')) ?>">
</head>
<body class="auth-body">
    <div class="auth-shell">
        <section class="auth-panel">
            <div class="auth-brand">
                <span class="brand__mark">DB</span>
                <div>
                    <h1>ระบบสำนักงานอิเล็กทรอนิกส์</h1>
                    <p>โรงเรียนดีบุกพังงาวิทยายน</p>
                </div>
            </div>

            <?php if (!empty($alert)) : ?>
                <div class="alert alert--<?= h($alert['type'] ?? 'info') ?>">
                    <p><?= h($alert['message'] ?? '') ?></p>
                </div>
            <?php endif; ?>

            <form class="form" method="post" action="<?= h(app_url('/login')) ?>" data-validate>
                <?= csrf_field() ?>
                <div class="form__group">
                    <label for="pid">เลขบัตรประชาชน</label>
                    <input id="pid" name="pid" type="text" inputmode="numeric" maxlength="13" required>
                </div>
                <div class="form__group">
                    <label for="password">รหัสผ่าน</label>
                    <input id="password" name="password" type="password" required>
                </div>
                <button class="btn btn--primary btn--block" type="submit">เข้าสู่ระบบ</button>
            </form>

            <div class="auth-meta">
                <span>ต้องการความช่วยเหลือ? ติดต่อสารบรรณ</span>
            </div>
        </section>
        <aside class="auth-aside">
            <div class="info-card">
                <h2>ระบบเอกสารเวียนแบบครบวงจร</h2>
                <p>ติดตามสถานะหนังสือ, อ่านแล้ว/ยังไม่อ่าน, และการอนุมัติอย่างโปร่งใส</p>
                <ul>
                    <li>กล่องหนังสือเข้าแบบ Email</li>
                    <li>เลขที่หนังสือออกแบบกันชน</li>
                    <li>Audit log + Correlation ID</li>
                </ul>
            </div>
        </aside>
    </div>

    <script src="<?= h(app_url('/assets/js/app.js')) ?>" defer></script>
    <script src="<?= h(app_url('/assets/js/modules/form-validation.js')) ?>" defer></script>
</body>
</html>

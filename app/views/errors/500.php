<?php
require_once __DIR__ . '/../../helpers.php';
http_response_code(500);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>500</title>
    <link rel="stylesheet" href="<?= h(app_url('/assets/css/tokens.css')) ?>">
    <link rel="stylesheet" href="<?= h(app_url('/assets/css/base.css')) ?>">
    <link rel="stylesheet" href="<?= h(app_url('/assets/css/components.css')) ?>">
</head>
<body class="auth-body">
    <div class="centered">
        <h1>500</h1>
        <p>ขออภัย ระบบไม่สามารถให้บริการได้ในขณะนี้</p>
        <a class="btn btn--primary" href="<?= h(app_url('/dashboard')) ?>">กลับไปหน้าแดชบอร์ด</a>
    </div>
</body>
</html>

<?php
$asset_version = static function (string $relativePath, string $fallback = '1'): string {
    $absolutePath = dirname(__DIR__) . '/' . ltrim($relativePath, '/');
    $modifiedAt = @filemtime($absolutePath);

    return $modifiedAt !== false ? (string) $modifiedAt : $fallback;
};
?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <title>DB SARABUN - ระบบงานสารบรรณออนไลน์ โรงเรียนดีบุกพังงาวิทยายน</title>
    <meta name="title" content="DB SARABUN - ระบบงานสารบรรณออนไลน์ โรงเรียนดีบุกพังงาวิทยายน">
    <meta name="description" content="แพลตฟอร์มบริหารจัดการโรงเรียนครบวงจร ปลอดภัย ทันสมัย ใช้งานง่าย">
    <meta name="author" content="Deebuk Platform">

    <link rel="canonical" href="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"; ?>">

    <link rel="icon" href="assets/img/favicon/db-sarabun-logo.png" type="image/png">
    <link rel="apple-touch-icon" href="assets/img/favicon/db-sarabun-logo.png">
    <meta name="theme-color" content="#435EBE">

    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"; ?>">
    <meta property="og:title" content="DB Sarabun - ระบบสำนักงานอิเล็กทรอนิกส์ โรงเรียนดีบุกพังงาวิทยายน">
    <meta property="og:description" content="แพลตฟอร์มบริหารจัดการโรงเรียนครบวงจร ปลอดภัย ทันสมัย ใช้งานง่าย">
    <meta property="og:image" content="assets/img/favicon/db-sarabun-logo.png">

    <link href="assets/fonts/th-style.css?v=<?= htmlspecialchars($asset_version('assets/fonts/th-style.css'), ENT_QUOTES, 'UTF-8') ?>" rel="stylesheet" />
    <link rel="stylesheet" href="assets/css/tokens.css?v=<?= htmlspecialchars($asset_version('assets/css/tokens.css'), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="assets/css/base.css?v=<?= htmlspecialchars($asset_version('assets/css/base.css'), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="assets/css/components.css?v=<?= htmlspecialchars($asset_version('assets/css/components.css'), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="assets/css/main.css?v=<?= htmlspecialchars($asset_version('assets/css/main.css'), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="assets/css/vendor-sweetalert2.min.css?v=<?= htmlspecialchars($asset_version('assets/css/vendor-sweetalert2.min.css'), ENT_QUOTES, 'UTF-8') ?>">
    <script src="https://kit.fontawesome.com/b8df3af368.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>

</head>

<?php
require_once __DIR__ . '/../../helpers.php';

ob_start();
?>
<div class="content-header">
    <h1>กำลังพัฒนา</h1>
    <p>โมดูลนี้จะเปิดให้ใช้งานเร็ว ๆ นี้</p>
</div>
<section class="enterprise-card">
    <div class="enterprise-card-header">
        <div class="enterprise-card-title-group">
            <h2 class="enterprise-card-title">ทะเบียนเกียรติบัตร</h2>
            <p class="enterprise-card-subtitle">อยู่ระหว่างพัฒนา</p>
        </div>
    </div>
    <p>ระบบทะเบียนเกียรติบัตรจะเปิดใช้งานเร็ว ๆ นี้</p>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';

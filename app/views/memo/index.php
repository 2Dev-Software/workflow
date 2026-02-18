<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../auth/csrf.php';

$values = $values ?? ['writeDate' => '', 'subject' => '', 'detail' => ''];
$current_user = (array) ($current_user ?? []);
$factions = (array) ($factions ?? []);

$selected_sender_fid = trim((string) ($values['sender_fid'] ?? ''));

if ($selected_sender_fid === '' && !empty($factions)) {
    $selected_sender_fid = (string) ($factions[0]['fID'] ?? '');
}

$signature_src = trim((string) ($current_user['signature'] ?? ''));
$current_name = trim((string) ($current_user['fName'] ?? ''));
$current_position = trim((string) ($current_user['position_name'] ?? ''));

ob_start();
?>
<style>
    .content-memo .memo-detail {
        --memo-label-width: 56px;
    }

    .content-memo .memo-detail .form-group-row.memo-subject-row {
        gap: 10px;
    }

    .content-memo .memo-detail .form-group-row.memo-to-row {
        gap: 10px;
    }

    .content-memo .memo-detail .form-group-row.memo-subject-row > p:first-child,
    .content-memo .memo-detail .form-group-row.memo-to-row > p:first-child {
        width: var(--memo-label-width);
        min-width: var(--memo-label-width);
    }

    .content-memo .memo-detail .form-group-row.memo-subject-row input[name="subject"] {
        flex: 1 1 auto;
        min-width: 0;
    }
</style>
<div class="content-header">
    <h1>ยินดีต้อนรับ</h1>
    <p>บันทึกข้อความ</p>
</div>

<div class="content-memo">
    <div class="memo-header">
        <img src="assets/img/garuda-logo.png" alt="">
        <p>บันทึกข้อความ</p>
        <div></div>
    </div>

    <form method="POST" id="circularComposeForm">
        <?= csrf_field() ?>
        <input type="hidden" name="flow_mode" value="CHAIN">
        <input type="hidden" name="to_choice" value="DIRECTOR">

        <div class="memo-detail">
            <div class="form-group-row">
                <p><strong>ส่วนราชการ</strong></p>

                <div class="custom-select-wrapper">
                    <div class="custom-select-trigger">
                        <p class="select-value">
                            <?php
                            $selected_faction_name = '';

foreach ($factions as $faction) {
    if ((string) ($faction['fID'] ?? '') === $selected_sender_fid) {
        $selected_faction_name = (string) ($faction['fname'] ?? '');
        break;
    }
}
echo h($selected_faction_name !== '' ? $selected_faction_name : 'เลือกส่วนราชการ');
?>
                        </p>
                        <i class="fa-solid fa-chevron-down"></i>
                    </div>

                    <div class="custom-options">
                        <?php foreach ($factions as $faction) : ?>
                            <?php $fid = (string) ($faction['fID'] ?? ''); ?>
                            <div class="custom-option<?= $fid === $selected_sender_fid ? ' selected' : '' ?>" data-value="<?= h($fid) ?>">
                                <?= h((string) ($faction['fname'] ?? '')) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <input type="hidden" name="sender_fid" value="<?= h($selected_sender_fid) ?>">
                </div>

                <p><strong>โรงเรียนดีบุกพังงาวิทยายน</strong></p>
            </div>

            <div class="form-group-row memo-subject-row">
                <p><strong>เรื่อง</strong></p>
                <input type="text" name="subject" value="<?= h((string) ($values['subject'] ?? '')) ?>" required>
            </div>

            <div class="form-group-row memo-to-row">
                <p><strong>เรียน</strong></p>
                <p>ผู้อำนวยการโรงเรียนดีบุกพังงาวิทยายน</p>
            </div>

            <div class="content-editor">
                <p><strong>รายละเอียด:</strong></p>
                <textarea name="detail" id="memo_editor"><?= h((string) ($values['detail'] ?? '')) ?></textarea>
            </div>

            <div class="form-group-row signature">
                <img src="<?= h($signature_src) ?>" alt="">
                <p>(<?= h($current_name !== '' ? $current_name : '-') ?>)</p>
                <p><?= h($current_position !== '' ? $current_position : '-') ?></p>
            </div>

            <div class="form-group-row submit">
                <button type="submit">บันทึกเอกสาร</button>
            </div>
        </div>
    </form>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js"></script>
<script>
tinymce.init({
    selector: '#memo_editor',
    height: 500,
    menubar: false,
    language: 'th_TH',
    plugins: 'searchreplace autolink directionality visualblocks visualchars image link media codesample table charmap pagebreak nonbreaking anchor insertdatetime advlist lists wordcount help charmap emoticons',
    toolbar: 'undo redo | fontfamily | fontsize | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | outdent indent |  numlist bullist | forecolor backcolor removeformat | pagebreak | charmap emoticons',
    font_family_formats: 'TH Sarabun New=Sarabun, sans-serif;',
    font_size_formats: '8pt 9pt 10pt 12pt 14pt 16pt 18pt 20pt 22pt 24pt 26pt 36pt 48pt 72pt',
    content_style: `
        @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&display=swap');
        body {
            font-family: 'Sarabun', sans-serif;
            font-size: 16pt;
            line-height: 1.5;
            color: #000;
            background-color: #fff;
            padding: 0 20px;
            margin: 0 auto;
        }
        p {
            margin-bottom: 0px;
        }
    `,
    nonbreaking_force_tab: true,
    promotion: false,
    branding: false
});

document.addEventListener('DOMContentLoaded', function() {
    return;
});
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';

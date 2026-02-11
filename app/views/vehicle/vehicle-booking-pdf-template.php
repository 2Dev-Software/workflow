<?php
declare(strict_types=1);

require_once __DIR__ . '/../../helpers.php';

if (!function_exists('vehicle_booking_pdf_render_html')) {
    /**
     * Render Vehicle Booking PDF HTML (mPDF).
     *
     * @param array<string, mixed> $data
     */
    function vehicle_booking_pdf_render_html(array $data): string
    {
        $school_name = trim((string) ($data['school_name'] ?? ''));
        $write_date_label = trim((string) ($data['write_date_label'] ?? '-'));

        $paragraph_lines = $data['paragraph_lines'] ?? [];
        if (!is_array($paragraph_lines)) {
            $paragraph_lines = [];
        }
        $paragraph_lines = array_values(array_filter(array_map(
            static fn($v) => trim((string) $v),
            $paragraph_lines
        ), static fn(string $v) => $v !== ''));

        $requester_sig = $data['requester_signature'] ?? null;
        $requester_sig = is_string($requester_sig) && $requester_sig !== '' ? $requester_sig : null;
        $requester_name = trim((string) ($data['requester_name'] ?? '-'));
        $requester_position = trim((string) ($data['requester_position'] ?? '-'));

        $vehicle_label = trim((string) ($data['vehicle_label'] ?? '-'));
        $driver_name = trim((string) ($data['driver_name'] ?? ''));
        $driver_tel = trim((string) ($data['driver_tel'] ?? ''));

        $assigned_note = trim((string) ($data['assigned_note'] ?? ''));
        $assigned_sig = $data['assigned_signature'] ?? null;
        $assigned_sig = is_string($assigned_sig) && $assigned_sig !== '' ? $assigned_sig : null;
        $assigned_name = trim((string) ($data['assigned_name'] ?? '-'));
        $assigned_position = trim((string) ($data['assigned_position'] ?? '-'));

        $boss_note = trim((string) ($data['boss_note'] ?? ''));
        $boss_name = trim((string) ($data['boss_name'] ?? '-'));
        $boss_position_line_1 = trim((string) ($data['boss_position_line_1'] ?? '-'));
        $boss_position_line_2 = trim((string) ($data['boss_position_line_2'] ?? ''));

	        $boss_signature = $data['boss_signature'] ?? null;
	        $boss_signature = is_string($boss_signature) && $boss_signature !== '' ? $boss_signature : null;

	        $assigned_note_line_2 = $assigned_note !== '' ? $assigned_note : 'อื่นๆ ........................................................';

        ob_start();
        ?>
<!doctype html>
<html lang="th">
<head>
	  <meta charset="utf-8">
	  <style>
	    /* Thai official docs often use Sarabun with tighter leading (Word-like). */
	    body { font-family: sarabun; font-size: 15pt; line-height: 1.35; color: #000; }
	    * { box-sizing: border-box; }
	    .center { text-align: center; }
	    .title { font-weight: bold; font-size: 18.5pt; margin-top: 4pt; }
	    .date-line { margin-top: 4pt; margin-bottom: 8pt; }
	    .salutation { margin-top: 8pt; }

    /* Word-like paragraph block with consistent left indent for every line. */
    .para-block { margin: 0; margin-left: 2.2em; }
    .para-line { margin: 0 0 3pt 0; }

	    .sig-block { text-align: center; margin-top: 14pt; }
	    .sig-img { display: block; height: 54pt; width: auto; max-width: 240pt; margin: 0 auto; }
	    .sig-name { margin-top: 2pt; }
	    .sig-role { margin-top: 0pt; }

    .space-md { height: 14pt; }
    .note { margin: 0 0 6pt 0; }

	    .approval-grid { width: 100%; margin-top: 14pt; border-collapse: collapse; }
	    .approval-grid td { vertical-align: top; }
    .approval-grid td.col { width: 50%; }
    .approval-grid td.gap { width: 12pt; }

    .box { width: 100%; border-collapse: collapse; border: 0.6pt solid #000; }
    .box-title { font-weight: bold; text-align: center; padding: 8pt 10pt; border-bottom: 0.6pt solid #000; }
    .box-body { padding: 10pt 12pt; }
	    .box-body.fixed { height: 112pt; }
    .box-sign { padding: 10pt 12pt; border-top: 0.6pt solid #000; text-align: center; }

    .fill-line { border-bottom: 0.6pt dotted #000; height: 18pt; margin: 0 0 6pt 0; }
    .sign-table { width: 100%; border-collapse: collapse; margin-top: 6pt; }
    .sign-table td { vertical-align: bottom; }
    .sign-label { width: 46pt; text-align: left; }
	    .sign-dots { border-bottom: 0.6pt dotted #000; }
	  </style>
	</head>
	<body>
  <div class="center title">แบบขออนุญาตใช้รถยนต์ราชการ</div>
  <div class="center date-line">วันที่ <?= h($write_date_label) ?></div>

  <div class="salutation">เรียน&nbsp;&nbsp;ผู้อำนวยการ<?= h($school_name) ?></div>

  <div class="para-block">
    <?php foreach ($paragraph_lines as $line): ?>
      <div class="para-line"><?= h($line) ?></div>
    <?php endforeach; ?>
  </div>

  <div class="sig-block">
    <?php if ($requester_sig): ?>
      <img class="sig-img" src="<?= h($requester_sig) ?>" alt="signature">
    <?php else: ?>
      <div style="height:60pt;"></div>
    <?php endif; ?>
    <div class="sig-name">(<?= h($requester_name !== '' ? $requester_name : '-') ?>)</div>
    <div class="sig-role"><?= h($requester_position !== '' ? $requester_position : '-') ?></div>
    <div class="sig-role">ผู้ขออนุญาต</div>
  </div>

  <table class="approval-grid" cellpadding="0" cellspacing="0">
    <tr>
      <td class="col">
        <table class="box" cellpadding="0" cellspacing="0">
          <tr>
            <td class="box-title">ความเห็นเจ้าหน้าที่ควบคุมยานพาหนะ</td>
          </tr>
          <tr>
            <td class="box-body fixed">
              <div class="note">
                1. ควรอนุญาตให้ใช้รถยนต์ส่วนกลาง หมายเลขทะเบียน <?= h($vehicle_label !== '' ? $vehicle_label : '-') ?>
                โดยมี <?= h($driver_name !== '' ? $driver_name : '-') ?> ทำหน้าที่พนักงานขับรถ<?= $driver_tel !== '' ? (' (' . h($driver_tel) . ')') : '' ?>
              </div>
              <div class="note">2. <?= h($assigned_note_line_2) ?></div>
            </td>
          </tr>
          <tr>
            <td class="box-sign">
              <?php if ($assigned_sig): ?>
                <img class="sig-img" src="<?= h($assigned_sig) ?>" alt="signature">
              <?php else: ?>
                <div style="height:60pt;"></div>
              <?php endif; ?>
              <div class="sig-name">(<?= h($assigned_name !== '' ? $assigned_name : '-') ?>)</div>
              <div class="sig-role"><?= h($assigned_position !== '' ? $assigned_position : '-') ?></div>
              <div class="sig-role">ผู้ตรวจสอบ</div>
            </td>
          </tr>
        </table>
      </td>
      <td class="gap"></td>
      <td class="col">
        <table class="box" cellpadding="0" cellspacing="0">
          <tr>
            <td class="box-title">ความเห็นผู้บังคับบัญชา</td>
          </tr>
          <tr>
            <td class="box-body fixed">
              <?php if ($boss_note !== ''): ?>
                <div class="note"><?= nl2br(h($boss_note)) ?></div>
              <?php else: ?>
                <div class="fill-line"></div>
                <div class="fill-line"></div>
                <div class="fill-line"></div>
                <div class="fill-line"></div>
              <?php endif; ?>
            </td>
	          </tr>
	          <tr>
	            <td class="box-sign">
	              <?php if ($boss_signature): ?>
	                <img class="sig-img" src="<?= h($boss_signature) ?>" alt="signature">
	              <?php else: ?>
	                <div style="height:60pt;"></div>
	              <?php endif; ?>
	              <div class="sig-name">(<?= h($boss_name !== '' ? $boss_name : '-') ?>)</div>
	              <div class="sig-role"><?= h($boss_position_line_1 !== '' ? $boss_position_line_1 : '-') ?></div>
	              <?php if ($boss_position_line_2 !== ''): ?>
	                <div class="sig-role"><?= h($boss_position_line_2) ?></div>
	              <?php endif; ?>
	              <div class="sig-role">ผู้อนุญาต</div>
	            </td>
	          </tr>
	        </table>
	      </td>
	    </tr>
	  </table>
	</body>
	</html>
	        <?php
	        return (string) ob_get_clean();
    }
}

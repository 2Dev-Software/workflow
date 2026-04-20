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
        $order_status_label = trim((string) ($data['order_status_label'] ?? '-'));

        $paragraph_lines = $data['paragraph_lines'] ?? [];

        if (!is_array($paragraph_lines)) {
            $paragraph_lines = [];
        }
        $paragraph_lines = array_values(array_filter(array_map(
            static fn ($v) => trim((string) $v),
            $paragraph_lines
        ), static fn (string $v) => $v !== ''));
        $paragraph_text = implode(' ', $paragraph_lines);

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
        ob_start();
        ?>
<!doctype html>
<html lang="th">
<head>
	  <meta charset="utf-8">
	  <style>
	    body {
        font-family: sarabun;
        font-size: 12pt;
        line-height: 1.55;
        color: #111;
        margin: 0;
      }
	    * { box-sizing: border-box; }
      .center { text-align: center; }
      .form-code {
        font-size: 11pt;
        text-align: right;
        margin: 0 0 4pt 0;
      }
      .header-table,
      .signature-table,
      .approval-sign {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
      }
      .header-table td,
      .signature-table td,
      .approval-sign td {
        vertical-align: top;
      }
	    .title {
        font-weight: bold;
        font-size: 14pt;
        text-align: center;
        padding: 0 0 4pt 0;
      }
	    .date-line {
        text-align: right;
        padding: 0 0 10pt 0;
      }
	    .salutation {
        padding: 0 0 10pt 0;
      }
      .para-block {
        margin: 0 0 8pt 0;
      }
      .para-text {
        margin: 0;
        text-indent: 1.8em;
        text-align: justify;
      }
      .signature-table {
        margin: 16pt 0 12pt 0;
      }
      .signature-spacer,
      .approval-sign-spacer {
        width: 36%;
      }
      .sig-block,
      .approval-sign-block {
        width: 64%;
        text-align: center;
      }
      .sig-meta {
        display: inline-block;
        min-width: 200pt;
        text-align: center;
      }
      .approval-sign-block .sig-name,
      .approval-sign-block .sig-role {
        width: 200pt;
        margin-left: auto;
        margin-right: auto;
        text-align: center;
      }
	    .sig-img {
        display: block;
        height: 54pt;
        width: auto;
        max-width: 220pt;
        margin: 0 auto;
      }
      .sig-name { margin-top: 2pt; }
      .sig-role { margin-top: 0; }
      .sig-role-nowrap { white-space: nowrap; }
      .approval-stack {
        margin-top: 10pt;
      }
      .approval-card {
        margin: 0 0 18pt 0;
      }
      .approval-card--boss {
        page-break-inside: avoid;
        break-inside: avoid;
      }
      .approval-title {
        font-weight: bold;
        text-align: left;
        padding: 0 0 6pt 0;
        margin: 0 0 10pt 0;
        border-bottom: 0.8pt solid #000;
      }
      .approval-body {
        padding: 0;
        min-height: 58pt;
      }
      .approval-sign {
        margin-top: 8pt;
      }
      .note {
        margin: 0 0 7pt 0;
      }
      .status-row {
        font-weight: bold;
        margin-bottom: 8pt;
      }
      .fill-line {
        border-bottom: 0.6pt dotted #000;
        height: 18pt;
        margin: 0 0 7pt 0;
      }
      .muted {
        color: #333;
      }
	  </style>
	</head>
	<body>
  <div class="form-code">แบบ 3</div>
  <table class="header-table" cellpadding="0" cellspacing="0">
    <tr>
      <td class="title">แบบขออนุญาตใช้รถยนต์ราชการ</td>
    </tr>
    <tr>
      <td class="date-line">วันที่ <?= h($write_date_label) ?></td>
    </tr>
    <tr>
      <td class="salutation">เรียน&nbsp;&nbsp;ผู้อำนวยการ<?= h($school_name) ?></td>
    </tr>
  </table>
  <div class="para-block">
    <div class="para-text"><?= h($paragraph_text !== '' ? $paragraph_text : '-') ?></div>
  </div>

  <table class="signature-table" cellpadding="0" cellspacing="0">
    <tr>
      <td class="signature-spacer"></td>
      <td class="sig-block">
        <?php if ($requester_sig): ?>
          <img class="sig-img" src="<?= h($requester_sig) ?>" alt="signature">
        <?php else: ?>
          <div style="height:60pt;"></div>
        <?php endif; ?>
        <div class="sig-meta">
          <div class="sig-name">(<?= h($requester_name !== '' ? $requester_name : '-') ?>)</div>
          <div class="sig-role"><?= h($requester_position !== '' ? $requester_position : '-') ?></div>
          <div class="sig-role">ผู้ขออนุญาต</div>
        </div>
      </td>
    </tr>
  </table>

  <div class="approval-stack">
    <div class="approval-card">
      <div class="approval-title">ความเห็นเจ้าหน้าที่</div>
      <div class="approval-body">
        <div class="note">
          1. ควรอนุญาตให้ใช้รถยนต์ส่วนกลาง หมายเลขทะเบียน <?= h($vehicle_label !== '' ? $vehicle_label : '-') ?>
          โดยมี <?= h($driver_name !== '' ? $driver_name : '-') ?> ทำหน้าที่พนักงานขับรถ<?= $driver_tel !== '' ? (' (' . h($driver_tel) . ')') : '' ?>
        </div>
        <?php if ($assigned_note !== ''): ?>
          <div class="note"><?= h($assigned_note) ?></div>
        <?php endif; ?>
      </div>
      <table class="approval-sign" cellpadding="0" cellspacing="0">
        <tr>
          <td class="approval-sign-spacer"></td>
          <td class="approval-sign-block">
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
    </div>

    <div class="approval-card approval-card--boss">
      <div class="approval-title">ความเห็นผู้บังคับบัญชา</div>
      <div class="approval-body">
        <div class="status-row">ผลการพิจารณา: <?= h($order_status_label !== '' ? $order_status_label : 'รอพิจารณา') ?></div>
        <?php if ($boss_note !== ''): ?>
          <div class="note"><?= nl2br(h($boss_note)) ?></div>
        <?php else: ?>
          <div class="fill-line"></div>
          <div class="fill-line"></div>
          <div class="fill-line"></div>
          <div class="fill-line"></div>
        <?php endif; ?>
      </div>
      <table class="approval-sign" cellpadding="0" cellspacing="0">
        <tr>
          <td class="approval-sign-spacer"></td>
          <td class="approval-sign-block">
            <?php if ($boss_signature): ?>
              <img class="sig-img" src="<?= h($boss_signature) ?>" alt="signature">
            <?php else: ?>
              <div style="height:60pt;"></div>
            <?php endif; ?>
            <div class="sig-name">(<?= h($boss_name !== '' ? $boss_name : '-') ?>)</div>
            <div class="sig-role sig-role-nowrap"><?= h($boss_position_line_1 !== '' ? $boss_position_line_1 : '-') ?></div>
            <?php if ($boss_position_line_2 !== ''): ?>
              <div class="sig-role"><?= h($boss_position_line_2) ?></div>
            <?php endif; ?>
            <div class="sig-role">ผู้อนุญาต</div>
          </td>
        </tr>
      </table>
    </div>
  </div>
	</body>
	</html>
	        <?php
            return (string) ob_get_clean();
    }
}

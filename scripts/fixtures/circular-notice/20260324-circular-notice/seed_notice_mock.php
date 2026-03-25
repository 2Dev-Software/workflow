<?php

declare(strict_types=1);

require_once '/var/www/html/app/db/db.php';
require_once '/var/www/html/app/modules/circulars/repository.php';
require_once '/var/www/html/app/modules/circulars/service.php';
require_once '/var/www/html/app/modules/system/system.php';

$recipientPid = '1829900159722';
$dhYear = function_exists('system_get_dh_year') ? (int) system_get_dh_year() : 2569;
$seedDir = '/var/www/html/scripts/fixtures/circular-notice/20260324-circular-notice';
$uploadDir = '/var/www/html/storage/uploads/circulars/2026/03';

if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
    throw new RuntimeException('Cannot create circular upload directory');
}

$items = [
    [
        'sender_pid' => '1930500083592',
        'sender_fid' => 2,
        'subject' => 'แจ้งกำหนดการประชุมเตรียมความพร้อมเปิดภาคเรียนที่ 1 ปีการศึกษา 2569',
        'detail' => "เรียน บุคลากรที่เกี่ยวข้อง\n\nขอแจ้งกำหนดการประชุมเตรียมความพร้อมก่อนเปิดภาคเรียนที่ 1 ปีการศึกษา 2569 เพื่อสรุปความพร้อมของอาคารสถานที่ เวรประจำวัน แผนรับนักเรียนใหม่ และงานสนับสนุนการจัดการเรียนการสอน\n\nกำหนดประชุมในวันพฤหัสบดีที่ 28 มีนาคม 2569 เวลา 13.30 น. ณ ห้องประชุม 1\n\nจึงเรียนมาเพื่อทราบและขอความร่วมมือเข้าร่วมประชุมตามวันและเวลาดังกล่าวโดยพร้อมเพรียงกัน",
        'attachment_source' => 'circular-open-term-plan.png',
        'attachment_name' => 'กำหนดการประชุมเปิดภาคเรียน-2569.png',
        'created_at' => '2026-03-24 08:35:00',
        'read_at' => null,
    ],
    [
        'sender_pid' => '1810500062871',
        'sender_fid' => 3,
        'subject' => 'ประชาสัมพันธ์แนวปฏิบัติการเบิกวัสดุสำนักงานและอุปกรณ์การเรียน',
        'detail' => "เรียน คุณครูและบุคลากร\n\nเพื่อให้การเบิกวัสดุสำนักงานและอุปกรณ์การเรียนเป็นไปตามแผนงบประมาณและสามารถติดตามการใช้วัสดุได้อย่างถูกต้อง ขอให้ทุกฝ่ายดำเนินการตามแนวปฏิบัติที่แนบมาพร้อมหนังสือเวียนฉบับนี้\n\nทั้งนี้ให้ตรวจสอบยอดคงเหลือก่อนเสนอเบิก พร้อมแนบเหตุผลการใช้งานและระยะเวลาที่ต้องใช้วัสดุอย่างชัดเจน เริ่มใช้แนวปฏิบัตินี้ตั้งแต่วันที่ 1 เมษายน 2569 เป็นต้นไป",
        'attachment_source' => 'circular-office-supplies-guideline.png',
        'attachment_name' => 'แนวปฏิบัติการเบิกวัสดุสำนักงาน.png',
        'created_at' => '2026-03-23 15:20:00',
        'read_at' => '2026-03-23 17:05:00',
    ],
    [
        'sender_pid' => '1920100023843',
        'sender_fid' => 4,
        'subject' => 'แนวทางการส่งเอกสารผ่านระบบสำนักงานอิเล็กทรอนิกส์',
        'detail' => "เรียน บุคลากรโรงเรียนดีบุกพังงาวิทยายน\n\nฝ่ายกลุ่มบริหารงานทั่วไปขอประชาสัมพันธ์แนวทางการส่งหนังสือเวียน บันทึกข้อความ และเอกสารประกอบผ่านระบบ Paperless เพื่อให้การดำเนินงานเป็นมาตรฐานเดียวกันทั้งโรงเรียน\n\nกรุณาตรวจสอบหัวข้อ รายละเอียด ผู้รับ และไฟล์แนบให้ครบถ้วนก่อนกดส่งทุกครั้ง และให้ใช้ช่องรายละเอียดเพื่อระบุข้อมูลที่จำเป็นต่อการดำเนินงานอย่างชัดเจน",
        'attachment_source' => 'circular-paperless-guideline.png',
        'attachment_name' => 'คู่มือระบบสำนักงานอิเล็กทรอนิกส์.png',
        'created_at' => '2026-03-22 10:10:00',
        'read_at' => null,
    ],
    [
        'sender_pid' => '3820400215231',
        'sender_fid' => 5,
        'subject' => 'ขอความร่วมมือกำกับดูแลกิจกรรมหน้าเสาธงและช่วงพักกลางวัน',
        'detail' => "เรียน ครูผู้ปฏิบัติหน้าที่เวรประจำวัน\n\nกลุ่มบริหารกิจการนักเรียนขอความร่วมมือกำกับดูแลกิจกรรมหน้าเสาธง การเดินแถวเข้าชั้นเรียน และจุดพักกลางวันของนักเรียนตามพื้นที่ที่ได้รับมอบหมาย เพื่อรักษาระเบียบวินัยและความปลอดภัยของนักเรียนในช่วงสัปดาห์แรกของการเปิดภาคเรียน\n\nรายละเอียดพื้นที่รับผิดชอบและตารางเวรแนบมาพร้อมหนังสือเวียนฉบับนี้",
        'attachment_source' => 'circular-flag-ceremony-supervision.png',
        'attachment_name' => 'ตารางเวรหน้าเสาธงและพักกลางวัน.png',
        'created_at' => '2026-03-21 11:45:00',
        'read_at' => '2026-03-21 12:30:00',
    ],
    [
        'sender_pid' => '1820700059157',
        'sender_fid' => 6,
        'subject' => 'ตารางเวรตรวจความเรียบร้อยอาคารเรียนและพื้นที่ส่วนกลาง',
        'detail' => "เรียน ผู้เกี่ยวข้อง\n\nกลุ่มสนับสนุนการสอนขอแจ้งตารางเวรตรวจความเรียบร้อยประจำสัปดาห์ ทั้งอาคารเรียน ห้องน้ำ สนามกีฬา และพื้นที่ส่วนกลางของโรงเรียน โดยให้ผู้ปฏิบัติลงบันทึกหลังการตรวจทุกครั้ง และรายงานเหตุขัดข้องผ่านระบบในวันเดียวกันเพื่อเร่งประสานงานซ่อมแซมต่อไป\n\nโปรดตรวจสอบตารางเวรและจุดตรวจจากเอกสารแนบ",
        'attachment_source' => 'circular-building-duty-check.png',
        'attachment_name' => 'ตารางเวรตรวจอาคารและพื้นที่ส่วนกลาง.png',
        'created_at' => '2026-03-20 09:00:00',
        'read_at' => null,
    ],
    [
        'sender_pid' => '1820700006258',
        'sender_fid' => 2,
        'subject' => 'ขอความร่วมมือส่งข้อมูลนักเรียนที่ต้องติดตามเป็นพิเศษ',
        'detail' => "เรียน ครูประจำชั้นและครูที่ปรึกษา\n\nเพื่อให้การดูแลช่วยเหลือนักเรียนเป็นไปอย่างต่อเนื่อง ขอความร่วมมือรวบรวมรายชื่อนักเรียนที่มีประเด็นด้านการเรียน พฤติกรรม หรือเศรษฐกิจครอบครัว พร้อมสรุปข้อมูลเบื้องต้นตามแบบฟอร์มที่แนบมาพร้อมหนังสือเวียนฉบับนี้\n\nขอให้ส่งข้อมูลภายในวันศุกร์ที่ 29 มีนาคม 2569 เพื่อใช้ประกอบการวางแผนดูแลนักเรียนต่อไป",
        'attachment_source' => 'circular-student-care-report.png',
        'attachment_name' => 'แบบฟอร์มติดตามนักเรียนเป็นพิเศษ.png',
        'created_at' => '2026-03-19 14:25:00',
        'read_at' => null,
    ],
];

$created = [];

foreach ($items as $item) {
    $circularId = circular_create_internal([
        'dh_year' => $dhYear,
        'circularType' => CIRCULAR_TYPE_INTERNAL,
        'subject' => $item['subject'],
        'detail' => $item['detail'],
        'linkURL' => null,
        'fromFID' => (int) $item['sender_fid'],
        'status' => INTERNAL_STATUS_SENT,
        'createdByPID' => $item['sender_pid'],
    ], [
        'pids' => [$recipientPid],
        'targets' => [
            [
                'targetType' => 'PERSON',
                'pID' => $recipientPid,
                'isCc' => 0,
            ],
        ],
    ], []);

    $createdAt = (string) $item['created_at'];
    db_execute('UPDATE dh_circulars SET createdAt = ?, updatedByPID = ? WHERE circularID = ?', 'ssi', $createdAt, $item['sender_pid'], $circularId);
    db_execute('UPDATE dh_circular_inboxes SET deliveredAt = ? WHERE circularID = ? AND pID = ?', 'sis', $createdAt, $circularId, $recipientPid);
    db_execute('UPDATE dh_circular_routes SET actionAt = ? WHERE circularID = ?', 'si', $createdAt, $circularId);

    if (!empty($item['read_at'])) {
        db_execute('UPDATE dh_circular_inboxes SET isRead = 1, readAt = ? WHERE circularID = ? AND pID = ?', 'sis', $item['read_at'], $circularId, $recipientPid);
    }

    $sourcePath = $seedDir . '/' . $item['attachment_source'];
    if (is_file($sourcePath)) {
        $storedName = bin2hex(random_bytes(16)) . '.png';
        $targetPath = $uploadDir . '/' . $storedName;
        if (!copy($sourcePath, $targetPath)) {
            throw new RuntimeException('Failed to copy attachment for circular #' . $circularId);
        }

        $relativePath = 'storage/uploads/circulars/2026/03/' . $storedName;
        $mime = mime_content_type($targetPath) ?: 'image/png';
        $size = filesize($targetPath) ?: 0;
        $hash = hash_file('sha256', $targetPath) ?: null;

        $fileStmt = db_query(
            'INSERT INTO dh_files (fileName, filePath, mimeType, fileSize, checksumSHA256, storageProvider, uploadedByPID) VALUES (?, ?, ?, ?, ?, ?, ?)',
            'sssisss',
            (string) $item['attachment_name'],
            $relativePath,
            (string) $mime,
            (int) $size,
            $hash,
            'local',
            (string) $item['sender_pid']
        );
        $fileId = db_last_insert_id();
        mysqli_stmt_close($fileStmt);

        $refStmt = db_query(
            'INSERT INTO dh_file_refs (fileID, moduleName, entityName, entityID, attachedByPID) VALUES (?, ?, ?, ?, ?)',
            'issss',
            (int) $fileId,
            CIRCULAR_MODULE_NAME,
            CIRCULAR_ENTITY_NAME,
            (string) $circularId,
            (string) $item['sender_pid']
        );
        mysqli_stmt_close($refStmt);
    }

    $created[] = [
        'circular_id' => $circularId,
        'subject' => $item['subject'],
        'sender_pid' => $item['sender_pid'],
        'created_at' => $createdAt,
        'read_at' => $item['read_at'],
    ];
}

echo json_encode([
    'ok' => true,
    'recipient_pid' => $recipientPid,
    'count' => count($created),
    'items' => $created,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . PHP_EOL;

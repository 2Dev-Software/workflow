INSERT INTO `dh_positions` (`positionID`, `positionName`, `positionDescription`)
VALUES (
    9,
    'รองผู้อำนวยการกลุ่มบริหารงานทั่วไป',
    'กำกับ ดูแล วางแผน อำนวยการ และประสานงานด้านการบริหารงานทั่วไปของสถานศึกษา ให้การดำเนินงานด้านธุรการ งานสารบรรณ งานอาคารสถานที่ งานพัสดุ งานประชาสัมพันธ์ งานบริการ และงานสนับสนุนต่าง ๆ เป็นไปด้วยความเรียบร้อย มีประสิทธิภาพ และสอดคล้องกับนโยบายของสถานศึกษา'
)
ON DUPLICATE KEY UPDATE
    `positionName` = VALUES(`positionName`),
    `positionDescription` = VALUES(`positionDescription`);

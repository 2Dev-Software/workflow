INSERT INTO `dh_roles` (`roleID`, `roleName`, `roleDescription`)
VALUES (7, 'เจ้าหน้าที่ซ่อมแซม', 'รับผิดชอบตรวจสอบ รับเรื่อง และดำเนินงานแจ้งเหตุซ่อมแซมของสถานศึกษา')
ON DUPLICATE KEY UPDATE
    `roleName` = VALUES(`roleName`),
    `roleDescription` = VALUES(`roleDescription`);

UPDATE `teacher`
SET
    `roleID` = CASE
        WHEN `roleID` IS NULL OR `roleID` = '' OR `roleID` = '0' THEN '7'
        WHEN FIND_IN_SET('7', REPLACE(CAST(`roleID` AS CHAR), ' ', '')) > 0 THEN REPLACE(CAST(`roleID` AS CHAR), ' ', '')
        ELSE CONCAT(TRIM(BOTH ',' FROM REPLACE(CAST(`roleID` AS CHAR), ' ', '')), ',7')
    END,
    `positionID` = 8
WHERE `positionID` = 9;

DELETE FROM `dh_positions`
WHERE `positionID` = 9
  AND `positionName` = 'เจ้าหน้าที่ซ่อมแซม';

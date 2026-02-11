-- Remove deprecated rejection reason from room bookings.
-- The room booking workflow uses status only; no reason is captured or displayed.

SET @col_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'dh_room_bookings'
    AND COLUMN_NAME = 'statusReason'
);

SET @sql := IF(
  @col_exists > 0,
  'ALTER TABLE `dh_room_bookings` DROP COLUMN `statusReason`',
  'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


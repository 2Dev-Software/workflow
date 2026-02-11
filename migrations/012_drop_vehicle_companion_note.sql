-- Remove deprecated free-text companion note from vehicle bookings.
-- The passenger list is captured via companionIds/companionCount and passengerCount is derived.

SET @col_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'dh_vehicle_bookings'
    AND COLUMN_NAME = 'companionNote'
);

SET @sql := IF(
  @col_exists > 0,
  'ALTER TABLE `dh_vehicle_bookings` DROP COLUMN `companionNote`',
  'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


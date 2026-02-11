-- Add soft delete support for vehicles so historical bookings keep vehicle info.

SET @col_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'dh_vehicles'
    AND COLUMN_NAME = 'deletedAt'
);

SET @sql := IF(
  @col_exists > 0,
  'SELECT 1',
  'ALTER TABLE `dh_vehicles` ADD COLUMN `deletedAt` timestamp NULL DEFAULT NULL AFTER `updatedAt`'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'dh_vehicles'
    AND INDEX_NAME = 'idx_vehicle_deletedAt'
);

SET @sql := IF(
  @idx_exists > 0,
  'SELECT 1',
  'ALTER TABLE `dh_vehicles` ADD KEY `idx_vehicle_deletedAt` (`deletedAt`)'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


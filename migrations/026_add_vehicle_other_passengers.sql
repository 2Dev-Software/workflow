-- Add non-teacher passenger support to vehicle reservations.

SET @other_passenger_count_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'dh_vehicle_bookings'
    AND COLUMN_NAME = 'otherPassengerCount'
);

SET @sql := IF(
  @other_passenger_count_exists > 0,
  'SELECT 1',
  'ALTER TABLE `dh_vehicle_bookings` ADD COLUMN `otherPassengerCount` int(11) NOT NULL DEFAULT 0 AFTER `companionCount`'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @other_passenger_names_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'dh_vehicle_bookings'
    AND COLUMN_NAME = 'otherPassengerNames'
);

SET @sql := IF(
  @other_passenger_names_exists > 0,
  'SELECT 1',
  'ALTER TABLE `dh_vehicle_bookings` ADD COLUMN `otherPassengerNames` text DEFAULT NULL AFTER `otherPassengerCount`'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add approval note for room booking approve/reject workflow.
SET @col_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'dh_room_bookings'
      AND COLUMN_NAME = 'approvalNote'
);

SET @sql := IF(
    @col_exists = 0,
    'ALTER TABLE `dh_room_bookings` ADD COLUMN `approvalNote` TEXT NULL AFTER `approvedAt`',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

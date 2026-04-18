-- Add public share token support for official orders.

SET @share_token_col_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'dh_orders'
    AND COLUMN_NAME = 'shareToken'
);

SET @sql := IF(
  @share_token_col_exists > 0,
  'SELECT 1',
  'ALTER TABLE `dh_orders` ADD COLUMN `shareToken` varchar(96) DEFAULT NULL AFTER `deletedAt`'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @share_created_col_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'dh_orders'
    AND COLUMN_NAME = 'shareCreatedAt'
);

SET @sql := IF(
  @share_created_col_exists > 0,
  'SELECT 1',
  'ALTER TABLE `dh_orders` ADD COLUMN `shareCreatedAt` datetime DEFAULT NULL AFTER `shareToken`'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @share_token_idx_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'dh_orders'
    AND INDEX_NAME = 'uq_order_share_token'
);

SET @sql := IF(
  @share_token_idx_exists > 0,
  'SELECT 1',
  'ALTER TABLE `dh_orders` ADD UNIQUE KEY `uq_order_share_token` (`shareToken`)'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

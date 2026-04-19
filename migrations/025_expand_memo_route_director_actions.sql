-- Expand memo route action enum for director management decisions.

SET @memo_route_action_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'dh_memo_routes'
    AND COLUMN_NAME = 'action'
);

SET @sql := IF(
  @memo_route_action_exists > 0,
  'ALTER TABLE `dh_memo_routes`
     MODIFY `action` enum(
       ''CREATE'',''UPDATE'',''SUBMIT'',''OPEN'',''RETURN'',''RESUBMIT'',
       ''APPROVE_UNSIGNED'',''SIGN'',''REJECT'',''CANCEL'',''ARCHIVE'',
       ''RECALL'',''FORWARD'',''DIRECTOR_APPROVE'',''DIRECTOR_REJECT'',
       ''DIRECTOR_SIGNED'',''DIRECTOR_ACKNOWLEDGED'',''DIRECTOR_AGREED'',
       ''DIRECTOR_NOTIFIED'',''DIRECTOR_ASSIGNED'',''DIRECTOR_SCHEDULED'',
       ''DIRECTOR_PERMITTED'',''DIRECTOR_APPROVED'',''DIRECTOR_REJECTED'',
       ''DIRECTOR_REQUEST_MEETING''
     ) NOT NULL',
  'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

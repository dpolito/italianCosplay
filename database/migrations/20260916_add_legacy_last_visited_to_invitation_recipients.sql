SET @legacy_last_visited_column_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'legacy_invitation_email_recipients'
      AND COLUMN_NAME = 'legacy_last_visited_at'
);

SET @legacy_last_visited_sql = IF(
    @legacy_last_visited_column_exists = 0,
    'ALTER TABLE `legacy_invitation_email_recipients` ADD COLUMN `legacy_last_visited_at` DATETIME DEFAULT NULL AFTER `source_label`',
    'SELECT 1'
);

PREPARE legacy_last_visited_stmt FROM @legacy_last_visited_sql;
EXECUTE legacy_last_visited_stmt;
DEALLOCATE PREPARE legacy_last_visited_stmt;

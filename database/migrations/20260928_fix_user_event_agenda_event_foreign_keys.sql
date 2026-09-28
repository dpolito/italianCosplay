SET @constraint_name = (
  SELECT CONSTRAINT_NAME
  FROM information_schema.KEY_COLUMN_USAGE
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'user_event_agenda'
    AND COLUMN_NAME = 'event_id'
    AND REFERENCED_TABLE_NAME IS NOT NULL
    AND REFERENCED_TABLE_NAME <> 'events'
  LIMIT 1
);

SET @sql = IF(
  @constraint_name IS NULL,
  'SELECT 1',
  CONCAT('ALTER TABLE `user_event_agenda` DROP FOREIGN KEY `', @constraint_name, '`')
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @constraint_exists = (
  SELECT COUNT(*)
  FROM information_schema.KEY_COLUMN_USAGE
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'user_event_agenda'
    AND COLUMN_NAME = 'event_id'
    AND REFERENCED_TABLE_NAME = 'events'
    AND REFERENCED_COLUMN_NAME = 'id'
);

SET @sql = IF(
  @constraint_exists > 0,
  'SELECT 1',
  'ALTER TABLE `user_event_agenda`
    ADD CONSTRAINT `fk_user_event_agenda_event`
    FOREIGN KEY (`event_id`) REFERENCES `events` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @constraint_name = (
  SELECT CONSTRAINT_NAME
  FROM information_schema.KEY_COLUMN_USAGE
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'user_event_agenda_events'
    AND COLUMN_NAME = 'event_id'
    AND REFERENCED_TABLE_NAME IS NOT NULL
    AND REFERENCED_TABLE_NAME <> 'events'
  LIMIT 1
);

SET @sql = IF(
  @constraint_name IS NULL,
  'SELECT 1',
  CONCAT('ALTER TABLE `user_event_agenda_events` DROP FOREIGN KEY `', @constraint_name, '`')
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @constraint_exists = (
  SELECT COUNT(*)
  FROM information_schema.KEY_COLUMN_USAGE
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'user_event_agenda_events'
    AND COLUMN_NAME = 'event_id'
    AND REFERENCED_TABLE_NAME = 'events'
    AND REFERENCED_COLUMN_NAME = 'id'
);

SET @sql = IF(
  @constraint_exists > 0,
  'SELECT 1',
  'ALTER TABLE `user_event_agenda_events`
    ADD CONSTRAINT `fk_user_event_agenda_events_event`
    FOREIGN KEY (`event_id`) REFERENCES `events` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

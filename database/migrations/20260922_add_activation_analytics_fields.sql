SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'last_login_at') = 0, 'ALTER TABLE `users` ADD COLUMN `last_login_at` DATETIME NULL DEFAULT NULL AFTER `updated_at`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'login_count') = 0, 'ALTER TABLE `users` ADD COLUMN `login_count` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `last_login_at`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'last_activity_at') = 0, 'ALTER TABLE `users` ADD COLUMN `last_activity_at` DATETIME NULL DEFAULT NULL AFTER `login_count`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND INDEX_NAME = 'idx_users_last_login_at') = 0, 'ALTER TABLE `users` ADD INDEX `idx_users_last_login_at` (`last_login_at`)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND INDEX_NAME = 'idx_users_last_activity_at') = 0, 'ALTER TABLE `users` ADD INDEX `idx_users_last_activity_at` (`last_activity_at`)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'event_views' AND COLUMN_NAME = 'guest_views') = 0, 'ALTER TABLE `event_views` ADD COLUMN `guest_views` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `views`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'event_views' AND COLUMN_NAME = 'logged_views') = 0, 'ALTER TABLE `event_views` ADD COLUMN `logged_views` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `guest_views`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

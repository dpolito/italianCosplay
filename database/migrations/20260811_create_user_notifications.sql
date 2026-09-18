CREATE TABLE IF NOT EXISTS `user_notifications` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `notification_type` VARCHAR(50) NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `message` TEXT NOT NULL,
  `source_entity_type` VARCHAR(50) NOT NULL,
  `source_entity_id` BIGINT UNSIGNED NOT NULL,
  `payload` JSON NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `read_at` DATETIME NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_user_notifications_user_id` (`user_id`),
  KEY `idx_user_notifications_is_read` (`is_read`),
  KEY `idx_user_notifications_created_at` (`created_at`),
  KEY `idx_user_notifications_type` (`notification_type`),
  KEY `idx_user_notifications_source` (`source_entity_type`, `source_entity_id`),
  CONSTRAINT `fk_user_notifications_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

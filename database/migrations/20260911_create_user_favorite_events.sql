CREATE TABLE IF NOT EXISTS `user_favorite_events` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `entity_type` VARCHAR(50) NOT NULL,
  `entity_id` BIGINT UNSIGNED NOT NULL,
  `action` ENUM('add', 'remove') NOT NULL,
  `request_path` VARCHAR(255) NULL,
  `referrer` VARCHAR(500) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_user_favorite_events_user_id` (`user_id`),
  KEY `idx_user_favorite_events_entity` (`entity_type`, `entity_id`),
  KEY `idx_user_favorite_events_action` (`action`),
  KEY `idx_user_favorite_events_created_at` (`created_at`),
  KEY `idx_user_favorite_events_type_action_date` (`entity_type`, `action`, `created_at`),
  CONSTRAINT `fk_user_favorite_events_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

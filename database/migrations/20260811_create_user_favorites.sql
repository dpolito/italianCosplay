CREATE TABLE IF NOT EXISTS `user_favorites` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `entity_type` VARCHAR(50) NOT NULL,
  `entity_id` BIGINT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL,
  UNIQUE KEY `uq_user_favorites_user_entity` (`user_id`, `entity_type`, `entity_id`),
  KEY `idx_user_favorites_user_id` (`user_id`),
  KEY `idx_user_favorites_entity_type` (`entity_type`),
  KEY `idx_user_favorites_created_at` (`created_at`),
  CONSTRAINT `fk_user_favorites_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

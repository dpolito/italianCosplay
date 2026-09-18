CREATE TABLE IF NOT EXISTS `user_age_declarations` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `declared_adult` TINYINT(1) NOT NULL DEFAULT 1,
  `declared_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_user_age_declarations_user_id` (`user_id`),
  KEY `idx_user_age_declarations_declared_adult` (`declared_adult`),
  KEY `idx_user_age_declarations_declared_at` (`declared_at`),
  CONSTRAINT `fk_user_age_declarations_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

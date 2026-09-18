CREATE TABLE IF NOT EXISTS `user_marketing_consents` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `opted_in` TINYINT(1) NOT NULL DEFAULT 0,
  `opted_in_at` DATETIME NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_user_marketing_consents_user_id` (`user_id`),
  KEY `idx_user_marketing_consents_opted_in` (`opted_in`),
  KEY `idx_user_marketing_consents_created_at` (`created_at`),
  CONSTRAINT `fk_user_marketing_consents_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

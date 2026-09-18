CREATE TABLE IF NOT EXISTS `user_cosplay_portfolio` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `anilist_character_id` INT NOT NULL,
  `custom_name` VARCHAR(255) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `reference_image` VARCHAR(500) DEFAULT NULL,
  `is_public` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_user_cosplay_portfolio_user_character` (`user_id`, `anilist_character_id`),
  KEY `idx_user_cosplay_portfolio_user_id` (`user_id`),
  KEY `idx_user_cosplay_portfolio_character_id` (`anilist_character_id`),
  KEY `idx_user_cosplay_portfolio_is_public` (`is_public`),
  CONSTRAINT `fk_user_cosplay_portfolio_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_user_cosplay_portfolio_character`
    FOREIGN KEY (`anilist_character_id`) REFERENCES `anilist_characters` (`id`)
    ON DELETE RESTRICT
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_event_cosplay_plans` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `event_id` INT NOT NULL,
  `portfolio_id` BIGINT UNSIGNED NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'porterò',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_user_event_cosplay_plans_user_event_portfolio` (`user_id`, `event_id`, `portfolio_id`),
  KEY `idx_user_event_cosplay_plans_user_id` (`user_id`),
  KEY `idx_user_event_cosplay_plans_event_id` (`event_id`),
  KEY `idx_user_event_cosplay_plans_portfolio_id` (`portfolio_id`),
  KEY `idx_user_event_cosplay_plans_status` (`status`),
  CONSTRAINT `fk_user_event_cosplay_plans_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_user_event_cosplay_plans_event`
    FOREIGN KEY (`event_id`) REFERENCES `events` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_user_event_cosplay_plans_portfolio`
    FOREIGN KEY (`portfolio_id`) REFERENCES `user_cosplay_portfolio` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

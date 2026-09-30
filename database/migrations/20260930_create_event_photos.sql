CREATE TABLE IF NOT EXISTS `photos` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `event_id` INT NOT NULL,
  `uploaded_by_user_id` INT NOT NULL,
  `storage_key` VARCHAR(255) NOT NULL,
  `thumbnail_storage_key` VARCHAR(255) NOT NULL,
  `original_filename` VARCHAR(255) NULL,
  `width` INT UNSIGNED NOT NULL,
  `height` INT UNSIGNED NOT NULL,
  `thumbnail_width` INT UNSIGNED NOT NULL,
  `thumbnail_height` INT UNSIGNED NOT NULL,
  `filesize` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `status` ENUM('processing','published','hidden','rejected') NOT NULL DEFAULT 'processing',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL,
  `deleted_at` DATETIME NULL,
  KEY `idx_photos_event_status_created` (`event_id`, `status`, `created_at`),
  KEY `idx_photos_uploaded_by_user` (`uploaded_by_user_id`),
  KEY `idx_photos_status` (`status`),
  KEY `idx_photos_created_at` (`created_at`),
  CONSTRAINT `fk_photos_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_photos_uploaded_by_user` FOREIGN KEY (`uploaded_by_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `photo_cosplayers` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `photo_id` BIGINT UNSIGNED NOT NULL,
  `user_id` INT NULL,
  `cosplay_id` BIGINT UNSIGNED NULL,
  `display_name` VARCHAR(120) NULL,
  `instagram_username` VARCHAR(60) NULL,
  `status` ENUM('confirmed','pending','rejected') NOT NULL DEFAULT 'confirmed',
  `created_by_user_id` INT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_photo_cosplayers_photo` (`photo_id`),
  KEY `idx_photo_cosplayers_user` (`user_id`),
  KEY `idx_photo_cosplayers_cosplay` (`cosplay_id`),
  UNIQUE KEY `uniq_photo_cosplayer_registered` (`photo_id`, `user_id`, `cosplay_id`),
  CONSTRAINT `fk_photo_cosplayers_photo` FOREIGN KEY (`photo_id`) REFERENCES `photos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_photo_cosplayers_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_photo_cosplayers_cosplay` FOREIGN KEY (`cosplay_id`) REFERENCES `user_cosplay_portfolio` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_photo_cosplayers_created_by` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `photo_reports` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `photo_id` BIGINT UNSIGNED NOT NULL,
  `user_id` INT NULL,
  `reason` VARCHAR(80) NOT NULL,
  `message` VARCHAR(500) NULL,
  `status` ENUM('open','reviewed','closed') NOT NULL DEFAULT 'open',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_photo_reports_photo` (`photo_id`),
  KEY `idx_photo_reports_status` (`status`),
  CONSTRAINT `fk_photo_reports_photo` FOREIGN KEY (`photo_id`) REFERENCES `photos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_photo_reports_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

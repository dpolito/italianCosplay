CREATE TABLE IF NOT EXISTS `photo_upload_sessions` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `event_id` INT NOT NULL,
  `status` ENUM('draft','uploading','ready','completed','cancelled','expired') NOT NULL DEFAULT 'draft',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL,
  `expires_at` DATETIME NOT NULL,
  KEY `idx_photo_upload_sessions_user_status_expiry` (`user_id`, `status`, `expires_at`),
  KEY `idx_photo_upload_sessions_event` (`event_id`),
  CONSTRAINT `fk_photo_upload_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_photo_upload_sessions_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `photo_upload_items` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `upload_session_id` BIGINT UNSIGNED NOT NULL,
  `photo_id` BIGINT UNSIGNED NULL,
  `original_filename` VARCHAR(255) NOT NULL,
  `mime_type` VARCHAR(100) NULL,
  `file_size` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `file_hash` CHAR(64) NOT NULL,
  `status` ENUM('uploading','completed','error','removed') NOT NULL DEFAULT 'uploading',
  `error_message` VARCHAR(255) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL,
  KEY `idx_photo_upload_items_session_status` (`upload_session_id`, `status`),
  KEY `idx_photo_upload_items_photo` (`photo_id`),
  UNIQUE KEY `uniq_photo_upload_items_session_hash_size` (`upload_session_id`, `file_hash`, `file_size`),
  CONSTRAINT `fk_photo_upload_items_session` FOREIGN KEY (`upload_session_id`) REFERENCES `photo_upload_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_photo_upload_items_photo` FOREIGN KEY (`photo_id`) REFERENCES `photos` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

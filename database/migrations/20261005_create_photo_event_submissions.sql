CREATE TABLE IF NOT EXISTS `photo_event_submissions` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `event_id` INT NULL,
  `event_name` VARCHAR(180) NOT NULL,
  `normalized_event_name` VARCHAR(180) NOT NULL,
  `year` SMALLINT UNSIGNED NOT NULL,
  `location_name` VARCHAR(160) NULL,
  `event_date` DATE NULL,
  `status` ENUM('pending','approved','merged','rejected') NOT NULL DEFAULT 'pending',
  `resolved_event_id` INT NULL,
  `resolved_at` DATETIME NULL,
  `resolved_by` INT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL,
  KEY `idx_photo_event_submissions_status_created` (`status`, `created_at`),
  KEY `idx_photo_event_submissions_event_year` (`event_id`, `year`, `status`),
  KEY `idx_photo_event_submissions_normalized_year` (`normalized_event_name`, `year`, `status`),
  KEY `idx_photo_event_submissions_user` (`user_id`),
  CONSTRAINT `fk_photo_event_submissions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_photo_event_submissions_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_photo_event_submissions_resolved_event` FOREIGN KEY (`resolved_event_id`) REFERENCES `events` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_photo_event_submissions_resolved_by` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `photos`
  DROP FOREIGN KEY `fk_photos_event`;

ALTER TABLE `photos`
  ADD COLUMN `event_submission_id` BIGINT UNSIGNED NULL AFTER `event_id`,
  MODIFY COLUMN `event_id` INT NULL,
  ADD KEY `idx_photos_event_submission` (`event_submission_id`, `status`, `created_at`),
  ADD CONSTRAINT `fk_photos_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_photos_event_submission` FOREIGN KEY (`event_submission_id`) REFERENCES `photo_event_submissions` (`id`) ON DELETE SET NULL;

ALTER TABLE `photo_upload_sessions`
  DROP FOREIGN KEY `fk_photo_upload_sessions_event`;

ALTER TABLE `photo_upload_sessions`
  ADD COLUMN `event_submission_id` BIGINT UNSIGNED NULL AFTER `event_id`,
  MODIFY COLUMN `event_id` INT NULL,
  ADD KEY `idx_photo_upload_sessions_submission` (`event_submission_id`),
  ADD CONSTRAINT `fk_photo_upload_sessions_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_photo_upload_sessions_submission` FOREIGN KEY (`event_submission_id`) REFERENCES `photo_event_submissions` (`id`) ON DELETE SET NULL;

CREATE TABLE IF NOT EXISTS `user_event_agenda` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `event_id` INT NOT NULL,
  `status` VARCHAR(20) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_user_event_agenda_user_event` (`user_id`, `event_id`),
  KEY `idx_user_event_agenda_user_id` (`user_id`),
  KEY `idx_user_event_agenda_event_id` (`event_id`),
  KEY `idx_user_event_agenda_status` (`status`),
  KEY `idx_user_event_agenda_created_at` (`created_at`),
  CONSTRAINT `fk_user_event_agenda_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_user_event_agenda_event`
    FOREIGN KEY (`event_id`) REFERENCES `events` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

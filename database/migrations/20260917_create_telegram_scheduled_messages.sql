CREATE TABLE IF NOT EXISTS `telegram_scheduled_messages` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `message` TEXT NOT NULL,
    `parse_mode` VARCHAR(32) DEFAULT NULL,
    `disable_web_page_preview` TINYINT(1) NOT NULL DEFAULT 1,
    `disable_notification` TINYINT(1) NOT NULL DEFAULT 0,
    `status` ENUM('scheduled', 'sent', 'failed', 'cancelled') NOT NULL DEFAULT 'scheduled',
    `scheduled_at` DATETIME NOT NULL,
    `sent_at` DATETIME DEFAULT NULL,
    `telegram_message_id` BIGINT DEFAULT NULL,
    `error_message` VARCHAR(255) DEFAULT NULL,
    `created_by` INT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_telegram_scheduled_status_date` (`status`, `scheduled_at`),
    KEY `idx_telegram_scheduled_created_by` (`created_by`),
    CONSTRAINT `fk_telegram_scheduled_created_by`
        FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

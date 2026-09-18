CREATE TABLE IF NOT EXISTS `organization_email_deliveries` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `organization_id` INT UNSIGNED NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `status` ENUM('sent','failed') NOT NULL DEFAULT 'sent',
    `sent_by` INT DEFAULT NULL,
    `sent_at` DATETIME DEFAULT NULL,
    `last_attempted_at` DATETIME DEFAULT NULL,
    `error_message` VARCHAR(1000) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_organization_email_delivery` (`organization_id`, `email`),
    KEY `idx_organization_email_deliveries_status` (`status`, `sent_at`),
    KEY `idx_organization_email_deliveries_sent_by` (`sent_by`),
    CONSTRAINT `fk_organization_email_deliveries_organization`
        FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT `fk_organization_email_deliveries_sent_by`
        FOREIGN KEY (`sent_by`) REFERENCES `users` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

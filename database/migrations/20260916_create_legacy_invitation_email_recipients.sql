CREATE TABLE IF NOT EXISTS `legacy_invitation_email_recipients` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(255) NOT NULL,
    `recipient_name` VARCHAR(180) DEFAULT NULL,
    `source_label` VARCHAR(120) DEFAULT NULL,
    `legacy_last_visited_at` DATETIME DEFAULT NULL,
    `tracking_token` CHAR(64) NOT NULL,
    `status` ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
    `subject` VARCHAR(255) DEFAULT NULL,
    `sent_by` INT DEFAULT NULL,
    `sent_at` DATETIME DEFAULT NULL,
    `last_attempted_at` DATETIME DEFAULT NULL,
    `error_message` VARCHAR(1000) DEFAULT NULL,
    `click_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `first_clicked_at` DATETIME DEFAULT NULL,
    `last_clicked_at` DATETIME DEFAULT NULL,
    `last_click_user_agent` VARCHAR(500) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_legacy_invitation_email` (`email`),
    UNIQUE KEY `uniq_legacy_invitation_tracking_token` (`tracking_token`),
    KEY `idx_legacy_invitation_status` (`status`, `sent_at`),
    KEY `idx_legacy_invitation_clicks` (`click_count`, `last_clicked_at`),
    KEY `idx_legacy_invitation_sent_by` (`sent_by`),
    CONSTRAINT `fk_legacy_invitation_sent_by`
        FOREIGN KEY (`sent_by`) REFERENCES `users` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

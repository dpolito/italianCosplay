CREATE TABLE IF NOT EXISTS `event_report_privacy_acceptances` (
	`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`event_id` INT NOT NULL,
	`privacy_policy_version_id` BIGINT UNSIGNED NOT NULL,
	`accepted_at` DATETIME NOT NULL,
	`ip_address` VARCHAR(45) DEFAULT NULL,
	`user_agent` VARCHAR(255) DEFAULT NULL,
	`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uniq_event_report_privacy_acceptances_event` (`event_id`),
	KEY `idx_event_report_privacy_acceptances_policy` (`privacy_policy_version_id`),
	CONSTRAINT `fk_event_report_privacy_acceptances_event`
		FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
	CONSTRAINT `fk_event_report_privacy_acceptances_policy`
		FOREIGN KEY (`privacy_policy_version_id`) REFERENCES `privacy_policy_versions` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

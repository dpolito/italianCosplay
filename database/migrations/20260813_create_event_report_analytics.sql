CREATE TABLE IF NOT EXISTS `event_report_analytics` (
	`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`event_id` INT DEFAULT NULL,
	`session_key` VARCHAR(64) NOT NULL,
	`event_name` VARCHAR(50) NOT NULL,
	`step_name` VARCHAR(50) DEFAULT NULL,
	`fields_completed` TINYINT UNSIGNED DEFAULT NULL,
	`page_url` VARCHAR(255) DEFAULT NULL,
	`referrer` VARCHAR(255) DEFAULT NULL,
	`ip_hash` CHAR(64) DEFAULT NULL,
	`user_agent_hash` CHAR(64) DEFAULT NULL,
	`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	KEY `idx_event_report_analytics_event_name` (`event_name`),
	KEY `idx_event_report_analytics_event_id` (`event_id`),
	KEY `idx_event_report_analytics_session_key` (`session_key`),
	KEY `idx_event_report_analytics_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `api_clients` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(120) NOT NULL,
  `description` TEXT NULL,
  `environment` ENUM('test','live') NOT NULL DEFAULT 'test',
  `key_prefix` VARCHAR(32) NOT NULL,
  `key_hash` VARCHAR(255) NOT NULL,
  `status` ENUM('active','disabled','revoked') NOT NULL DEFAULT 'active',
  `requests_per_minute` INT UNSIGNED NOT NULL DEFAULT 60,
  `requests_per_day` INT UNSIGNED NOT NULL DEFAULT 5000,
  `expires_at` DATETIME NULL,
  `last_used_at` DATETIME NULL,
  `last_ip` VARCHAR(45) NULL,
  `rotated_at` DATETIME NULL,
  `admin_notes` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL,
  `deleted_at` DATETIME NULL,
  UNIQUE KEY `uniq_api_clients_key_prefix` (`key_prefix`),
  KEY `idx_api_clients_status` (`status`),
  KEY `idx_api_clients_environment` (`environment`),
  KEY `idx_api_clients_last_used_at` (`last_used_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `api_client_scopes` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `api_client_id` BIGINT UNSIGNED NOT NULL,
  `scope` VARCHAR(80) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_api_client_scope` (`api_client_id`, `scope`),
  KEY `idx_api_client_scopes_scope` (`scope`),
  CONSTRAINT `fk_api_client_scopes_client` FOREIGN KEY (`api_client_id`) REFERENCES `api_clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `api_usage_counters` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `api_client_id` BIGINT UNSIGNED NOT NULL,
  `window_type` ENUM('minute','day') NOT NULL,
  `window_start` DATETIME NOT NULL,
  `request_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_api_usage_window` (`api_client_id`, `window_type`, `window_start`),
  KEY `idx_api_usage_window_start` (`window_start`),
  CONSTRAINT `fk_api_usage_client` FOREIGN KEY (`api_client_id`) REFERENCES `api_clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `api_request_logs` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `api_client_id` BIGINT UNSIGNED NULL,
  `key_prefix` VARCHAR(32) NULL,
  `endpoint` VARCHAR(255) NOT NULL,
  `http_method` VARCHAR(10) NOT NULL,
  `status_code` SMALLINT UNSIGNED NOT NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` VARCHAR(255) NULL,
  `response_time_ms` INT UNSIGNED NULL,
  `rate_limited` TINYINT(1) NOT NULL DEFAULT 0,
  `error_code` VARCHAR(80) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_api_request_logs_client_created` (`api_client_id`, `created_at`),
  KEY `idx_api_request_logs_created_at` (`created_at`),
  KEY `idx_api_request_logs_status_code` (`status_code`),
  KEY `idx_api_request_logs_endpoint` (`endpoint`),
  KEY `idx_api_request_logs_rate_limited` (`rate_limited`),
  CONSTRAINT `fk_api_request_logs_client` FOREIGN KEY (`api_client_id`) REFERENCES `api_clients` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

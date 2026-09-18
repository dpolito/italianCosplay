CREATE TABLE IF NOT EXISTS cookie_consent_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(191) NOT NULL,
    cookie_policy_version_id BIGINT UNSIGNED NULL,
    consent_level VARCHAR(50) NOT NULL,
    consent_details JSON NOT NULL,
    accepted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_cookie_consent_logs_user_id (user_id),
    KEY idx_cookie_consent_logs_version_id (cookie_policy_version_id),
    KEY idx_cookie_consent_logs_consent_level (consent_level),
    KEY idx_cookie_consent_logs_accepted_at (accepted_at),
    CONSTRAINT fk_cookie_consent_logs_version
        FOREIGN KEY (cookie_policy_version_id) REFERENCES cookie_policy_versions(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

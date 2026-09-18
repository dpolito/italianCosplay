CREATE TABLE IF NOT EXISTS privacy_policy_versions (
                                                       id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                                                       version_number INT UNSIGNED NOT NULL,
                                                       title VARCHAR(255) NOT NULL,
    content LONGTEXT NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 0,
    published_at DATETIME NULL,
    created_by INT NULL,
    updated_by INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    UNIQUE KEY uq_privacy_policy_versions_version_number (version_number),
    KEY idx_privacy_policy_versions_is_active (is_active),
    KEY idx_privacy_policy_versions_published_at (published_at),
    CONSTRAINT fk_privacy_policy_versions_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL
    ON UPDATE CASCADE,
    CONSTRAINT fk_privacy_policy_versions_updated_by
    FOREIGN KEY (updated_by) REFERENCES users(id)
    ON DELETE SET NULL
    ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS privacy_policy_acceptances (
                                                          id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                                                          privacy_policy_version_id BIGINT UNSIGNED NOT NULL,
                                                          user_id INT NULL,
                                                          anonymous_token VARCHAR(128) NULL,
    accepted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    KEY idx_privacy_policy_acceptances_user_id (user_id),
    KEY idx_privacy_policy_acceptances_anonymous_token (anonymous_token),
    KEY idx_privacy_policy_acceptances_version_id (privacy_policy_version_id),
    CONSTRAINT fk_privacy_policy_acceptances_version
    FOREIGN KEY (privacy_policy_version_id) REFERENCES privacy_policy_versions(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
    CONSTRAINT fk_privacy_policy_acceptances_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE SET NULL
    ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `organization_invitations` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `organization_id` INT UNSIGNED NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `user_id` INT DEFAULT NULL,
    `role` ENUM('admin','editor','viewer') NOT NULL DEFAULT 'viewer',
    `status` ENUM('pending','accepted','declined','revoked','expired') NOT NULL DEFAULT 'pending',
    `token_hash` CHAR(64) NOT NULL,
    `invited_by` INT NOT NULL,
    `accepted_by_user_id` INT DEFAULT NULL,
    `expires_at` DATETIME NOT NULL,
    `accepted_at` DATETIME DEFAULT NULL,
    `declined_at` DATETIME DEFAULT NULL,
    `revoked_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_organization_invitations_token_hash` (`token_hash`),
    KEY `idx_organization_invitations_lookup` (`organization_id`, `email`, `status`),
    KEY `idx_organization_invitations_user_status` (`user_id`, `status`),
    KEY `idx_organization_invitations_expiry` (`status`, `expires_at`),
    CONSTRAINT `fk_organization_invitations_organization`
        FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT `fk_organization_invitations_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    CONSTRAINT `fk_organization_invitations_invited_by`
        FOREIGN KEY (`invited_by`) REFERENCES `users` (`id`)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT `fk_organization_invitations_accepted_by`
        FOREIGN KEY (`accepted_by_user_id`) REFERENCES `users` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

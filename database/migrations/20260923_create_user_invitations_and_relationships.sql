CREATE TABLE IF NOT EXISTS `user_invitation_blocks` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(255) NOT NULL,
    `reason` VARCHAR(80) NOT NULL DEFAULT 'recipient_opt_out',
    `created_at` DATETIME NOT NULL,
    UNIQUE KEY `uniq_user_invitation_block_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_invitations` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `invited_by_user_id` INT NOT NULL,
    `invited_user_id` INT DEFAULT NULL,
    `email` VARCHAR(255) NOT NULL,
    `status` ENUM('pending', 'accepted', 'expired', 'blocked') NOT NULL DEFAULT 'pending',
    `token_hash` CHAR(64) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `accepted_at` DATETIME DEFAULT NULL,
    `blocked_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME DEFAULT NULL,
    UNIQUE KEY `uniq_user_invitation_token` (`token_hash`),
    KEY `idx_user_invitations_email_status` (`email`, `status`, `expires_at`),
    KEY `idx_user_invitations_inviter` (`invited_by_user_id`, `created_at`),
    KEY `idx_user_invitations_invited_user` (`invited_user_id`),
    CONSTRAINT `fk_user_invitations_invited_by`
        FOREIGN KEY (`invited_by_user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE,
    CONSTRAINT `fk_user_invitations_invited_user`
        FOREIGN KEY (`invited_user_id`) REFERENCES `users` (`id`)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_relationships` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `related_user_id` INT NOT NULL,
    `relationship_type` VARCHAR(40) NOT NULL,
    `status` VARCHAR(40) NOT NULL DEFAULT 'active',
    `source` VARCHAR(40) NOT NULL DEFAULT 'invitation',
    `source_invitation_id` BIGINT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME DEFAULT NULL,
    UNIQUE KEY `uniq_user_relationship` (`user_id`, `related_user_id`, `relationship_type`, `source`),
    KEY `idx_user_relationships_related` (`related_user_id`, `relationship_type`, `status`),
    KEY `idx_user_relationships_invitation` (`source_invitation_id`),
    CONSTRAINT `fk_user_relationships_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE,
    CONSTRAINT `fk_user_relationships_related_user`
        FOREIGN KEY (`related_user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE,
    CONSTRAINT `fk_user_relationships_invitation`
        FOREIGN KEY (`source_invitation_id`) REFERENCES `user_invitations` (`id`)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

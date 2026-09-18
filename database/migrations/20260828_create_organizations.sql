CREATE TABLE IF NOT EXISTS `organizations` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `owner_user_id` INT NOT NULL,
    `name` VARCHAR(180) NOT NULL,
    `slug` VARCHAR(180) NOT NULL,
    `legal_name` VARCHAR(220) DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `website_url` VARCHAR(500) DEFAULT NULL,
    `email` VARCHAR(255) DEFAULT NULL,
    `phone` VARCHAR(50) DEFAULT NULL,
    `logo_path` VARCHAR(500) DEFAULT NULL,
    `cover_path` VARCHAR(500) DEFAULT NULL,
    `facebook_url` VARCHAR(500) DEFAULT NULL,
    `instagram_url` VARCHAR(500) DEFAULT NULL,
    `tiktok_url` VARCHAR(500) DEFAULT NULL,
    `youtube_url` VARCHAR(500) DEFAULT NULL,
    `status` ENUM('draft','pending_review','active','suspended','archived') NOT NULL DEFAULT 'draft',
    `is_public` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_organizations_slug` (`slug`),
    KEY `idx_organizations_owner` (`owner_user_id`),
    KEY `idx_organizations_status_public` (`status`, `is_public`),
    CONSTRAINT `fk_organizations_owner`
        FOREIGN KEY (`owner_user_id`) REFERENCES `users` (`id`)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `organization_users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `organization_id` INT UNSIGNED NOT NULL,
    `user_id` INT NOT NULL,
    `role` ENUM('owner','admin','editor','viewer') NOT NULL DEFAULT 'editor',
    `status` ENUM('invited','active','removed') NOT NULL DEFAULT 'invited',
    `invited_by` INT DEFAULT NULL,
    `invitation_token` VARCHAR(100) DEFAULT NULL,
    `invited_at` DATETIME DEFAULT NULL,
    `joined_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_organization_users_member` (`organization_id`, `user_id`),
    UNIQUE KEY `uniq_organization_users_token` (`invitation_token`),
    KEY `idx_organization_users_user` (`user_id`),
    KEY `idx_organization_users_status` (`organization_id`, `status`),
    CONSTRAINT `fk_organization_users_organization`
        FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT `fk_organization_users_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT `fk_organization_users_invited_by`
        FOREIGN KEY (`invited_by`) REFERENCES `users` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `organization_event_masters` (
    `organization_id` INT UNSIGNED NOT NULL,
    `event_master_id` INT NOT NULL,
    `role` ENUM('organizer', 'co_organizer') NOT NULL DEFAULT 'organizer',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`organization_id`, `event_master_id`),
    KEY `idx_organization_event_masters_event_master_role`
        (`event_master_id`, `role`),
    CONSTRAINT `fk_organization_event_masters_organization`
        FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT `fk_organization_event_masters_event_master`
        FOREIGN KEY (`event_master_id`) REFERENCES `events_master` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

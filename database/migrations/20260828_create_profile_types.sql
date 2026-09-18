CREATE TABLE IF NOT EXISTS `profile_types` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(80) NOT NULL,
    `slug` VARCHAR(80) NOT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `icon` VARCHAR(80) DEFAULT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_profile_types_slug` (`slug`),
    KEY `idx_profile_types_active_sort` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_profile_types` (
    `user_id` INT NOT NULL,
    `profile_type_id` INT UNSIGNED NOT NULL,
    `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`, `profile_type_id`),
    KEY `idx_user_profile_types_profile` (`profile_type_id`),
    KEY `idx_user_profile_types_primary` (`user_id`, `is_primary`),
    CONSTRAINT `fk_user_profile_types_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT `fk_user_profile_types_profile`
        FOREIGN KEY (`profile_type_id`) REFERENCES `profile_types` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `profile_types` (`name`, `slug`, `description`, `icon`, `sort_order`, `is_active`)
VALUES
    ('Cosplayer', 'cosplayer', 'Profilo per chi partecipa agli eventi in cosplay.', 'sparkles', 10, 1),
    ('Fotografo', 'fotografo', 'Profilo per fotografi cosplay ed eventi.', 'camera', 20, 1),
    ('Propmaker', 'propmaker', 'Profilo per creator, maker e propmaker.', 'hammer', 30, 1),
    ('Organizzatore', 'organizzatore', 'Profilo per chi organizza eventi, fiere o community.', 'calendar', 40, 1),
    ('Standista', 'standista', 'Profilo per espositori, artist alley e attività commerciali.', 'store', 50, 1)
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `description` = VALUES(`description`),
    `icon` = VALUES(`icon`),
    `sort_order` = VALUES(`sort_order`),
    `is_active` = VALUES(`is_active`),
    `updated_at` = NOW();

CREATE TABLE IF NOT EXISTS `professional_profiles` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `profile_type_id` INT UNSIGNED NOT NULL,
    `display_name` VARCHAR(180) NOT NULL,
    `slug` VARCHAR(180) NOT NULL,
    `headline` VARCHAR(220) DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `website_url` VARCHAR(500) DEFAULT NULL,
    `email_public` VARCHAR(255) DEFAULT NULL,
    `phone_public` VARCHAR(50) DEFAULT NULL,
    `instagram_url` VARCHAR(500) DEFAULT NULL,
    `facebook_url` VARCHAR(500) DEFAULT NULL,
    `tiktok_url` VARCHAR(500) DEFAULT NULL,
    `portfolio_url` VARCHAR(500) DEFAULT NULL,
    `base_regione_id` INT DEFAULT NULL,
    `base_provincia_id` INT DEFAULT NULL,
    `base_comune_id` INT DEFAULT NULL,
    `travel_radius_km` INT DEFAULT NULL,
    `accepts_opportunities` TINYINT(1) NOT NULL DEFAULT 0,
    `is_public` TINYINT(1) NOT NULL DEFAULT 0,
    `status` ENUM('draft','pending_review','active','suspended','archived') NOT NULL DEFAULT 'draft',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_professional_profiles_slug` (`slug`),
    UNIQUE KEY `uniq_professional_profiles_user_type` (`user_id`, `profile_type_id`),
    KEY `idx_professional_profiles_type_status` (`profile_type_id`, `status`, `is_public`),
    KEY `idx_professional_profiles_location` (`base_regione_id`, `base_provincia_id`, `base_comune_id`),
    KEY `idx_professional_profiles_opportunities` (`accepts_opportunities`, `status`),
    CONSTRAINT `fk_professional_profiles_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT `fk_professional_profiles_type`
        FOREIGN KEY (`profile_type_id`) REFERENCES `profile_types` (`id`)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT `fk_professional_profiles_regione`
        FOREIGN KEY (`base_regione_id`) REFERENCES `regioni` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    CONSTRAINT `fk_professional_profiles_provincia`
        FOREIGN KEY (`base_provincia_id`) REFERENCES `province` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    CONSTRAINT `fk_professional_profiles_comune`
        FOREIGN KEY (`base_comune_id`) REFERENCES `comuni` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `professional_profile_categories` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(120) NOT NULL,
    `slug` VARCHAR(120) NOT NULL,
    `profile_type_slug` VARCHAR(80) DEFAULT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_professional_profile_categories_slug` (`slug`),
    KEY `idx_professional_profile_categories_type` (`profile_type_slug`, `is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `professional_profile_category_links` (
    `professional_profile_id` INT UNSIGNED NOT NULL,
    `category_id` INT UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`professional_profile_id`, `category_id`),
    KEY `idx_professional_profile_category_links_category` (`category_id`),
    CONSTRAINT `fk_professional_profile_category_links_profile`
        FOREIGN KEY (`professional_profile_id`) REFERENCES `professional_profiles` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT `fk_professional_profile_category_links_category`
        FOREIGN KEY (`category_id`) REFERENCES `professional_profile_categories` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `professional_profile_operating_regions` (
    `professional_profile_id` INT UNSIGNED NOT NULL,
    `regione_id` INT NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`professional_profile_id`, `regione_id`),
    KEY `idx_professional_profile_operating_regions_region` (`regione_id`),
    CONSTRAINT `fk_professional_profile_operating_regions_profile`
        FOREIGN KEY (`professional_profile_id`) REFERENCES `professional_profiles` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT `fk_professional_profile_operating_regions_region`
        FOREIGN KEY (`regione_id`) REFERENCES `regioni` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `professional_profile_portfolio_items` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `professional_profile_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(180) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `image_path` VARCHAR(500) DEFAULT NULL,
    `external_url` VARCHAR(500) DEFAULT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `is_public` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_professional_profile_portfolio_profile` (`professional_profile_id`, `is_public`, `sort_order`),
    CONSTRAINT `fk_professional_profile_portfolio_profile`
        FOREIGN KEY (`professional_profile_id`) REFERENCES `professional_profiles` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `event_opportunities` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `organization_id` INT UNSIGNED DEFAULT NULL,
    `event_master_id` INT DEFAULT NULL,
    `event_id` INT DEFAULT NULL,
    `created_by` INT NOT NULL,
    `profile_type_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(180) NOT NULL,
    `description` TEXT NOT NULL,
    `requirements` TEXT DEFAULT NULL,
    `compensation_type` ENUM('free','paid','revenue_share','to_define') NOT NULL DEFAULT 'to_define',
    `budget_min` DECIMAL(10,2) DEFAULT NULL,
    `budget_max` DECIMAL(10,2) DEFAULT NULL,
    `currency` VARCHAR(10) NOT NULL DEFAULT 'EUR',
    `regione_id` INT DEFAULT NULL,
    `provincia_id` INT DEFAULT NULL,
    `comune_id` INT DEFAULT NULL,
    `starts_on` DATE DEFAULT NULL,
    `ends_on` DATE DEFAULT NULL,
    `application_deadline` DATE DEFAULT NULL,
    `status` ENUM('draft','published','closed','archived') NOT NULL DEFAULT 'draft',
    `is_paid_feature` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_event_opportunities_org` (`organization_id`),
    KEY `idx_event_opportunities_event_master` (`event_master_id`),
    KEY `idx_event_opportunities_event` (`event_id`),
    KEY `idx_event_opportunities_type_status` (`profile_type_id`, `status`),
    KEY `idx_event_opportunities_location` (`regione_id`, `provincia_id`, `comune_id`),
    KEY `idx_event_opportunities_deadline` (`application_deadline`),
    CONSTRAINT `fk_event_opportunities_organization`
        FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    CONSTRAINT `fk_event_opportunities_event_master`
        FOREIGN KEY (`event_master_id`) REFERENCES `events_master` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    CONSTRAINT `fk_event_opportunities_event`
        FOREIGN KEY (`event_id`) REFERENCES `events` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    CONSTRAINT `fk_event_opportunities_created_by`
        FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT `fk_event_opportunities_profile_type`
        FOREIGN KEY (`profile_type_id`) REFERENCES `profile_types` (`id`)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT `fk_event_opportunities_regione`
        FOREIGN KEY (`regione_id`) REFERENCES `regioni` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    CONSTRAINT `fk_event_opportunities_provincia`
        FOREIGN KEY (`provincia_id`) REFERENCES `province` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    CONSTRAINT `fk_event_opportunities_comune`
        FOREIGN KEY (`comune_id`) REFERENCES `comuni` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `event_opportunity_applications` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `opportunity_id` INT UNSIGNED NOT NULL,
    `user_id` INT NOT NULL,
    `professional_profile_id` INT UNSIGNED DEFAULT NULL,
    `message` TEXT DEFAULT NULL,
    `status` ENUM('draft','sent','shortlisted','accepted','rejected','withdrawn') NOT NULL DEFAULT 'sent',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_event_opportunity_applications_user` (`opportunity_id`, `user_id`),
    KEY `idx_event_opportunity_applications_user` (`user_id`),
    KEY `idx_event_opportunity_applications_profile` (`professional_profile_id`),
    KEY `idx_event_opportunity_applications_status` (`opportunity_id`, `status`),
    CONSTRAINT `fk_event_opportunity_applications_opportunity`
        FOREIGN KEY (`opportunity_id`) REFERENCES `event_opportunities` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT `fk_event_opportunity_applications_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT `fk_event_opportunity_applications_profile`
        FOREIGN KEY (`professional_profile_id`) REFERENCES `professional_profiles` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `professional_profile_categories` (`name`, `slug`, `profile_type_slug`, `sort_order`, `is_active`)
VALUES
    ('Artigianato', 'artigianato', 'standista', 10, 1),
    ('Artist Alley', 'artist-alley', 'standista', 20, 1),
    ('Gadget e merchandise', 'gadget-merchandise', 'standista', 30, 1),
    ('Abbigliamento cosplay', 'abbigliamento-cosplay', 'standista', 40, 1),
    ('Fotografia cosplay', 'fotografia-cosplay', 'fotografo', 50, 1),
    ('Video e reel', 'video-reel', 'fotografo', 60, 1),
    ('Props e accessori', 'props-accessori', 'propmaker', 70, 1),
    ('Armature e stampa 3D', 'armature-stampa-3d', 'propmaker', 80, 1)
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `profile_type_slug` = VALUES(`profile_type_slug`),
    `sort_order` = VALUES(`sort_order`),
    `is_active` = VALUES(`is_active`);

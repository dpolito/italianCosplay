-- Requests made by users to manage an event master through an organization.
CREATE TABLE IF NOT EXISTS `event_master_claims` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `event_master_id` INT NOT NULL,
    `organization_id` INT UNSIGNED NOT NULL,
    `requested_by` INT NOT NULL,
    `role` ENUM('organizer', 'co_organizer') NOT NULL DEFAULT 'organizer',
    `status` ENUM('pending', 'approved', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending',
    `evidence` TEXT DEFAULT NULL,
    `reviewed_by` INT DEFAULT NULL,
    `reviewed_at` DATETIME DEFAULT NULL,
    `review_notes` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_event_master_claim_request`
        (`event_master_id`, `organization_id`, `requested_by`),
    KEY `idx_event_master_claims_status` (`status`, `created_at`),
    KEY `idx_event_master_claims_organization` (`organization_id`, `status`),
    KEY `idx_event_master_claims_requested_by` (`requested_by`, `status`),
    CONSTRAINT `fk_event_master_claims_event_master`
        FOREIGN KEY (`event_master_id`) REFERENCES `events_master` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT `fk_event_master_claims_organization`
        FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT `fk_event_master_claims_requested_by`
        FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT `fk_event_master_claims_reviewed_by`
        FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

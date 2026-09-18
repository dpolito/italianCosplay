ALTER TABLE `events_master`
    ADD COLUMN `status` ENUM('draft','pending_review','active','suspended','archived') NOT NULL DEFAULT 'active' AFTER `social_youtube`,
    ADD COLUMN `is_public` TINYINT(1) NOT NULL DEFAULT 1 AFTER `status`,
    ADD KEY `idx_events_master_status_public` (`status`, `is_public`);

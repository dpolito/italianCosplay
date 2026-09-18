ALTER TABLE `organization_users`
    MODIFY COLUMN `status` ENUM('invited','active','declined','removed') NOT NULL DEFAULT 'invited';

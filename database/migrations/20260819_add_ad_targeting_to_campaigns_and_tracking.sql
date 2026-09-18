ALTER TABLE ad_campaigns
    ADD COLUMN target_type ENUM('national','region','province') NOT NULL DEFAULT 'national' AFTER position_id,
    ADD COLUMN target_value VARCHAR(100) NULL AFTER target_type,
    ADD INDEX idx_ad_campaigns_target (target_type, target_value);

ALTER TABLE ad_impressions
    ADD COLUMN target_type ENUM('national','region','province') NOT NULL DEFAULT 'national' AFTER position_id,
    ADD COLUMN target_value VARCHAR(100) NULL AFTER target_type,
    ADD INDEX idx_ad_impressions_target (target_type, target_value);

ALTER TABLE ad_clicks
    ADD COLUMN target_type ENUM('national','region','province') NOT NULL DEFAULT 'national' AFTER position_id,
    ADD COLUMN target_value VARCHAR(100) NULL AFTER target_type,
    ADD INDEX idx_ad_clicks_target (target_type, target_value);

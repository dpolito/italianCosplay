ALTER TABLE `ad_impressions`
  DROP COLUMN `user_hash`,
  DROP COLUMN `ip_hash`,
  DROP COLUMN `user_agent_hash`;

ALTER TABLE `ad_clicks`
  DROP COLUMN `user_hash`,
  DROP COLUMN `ip_hash`,
  DROP COLUMN `user_agent_hash`;

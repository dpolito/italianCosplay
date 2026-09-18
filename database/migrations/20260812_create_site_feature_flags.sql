CREATE TABLE IF NOT EXISTS `site_feature_flags` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `flag_key` VARCHAR(100) NOT NULL,
  `label` VARCHAR(150) NOT NULL,
  `description` VARCHAR(255) NULL,
  `is_enabled` TINYINT(1) NOT NULL DEFAULT 0,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_site_feature_flags_flag_key` (`flag_key`),
  KEY `idx_site_feature_flags_enabled` (`is_enabled`),
  KEY `idx_site_feature_flags_sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `site_feature_flags` (`flag_key`, `label`, `description`, `is_enabled`, `sort_order`)
VALUES
  ('enable_events', 'Eventi pubblici', 'Abilita il catalogo pubblico degli eventi cosplay.', 1, 10),
  ('enable_blog', 'Blog pubblico', 'Abilita il blog editoriale e le relative pagine pubbliche.', 1, 20),
  ('enable_user_registration', 'Registrazione utenti', 'Consente la creazione di nuovi account.', 1, 30),
  ('enable_public_profiles', 'Profili pubblici', 'Mostra le pagine profilo pubbliche degli utenti.', 1, 40),
  ('enable_notifications', 'Notifiche utente', 'Attiva il sistema notifiche nell’area personale.', 1, 50),
  ('enable_favorites', 'Preferiti', 'Mostra la sezione preferiti nell’area personale.', 1, 60),
  ('enable_cosplay_portfolio', 'Portfolio cosplay', 'Mostra la gestione del portfolio cosplay nell’area personale.', 1, 70),
  ('enable_personal_agenda', 'Agenda personale', 'Mostra la gestione dell’agenda personale degli eventi.', 1, 80),
  ('enable_marketing', 'Marketing', 'Abilita la raccolta e le preferenze marketing nel banner cookie.', 1, 90),
  ('enable_advertising', 'Advertising', 'Abilita moduli pubblicitari e campagne sponsor.', 1, 100),
  ('enable_guest_directory', 'Directory guest', 'Mostra l’elenco e le schede dei guest registrati.', 1, 110)
ON DUPLICATE KEY UPDATE
  `label` = VALUES(`label`),
  `description` = VALUES(`description`),
  `sort_order` = VALUES(`sort_order`);

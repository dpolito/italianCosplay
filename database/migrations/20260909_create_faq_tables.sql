CREATE TABLE IF NOT EXISTS `faq_categories` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(160) NOT NULL,
  `description` VARCHAR(500) NULL,
  `feature_flag_key` VARCHAR(100) NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME NULL,
  UNIQUE KEY `uq_faq_categories_slug` (`slug`),
  KEY `idx_faq_categories_public` (`is_active`, `deleted_at`, `sort_order`),
  KEY `idx_faq_categories_feature_flag` (`feature_flag_key`),
  CONSTRAINT `fk_faq_categories_feature_flag`
    FOREIGN KEY (`feature_flag_key`) REFERENCES `site_feature_flags` (`flag_key`)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `faq_items` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `category_id` BIGINT UNSIGNED NOT NULL,
  `question` VARCHAR(255) NOT NULL,
  `answer` TEXT NOT NULL,
  `feature_flag_key` VARCHAR(100) NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME NULL,
  KEY `idx_faq_items_category_public` (`category_id`, `is_active`, `deleted_at`, `sort_order`),
  KEY `idx_faq_items_feature_flag` (`feature_flag_key`),
  FULLTEXT KEY `ft_faq_items_search` (`question`, `answer`),
  CONSTRAINT `fk_faq_items_category`
    FOREIGN KEY (`category_id`) REFERENCES `faq_categories` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_faq_items_feature_flag`
    FOREIGN KEY (`feature_flag_key`) REFERENCES `site_feature_flags` (`flag_key`)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


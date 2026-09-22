-- Punga Mail 0.6.28 adds privacy-conscious internal campaign click statistics.
CREATE TABLE IF NOT EXISTS `#__pungamail_campaign_clicks` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `newsletter_id` BIGINT UNSIGNED NOT NULL,
  `link_index` INT UNSIGNED NOT NULL DEFAULT 0,
  `destination_url` TEXT NOT NULL,
  `destination_path` VARCHAR(2048) NOT NULL DEFAULT '',
  `utm_source` VARCHAR(255) NOT NULL DEFAULT '',
  `utm_medium` VARCHAR(255) NOT NULL DEFAULT '',
  `utm_campaign` VARCHAR(255) NOT NULL DEFAULT '',
  `utm_id` VARCHAR(255) NOT NULL DEFAULT '',
  `utm_content` VARCHAR(255) NOT NULL DEFAULT '',
  `is_automated` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `clicked_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pungamail_campaign_click_newsletter` (`newsletter_id`, `clicked_at`),
  KEY `idx_pungamail_campaign_click_time` (`clicked_at`),
  KEY `idx_pungamail_campaign_click_link` (`newsletter_id`, `link_index`, `is_automated`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

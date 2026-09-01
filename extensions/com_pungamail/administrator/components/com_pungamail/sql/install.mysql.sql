CREATE TABLE IF NOT EXISTS `#__pungamail_subscribers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NULL,
  `email` VARCHAR(320) NOT NULL,
  `recipient_name` VARCHAR(255) NOT NULL DEFAULT '',
  `email_normalized` VARCHAR(320) NOT NULL,
  `status` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `source` VARCHAR(32) NOT NULL DEFAULT 'external',
  `language` VARCHAR(12) NULL,
  `confirmation_token_hash` CHAR(64) NULL,
  `confirmation_expires` DATETIME NULL,
  `confirmed_at` DATETIME NULL,
  `unsubscribed_at` DATETIME NULL,
  `created` DATETIME NOT NULL,
  `modified` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_pungamail_subscriber_email` (`email_normalized`),
  UNIQUE KEY `idx_pungamail_subscriber_user` (`user_id`),
  UNIQUE KEY `idx_pungamail_confirmation_token` (`confirmation_token_hash`),
  KEY `idx_pungamail_subscriber_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__pungamail_suppressions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `subscriber_id` BIGINT UNSIGNED NULL,
  `email_normalized` VARCHAR(320) NOT NULL,
  `reason` VARCHAR(32) NOT NULL DEFAULT 'unsubscribed',
  `created` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_pungamail_suppression_email` (`email_normalized`),
  KEY `idx_pungamail_suppression_subscriber` (`subscriber_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__pungamail_templates` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `subject` VARCHAR(255) NOT NULL DEFAULT '',
  `body_markdown` MEDIUMTEXT NOT NULL,
  `state` TINYINT NOT NULL DEFAULT 1,
  `style_overrides` MEDIUMTEXT NULL,
  `custom_css` MEDIUMTEXT NULL,
  `created` DATETIME NOT NULL,
  `modified` DATETIME NOT NULL,
  `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_pungamail_template_state` (`state`),
  KEY `idx_pungamail_template_title` (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__pungamail_newsletters` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `body_markdown` MEDIUMTEXT NOT NULL,
  `state` TINYINT NOT NULL DEFAULT 1,
  `status` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `template_id` BIGINT UNSIGNED NULL,
  `style_overrides` MEDIUMTEXT NULL,
  `custom_css` MEDIUMTEXT NULL,
  `include_subscribers` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `content_cutoff_start` DATETIME NULL,
  `content_cutoff_end` DATETIME NULL,
  `snapshot_subject` VARCHAR(255) NULL,
  `snapshot_html` MEDIUMTEXT NULL,
  `snapshot_text` MEDIUMTEXT NULL,
  `recipient_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `sent_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `failed_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `created` DATETIME NOT NULL,
  `modified` DATETIME NOT NULL,
  `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
  `sent_at` DATETIME NULL,
  `reminder_sent_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pungamail_newsletter_state` (`state`),
  KEY `idx_pungamail_newsletter_status` (`status`),
  KEY `idx_pungamail_newsletter_sent` (`sent_at`),
  KEY `idx_pungamail_newsletter_template` (`template_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__pungamail_newsletter_items` (
  `newsletter_id` BIGINT UNSIGNED NOT NULL,
  `source_key` VARCHAR(191) NOT NULL,
  `source_item_id` VARCHAR(191) NOT NULL,
  `ordering` INT UNSIGNED NOT NULL DEFAULT 0,
  `title_override` VARCHAR(255) NULL,
  `excerpt_override` TEXT NULL,
  `snapshot_title` VARCHAR(255) NULL,
  `snapshot_excerpt` TEXT NULL,
  `snapshot_url` TEXT NULL,
  PRIMARY KEY (`newsletter_id`, `source_key`, `source_item_id`),
  KEY `idx_pungamail_items_ordering` (`newsletter_id`, `ordering`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__pungamail_newsletter_sources` (
  `newsletter_id` BIGINT UNSIGNED NOT NULL,
  `source_key` VARCHAR(191) NOT NULL,
  PRIMARY KEY (`newsletter_id`, `source_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__pungamail_newsletter_groups` (
  `newsletter_id` BIGINT UNSIGNED NOT NULL,
  `group_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`newsletter_id`, `group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__pungamail_send_queue` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `newsletter_id` BIGINT UNSIGNED NOT NULL,
  `subscriber_id` BIGINT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NULL,
  `email` VARCHAR(320) NOT NULL,
  `recipient_name` VARCHAR(255) NOT NULL DEFAULT '',
  `email_normalized` VARCHAR(320) NOT NULL,
  `source` VARCHAR(32) NOT NULL,
  `status` VARCHAR(16) NOT NULL DEFAULT 'pending',
  `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `next_attempt_at` DATETIME NULL,
  `last_error` TEXT NULL,
  `created` DATETIME NOT NULL,
  `modified` DATETIME NOT NULL,
  `sent_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_pungamail_queue_recipient` (`newsletter_id`, `email_normalized`),
  KEY `idx_pungamail_queue_work` (`status`, `next_attempt_at`),
  KEY `idx_pungamail_queue_newsletter` (`newsletter_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__pungamail_events` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `subscriber_id` BIGINT UNSIGNED NULL,
  `event_type` VARCHAR(48) NOT NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` VARCHAR(512) NULL,
  `metadata` TEXT NULL,
  `created` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pungamail_event_subscriber` (`subscriber_id`, `created`),
  KEY `idx_pungamail_event_type` (`event_type`, `created`),
  KEY `idx_pungamail_event_ip` (`ip_address`, `created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

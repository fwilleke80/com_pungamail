ALTER TABLE `#__pungamail_subscribers`
  ADD COLUMN `bounce_count` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `unsubscribed_at`,
  ADD COLUMN `soft_bounce_count` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `bounce_count`,
  ADD COLUMN `last_bounce_at` DATETIME NULL AFTER `soft_bounce_count`,
  ADD COLUMN `last_bounce_class` VARCHAR(16) NULL AFTER `last_bounce_at`,
  ADD COLUMN `last_bounce_reason` TEXT NULL AFTER `last_bounce_class`;

ALTER TABLE `#__pungamail_templates`
  ADD COLUMN `heading_mode` VARCHAR(12) NOT NULL DEFAULT 'inherit' AFTER `custom_css`,
  ADD COLUMN `mail_heading` VARCHAR(255) NULL AFTER `heading_mode`,
  ADD COLUMN `browser_view` TINYINT NOT NULL DEFAULT -1 AFTER `mail_heading`,
  ADD COLUMN `reply_to_mode` VARCHAR(12) NOT NULL DEFAULT 'inherit' AFTER `browser_view`,
  ADD COLUMN `reply_to_email` VARCHAR(320) NULL AFTER `reply_to_mode`,
  ADD COLUMN `reply_to_name` VARCHAR(255) NULL AFTER `reply_to_email`;

ALTER TABLE `#__pungamail_newsletters`
  ADD COLUMN `heading_mode` VARCHAR(12) NOT NULL DEFAULT 'inherit' AFTER `custom_css`,
  ADD COLUMN `mail_heading` VARCHAR(255) NULL AFTER `heading_mode`,
  ADD COLUMN `browser_view` TINYINT NOT NULL DEFAULT -1 AFTER `mail_heading`,
  ADD COLUMN `reply_to_mode` VARCHAR(12) NOT NULL DEFAULT 'inherit' AFTER `browser_view`,
  ADD COLUMN `reply_to_email` VARCHAR(320) NULL AFTER `reply_to_mode`,
  ADD COLUMN `reply_to_name` VARCHAR(255) NULL AFTER `reply_to_email`,
  ADD COLUMN `snapshot_reply_to_email` VARCHAR(320) NULL AFTER `snapshot_text`,
  ADD COLUMN `snapshot_reply_to_name` VARCHAR(255) NULL AFTER `snapshot_reply_to_email`,
  ADD COLUMN `snapshot_list_id` VARCHAR(255) NULL AFTER `snapshot_reply_to_name`,
  ADD COLUMN `snapshot_browser_enabled` TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER `snapshot_list_id`,
  ADD COLUMN `snapshot_browser_token` CHAR(32) NULL AFTER `snapshot_browser_enabled`,
  ADD COLUMN `intended_count` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `recipient_count`,
  ADD COLUMN `suppressed_count` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `intended_count`,
  ADD COLUMN `deduplicated_count` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `suppressed_count`,
  ADD COLUMN `invalid_count` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `deduplicated_count`,
  ADD COLUMN `cancelled_count` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `failed_count`,
  ADD COLUMN `bounced_count` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `cancelled_count`,
  ADD COLUMN `scheduled_at` DATETIME NULL AFTER `sent_at`,
  ADD COLUMN `cancelled_at` DATETIME NULL AFTER `scheduled_at`,
  ADD COLUMN `queue_paused` TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER `cancelled_at`,
  ADD KEY `idx_pungamail_newsletter_scheduled` (`status`, `scheduled_at`);

ALTER TABLE `#__pungamail_send_queue`
  ADD COLUMN `failure_class` VARCHAR(16) NULL AFTER `last_error`,
  ADD COLUMN `cancelled_at` DATETIME NULL AFTER `sent_at`,
  ADD COLUMN `bounce_id` BIGINT UNSIGNED NULL AFTER `cancelled_at`;

ALTER TABLE `#__pungamail_events`
  ADD COLUMN `newsletter_id` BIGINT UNSIGNED NULL AFTER `event_type`,
  ADD KEY `idx_pungamail_event_newsletter` (`newsletter_id`, `event_type`);

CREATE TABLE IF NOT EXISTS `#__pungamail_topics` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `alias` VARCHAR(191) NOT NULL,
  `description` TEXT NULL,
  `state` TINYINT NOT NULL DEFAULT 1,
  `ordering` INT NOT NULL DEFAULT 0,
  `created` DATETIME NOT NULL,
  `modified` DATETIME NOT NULL,
  `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_pungamail_topic_alias` (`alias`),
  KEY `idx_pungamail_topic_state_order` (`state`, `ordering`),
  KEY `idx_pungamail_topic_title` (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__pungamail_subscriber_topics` (
  `subscriber_id` BIGINT UNSIGNED NOT NULL,
  `topic_id` BIGINT UNSIGNED NOT NULL,
  `status` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `confirmed_at` DATETIME NULL,
  `unsubscribed_at` DATETIME NULL,
  `modified` DATETIME NOT NULL,
  PRIMARY KEY (`subscriber_id`, `topic_id`),
  KEY `idx_pungamail_subscriber_topic_target` (`topic_id`, `status`, `subscriber_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__pungamail_newsletter_topics` (
  `newsletter_id` BIGINT UNSIGNED NOT NULL,
  `topic_id` BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (`newsletter_id`, `topic_id`),
  KEY `idx_pungamail_newsletter_topic` (`topic_id`, `newsletter_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__pungamail_preference_requests` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `subscriber_id` BIGINT UNSIGNED NOT NULL,
  `token_hash` CHAR(64) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `created` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_pungamail_preference_token` (`token_hash`),
  KEY `idx_pungamail_preference_subscriber` (`subscriber_id`, `expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__pungamail_preference_request_topics` (
  `request_id` BIGINT UNSIGNED NOT NULL,
  `topic_id` BIGINT UNSIGNED NOT NULL,
  `action` VARCHAR(12) NOT NULL,
  PRIMARY KEY (`request_id`, `topic_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__pungamail_bounces` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `subscriber_id` BIGINT UNSIGNED NULL,
  `newsletter_id` BIGINT UNSIGNED NULL,
  `queue_id` BIGINT UNSIGNED NULL,
  `email` VARCHAR(320) NOT NULL,
  `email_normalized` VARCHAR(320) NOT NULL,
  `message_key` CHAR(64) NOT NULL,
  `classification` VARCHAR(16) NOT NULL,
  `status_code` VARCHAR(32) NULL,
  `diagnostic` TEXT NULL,
  `occurred_at` DATETIME NOT NULL,
  `processed_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_pungamail_bounce_message` (`message_key`),
  KEY `idx_pungamail_bounce_email` (`email_normalized`, `occurred_at`),
  KEY `idx_pungamail_bounce_newsletter` (`newsletter_id`, `classification`),
  KEY `idx_pungamail_bounce_queue` (`queue_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__pungamail_mail_settings` (
  `id` TINYINT UNSIGNED NOT NULL,
  `bounce_host` VARCHAR(255) NOT NULL DEFAULT '',
  `bounce_port` SMALLINT UNSIGNED NOT NULL DEFAULT 993,
  `bounce_security` VARCHAR(12) NOT NULL DEFAULT 'ssl',
  `validate_cert` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `bounce_username` VARCHAR(320) NOT NULL DEFAULT '',
  `bounce_password_cipher` TEXT NULL,
  `bounce_mailbox` VARCHAR(191) NOT NULL DEFAULT 'INBOX',
  `bounce_address` VARCHAR(320) NOT NULL DEFAULT '',
  `created` DATETIME NOT NULL,
  `modified` DATETIME NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__pungamail_digests` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `state` TINYINT NOT NULL DEFAULT 1,
  `template_id` BIGINT UNSIGNED NOT NULL,
  `subject_pattern` VARCHAR(255) NOT NULL,
  `recurrence_minutes` INT UNSIGNED NOT NULL DEFAULT 10080,
  `next_run_at` DATETIME NOT NULL,
  `cutoff_mode` VARCHAR(16) NOT NULL DEFAULT 'since_last',
  `rolling_hours` INT UNSIGNED NOT NULL DEFAULT 168,
  `include_subscribers` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `generation_mode` VARCHAR(16) NOT NULL DEFAULT 'draft',
  `empty_action` VARCHAR(20) NOT NULL DEFAULT 'skip',
  `last_cutoff_at` DATETIME NULL,
  `last_run_at` DATETIME NULL,
  `created` DATETIME NOT NULL,
  `modified` DATETIME NOT NULL,
  `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_pungamail_digest_due` (`state`, `next_run_at`),
  KEY `idx_pungamail_digest_template` (`template_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__pungamail_digest_sources` (`digest_id` BIGINT UNSIGNED NOT NULL, `source_key` VARCHAR(191) NOT NULL, PRIMARY KEY (`digest_id`, `source_key`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `#__pungamail_digest_categories` (`digest_id` BIGINT UNSIGNED NOT NULL, `source_key` VARCHAR(191) NOT NULL, `category_id` INT UNSIGNED NOT NULL, PRIMARY KEY (`digest_id`, `source_key`, `category_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `#__pungamail_digest_topics` (`digest_id` BIGINT UNSIGNED NOT NULL, `topic_id` BIGINT UNSIGNED NOT NULL, PRIMARY KEY (`digest_id`, `topic_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `#__pungamail_digest_groups` (`digest_id` BIGINT UNSIGNED NOT NULL, `group_id` INT UNSIGNED NOT NULL, PRIMARY KEY (`digest_id`, `group_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__pungamail_digest_runs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `digest_id` BIGINT UNSIGNED NOT NULL,
  `started_at` DATETIME NOT NULL,
  `completed_at` DATETIME NULL,
  `status` VARCHAR(20) NOT NULL,
  `newsletter_id` BIGINT UNSIGNED NULL,
  `item_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `message` TEXT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pungamail_digest_run` (`digest_id`, `started_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

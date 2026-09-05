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
  `bounce_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `soft_bounce_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `last_bounce_at` DATETIME NULL,
  `last_bounce_class` VARCHAR(16) NULL,
  `last_bounce_reason` TEXT NULL,
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
  `new_content_item_template` MEDIUMTEXT NULL,
  `state` TINYINT NOT NULL DEFAULT 1,
  `style_overrides` MEDIUMTEXT NULL,
  `custom_css` MEDIUMTEXT NULL,
  `heading_mode` VARCHAR(12) NOT NULL DEFAULT 'inherit',
  `mail_heading` VARCHAR(255) NULL,
  `browser_view` TINYINT NOT NULL DEFAULT -1,
  `reply_to_mode` VARCHAR(12) NOT NULL DEFAULT 'inherit',
  `reply_to_email` VARCHAR(320) NULL,
  `reply_to_name` VARCHAR(255) NULL,
  `created` DATETIME NOT NULL,
  `modified` DATETIME NOT NULL,
  `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
  `checked_out` INT UNSIGNED NOT NULL DEFAULT 0,
  `checked_out_time` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pungamail_template_state` (`state`),
  KEY `idx_pungamail_template_title` (`title`),
  KEY `idx_pungamail_template_checkout` (`checked_out`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__pungamail_newsletters` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `body_markdown` MEDIUMTEXT NOT NULL,
  `new_content_item_template` MEDIUMTEXT NULL,
  `state` TINYINT NOT NULL DEFAULT 1,
  `status` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `template_id` BIGINT UNSIGNED NULL,
  `style_overrides` MEDIUMTEXT NULL,
  `custom_css` MEDIUMTEXT NULL,
  `heading_mode` VARCHAR(12) NOT NULL DEFAULT 'inherit',
  `mail_heading` VARCHAR(255) NULL,
  `browser_view` TINYINT NOT NULL DEFAULT -1,
  `reply_to_mode` VARCHAR(12) NOT NULL DEFAULT 'inherit',
  `reply_to_email` VARCHAR(320) NULL,
  `reply_to_name` VARCHAR(255) NULL,
  `include_subscribers` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `content_cutoff_start` DATETIME NULL,
  `content_cutoff_end` DATETIME NULL,
  `snapshot_subject` VARCHAR(255) NULL,
  `snapshot_html` MEDIUMTEXT NULL,
  `snapshot_text` MEDIUMTEXT NULL,
  `snapshot_reply_to_email` VARCHAR(320) NULL,
  `snapshot_reply_to_name` VARCHAR(255) NULL,
  `snapshot_list_id` VARCHAR(255) NULL,
  `snapshot_browser_enabled` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `snapshot_browser_token` CHAR(32) NULL,
  `recipient_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `intended_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `suppressed_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `deduplicated_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `invalid_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `sent_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `failed_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `cancelled_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `bounced_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `created` DATETIME NOT NULL,
  `modified` DATETIME NOT NULL,
  `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
  `checked_out` INT UNSIGNED NOT NULL DEFAULT 0,
  `checked_out_time` DATETIME NULL,
  `sent_at` DATETIME NULL,
  `scheduled_at` DATETIME NULL,
  `cancelled_at` DATETIME NULL,
  `queue_paused` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `reminder_sent_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pungamail_newsletter_state` (`state`),
  KEY `idx_pungamail_newsletter_status` (`status`),
  KEY `idx_pungamail_newsletter_sent` (`sent_at`),
  KEY `idx_pungamail_newsletter_scheduled` (`status`, `scheduled_at`),
  KEY `idx_pungamail_newsletter_template` (`template_id`),
  KEY `idx_pungamail_newsletter_checkout` (`checked_out`)
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

CREATE TABLE IF NOT EXISTS `#__pungamail_topics` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `alias` VARCHAR(191) NOT NULL,
  `description` TEXT NULL,
  `audience_mode` VARCHAR(16) NOT NULL DEFAULT 'everyone',
  `state` TINYINT NOT NULL DEFAULT 1,
  `ordering` INT NOT NULL DEFAULT 0,
  `created` DATETIME NOT NULL,
  `modified` DATETIME NOT NULL,
  `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
  `checked_out` INT UNSIGNED NOT NULL DEFAULT 0,
  `checked_out_time` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_pungamail_topic_alias` (`alias`),
  KEY `idx_pungamail_topic_state_order` (`state`, `ordering`),
  KEY `idx_pungamail_topic_title` (`title`),
  KEY `idx_pungamail_topic_checkout` (`checked_out`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__pungamail_topic_groups` (
  `topic_id` BIGINT UNSIGNED NOT NULL,
  `group_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`topic_id`, `group_id`),
  KEY `idx_pungamail_topic_group` (`group_id`, `topic_id`)
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
  `failure_class` VARCHAR(16) NULL,
  `created` DATETIME NOT NULL,
  `modified` DATETIME NOT NULL,
  `sent_at` DATETIME NULL,
  `cancelled_at` DATETIME NULL,
  `bounce_id` BIGINT UNSIGNED NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_pungamail_queue_recipient` (`newsletter_id`, `email_normalized`),
  KEY `idx_pungamail_queue_work` (`status`, `next_attempt_at`),
  KEY `idx_pungamail_queue_newsletter` (`newsletter_id`, `status`)
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
  `recurrence_value` SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `recurrence_unit` VARCHAR(10) NOT NULL DEFAULT 'weeks',
  `recurrence_anchor_day` TINYINT UNSIGNED NULL,
  `next_run_at` DATETIME NOT NULL,
  `cutoff_mode` VARCHAR(16) NOT NULL DEFAULT 'since_last',
  `rolling_hours` INT UNSIGNED NOT NULL DEFAULT 168,
  `include_subscribers` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `generation_mode` VARCHAR(16) NOT NULL DEFAULT 'draft',
  `empty_action` VARCHAR(20) NOT NULL DEFAULT 'skip',
  `content_order` VARCHAR(12) NOT NULL DEFAULT 'newest',
  `max_items` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `minimum_items` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `last_cutoff_at` DATETIME NULL,
  `last_run_at` DATETIME NULL,
  `created` DATETIME NOT NULL,
  `modified` DATETIME NOT NULL,
  `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
  `checked_out` INT UNSIGNED NOT NULL DEFAULT 0,
  `checked_out_time` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pungamail_digest_due` (`state`, `next_run_at`),
  KEY `idx_pungamail_digest_template` (`template_id`),
  KEY `idx_pungamail_digest_checkout` (`checked_out`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__pungamail_digest_sources` (
  `digest_id` BIGINT UNSIGNED NOT NULL,
  `source_key` VARCHAR(191) NOT NULL,
  PRIMARY KEY (`digest_id`, `source_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__pungamail_digest_categories` (
  `digest_id` BIGINT UNSIGNED NOT NULL,
  `source_key` VARCHAR(191) NOT NULL,
  `category_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`digest_id`, `source_key`, `category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__pungamail_digest_topics` (
  `digest_id` BIGINT UNSIGNED NOT NULL,
  `topic_id` BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (`digest_id`, `topic_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__pungamail_digest_groups` (
  `digest_id` BIGINT UNSIGNED NOT NULL,
  `group_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`digest_id`, `group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS `#__pungamail_events` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `subscriber_id` BIGINT UNSIGNED NULL,
  `event_type` VARCHAR(48) NOT NULL,
  `newsletter_id` BIGINT UNSIGNED NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` VARCHAR(512) NULL,
  `metadata` TEXT NULL,
  `created` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pungamail_event_subscriber` (`subscriber_id`, `created`),
  KEY `idx_pungamail_event_type` (`event_type`, `created`),
  KEY `idx_pungamail_event_newsletter` (`newsletter_id`, `event_type`),
  KEY `idx_pungamail_event_ip` (`ip_address`, `created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

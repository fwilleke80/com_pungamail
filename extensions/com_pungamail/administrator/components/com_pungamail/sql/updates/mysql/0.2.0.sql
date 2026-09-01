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

CREATE TABLE IF NOT EXISTS `#__pungamail_newsletter_sources` (
  `newsletter_id` BIGINT UNSIGNED NOT NULL,
  `source_key` VARCHAR(191) NOT NULL,
  PRIMARY KEY (`newsletter_id`, `source_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `#__pungamail_newsletters`
  ADD COLUMN `template_id` BIGINT UNSIGNED NULL AFTER `status`,
  ADD COLUMN `style_overrides` MEDIUMTEXT NULL AFTER `template_id`,
  ADD COLUMN `custom_css` MEDIUMTEXT NULL AFTER `style_overrides`,
  ADD COLUMN `reminder_sent_at` DATETIME NULL AFTER `sent_at`,
  ADD KEY `idx_pungamail_newsletter_template` (`template_id`);

ALTER TABLE `#__pungamail_newsletter_items`
  ADD COLUMN `source_key` VARCHAR(191) NOT NULL DEFAULT 'com_content.article' AFTER `newsletter_id`,
  ADD COLUMN `source_item_id` VARCHAR(191) NULL AFTER `source_key`;

UPDATE `#__pungamail_newsletter_items`
SET `source_item_id` = CAST(`content_id` AS CHAR)
WHERE `source_item_id` IS NULL;

ALTER TABLE `#__pungamail_newsletter_items`
  DROP PRIMARY KEY,
  DROP COLUMN `content_id`,
  MODIFY `source_item_id` VARCHAR(191) NOT NULL,
  ADD PRIMARY KEY (`newsletter_id`, `source_key`, `source_item_id`);

INSERT IGNORE INTO `#__pungamail_newsletter_sources` (`newsletter_id`, `source_key`)
SELECT `id`, 'com_content.article' FROM `#__pungamail_newsletters`;

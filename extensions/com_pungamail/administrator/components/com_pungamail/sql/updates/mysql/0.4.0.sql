-- Punga Mail 0.4.0
ALTER TABLE `#__pungamail_newsletters` MODIFY `include_subscribers` TINYINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `#__pungamail_topics` ADD COLUMN `audience_mode` VARCHAR(16) NOT NULL DEFAULT 'everyone' AFTER `description`;
CREATE TABLE IF NOT EXISTS `#__pungamail_topic_groups` (
  `topic_id` BIGINT UNSIGNED NOT NULL,
  `group_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`topic_id`, `group_id`),
  KEY `idx_pungamail_topic_group` (`group_id`, `topic_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

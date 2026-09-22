-- Punga Mail 0.6.24 adds generic per-content-type Automatic Newsletter filters.
CREATE TABLE IF NOT EXISTS `#__pungamail_digest_filters` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `digest_id` BIGINT UNSIGNED NOT NULL,
  `source_key` VARCHAR(191) NOT NULL,
  `field_name` VARCHAR(128) NOT NULL,
  `operator_name` VARCHAR(24) NOT NULL DEFAULT 'eq',
  `filter_value` TEXT NULL,
  `ordering` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_pungamail_digest_filter` (`digest_id`, `source_key`, `ordering`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- Preserve the old per-source Category IDs restriction as a normal category filter.
INSERT INTO `#__pungamail_digest_filters` (`digest_id`, `source_key`, `field_name`, `operator_name`, `filter_value`, `ordering`)
SELECT `digest_id`, `source_key`, 'catid', 'in', CONCAT('[', GROUP_CONCAT(`category_id` ORDER BY `category_id` SEPARATOR ','), ']'), 0
FROM `#__pungamail_digest_categories`
GROUP BY `digest_id`, `source_key`;

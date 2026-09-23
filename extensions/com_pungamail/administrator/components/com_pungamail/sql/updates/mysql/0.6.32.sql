-- Punga Mail 0.6.32: statistics reset baseline.
CREATE TABLE IF NOT EXISTS `#__pungamail_statistics_state` (
  `id` TINYINT UNSIGNED NOT NULL,
  `reset_at` DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `#__pungamail_statistics_state` (`id`, `reset_at`) VALUES (1, NULL);

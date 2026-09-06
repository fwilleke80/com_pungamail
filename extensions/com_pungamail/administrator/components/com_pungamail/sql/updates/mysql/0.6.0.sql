-- Punga Mail 0.6.0 centralizes selected-content layouts by registered Joomla content type.
CREATE TABLE IF NOT EXISTS `#__pungamail_content_layouts` (
  `source_key` VARCHAR(191) NOT NULL,
  `layout_markdown` MEDIUMTEXT NOT NULL,
  `created` DATETIME NOT NULL,
  `modified` DATETIME NOT NULL,
  PRIMARY KEY (`source_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

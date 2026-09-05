-- Punga Mail 0.5.0
-- Automatic Newsletter content-selection controls.
ALTER TABLE `#__pungamail_digests`
  ADD COLUMN `content_order` VARCHAR(12) NOT NULL DEFAULT 'newest' AFTER `empty_action`,
  ADD COLUMN `max_items` SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER `content_order`,
  ADD COLUMN `minimum_items` SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER `max_items`;

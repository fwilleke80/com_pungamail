-- Punga Mail 0.6.2
-- Persist the latest returned-mail check summary for Delivery and Dashboard status.
ALTER TABLE `#__pungamail_mail_settings`
  ADD COLUMN `bounce_last_check_at` DATETIME NULL AFTER `bounce_address`,
  ADD COLUMN `bounce_last_check_status` VARCHAR(16) NOT NULL DEFAULT '' AFTER `bounce_last_check_at`,
  ADD COLUMN `bounce_last_check_result` TEXT NULL AFTER `bounce_last_check_status`,
  ADD COLUMN `bounce_last_check_error` TEXT NULL AFTER `bounce_last_check_result`;

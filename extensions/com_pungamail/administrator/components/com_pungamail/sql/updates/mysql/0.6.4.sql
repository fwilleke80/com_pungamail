-- Punga Mail 0.6.4
-- Track which latest returned-mail attention item an administrator has reviewed.
ALTER TABLE `#__pungamail_mail_settings`
  ADD COLUMN `bounce_last_check_acknowledged_at` DATETIME NULL AFTER `bounce_last_check_error`;

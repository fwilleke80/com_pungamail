-- Punga Mail 0.6.3
-- Add independent encrypted SMTP transport settings. Existing installs default to Joomla's mail transport.
ALTER TABLE `#__pungamail_mail_settings`
  ADD COLUMN `smtp_mode` VARCHAR(12) NOT NULL DEFAULT 'joomla' AFTER `id`,
  ADD COLUMN `smtp_host` VARCHAR(255) NOT NULL DEFAULT '' AFTER `smtp_mode`,
  ADD COLUMN `smtp_port` SMALLINT UNSIGNED NOT NULL DEFAULT 587 AFTER `smtp_host`,
  ADD COLUMN `smtp_security` VARCHAR(12) NOT NULL DEFAULT 'tls' AFTER `smtp_port`,
  ADD COLUMN `smtp_auth` TINYINT UNSIGNED NOT NULL DEFAULT 1 AFTER `smtp_security`,
  ADD COLUMN `smtp_username` VARCHAR(320) NOT NULL DEFAULT '' AFTER `smtp_auth`,
  ADD COLUMN `smtp_password_cipher` TEXT NULL AFTER `smtp_username`;

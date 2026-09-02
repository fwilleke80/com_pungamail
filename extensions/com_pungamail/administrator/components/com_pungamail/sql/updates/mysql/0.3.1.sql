ALTER TABLE `#__pungamail_newsletters`
  ADD COLUMN `checked_out` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `created_by`,
  ADD COLUMN `checked_out_time` DATETIME NULL AFTER `checked_out`,
  ADD KEY `idx_pungamail_newsletter_checkout` (`checked_out`);

ALTER TABLE `#__pungamail_templates`
  ADD COLUMN `checked_out` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `created_by`,
  ADD COLUMN `checked_out_time` DATETIME NULL AFTER `checked_out`,
  ADD KEY `idx_pungamail_template_checkout` (`checked_out`);

ALTER TABLE `#__pungamail_topics`
  ADD COLUMN `checked_out` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `created_by`,
  ADD COLUMN `checked_out_time` DATETIME NULL AFTER `checked_out`,
  ADD KEY `idx_pungamail_topic_checkout` (`checked_out`);

ALTER TABLE `#__pungamail_digests`
  ADD COLUMN `checked_out` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `created_by`,
  ADD COLUMN `checked_out_time` DATETIME NULL AFTER `checked_out`,
  ADD KEY `idx_pungamail_digest_checkout` (`checked_out`);

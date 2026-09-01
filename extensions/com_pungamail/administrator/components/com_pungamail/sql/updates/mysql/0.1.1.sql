ALTER TABLE `#__pungamail_newsletters`
  ADD COLUMN `state` TINYINT NOT NULL DEFAULT 1 AFTER `body_markdown`,
  ADD KEY `idx_pungamail_newsletter_state` (`state`);

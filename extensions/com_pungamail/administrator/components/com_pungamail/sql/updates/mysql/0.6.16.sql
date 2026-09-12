-- Punga Mail 0.6.16 adds non-destructive archiving for historical delivery queue rows.
ALTER TABLE `#__pungamail_send_queue`
  ADD COLUMN `archived` TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER `bounce_id`,
  ADD COLUMN `archived_at` DATETIME NULL AFTER `archived`,
  ADD KEY `idx_pungamail_queue_archive` (`archived`, `status`, `created`);

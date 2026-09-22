-- Punga Mail 0.6.29 preserves Newsletter form state and makes the first Automatic Newsletter cutoff explicit.
ALTER TABLE `#__pungamail_digests`
  ADD COLUMN `first_run_cutoff_mode` VARCHAR(16) NOT NULL DEFAULT 'recurrence' AFTER `rolling_hours`,
  ADD COLUMN `first_run_lookback_hours` INT UNSIGNED NOT NULL DEFAULT 168 AFTER `first_run_cutoff_mode`;

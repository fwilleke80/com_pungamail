-- Punga Mail 0.3.13
-- Calendar-aware recurrence for automatic newsletters.
ALTER TABLE `#__pungamail_digests`
  ADD COLUMN `recurrence_value` SMALLINT UNSIGNED NOT NULL DEFAULT 1 AFTER `recurrence_minutes`,
  ADD COLUMN `recurrence_unit` VARCHAR(10) NOT NULL DEFAULT 'weeks' AFTER `recurrence_value`,
  ADD COLUMN `recurrence_anchor_day` TINYINT UNSIGNED NULL AFTER `recurrence_unit`;

-- Existing schedules keep their exact cadence. Whole weeks and whole days are
-- represented directly; older minute-based schedules remain on their legacy
-- cadence until an administrator next saves them using the new day/week/month UI.
UPDATE `#__pungamail_digests`
SET `recurrence_unit` = CASE
    WHEN MOD(`recurrence_minutes`, 10080) = 0 THEN 'weeks'
    WHEN MOD(`recurrence_minutes`, 1440) = 0 THEN 'days'
    ELSE 'legacy'
  END,
  `recurrence_value` = CASE
    WHEN MOD(`recurrence_minutes`, 10080) = 0 THEN GREATEST(1, `recurrence_minutes` DIV 10080)
    WHEN MOD(`recurrence_minutes`, 1440) = 0 THEN GREATEST(1, `recurrence_minutes` DIV 1440)
    ELSE 1
  END,
  `recurrence_anchor_day` = NULL;

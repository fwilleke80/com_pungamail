-- Punga Mail 0.6.33: Automatic Newsletter cutoffs follow actually sent newsletters.
--
-- Earlier releases advanced last_cutoff_at when a run merely created a draft,
-- queued a newsletter, or skipped an empty run. Reclassify existing generated
-- newsletters that were genuinely delivered, then rebuild each digest cutoff
-- from the newest delivered run. This deliberately resets the cutoff to NULL
-- when an Automatic Newsletter has never actually sent anything.

UPDATE `#__pungamail_digest_runs` AS `r`
INNER JOIN `#__pungamail_newsletters` AS `n` ON `n`.`id` = `r`.`newsletter_id`
SET `r`.`status` = CASE
  WHEN `n`.`status` = 4 THEN 'sent_with_failures'
  ELSE 'sent'
END
WHERE `n`.`status` IN (3, 4)
  AND `n`.`sent_count` > 0;

UPDATE `#__pungamail_digests` AS `d`
LEFT JOIN (
  SELECT `digest_id`, MAX(`completed_at`) AS `last_sent_cutoff`
  FROM `#__pungamail_digest_runs`
  WHERE `status` IN ('sent', 'sent_with_failures')
    AND `completed_at` IS NOT NULL
  GROUP BY `digest_id`
) AS `sent_runs` ON `sent_runs`.`digest_id` = `d`.`id`
SET `d`.`last_cutoff_at` = `sent_runs`.`last_sent_cutoff`;

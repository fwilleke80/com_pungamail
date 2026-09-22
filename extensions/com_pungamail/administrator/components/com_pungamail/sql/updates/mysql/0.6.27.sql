-- Punga Mail 0.6.27 adds configurable UTM campaign tracking overrides to newsletters and automatic newsletters.
ALTER TABLE `#__pungamail_newsletters`
  ADD COLUMN `campaign_scope` VARCHAR(12) NOT NULL DEFAULT 'inherit' AFTER `reply_to_name`,
  ADD COLUMN `utm_source` VARCHAR(255) NULL AFTER `campaign_scope`,
  ADD COLUMN `utm_medium` VARCHAR(255) NULL AFTER `utm_source`,
  ADD COLUMN `utm_campaign` VARCHAR(255) NULL AFTER `utm_medium`,
  ADD COLUMN `utm_id` VARCHAR(255) NULL AFTER `utm_campaign`,
  ADD COLUMN `utm_content` VARCHAR(255) NULL AFTER `utm_id`;

ALTER TABLE `#__pungamail_digests`
  ADD COLUMN `campaign_scope` VARCHAR(12) NOT NULL DEFAULT 'inherit' AFTER `minimum_items`,
  ADD COLUMN `utm_source` VARCHAR(255) NULL AFTER `campaign_scope`,
  ADD COLUMN `utm_medium` VARCHAR(255) NULL AFTER `utm_source`,
  ADD COLUMN `utm_campaign` VARCHAR(255) NULL AFTER `utm_medium`,
  ADD COLUMN `utm_id` VARCHAR(255) NULL AFTER `utm_campaign`,
  ADD COLUMN `utm_content` VARCHAR(255) NULL AFTER `utm_id`;

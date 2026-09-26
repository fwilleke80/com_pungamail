-- Punga Mail 0.6.34 adds public Newsletter Archive visibility controls.
ALTER TABLE `#__pungamail_newsletters`
  ADD COLUMN `archive_visibility` TINYINT NOT NULL DEFAULT -1 AFTER `browser_view`;

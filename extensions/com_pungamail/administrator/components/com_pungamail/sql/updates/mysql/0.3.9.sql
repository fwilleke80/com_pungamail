-- Punga Mail 0.3.9 adds configurable Markdown layouts for {new_content} items.
ALTER TABLE `#__pungamail_templates`
  ADD COLUMN `new_content_item_template` MEDIUMTEXT NULL AFTER `body_markdown`;

ALTER TABLE `#__pungamail_newsletters`
  ADD COLUMN `new_content_item_template` MEDIUMTEXT NULL AFTER `body_markdown`;

-- Punga Mail 0.2.2
-- Snapshot the resolved per-recipient display label for {recipient}.

ALTER TABLE `#__pungamail_send_queue`
  ADD COLUMN `recipient_name` VARCHAR(255) NOT NULL DEFAULT '' AFTER `email`;

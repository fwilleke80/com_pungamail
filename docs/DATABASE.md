# Punga Mail database architecture

Punga Mail 0.1.1 owns seven tables. All application timestamps are stored in UTC using Joomla's SQL date representation.

## `#__pungamail_subscribers`

Canonical subscriber identity and subscription state. `user_id` may link the record to a Joomla user; external subscribers have no Joomla account.

Important constraints:

- `email_normalized` is unique.
- `user_id` is unique when non-null.
- confirmation tokens are stored as SHA-256 hashes, never plaintext.

Subscription status values:

- `0` — pending confirmation
- `1` — subscribed
- `2` — unsubscribed

## `#__pungamail_suppressions`

Persistent do-not-send barrier keyed by normalized email address. Recipient assembly checks suppressions independently of subscriber status, so discovering an address later through a Joomla user group cannot silently undo an opt-out.

An explicit new double opt-in may deliberately remove an unsubscribe suppression.

## `#__pungamail_newsletters`

Stores draft content and immutable send-snapshot metadata.

Two independent state concepts are intentionally kept separate:

### Joomla record `state`

- `1` — active
- `-2` — trashed

This drives Joomla-standard list/trash/restore behavior.

### Delivery `status`

- `0` — draft
- `1` — queued
- `2` — sending
- `3` — sent
- `4` — sent with failures

A newsletter can therefore retain its historical delivery status while being moved to Joomla's Trash.

`content_cutoff_start` stores the editor-selected lower bound used to discover candidate `com_content` articles. `content_cutoff_end` is frozen when the newsletter is queued and provides the default lower bound for the next newsletter.

## `#__pungamail_newsletter_items`

Mapping of newsletters to Joomla `com_content` articles. Draft-time title/excerpt overrides are separate from immutable title/excerpt/URL snapshots captured on queueing.

## `#__pungamail_newsletter_groups`

Joomla user groups explicitly selected as additional recipients for a newsletter.

## `#__pungamail_send_queue`

Frozen per-recipient delivery snapshot. The unique key `(newsletter_id, email_normalized)` is a database-level idempotency barrier against duplicate queue rows.

Workers atomically claim eligible rows by moving them from `pending` to `processing`. Successful handoff changes the row to `sent`; recoverable failures return it to `pending` with retry metadata; exhausted failures become `failed`. Stale `processing` claims are recoverable after the configured timeout.

SMTP handoff and the database update cannot be one distributed transaction. A process crash in the narrow interval after SMTP acceptance but before the `sent` update can therefore produce one duplicate after stale-claim recovery. Punga Mail intentionally prefers that rare duplicate to silently dropping a message.

## `#__pungamail_events`

Small append-only subscription audit trail, including signup request, confirmation send/completion, unsubscribe and profile-preference changes.

## Migration policy

The component manifest declares Joomla's schema-update directory:

`administrator/components/com_pungamail/sql/updates/mysql`

The migration history is append-only:

- `0.1.0.sql` — immutable original baseline schema.
- `0.1.1.sql` — adds the independent Joomla newsletter `state` column and index used for Trash/Restore.

For every future schema-changing release:

1. update `install.mysql.sql` so a fresh installation lands directly on the newest schema;
2. add a new version-numbered SQL file containing only the transition from the prior released schema;
3. never rewrite a released migration;
4. never hide `ALTER TABLE`, `CREATE TABLE` or `DROP TABLE` operations in runtime PHP;
5. keep migrations deterministic and compatible with Joomla applying multiple skipped updates in order.

The release checker pins the released 0.1.0 migration by SHA-256 and verifies that the current fresh-install schema contains the 0.1.1 additions while `0.1.1.sql` remains the expected transition rather than a copied baseline.

## Uninstall policy

In 0.1.1 the component has no automatic uninstall SQL. Removing the extension therefore leaves subscribers, suppressions, newsletter history, queue data and audit events intact.

For deliberate destruction, `sql/purge.mysql.sql` is supplied in the source tree.

A configurable **Uninstall: Remove database tables** option is planned for a later release and will default to disabled; it is deliberately not part of 0.1.1.

## Referential-integrity policy

Punga Mail deliberately avoids cross-extension SQL foreign keys. It references Joomla-owned `#__users`, `#__usergroups` and `#__content` data, whose extensions/lifecycle are controlled independently. Relationships are maintained by application transactions, explicit cleanup and indexed identifiers so upgrades and data-preserving uninstalls remain predictable.

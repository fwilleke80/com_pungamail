# Database architecture

Punga Mail owns seven tables. All timestamps are stored in UTC using Joomla's SQL date format.

## `#__pungamail_subscribers`

Canonical identity/subscription record. An address may be linked to a Joomla user, but external subscribers do not require a Joomla account.

Important constraints:

- `email_normalized` is unique.
- `user_id` is unique when non-null.
- confirmation tokens are stored as SHA-256 hashes, never plaintext.

Subscription states:

- `0` — pending confirmation
- `1` — subscribed
- `2` — unsubscribed

## `#__pungamail_suppressions`

Permanent “do not send” barrier keyed by normalized email. Recipient assembly checks this table last, so importing a user or reconstructing a subscriber cannot accidentally undo an unsubscribe.

A successful explicit double-opt-in may remove an earlier unsubscribe suppression for the same address.

## `#__pungamail_newsletters`

Draft and immutable send-snapshot metadata.

States:

- `0` — draft
- `1` — queued
- `2` — sending
- `3` — sent
- `4` — send completed with failures

`content_cutoff_start` records the preceding newsletter cutoff used for “new since last newsletter”. `content_cutoff_end` is fixed when the send is queued. The next newsletter therefore has a deterministic lower bound even if publication or queue processing takes time.

## `#__pungamail_newsletter_items`

Many-to-many mapping from newsletters to Joomla content items. Draft-time override fields are separate from immutable snapshot fields populated when the newsletter is queued.

## `#__pungamail_newsletter_groups`

Selected Joomla user groups for a newsletter.

## `#__pungamail_send_queue`

Frozen recipient snapshot (including source) and delivery state. The unique key `(newsletter_id, email_normalized)` prevents duplicate queue rows. A worker atomically claims a pending row by changing it to `processing`, preventing concurrent workers from sending the same pending row.

After a successful SMTP handoff the row is marked `sent`; failures return it to `pending` for retry or mark it `failed` after maximum attempts. SMTP handoff and the database commit cannot be one atomic transaction, so a process crash in the narrow interval between them can result in one duplicate message after stale-claim recovery. This tradeoff deliberately prefers a rare duplicate over silently dropping a message.

## `#__pungamail_events`

Small append-only subscriber audit trail: signup requested, confirmation sent, confirmed, unsubscribed, profile change, resubscribed, etc.

## Migration policy

The component manifest declares Joomla's normal schema update directory:

`administrator/components/com_pungamail/sql/updates/mysql`

`0.1.0.sql` is an explicit baseline corresponding to the fresh-install schema. Future releases must:

1. update `install.mysql.sql` so a fresh install always lands on the current schema;
2. add a new version-numbered update file containing only the transition from the prior schema;
3. never rewrite an already-released migration;
4. avoid runtime `ALTER TABLE` statements;
5. keep migrations deterministic and safe when multiple intermediate releases are skipped.

## Uninstall policy

Punga Mail does **not** attach `uninstall.mysql.sql` to the manifest. Joomla therefore removes extension files but leaves subscriber, suppression, newsletter and audit data intact. This avoids turning a temporary uninstall/reinstall into an irreversible mailing-list deletion.

For an intentional permanent purge, `sql/purge.mysql.sql` is supplied in the source tree. It must be run manually by an administrator who explicitly wants to delete all Punga Mail data.

## Referential-integrity policy

The 0.1 schema deliberately avoids SQL foreign keys. Joomla extensions are installed, upgraded and sometimes removed independently, and Punga Mail also references Joomla-owned tables such as `#__users`, `#__usergroups` and `#__content`. Referential relationships are therefore maintained by application transactions and indexed IDs rather than cross-extension foreign-key constraints. This keeps upgrades and deliberate data-preserving uninstalls predictable.

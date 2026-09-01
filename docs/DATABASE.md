# Punga Mail database architecture

Punga Mail 0.2.5 uses the nine-table schema introduced in 0.2.0, with the `recipient_name` snapshot column added in 0.2.2. Versions 0.2.4 and 0.2.5 change no tables. Application timestamps are stored in UTC using Joomla's SQL date representation. Punga Mail deliberately avoids cross-extension foreign keys so Joomla extensions can be upgraded/uninstalled independently; application transactions, indexed identifiers and immutable snapshots maintain relationships.

## `#__pungamail_subscribers`

Canonical subscriber identity and subscription state. `user_id` may link a subscriber to a Joomla user; external subscribers have no Joomla account. Normalized email, Joomla user ID (when present) and confirmation-token hash are unique.

Status values are `0` pending, `1` subscribed and `2` unsubscribed.

## `#__pungamail_suppressions`

Persistent do-not-send barrier keyed by normalized email. Recipient resolution checks suppression independently of subscription source, so discovering an address through a Joomla user group cannot silently undo an opt-out.

## `#__pungamail_templates`

Reusable newsletter templates. Stores title, default subject, Markdown body, Joomla record state, optional mail-style override JSON and optional custom CSS. Applying a template copies these values into a newsletter draft; newsletters are not live-linked to later template edits.

## `#__pungamail_newsletters`

Newsletter drafts and immutable send-snapshot metadata.

Two state concepts remain separate:

- Joomla `state`: `1` active, `-2` trashed.
- Delivery `status`: `0` draft, `1` queued, `2` sending, `3` sent, `4` sent with failures.

0.2.0 adds `template_id`, layered style/custom-CSS data, and `reminder_sent_at`. `content_cutoff_start` is the editor-selected discovery lower bound; `content_cutoff_end` is frozen at queue time and becomes the default for the next newsletter.

## `#__pungamail_newsletter_items`

Selected content items. 0.2.0 generalizes the identity from a `com_content` integer ID to the composite `(newsletter_id, source_key, source_item_id)`. `source_key` is normally a Joomla registered content-type alias such as `com_content.article`. This prevents ID collisions between components and avoids further schema changes when additional registered content types become usable.

Draft-time title/excerpt overrides are separate from immutable title/excerpt/URL snapshots captured when queueing.

## `#__pungamail_newsletter_sources`

The registered Joomla content types enabled for each newsletter's “New content since …” candidate list.

## `#__pungamail_newsletter_groups`

Additional Joomla user groups selected as recipients.

## `#__pungamail_send_queue`

Frozen per-recipient delivery snapshot. `recipient_name` stores the resolved Joomla display name (or email fallback) at queue time for `{recipient}` personalization. `(newsletter_id, email_normalized)` is a database-level idempotency barrier. Workers atomically claim `pending` rows, send them, retry recoverable failures, and mark exhausted attempts `failed`. Stale `processing` rows are recoverable.

SMTP handoff and DB update cannot be one distributed transaction. A crash after SMTP acceptance but before the `sent` update can therefore cause one duplicate after stale recovery; Punga Mail prefers that rare duplicate to silently losing a message.

## `#__pungamail_events`

Append-only subscription audit trail for signup, confirmation, unsubscribe and profile-preference events.

## Registered-content policy

Punga Mail reads Joomla's `#__content_types` registry as metadata and queries the registered extension table directly. It does **not** persist or discover content through Joomla's deprecated UCM storage classes/`#__ucm_content`. A type is offered only when its registered table/key/title/publication mappings are sufficient for safe generic querying.

## Migration policy

The migration chain is append-only:

- `0.1.0.sql` — immutable original baseline.
- `0.1.1.sql` — adds Joomla newsletter record state for Trash/Restore.
- `0.2.0.sql` — adds templates, per-newsletter content-source selection, style/reminder fields and migrates article selections to `(source_key, source_item_id)`.
- `0.2.1.sql` — no-op version marker so Joomla’s stored database version stays aligned with the manifest.
- `0.2.2.sql` — adds `recipient_name` to the frozen send queue snapshot.
- `0.2.4.sql` — no-op version marker for the administrator-UI bugfix release.
- `0.2.5.sql` — no-op version marker for the mail-language/footer configuration release.

Every schema release updates `install.mysql.sql`, adds one forward migration, and never rewrites a released migration. Runtime component/plugin PHP must not execute ad-hoc schema DDL.

## Uninstall policy

Component Options → **Maintenance & Data → Uninstall: Remove database tables** controls destructive uninstall and defaults to **No**.

- **No**: uninstall preserves all Punga Mail tables/data.
- **Yes**: package uninstall drops all nine Punga Mail tables.

`sql/purge.mysql.sql` remains available in the source tree for deliberate manual cleanup.

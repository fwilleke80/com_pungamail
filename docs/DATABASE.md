# Punga Mail database architecture

Punga Mail 0.3.13 uses the normalized topic membership, digest automation/history, preference requests, bounce history, delivery metadata, encrypted mailbox settings and Joomla-compatible editor checkout metadata introduced by earlier 0.3.x releases. Application timestamps are stored in UTC using Joomla's SQL date representation. Punga Mail deliberately avoids cross-extension foreign keys so Joomla extensions can be upgraded/uninstalled independently; application transactions, indexed identifiers and immutable snapshots maintain relationships.

Topic membership uses `#__pungamail_topics`, `#__pungamail_subscriber_topics`, and `#__pungamail_newsletter_topics`. Digest definitions use normalized source/category/topic/group relations and append execution outcomes to `#__pungamail_digest_runs`. `#__pungamail_bounces` retains delivery-status history; address-level suppression remains authoritative in `#__pungamail_suppressions`.

## `#__pungamail_subscribers`

Canonical subscriber identity and subscription state. `user_id` may link a subscriber to a Joomla user; external subscribers have no Joomla account. Normalized email, Joomla user ID (when present) and confirmation-token hash are unique.

Status values are `0` pending, `1` subscribed and `2` unsubscribed.

## `#__pungamail_suppressions`

Persistent do-not-send barrier keyed by normalized email. Recipient resolution checks suppression independently of subscription source, so discovering an address through a Joomla user group cannot silently undo an opt-out.

## `#__pungamail_templates`

Reusable newsletter templates. Stores title, default subject, Markdown body, Joomla record state, optional mail-style override JSON and optional custom CSS. Applying a template copies these values into a newsletter draft; newsletters are not live-linked to later template edits.

Templates, newsletters, topics and digest definitions expose `checked_out` and `checked_out_time`. These fields prevent concurrent administrator edits and make abandoned editor sessions recoverable through Joomla Global Check-in.

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
- `0.2.3.sql` — no-op version marker for the queue/dashboard administrator workflow fix.
- `0.2.4.sql` — no-op version marker for the administrator-UI bugfix release.
- `0.2.5.sql` — no-op version marker for the mail-language/footer configuration release.
- `0.2.6.sql` — no-op version marker for administrator UI and subscriber-management improvements.
- `0.3.0.sql` — adds bounce/delivery state, topic and preference relations, digest definitions/history, schedule/snapshot metadata, and secure mail-settings storage.
- `0.3.1.sql` — adds Joomla-compatible checkout metadata to newsletters, templates, topics and digest definitions.
- `0.3.2.sql` — no-op version marker; profile topic selection uses the existing normalized subscriber-topic relation.
- `0.3.3.sql` — no-op version marker for the stabilization and security-hardening release.
- `0.3.4.sql` — portable version marker; package preflight conditionally adds the subscriber `recipient_name` column omitted from the earlier upgrade path, while fresh installations are left unchanged.
- `0.3.5.sql` — no-op version marker for the complete frontend and administrator subscriber-topic management workflow.
- `0.3.6.sql` — no-op version marker for terminology, audience explanations, and Preflight clarity; recipient and schema semantics are unchanged.
- `0.3.7.sql` — no-op version marker for the Channel UX, subscriber membership editor, standalone subscription save fix, and form-state preservation.
- `0.3.8.sql` — no-op version marker for linked Joomla-user recipient-name presentation.
- `0.3.9.sql` — adds the selected-content item-template override fields used by global/template/newsletter inheritance.
- `0.3.10.sql` — no-op version marker for tabbed Newsletter editing and editor-help improvements.
- `0.3.11.sql` — no-op version marker for Markdown hard-break/escape fixes and collapsible selected-content layout overrides.
- `0.3.12.sql` — no-op version marker for heading-background styling, plugin-token excerpt sanitization, and Markdown horizontal rules.
- `0.3.13.sql` — adds calendar-aware Automatic Newsletter recurrence metadata (`recurrence_value`, `recurrence_unit`, and `recurrence_anchor_day`) while preserving older minute-based cadences until they are explicitly resaved.

Every schema release updates `install.mysql.sql`, adds one forward migration, and never rewrites a released migration. Runtime component/plugin PHP must not execute ad-hoc schema DDL.

## Uninstall policy

Component Options → **Maintenance & Data → Uninstall: Remove database tables** controls destructive uninstall and defaults to **No**.

- **No**: uninstall preserves all Punga Mail tables/data.
- **Yes**: package uninstall drops all Punga Mail tables.

`sql/purge.mysql.sql` remains available in the source tree for deliberate manual cleanup.

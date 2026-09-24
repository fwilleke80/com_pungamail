# Punga Mail database architecture

Punga Mail 0.6.33 uses the normalized topic membership, digest automation/history, preference requests, bounce history, delivery metadata, encrypted mailbox settings and Joomla-compatible editor checkout metadata introduced by earlier 0.3.x releases. Application timestamps are stored in UTC using Joomla's SQL date representation. Punga Mail deliberately avoids cross-extension foreign keys so Joomla extensions can be upgraded/uninstalled independently; application transactions, indexed identifiers and immutable snapshots maintain relationships.

Topic membership uses `#__pungamail_topics`, `#__pungamail_subscriber_topics`, and `#__pungamail_newsletter_topics`. Digest definitions use normalized source/category/topic/group relations and append execution outcomes to `#__pungamail_digest_runs`. `#__pungamail_bounces` retains delivery-status history; address-level suppression remains authoritative in `#__pungamail_suppressions`.

## `#__pungamail_subscribers`

Canonical subscriber identity and subscription state. `user_id` may link a subscriber to a Joomla user; external subscribers have no Joomla account. Normalized email, Joomla user ID (when present) and confirmation-token hash are unique.

Status values are `0` pending, `1` subscribed and `2` unsubscribed.

## `#__pungamail_suppressions`

Persistent do-not-send barrier keyed by normalized email. Recipient resolution checks suppression independently of subscription source, so discovering an address through a Joomla user group cannot silently undo an opt-out.

## `#__pungamail_content_layouts`

Central Markdown layouts for items inserted at `{new_content}`. `source_key = __default__` stores the fallback layout; a row keyed by a Joomla registered content-type alias stores an optional custom layout for that type. The originating extension is not required to implement a Punga Mail integration. At render time Punga Mail reads the registered source table and exposes safe columns as placeholders.

## `#__pungamail_templates`

Reusable newsletter templates. Stores title, default subject, Markdown body, Joomla record state, optional mail-style override JSON and optional custom CSS. Applying a template copies these values into a newsletter draft; newsletters are not live-linked to later template edits.

Templates, newsletters, topics and digest definitions expose `checked_out` and `checked_out_time`. These fields prevent concurrent administrator edits and make abandoned editor sessions recoverable through Joomla Global Check-in.

## `#__pungamail_newsletters`

Newsletter drafts and immutable send-snapshot metadata.

Two state concepts remain separate:

- Joomla `state`: `1` active, `2` archived, `-2` trashed.
- Delivery `status`: `0` draft, `1` queued, `2` sending, `3` sent, `4` sent with failures.

0.2.0 adds `template_id`, layered style/custom-CSS data, and `reminder_sent_at`. `content_cutoff_start` is the editor-selected discovery lower bound; `content_cutoff_end` is frozen at queue time and becomes the default for the next newsletter. 0.6.27 adds `campaign_scope` plus nullable `utm_source`, `utm_medium`, `utm_campaign`, `utm_id`, and `utm_content` overrides. Empty UTM override values inherit Component Options at render time.

## `#__pungamail_newsletter_items`

Selected content items. 0.2.0 generalizes the identity from a `com_content` integer ID to the composite `(newsletter_id, source_key, source_item_id)`. `source_key` is normally a Joomla registered content-type alias such as `com_content.article`. This prevents ID collisions between components and avoids further schema changes when additional registered content types become usable.

Draft-time title/excerpt overrides are separate from immutable title/excerpt/URL snapshots captured when queueing.

## `#__pungamail_newsletter_sources`

The registered Joomla content types enabled for each newsletter's “New content since …” candidate list.

## `#__pungamail_newsletter_groups`

Additional Joomla user groups selected as recipients.


## `#__pungamail_digests` and `#__pungamail_digest_filters`

`#__pungamail_digests` stores Automatic Newsletter definitions, recurrence/cutoff state, generation mode, Template/audience references, and from 0.6.27 the same campaign-tracking override fields as ordinary Newsletters (`campaign_scope`, `utm_source`, `utm_medium`, `utm_campaign`, `utm_id`, `utm_content`). When an Automatic Newsletter generates a real Newsletter, those settings are copied into the generated Newsletter so attribution remains stable and inspectable.

`#__pungamail_digest_filters` stores generic per-source field/operator/value rules introduced in 0.6.24. Filters reference a registered content source and database field without coupling Punga Mail to a particular component. All rules for one source use AND semantics. Legacy category-ID restrictions were migrated to `catid IN (...)` rules.

Internal URLs carry a compact authenticated token; the system plugin validates it against the visible UTM parameters before recording the click and dispatching `onPungaMailCampaignVisit` for optional consumers such as Punga Analytics. Since 0.6.30 newly generated tokens keep only Newsletter/link identity plus a 128-bit truncated HMAC-SHA256 signature; the validator still accepts the older long token format from 0.6.27–0.6.29. Validated click rows are stored in `#__pungamail_campaign_clicks` as described below; recipient identity is not stored.

## `#__pungamail_campaign_clicks`

Stores validated internal campaign visits for the Statistics page. Rows identify the Newsletter and link index plus the effective campaign/link attribution and timestamp. Likely automated mail-security traffic is classified separately. The table deliberately stores no subscriber ID, email address, IP address, or raw user agent.


## `#__pungamail_send_queue`

Frozen per-recipient delivery snapshot. `recipient_name` stores the resolved Joomla display name (or email fallback) at queue time for `{recipient}` personalization. `(newsletter_id, email_normalized)` is a database-level idempotency barrier. Workers atomically claim `pending` rows, send them, retry recoverable failures, and mark exhausted attempts `failed`. Stale `processing` rows are recoverable. `archived`/`archived_at` are presentation/history metadata only: they never replace `status`. Only non-processing rows can be archived, and retrying an archived failed row clears its archive metadata before returning it to `pending`.

SMTP handoff and DB update cannot be one distributed transaction. A crash after SMTP acceptance but before the `sent` update can therefore cause one duplicate after stale recovery; Punga Mail prefers that rare duplicate to silently losing a message.


## `#__pungamail_mail_settings`

Singleton storage for Punga Mail-specific mail-account secrets and returned-mail status. The returned-mail IMAP password and optional Custom SMTP password are encrypted. 0.6.2 stores only the **latest** returned-mail check timestamp/status, a compact JSON counter summary, and a sanitized failure message. 0.6.4 adds the timestamp of the latest returned-mail result acknowledged on the Dashboard so a reviewed warning can stay hidden while a later check still creates a fresh attention item. Joomla Scheduled Tasks remains the authoritative task-execution history; Punga Mail does not create an unbounded duplicate run-history table merely to render the Delivery/Dashboard status.


## `#__pungamail_statistics_state`

Stores the single Statistics reporting baseline (`id=1`, `reset_at`). **Reset statistics** deletes accumulated campaign-click rows and advances this baseline instead of deleting Newsletter, queue, bounce, subscriber, or audit history. Dashboard period queries use the later of the selected period cutoff and this reset baseline.

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
- `0.4.0.sql` — changes the default for new Newsletter `include_subscribers` rows to `0`, adds `audience_mode` to Channels, and creates `#__pungamail_topic_groups` for selected Joomla-group eligibility. Existing Newsletter audience values are not changed.

- `0.4.1.sql` — version marker only; 0.4.1 fixes Channel/group persistence logic and does not change the schema.
- `0.4.2.sql` — version marker only; 0.4.2 fixes administrator timezone presentation and the Automatic Newsletter date/time control without changing the schema.

- `0.4.3.sql` — version marker only; 0.4.3 fixes plugin bootstrap and unsubscribe routing without changing the schema.

- `0.4.4.sql` — version marker only; 0.4.4 changes administrator workflows and presentation without changing the schema.
- `0.4.5.sql` — version marker only; 0.4.5 refines administrator authoring and delivery-health presentation without changing the schema.
- `0.5.0.sql` — adds Automatic Newsletter content-selection controls: `content_order`, `max_items`, and `minimum_items`.
- `0.5.1.sql` — authoring/UX maintenance marker; no schema changes.
- `0.5.2.sql` — unsaved-change tracking correction marker; no schema changes.
- `0.6.0.sql` — creates `#__pungamail_content_layouts` for the central default/per-content-type selected-content layout system. The package installer migrates the former component-wide layout into the central Default layout; legacy per-Newsletter/per-Template overrides remain stored because they cannot always be mapped losslessly.
- `0.6.1.sql` — schema-version marker only. Newsletter archiving uses the existing `state` column with Joomla state `2` and therefore requires no table alteration.

- `0.6.2.sql` — adds latest returned-mail check timestamp/status/result fields to `#__pungamail_mail_settings` for Delivery and Dashboard visibility.
- `0.6.3.sql` — adds Punga Mail-specific outgoing SMTP mode/host/port/security/auth/username fields and an encrypted SMTP-password column. Existing installations default to `smtp_mode = joomla`, preserving Joomla's global transport until Custom SMTP is explicitly selected.
- `0.6.4.sql` — adds `bounce_last_check_acknowledged_at` to `#__pungamail_mail_settings` so Dashboard suppression warnings can be acknowledged without changing bounce/suppression data.
- `0.6.5.sql` — database-version marker for live unsaved Joomla-user Channel eligibility and subscriber deletion; no schema change.
- `0.6.7.sql` — database-version marker for the clickable Automatic Newsletter state control; no schema change.
- `0.6.8.sql` — database-version marker for duplicate-subscriber protection and Joomla User Custom Field placeholders; no schema change.
- `0.6.9.sql` — database-version marker for the Joomla User Custom Field name/discovery correction; no schema change.
- `0.6.10.sql` — database-version marker for frontend Joomla document-title composition; no schema change.
- `0.6.11.sql` — no-op version marker for the Joomla ACL/Permissions release; no database schema change is required.
- `0.6.12.sql` — no-op version marker for the Joomla user-profile display fix; no database schema change is required.
- `0.6.13.sql` — no-op version marker for Channel lifecycle/membership consistency fixes; no database schema change is required.
- `0.6.14.sql` — no-op version marker for Channel permanent-delete cascade fixes; no database schema change is required.
- `0.6.15.sql` — no-op version marker for registered content-type routing fixes; no database schema change is required.
- `0.6.16.sql` — adds `archived` and `archived_at` to send-queue rows plus an archive/filter index; archive metadata never changes the delivery status.
- `0.6.17.sql` — version marker for the diagnostics-copy cleanup; no schema change.
- `0.6.18.sql` — version marker for Joomla Media Manager logo fields and stable custom-CSS mail selectors; no schema change.
- `0.6.19.sql` — version marker for the hierarchical Mail Layout editor, header/footer regions, and background-image styling; no schema change.
- `0.6.20.sql` — version marker for Header background-image display behavior; no schema change.
- `0.6.21.sql` — version marker for Automatic Newsletter test sending and localized `{date}` placeholders; no schema change.
- `0.6.22.sql` — version marker for generic content-layout date/time range formatters and nested placeholder-valued arguments; no schema change.
- `0.6.23.sql` — version marker for site-language/site-timezone date formatting across newsletter content formatters; no schema change.

- `0.6.24.sql` — adds `#__pungamail_digest_filters` and migrates legacy per-source category restrictions to generic `catid IN (...)` rules.
- `0.6.25.sql` — version marker for type-aware Automatic Newsletter filter inputs; no schema change.
- `0.6.26.sql` — version marker for the Automatic Newsletter filter-editor JavaScript hotfix; no schema change.
- `0.6.27.sql` — adds campaign-tracking scope and UTM override columns to ordinary and Automatic Newsletters. It also ships generic registered-content fixes for Joomla `"null"` mappings and legacy zero publication dates without introducing source-specific schema.
- `0.6.28.sql` — adds aggregate/link-level trusted internal campaign-click storage used by Punga Mail Statistics.
- `0.6.32.sql` — adds the Statistics reset-baseline state table; unsubscribe campaign attribution and lifecycle events reuse existing event/campaign storage.
- `0.6.33.sql` — repairs Automatic Newsletter history/cutoff state from existing genuinely sent generated newsletters. No schema columns are added.


### Automatic Newsletter first-run cutoff

`#__pungamail_digests.first_run_cutoff_mode` stores `recurrence`, `lookback`, or `all`. `first_run_lookback_hours` stores the custom recent-period duration. These fields are consulted only while `last_cutoff_at` is empty. `last_cutoff_at` is advanced only after a generated Automatic Newsletter actually reaches a sent state with at least one delivered recipient; draft creation, queue creation, skipped/no-content runs, trashing, and deleting an unsent draft do not advance it. The stored cutoff uses the generation completion time of the sent Automatic Newsletter, so sending an older generated draft later cannot move the cutoff backwards.

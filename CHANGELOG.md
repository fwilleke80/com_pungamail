# Changelog

## 0.4.2 — 2026-09-04

- Fixed Automatic Newsletter schedule times displaying as UTC in the Dashboard and Automatic Newsletters list while the editor used the Joomla site timezone.
- Kept UTC as the canonical database representation while consistently converting schedule/status timestamps to the configured Joomla timezone for administrator display.
- Replaced the Automatic Newsletter editor's browser `datetime-local` input with Joomla's native calendar control including a 24-hour time picker.
- Applied the same administrator-timezone display rule to manually scheduled Newsletter status times.
- Added release regression checks for the date/time picker and UTC-to-site-timezone display contract.

## 0.4.1 — 2026-09-04

- Fixed saving Channels restricted to selected Joomla user groups. Joomla database drivers require `insertObject()` objects to be passed by reference; the 0.4.0 Channel-group relation writer passed a temporary object expression, so the Channel row could be created while the selected groups failed to save.
- Made Channel metadata and selected-group persistence transactional. If relation persistence fails, the Channel save is rolled back instead of leaving a partially saved Channel.
- Added release regression checks for by-reference Channel-group inserts and transactional Channel saves. No database schema changes are required.

## 0.4.0 — 2026-09-04

- Added a shared Punga Mail Markdown editor built on Joomla’s CodeMirror provider when available, with a plain textarea fallback, formatting toolbar, context-aware placeholder insertion, Edit/Preview mode, and collapsible detailed help. Preview uses Punga Mail’s own Markdown renderer.
- Applied the Markdown editor consistently to Newsletter and Template bodies, selected-content layouts, confirmation messages, reminder messages, and other Markdown-capable Component Options fields.
- Reorganized the Template editor so message settings live in its Settings tab, matching the Newsletter editor’s information hierarchy more closely.
- Redesigned the Dashboard as an operational overview with active-recipient, last-newsletter, next-automatic-newsletter and delivery-health cards; actionable warnings; quick actions; recent activity; upcoming mail; Channel statistics; Automatic Newsletter status; and a lightweight 30-day delivery chart. Low-level queue controls are shown only when useful as a recovery/manual action.
- Added optional email notification when an Automatic Newsletter creates a draft for review. The reviewer address is explicit, the message includes selected/excluded item counts, and it links directly to the generated Joomla administrator draft. Automatic-send runs do not trigger this notification.
- Added Channel eligibility rules: everyone, registered Joomla users, or selected Joomla user groups. Eligibility is applied to public signup, module/profile choices, administrator recipient editing, confirmation/save paths, and is rechecked dynamically at delivery time. External email-only subscribers cannot join account/group-restricted Channels.
- New Newsletters no longer preselect “All newsletter subscribers, regardless of Channel”; a recipient source must now be chosen deliberately. Existing Newsletter audience settings are preserved.
- Fixed the mail-heading layout so its background spans the full content container while the heading text keeps the configured content padding.
- Fixed CSV export with multiple selected Channels by avoiding nested positional bindings in the filtered membership subquery.
- Expanded release checks for the new access controls, authoring UI, Dashboard, draft notification, safe audience default, heading layout, CSV filter, schema migration and uninstall cleanup.

## 0.3.13 — 2026-09-04

- Automatic Newsletters can now repeat in days, weeks, or true calendar months; monthly schedules preserve their intended day-of-month instead of drifting by fixed 30-day intervals.
- Existing Automatic Newsletter intervals are migrated without changing their cadence: whole-week and whole-day intervals use the new units directly, while older custom minute intervals keep their legacy cadence until explicitly resaved.
- Registered content-source names are resolved through the current Joomla administrator language where a component language key is available.
- Newsletter content-selection titles now link to the corresponding frontend item and open in a new browser tab.
- The Template editor now uses Joomla-native Settings, Mail content, and Design tabs.
- Component Options > Newsletter reminder now includes a plain-language explanation and complete help-toggle descriptions.
- Component Options now renders **Mail heading background** as a Joomla colour picker instead of a plain text field.
- Added an independent **Mail heading text colour** with Component Options → Template → Newsletter inheritance. Existing installations fall back to the previous content-heading colour until a separate mail-heading colour is chosen.

## 0.3.12 — 2026-09-04

- Moved the Newsletter **Template** selector and **Apply template** action into the **Mail content** tab, next to the content they affect.
- Added a visible chevron to the collapsed **Selected content layout** override in Newsletter and Template editors; the chevron rotates when the section is expanded.
- Added an optional mail-heading background colour. The heading renders as a 100%-width block; leaving the background blank preserves the previous appearance. Template and Newsletter design overrides can inherit or replace the global value.
- Sanitized `{new_content}` excerpts before truncation by removing unresolved Joomla-style content-plugin commands without executing content plugins. Paired plugin markers are removed while readable enclosed text remains.
- Added Markdown support for a standalone `---` horizontal rule and email-safe `<hr>` styling.
- Added regression coverage for the editor placement, disclosure indicator, heading background, plugin-token sanitization, and horizontal-rule rendering. No database schema changes are required.

## 0.3.11 — 2026-09-03

- Fixed Markdown hard line breaks: two trailing spaces now render as `<br>` instead of being collapsed into an ordinary space.
- Fixed escaped Markdown punctuation leaking into rendered newsletter output, so values such as `28\.8\.2026` are displayed as `28.8.2026` while escaped formatting characters remain literal.
- Made the `{new_content}` layout override in Newsletter and Template editors collapsible and collapsed by default. A small badge remains visible when a custom override is active.
- Added regression coverage for Markdown hard breaks, escape handling, and the collapsed override editor presentation. No schema changes are required.

## 0.3.10 — 2026-09-03

- Split the Newsletter editor into Joomla-native **Settings**, **Mail content**, **Content selection**, and **Design** tabs to reduce vertical scrolling while preserving the existing toolbar, form controls, audience summary, and compact selected-content picker.
- Made the Component Options `{new_content}` item-template editor use monospaced text, matching the other Markdown editors.
- Added always-visible small help text listing the supported `{new_content}` item placeholders in Component Options, Templates, and individual Newsletters.
- Added release regression coverage for the tabbed editor and consistent Markdown item-template presentation. No schema changes are required.

## 0.3.9 — 2026-09-03

- Made Joomla Scheduled Task descriptions distinguish clearly between manually scheduled ordinary newsletters and recurring Automatic Newsletters.
- Changed Automatic Newsletter recurrence from an administrator-facing minute count to days. Existing minute values remain stored internally and are converted automatically, so weekly `10080` configurations appear as 7 days.
- Changed the rolling-content look-back control from hours to days and show it only when **Content from a recent time period** is selected.
- Show the unattended-send warning and confirmation only when **Send immediately** is selected.
- Disabled the **View in browser** link in administrator previews and preflight displays, matching the already non-navigable Unsubscribe link.
- Added configurable Markdown layouts for each `{new_content}` item with `{title}`, `{title_link}`, `{publish_date}`, `{excerpt}`, `{read_more}`, `{url}`, and `{content_type}` placeholders. Layouts inherit from component options to Templates to individual Newsletters.
- Added the `new_content_item_template` template/newsletter columns and release regression coverage for the new Automatic Newsletter, preview, task-description, and selected-content layout behavior.

## 0.3.8 — 2026-09-03

- Fixed the recipient editor for linked Joomla users: it now shows the current Joomla display name instead of an empty external-recipient name field.
- Kept Joomla as the source of truth for linked-user names rather than copying them into Punga Mail, so later Joomla profile-name changes appear automatically. External subscribers retain the editable recipient-name field.
- Added release regression coverage for linked-user display names and the external-only custom-name field. Added the forward-only no-op `0.3.8.sql` marker; no schema changes are required.

## 0.3.7 — 2026-09-03

- Renamed **Newsletter Topics** to **Channels / Kanäle** throughout the administrator and public UI, moved Channels directly after Templates in the Punga Mail sidebar, synchronized Joomla system-language catalogs, and simplified technical or ambiguous wording across subscription and delivery screens.
- Replaced the visible numeric Channel ordering field with Joomla-style drag-and-drop ordering in the Channels list while preserving the existing database ordering values.
- Fixed the standalone frontend Newsletter menu page so Channel choices can be saved without a signup-module context; the component now falls back to all published Channels when no module ID exists.
- Expanded the subscriber editor so administrators can see and change memberships for every non-trashed Channel, including clearly marked unpublished Channels; public forms continue to offer published Channels only.
- Fixed Automatic Newsletter validation so an unsuccessful Save or Save & Close stays in the editor, reports the error, and preserves the submitted settings instead of returning to the list and discarding the draft.
- Expanded release checks for the Channel UX, administrator membership path, standalone subscription path, drag ordering, preserved Automatic Newsletter form state, language quality, and package metadata. Added the forward-only no-op `0.3.7.sql` marker; no schema changes are required.

## 0.3.6 — 2026-09-03

- Standardized user-facing terminology on **newsletter reception** as the global master permission and **newsletter topics** as optional preferences, replacing ambiguous singular-newsletter and list/topic wording.
- Added plain-language status and consequence summaries to the public Newsletter page and signup module, including the exact outcome when no topic is selected.
- Reorganized the administrator subscriber editor into identity/permission, newsletter topics, and delivery-health sections without changing consent or suppression behavior.
- Added live audience summaries to newsletter and digest editors. Audience sources are explicitly described as combined rather than intersecting, and selecting all globally subscribed recipients visibly states that topics do not narrow the audience.
- Added a blocking Preflight check when no audience source is configured and a recipient-specific warning when all-subscriber targeting includes eligible people outside the selected topics.
- Added English/German terminology parity, administrator documentation, test coverage, and the forward-only no-op `0.3.6.sql` marker. No schema or recipient-resolution semantics changed.

## 0.3.5 — 2026-09-03

- Completed the public Newsletter subscription menu page: guests can choose from all published topics during double-opt-in signup, and logged-in users can save their preselected topic memberships independently of the global subscription switch.
- Added full subscriber editing in the administrator. Existing subscriber email addresses now open an editor for the raw global status, external recipient name, and all published topic memberships; new subscribers can receive topic memberships during creation.
- Kept consent boundaries explicit: saving topic choices alone never globally subscribes or unsubscribes an address and never clears a manual or bounce suppression. Changing a protected subscriber explicitly to Subscribed remains a deliberate administrator reactivation.
- Removed the redundant standalone suppression column from the Subscribers list. The Subscription status cell now shows the raw global state and, when applicable, a separate delivery-block/bounce badge and reason.
- Added Apply and Save & Close behavior to the subscriber editor, preserved the Subscribers sidebar context, and added English/German UI, documentation, and release-regression coverage.
- Added the forward-only no-op `0.3.5.sql` version marker; no schema changes are required.

## 0.3.4 — 2026-09-02

- Fixed a fatal error on the public Newsletter subscription menu-item page caused by `SubscriptionModel::getState()` incompatibly overriding Joomla's base-model method signature.
- Made the package manifest the single release-metadata source for build/check scripts. Archive names, source prefix, release version, and child-extension ZIP mapping are now derived automatically, so future version bumps no longer modify `build.py` or a checker version constant.
- Added a comprehensive live acceptance test guide with step-by-step coverage of installation/update, every administrator and frontend workflow, mail generation, all Scheduled Tasks, access-safe digests, queue controls, bounces, CSV, ACL/CSRF/privacy, multilingual output, and end-to-end regressions.
- Fixed the existing-installation upgrade path for the subscriber `recipient_name` column used by Joomla profile topic/preference persistence and CSV subscriber names.
- Added a conditional Joomla package-installer repair that works both for older upgraded databases, where the column is missing, and fresh 0.3.x databases, where it already exists.
- Kept the 0.3.4 SQL update as a portable version marker, avoiding conditional DDL through the database prepared-statement protocol.
- Added a cumulative migration-versus-fresh-install schema parity check so future columns cannot be added only to one installation path unnoticed.

## 0.3.3 — 2026-09-02

- Corrected signup-module topic-mode detection: no configured topic IDs always presents all published topics as choices, even when only one topic currently exists; direct single-topic mode now requires exactly one explicitly configured topic.
- Preserved global consent when a logged-in user changes only topic preferences; topic changes can no longer reactivate a globally unsubscribed subscriber.
- Hardened frontend return-URL validation against external hosts that merely share the site's URL prefix.
- Added an explicit backend component access check, mail-header control-character filtering, and stricter bounce-mailbox host/folder/username validation.
- Bounded CSV upload reads, cleared stale preview data before a new upload, protected exported cells from spreadsheet-formula execution, and made unchanged import counts accurate.
- Sanitized operational errors before logging, persistence, or display so mailbox credentials, authorization payloads, tokens, and secret query parameters are redacted.
- Made automatic-digest content access checks fail closed when a registered content type lacks usable access metadata.
- Serialized overlapping digest generation and bounce-mailbox processing with database advisory locks.
- Made the Dashboard tolerate incomplete schema updates and show an actionable Joomla Database repair notice instead of failing the entire page.
- Expanded release checks for consent, topic modes, access controls, redaction, CSV safety, task locking, database-update resilience, and English/German system-language parity.
- Added the forward-only no-op `0.3.3.sql` version marker; no schema changes are required.

## 0.3.2 — 2026-09-02

- Added a published-topic multi-select beside **Receive newsletter** in Joomla registration, frontend profile, administrator user, and administrator profile forms.
- Profile topic choices use the same normalized memberships as the frontend signup module; current memberships are preselected and clearing the selector unsubscribes only from currently published topics.
- Kept the global preference independent from topic membership: topic choices are retained while globally unsubscribed, but global opt-out and suppression still prevent all delivery.
- Added administrator/profile topic-change audit events without storing subscriber security tokens.
- Added the forward-only no-op `0.3.2.sql` version marker; the existing normalized topic schema requires no change.
- Updated English/German plug-in strings, the administrator guide, topics/signup tutorial, README, and release checks.

## 0.3.1 — 2026-09-02

- Added a comprehensive non-technical administrator guide plus focused tutorials for newsletters, digests, topics/signup, templates, delivery health/bounces, and CSV import/export.
- Documented the current distinction between the Joomla profile's global newsletter preference and topic choices; direct topic selection in the user profile is scheduled for the next feature update.
- Fixed empty-digest handling so **Create draft** always stops at an editable Draft, even when the automation otherwise uses automatic sending.
- Fixed the Dashboard fatal SQL error by using the digest definition's Joomla `state` field instead of querying a nonexistent `enabled` column.
- Added Joomla-compatible checkout fields and editor locking for newsletters, templates, topics and digest automations. Save retains the lock; Save & Close and Cancel release it; abandoned editor sessions appear in Joomla Global Check-in.
- Added the missing Save & Close toolbar action to Topic and Digest editors.
- Fixed the Delivery and CSV Import action controllers, whose private `redirect()` helpers conflicted with Joomla's inherited public controller method and caused action requests to fail during PHP class loading.
- Hardened the backend transport test with an explicit task field, Joomla's supported sender tuple API, recipient/sender validation, false-result detection, logged diagnostics and a normal backend error message instead of a generic error page.
- Added the forward-only `0.3.1.sql` migration without changing the released `0.3.0.sql` migration.
- Expanded release checks for schema/query alignment, controller inheritance collisions, editor checkout/check-in, toolbar completeness and test-mail dispatch.
- Added the subscription menu-item title and description to the administrator system-language catalogs so Joomla's Menu Item Type chooser resolves them in English and German.

## 0.3.0 — 2026-09-02

- Added first-class mailing lists/topics, normalized subscriber and newsletter memberships, topic-aware double opt-in, and module behavior for zero, one, or multiple configured topics.
- Added recurring digest definitions and run history through Joomla Scheduled Tasks. Digest content is filtered against every resolved recipient's Joomla view levels and category access; content that any recipient cannot view is excluded.
- Added scheduled newsletter delivery, cancellation, per-mailing and global queue pause/resume, cancellation of an unsent remainder, and immutable operational delivery statistics.
- Added DSN bounce-mailbox processing through PHP IMAP, retained hard/soft/unknown bounce history, configurable soft-bounce suppression, and administrator suppression recovery.
- Added comprehensive preflight validation with blocking errors, warnings, resolved/excluded-recipient inspection, link/image checks, and message-size reporting.
- Added immutable sent-newsletter browser views with Joomla SEF routing and configurable browser links.
- Added suppression-safe UTF-8 CSV preview/mapping/import and filtered export.
- Added Reply-To inheritance, stable List-ID headers, supported envelope-sender handling, Joomla transport test mail, and local mail diagnostics.
- Added configurable body-heading inheritance: component default, template override, and newsletter override, including an intentional no-heading mode.
- Added the forward-only `0.3.0.sql` migration; existing IDs, snapshots, send-rate/batch configuration, and retry configuration are preserved.
- Moved bounce/return mailbox credentials into Component Options while keeping operational controls, diagnostics, and history on the Delivery page; stored passwords remain encrypted and are never rendered back.
- Expanded the Dashboard Scheduled Tasks overview and added contextual warnings when enabled digests, scheduled newsletters, or configured bounce processing lack their required enabled Joomla task.

## 0.2.6 — 2026-09-02

- Fixed the untranslated Restore toolbar label in trashed Newsletter and Template views by using component-owned English/German language keys.
- Removed forced `table-secondary` styling from trashed rows so Joomla/Atum provides the same light/dark-mode table colours as normal list rows.
- Reordered Newsletter editor toolbar actions: Save, Save & Close and Cancel remain on the left; Preview, Test Mail and Check recipients & send are grouped on the right. Template Preview is likewise separated to the right.
- Enabled Joomla's native **Toggle Inline Help** control in Component Options via `config.xml`.
- Added **Subscribers → New**. Administrators can directly add an external email recipient or select an existing Joomla user, which explicitly enables that user's Punga Mail subscription preference.
- Administrator-added subscriptions remove an existing suppression deliberately and write audit events.
- Added `COM_PUNGAMAIL_CONFIGURATION` so Joomla's configuration page/browser title renders as **Punga Mail: Options** / **Punga Mail: Optionen** instead of the untranslated language key.
- Added the no-op `0.2.6.sql` version marker; no database schema changes are required.

## 0.2.5 — 2026-09-02

- Moved subscriber-facing newsletter strings for the footer reason, unsubscribe label and “Read more” label from the Administrator catalog to the Website language catalog so they are discoverable as Website language overrides.
- Added `MailTextService`, which explicitly resolves the configured frontend language and Joomla Website override file even when newsletters are rendered from the Administrator or Scheduled Tasks applications.
- Added **Mail Design → Footer reason (Markdown)** to Component Options. It uses the localized Website footer string as its initial/default content and supports Markdown.
- Added the footer reason to the plain-text newsletter alternative as well as HTML output.
- Added a regression test proving that a Website language override wins over the component frontend language string.
- Added the no-op `0.2.5.sql` version marker so Joomla’s recorded database version remains aligned with the 0.2.5 manifest.

## 0.2.4 — 2026-09-01

- Fixed a regression in the newsletter editor where **Apply filters** submitted an empty Joomla `task` value and returned to the Punga Mail Dashboard, discarding the visible editor state. The regression was introduced by the 0.2.2 `adminForm` toolbar conversion; registered content-type support remains unchanged.
- Converted the in-form **Apply filters** and **Apply template** actions to Joomla `Joomla.submitbutton(...)` task submission so they cooperate with the editor's canonical hidden `task` field.
- Applying content filters now saves the complete current draft state before refreshing the editor, including incomplete drafts, so title/body/recipient/style/content selections are preserved.
- Added release regression checks for in-form Joomla task-button collisions.
- Added the no-op `0.2.4.sql` version marker so Joomla's recorded database version remains aligned with the 0.2.4 manifest.

## 0.2.3 — 2026-09-01

- Fixed a fatal Joomla administrator editor error caused by calling the nonexistent `ToolbarHelper::save2close()` method. Newsletter and Template editors now use Joomla's canonical `ToolbarHelper::apply()` for **Save** and `ToolbarHelper::save()` for **Save & Close** while retaining the existing controller tasks.
- Added a release regression check that rejects unsupported ToolbarHelper methods in the editor views.
- Removed **Process queue** from the Newsletters list toolbar. Normal newsletter delivery remains **Check recipients & send** followed by Joomla Scheduled Tasks.
- Added **Process queue now** to the Dashboard queue card as an explicit maintenance/diagnostic action with CSRF protection and return-to-dashboard behavior.
- Added the no-op `0.2.3.sql` version marker so Joomla's stored database schema version stays aligned with the 0.2.3 manifest.

## 0.2.2 — 2026-09-01

- Fixed Joomla administrator sidebar context for newsletter/template editors, previews and preflight screens. Secondary screens now retain the owning `newsletters`/`templates` URL context so Joomla keeps **Components → Punga Mail** expanded and the relevant submenu selected.
- Replaced the newsletter `{site_name}` placeholder with `{recipient}`. Registered Joomla users receive their display name; external subscribers receive their email address.
- Added `recipient_name` to frozen queue rows so personalization is snapshotted at queue time instead of performing a live user lookup during delivery.
- Backend newsletter/template previews personalize `{recipient}` with the currently logged-in administrator. Test mail does the same because it is sent to that administrator.
- Added a forward-only `0.2.2.sql` migration and updated fresh-install schema/documentation.
- Expanded renderer and release regression checks for recipient personalization and administrator sidebar routing.
- Moved newsletter and template editor actions into Joomla's standard administrator toolbar. Newsletter drafts now expose Save, Save & Close, Preview, Send test mail, Check recipients & send, and Cancel at the top of the page; templates expose Save, Save & Close, Preview, and Cancel.

## 0.2.1 — 2026-09-01

- Fixed `{new_content}` rendering in previews and test/queued mail. The 0.2.0 internal Markdown sentinel could be modified by underscore-emphasis parsing and leak into output; 0.2.1 removes the sentinel approach entirely and renders Markdown segments around the placeholder directly.
- Added GitHub-style Markdown pipe tables with optional left/center/right alignment. Tables render with conservative inline email CSS and become readable tab-delimited text in the plain-text alternative.
- Added Markdown-table help to newsletter and template editors.
- Added renderer regression checks.
- No database schema changes; `0.2.1.sql` is an intentional no-op version marker so Joomla’s stored database version matches the manifest.

## 0.2.0 — 2026-09-01

- Added reusable newsletter Templates with Joomla-standard list/trash workflow, Markdown editing, preview and style overrides.
- Added component-level mail design plus inheritable per-template and per-newsletter styling and optional advanced CSS.
- Generalized “New content since …” from Joomla articles to usable registered Joomla content types without depending on deprecated UCM persistence.
- Added content-type filters, search and a bounded scrollable candidate-content panel.
- Generalized selected-item storage to `(source_key, source_item_id)` and added newsletter source selections.
- Added Joomla SEF routing and a public Newsletter subscription menu-item/view for confirmation, unsubscribe and status routes.
- Fixed recursive backend preview navigation by disabling unsubscribe navigation in previews/preflight.
- Added optional newsletter-age reminder through Joomla Scheduled Tasks.
- Added direct Dashboard link to Joomla Scheduled Tasks.
- Added opt-in **Uninstall: Remove database tables** setting; data preservation remains the default.
- Added forward-only `0.2.0.sql` migration while preserving released 0.1.x migrations.
- Updated English/German translations, README, concept and database documentation.

## 0.1.1 — 2026-09-01

- Converted Newsletters and Subscribers to Joomla-standard administrator list views with Search Tools, sorting, filtering and pagination.
- Added independent Joomla newsletter record state with Trash, Restore and permanent-delete workflow.
- Added versioned `0.1.1.sql` migration without modifying the released 0.1.0 baseline.
- Added Punga Mail Dashboard with version, subscriber/newsletter/queue metrics and Scheduled Task status.
- Added complete German translations for the component, signup module, user plugin and task plugin, including public subscription pages.
- Added configurable double-opt-in confirmation subject and Markdown message with documented `{confirmation_url}`, `{site_name}` and `{email}` placeholders.
- Added `{new_content}` newsletter placeholder; selected articles are inserted only at that position and no automatic heading is generated.
- Added `{site_name}` newsletter placeholder documentation.
- Added editable **Content published since** date for the candidate article list.
- Added rendered HTML/plain-text newsletter preview using the same renderer as test and production sends.
- Added Markdown image support for HTTP(S), root-relative and site-relative hosted images with responsive email-safe output and plain-text fallback.
- Clarified suppression semantics directly in the Subscribers administration page.
- Expanded release validation to protect the immutable migration chain.

## 0.1.0 — 2026-09-01

Initial Punga Mail build.

- Joomla 6 package containing component, signup module, user plugin and task plugin.
- Double-opt-in external subscriptions.
- Joomla profile subscription preference.
- Permanent suppression list.
- Markdown newsletter editor.
- New `com_content` article discovery, selection, ordering and per-newsletter overrides.
- Joomla user-group recipient targeting.
- Recipient preview and deduplication.
- HTML/plain-text rendering, test mail and immutable send snapshots.
- Persistent idempotent send queue with retry handling.
- Visible unsubscribe flow and RFC 8058 one-click unsubscribe support.
- Clean versioned database schema and migration baseline.
- Added `docs/CONCEPT.md` covering implemented features, planned directions, user workflows and explicit non-goals.

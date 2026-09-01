# Changelog

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

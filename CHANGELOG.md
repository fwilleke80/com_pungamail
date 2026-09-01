# Changelog

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

# Punga Mail

Punga Mail is a focused newsletter package for Joomla! 6. It combines Joomla users and external double-opt-in subscribers, lets editors compose newsletters in Markdown, curates newly published Joomla articles, and sends through Joomla's configured mail transport using a persistent queue.

Version: **0.1.1**

## Package contents

- `com_pungamail` — dashboard, subscriber management, newsletter composition, confirmation/unsubscribe endpoints, rendering and send queue.
- `mod_pungamail_signup` — frontend double-opt-in signup and logged-in subscription-status module.
- `plg_user_pungamail` — adds the newsletter preference to Joomla user profile forms.
- `plg_task_pungamail` — Joomla Scheduled Tasks routine for processing the persistent send queue.

## 0.1.1 feature set

- Joomla-standard administrator list views for Newsletters and Subscribers with Search Tools, sortable columns and pagination.
- Newsletter Trash, Restore and permanent-delete workflow using a Joomla record `state` independent from delivery status.
- Punga Mail Dashboard with installed version, subscriber/newsletter/queue counts and Scheduled Task status.
- English and German (`en-GB`, `de-DE`) translations for component, module and plugins, including public subscription pages.
- External subscriptions with double opt-in and throttled confirmation requests.
- Joomla-user subscription/profile opt-out.
- Persistent suppression records for explicit opt-outs.
- Configurable double-opt-in confirmation subject and Markdown body.
- Confirmation placeholders: `{confirmation_url}`, `{site_name}`, `{email}`.
- Newsletter drafts written in safe Markdown.
- Markdown images using HTTP(S), root-relative or site-relative URLs; images remain remotely hosted.
- Newsletter placeholders: `{new_content}` and `{site_name}`.
- `{new_content}` inserts selected Joomla article entries exactly at the placeholder position and never adds an automatic heading.
- Editable **Content published since** date, initially derived from the preceding sent newsletter cutoff.
- Selection/order of published `com_content` articles plus newsletter-specific title/excerpt overrides.
- Joomla user-group targeting in addition to confirmed subscribers.
- Exact recipient preflight and deduplication by normalized email address.
- Rendered HTML and plain-text newsletter preview before sending.
- Test send to the current administrator.
- HTML + plain-text multipart mail through Joomla's configured mailer (SMTP when Joomla is configured for SMTP).
- Immutable send snapshots.
- Persistent, idempotent queue with retry metadata and stale-worker recovery.
- Joomla Scheduled Tasks integration plus manual queue processing.
- RFC 8058 `List-Unsubscribe` / one-click unsubscribe support when the endpoint is HTTPS.
- Human-facing unsubscribe page requiring explicit POST confirmation.
- Subscriber audit events.

## Installation

Install `pkg_pungamail_v0-1-1.zip` through **System → Install → Extensions**.

The package enables the Punga Mail user and task plugins automatically. The signup module is installed but not published; publish it in the desired site module position.

Open **Components → Punga Mail → Options** and review sender identity, subscription defaults, confirmation mail and queue settings.

For unattended delivery, create and enable a Joomla Scheduled Task of type **Punga Mail — Process send queue**. Punga Mail does not require its own cron endpoint; it hooks into Joomla's scheduler. Your server still needs to execute Joomla Scheduled Tasks by whatever mechanism your Joomla installation uses.

## Newsletter Markdown

The editor intentionally supports a conservative, email-oriented subset:

- headings
- paragraphs
- bold and italic text
- links
- ordered and unordered lists
- images

Raw HTML is escaped.

Example:

```markdown
# September news

Here is what changed this month.

![Courtyard](https://example.com/images/courtyard.jpg)

## New on the website

{new_content}

Best wishes,
The team
```

`{new_content}` must be placed on its own line. Selected Joomla articles are not appended when the placeholder is absent.

## Development

The source archive is the canonical Git-ready project tree. Generated installer archives are excluded from it.

Requirements:

- Python 3.10+
- PHP CLI for the full lint pass (optional for building, required by the release validation used for official builds)

Build:

```bash
python3 build.py
```

Outputs are written to `dist/`.

## Database and migrations

See [`docs/DATABASE.md`](docs/DATABASE.md). Important rules:

1. Runtime PHP never mutates schema.
2. `install.mysql.sql` always represents the newest fresh-install schema.
3. Released migrations are immutable.
4. Every schema-changing release adds a versioned transition under `sql/updates/mysql/`.
5. Queue idempotency and suppression semantics are enforced at the database boundary where appropriate.
6. Uninstall currently preserves Punga Mail data; `sql/purge.mysql.sql` is the explicit manual destructive path.

## Concept and roadmap

See [`docs/CONCEPT.md`](docs/CONCEPT.md) for implemented functionality, workflows, architectural decisions, next-update plans and non-goals.

## License

Punga Mail is released under the MIT License. See [`LICENSE.md`](LICENSE.md).

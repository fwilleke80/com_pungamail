# Punga Mail

Punga Mail is a focused newsletter package for Joomla! 6. It combines Joomla users and external double-opt-in subscribers, lets editors compose newsletters in Markdown, and presents newly published Joomla content for inclusion in a curated “What’s new” section.

Version: **0.1.0**

## Package contents

- `com_pungamail` — administration, subscriber management, newsletter composition, confirmation/unsubscribe endpoints, rendering and send queue.
- `mod_pungamail_signup` — frontend newsletter signup/status module.
- `plg_user_pungamail` — adds the newsletter preference to Joomla user profile forms.
- `plg_task_pungamail` — Joomla Scheduled Tasks routine for processing the persistent send queue.

## 0.1 scope

- External email subscriptions with double opt-in.
- Joomla-user subscriptions/profile opt-out.
- Permanent suppression records for unsubscribed addresses.
- Newsletter drafts written in Markdown.
- Selection and ordering of `com_content` articles published since the previous sent newsletter.
- Newsletter-specific article title and excerpt overrides.
- Joomla user-group targeting in addition to subscribers.
- Recipient deduplication by normalized email address.
- HTML + plain-text multipart email.
- Test send and preview.
- Immutable send snapshots.
- Persistent, idempotent send queue with retry metadata.
- RFC 8058 `List-Unsubscribe` / one-click unsubscribe headers.
- Visible unsubscribe page requiring an explicit POST confirmation.
- Subscriber audit events.

## Installation

Install the generated `pkg_pungamail_v0-1-0.zip` through **System → Install → Extensions**.

The package enables the Punga Mail user and task plugins automatically. The signup module is installed but not published; publish it in the desired site module position.

After installation, open **Components → Punga Mail → Options** and review sender identity, user-subscription default, confirmation lifetime and queue settings.

For unattended delivery, create a Joomla Scheduled Task of type **Punga Mail — Process send queue**. The administrator UI also provides a manual queue-processing action.

## Development

The source archive is the canonical Git tree. Generated installer archives are intentionally not committed.

Requirements for building:

- Python 3.10+
- standard `zipfile` module

Build:

```bash
python3 build.py
```

Outputs are written to `dist/`.

## Project concept

The product scope, implemented 0.1 feature set, planned directions, non-goals and user workflows are documented in [`docs/CONCEPT.md`](docs/CONCEPT.md).

## Database and migrations

Database ownership and migration policy are documented in [`docs/DATABASE.md`](docs/DATABASE.md).

Important rules:

1. Schema changes are never performed ad hoc in runtime PHP.
2. Fresh installs use `administrator/components/com_pungamail/sql/install.mysql.sql`.
3. Every release that changes the schema gets a monotonically versioned SQL file in `sql/updates/mysql/`.
4. Upgrade scripts must tolerate skipped releases because Joomla applies all intermediate schema updates in order.
5. Queue uniqueness and suppression semantics are enforced in the database, not only in application code.
6. Punga Mail deliberately preserves its data tables when the extension is uninstalled. See `docs/DATABASE.md` for the explicit purge procedure.

Joomla records the installed component schema version in `#__schemas`, using the update files declared by the component manifest.

## Security model

- Signup and profile mutations use Joomla CSRF protection.
- Confirmation tokens are random and stored only as SHA-256 hashes.
- Confirmation requests expire and are throttled.
- Unsubscribe links use a signed token derived from Joomla’s site secret and the subscriber identity; no reversible secret is stored.
- GET requests never unsubscribe a recipient. The visible unsubscribe page requires POST. RFC 8058 one-click unsubscribe is accepted only as the standards-defined POST endpoint.
- Email addresses are normalized for deduplication; the original address casing is retained for display/sending.
- Queue rows have a unique `(newsletter_id, email_normalized)` key, providing an idempotency barrier against duplicate delivery.

## Sending semantics

When a newsletter is queued, Punga Mail freezes:

- subject,
- rendered HTML,
- rendered plain text,
- selected article title/excerpt/URL snapshots,
- recipient email addresses.

Editing the draft after queueing is not allowed. To make changes, duplicate the newsletter into a new draft.

A queue row moves through `pending → processing → sent` or `failed`. Transient failures are retried up to the configured maximum. A sent row is never selected for delivery again.

“Delivered” is intentionally not claimed: Punga Mail knows only whether Joomla’s configured mail transport accepted the message for sending.

## License

Punga Mail is released under the MIT License. See [`LICENSE.md`](LICENSE.md).

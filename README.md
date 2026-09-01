# Punga Mail

Punga Mail is a focused, self-hosted newsletter extension for **Joomla! 6**.

Version: **0.2.5**

Its core workflow is deliberately small:

**subscribe → confirm → compose → curate new Joomla content → preview → queue → send → unsubscribe**

Punga Mail uses Joomla's users, user groups, content-type registry, routing, mailer, Scheduled Tasks, administrator list conventions, language system, and extension update/migration infrastructure rather than recreating those subsystems.

## Highlights

### Subscribers

- Joomla users and external email-only subscribers share one canonical subscription model.
- Public signup module with double opt-in.
- Configurable Markdown confirmation mail.
- Logged-in users can manage the same subscription state from their profile.
- Persistent suppressions prevent an unsubscribed address from silently re-entering a recipient set through Joomla user-group targeting.
- Human unsubscribe page plus RFC 8058 one-click unsubscribe support where HTTPS permits it.
- English and German UI.

### Newsletter authoring

- Markdown body with headings, emphasis, lists, links, images and GitHub-style pipe tables.
- HTML and plain-text alternatives are generated from the same source.
- Markdown images can reference HTTP(S), root-relative or site-relative images hosted on the Joomla site.
- `{new_content}` inserts the selected content items exactly where the author places it; Punga Mail does not append content automatically or generate a heading.
- `{recipient}` inserts the recipient’s Joomla display name for registered users and falls back to the email address for external subscribers. Backend previews use the currently logged-in administrator.
- Newsletter and template editors use Joomla's standard top administrator toolbar for Save, Save & Close, Preview, Cancel, and newsletter delivery actions.
- Rendered HTML/text preview and immediate test mail before queueing.
- Per-item title and excerpt overrides do not modify the original content item.

### Joomla registered content types

The **New content since …** picker is not limited to `com_content` articles. Punga Mail reads Joomla's registered content-type metadata and offers usable types as selectable sources.

This keeps integrations Joomla-native: a correctly registered third-party content type normally requires **no Punga Mail-specific code**. Punga Mail reads the registered table/field mapping directly and does not depend on Joomla's deprecated UCM storage classes. If a future extension proves impossible to consume from its registered metadata, a narrowly scoped fallback integration can be considered then; it is not part of the normal 0.2.0 architecture.

The newsletter editor provides:

- editable **Content published since** date;
- selectable registered content types;
- client-side search;
- compact scrollable candidate list;
- selection count and per-item metadata;
- ordering and newsletter-local title/excerpt overrides.

### Templates

0.2.0 adds reusable newsletter templates:

- standard Joomla Templates list with Search Tools, sorting, pagination, Trash/Restore/Delete;
- default subject and Markdown body;
- the same placeholders/images/rendering model as newsletters;
- template-specific mail-style overrides;
- rendered preview;
- applying a template **copies** its subject/body/style into the newsletter, so later template edits cannot change an existing draft or sent newsletter.

### Administrator navigation

Secondary Punga Mail screens preserve their owning Joomla administrator submenu context. Opening a newsletter/template editor, preview or preflight page therefore keeps **Components → Punga Mail → Newsletters/Templates** expanded and selected instead of collapsing the sidebar.

### Mail design

Component Options define the base mail design, including dimensions, backgrounds, typography, links, logo, padding, footer and optional advanced CSS. The footer reason text is a configurable Markdown field; when no custom value is stored, it uses the frontend/Website language string `COM_PUNGAMAIL_MAIL_FOOTER_REASON`.

Templates can override individual design values, and newsletters can override them again. Blank override fields mean **inherit**. The inheritance order is:

**Component defaults → Template overrides → Newsletter overrides**

Punga Mail emits conservative email-safe inline styles as the reliable baseline. Optional custom CSS is additive and should be considered an enhancement because CSS support varies between mail clients. Subscriber-facing reusable mail strings (`COM_PUNGAMAIL_MAIL_FOOTER_REASON`, `COM_PUNGAMAIL_MAIL_UNSUBSCRIBE`, `COM_PUNGAMAIL_MAIL_READ_MORE`) live in the Website language catalog and Website language overrides are honored even when rendering occurs in the administrator or Scheduled Tasks application.

### Queue and Scheduled Tasks

Test messages are sent synchronously through Joomla's configured mailer. Real newsletters are frozen into an immutable recipient/message snapshot and placed into the persistent queue.

Punga Mail therefore uses whatever mail transport Joomla is configured to use, including Joomla SMTP settings; it does not maintain a second SMTP configuration or call PHP `mail()` directly.

For unattended delivery create and enable the Joomla Scheduled Task:

**Punga Mail — Process send queue**

The Dashboard links directly to Joomla's Scheduled Tasks manager and provides a **Process queue now** diagnostic/maintenance action. The Newsletters list deliberately does not expose that button because normal delivery is handled by **Check recipients & send** plus the Scheduled Task. Queue processing is batch-based, retryable, stale-worker recoverable, and protected by database-level recipient uniqueness plus atomic worker claiming.

### Optional newsletter reminder

Component Options can enable a reminder when the last successfully sent newsletter is older than a configured number of days. Create a Joomla Scheduled Task of type:

**Punga Mail — Newsletter reminder**

Running it daily is appropriate. Punga Mail records one reminder per sent-newsletter cycle, so repeatedly executing the task after the threshold does not send a daily reminder flood.

### SEF frontend URLs

0.2.0 adds a public **Newsletter subscription** menu-item type and a component router.

For clean confirmation/unsubscribe/status URLs:

1. Create a Joomla menu item of type **Punga Mail → Newsletter subscription**.
2. Keep the item **Published**.
3. It may be hidden from the visible menu; Punga Mail can still use it as its SEF routing anchor.

The subscription landing page is useful on its own and also anchors confirmation, unsubscribe and status routes. Mail link generation explicitly prefers a published Punga Mail subscription menu item when available.

## Installation and update

Install `pkg_pungamail_v0-2-5.zip` through **System → Install → Extensions**.

The package contains:

- `com_pungamail`
- `mod_pungamail_signup`
- `plg_user_pungamail`
- `plg_task_pungamail`

The user and task plugins are enabled automatically after installation/update.

Updating from earlier releases uses Joomla's versioned SQL migration chain. Released migration files remain immutable. 0.2.1 contains a no-op version-marker migration; 0.2.2 adds the per-recipient display-name snapshot used by `{recipient}`; 0.2.4 contains the administrator-UI bugfix version marker; 0.2.5 adds a no-op version marker for the mail-language/footer configuration update.

## Uninstall/data policy

By default, uninstalling Punga Mail **preserves its database tables and data**.

Component Options → **Maintenance / Data** contains:

**Uninstall: Remove database tables**

The default is **No**. Set it to **Yes** only when you explicitly want uninstalling the package to destroy Punga Mail subscribers, suppressions, templates, newsletters, queue/history and audit data.

The source tree also contains `sql/purge.mysql.sql` for deliberate manual cleanup.

## Database and engineering policy

Punga Mail 0.2.5 retains the same nine-table model introduced in 0.2.0 and the `recipient_name` send-queue snapshot added in 0.2.2. Important design rules include:

- explicit indexes and uniqueness constraints;
- UTC application timestamps;
- separate Joomla record state and newsletter delivery status;
- `(newsletter_id, source_key, source_item_id)` identity for selected content;
- `(newsletter_id, email_normalized)` database-level queue idempotency barrier;
- no hidden runtime schema migrations;
- append-only versioned SQL migration history;
- immutable send snapshots after queueing, including the resolved recipient display name;
- no cross-extension SQL foreign keys.

See [`docs/DATABASE.md`](docs/DATABASE.md) for details and [`docs/CONCEPT.md`](docs/CONCEPT.md) for the product architecture/roadmap.

## Building

The canonical source tree contains the complete project. Generated release files live in `dist/` and are not part of the Git-oriented source archive.

Requirements for local validation/build:

- Python 3
- PHP CLI for PHP syntax linting

Run:

```bash
python3 tools/check.py
python3 build.py
```

`build.py` runs the release checks first and produces:

- `dist/pkg_pungamail_v0-2-5.zip` — Joomla installer package
- `dist/pungamail_v0-2-5_source.zip` — complete Git-ready source tree

## License

Punga Mail is released under the MIT License. See [`LICENSE.md`](LICENSE.md).

# Punga Mail

Punga Mail is a focused, self-hosted newsletter extension for **Joomla! 6**.

Version: **0.3.4**

Its core workflow is deliberately small:

**subscribe → choose topics → confirm → compose or automate → preflight → schedule/queue → send → process bounces**

Version 0.3.4 repairs the existing-installation migration for subscriber display names. It also retains the 0.3.3 stabilization work: stricter consent preservation, corrected signup-module topic modes, fail-closed digest access checks, safer CSV/mail/error handling, overlapping-task protection, and a dashboard warning for incomplete database updates. Existing Joomla-group targeting remains available as a separate audience source.

Automatic digests enforce website visibility before generation. Punga Mail resolves the intended recipients and includes a content item only when every recipient would normally be authorized to view it through Joomla access levels and, where applicable, category access. This conservative shared-content rule prevents restricted website content from leaking through email.

Punga Mail uses Joomla's users, user groups, content-type registry, routing, mailer, Scheduled Tasks, administrator list conventions, language system, and extension update/migration infrastructure rather than recreating those subsystems.

## Administrator documentation

- [`docs/USER_GUIDE.md`](docs/USER_GUIDE.md) — complete non-technical administrator reference
- [`docs/TEST_GUIDE.md`](docs/TEST_GUIDE.md) — comprehensive step-by-step live acceptance and regression testing
- [`docs/TUTORIAL_NEWSLETTER.md`](docs/TUTORIAL_NEWSLETTER.md) — compose, preflight, send, schedule, and monitor a newsletter
- [`docs/TUTORIAL_DIGEST.md`](docs/TUTORIAL_DIGEST.md) — create a recipient/access-safe recurring digest
- [`docs/TUTORIAL_TOPICS_AND_SIGNUP.md`](docs/TUTORIAL_TOPICS_AND_SIGNUP.md) — topics, module modes, double opt-in, and preference management
- [`docs/TUTORIAL_TEMPLATES.md`](docs/TUTORIAL_TEMPLATES.md) — reusable content and design inheritance
- [`docs/TUTORIAL_DELIVERY_HEALTH.md`](docs/TUTORIAL_DELIVERY_HEALTH.md) — queue, mail tests, bounce processing, and operational statistics
- [`docs/TUTORIAL_IMPORT_EXPORT.md`](docs/TUTORIAL_IMPORT_EXPORT.md) — suppression-safe CSV workflows

## Highlights

### Subscribers

- Joomla users and external email-only subscribers share one canonical subscription model.
- Public signup module with double opt-in.
- Configurable Markdown confirmation mail.
- Logged-in users can manage the same global subscription and published-topic memberships directly from their Joomla profile.
- The frontend signup module remains available for registered and email-only users, but registered users do not need the module merely to choose topics.
- Persistent suppressions prevent an unsubscribed address from silently re-entering a recipient set through Joomla user-group targeting.
- Human unsubscribe page plus RFC 8058 one-click unsubscribe support where HTTPS permits it.
- English and German UI.
- Administrator **Subscribers → New** can directly add an external email recipient or enable the newsletter preference for an existing Joomla user.

### Newsletter authoring

- Markdown body with headings, emphasis, lists, links, images and GitHub-style pipe tables.
- HTML and plain-text alternatives are generated from the same source.
- Markdown images can reference HTTP(S), root-relative or site-relative images hosted on the Joomla site.
- `{new_content}` inserts the selected content items exactly where the author places it; Punga Mail does not append content automatically or generate a heading.
- `{recipient}` inserts the recipient’s Joomla display name for registered users and falls back to the email address for external subscribers. Backend previews use the currently logged-in administrator.
- Newsletter and template editors use Joomla's standard top administrator toolbar. Save, Save & Close and Cancel stay on the left; preview/test/send actions are grouped on the right.
- Newsletter, template, topic and digest editors use Joomla checkout/check-in. Closing an editor without Save & Close or Cancel leaves a recoverable entry in Joomla Global Check-in.
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

### Administrator UI

- Trashed newsletter/template rows use the same Joomla table colours as active rows, which keeps Atum light/dark mode styling intact.
- Component Options expose Joomla's standard **Toggle Inline Help** control for field descriptions.
- The component configuration page title is localized as **Punga Mail: Options** / **Punga Mail: Optionen**.

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

Enable the additional task types for the corresponding features:

- **Punga Mail — Queue scheduled newsletters** moves due scheduled newsletters into the immutable send queue.
- **Punga Mail — Generate automatic digests** runs due digest definitions. Automatic-send digests subsequently use the normal send-queue task.
- **Punga Mail — Process bounce mailbox** retrieves returned mail and applies bounce history/suppression rules; it requires PHP IMAP and mailbox credentials under **Component Options → Bounce / return mailbox**.

The Dashboard lists all Punga Mail task types, links directly to Joomla's Scheduled Tasks manager, and warns contextually when an enabled digest, scheduled newsletter, or configured bounce mailbox lacks its required task. It provides a **Process queue now** diagnostic/maintenance action. The Newsletters list deliberately does not expose that button because normal delivery is handled by **Check recipients & send** plus the Scheduled Task. Queue processing is batch-based, retryable, stale-worker recoverable, and protected by database-level recipient uniqueness plus atomic worker claiming.

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

Install `pkg_pungamail_v0-3-4.zip` through **System → Install → Extensions**.

The package contains:

- `com_pungamail`
- `mod_pungamail_signup`
- `plg_user_pungamail`
- `plg_task_pungamail`

The user and task plugins are enabled automatically after installation/update.

Updating from earlier releases uses Joomla's versioned SQL migration chain. Released migration files remain immutable. The 0.3.1 migration adds Joomla checkout metadata to editable records. The 0.3.2 and 0.3.3 migrations are no-op version markers. During a 0.3.4 package update, the Joomla installer checks the actual subscriber table and repairs the missing display-name column only when required; its SQL file is a portable version marker.

## Uninstall/data policy

By default, uninstalling Punga Mail **preserves its database tables and data**.

Component Options → **Maintenance / Data** contains:

**Uninstall: Remove database tables**

The default is **No**. Set it to **Yes** only when you explicitly want uninstalling the package to destroy Punga Mail subscribers, suppressions, templates, newsletters, queue/history and audit data.

The source tree also contains `sql/purge.mysql.sql` for deliberate manual cleanup.

## Database and engineering policy

Punga Mail 0.3.4 uses the existing normalized topic, digest, bounce and preference-request relationships plus Joomla-compatible editor checkout metadata, while preserving existing identifiers and immutable snapshots. Important design rules include:

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

- `dist/pkg_pungamail_v0-3-4.zip` — Joomla installer package
- `dist/pungamail_v0-3-4_source.zip` — complete Git-ready source tree

The canonical release metadata lives in `package/pkg_pungamail.xml`. The build script reads the package name, version, and child-extension archive names from that manifest, so a version bump does not require editing `build.py` or the version in `tools/check.py`. Joomla still requires the version in each constituent extension manifest; the release checker verifies that they all match the package manifest.

## License

Punga Mail is released under the MIT License. See [`LICENSE.md`](LICENSE.md).

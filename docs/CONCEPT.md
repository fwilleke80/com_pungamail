# Punga Mail — concept and roadmap

## Purpose

Punga Mail is a focused Joomla! 6 newsletter system. Its central workflow is intentionally short:

**subscribe → confirm → compose → curate new Joomla content → preview → send → unsubscribe**

It is not intended to become a general marketing-automation platform.

## Product principles

- Joomla-native administration and permissions.
- Joomla users and external subscribers feed one canonical recipient model.
- Explicit opt-out/suppression always wins over automatic audience discovery.
- Double opt-in for public external subscriptions.
- Markdown-first authoring rather than a proprietary visual page builder.
- Email output has both HTML and plain-text alternatives.
- Joomla's configured mail transport is used instead of maintaining a second SMTP configuration.
- Sending is persistent, resumable and idempotent at the queue level.
- A newsletter is mutable while drafting and immutable after queueing.
- Sent history is a snapshot of what was actually sent.
- Database migrations are explicit, versioned and immutable after release.
- Tracking/marketing surveillance is outside the core product concept.

## Implemented in 0.1.1

### Administration

- Dashboard as the default Punga Mail backend view.
- Dashboard displays current installed version, subscriber/newsletter/queue counts and Scheduled Task state.
- Joomla-standard Newsletters list using `ListModel`, Search Tools, sortable columns, pagination and checkboxes.
- Newsletters can be moved to Trash, restored, and permanently deleted from Trash.
- Joomla record state is independent of delivery status.
- Joomla-standard Subscribers list with search/filter/sort/pagination and bulk actions.
- Suppression status and reason are explained directly on the Subscribers page.
- Component options for sender identity, subscription policy, double-opt-in mail and queue processing.
- English and German administrator translations.

### Subscribers

- External email-only subscribers.
- Frontend signup module.
- Double-opt-in confirmation.
- Confirmation tokens stored only as hashes and given an expiry.
- Signup/resend rate limiting.
- Joomla-user profile preference through the user plugin.
- Logged-in signup module shows current subscription state and allows opt-in/out.
- Canonical normalized email identity to prevent duplicate recipients.
- Reconciliation between external subscriber identities and Joomla users.
- Persistent suppressions so an opted-out address cannot be reintroduced merely by user-group targeting.
- Subscription/audit event history.
- German and English public/module/profile UI.

### Configurable confirmation email

The double-opt-in confirmation message can be authored as Markdown in Component Options. The editor documents its placeholders:

- `{confirmation_url}` — unique confirmation URL.
- `{site_name}` — Joomla site name.
- `{email}` — requested email address.

The same safe Markdown renderer is used for HTML and plain-text alternatives, including Markdown images.

### Newsletter authoring

- Internal newsletter title and outgoing email subject.
- Markdown body.
- Supported Markdown includes headings, paragraphs, bold, italic, links, lists and images.
- Raw HTML is escaped.
- Remote images may use absolute HTTP(S), root-relative or site-relative URLs and remain hosted on the web server.
- HTML mail images receive conservative responsive inline styling.
- Plain-text rendering preserves useful image alt text and URL.
- `{site_name}` placeholder.
- `{new_content}` placeholder.

`{new_content}` is an explicit insertion point. It must be placed on its own line. Selected articles are rendered only at that point; Punga Mail does **not** append them automatically and does **not** generate a “What's new” heading. The author controls headings and surrounding copy in Markdown.

### Joomla content curation

- Candidate list from published Joomla `com_content` articles.
- Editable **Content published since** date.
- New drafts default that date from the previous successfully completed newsletter cutoff.
- Selected candidate articles can be included/excluded and ordered.
- Per-newsletter title and excerpt overrides leave the source Joomla article unchanged.
- Article title/excerpt/URL are snapshotted when the newsletter is queued.

### Audience

- All confirmed newsletter subscribers can be selected as an audience source.
- Joomla user groups can be selected as additional recipient sources.
- Recipient addresses are normalized and deduplicated.
- Explicit subscription/suppression policy is applied before final eligibility.
- Exact preflight recipient list is shown before queueing.

### Preview and mail rendering

- Rendered newsletter preview before send.
- HTML and plain-text previews.
- Preview, test send and final queue snapshot all use the same `NewsletterRenderer`.
- Test mail can be sent to the current administrator.
- Multipart HTML/plain-text output.
- Visible unsubscribe footer.
- RFC 8058 one-click unsubscribe metadata where HTTPS permits it.

### Sending and queue

- Joomla `MailerFactoryInterface`; Punga Mail therefore uses Joomla's configured mail transport, including SMTP when configured globally.
- Persistent send queue.
- Database uniqueness prevents the same normalized address being queued twice for one newsletter.
- Atomic worker claims prevent normal concurrent-worker duplication.
- Retry count/delay and terminal failures.
- Stale worker recovery.
- Joomla Scheduled Tasks plugin: **Punga Mail — Process send queue**.
- Manual administrator queue-processing action for diagnostics/testing.
- Immutable newsletter/recipient snapshots after queueing.

### Database/release engineering

- Seven normalized Punga Mail tables.
- Explicit indexes and constraints.
- UTC timestamps.
- Fresh-install schema reflects 0.1.1.
- Immutable `0.1.0.sql` baseline plus `0.1.1.sql` transition.
- No runtime schema mutation.
- Data-preserving uninstall in 0.1.1 plus explicit manual purge SQL.
- Release validator checks required files, XML, manifest versions, PHP syntax, migration integrity, runtime schema mutation and unsafe Joomla `bind()` values.
- Reproducible Python build.
- MIT `LICENSE.md`, README, changelog and documentation.
- Two official artifacts per release: Joomla installer and complete Git-ready source tree.

## Core workflows

### Public subscription

```text
Signup module
    ↓
email submitted
    ↓
pending subscriber + hashed token
    ↓
custom/localized Markdown confirmation email
    ↓
confirmation page
    ↓
explicit POST
    ↓
subscribed
```

### Newsletter creation

```text
New draft
    ↓
choose/edit “Content published since” date
    ↓
select/reorder Joomla articles
    ↓
write Markdown body containing {new_content}
    ↓
choose subscribers / Joomla groups
    ↓
preview and/or test send
    ↓
preflight exact deduplicated recipients
    ↓
queue + freeze message and recipients
    ↓
Joomla Scheduled Task worker
    ↓
sent history
```

### Unsubscribe

Human-facing unsubscribe links open a page and require an explicit POST before state changes. This avoids accidental unsubscription by link scanners. RFC 8058 uses a separate signed server-to-server POST endpoint as required by that standard.

## Planned for the update after 0.1.1

These items are deliberately **not** folded into 0.1.1.

### Newsletter templates

- Dedicated Templates backend page.
- Same Markdown/editor capabilities as a newsletter.
- Standard Joomla list table with search, sorting, pagination, Trash/Restore and permanent deletion.
- Newsletter editor can choose an existing template to speed up creation.
- Applying a template copies its content/settings into the newsletter; existing newsletters are not live-linked to later template edits.

### Mail styling

- Component Options define a conservative default email style: dimensions, typography, backgrounds, links, header/logo, image behavior and footer presentation.
- Templates and individual newsletters can override/customize styling rather than being locked to the global default.
- Generated mail remains biased toward email-client compatibility, with reliable inline styling as the baseline and carefully scoped custom CSS where useful.

### Newsletter reminder

- Optional Scheduled Task reminder when no newsletter has been sent for a configurable number of days.
- Reminder should not repeat every day after the threshold; it resets after another newsletter is sent.

### Optional destructive uninstall

- Component option **Uninstall: Remove database tables**.
- Default: **No**.
- When explicitly enabled, uninstall removes all Punga Mail tables.
- When disabled, current data-preserving behavior remains.

## Later candidates

- Restrict automatic content discovery to selected Joomla categories and optionally child categories.
- Richer search/filtering inside the candidate article picker.
- Drag-and-drop selected-article ordering.
- Subscriber CSV import/export with suppression-safe semantics.
- Subscriber audit-history UI.
- Better queue diagnostics and explicit retries for terminal failures.
- Optional browser/archive rendering from immutable newsletter snapshots.
- Additional content-source providers only if a concrete need appears beyond `com_content`.

## Explicit non-goals for the foreseeable product

- open-tracking pixels
- click tracking
- behavioural profiling
- A/B testing
- advertising/marketing automation funnels
- visual drag-and-drop email-builder complexity
- silently subscribing arbitrary addresses without an appropriate subscription policy

Keeping those concerns out is intentional: Punga Mail should remain a clean, self-hosted Joomla newsletter component rather than a marketing suite.

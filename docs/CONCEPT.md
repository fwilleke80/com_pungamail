# Punga Mail — Concept

Punga Mail is a focused newsletter extension for Joomla! 6. Its purpose is to make a small site's recurring newsletter workflow simple, transparent and self-hosted:

1. maintain a trustworthy recipient list;
2. discover content published since the previous newsletter;
3. curate that content into a concise “What's new” section;
4. write the surrounding message in Markdown;
5. preview the exact message and recipient set;
6. send reliably through Joomla's configured mail transport; and
7. retain a useful historical record of what was sent and to whom.

Punga Mail is deliberately **not** intended to become a general marketing-automation platform. The project favours a small, understandable data model and Joomla-native behaviour over campaign funnels, behavioural tracking or proprietary cloud services.

Version documented here: **0.1.0**

## Product principles

### Joomla-native

Punga Mail uses Joomla users and user groups, `com_content`, Joomla's mailer, Joomla extension permissions, Joomla forms, and Joomla Scheduled Tasks rather than duplicating those systems.

### One canonical recipient identity

External newsletter subscribers and Joomla users share one canonical subscriber model. Recipient assembly works on normalized email addresses, so one address receives at most one copy of a newsletter even when it is reachable through several sources.

### Explicit subscription state

Subscription state and suppression state are separate concepts. An address that has explicitly unsubscribed remains suppressed even if it is encountered again through a Joomla user group. A later explicit opt-in can remove that suppression.

### Safe delivery over clever delivery

Sending is persistent and restartable. Queue rows are stored before delivery begins and are protected by a database uniqueness constraint. A newsletter is frozen before it enters the queue so later content edits cannot change a message halfway through delivery.

### No hidden tracking

Punga Mail 0.1 does not use tracking pixels, open tracking or click rewriting. It reports what it can actually know: whether Joomla's configured mail transport accepted an outgoing message, not whether a human opened or read it.

### Data ownership

Subscriber, newsletter and audit data belong to the site owner. Uninstalling the extension does not silently destroy those records. A separate explicit purge script exists for deliberate permanent deletion.

## Package architecture

Punga Mail is distributed as one Joomla package containing four extensions.

### `com_pungamail`

The component owns the data model and the newsletter workflow. It provides:

- administrator newsletter management;
- subscriber administration;
- Markdown rendering;
- `com_content` discovery and selection;
- recipient resolution and deduplication;
- preflight and immutable send snapshots;
- persistent queue creation and processing;
- confirmation and unsubscribe endpoints; and
- subscription/audit persistence.

### `mod_pungamail_signup`

The site module provides the public signup surface.

Anonymous visitors can enter an email address and receive a double-opt-in confirmation message. Logged-in Joomla users see and can change their current newsletter subscription state instead.

### `plg_user_pungamail`

The user plugin adds the Punga Mail subscription preference to applicable Joomla registration/profile/user forms and writes changes into the canonical subscriber state.

### `plg_task_pungamail`

The task plugin exposes **Punga Mail — Process send queue** to Joomla Scheduled Tasks so delivery does not depend on an administrator keeping a browser request open.

## Implemented in 0.1.0

### Subscribers and subscription lifecycle

- External email-only subscribers; no Joomla account required.
- Public signup module.
- Double-opt-in confirmation workflow.
- Random confirmation tokens stored only as SHA-256 hashes.
- Configurable confirmation-token lifetime.
- Generic signup response that does not reveal whether an address is already subscribed.
- Honeypot protection on the public form.
- Per-IP signup throttling.
- Per-address confirmation resend throttling.
- Pending, subscribed and unsubscribed subscriber states.
- Permanent suppression records for explicit unsubscribe.
- Re-subscription through a new explicit confirmation removes an unsubscribe suppression.
- Joomla-user subscription preference through profile/user forms.
- Configurable default subscription policy for Joomla users that do not yet have an explicit Punga Mail preference.
- Reconciliation when a Joomla user changes their email address.
- Deterministic reconciliation when a Joomla user's new address already exists as an external subscriber.
- Deduplication by normalized email address.
- Subscriber audit events for important lifecycle transitions.

### Unsubscribe handling

- Every production newsletter contains a personalized visible unsubscribe URL.
- Visiting the visible URL does **not** change state.
- The visible unsubscribe page requires an explicit POST confirmation.
- Signed unsubscribe tokens are derived from the stable subscriber ID and Joomla site secret.
- RFC 8058 one-click unsubscribe POST endpoint.
- `List-Unsubscribe` header on production newsletter messages.
- `List-Unsubscribe-Post: List-Unsubscribe=One-Click` is advertised only when the generated endpoint uses HTTPS.
- Unsubscribe suppression is checked after recipient discovery, so another recipient source cannot override it accidentally.

### Newsletter authoring

- Draft newsletters with an internal title and mail subject.
- Main newsletter introduction/body written in Markdown.
- Safe built-in Markdown renderer for headings, paragraphs, emphasis, links and lists.
- Raw HTML in Markdown is escaped rather than trusted.
- Published Joomla `com_content` articles discovered from the newsletter content cutoff.
- Publication time is preferred over creation time when deciding whether an article is new.
- Article selection for the “What's new” section.
- Explicit article ordering.
- Newsletter-specific article title override.
- Newsletter-specific article excerpt override.
- Default excerpt derived from the Joomla article intro text.
- Article URL generated as an absolute Joomla site URL.
- Selected articles remain attached to a draft even if they fall outside the current discovery window later.

### Recipient selection

- Confirmed newsletter subscribers can be included as a recipient source.
- One or more Joomla user groups can be selected as additional recipient sources.
- Descendant Joomla groups are respected when resolving group membership.
- Blocked Joomla users are excluded.
- Explicit user opt-out and suppression remain authoritative.
- Recipient sets are deduplicated by normalized email address.
- Preflight displays every resolved recipient before the newsletter can be queued.
- Preflight displays a recipient count and source breakdown.

### Rendering and preflight

- HTML rendering from the Markdown body and selected article snapshots.
- Plain-text rendering from the same source material.
- `multipart/alternative` outgoing messages with HTML and plain-text bodies.
- HTML preview before sending.
- Plain-text preview before sending.
- Test send to the current administrator's Joomla email address.
- Exact resolved-recipient preview before queueing.
- Sender identity shown during preflight.

### Immutable send snapshots

Queueing a newsletter freezes:

- the subject;
- rendered HTML;
- rendered plain text;
- the final content cutoff;
- selected article titles;
- selected article excerpts;
- selected article URLs; and
- the exact recipient addresses and their source.

A queued or sent newsletter cannot be edited. It can be duplicated into a new draft instead.

This means historical newsletter views describe the message that was actually sent rather than reconstructing it from Joomla articles that may have changed later.

### Persistent send queue

- Database-backed queue.
- Unique `(newsletter_id, email_normalized)` delivery key as a database-level idempotency barrier.
- Queue rows progress through `pending`, `processing`, `sent` and `failed` states.
- Atomic claim operation prevents two active workers from intentionally sending the same pending row.
- Configurable batch size.
- Configurable maximum attempts.
- Configurable retry delay.
- Failed transient sends are returned to pending with a future retry time.
- Terminal failures retain their error message.
- Stale `processing` claims are recovered after an interrupted worker.
- Manual administrator queue-processing action.
- Joomla Scheduled Tasks integration for unattended processing.
- Newsletter-level recipient, sent and failed counters.
- Frozen recipient history with individual state, attempt count, send time and error.

There is an unavoidable distributed-systems boundary between SMTP handoff and the database update that marks a row sent. A process terminating in that very small interval can cause one duplicate after stale-claim recovery. Punga Mail deliberately prefers that rare duplicate to silently losing a message.

### Database and release engineering

- Seven normalized Punga Mail tables.
- Explicit primary, unique and lookup indexes.
- UTC SQL timestamps.
- No runtime schema mutation in PHP.
- Fresh-install SQL schema.
- Explicit `0.1.0` migration baseline.
- Joomla schema updater declared in the component manifest.
- Migration policy documented in `docs/DATABASE.md`.
- Data-preserving uninstall policy.
- Separate manual destructive purge SQL file.
- Static release checker for required files, XML validity, manifest versions, PHP syntax, migration-baseline parity, package members and unsafe database bindings.
- Reproducible Python build script.
- Ready-to-install Joomla package ZIP.
- Complete Git-oriented source ZIP.
- MIT `LICENSE.md`.
- `README.md`, `CHANGELOG.md`, this concept document and database documentation.

## User workflows implemented in 0.1.0

### External visitor subscribes

```text
Signup module
    ↓
email submitted
    ↓
pending subscriber + hashed token
    ↓
confirmation email
    ↓
confirmation page
    ↓
explicit confirm POST
    ↓
subscribed
```

### Joomla user manages their preference

```text
Joomla profile / signup module
    ↓
Punga Mail preference
    ↓
canonical subscriber state
    ↓
recipient resolver respects preference
```

### Editor sends a newsletter

```text
New draft
    ↓
write Markdown introduction
    ↓
select/reorder newly published articles
    ↓
choose subscribers / Joomla groups
    ↓
preview or send test
    ↓
preflight exact recipients
    ↓
queue + freeze snapshots
    ↓
Scheduled Task / manual worker
    ↓
sent history
```

### Recipient unsubscribes

```text
newsletter
    ↓
personalized unsubscribe URL
    ↓
confirmation page
    ↓
explicit POST
    ↓
unsubscribed subscriber + suppression
```

Mailbox-provider RFC 8058 requests use the separate signed one-click POST endpoint and do not require the human-facing confirmation page.

## Deliberately limited in 0.1.0

The first build keeps several things intentionally simple:

- `com_content` is the only automatic content source.
- New-content discovery is site-wide; category scoping is not yet configurable.
- The article picker is intentionally simple and does not yet provide search/filter/pagination for very large sites.
- Newsletter styling uses one built-in responsive HTML presentation rather than a visual template builder.
- The administration/site copy is primarily English; the extension structure is ready for broader localization but not every UI sentence has been moved to language constants yet.
- There is one newsletter audience rather than independent topic/list subscriptions.
- External subscriber import/export is not implemented.
- Bounce mailbox processing is not implemented.
- Newsletter sends are queued immediately after approval; future-dated campaign scheduling is not implemented.
- There is no open tracking, tracking pixel, click tracking, A/B testing or behavioural segmentation.

These are scope decisions rather than architectural dead ends.

## Planned next-stage features

The following features are good candidates after the 0.1 baseline has been installed and exercised on a real Joomla site. They are **planned directions**, not promises for a particular version number.

### Administration polish

- Move all remaining visible strings into Joomla language files.
- Search, filtering and pagination for newsletters and subscribers.
- Subscriber event/history view.
- Better queue diagnostics and explicit retry action for terminal failures.
- Dashboard/overview counts for subscriber and queue health.

### Content curation

- Component options for allowed `com_content` categories.
- Optional inclusion of child categories.
- Article-picker search and category/date filtering.
- Drag-and-drop article ordering instead of numeric ordering fields.
- Clear display of article access level so editors can see when a linked article is not public.
- Extensible content-source provider interface if a later project genuinely needs items from components other than `com_content`.

### Newsletter presentation

- Configurable logo/header/footer.
- Conservative colour and width options suitable for email clients.
- Configurable standard footer text.
- Better preview sizes for desktop/mobile-like layouts.
- Optional newsletter browser/archive view based on the immutable snapshot.

### Subscriber administration

- CSV export.
- Carefully designed CSV import that never bypasses existing suppressions.
- Administrator-created subscriber flow with explicit confirmation by default.
- Optional subscriber language preference and localized confirmation/system mail.
- Expiry/cleanup policy for old unconfirmed pending subscriptions and audit events.

### Delivery operations

- Explicit retry of terminal queue failures.
- Configurable stale-claim timeout.
- Optional future send date using Joomla Scheduled Tasks while preserving the existing frozen-recipient semantics.
- Optional hard-bounce integration if a transport-independent, maintainable approach is justified.

## Features intentionally outside the core concept

The following are not current product goals:

- tracking pixels / open-rate analytics;
- per-recipient click tracking;
- advertising attribution;
- behavioural profiling;
- marketing funnels;
- CRM features;
- lead scoring;
- A/B campaign optimization;
- third-party cloud dependency as a requirement; or
- a Mailchimp-style drag-and-drop page builder.

If a future requirement clearly justifies one of these, it should be evaluated as a deliberate scope change rather than allowed to accrete into the component accidentally.

## Data model summary

The authoritative schema is documented in [`DATABASE.md`](DATABASE.md). Conceptually:

```text
subscribers ───────┐
                   ├── recipient resolver ── send_queue
suppressions ──────┘                         │
                                             │
newsletters ── newsletter_items ─────────────┤
      │                                      │
      └──── newsletter_groups ───────────────┘

subscribers ── events
```

The central invariant is that `send_queue` is a frozen delivery record. Subscriber/profile changes after queueing do not rewrite history or silently change the recipient snapshot.

## Release philosophy

Punga Mail releases should remain reproducible and inspectable.

Every released version must provide:

1. a ready-to-install Joomla package ZIP; and
2. a complete source ZIP containing the canonical project tree suitable for committing to Git.

The source tree includes `README.md`, `CHANGELOG.md`, `LICENSE.md`, `docs/CONCEPT.md`, `docs/DATABASE.md`, build/check tools, manifests, migrations and complete source for every constituent extension.

Schema migrations are append-only after release: once a migration has shipped, it is never rewritten. A fresh install schema is updated to represent the latest state, while upgrades reach that state through the ordered migration chain.

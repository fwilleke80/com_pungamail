# Punga Mail concept

## Purpose

Punga Mail is a focused, self-hosted Joomla! 6 newsletter extension. Its primary workflow is deliberately short:

**subscribe → confirm → compose from a template → discover new Joomla content → preview/test → preflight → queue → scheduled send → unsubscribe/history**.

It is not intended to become a behavioural marketing/analytics platform.

## Implemented through 0.6.18
- Joomla User subscriber Channel eligibility updates immediately before save by resolving the selected Joomla account ID from Joomla's real hidden User field value.

### Lists, automation and access safety

- Subscribers can independently join or leave published mailing topics without changing their global subscription state.
- Newsletters can target topics and Joomla user groups; recipient resolution deduplicates addresses and applies unsubscribe and suppression barriers.
- Recurring digests discover content through Joomla registered content types and can create a safe draft or explicitly enabled automatic send.
- Before a digest is created, each item is checked against the resolved recipients' Joomla authorized view levels and applicable category access. An item is included only in the common visible set, so email never broadens website access. Registered types without usable access metadata are excluded from automatic digests rather than assumed public.
- Digest runs retain newsletter IDs, outcomes, no-content skips and failure details.

### Delivery health

- Scheduled sending, queue pause/resume and unsent-remainder cancellation build on the existing task and retry infrastructure.
- Outgoing mail can use Joomla's global mail configuration or a Punga Mail-specific SMTP account created through Joomla's mailer factory. Existing installations default to Joomla transport; custom SMTP credentials are stored encrypted.
- Delivery-status notifications are processed from a dedicated mailbox using PHP IMAP. Permanent/hard bounces suppress immediately; temporary/soft bounces suppress only at the configured threshold; history is retained.
- Preflight distinguishes blocking errors and warnings and explains included/excluded recipients without mutating subscriber state.
- Sent browser views and statistics read immutable mailing snapshots.
- CSV import never silently reactivates globally unsubscribed, suppressed or hard-bounced addresses.

### Subscribers

- The administrator can add a standalone email recipient directly or select an existing Joomla user to explicitly enable their newsletter preference.
- Joomla users and external email-only subscribers share one canonical subscriber model.
- Frontend signup module with double opt-in, throttling/honeypot and generic anti-enumeration response.
- Editable Markdown confirmation mail with `{confirmation_url}`, `{site_name}` and `{email}` placeholders.
- User-profile global subscription preference and published-topic selector, backed by the same normalized memberships as the frontend signup/subscription experience.
- Signed visible unsubscribe flow requiring an explicit POST.
- RFC 8058 one-click unsubscribe endpoint/header support on HTTPS.
- Persistent suppression barrier so user-group discovery cannot undo an opt-out.
- English and German UI/mail text.

### Public subscription page and SEF routing

Punga Mail supplies a **Newsletter Subscription** site menu-item type and a component `RouterView` router. A published menu item may be hidden from navigation while still anchoring clean Joomla SEF routes for signup, confirmation, unsubscribe and status pages. Outgoing mail URLs preferentially use that published Punga Mail menu item when available.

### Newsletter editor

- Markdown body, HTML/plain-text output, remote/site-relative Markdown images and GitHub-style pipe tables.
- `{new_content}` is an explicit insertion point and adds no automatic heading. Selected items are rendered through central Markdown layouts: one Default layout plus optional per-registered-content-type layouts. Punga Mail always provides normalized title/link/date/excerpt/read-more/URL/content-type placeholders and additionally discovers safe columns directly from each registered source table, so third-party extensions do not need Punga Mail-specific providers or placeholder APIs. Rendering remains segment-based so Markdown parsing cannot mutate or expose an internal placeholder token.
- `{recipient}` recipient-personalization placeholder: Joomla display name for registered users, email fallback for external subscribers; backend previews use the currently logged-in administrator.
- Editable “Content published since” date.
- Joomla registered content types are discovered generically from `#__content_types` metadata; Punga Mail reads their real registered table and does not require other extensions to implement a Punga-specific integration.
- Administrator selects which usable registered content types participate.
- Combined new-content candidate list with type badges, text search, selection, ordering and title/excerpt overrides.
- Candidate list is bounded/scrollable to keep the editor compact.
- Preview and test mail use the same renderer as the final frozen send snapshot.

### Joomla administrator navigation

Newsletter/template editor, preview and preflight URLs preserve the corresponding list-view context (`newsletters` or `templates`) while Punga Mail internally maps a secondary `screen` parameter to the actual MVC view. This lets Joomla’s own administrator-menu matching keep the Punga Mail branch expanded and the owning submenu selected.

### Templates

- First-class reusable newsletter templates with default subject, Markdown body and style overrides.
- Standard Joomla administrator list/search/sort/pagination/Trash/Restore/Delete behavior.
- Template preview.
- Applying a template copies it into a newsletter draft; subsequent template edits do not alter existing newsletters.

### Administrator conventions

- Newsletter and Template trash views retain Joomla/Atum native table colouring, including dark mode.
- Editor toolbars keep save/cancel actions left and newsletter preview/test/send actions right.
- Component Options use Joomla's native Toggle Inline Help mechanism.
- Configuration-page titles and component-specific toolbar labels are translated through Punga Mail language keys.
- Newsletter, template, topic and digest editors participate in Joomla checkout/check-in and Global Check-in recovery.

### Mail language and footer copy

Subscriber-facing reusable mail strings belong to the Joomla **Website** language catalog, not the Administrator catalog. Punga Mail explicitly resolves the configured frontend language and Website override file when rendering newsletters, including rendering triggered from Administrator previews and Scheduled Tasks. This makes Joomla Website language overrides authoritative for the footer reason, unsubscribe label and “Read more” label.

The footer reason is additionally editable under **Component Options → Mail Design → Footer reason (Markdown)**. When no custom value is stored, the localized Website string `COM_PUNGAMAIL_MAIL_FOOTER_REASON` is used. The footer Markdown is rendered into both HTML and plain-text message alternatives.

### Mail design

Layered style inheritance:

1. Component Options define the default mail design.
2. A template may override individual style values.
3. A newsletter may override them again.

Blank override values inherit. The base renderer uses conservative inline styles for email-client compatibility. Templates/newsletters may add optional custom CSS; custom CSS is kept inside the message style context and cannot inject markup into backend previews.

### Sending

- Joomla `MailerFactoryInterface`; Punga Mail either uses Joomla's global mailer settings or supplies an isolated Custom SMTP `Registry` to Joomla's mailer factory. Punga Mail does not maintain a parallel PHPMailer stack.
- Persistent send queue, database recipient uniqueness, atomic claims, retry/terminal-failure handling and stale-worker recovery. Completed queue history can be archived non-destructively; retrying an archived failed row restores it to the current queue before it becomes pending.
- Joomla Scheduled Task **Punga Mail — Process send queue** for normal unattended delivery.
- Separate Joomla task types queue due scheduled newsletters, generate due automatic digests, and process the configured bounce mailbox. Automatic-send digests still pass through the normal send queue.
- Dashboard exposes all task states, warns contextually when a configured feature lacks its required enabled task, links directly to Joomla Scheduled Tasks, and provides **Process queue now** only as a manual diagnostic/maintenance action. The Newsletters list intentionally has no manual queue button.
- Sent newsletters and recipient rows are immutable historical snapshots.

### Newsletter-age reminder

Optional Joomla Scheduled Task **Punga Mail — Newsletter reminder**. When enabled, it sends one reminder after the latest newsletter exceeds the configured age threshold. The cycle resets when another newsletter is sent. Reminder subject/body are configurable; Markdown supports `{days}`, `{last_newsletter}`, `{last_sent_date}` and `{site_name}`.

### Administration and lifecycle

- Dashboard with installed version, subscriber/newsletter/queue summaries and scheduler status.
- Joomla-standard Newsletters, Templates and Subscribers backend tables.
- English and German translations.
- Forward-only SQL migrations from 0.1.0 onward.
- **Uninstall: Remove database tables** option, disabled by default.
- MIT license and complete Git-ready source release alongside every Joomla installer.

## Content-type compatibility philosophy

Punga Mail prefers Joomla-native contracts. Registered content types are the normal discovery mechanism. This keeps extensions independent: a component such as Punga Audio Archive does not need to know Punga Mail exists merely to expose a properly registered content type.

A future optional provider hook is acceptable only as an escape hatch for unusual components whose registered metadata is insufficient; it must not become the default integration path.

## Explicit non-goals

- open-tracking pixels
- click tracking
- behavioural profiling
- A/B testing
- advertising funnels/marketing automation
- mandatory external SaaS services
- a heavyweight drag-and-drop email builder
- silently re-subscribing suppressed addresses

## Possible later additions

### Longer-term candidates

- richer category/subcategory selectors per registered content type where the source exposes categories
- richer subscriber audit-history UI
- explicit retry controls/diagnostics for terminal queue failures
- a full public archive built on immutable sent-newsletter browser views
- additional mail-design conveniences discovered through real-world use


### Joomla-native editor toolbars

Newsletter and template editors use Joomla's standard top administrator toolbar. **Save** uses Joomla's canonical Apply toolbar behavior and stays in the editor; **Save & Close** uses Joomla's Save toolbar behavior and returns to the owning list. Draft newsletters additionally expose Preview, Send test mail, Check recipients & send, and Cancel. Templates additionally expose Preview and Cancel. Context-specific form actions such as applying a content date or template remain next to the fields they affect.


### Administrator attention acknowledgment

Returned-mail suppression warnings on the Dashboard are operational attention items, not subscriber state. Administrators can acknowledge the exact latest check after review; this only hides that Dashboard warning. Suppressions and bounce history remain unchanged, and a later check with new exclusions becomes visible again. Grouped Audience/Design routes remain the canonical administrator context for their child screens and actions.

## Administrator ACL

Punga Mail 0.6.11 delegates backend authorization to Joomla ACL instead of maintaining a separate user/group whitelist. `access.xml` defines standard newsletter CRUD actions plus Punga Mail-specific capabilities for sending, Automatic Newsletters, Audience, Design, Delivery, and Tools. Ordinary component configuration uses Joomla `core.options`; changing ACL remains protected by `core.admin`.

Authorization is enforced twice by design: views/controllers reject unauthorized access server-side, while toolbar actions, Dashboard controls, and Punga Mail section navigation are filtered for usability. UI visibility is never treated as the security boundary. Newsletter editing and sending are separate capabilities so an editorial role can prepare drafts without being permitted to schedule or transmit them.


### Channel lifecycle

Channel trash is reversible and preserves relationships. Permanent deletion is explicit cleanup: subscriber memberships, pending preference actions, and Newsletter/Automatic Newsletter Channel-targeting relations are removed transactionally, while the subscriber/campaign records and immutable sent-message/delivery snapshots remain intact.

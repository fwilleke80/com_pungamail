# Punga Mail Administrator Guide

This guide explains Punga Mail from the point of view of a normal Joomla administrator. It covers the everyday screens, controls, settings, and decisions involved in collecting subscriptions, composing newsletters, scheduling or automating delivery, and keeping the mailing list healthy.

The guide describes Punga Mail 0.6.3. Names may appear in English or German depending on the administrator language selected in Joomla.

## What Punga Mail does

Punga Mail is a self-hosted newsletter extension for Joomla. It lets you:

- collect email subscriptions with confirmation;
- let subscribers choose mailing topics such as News, Events, or Development Updates;
- build newsletters from content already published on the website;
- write additional content in Markdown;
- reuse newsletter templates and design settings;
- preview, test, and validate a newsletter before delivery;
- send immediately or at a scheduled time;
- generate recurring content digests;
- monitor the send queue and delivery results;
- process returned mail and suppress addresses that repeatedly fail;
- import and export subscribers as CSV files.

Punga Mail can either use Joomla's globally configured mail transport or use its own Custom SMTP account. Existing installations continue to use Joomla's mail settings unless Custom SMTP is explicitly selected under Punga Mail Component Options.

## The main concepts

### Newsletter delivery and Channel choices are different

Punga Mail keeps two related choices separate:

| Choice | Meaning |
| --- | --- |
| Newsletter reception | The master permission: whether the address may receive any Punga Mail newsletter at all. |
| Channel choices | Which named Channels the subscriber has chosen. |

A subscriber can leave one topic and remain interested in other topics. **Stop all newsletters** is different: it turns newsletter reception off globally and prevents that address from being reintroduced through Joomla user-group targeting.

The Joomla user-profile field **Receive newsletters** controls the master permission. The **Channels** selector beside it manages the same topic memberships as the frontend Punga Mail signup module.

### Recipient sources are combined

A newsletter or digest can use three recipient sources:

- all globally subscribed recipients, regardless of topic;
- members of selected Punga Mail topics;
- members of selected Joomla user groups.

These sources are combined as an **either/or audience**, then duplicate email addresses are removed. Selecting two topics means members of either topic, not only people in both. Joomla groups remain separate from Punga Mail topics.

If **All globally subscribed recipients, regardless of topic** is selected, topic selections do not narrow that source. Clear that option when the newsletter should go only to selected topics. Joomla group selections add their eligible members to the audience. The editor summarizes this rule live, and Preflight warns when the resulting mailing includes eligible recipients outside the selected topics.

Every source is still subject to address validity, global opt-out, and suppression rules.

### Delivery terminology

| Term | Meaning |
| --- | --- |
| Queued | The immutable mailing and recipient rows have been created and await processing. |
| Transport accepted | The active outgoing mail transport accepted the message for onward delivery. It does not prove that a human received or read it. |
| Temporary failure | An attempt failed but may be retried according to the queue settings. |
| Permanent failure | The queue exhausted its retries or encountered a non-recoverable send failure. |
| Hard bounce | A later delivery report indicates a permanent address failure, such as an unknown mailbox. |
| Soft bounce | A later delivery report indicates a temporary condition, such as a full mailbox or transient server problem. |
| Suppressed | Punga Mail must not send to that normalized email address. |

## First-time setup checklist

After installing or updating Punga Mail:

1. Open **Components → Punga Mail → Dashboard** and confirm the installed version.
2. Open **Options** and review the sender, design, subscription, queue, and bounce settings.
3. Under **Options → Mail**, choose **Use Joomla settings** or configure **Custom SMTP**, then send a controlled test message.
4. Create and enable **Punga Mail — Send pending newsletters** in Joomla Scheduled Tasks.
5. Create a published **Punga Mail → Newsletter subscription** menu item. It may be hidden from the visible menu.
6. Publish the **Punga Mail Signup** site module where visitors can find it.
7. Create any Channels you want visitors to choose.
8. Send a second mail test from **Delivery / Bounces** to verify the saved active transport.
9. If bounce processing is required, configure and test the return mailbox, then create its Scheduled Task.

Create the digest, scheduled-send, and reminder tasks only if you use those features. The Dashboard warns when an enabled feature is missing its required task.

## Administrator navigation

Punga Mail keeps the primary Joomla sidebar compact by grouping related work:

| Sidebar item | Contains |
| --- | --- |
| Dashboard | Overall status, upcoming mail, recent activity and quick actions. |
| Newsletters | Ordinary drafts, scheduled/sent mail, archived newsletters and delivery history. |
| Automatic Newsletters | Recurring newsletter rules and their run history. |
| Audience | **Subscribers** and **Channels**, shown as tabs inside one section. |
| Design | **Templates** and **Content layouts**, shown as tabs inside one section. |
| Delivery | Queue inspection, bounce handling and delivery diagnostics. |
| Tools | Subscriber **Import / Export** and future infrequent maintenance tools. |

When you move between Subscribers and Channels, or between Templates and Content layouts, the parent sidebar entry remains selected. Existing old administrator bookmarks continue to work, but normal navigation uses these grouped sections.

## Dashboard

Open **Components → Punga Mail** to reach the Dashboard. The 0.4 Dashboard is designed as a control centre rather than a database-status page.

The top cards show active recipients and Channels, the last sent newsletter, the next enabled Automatic Newsletter, and recent delivery health. **Needs your attention** stays quiet when everything is healthy and surfaces only actionable problems such as a missing Scheduled Task, an incomplete database update, or failed deliveries.

**Quick actions** provide direct paths to a new Newsletter, new Automatic Newsletter, recipient creation, Channels, and Templates. When pending mail exists or the normal queue task is unavailable, a manual **Process queue now** action remains available as a recovery/testing tool.

The lower panels summarize recent newsletter/automation activity, upcoming scheduled mail, the last 30 days of recipient processing, the most-used Channels, and enabled Automatic Newsletters. Scheduled-task and queue implementation details are deliberately not shown unless they require action.

## Component Options

Open **Options** from the toolbar on Punga Mail's main backend sections. Joomla's **Toggle Inline Help** button displays the descriptions beside the fields.

### Mail

| Setting | What it controls |
| --- | --- |
| From name | The sender name displayed by mail clients. Leave blank to use Joomla's global sender name. The resolved sender is used with either outgoing transport mode. |
| From email | The sender email address. Leave blank to use Joomla's global sender address. The address must be valid and should be permitted by the SMTP account/provider you use. |
| Outgoing transport | **Use Joomla settings** keeps the existing behavior and uses Joomla's globally configured mailer. **Custom SMTP** lets Punga Mail authenticate with a separate SMTP account without changing Joomla system mail. |
| SMTP server / port | Host name and TCP port supplied by the newsletter SMTP provider. Common submission ports are 587 with STARTTLS or 465 with SSL/TLS; always use the provider's actual values. |
| SMTP security | STARTTLS, SSL/TLS, or none. Match the provider configuration. |
| SMTP authentication | Enable when the server requires a username/password. |
| SMTP username | Login name for the Custom SMTP account, commonly the full mailbox address. |
| SMTP password | Stored encrypted by Punga Mail and never displayed again. Leave blank later to keep the saved password. |
| Reply-To mode | **None** omits a Reply-To address. **Custom** uses the Reply-To fields below. Templates and newsletters may inherit, replace, or disable this choice. |
| Reply-To email | The address that receives ordinary reader replies when custom Reply-To is enabled. It is validated before sending. |
| Reply-To name | The optional display name for the Reply-To address. |
| New Joomla users subscribed by default | Controls Joomla users who have no explicit Punga Mail preference. **No** is the consent-safe default. When **Yes**, eligible users selected through Joomla groups may receive mail unless they have opted out or the address is suppressed. |

**Use Joomla settings** is the backward-compatible default. Select **Custom SMTP** only when Punga Mail should use a separate outgoing account, for example `newsletter@example.com`, while Joomla system messages continue through another account. Punga Mail still creates the mailer through Joomla's mail API; the custom mode supplies a Punga Mail-specific SMTP configuration to that mailer.

The SMTP password is intentionally not stored in Joomla's ordinary component-parameter JSON. Use **Save outgoing mail settings** inside the outgoing-mail section after entering or changing the Custom SMTP connection. The normal Joomla Options **Save** button stores ordinary component fields such as From name, From email, and Reply-To. **Send test mail** in the outgoing-mail section tests the values currently shown there; a blank password reuses the saved encrypted password.

### Mail design

These values form the global design. A template can override individual values, and a newsletter can override them again:

**Global design → template override → newsletter override**

A blank template or newsletter design field means “inherit.”

| Setting | What it controls |
| --- | --- |
| Mail body heading | The visible first heading inside the email. `{site_name}` deliberately uses the Joomla site name. Any other text is used literally. Leave it empty to render no heading. This is not the email subject. |
| Browser view enabled | Adds a standard browser-view link and creates a public view from the immutable sent snapshot. Drafts never become public. |
| Content width | Maximum content width in pixels. Default: 680; allowed range: 320–1200. |
| Outer background | Background surrounding the main content area. |
| Content background | Background of the main message panel. |
| Text color | Default body-text color. |
| Heading color | Default heading color. |
| Mail heading background | Optional background for the first mail heading. Leave empty for no background; when set, the heading spans the full mail content width. |
| Mail heading text colour | Optional text colour for that full-width mail heading. Leave empty to reuse the content-heading colour. |
| Link color | Default link color. |
| Font family | Email-safe CSS font list, for example `Arial, Helvetica, sans-serif`. |
| Font size | Base text size in pixels. Default: 16; allowed range: 10–28. |
| Content padding | Space inside the message panel in pixels. Default: 32; allowed range: 0–96. |
| Logo URL | Optional absolute URL, root-relative path, or site-relative path to a publicly available logo. |
| Logo width | Display width of the logo in pixels. Default: 180; allowed range: 40–600. |
| Footer color | Color of the standard footer and subscription explanation. |
| Footer reason | Markdown text explaining why the recipient received the message. Leave blank to use the translated frontend default. Joomla Website language overrides are honored. |
| Custom CSS | Optional advanced additions. Email-client CSS support varies, so important presentation should still work with Punga Mail's built-in inline styling. |

Template and newsletter message options can also override the heading, browser view, and Reply-To settings. See [Templates](#templates) and [Newsletter editor](#newsletter-editor).

### Subscriptions and confirmation

| Setting | What it controls |
| --- | --- |
| Confirmation validity in hours | How long an email confirmation link remains usable. Default: 48 hours; allowed range: 1–168. |
| Signup requests per IP per hour | Rate limit for public signup requests from one IP address. Default: 12; allowed range: 1–500. |
| Minimum resend interval | Minimum time before another confirmation message may be requested. Default: 10 minutes; allowed range: 1–1440. |
| Confirmation subject | Optional custom subject for confirmation messages. Supports `{site_name}` and `{email}`. Leave blank for the translated default. |
| Confirmation message | Optional Markdown message. Use `{confirmation_url}` for the required confirmation link; `{site_name}` and `{email}` are also available. Leave blank for the translated default. |

Email-only visitors and email-only Channel changes require email confirmation. A request does not become active until the recipient uses the confirmation link.

### Send queue

| Setting | What it controls |
| --- | --- |
| Queue paused | Globally stops queue workers from sending. Queued rows remain pending and are not marked failed. Resume when ready. |
| Batch size | Maximum number of recipients processed in one task run. Default: 25; allowed range: 1–250. |
| Maximum attempts | Maximum attempts for a queue recipient before a failed state. Default: 3; allowed range: 1–10. |
| Retry interval | Delay in minutes before another attempt after a recoverable failure. Default: 15; allowed range: 1–1440. |

Do not increase the batch size without considering your mail provider's limits and the frequency of the Scheduled Task.

### Bounce / return mailbox

Punga Mail uses PHP IMAP to read unseen delivery-status notifications from a dedicated mailbox. The returned-mail mailbox is independent of the outgoing transport: it can be used whether Punga Mail sends through Joomla settings or Custom SMTP.

| Setting | What it controls |
| --- | --- |
| Server | IMAP host name supplied by the mailbox provider. |
| Port | IMAP port, commonly 993 for SSL. Use the provider's value. |
| Security | SSL, TLS, or none, as required by the provider. |
| Mailbox folder | Folder to inspect, normally `INBOX`. |
| Username | Mailbox login name, often the full email address. |
| Password | Mailbox password. An existing password is never shown. Leave the field blank to keep the stored password. |
| Bounce address | Return/envelope address Punga Mail asks Joomla's mail layer to use where the active transport supports it. Supplying a value does not guarantee that every transport or upstream provider permits envelope-sender changes. |
| Validate certificate | Verifies the mail server's TLS certificate. Keep enabled for normal secure use. |
| Temporary failures before blocking address | Number of temporary/soft delivery failures after which an address is suppressed. Default: 3; allowed range: 1–20. This threshold applies **only** to temporary failures. A permanent/hard failure blocks delivery immediately after the first confirmed hard bounce. |

Use **Save mailbox settings** inside this section. The ordinary Joomla Options save button does not store the password field. **Test connection** tries a read-only connection and reports the result. Testing and processing require the PHP IMAP extension.

Punga Mail marks processed or unparseable unseen messages as seen. Use a dedicated mailbox so unrelated unread mail is not consumed by this process.

### Newsletter reminder

This optional feature alerts an administrator when no newsletter has been sent for a chosen period. The Component Options tab now begins with the same plain-language explanation shown here, and every reminder setting has Joomla inline-help text.

| Setting | What it controls |
| --- | --- |
| Enable reminder | Enables reminder evaluation. A corresponding Scheduled Task is still required. |
| Days since last newsletter | Age at which a reminder should be sent. Default: 30 days; allowed range: 1–3650. |
| Reminder email | Recipient of the reminder. Leave blank to use Joomla's global sender email. |
| Reminder subject | Subject of the administrative reminder. |
| Reminder message | Markdown body. Available placeholders are `{days}`, `{last_newsletter}`, `{last_sent_date}`, and `{site_name}`. Leave blank for the translated default. |

Run **Punga Mail — Newsletter reminder** daily. Punga Mail records the reminder for the current last-sent cycle so it does not send the same warning every day.

### Automatic Newsletter draft notifications

When an Automatic Newsletter is configured to **Create draft**, Punga Mail can email the person who reviews newsletters as soon as a new draft is ready. Enable the notification and enter an explicit reviewer address under Component Options → Automatic newsletters. The message names the automation and draft, reports selected and access-excluded content counts, and links directly to the draft in Joomla administration. Automatic Newsletters configured to send immediately do not send this review notification.

### Maintenance / Data

| Setting | What it controls |
| --- | --- |
| Uninstall: Remove database tables | **No**, the default, preserves all Punga Mail data when the extension is uninstalled. **Yes** permanently removes subscriber, suppression, topic, newsletter, queue, history, and related data during uninstall. |

Leave this at **No** unless permanent deletion is intentional and a suitable backup exists.

## Channels

Topics are named mailing choices such as News, Events, or Development Updates. They are not Joomla user groups.

### Channels list

The list supports Joomla search tools, status filtering, sorting, pagination, publish, unpublish, trash, restore, and delete.

The columns show the title and description, alias, current member count, publication state, ordering, and record ID.

- **Published** topics can be shown in the frontend module and selected for current newsletter/digest targeting.
- **Unpublished** topics remain stored but are not available for public selection.
- **Trashed** topics can be restored.
- A trashed topic cannot be permanently deleted while subscriber, newsletter, or digest relationships still use it.

### Channel editor

In addition to title, alias, description and drag ordering, each Channel controls **Who can subscribe?**:

| Choice | Eligibility |
| --- | --- |
| Everyone | Joomla users and external email-only subscribers may see and subscribe to the Channel. |
| Registered users | Only subscribers linked to a Joomla account may use the Channel. |
| Selected Joomla user groups | Only linked Joomla accounts belonging to at least one selected group (including Joomla’s inherited group membership) may use the Channel. |

Eligibility is not merely a hidden checkbox. Punga Mail rechecks it when preferences are saved and again when recipients are resolved for delivery. If a user later loses an allowed Joomla group, an old membership cannot keep delivering the restricted Channel.

| Field | What it means |
| --- | --- |
| Title | Public name visitors and administrators see. Required. |
| Alias | Stable internal identifier. Leave blank to generate it from the title. It must be unique. Avoid changing it after using it in CSV workflows. |
| Description | Optional plain-text explanation shown by the signup module when visitors choose among multiple topics. |
| Ordering | Reorder Channels directly in the list by dragging the handle when the table is sorted by Ordering. |

**Save** keeps the editor open. **Save & Close** saves and returns to the list. **Cancel** leaves without saving and releases the editor lock.

## Subscribers

The Subscribers page combines Joomla-user and email-only subscriber records. Select an email address to open its subscriber editor.

### Subscriber list

Search by email or subscriber information and filter by subscription status or suppression state. The list shows:

- email address and, for Joomla users, their display name and user ID;
- source, such as an external subscriber or Joomla user;
- global subscription status and any exceptional delivery block;
- confirmation date;
- bounce count, last classification, date, and reason;
- creation date and ID.

The list deliberately separates **Subscription** from **Delivery** because they answer different questions. Subscription is the person's newsletter permission; Delivery is whether Punga Mail can currently send to the address. A subscriber may therefore be **Subscribed** while **Delivery blocked** because of a permanent bounce. The subscriber record remains present and subscribed; only sending is suppressed.

| Column/state | Meaning |
| --- | --- |
| Subscription: Subscribed | The person has newsletter permission enabled. |
| Subscription: Pending | Waiting for confirmation. |
| Subscription: Unsubscribed | The person has globally opted out. |
| Delivery: Deliverable | No current suppression prevents Punga Mail from sending. |
| Delivery: Delivery blocked — permanent failure | A hard bounce indicates a permanent address/domain failure, so delivery was stopped immediately. |
| Delivery: Delivery blocked — temporary-failure threshold reached | Repeated soft bounces reached the configured threshold. |
| Delivery: Temporary failure — X of Y | Delivery is still allowed, but soft failures are accumulating toward the configured threshold. |

### Toolbar actions

**New** directly adds either:

- an email-only address; or
- an existing Joomla user.

Administrator additions are intentional reactivation actions: adding an existing protected address can restore its active subscription and clear suppression. Verify consent and address correction before doing this.

The creation form also accepts an optional external recipient name and published-topic memberships. Topic selection does not replace the global subscription state.

**Unsubscribe selected** globally unsubscribes the selected records. It is not the same as removing one topic.

**Send confirmation selected** sends or resends confirmation for the selected records, subject to the configured validity and resend rules.

For a record suppressed by a permanent delivery failure or temporary-failure threshold, **Clear bounce suppression** appears in the Bounce column. Use it only after the address has been corrected or you have good reason to believe it can receive mail again. Bounce history is retained; the suppression block and active temporary-failure count are cleared.

### Subscriber editor and Channel choices

Select an email address in the list to edit that subscriber. The editor shows:

| Field | What it means |
| --- | --- |
| Email / Joomla user | The canonical subscriber identity. Existing identities are read-only here; edit a linked account through Joomla Users. |
| Recipient name | Optional name for an external email-only subscriber. Linked accounts use the Joomla display name. |
| Newsletter permission | The master state: Pending, Subscribed, or Unsubscribed. Pending can be retained for an existing confirmation request but cannot be assigned manually. |
| Channels | All non-trashed Channels. Unpublished Channels remain visible to administrators. For a linked Joomla user, Channels the user is not eligible for are disabled and explain the required account/group access; external subscribers cannot be assigned registered/group-restricted Channels. |

**Apply** saves and keeps the editor open. **Save & Close** saves and returns to Subscribers. **Cancel** discards unsaved changes.

Topic updates are deliberately independent of global consent. Merely selecting or clearing topics never subscribes, unsubscribes, or unsuppresses the address. Explicitly changing an unsubscribed or blocked address to **Subscribed** is a privileged reactivation and clears its current suppression; use it only after checking consent and any bounce reason.

Administrators may also manage a registered user's topics in Joomla's user editor. Bulk memberships remain available through CSV import. Subscribers can manage their own published topics in their Joomla profile, signup module, or Newsletter menu page.

## Templates

Templates store reusable subject, Markdown body, message options, and visual overrides.

The Templates list supports search, sorting, pagination, trash, restore, and delete. Opening a template shows the editor.

### Template fields

| Field | What it means |
| --- | --- |
| Title | Administrator-facing name of the template. Required. |
| Email subject | Default subject copied to a newsletter. A digest can replace it with a subject pattern. |
| Newsletter body (Markdown) | Reusable message content. Place `{new_content}` exactly where selected website content should appear. Place `{recipient}` where the recipient's name should appear. |

The body supports Markdown headings, emphasis, lists, links, images, pipe tables, and a standalone `---` horizontal rule. The Punga Mail Markdown editor uses Joomla CodeMirror when available without line numbers, provides formatting buttons and context-specific placeholder insertion, and can switch between source and a preview generated by Punga Mail’s own renderer. The Table button inserts a starter pipe table. The Image button opens Joomla's media picker when available and inserts the selected image as Markdown. Detailed syntax/placeholder help is collapsed by default. Images may use HTTP(S), root-relative, or site-relative URLs. Preview the output because mail clients differ.

### Template message options

| Field | Choices |
| --- | --- |
| Mail body heading | **Inherit** uses the global setting; **Custom** uses the custom heading field; **Use site name** deliberately uses Joomla's site name; **No heading** omits it. |
| Custom heading | Text used only when heading mode is Custom. |
| Browser view | **Inherit**, **Enabled**, or **Disabled**. |
| Reply-To | **Inherit**, **Custom**, or **None**. |
| Reply-To email/name | Values used when Reply-To is Custom. |

### Template style overrides

Every global design property can be overridden: content width, backgrounds, text/heading/link colors, font family and size, padding, logo URL and width, and footer color. Leave an override blank to inherit the global value.

Template custom CSS is added after the global custom CSS. Newsletter custom CSS can add another layer.

**Preview** renders the template. The editor follows Joomla's familiar main-content plus right-sidebar pattern: reusable subject/body live in **Mail content**, visual overrides live in **Design**, and template-level message behavior such as the mail heading, browser view and Reply-To lives in the right sidebar. Templates do not have an artificial Published/Unpublished state. Selected-content item formatting is managed centrally under **Content layouts**, not inside individual Templates or Newsletters. **Save**, **Save & Close**, and **Cancel** behave like standard Joomla editor actions.

Applying a template in a newsletter **copies** its values. Later template edits do not alter an existing draft and can never alter a sent snapshot.

## Newsletters

### Newsletters list

Search by title or subject. Filter by Joomla record state and delivery status. Sort by title, subject, status, counts, send/schedule time, creation date, or ID.

The delivery statuses are:

| Status | Meaning |
| --- | --- |
| Draft | Editable and not scheduled or queued. |
| Scheduled | Editable until its scheduled time is processed. |
| Queued | Frozen and waiting for queue processing. |
| Sending | At least part of the frozen queue is being processed. |
| Sent | Queue processing completed without permanent queue failures. Later bounces may still be recorded. |
| Sent with failures | Delivery processing completed, but one or more recipient rows failed. |
| Failed | Mailing setup or processing failed as a whole. |
| Cancelled | The schedule or unsent remainder was cancelled. Messages already accepted by transport cannot be recalled. |

Draft/sent lifecycle status is separate from Joomla's Current/Archived/Trashed record state. **Archive** removes an old newsletter from the normal Current list without deleting it or changing its delivery status. Use the **Archived** filter to inspect archived newsletters and **Unarchive** to return them to the Current list. Archived newsletters retain their selected content, immutable sent snapshot, recipients, statistics and delivery history, and may still be duplicated as a new draft.

A Scheduled, Queued or Sending newsletter cannot be archived because it is still operationally active. Cancel the schedule or remaining delivery first, or wait for sending to finish. **Trash** remains a separate action for records you intend to remove; it likewise does not rewrite the historical delivery status.

### Newsletter editor

The editor follows Joomla's familiar main-content plus right-sidebar pattern. The main area keeps the **Settings**, **Mail content**, **Content selection**, and **Design** tabs. Template selection/application stays on **Mail content**, where its effects are immediately visible; the right sidebar is reserved for the Newsletter's real Punga Mail lifecycle status and scheduling controls.

Only Draft and Scheduled newsletters remain editable. Once queueing begins, Punga Mail shows the frozen message, statistics, and frozen recipients instead of the editor.

If you change a Newsletter and then close the browser tab or navigate away without saving, the browser shows its standard unsaved-changes confirmation. The same protection applies to Template and Automatic Newsletter editors. Browsers intentionally control the wording of this warning.

#### Basic fields

| Field | What it means |
| --- | --- |
| Internal title | Administrator-facing newsletter name. It is not the email subject or body heading. Required. |
| Template | Optional template to copy from. Choose it, then select **Apply template**. |
| Email subject | Subject shown in the recipient's mail client. Required. |
| Newsletter body (Markdown) | Your message. `{new_content}` inserts selected website content at that exact position. `{recipient}` is personalized with a Joomla display name when available and otherwise the email address. |

Applying a template replaces the draft values with the selected template's copied values. Save important draft changes first if you may want to recover them.

#### New content picker

The picker reads content types registered with Joomla, including compatible third-party content types. It is not limited to ordinary Joomla articles.

| Control | What it does |
| --- | --- |
| Content published since | Sets the earliest publication date shown in the candidate list. |
| Apply filters | Reloads the candidates using the selected date and content types. Save the draft as part of the action. |
| Content types | Chooses which registered content sources are searched. |
| Search | Filters the currently displayed candidate rows by source/title in the browser. It does not change stored content. |
| Item title | Opens the current frontend page for that content item in a new browser tab, so you can inspect it without leaving the Newsletter editor. |
| Item checkbox | Includes or removes that item from the newsletter selection. |
| Selected content | Shows the items currently included in `{new_content}`. Drag selected rows to change newsletter order. |
| Available content sort | Sorts only the unselected candidate list by newest, oldest, or title. It does not disturb the manual order of selected items. |
| Select visible | Selects all currently visible search results. |
| Clear selected | Removes all items from the current newsletter selection. |
| Title override | Changes the title in this newsletter only. |
| Excerpt override | Changes the excerpt in this newsletter only. |

Selecting content does nothing unless the body contains `{new_content}`. Punga Mail does not automatically append the items or add a “new content” heading.

### Layout of selected content

Selected content is formatted centrally under **Punga Mail → Content layouts**. This keeps Newsletter, Template and Automatic Newsletter editors focused on message composition instead of repeating layout controls in several places.

The **Default content layout** is used for every registered content type unless that type has its own custom layout. The list also shows each usable Joomla registered content type, for example Articles or a calendar Event type.

Every layout always has the normalized Punga Mail placeholders:

- `{title}`
- `{title_link}`
- `{publish_date}`
- `{excerpt}`
- `{read_more}`
- `{url}`
- `{content_type}`

For a specific registered content type, Punga Mail also inspects its registered backing database table and exposes safe table columns directly as placeholders. No Punga Mail plugin or cooperation from the originating extension is required. If an Event table contains fields such as `start_at`, `end_at` and `venue`, that Event layout can use:

```text
### {title_link}

**{start_at|datetime}**

{venue}

{excerpt}

{read_more}
```

The Content layout editor shows the placeholders available for the selected content type in a right-hand reference panel, including the database type. Date-like fields also offer `{field|date}`, `{field|time}`, and `{field|datetime}` formatting.

`{publish_date}` remains the date Punga Mail uses to describe when the item became new website content; it is deliberately separate from semantic fields such as an event start date. Generic Punga Mail placeholders take precedence over same-named database columns so Newsletter title/excerpt overrides continue to work. Obvious credential/secret fields are not exposed as layout placeholders.

Excerpts are generated as readable text without executing Joomla content plugins; unresolved plugin-command markers such as `{snippet alias="example"}` are removed before the excerpt is shortened. If a selected website item is later removed, unavailable, or inaccessible to part of the resolved audience, Preflight reports it. Inaccessible selected content is a blocking error, preventing accidental disclosure.

#### Message options and style

Newsletter message options use the same choices as templates. **Inherit** first uses the applied template value and then the global value. A newsletter's custom choice is the final override.

Every visual field can also override the applied template/global design. Leave it blank to inherit. Newsletter custom CSS is the last CSS layer.

The preview, test message, Preflight, real message, and browser version all use the same resolved heading and design hierarchy.

#### Audience

| Control | What it does |
| --- | --- |
| All globally subscribed recipients, regardless of topic | Includes every globally active, confirmed, non-suppressed subscriber, including people with no topic. When selected, topic choices do not narrow this source. |
| Channels | When the all-subscriber choice is clear, includes active members of any selected Channel. Multiple Channels are combined and deduplicated. |
| Additional Joomla user groups | Adds eligible unblocked users in any selected group, including inherited child-group membership. Explicit Punga Mail opt-outs and suppressions still win. Users without an explicit preference are included only when the corresponding Component Option allows it. |

A new Newsletter starts with **no audience selected**. Choose at least one effective source deliberately; Punga Mail does not assume that “all subscribers” is safe. Existing newsletters retain their saved audience. The live summary explains the current union. The final audience is calculated at Preflight/queue time, not assumed from raw group or topic totals. Preflight blocks a mailing with no source and warns when all-subscriber targeting adds people outside selected topics.

#### Toolbar actions

| Action | Result |
| --- | --- |
| Save | Saves and keeps the editor open. |
| Save & Close | Saves, checks in the record, and returns to the list. |
| Cancel | Leaves without saving the current changes and checks in the record. |
| Preview | Saves and displays rendered HTML and plain text without sending. |
| Send test mail | Saves and sends a synchronous test through the active Punga Mail outgoing transport (Joomla settings or Custom SMTP). It does not create the real audience queue. |
| Check recipients & send | Saves and opens the non-mutating Preflight screen. No messages are sent until you confirm queueing. |
| Duplicate as new draft | Creates an independent editable Draft from any saved Newsletter, including a Draft, Scheduled, or already-sent Newsletter. |

### Preview and test mail

Preview uses the current administrator for `{recipient}` and disables both personal actions: Unsubscribe and View in browser. Neither preview link can navigate. A test message exercises Punga Mail's active outgoing transport and uses the same rendering hierarchy, but it is not a substitute for Preflight because it does not resolve the real audience.

### Check before sending: final validation

Preflight never changes subscriber state. It shows the resolved recipients, excluded candidates and reasons, rendered HTML/plain text, sender, content count, and validation results.

Blocking errors prevent queueing. The checks include:

- subject and sender validity;
- Reply-To validity when used;
- at least one eligible recipient;
- non-empty HTML and plain-text output;
- unsubscribe mechanism;
- browser-view link when enabled;
- missing selected content references;
- Joomla access permission safety for selected content;
- message size over 1 MiB.

Warnings do not block sending. They include malformed or suspicious links, images without an `alt` attribute, message size over 500 KiB, and a paused queue.

The recipient panels explain final inclusion and exclusion. Typical exclusion reasons are invalid address, globally unsubscribed/not subscribed, suppressed, permanent delivery failure, temporary-failure threshold, not in a selected topic, and duplicate eliminated.

If the result is correct, choose **Queue emails** for immediate queueing. The confirmation dialog states the number of unique recipients about to be queued. Scheduling does not require navigating to Preflight first; use the Schedule controls in the Newsletter editor sidebar instead.

### Scheduled newsletters

The Newsletter editor sidebar accepts the date/time in Joomla's configured site timezone, which is shown beside the control. **Schedule** and **Reschedule** run the same blocking sendability checks used by Preflight before the schedule is accepted; the scheduled-send task validates again when it creates the immutable queue. A Scheduled newsletter stays editable until that task processes the due record.

Use **Cancel schedule** in the sidebar before queueing to return to Draft. Once the Newsletter becomes Queued or Sending, editing is no longer allowed.

Scheduled sending requires both:

1. **Punga Mail — Prepare scheduled newsletters**, to move due newsletters into the queue; and
2. **Punga Mail — Send pending newsletters**, to send the queued recipients.

### Queued, sending, and sent newsletter detail

After queueing, the newsletter detail shows the immutable subject/body snapshot, counts, and frozen recipient rows. Each row includes recipient name, email, source, queue status, attempts, send time, and last error.

Available operational actions include:

- **Duplicate as draft** to create an editable copy;
- **Pause mailing** or **Resume mailing** for this newsletter only;
- **Cancel remaining** to cancel pending/processing rows. Already accepted messages cannot be recalled.

The delivery statistics are historical and tied to that mailing:

- intended;
- queued;
- accepted by transport;
- temporary failures;
- permanent failures;
- hard bounces;
- soft bounces;
- suppressed before queue creation;
- attributable unsubscribes;
- remaining;
- cancelled.

### Browser view

When enabled through the global/template/newsletter hierarchy, the email contains a browser-view link. The public page uses the immutable sent snapshot and a random public key. It does not expose a draft, subscriber token, unsubscribe token, or recipient-specific version.

Keep a published Punga Mail subscription menu item so Joomla can produce a clean SEF route. The browser page is a single-mailing view, not a public archive.

## Automatic Newsletters

An Automatic Newsletter is a recurring definition that creates newsletters from newly published registered Joomla content.

The Automatic Newsletters list supports search, enabled/disabled filtering, sorting, pagination, enable, disable, trash, restore, and delete. It shows the chosen template, generation mode, next run, and status.

### Automatic Newsletter fields

#### Basics

| Field | What it means |
| --- | --- |
| Title | Administrator-facing automation name. |
| Template | Required source for body, default subject, message options, and design. Include `{new_content}` in the template body where digest items should appear. |
| Subject pattern | Optional replacement for the template subject. Supports `{date}` and `{site_name}`. If blank, the template subject is used. |

#### Content

Select one or more registered content types. Their names follow the current Joomla administrator language when the registered component provides a matching language string. For each source, optional **Category IDs** may contain comma-separated numeric Joomla category IDs, for example `1, 4, 12`. Leave it blank to include all categories from that content source.

Category filters are applied only where the registered content provides a category ID.

Automatic Newsletters also provide three selection controls: **Order** chooses newest-first or oldest-first; **Maximum items** caps the number included (`0` means no limit); and **Minimum items** can require a certain number of eligible items before a newsletter is generated (`0` disables only this threshold). A value of `0` does **not** cause an empty newsletter to be sent: the separate **If no new content is found** setting still decides whether an empty run is skipped or produces an empty draft. The minimum is evaluated before the maximum. If a positive minimum is missed in **Since last** mode, Punga Mail records a skipped run without advancing the content cutoff, allowing eligible content to accumulate for the next scheduled run.

#### Schedule and generation

| Field | What it means |
| --- | --- |
| Next run | Earliest date/time at which the digest task should run this definition, displayed in Joomla's site timezone. |
| Repeat every | Number plus unit for the recurrence: days, weeks, or calendar months. Months are real calendar months rather than a fixed 30-day approximation, so monthly schedules do not drift. |
| Content cutoff: Since last | Uses the previous successful automatic-newsletter cutoff so the same item is not intentionally repeated. For the first run, it looks back one recurrence interval. |
| Content from a recent time period | Uses a fixed recent window on every run. Selecting it reveals **Look back … days**. Overlapping windows can intentionally repeat content. |
| Look back … days | Shown only for the fixed recent-period mode. Default: 7 days. |
| Create draft | Safe default. Generates an editable newsletter and stops. |
| Create and send automatically | Generates and immediately creates the immutable send queue without administrator review. Selecting this mode requires the explicit confirmation checkbox when it is newly enabled. |
| Confirm automatic sending | Safety acknowledgement required when changing a digest to automatic sending. It is not a persistent “always checked” setting. |
| Empty digest: Skip | Default. Creates no newsletter, sends nothing, and records a no-content history result. |
| Empty digest: Create draft | Creates a draft even when no matching content remains. It stops at Draft even when the digest normally sends automatically, so an administrator must add/review content before sending. |

The recipient controls behave exactly like the newsletter editor: sources are combined, topic choices are a union, and all addresses are deduplicated and filtered by opt-out/suppression rules.

### Content access safety

Before generating a digest, Punga Mail resolves its current recipients and checks Joomla's access levels and applicable category access. It includes an item only when **every resolved recipient would normally be allowed to view that item on the website**.

This deliberately conservative shared-content rule prevents an automatic newsletter from exposing restricted content to a broader audience. If a registered content type does not provide usable access metadata, its items are excluded instead of being assumed public. If recipients have mixed access, use separate automatic newsletters/audiences for public and restricted content. Excluded items and no-content outcomes appear in the run details.

### Automatic Newsletter task and history

Create and enable **Punga Mail — Create automatic newsletters** in Joomla Scheduled Tasks. A frequent task, such as every 5 or 15 minutes, is reasonable; each automatic newsletter’s own Next run and recurrence decide whether work is due.

An automatic-send definition also requires **Punga Mail — Send pending newsletters**. A draft run does not send until an administrator reviews its generated newsletter and sends or schedules it.

If Component Options → Automatic newsletters → **Notify reviewer about new drafts** is enabled, a successful draft run also sends the configured reviewer a direct administrator link. Automatic-send runs do not send this review notification.

The editor's history table shows the latest runs with a plain-language result (draft created, queued, skipped, or failed), the generated Newsletter title/link and its current lifecycle state, content-item count, duration, and details such as access exclusions or errors. A **Skipped — not enough content** result also confirms that the rolling cutoff was retained.

Only enabled digests run. Editing a digest does not itself generate a newsletter.

## Delivery / Bounces

This page contains operational controls, the live mail queue, and health information. Bounce mailbox credentials themselves are stored under **Component Options → Bounce / return mailbox**.

### Mail queue

The queue table shows the real per-recipient send rows, including Newsletter, recipient, state, attempts, next-attempt time, queued/updated/sent timestamps, and the latest error. Filter it by queue state, Newsletter, or recipient/title search.

**Retry selected** is deliberately limited to failed rows and resets them for another normal queue attempt. **Cancel selected** is limited to pending or failed unsent rows; processing or already-sent rows are never recalled or rewritten by this action.

### Bounce mailbox card

The card shows whether the mailbox is configured and the current server without revealing its password. **Open Component Options** goes to the settings. **Check returned mail now** performs one mailbox run immediately.

The same card also shows the **last returned-mail check**. Manual checks and **Punga Mail — Check returned mail** Scheduled Task runs update the same summary: check time, number of returned messages processed, permanent failures, temporary failures, and addresses newly excluded from future delivery. A failed mailbox check is shown with its sanitized error instead of silently disappearing into the scheduler log.

If the most recent successful check newly excluded one or more addresses, the Dashboard also surfaces this under **Needs attention** with a link back to Delivery. Punga Mail suppresses delivery to those addresses; it does **not** delete subscriber records, Channel memberships, or bounce history.

The recent-bounces table shows timestamp, address, classification, SMTP/status code, diagnostic message, and whether the address is now suppressed.

Punga Mail processes standard delivery-status notifications where possible:

- **permanent/hard failures** suppress immediately after the first confirmed hard bounce;
- **temporary/soft failures** increase the subscriber's count and suppress only when **Temporary failures before blocking address** reaches the configured threshold;
- while a soft-bounce address is still below the threshold, the UI shows its progress, for example **Temporary failure — 1 of 3 before delivery is stopped**;
- unknown failures are retained for inspection but do not automatically pretend to be permanent failures;
- duplicate delivery reports are ignored;
- bounce history is retained after suppression is cleared.

### Queue control

**Pause queue** is global. It leaves queued messages pending rather than failed. **Resume queue** allows later Scheduled Task/manual runs to continue.

This differs from opening one Queued/Sending newsletter and pausing only that mailing. It also differs from **Cancel remaining**, which permanently cancels its unsent rows.

### Mail test

Enter a recipient address and select **Send test mail**. Punga Mail sends a small diagnostic message using the currently active outgoing transport—either Joomla settings or Custom SMTP—and displays the real success or failure result.

This tests local configuration and transport acceptance only. It does not prove inbox placement, DNS authentication, or human delivery.

### Diagnostics

The page reports information that can be determined locally:

- outgoing transport source (Joomla settings or Custom SMTP), active mailer type, and Custom SMTP host where applicable;
- resolved sender address and basic validity;
- PHP IMAP availability;
- queue batch size;
- maximum attempts;
- retry interval;
- global queue state.

SPF, DKIM, and DMARC guidance is advisory. Punga Mail does not claim to configure or verify DNS automatically.

## Subscriber Import / Export

### Import

Upload a UTF-8 CSV file with a header row. Punga Mail detects comma, semicolon, or tab delimiters. Files are limited to 5 MiB and up to 20,000 data rows per import.

The preview shows the first 20 rows and lets you map:

| Mapping | Accepted content |
| --- | --- |
| Email | Required email-address column. |
| Name | Optional subscriber display name. |
| Status | Optional. Active values: `active`, `subscribed`, `1`, `yes`, `ja`. Unsubscribed values: `unsubscribed`, `inactive`, `2`, `no`, `nein`. Other/blank values do not explicitly request a state change. |
| Topics | Optional topic titles or aliases separated by `|`, `;`, or `,`. Only currently published topics are matched. Imported memberships are added without removing other existing topic memberships. |

Duplicate addresses within one file are skipped after the first occurrence. Malformed addresses are invalid. Existing addresses are updated rather than duplicated.

Punga Mail does **not** silently reactivate a globally unsubscribed, suppressed, or hard-bounced address. If a row explicitly requests Active, it is reported as a protected-state conflict unless **Explicitly reactivate protected addresses** is checked.

That checkbox is intentionally prominent and dangerous: use it only when you have verified consent and corrected the underlying reason. A CSV file merely containing an address is not enough to reactivate it.

After commit, the result reports added, updated, unchanged, skipped, invalid, protected-state conflicts, and errors.

### Export

Choose a scope:

- all subscribers;
- active subscribers;
- unsubscribed subscribers;
- suppressed subscribers.

Optionally select one or more Channels. Multiple selected Channels include members of any selected Channel; 0.4.0 fixes the prepared-statement error that previously affected multi-Channel exports. The UTF-8 CSV contains email, name, numeric status, source, language value, topic aliases, suppression reason, bounce counts, and last-bounce details.

Treat exported files as personal data and store/share them accordingly.

## Frontend signup module

Create or edit a **Punga Mail Signup** site module under Joomla's module manager. Choose its position, menu assignment, publication status, and normal Joomla display options as you would for another module.

Punga Mail-specific options are:

| Option | What it does |
| --- | --- |
| Intro text | Optional plain text displayed above the form. Line breaks are preserved. |
| Button label | Optional replacement for the translated Subscribe label. |
| Channels | Controls which published Channels this module offers. See the modes below. |

### Channel selection modes

| Module configuration | Visitor experience |
| --- | --- |
| No Channels selected | Offers all currently published Channels the current visitor/account is eligible to subscribe to. |
| Exactly one Channel selected | Hides the selector, names the Channel, and performs the Channel action directly. |
| Multiple Channels selected | Shows only those published Channels and allows one or more choices. |

For logged-out visitors, the module asks for an email address and sends a confirmation link. Channel choices become active only after that confirmation.

For logged-in users, the module uses the Joomla account email and current Punga Mail record. Topic changes apply only to the topics visible in that module; memberships in topics not shown by that module remain unchanged.

Only the exactly-one-topic module configuration uses the direct single-topic action. With no configured topics, even one currently published topic remains an explicit visitor choice. Multi-choice forms use checkboxes and **Save my choices** to update only the visible topics.

The separate **Start receiving newsletters** / **Stop all newsletters** control changes newsletter reception, the master permission. Leaving one topic does not switch reception off globally. A user with newsletter reception disabled may still see stored topic choices, but cannot receive mail until intentionally enabling reception again.

## Joomla user-profile integration

The enabled Punga Mail user plugin adds a **Newsletter** fieldset to Joomla registration, frontend profile editing, administrator user editing, and the administrator's own profile.

| Field | What it does |
| --- | --- |
| Receive newsletters | **Yes** enables the user's master Punga Mail permission. **No** globally opts the address out and overrides every topic/Joomla-group selection. |
| Channels | Multi-select containing currently published Channels the Joomla account is eligible to subscribe to. Existing eligible memberships are preselected. Saving adds selected memberships and removes cleared visible memberships. |

The two controls are deliberately independent. Topic choices are retained when **Receive newsletters** is No, but no newsletter is delivered until the master permission is Yes again. This lets a user opt out completely without losing their preferred topic set.

Leaving all topics clear does not mean “receive nothing.” A user with newsletter reception enabled may still receive a newsletter targeted to **All globally subscribed recipients, regardless of topic** or an eligible Joomla user group. The selector controls only topic-targeted mailings.

Unpublished topics are not shown or changed by the profile form. Their stored history/membership is retained. Registered users therefore no longer require a published signup module solely to choose topics, although the module remains useful for inline account preferences and email-only signup.

## Newsletter subscription menu item

Create a menu item of type **Punga Mail → Newsletter subscription**.

The page is a complete standalone subscription destination and acts as the Joomla SEF routing anchor for confirmation, unsubscribe, and browser-view routes. Keep it Published. It may be assigned to a hidden menu if it should not appear in site navigation.

- Logged-out visitors enter an email address, choose from published Channels available to external subscribers, and confirm their address using the email Punga Mail sends them.
- Logged-in users see their account email, a clearly labelled newsletter-reception master state, and a preselected checklist of published Channels allowed for their Joomla account/group membership.
- The topic Save action changes only topic memberships. The separate Start/Stop all newsletters action controls global delivery.
- A consequence summary states what the current topic choice means, including the possibility of general newsletters when no topic is selected.
- If no topics are published, the page still provides global newsletter signup and subscription management.

Unlike the signup module, this menu page has no configured Channel subset: it offers every currently published Channel the current visitor is eligible to subscribe to.

Do not restrict the menu item to an access level that ordinary email recipients cannot use, or their confirmation and unsubscribe links may not reach the intended page.

## Scheduled Tasks

Go to **System → Scheduled Tasks**, select **New**, choose the Punga Mail task type, select a suitable execution rule, enable it, and save.

| Task | When it is needed | Suggested frequency |
| --- | --- | --- |
| Punga Mail — Send pending newsletters | Always, for unattended real-newsletter delivery and automatic sending. | Every 1–5 minutes, adjusted for batch size and provider limits. |
| Punga Mail — Prepare scheduled newsletters | Only for ordinary newsletters you manually scheduled for a specific date/time. It notices when that time arrives and prepares the already-created newsletter for delivery. | Every 1–5 minutes. |
| Punga Mail — Create automatic newsletters | For recurring Automatic Newsletters. It finds eligible new content and creates the due newsletter as a draft or approved automatic send. It is not used for an ordinary newsletter scheduled for a fixed date/time. | Every 5–15 minutes. Each definition has its own due time. |
| Punga Mail — Check returned mail | When returned-mail processing is configured. | Every 15–60 minutes, depending on volume. |
| Punga Mail — Newsletter reminder | When the optional administrative reminder is enabled. | Daily. |

Task frequency controls how soon Punga Mail notices due work; it does not replace the newsletter schedule or digest recurrence. For example, a newsletter scheduled at 10:00 may be queued at 10:04 if its task runs every five minutes.

## Editor locks and Global Check-in

Opening an existing newsletter, template, topic, or digest checks out that database record to prevent conflicting edits.

- **Save & Close** and **Cancel** check the record in normally.
- Closing the browser tab or navigating away without either action can leave it checked out.
- Such records appear in Joomla's **Global Check-in** dashboard card/page and can be checked in by an authorized administrator.

Do not use Global Check-in while another administrator is genuinely editing the item.

## Access and safety notes

- Joomla ACL controls access to Punga Mail's administrator component and actions.
- Forms use Joomla's normal request-token protection.
- Subscriber confirmation and unsubscribe links use security tokens; do not copy them into logs or public pages.
- Public browser views use a separate random key and an immutable sent snapshot.
- Preflight does not alter subscriptions.
- Sending freezes message and recipient history. Duplicate as Draft for later reuse.
- A pause is reversible; cancellation is not. Already sent messages cannot be recalled.
- Global unsubscribe and suppression always take precedence over audience targeting.
- Digest and manual-newsletter access checks prevent restricted website content from being sent to recipients who could not normally view it.

## Common troubleshooting

### A subscriber expected a message but is absent

Open Preflight and inspect **Excluded recipients**. Check global status, topic membership, selected audience sources, suppression/bounce state, address validity, and duplicate elimination. For Joomla-group users, also check the **New Joomla users subscribed by default** option and their explicit profile preference.

### A newsletter remains Scheduled

Confirm that **Punga Mail — Prepare scheduled newsletters** exists, is enabled, and has run after the due time. Then confirm that **Punga Mail — Send pending newsletters** is also enabled.

### A newsletter remains Queued

Check the Dashboard task card, global queue pause, per-mailing pause, task execution history, batch size, retry status, and the active outgoing-mail configuration under Punga Mail Options/Delivery diagnostics.

### An Automatic Newsletter does not run

Confirm that the digest itself is enabled, its Next run is due, its template is available, at least one content type is selected, and the digest Scheduled Task is enabled. Check the digest history for no-content, access-exclusion, or failure details.

### Returned-mail processing fails

Confirm PHP IMAP is available in Delivery diagnostics. Recheck host, port, security, folder, username, certificate, and password under Options, then use Test connection. Remember that leaving the password blank keeps the previous stored value.

### A browser-view URL does not look SEF-friendly

Create a Published **Punga Mail → Newsletter subscription** menu item. It may live in a hidden menu.

### A record says it is checked out

Ask the named administrator to use Save & Close or Cancel. If the edit session was abandoned, use Joomla Global Check-in.

## Tutorials

- [Run the complete live acceptance and regression test](TEST_GUIDE.md)
- [Create and send a newsletter](TUTORIAL_NEWSLETTER.md)
- [Create an automatic digest](TUTORIAL_DIGEST.md)
- [Set up topics and frontend signup](TUTORIAL_TOPICS_AND_SIGNUP.md)
- [Create and use templates](TUTORIAL_TEMPLATES.md)
- [Set up delivery health and bounce handling](TUTORIAL_DELIVERY_HEALTH.md)
- [Import and export subscribers](TUTORIAL_IMPORT_EXPORT.md)

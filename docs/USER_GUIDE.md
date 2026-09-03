# Punga Mail Administrator Guide

This guide explains Punga Mail from the point of view of a normal Joomla administrator. It covers the everyday screens, controls, settings, and decisions involved in collecting subscriptions, composing newsletters, scheduling or automating delivery, and keeping the mailing list healthy.

The guide describes Punga Mail 0.3.4. Names may appear in English or German depending on the administrator language selected in Joomla.

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

Punga Mail sends through Joomla's configured mail system. Configure the actual mail transport under Joomla's global server settings; Punga Mail does not have a separate SMTP system.

## The main concepts

### Global subscription and topic membership are different

Punga Mail keeps two related choices separate:

| Choice | Meaning |
| --- | --- |
| Global subscription | Whether the address may receive Punga Mail newsletters at all. |
| Topic membership | Which named topics the subscriber has chosen. |

A subscriber can leave one topic and remain subscribed to other topics. **Unsubscribe completely** is different: it opts the address out globally and prevents that address from being reintroduced through Joomla user-group targeting.

The Joomla user-profile field **Receive newsletter** controls the global subscription. The **Newsletter topics** selector beside it manages the same topic memberships as the frontend Punga Mail signup module.

### Recipient sources are combined

A newsletter or digest can use three recipient sources:

- all confirmed subscribers;
- members of selected Punga Mail topics;
- members of selected Joomla user groups.

These sources are combined as an **either/or audience**, then duplicate email addresses are removed. Selecting two topics means members of either topic, not only people in both. Joomla groups remain separate from Punga Mail topics.

If **All confirmed subscribers** is selected, topic selections do not narrow that source. Clear that option when the newsletter should go only to selected topics. Joomla group selections add their eligible members to the audience.

Every source is still subject to address validity, global opt-out, and suppression rules.

### Delivery terminology

| Term | Meaning |
| --- | --- |
| Queued | The immutable mailing and recipient rows have been created and await processing. |
| Transport accepted | Joomla's configured mail transport accepted the message for onward delivery. It does not prove that a human received or read it. |
| Temporary failure | An attempt failed but may be retried according to the queue settings. |
| Permanent failure | The queue exhausted its retries or encountered a non-recoverable send failure. |
| Hard bounce | A later delivery report indicates a permanent address failure, such as an unknown mailbox. |
| Soft bounce | A later delivery report indicates a temporary condition, such as a full mailbox or transient server problem. |
| Suppressed | Punga Mail must not send to that normalized email address. |

## First-time setup checklist

After installing or updating Punga Mail:

1. Open **Components → Punga Mail → Dashboard** and confirm the installed version.
2. Open **Options** and review the sender, design, subscription, queue, and bounce settings.
3. Confirm that Joomla itself can send mail.
4. Create and enable **Punga Mail — Process send queue** in Joomla Scheduled Tasks.
5. Create a published **Punga Mail → Newsletter subscription** menu item. It may be hidden from the visible menu.
6. Publish the **Punga Mail Signup** site module where visitors can find it.
7. Create any topics you want visitors to choose.
8. Send a mail test from **Delivery / Bounces**.
9. If bounce processing is required, configure and test the return mailbox, then create its Scheduled Task.

Create the digest, scheduled-send, and reminder tasks only if you use those features. The Dashboard warns when an enabled feature is missing its required task.

## Dashboard

Open **Components → Punga Mail** to reach the Dashboard.

The summary cards show:

- the Punga Mail version;
- active subscribers, with pending and suppressed totals;
- active newsletters, with draft, sent, and trashed totals;
- pending queue rows, with processing and failed totals.

**Process queue now** processes a batch immediately. It is useful for testing or maintenance, but normal unattended delivery should use Joomla Scheduled Tasks.

The **Scheduled Tasks** card shows whether each Punga Mail task type is configured and enabled, plus its next execution when known. The button below the card opens Joomla's Scheduled Tasks manager.

Warnings appear when:

- Punga Mail detects an incomplete database update; use **System → Maintenance → Database** to apply Joomla's suggested repair before sending;
- the normal send-queue task is not enabled;
- an enabled digest exists without the digest task;
- a scheduled newsletter exists without the scheduled-newsletter task;
- a bounce mailbox is configured without the bounce-processing task.

## Component Options

Open **Options** from the Dashboard or Subscribers toolbar. Joomla's **Toggle Inline Help** button displays the descriptions beside the fields.

### Mail

| Setting | What it controls |
| --- | --- |
| From name | The sender name displayed by mail clients. Leave blank to use Joomla's global sender name. |
| From email | The sender email address. Leave blank to use Joomla's global sender address. The address must be valid. |
| Reply-To mode | **None** omits a Reply-To address. **Custom** uses the Reply-To fields below. Templates and newsletters may inherit, replace, or disable this choice. |
| Reply-To email | The address that receives ordinary reader replies when custom Reply-To is enabled. It is validated before sending. |
| Reply-To name | The optional display name for the Reply-To address. |
| New Joomla users subscribed by default | Controls Joomla users who have no explicit Punga Mail preference. **No** is the consent-safe default. When **Yes**, eligible users selected through Joomla groups may receive mail unless they have opted out or the address is suppressed. |

Punga Mail always uses Joomla's configured transport. Change SMTP, sendmail, or other transport details in Joomla's global server configuration.

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
| Confirmation validity in hours | How long a double-opt-in confirmation link remains usable. Default: 48 hours; allowed range: 1–168. |
| Signup requests per IP per hour | Rate limit for public signup requests from one IP address. Default: 12; allowed range: 1–500. |
| Minimum resend interval | Minimum time before another confirmation message may be requested. Default: 10 minutes; allowed range: 1–1440. |
| Confirmation subject | Optional custom subject for confirmation messages. Supports `{site_name}` and `{email}`. Leave blank for the translated default. |
| Confirmation message | Optional Markdown message. Use `{confirmation_url}` for the required confirmation link; `{site_name}` and `{email}` are also available. Leave blank for the translated default. |

Email-only visitors and email-only topic preference changes use double opt-in. A request does not become active until the recipient uses the valid confirmation link.

### Send queue

| Setting | What it controls |
| --- | --- |
| Queue paused | Globally stops queue workers from sending. Queued rows remain pending and are not marked failed. Resume when ready. |
| Batch size | Maximum number of recipients processed in one task run. Default: 25; allowed range: 1–250. |
| Maximum attempts | Maximum attempts for a queue recipient before a failed state. Default: 3; allowed range: 1–10. |
| Retry interval | Delay in minutes before another attempt after a recoverable failure. Default: 15; allowed range: 1–1440. |

Do not increase the batch size without considering your mail provider's limits and the frequency of the Scheduled Task.

### Bounce / return mailbox

Punga Mail uses PHP IMAP to read unseen delivery-status notifications from a dedicated mailbox. This is independent of Joomla's outgoing SMTP configuration.

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
| Soft-bounce threshold | Number of recorded soft bounces after which an address is suppressed. Default: 3; allowed range: 1–20. Hard bounces suppress immediately. |

Use **Save mailbox settings** inside this section. The ordinary Joomla Options save button does not store the password field. **Test connection** tries a read-only connection and reports the result. Testing and processing require the PHP IMAP extension.

Punga Mail marks processed or unparseable unseen messages as seen. Use a dedicated mailbox so unrelated unread mail is not consumed by this process.

### Newsletter reminder

This optional feature alerts an administrator when no newsletter has been sent for a chosen period.

| Setting | What it controls |
| --- | --- |
| Enable reminder | Enables reminder evaluation. A corresponding Scheduled Task is still required. |
| Days since last newsletter | Age at which a reminder should be sent. Default: 30 days; allowed range: 1–3650. |
| Reminder email | Recipient of the reminder. Leave blank to use Joomla's global sender email. |
| Reminder subject | Subject of the administrative reminder. |
| Reminder message | Markdown body. Available placeholders are `{days}`, `{last_newsletter}`, `{last_sent_date}`, and `{site_name}`. Leave blank for the translated default. |

Run **Punga Mail — Newsletter reminder** daily. Punga Mail records the reminder for the current last-sent cycle so it does not send the same warning every day.

### Maintenance / Data

| Setting | What it controls |
| --- | --- |
| Uninstall: Remove database tables | **No**, the default, preserves all Punga Mail data when the extension is uninstalled. **Yes** permanently removes subscriber, suppression, topic, newsletter, queue, history, and related data during uninstall. |

Leave this at **No** unless permanent deletion is intentional and a suitable backup exists.

## Topics / Lists

Topics are named mailing choices such as News, Events, or Development Updates. They are not Joomla user groups.

### Topics list

The list supports Joomla search tools, status filtering, sorting, pagination, publish, unpublish, trash, restore, and delete.

The columns show the title and description, alias, current member count, publication state, ordering, and record ID.

- **Published** topics can be shown in the frontend module and selected for current newsletter/digest targeting.
- **Unpublished** topics remain stored but are not available for public selection.
- **Trashed** topics can be restored.
- A trashed topic cannot be permanently deleted while subscriber, newsletter, or digest relationships still use it.

### Topic editor

| Field | What it means |
| --- | --- |
| Title | Public name visitors and administrators see. Required. |
| Alias | Stable internal identifier. Leave blank to generate it from the title. It must be unique. Avoid changing it after using it in CSV workflows. |
| Description | Optional plain-text explanation shown by the signup module when visitors choose among multiple topics. |
| Ordering | Numeric order used for topic lists and module choices. Lower values appear first. |

**Save** keeps the editor open. **Save & Close** saves and returns to the list. **Cancel** leaves without saving and releases the editor lock.

## Subscribers

The Subscribers page combines Joomla-user and email-only subscriber records.

### Subscriber list

Search by email or subscriber information and filter by subscription status or suppression state. The list shows:

- email address and, for Joomla users, their display name and user ID;
- source, such as an external subscriber or Joomla user;
- effective delivery status;
- confirmation date;
- suppression state and reason;
- bounce count, last classification, date, and reason;
- creation date and ID.

Effective status badges have these meanings:

| Badge | Meaning |
| --- | --- |
| Active | Globally subscribed and not suppressed. |
| Pending | Waiting for confirmation. |
| Unsubscribed | Globally opted out. |
| Suppressed | Blocked at the email-address level for a non-bounce reason. |
| Bounced | Suppressed because of a hard bounce or the soft-bounce threshold. |

### Toolbar actions

**New** directly adds either:

- an email-only address; or
- an existing Joomla user.

Administrator additions are intentional reactivation actions: adding an existing protected address can restore its active subscription and clear suppression. Verify consent and address correction before doing this.

**Unsubscribe selected** globally unsubscribes the selected records. It is not the same as removing one topic.

**Send confirmation selected** sends or resends confirmation for the selected records, subject to the configured validity and resend rules.

For a record suppressed by a hard bounce or soft-bounce threshold, **Clear bounce suppression** appears in the Bounce column. Use it only after the address has been corrected or you have good reason to believe it can receive mail again. Bounce history is retained; the suppression block and active soft-bounce count are cleared.

### Topic membership

An administrator can manage a registered user's topic memberships in Joomla's user editor. Bulk or email-only subscriber memberships can be managed through CSV import. Subscribers can manage their own published-topic choices in their Joomla profile or through the frontend signup module/subscription experience.

## Templates

Templates store reusable subject, Markdown body, message options, and visual overrides.

The Templates list supports search, sorting, pagination, trash, restore, and delete. Opening a template shows the editor.

### Template fields

| Field | What it means |
| --- | --- |
| Title | Administrator-facing name of the template. Required. |
| Email subject | Default subject copied to a newsletter. A digest can replace it with a subject pattern. |
| Newsletter body (Markdown) | Reusable message content. Place `{new_content}` exactly where selected website content should appear. Place `{recipient}` where the recipient's name should appear. |

The body supports Markdown headings, emphasis, lists, links, images, and pipe tables. Images may use HTTP(S), root-relative, or site-relative URLs. Preview the output because mail clients differ.

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

**Preview** renders the template. **Save**, **Save & Close**, and **Cancel** behave like standard Joomla editor actions.

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

Draft/sent lifecycle status is separate from Joomla's Active/Trashed record state. Trash hides a record through Joomla list management; it does not rewrite its historical delivery state.

### Newsletter editor

Only Draft and Scheduled newsletters remain editable. Once queueing begins, Punga Mail shows the frozen message, statistics, and frozen recipients instead of the editor.

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
| Item checkbox | Includes or removes that item from the newsletter selection. |
| Ordering | Controls where the item appears inside `{new_content}`. |
| Title override | Changes the title in this newsletter only. |
| Excerpt override | Changes the excerpt in this newsletter only. |

Selecting content does nothing unless the body contains `{new_content}`. Punga Mail does not automatically append the items or add a “new content” heading.

If a selected website item is later removed, unavailable, or inaccessible to part of the resolved audience, Preflight reports it. Inaccessible selected content is a blocking error, preventing accidental disclosure.

#### Message options and style

Newsletter message options use the same choices as templates. **Inherit** first uses the applied template value and then the global value. A newsletter's custom choice is the final override.

Every visual field can also override the applied template/global design. Leave it blank to inherit. Newsletter custom CSS is the last CSS layer.

The preview, test message, Preflight, real message, and browser version all use the same resolved heading and design hierarchy.

#### Recipients

| Control | What it does |
| --- | --- |
| All confirmed subscribers | Includes every globally active, confirmed, non-suppressed subscriber. When selected, topic choices do not narrow this group. |
| Topics | When All confirmed subscribers is clear, includes active members of any selected topic. Multiple topics are combined and deduplicated. |
| Additional Joomla user groups | Adds eligible unblocked users in any selected group, including inherited child-group membership. Explicit Punga Mail opt-outs and suppressions still win. Users without an explicit preference are included only when the corresponding Component Option allows it. |

Choose at least one effective source. The final audience is calculated at Preflight/queue time, not assumed from raw group or topic totals.

#### Toolbar actions

| Action | Result |
| --- | --- |
| Save | Saves and keeps the editor open. |
| Save & Close | Saves, checks in the record, and returns to the list. |
| Cancel | Leaves without saving the current changes and checks in the record. |
| Preview | Saves and displays rendered HTML and plain text without sending. |
| Send test mail | Saves and sends a synchronous test through Joomla's configured mailer. It does not create the real audience queue. |
| Check recipients & send | Saves and opens the non-mutating Preflight screen. No messages are sent until you confirm queueing. |
| Cancel schedule | Available on a Scheduled newsletter; returns it safely to Draft before queueing starts. |

### Preview and test mail

Preview uses the current administrator for `{recipient}` and disables the personal unsubscribe link. A test message exercises Joomla's real transport and uses the same rendering hierarchy, but it is not a substitute for Preflight because it does not resolve the real audience.

### Preflight: final validation

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

The recipient panels explain final inclusion and exclusion. Typical exclusion reasons are invalid address, globally unsubscribed/not subscribed, suppressed, hard bounced, soft-bounce threshold, not in a selected topic, and duplicate eliminated.

If the result is correct, choose one of:

- **Queue emails** for immediate queueing; or
- enter a site-timezone date/time and choose **Schedule send**.

The confirmation dialog states the number of unique recipients about to be queued.

### Scheduled newsletters

The date/time entry uses Joomla's configured site timezone, which is shown below the control. A Scheduled newsletter stays editable. Editing does not create a partial queue; the immutable snapshot is created only when the scheduled-send task processes the due record.

Use **Cancel schedule** before queueing to return to Draft. Once the newsletter becomes Queued or Sending, editing is no longer allowed.

Scheduled sending requires both:

1. **Punga Mail — Queue scheduled newsletters**, to move due newsletters into the queue; and
2. **Punga Mail — Process send queue**, to send the queued recipients.

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

## Automatic Digests

A digest is a recurring definition that creates newsletters from newly published registered Joomla content.

The Digests list supports search, enabled/disabled filtering, sorting, pagination, enable, disable, trash, restore, and delete. It shows the chosen template, generation mode, next run, and status.

### Digest fields

#### Basics

| Field | What it means |
| --- | --- |
| Title | Administrator-facing automation name. |
| Template | Required source for body, default subject, message options, and design. Include `{new_content}` in the template body where digest items should appear. |
| Subject pattern | Optional replacement for the template subject. Supports `{date}` and `{site_name}`. If blank, the template subject is used. |

#### Content

Select one or more registered content types. For each source, optional **Category IDs** may contain comma-separated numeric Joomla category IDs, for example `1, 4, 12`. Leave it blank to include all categories from that content source.

Category filters are applied only where the registered content provides a category ID.

#### Schedule and generation

| Field | What it means |
| --- | --- |
| Next run | Earliest date/time at which the digest task should run this definition, displayed in Joomla's site timezone. |
| Recurrence minutes | Interval between due runs, minimum 15. Examples: 1440 for daily; 10080 for weekly. |
| Content cutoff: Since last | Uses the previous successful digest cutoff so the same item is not intentionally repeated. For the first run, it looks back one recurrence interval. |
| Content cutoff: Rolling | Looks back the configured number of hours on every run. This can intentionally repeat content when windows overlap. |
| Rolling hours | Lookback period used only by Rolling mode. Default: 168 hours (7 days). |
| Create draft | Safe default. Generates an editable newsletter and stops. |
| Create and send automatically | Generates and immediately creates the immutable send queue without administrator review. Selecting this mode requires the explicit confirmation checkbox when it is newly enabled. |
| Confirm automatic sending | Safety acknowledgement required when changing a digest to automatic sending. It is not a persistent “always checked” setting. |
| Empty digest: Skip | Default. Creates no newsletter, sends nothing, and records a no-content history result. |
| Empty digest: Create draft | Creates a draft even when no matching content remains. It stops at Draft even when the digest normally sends automatically, so an administrator must add/review content before sending. |

The recipient controls behave exactly like the newsletter editor: sources are combined, topic choices are a union, and all addresses are deduplicated and filtered by opt-out/suppression rules.

### Content access safety

Before generating a digest, Punga Mail resolves its current recipients and checks Joomla's access levels and applicable category access. It includes an item only when **every resolved recipient would normally be allowed to view that item on the website**.

This deliberately conservative shared-content rule prevents a digest from exposing restricted content to a broader audience. If a registered content type does not provide usable access metadata, its items are excluded from automatic digests instead of being assumed public. If recipients have mixed access, use separate digests/audiences for public and restricted content. Excluded items and no-content outcomes appear in the digest run details.

### Digest tasks and history

Create and enable **Punga Mail — Generate automatic digests** in Joomla Scheduled Tasks. A frequent task, such as every 5 or 15 minutes, is reasonable; each digest's own Next run and recurrence decide whether work is due.

An automatic-send digest also requires **Punga Mail — Process send queue**. A draft digest does not send until an administrator reviews its generated newsletter and queues or schedules it.

The editor's history table records run time, status, resulting newsletter link, content item count, and details such as access exclusions or errors.

Only enabled digests run. Editing a digest does not itself generate a newsletter.

## Delivery / Bounces

This page contains operational controls and health information. Bounce mailbox credentials themselves are stored under **Component Options → Bounce / return mailbox**.

### Bounce mailbox card

The card shows whether the mailbox is configured and the current server without revealing its password. **Open Component Options** goes to the settings. **Process bounces now** performs one mailbox run immediately.

The recent-bounces table shows timestamp, address, classification, SMTP/status code, diagnostic message, and whether the address is now suppressed.

Punga Mail processes standard delivery-status notifications where possible:

- hard failures suppress immediately;
- soft failures increase the subscriber's counts and suppress at the configured threshold;
- unknown failures are retained for inspection but do not automatically pretend to be permanent failures;
- duplicate delivery reports are ignored;
- bounce history is retained after suppression is cleared.

### Queue control

**Pause queue** is global. It leaves queued messages pending rather than failed. **Resume queue** allows later Scheduled Task/manual runs to continue.

This differs from opening one Queued/Sending newsletter and pausing only that mailing. It also differs from **Cancel remaining**, which permanently cancels its unsent rows.

### Mail test

Enter a recipient address and select **Send test mail**. Punga Mail sends a small diagnostic message using Joomla's configured transport and displays the real success or failure result.

This tests local configuration and transport acceptance only. It does not prove inbox placement, DNS authentication, or human delivery.

### Diagnostics

The page reports information that can be determined locally:

- Joomla mailer type;
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

Optionally select one or more topics. Multiple selected topics include members of any selected topic. The UTF-8 CSV contains email, name, numeric status, source, language value, topic aliases, suppression reason, bounce counts, and last-bounce details.

Treat exported files as personal data and store/share them accordingly.

## Frontend signup module

Create or edit a **Punga Mail Signup** site module under Joomla's module manager. Choose its position, menu assignment, publication status, and normal Joomla display options as you would for another module.

Punga Mail-specific options are:

| Option | What it does |
| --- | --- |
| Intro text | Optional plain text displayed above the form. Line breaks are preserved. |
| Button label | Optional replacement for the translated Subscribe label. |
| Topics | Controls which published topics this module offers. See the modes below. |

### Topic selection modes

| Module configuration | Visitor experience |
| --- | --- |
| No topics selected | Offers all currently published topics. |
| Exactly one topic selected | Hides the selector, names the topic, and performs the topic action directly. |
| Multiple topics selected | Shows only those published topics and allows one or more choices. |

For logged-out visitors, the module asks for an email address and uses double opt-in. Topic choices are activated only after confirmation.

For logged-in users, the module uses the Joomla account email and current Punga Mail record. Topic changes apply only to the topics visible in that module; memberships in topics not shown by that module remain unchanged.

Only the exactly-one-topic module configuration uses the direct single-topic action. With no configured topics, even one currently published topic remains an explicit visitor choice. Multi-choice forms use checkboxes and **Save topic preferences** to update only the visible topics.

The separate **Subscribe** / **Unsubscribe completely** control changes the global newsletter preference. Leaving one topic does not globally unsubscribe the address. A globally unsubscribed user may still see stored topic choices, but cannot receive mail until intentionally subscribed globally again.

## Joomla user-profile integration

The enabled Punga Mail user plugin adds a **Newsletter** fieldset to Joomla registration, frontend profile editing, administrator user editing, and the administrator's own profile.

| Field | What it does |
| --- | --- |
| Receive newsletter | **Yes** enables the user's global Punga Mail subscription. **No** globally unsubscribes the address and overrides every topic/Joomla-group selection. |
| Newsletter topics | Multi-select containing all currently published topics. Existing active memberships are preselected. Saving adds selected memberships and marks cleared published topics unsubscribed. |

The two controls are deliberately independent. Topic choices are retained when **Receive newsletter** is No, but no newsletter is delivered until the global preference is Yes again. This lets a user temporarily opt out completely without losing their preferred topic set.

Leaving all topics clear does not mean “receive nothing.” A globally subscribed user may still receive a newsletter targeted to **All confirmed subscribers** or an eligible Joomla user group. The selector controls only topic-targeted mailings.

Unpublished topics are not shown or changed by the profile form. Their stored history/membership is retained. Registered users therefore no longer require a published signup module solely to choose topics, although the module remains useful for inline account preferences and email-only signup.

## Newsletter subscription menu item

Create a menu item of type **Punga Mail → Newsletter subscription**.

The page gives visitors a stable Punga Mail subscription/status destination and acts as the Joomla SEF routing anchor for confirmation, unsubscribe, and browser-view routes. Keep it Published. It may be assigned to a hidden menu if it should not appear in site navigation.

Do not restrict the menu item to an access level that ordinary email recipients cannot use, or their confirmation and unsubscribe links may not reach the intended page.

## Scheduled Tasks

Go to **System → Scheduled Tasks**, select **New**, choose the Punga Mail task type, select a suitable execution rule, enable it, and save.

| Task | When it is needed | Suggested frequency |
| --- | --- | --- |
| Punga Mail — Process send queue | Always, for unattended real-newsletter delivery and automatic-send digests. | Every 1–5 minutes, adjusted for batch size and provider limits. |
| Punga Mail — Queue scheduled newsletters | When Send at a specific date/time is used. | Every 1–5 minutes. |
| Punga Mail — Generate automatic digests | When at least one digest is enabled. | Every 5–15 minutes. Digest definitions have their own due times. |
| Punga Mail — Process bounce mailbox | When bounce mailbox processing is configured. | Every 15–60 minutes, depending on volume. |
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

Confirm that **Punga Mail — Queue scheduled newsletters** exists, is enabled, and has run after the due time. Then confirm that **Punga Mail — Process send queue** is also enabled.

### A newsletter remains Queued

Check the Dashboard task card, global queue pause, per-mailing pause, task execution history, batch size, retry status, and Joomla mail configuration.

### A digest does not run

Confirm that the digest itself is enabled, its Next run is due, its template is available, at least one content type is selected, and the digest Scheduled Task is enabled. Check the digest history for no-content, access-exclusion, or failure details.

### Bounce processing fails

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

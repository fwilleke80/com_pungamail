# Tutorial: Set Up Delivery Health and Bounce Handling

This tutorial configures and verifies Punga Mail outgoing delivery, enables unattended queue processing, configures returned-mail processing, and explains the controls used when delivery must be paused or stopped.

## What the two mail directions do

Punga Mail uses two separate directions:

- **Outgoing mail** uses either Joomla's global mail settings or an optional Punga Mail-specific Custom SMTP account.
- **Returned mail** is read from a dedicated IMAP mailbox when bounce processing is enabled.

The return mailbox is independent of the outgoing transport. SMTP credentials do not configure IMAP automatically, even when both use the same mailbox address.

## 1. Configure and verify outgoing mail

Open **Components → Punga Mail → Options → Mail**.

Set the Punga Mail **From name** and **From email** if they should differ from Joomla's global sender. Configure Reply-To separately if reader replies should go to another address.

Choose the outgoing transport:

- **Use Joomla settings** — backward-compatible default. Punga Mail uses Joomla's globally configured SMTP/sendmail transport.
- **Custom SMTP** — Punga Mail uses its own SMTP host, port, security, authentication, username and encrypted password. Joomla system messages keep using Joomla's global mail configuration.

For Custom SMTP, use the exact submission settings supplied by the provider. Common combinations are port 587 with STARTTLS or port 465 with SSL/TLS, but the provider's documentation is authoritative. Use **Save outgoing mail settings** in this section to store the SMTP connection and encrypted password. The normal Joomla Options **Save** button stores ordinary Punga Mail fields such as From name/address and Reply-To.

Use the outgoing section's **Send test mail** to test the currently entered connection values. Leaving Password blank reuses the already stored password. Then open **Punga Mail → Delivery / Bounces** and inspect Diagnostics:

- outgoing transport source (Joomla settings or Custom SMTP);
- active mailer and Custom SMTP host where applicable;
- sender and validity indicator;
- batch size and retry settings.

The Delivery-page **Mail test** tests the currently saved active transport. A success means the mail transport accepted the test; confirm actual arrival separately.

If it fails, correct the active outgoing transport and sender configuration. Punga Mail intentionally uses Joomla's mailer API for both modes; Custom SMTP is an isolated configuration, not a second unrelated mail library.

## 2. Create the queue task

Go to **System → Scheduled Tasks**:

1. select **New**;
2. choose **Punga Mail — Send pending newsletters**;
3. set a frequent schedule, commonly every 1–5 minutes;
4. enable and save it.

The task sends at most the configured batch size per run and observes retry limits/intervals. Its frequency and batch size together determine throughput.

Example: a batch size of 25 with one run every five minutes can process roughly 300 recipients per hour when sends are successful. Mail-provider limits may require a lower effective rate.

## 3. Review queue settings

Under **Options → Send queue**:

- keep **Queue paused** at No for normal operation;
- set **Batch size** within provider limits;
- retain a sensible **Maximum attempts**;
- allow enough **Retry interval** for temporary problems to clear.

These controls already govern normal sending. Digest and scheduled newsletters use the same queue rather than bypassing them.

## 4. Create a dedicated return mailbox

Create an address intended only for bounced mail, such as `bounces@example.org`. Obtain from its provider:

- IMAP server;
- port;
- SSL/TLS requirement;
- mailbox/folder, normally `INBOX`;
- username;
- password.

The PHP IMAP extension must be installed on the Joomla server. Check **Delivery / Bounces → Diagnostics → PHP IMAP**.

Using a dedicated mailbox matters because Punga Mail examines unseen mail and marks handled/unparseable messages as seen.

## 5. Save and test the mailbox

Go to **Options → Bounce / return mailbox** and enter the values.

Keep **Validate certificate** enabled unless you are diagnosing a controlled internal server with a known certificate issue. Disabling verification reduces connection security.

Enter the **Bounce address** if the active outgoing mail transport/provider supports setting the SMTP envelope sender. This is the address returned delivery reports should reach. An ordinary visible message header alone cannot force upstream return routing, so verify the behavior with your provider.

Select **Save mailbox settings** inside the bounce section. The normal Joomla Options Save action deliberately does not store this password.

Select **Test connection**. It opens the mailbox read-only and reports whether access succeeds.

When editing later:

- the stored password is never shown;
- leave Password blank to retain it;
- enter a new value only to replace it.

## 6. Choose the temporary-failure threshold

**Temporary failures before blocking address** defaults to 3. The distinction is important:

- One **permanent/hard bounce** suppresses delivery immediately. The temporary threshold does not apply.
- Each **temporary/soft bounce** increases the active temporary-failure counter.
- Reaching the configured threshold suppresses delivery. Until then, the subscriber remains deliverable and the UI shows progress such as **Temporary failure — 1 of 3 before delivery is stopped**.
- Unknown/unclassified reports remain in history but are not treated as a permanent failure without evidence.

For example, an SMTP/DSN error such as an unknown mailbox or unresolvable recipient domain is normally permanent and is blocked after one confirmed hard bounce. A temporary mailbox-full or transient-server condition follows the threshold. The threshold protects list quality without blocking an address after one temporary problem.

## 7. Create the bounce task

Under Joomla Scheduled Tasks:

1. select **New**;
2. choose **Punga Mail — Check returned mail**;
3. run it every 15–60 minutes depending on volume;
4. enable and save it.

The Punga Mail Dashboard warns when a mailbox is configured but this task is unavailable or disabled.

Use **Process bounces now** on Delivery / Bounces for a manual test. The task reads unseen delivery-status messages, attempts to associate them with a queue/newsletter/subscriber, records history, and applies suppression rules.

## 8. Inspect results

The recent-bounces table shows:

- date/time reported;
- affected address;
- permanent, temporary, or unknown classification;
- SMTP/status code when present;
- diagnostic reason;
- whether delivery is blocked, including immediate permanent-failure wording or temporary-failure threshold progress.

Open **Audience → Subscribers** to see **Subscription** and **Delivery** separately. A subscriber can remain **Subscribed** while **Delivery blocked — permanent failure**; Punga Mail preserves the subscriber record and only suppresses sending.

Historical newsletter statistics may show a message as accepted by transport and later bounced. This is expected: initial acceptance is not proof of final delivery.

## 9. Correct and clear a bounced address

If a subscriber confirms that an address was corrected or the mailbox is valid again:

1. find the subscriber in **Subscribers**;
2. review the last classification and reason;
3. select **Clear bounce suppression**;
4. confirm the warning.

This removes the bounce-based delivery block and resets the active soft-bounce condition, while retaining bounce history for accountability.

Do not clear suppression merely to increase recipient count. Repeatedly sending to invalid addresses harms delivery reputation.

## 10. Inspect and manage the live mail queue

Open **Delivery / Bounces** and use the **Mail queue** table to inspect individual recipient rows. Filter by queue state, Newsletter, or recipient/title search. The table includes attempts, retry timing, timestamps, and the latest error.

Use **Retry selected** only when you intentionally want failed rows to enter the normal retry path again. **Cancel selected** affects only pending or failed unsent rows; it cannot recall mail already accepted by the transport or interrupt rows currently processing.

## 11. Pause and resume safely

### Pause every mailing

Use **Delivery / Bounces → Pause queue** or **Options → Queue paused: Yes**. Workers leave pending mail queued; they do not mark it failed.

Use **Resume queue** when the underlying problem is resolved.

### Pause one mailing

Open a Queued or Sending newsletter and choose **Pause mailing**. Other newsletters may continue.

### Cancel an unsent remainder

Open the mailing and choose **Cancel remaining**, then confirm. Pending/processing recipient rows become cancelled. Already accepted messages cannot be recalled.

Use cancellation only when the remaining delivery should never resume. A pause is the reversible option.

## 12. Read delivery statistics accurately

| Statistic | Interpretation |
| --- | --- |
| Intended | Candidates before final exclusion. |
| Queued | Frozen unique recipient rows. |
| Transport accepted | Joomla's mail transport accepted them. Not proof of inbox delivery/readership. |
| Temporary failures | Attempted rows still eligible for retry. |
| Permanent failures | Queue failures after retry/non-recoverable processing. |
| Hard/soft bounces | Later returned-mail classifications attributed to this mailing. |
| Suppressed | Candidates excluded during queue creation because of suppression rules. |
| Unsubscribes | Unsubscribe events attributable to this newsletter where the link identifies it. |
| Remaining | Pending or currently processing rows. |
| Cancelled | Rows stopped before successful transport acceptance. |

Punga Mail intentionally does not equate these operational facts with opens or readership.

## 13. DNS and provider checks

The local Diagnostics section cannot reliably prove every external mail-authentication condition. Separately verify with your domain/mail provider:

- SPF permits the real sending service;
- DKIM signing is active and aligned;
- DMARC policy/reporting is appropriate;
- the From domain and envelope/return setup are allowed;
- provider rate and recipient limits match queue settings.

Punga Mail does not modify DNS automatically.

## Routine health checklist

- Dashboard shows all needed tasks enabled.
- Queue is not unexpectedly paused.
- Scheduled Task execution history is successful.
- Mail test works after transport changes.
- Bounce connection works after mailbox/password changes.
- Recent hard/soft bounces are plausible, not a parser/provider anomaly.
- Suppressed addresses remain excluded in Preflight.
- Batch size/retry values match current provider limits.
- Automatic newsletters use the normal sending system and are not empty by default.

For all settings and statuses, see [Punga Mail Administrator Guide](USER_GUIDE.md).


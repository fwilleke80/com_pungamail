# Tutorial: Create and Send a Newsletter

This tutorial takes you from an empty draft to a reviewed, queued, and monitored newsletter. It assumes Punga Mail is installed and Joomla can already send mail.

## Before you begin

Confirm these items once:

- **Punga Mail — Process send queue** exists and is enabled under Joomla Scheduled Tasks.
- A Published **Punga Mail → Newsletter subscription** menu item exists, even if it is in a hidden menu.
- The sender under **Punga Mail → Options → Mail** is correct, or Joomla's global sender is correct.
- At least one eligible subscriber or Joomla user group exists.

If you plan to schedule instead of queue immediately, also enable **Punga Mail — Queue scheduled newsletters**.

## 1. Decide who should receive the newsletter

Before composing, decide whether this is for:

- every confirmed subscriber;
- one or more Punga Mail topics;
- one or more Joomla user groups;
- a combination of those sources.

Recipient sources are added together and duplicate addresses are removed. They are not an intersection.

For example, to send only to News and Events topic members:

1. clear **All confirmed subscribers**;
2. select **News** and **Events**;
3. leave Joomla groups clear unless their eligible members should also be added.

If **All confirmed subscribers** remains selected, choosing News does not restrict the mailing to News members.

## 2. Create the draft

Go to **Components → Punga Mail → Newsletters** and select **New**.

Enter:

- an **Internal title**, such as `September 2026 community update`;
- an **Email subject**, such as `What is new this September`;
- the body in **Markdown**.

The internal title is only for administrators. The email subject is what recipients see in their inbox.

A simple body might be:

```markdown
Hello {recipient},

Here are this month's highlights.

{new_content}

Thank you for reading.
```

`{recipient}` becomes the Joomla display name when one is available, otherwise the email address. `{new_content}` is replaced by the website content you select in the next step.

You can omit either placeholder. Punga Mail never appends selected content automatically, so include `{new_content}` wherever it should appear.

Select **Save** before moving on.

## 3. Apply a template, if wanted

If a reusable template already exists:

1. choose it in **Template**;
2. select **Apply template**;
3. review the copied subject, body, message options, and style fields.

Applying a template copies its values into this draft. The newsletter is not linked live to the template. Future template changes will not alter the draft.

Applying a template may replace work already entered in those fields, so save or copy anything you may need first.

## 4. Select website content

In **New content since …**:

1. set **Content published since**;
2. select the registered content types to search;
3. select **Apply filters**;
4. use Search to narrow the displayed titles if necessary;
5. check the items to include.

For each selected item you may set:

- **Ordering**: lower numbers appear first;
- **Title override**: changes the title only in this newsletter;
- **Excerpt override**: changes the excerpt only in this newsletter.

These overrides never edit the original website content.

The picker can include any suitable content type registered with Joomla, not only ordinary articles. If an expected type is absent, confirm that its extension has registered enough content metadata for Joomla to expose it.

## 5. Check message options

In **Message options**, choose whether this newsletter should inherit its template/global settings or override them.

### Body heading

- **Inherit** uses the template and then global setting.
- **Custom** uses **Custom heading**.
- **Use site name** deliberately displays Joomla's site name.
- **No heading** renders no first heading.

This heading appears inside the message. It is different from the inbox subject and internal title.

### Browser view

Enable this if the email should contain “View this newsletter in your browser.” The public version will be created from the frozen sent snapshot, not the editable draft.

### Reply-To

Use **Custom** if replies should go somewhere other than the inherited setting. Enter a valid address. Choose **None** if this newsletter deliberately should not have a Reply-To address.

## 6. Adjust the design, if necessary

Leave a style field blank to inherit from the template or Component Options. Override only the properties this newsletter needs.

The available properties cover width, backgrounds, text/heading/link colors, font, base size, padding, logo, and footer color. Custom CSS is optional and should be treated as an enhancement because mail clients vary.

## 7. Select recipients

In **Recipients**:

1. set **All confirmed subscribers** appropriately;
2. select any topics;
3. select any additional Joomla user groups.

Global unsubscribes, suppression, invalid addresses, and bounce rules always win. Joomla-group members without an explicit Punga Mail preference are included only if **New Joomla users subscribed by default** is enabled under Component Options.

Select **Save**.

## 8. Preview the output

Select **Preview**.

Check:

- heading and logo;
- subject and body structure;
- the position/order of new content;
- links and images;
- footer and unsubscribe wording;
- plain-text readability;
- the example `{recipient}` value.

Preview uses the current administrator as its example recipient. Its unsubscribe link is disabled on purpose.

Return to the editor and correct anything unexpected.

## 9. Send a test message

Select **Send test mail** in the editor and provide the test recipient if prompted.

Open the result in at least one real mail client. Check narrow/mobile display, image loading, links, plain-text alternative, sender, Reply-To, and spam-folder placement.

A successful test means Joomla's transport accepted the message; it does not guarantee that every provider will place the final mailing in an inbox.

## 10. Run Preflight

Select **Check recipients & send**.

Preflight does not send or change subscriptions. Review:

- validation checks;
- final unique recipient count;
- included recipients and their source;
- excluded recipients and reasons;
- sender and content count;
- final HTML and plain text.

Correct every red error. Review yellow warnings rather than dismissing them automatically.

Pay particular attention to:

- **Not in selected topic**: expected when topic targeting is in use;
- **Unsubscribed/not subscribed**: global preference excludes the address;
- **Suppressed/hard bounced/soft-bounce threshold**: delivery protection excludes it;
- **Duplicate eliminated**: the same address came from more than one source but will receive only one copy;
- **Content access**: a selected item cannot be viewed on the site by every recipient. This blocks sending to prevent disclosure.

## 11A. Send now

Select **Queue emails** and confirm the displayed unique-recipient count.

Punga Mail freezes the final message and audience, then adds recipient rows to the queue. The queue task sends them in configured batches.

## 11B. Schedule delivery

Instead of Queue emails:

1. enter the desired date/time on Preflight;
2. note the displayed Joomla site timezone;
3. select **Schedule send**.

The newsletter becomes Scheduled and remains editable until the scheduled task freezes it. If you edit it, run Preflight again so you understand the current result.

Use **Cancel schedule** in the editor to return it to Draft before queueing begins.

## 12. Monitor delivery

Open the newsletter from the Newsletters list.

While it is Queued or Sending, you can:

- inspect frozen recipient rows and attempts;
- pause/resume this mailing;
- cancel its unsent remainder.

After completion, review the operational statistics. “Transport accepted” is the accurate term for messages Joomla handed successfully to the mail system. Later bounce processing may move an accepted recipient into a bounced state.

If you want to reuse the content, choose **Duplicate as draft**. The original sent snapshot remains unchanged.

## A reliable final checklist

Before confirming a real queue:

- subject is correct and specific;
- From and Reply-To are correct;
- body heading is intentional;
- `{new_content}` is present if items were selected;
- content ordering/excerpts are correct;
- every selected item is appropriate for the audience's website access;
- browser-view choice is correct;
- HTML and plain text both read well;
- test message arrived and links worked;
- final recipient count and exclusions make sense;
- send-now or scheduled time is intentional.

For field-by-field reference, see [Punga Mail Administrator Guide](USER_GUIDE.md).


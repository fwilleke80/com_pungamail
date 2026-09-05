# Tutorial: Create an Automatic Newsletter

This tutorial creates a recurring newsletter from new Joomla content. Begin with **Create draft** mode; switch to automatic sending only after reviewing several successful runs.

## What an automatic newsletter does

A digest definition periodically:

1. finds matching website content;
2. resolves its intended recipients;
3. removes content that any recipient could not normally view on the website;
4. creates a newsletter from a template;
5. either leaves it as a Draft or places it into the normal send queue;
6. records the outcome in digest history.

The recurring Joomla task wakes Punga Mail up. Each Automatic Newsletter’s own **Next run** and **Repeat every** settings determine whether it is actually due.

You can also control the generated content set: choose newest-first or oldest-first ordering, cap the maximum number of included items, and optionally require a minimum number of eligible items before generation. If a **Since last** run misses that minimum, Punga Mail skips generation without advancing the rolling cutoff, so content can accumulate for the next run.

## 1. Prepare the template

Go to **Components → Punga Mail → Templates** and create a digest template.

Its Markdown body must contain `{new_content}` where the selected items should appear. For example:

```markdown
Hello {recipient},

Here is this week's new content:

{new_content}

See you next week.
```

Set the default subject, heading, browser-view, Reply-To, and design options. Save & Close the template.

Preview it, keeping in mind that no real digest items are selected in a standalone template preview.

## 2. Prepare the audience

Create and publish any Punga Mail Channels under **Channels**. Confirm that subscribers have chosen them through the module, or import the memberships.

Decide whether the digest should target:

- every confirmed subscriber;
- members of selected Channels;
- selected Joomla groups;
- the union of multiple sources.

For a Channel-only Automatic Newsletter, plan to clear **All globally subscribed recipients, regardless of topic** in the digest editor.

## 3. Create the digest

Go to **Components → Punga Mail → Automatic Newsletters** and select **New**.

Under **Basics**:

1. enter a clear Title such as `Weekly public news digest`;
2. select the prepared Template;
3. optionally enter a Subject pattern.

Subject patterns support:

- `{date}` — generation date in `YYYY-MM-DD` form;
- `{site_name}` — Joomla site name.

For example:

```text
{site_name}: weekly update for {date}
```

If the field is empty, the template subject is used.

## 4. Select content sources

Under **Digest content**, check one or more registered Joomla content types. Punga Mail displays the translated component label when Joomla provides one for the current administrator language.

To limit one source to categories, enter comma-separated numeric category IDs beside that source, for example:

```text
4, 12, 19
```

Leave the field blank for all categories from that source. Category filtering is available only where the registered content exposes a category ID.

The maximum matching set is intentionally generous; the access and cutoff rules are applied before the newsletter is generated.

## 5. Choose a cutoff rule

### Since last

Use **Since last** for a normal “what is new” digest. After a successful run, the next run starts from the preceding successful cutoff. This prevents routine repetition.

On the first run, Punga Mail looks back one recurrence interval. If a weekly Automatic Newsletter’s first run is scheduled for Friday, it normally finds the previous week’s matching content. If you instead choose **Content from a recent time period**, the separate **Look back … days** field appears and controls that fixed window.

### Rolling

Use **Content from a recent time period** when each Automatic Newsletter should always cover a fixed recent window, for example “the last 2 days.” The **Look back … days** field appears only in this mode.

Overlapping rolling windows can include the same item more than once. Choose this only when repetition is intentional.

## 6. Set the schedule

Enter:

- **Next run** in the Joomla site timezone shown on the form;
- **Repeat every** with a positive number and one of **days**, **weeks**, or **months**.

Useful intervals include:

| Schedule | Setting |
| --- | --- |
| Daily | 1 day |
| Weekly | 1 week |
| Every two weeks | 2 weeks |
| Monthly | 1 month |

Month intervals use calendar-month arithmetic. A newsletter anchored near the end of a month uses the last valid day in a shorter month and returns to its intended day when possible, rather than drifting by a fixed 30-day interval.

The task’s own frequency should be shorter than the Automatic Newsletter recurrence. Running the task every 5 or 15 minutes is normally enough.

## 7. Keep Create draft selected

Choose **Create draft** for initial testing. When due, Punga Mail creates a normal editable newsletter and records it in digest history, but sends nothing.

For **Empty digest**, choose **Skip**. This records “no content” and creates no empty newsletter.

The alternative **Create draft** creates a draft even with no matching content. It is useful only when an administrator intends to add manual content. An empty run stops at Draft even if the digest is otherwise configured for automatic sending.

## 8. Select recipients

Use the same audience rules as a normal newsletter:

- select **All globally subscribed recipients, regardless of topic** only for a site-wide digest;
- clear it for topic-only targeting;
- select one or more Channels as an either/or group;
- select Joomla groups only when their eligible members should be added.

Addresses are deduplicated. Unsubscribed, invalid, and suppressed addresses are excluded.

## 9. Understand the access-permission rule

Punga Mail does not assume that email bypasses website access rules.

For every possible digest item, it checks the resolved recipients' Joomla access levels and applicable category access. An item is included only if **every recipient would normally be allowed to view it on the website**.

This is intentionally conservative. Consider an audience containing public visitors and registered members:

- Public article: may be included.
- Registered-only article: excluded, because part of the audience cannot view it.

If you need both, create two digest definitions with appropriately separated audiences. For example:

- Public News digest → public topic → public content;
- Member News digest → registered Joomla group → member content.

Access exclusions are recorded in the run details. If all matching items are excluded and Empty digest is Skip, the run is recorded as no content and sends nothing.

## 10. Save and enable the digest

Select **Save & Close**. On the Digests list, select the digest and choose **Enable** if it is not already enabled.

Only enabled definitions are processed. Saving a digest does not run it immediately.

## 11. Create the Scheduled Task

Go to **System → Scheduled Tasks**:

1. select **New**;
2. choose **Punga Mail — Create automatic newsletters**;
3. configure it to run every 5–15 minutes;
4. enable and save it.

The Punga Mail Dashboard warns when an enabled digest exists without this task.

Draft-mode digests need only this task. Automatic-send digests also need **Punga Mail — Send pending newsletters**.

## 12. Review draft runs

After the first due time:

1. open the digest and inspect **Digest history**;
2. open the linked generated newsletter;
3. check its content, recipients, heading, subject, and design;
4. run Preview, test mail, and Preflight;
5. queue or schedule it manually if correct.

Repeat this for several cycles. Pay attention to cutoff boundaries, category IDs, content ordering, access exclusions, empty runs, and the actual audience.

## 13. Switch to automatic sending, if appropriate

Open the digest and change **Digest mode** to **Create and send automatically**.

Read the warning and select **Confirm automatic sending**. The acknowledgement is required when newly enabling auto-send so that ordinary editing cannot accidentally activate it.

Save the digest. On future due runs, Punga Mail creates the newsletter and immutable queue immediately. The normal queue task then performs delivery.

Before switching, confirm:

- the template is production-ready;
- `{new_content}` is placed correctly;
- the subject pattern works across dates;
- Since last/Rolling behavior is intentional;
- Empty digest is Skip;
- recipient targeting is narrowly correct;
- public/restricted content audiences are separated;
- the digest and send-queue tasks are both healthy;
- bounce processing and suppression are operating.

## 14. Read digest history

Each run records:

- when it started;
- result such as draft, queued, no content, or failed;
- resulting newsletter ID/link;
- included content count;
- details such as inaccessible items or failure messages.

The generated newsletter has its own delivery status and statistics. Digest history answers “what did automation generate?” while the newsletter detail answers “what happened during delivery?”

## Changing or stopping a digest

- **Disable** pauses future generation without deleting the definition.
- Change **Next run** to move the next due time.
- Change recurrence or cutoff carefully; Rolling can repeat recent content.
- **Trash** removes the definition from normal lists but preserves it for restoration.
- Use permanent Delete only from the Trashed filter and only when its history/relationships allow it.

For every field and task, see [Punga Mail Administrator Guide](USER_GUIDE.md).


## Content amount and order

In **Choose content** you can refine what each run generates:

- **Order** — newest first or oldest first.
- **Maximum items** — `0` means no limit; otherwise only that many eligible items are included.
- **Minimum items** — `0` disables only this threshold; the separate **If no new content is found** setting still controls a genuinely empty run. Otherwise Punga Mail generates nothing until at least that many eligible items are available.

The minimum threshold is evaluated before the maximum limit. For a rolling **Since last** Automatic Newsletter, a threshold miss does not advance the content cutoff. For example, a weekly digest requiring at least five items can see three items one week, skip, then include those three together with later items on the following run. The run history records the skip explicitly.

## Schedule time and timezone

The **Next run** control includes both a calendar and a 24-hour time picker. Enter the date and time as they should occur in the Joomla site timezone shown below the field. Punga Mail converts that value to UTC for storage and task processing, then converts it back to the configured Joomla timezone wherever the schedule is shown in the editor, Automatic Newsletters list and Dashboard.

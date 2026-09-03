# Tutorial: Import and Export Subscribers

This tutorial prepares a safe CSV, previews mappings, protects unsubscribed/suppressed addresses, adds topic memberships, and exports useful subscriber subsets.

## Before importing

Import only addresses you are entitled to contact. A technically valid CSV is not proof of consent.

Create and Publish the intended topics before importing memberships. Punga Mail matches topic cells only against currently published topic titles and aliases.

Export a current backup before a large update.

## 1. Prepare the CSV

Use UTF-8 and include a header row. Comma, semicolon, and tab separators are detected automatically.

A useful example is:

```csv
email,name,status,topics
alex@example.org,Alex Example,active,news|events
bea@example.org,Bea Example,unsubscribed,news
chris@example.org,Chris Example,,development-updates
```

The file may use different column names because you map them during preview.

Limits:

- maximum file size: 5 MiB;
- maximum processed data rows: 20,000;
- one row per email address is recommended.

## 2. Understand accepted values

### Email

Required. Malformed addresses are reported as invalid. Addresses are normalized for duplicate detection.

### Name

Optional. Used as the email-only subscriber display name and for `{recipient}` personalization where available.

### Status

Optional active values:

- `active`
- `subscribed`
- `1`
- `yes`
- `ja`

Optional unsubscribed values:

- `unsubscribed`
- `inactive`
- `2`
- `no`
- `nein`

A blank or unrecognized value does not explicitly ask Punga Mail to activate/unsubscribe the row.

### Topics

Optional. Use published topic title(s) or alias(es), separated with a vertical bar, semicolon, or comma:

```text
news|events
```

Matched memberships are added. Existing memberships not named by the row are not removed.

Aliases are better for repeatable imports because administrators may later change a public title.

## 3. Upload and preview

Go to **Components → Punga Mail → Import / Export**.

Choose the CSV and select **Preview import**. Punga Mail shows the first 20 rows and the total detected row count.

Map:

- Email — required;
- Name — optional;
- Status — optional;
- Topics — optional.

If a header is literally `email`, `name`, `status`, or `topics`, Punga Mail preselects it. Always verify the mapping before commit.

No subscriber changes occur during preview.

## 4. Decide about protected addresses

Punga Mail protects addresses that are:

- globally unsubscribed;
- suppressed;
- marked with a hard-bounce history.

Merely appearing in a CSV never silently reactivates them.

When a protected row explicitly says Active:

- with **Explicitly reactivate protected addresses** clear, it is reported as a conflict and skipped;
- with the checkbox selected, Punga Mail performs the intentional administrator reactivation.

Leave the checkbox clear for ordinary imports.

Select it only when all of these are true:

- the row explicitly requests an active status;
- consent has been verified;
- any bad address was corrected;
- the suppression/reactivation is genuinely intended.

Do not use it as a bulk “make everyone active” shortcut.

An imported Unsubscribed value is honored and globally opts the record out.

## 5. Commit and read the result

Select **Commit import**.

The result reports:

| Result | Meaning |
| --- | --- |
| Added | New canonical subscribers created. |
| Updated | Existing rows processed, including names/topics/state changes. |
| Unchanged | Valid rows for which no effective update was needed in an otherwise no-change import. |
| Skipped | Repeated email address within this uploaded file after its first occurrence. |
| Invalid | Email did not pass validation. |
| Conflicts | Protected unsubscribe/suppression/bounce state prevented activation. |
| Errors | A row raised another processing error. |

Review Subscribers afterward. Filter by Active, Unsubscribed, or Suppressed and search spot-check addresses from each result category.

## 6. Verify topic targeting

Create a Draft newsletter for one imported topic:

1. clear **All globally subscribed recipients, regardless of topic**;
2. select only the imported topic;
3. open Preflight;
4. inspect final and excluded recipients.

This confirms both the membership mapping and global protection state without sending or changing subscriptions.

## 7. Export subscribers

On Import / Export, choose a scope:

- **All**;
- **Active**;
- **Unsubscribed**;
- **Suppressed**.

Optionally select one or more topics. If several are selected, the export includes subscribers active in any selected topic.

Select **Download CSV**.

The UTF-8 export contains:

- email;
- name;
- numeric subscription status;
- source;
- stored language value;
- topic aliases joined with `|`;
- suppression reason;
- total and soft-bounce counts;
- last bounce date, classification, and reason.

## Common use cases

### Add new subscribers to topics

- map Email, Name, and Topics;
- omit Status or explicitly use Active only when consent is known;
- leave protected-address reactivation clear.

### Add topics to existing subscribers

- map Email and Topics;
- use aliases in the topic cells;
- remember that this merges memberships and does not remove others.

### Globally unsubscribe a supplied list

- map Email and Status;
- put `unsubscribed` in every intended row;
- preview carefully, then commit.

### Audit delivery problems

- export Suppressed;
- optionally restrict to a topic;
- inspect suppression reason and bounce columns.

### Move data to another system

- export All;
- protect the file as personal data;
- note that numeric statuses and suppression fields should retain their consent meaning in the destination.

## Data-handling checklist

- Keep an export backup before a large commit.
- Use UTF-8 and a clear header row.
- Deduplicate input intentionally rather than relying on skips.
- Create/publish topics before import.
- Use topic aliases for repeat processes.
- Never enable protected-address reactivation by default.
- Review conflicts instead of trying to defeat them.
- Delete temporary personal-data files according to your organization's retention rules.

For all subscriber states and controls, see [Punga Mail Administrator Guide](USER_GUIDE.md).

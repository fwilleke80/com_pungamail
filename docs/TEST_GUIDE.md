# Punga Mail 0.6.9 Live Acceptance Test Guide

This guide is for a Joomla administrator testing the current Punga Mail release on a real installation. It is an end-to-end acceptance and regression checklist covering installation, administration, subscriptions, Channels, content layouts, newsletter authoring, automation, delivery, returned mail, import/export, permissions, and frontend flows.

Use a staging clone first whenever possible. Several tests intentionally send mail, alter subscription state, create queue records, or suppress an address.

## How to record a test

For every test, record:

- result: **Pass**, **Fail**, **Blocked**, or **Not applicable**;
- date, tester, Joomla version, PHP version, database type/version, and Punga Mail version;
- the relevant account, Channel, newsletter, Automatic Newsletter, template, or content type;
- screenshots for unexpected UI behavior;
- the exact error message and relevant Joomla/PHP log entry for failures;
- whether cleanup was completed.

A test passes only when all expected results are true. “No error page appeared” is not enough.

## Safe test setup

1. Make a current database and file backup and verify how to restore it.
2. Prefer a staging clone with the same Joomla, PHP, database, mail, cron, SEF, and timezone configuration as production.
3. Open **Components → Punga Mail → Options → Send queue**, set **Pause queue processing** to **Yes**, and save.
4. Temporarily disable Punga Mail Scheduled Tasks until their dedicated tests.
5. Create a published Channel named `PM Test <date>` and use it for controlled recipient tests.
6. Use only mailboxes controlled by the tester. Do not use invented addresses at somebody else’s domain.
7. Create these Joomla users with controlled addresses:
   - **Test Registered** — ordinary Registered user;
   - **Test Special** — member of a group allowed to view a Special/custom-access test item;
   - **Test Manager** — administrator allowed to manage Punga Mail but not Joomla Super User;
   - **Test Restricted** — administrator without Punga Mail management permission.
8. Prepare one controlled external subscriber address that is not attached to a Joomla user.
9. Create harmless content with distinctive titles:
   - a Public item;
   - a Registered item;
   - a Special/custom-access item visible only to Test Special;
   - an unpublished item;
   - at least one item from a non-core extension that correctly registers a Joomla content type, if available.
10. For the non-core content type, choose one whose source table contains a useful type-specific field such as an event start date, venue, price, author, or similar value. This is used to test central Content Layouts.
11. Create a published menu item of type **Punga Mail → Newsletter subscription**. It may live in a hidden menu but must remain accessible to recipients.
12. Publish a **Punga Mail Signup** module on a test page.
13. If returned-mail testing is planned, use a dedicated mailbox and a controlled mail system that can produce known permanent and temporary delivery-status notifications.
14. Record the Joomla site timezone from **System → Global Configuration**. Scheduled times in this guide refer to the site timezone shown in the administrator UI.

## Recommended execution order

Run the sections in this order:

1. installation, update, navigation, Dashboard, and Options;
2. Audience: Channels, Subscribers, frontend signup, and Joomla profile;
3. Design: Templates, Content Layouts, and rendering;
4. Newsletter authoring, preview, Preflight, duplication, and scheduling;
5. Automatic Newsletters;
6. Delivery queue, Custom SMTP, browser view, and statistics;
7. import/export and Scheduled Tasks;
8. returned-mail processing last, because it intentionally changes delivery state;
9. ACL, privacy, language, responsive UI, and end-to-end regressions.

Keep the global queue paused except where a test explicitly says to resume it.

---

## A. Installation, update, navigation, and dashboard

### PM-001 — Fresh package installation

**Prerequisite:** Clean Joomla test installation and the current Punga Mail package ZIP.

**Steps:**

1. Open **System → Install → Extensions**.
2. Upload the Punga Mail package.
3. Wait for Joomla’s success message.
4. Open **System → Manage → Extensions** and search for `Punga Mail`.
5. Open **Components → Punga Mail**.

**Expected:** The package installs without SQL/prepared-statement errors. The component, signup module, user plugin, and task plugin are installed. Required plugins are enabled. The Dashboard opens without an unknown-column or migration error.

### PM-002 — Update from an older installation

**Prerequisite:** Restored copy of an older Punga Mail installation containing representative subscribers, Channels, templates, newsletters, sent snapshots, and if available Automatic Newsletters.

**Steps:**

1. Record representative IDs and take screenshots of important records.
2. Install the current package as an update.
3. Open **System → Maintenance → Database** and use Joomla’s database check/repair if offered.
4. Open every Punga Mail top-level section.
5. Reopen the recorded records and a historic sent newsletter.
6. If upgrading from before 0.6.0, open **Design → Content layouts** and inspect the migrated Default layout.

**Expected:** Update completes without SQL errors. Existing data and IDs remain intact. Historic sent snapshots are unchanged. The former global selected-content layout is migrated to the central Default Content Layout. Legacy per-newsletter/template layout data, if present, is preserved rather than silently destroyed.

### PM-003 — Reinstall the same package safely

**Steps:**

1. Back up the database.
2. Install the same package version over itself.
3. Reopen the Dashboard and one record from each main area.

**Expected:** Reinstall succeeds without duplicating or deleting data and without migration/unknown-column warnings.

### PM-004 — Uninstall data policy

**Prerequisite:** Disposable clone only.

**Steps:**

1. Leave **Options → Maintenance / Data → Uninstall: Remove database tables** set to **No**.
2. Uninstall Punga Mail and reinstall it.
3. Confirm that existing Punga Mail data remains.
4. Restore the disposable clone, set the option to **Yes**, save, and uninstall again.

**Expected:** Default uninstall preserves data. Explicit destructive uninstall removes Punga Mail tables. The destructive option is clearly labelled and never defaults to Yes.

### PM-005 — Current administrator navigation

**Steps:**

1. Open **Components → Punga Mail**.
2. Verify the sidebar contains: **Dashboard**, **Newsletters**, **Automatic Newsletters**, **Audience**, **Design**, **Delivery**, and **Tools**.
3. Open **Audience** and switch between **Subscribers** and **Channels**.
4. Open **Design** and switch between **Templates** and **Content layouts**.
5. Open **Tools** and verify Import / Export is available.
6. Open an editor from Audience and Design, then Save & Close or Cancel.

**Expected:** No old child section appears as an unnecessary first-level sidebar item. Audience/Design tabs work, their parent sidebar entry remains active, and editor actions return to the correct grouped section rather than Dashboard. No untranslated key, PHP warning, 404, or collapsed/wrong sidebar state appears.

### PM-006 — Options toolbar consistency

**Steps:**

1. Open Dashboard, Newsletters, Automatic Newsletters, Audience, Design, Delivery, and Tools.
2. Inspect the Joomla toolbar on each main section.

**Expected:** Main backend sections provide a consistent **Options** action where component options are applicable. Clicking it opens **Punga Mail: Options**.

### PM-007 — Dashboard cards, quick actions, and links

**Steps:**

1. Note the Dashboard counts and compare them with the corresponding filtered lists.
2. Verify Quick Actions include at least New Newsletter, New Automatic Newsletter, subscriber creation, Channels, and **Templates**.
3. Follow each Quick Action.
4. If queue items exist, use **Process queue now** while global queue processing is paused.

**Expected:** Counts agree with the underlying lists. Quick Actions open the intended page. Templates appears next to Channels. Manual queue processing while globally paused reports the paused state without falsely failing queued recipients.

### PM-008 — Upcoming mail on Dashboard

**Steps:**

1. Schedule an ordinary Newsletter for a future time.
2. Enable an Automatic Newsletter with a future next run.
3. Return to Dashboard.

**Expected:** Upcoming ordinary **Scheduled Newsletter** and **Automatic Newsletter** are both shown and clearly distinguishable, with correct site-timezone dates and useful links.

### PM-009 — Dashboard task and migration notices

**Steps:**

1. Configure features that require tasks (queue, scheduled newsletter, Automatic Newsletter, returned-mail mailbox, optional reminder) while the corresponding tasks are absent or disabled.
2. Reopen Dashboard.
3. Add/enable each required task and recheck.
4. On a disposable clone, temporarily create an incomplete schema situation and reopen Dashboard.

**Expected:** Dashboard shows only relevant missing-task warnings and removes them when corrected. An incomplete schema produces a clear repair/update warning rather than a crash.

### PM-009A — Returned-mail attention acknowledgement

**Prerequisite:** Latest returned-mail check has newly suppressed at least one address.

**Steps:**

1. Open Dashboard and locate **Needs your attention**.
2. Confirm the suppression warning offers **Review** and **Mark as reviewed**.
3. Click **Review**, inspect Delivery, and return.
4. Click **Mark as reviewed**.
5. Verify the subscriber’s delivery state and returned-mail history.
6. Later perform another check that newly suppresses another address.

**Expected:** Mark as reviewed removes only that Dashboard warning. It does not unsuppress/delete the subscriber or erase bounce history. A later suppression creates a fresh attention item.

---

## B. Component Options and diagnostics

### PM-010 — Save, reopen, and inline help

**Steps:**

1. Open **Punga Mail → Options**.
2. Toggle **Inline Help**.
3. Change one harmless setting, save, leave the page, and reopen it.

**Expected:** Help toggles normally, the title is **Punga Mail: Options**, and the saved value persists.

### PM-011 — Sender and Reply-To settings

**Steps:**

1. Set a valid From name and From email.
2. Test Reply-To modes **None** and **Custom**.
3. With Custom, set an address/name and save.
4. Later inspect a controlled test message’s headers.
5. Try an invalid From or Reply-To address and run Preflight/test mail.

**Expected:** Valid values persist and produce the expected headers. None omits a custom Reply-To. Invalid addresses are rejected or block sending clearly.

### PM-012 — Outgoing transport: Joomla settings

**Steps:**

1. Set **Outgoing transport** to **Use Joomla settings**.
2. Save Options.
3. Send a controlled mail from Delivery and a Newsletter test mail.

**Expected:** Punga Mail uses Joomla’s globally configured mail transport, while honoring Punga Mail’s resolved From/Reply-To settings. No Custom SMTP credentials are required.

### PM-013 — Outgoing transport: Custom SMTP

**Prerequisite:** Controlled SMTP account dedicated to testing.

**Steps:**

1. Select **Custom SMTP**.
2. Enter the provider’s host, port, security mode, authentication setting, username, and password.
3. Save the secure SMTP settings using the provided action.
4. Send a test message.
5. Reopen Options and verify the password is not displayed.
6. Leave the password blank, change a harmless SMTP setting, save again, and retest.
7. Inspect Delivery diagnostics.

**Expected:** Punga Mail sends through the dedicated SMTP account without changing Joomla’s global mail account. Stored password is never revealed. Blank password on later save preserves the stored secret. Diagnostics identify Custom SMTP and host without exposing credentials.

### PM-014 — Custom SMTP validation and failure handling

**Steps:**

1. Temporarily enter a wrong SMTP host, port, username, or password.
2. Use the SMTP test action.
3. Restore valid settings.

**Expected:** Failure is reported clearly without exposing the password. No unrelated component state changes. Restoring correct settings makes the test succeed.

### PM-015 — Global mail design

**Steps:**

1. Set distinctive but readable values for mail heading, width, backgrounds, text/heading/link colors, font, padding, logo, footer color/reason, and harmless custom CSS.
2. Preview a newsletter and send a test message to desktop and mobile clients.
3. Test mail heading as literal text, `{site_name}`, and empty.

**Expected:** Preview/test mail consistently reflect settings. Empty heading remains empty. Styles remain readable and structurally email-safe.

### PM-016 — Browser view option

**Steps:**

1. Enable browser view, save, send a controlled newsletter, and open its browser-view link.
2. Disable browser view and repeat with a new newsletter.

**Expected:** Enabled mail contains a valid browser-view link to the immutable sent snapshot. Disabled mail does not expose one. Drafts cannot be publicly enumerated through browser-view URLs.

### PM-017 — Subscription and confirmation options

**Steps:**

1. Set confirmation lifetime, signup rate limit, resend delay, confirmation subject, and confirmation Markdown.
2. Use placeholders such as `{confirmation_url}`, `{site_name}`, and `{email}`.
3. Run a controlled guest signup.

**Expected:** Values persist. Confirmation mail renders Markdown and placeholders correctly. Expired/altered links fail safely. Resend/rate limits are enforced.

### PM-018 — Default subscription for new Joomla users

**Steps:**

1. Set **New Joomla users subscribed by default** to No and create a controlled Joomla user.
2. Set it to Yes and create another controlled user.
3. Inspect both users in Audience → Subscribers/profile.

**Expected:** Only the second account receives the configured default. Changing the option does not silently rewrite existing explicit preferences.

### PM-019 — Queue and retry options

**Steps:**

1. Record global queue pause, batch size, maximum attempts, and retry delay.
2. Change them to safe test values and save.
3. Use queue tests later to verify behavior.

**Expected:** Settings persist and are reflected by queue processing. Global pause does not itself mark mail failed.

### PM-020 — Returned-mail mailbox configuration and password secrecy

**Steps:**

1. Enter return mailbox host, port/security, folder, username, and password.
2. Save using the secure mailbox action.
3. Reopen Options.
4. Leave password blank, change another mailbox value, and save again.

**Expected:** Password is not redisplayed and is stored separately from normal Joomla component parameters. Blank password preserves the existing secret.

### PM-021 — Returned-mail connection test

**Steps:**

1. Use valid mailbox settings and click the mailbox connection test.
2. Temporarily use invalid credentials and test again.
3. Restore the valid values.

**Expected:** Success/failure is reported clearly. Invalid credentials do not leak secrets or corrupt subscriber state.

### PM-022 — Temporary-failure threshold wording

**Steps:**

1. Inspect **Temporary failures before blocking address** and its help text.
2. Set the threshold to 3.
3. Compare the wording with Delivery/Subscriber bounce-state wording later.

**Expected:** UI explicitly states the threshold applies only to temporary/soft failures and that a permanent/hard failure stops delivery immediately.

### PM-023 — Reminder options

**Steps:**

1. Enable Newsletter reminder.
2. Configure age threshold, recipient, subject, and Markdown with supported placeholders.
3. Run the Scheduled Task test later.

**Expected:** Settings persist, help explains the feature in user-facing language, and mail content renders correctly.

### PM-024 — Automatic Newsletter draft notification options

**Steps:**

1. Enable draft notifications and configure an explicit recipient address.
2. Run a draft-producing Automatic Newsletter later.
3. Repeat with Automatic send mode.

**Expected:** Draft mode can notify the configured reviewer with useful context and direct admin link. Automatic-send mode does not send the draft-review notification.

---

## C. Channels

### PM-030 — Create a Channel

**Steps:**

1. Open **Audience → Channels**.
2. Create a Channel with title, alias/description as available, and publish it.
3. Save & Close and reopen it.

**Expected:** Channel persists, appears in the list, and the Audience sidebar/tab context remains correct.

### PM-031 — Validation and unique alias

**Steps:**

1. Attempt to create invalid/duplicate Channel data where applicable.
2. Save.

**Expected:** Validation reports the problem without losing unrelated field values or leaving a partial record.

### PM-032 — Channel list controls and ordering

**Steps:**

1. Sort/filter the Channel list.
2. Reorder Channels using the supported ordering controls.
3. Reload the page.

**Expected:** Filtering/sorting work and manual ordering persists.

### PM-033 — Publish, unpublish, trash, restore, and delete

**Steps:**

1. Exercise publish/unpublish on a test Channel.
2. Trash it, filter Trash, restore it, then permanently delete only a disposable Channel.

**Expected:** Joomla lifecycle actions behave normally. Existing memberships are not silently reassigned.

### PM-034 — Channel eligibility: everyone

**Steps:**

1. Configure a Channel for everyone/external subscribers.
2. View the signup page/module logged out and logged in.

**Expected:** External email-only visitors can subscribe, subject to normal confirmation rules.

### PM-035 — Channel eligibility: registered users

**Steps:**

1. Configure a Channel for registered Joomla users only.
2. Compare logged-out visitor, ordinary logged-in user, and administrator subscriber editor.

**Expected:** Logged-out external addresses cannot subscribe. Eligible registered users can. Backend controls do not create invalid external memberships.

### PM-036 — Channel eligibility: selected Joomla groups

**Steps:**

1. Restrict a Channel to a selected Joomla user group.
2. Test with Test Registered and Test Special/group-qualified user.
3. Change the qualified user’s Joomla group membership and re-evaluate signup/profile and delivery eligibility.

**Expected:** Eligibility follows current Joomla group membership dynamically. A user who loses the qualifying group stops receiving that restricted Channel even if an old membership row still exists.

### PM-037 — Member count and in-use protection

**Steps:**

1. Add/remove controlled subscribers from a Channel.
2. Compare displayed member count.
3. Attempt destructive operations on a Channel currently referenced where protection applies.

**Expected:** Counts are accurate and destructive actions do not silently break references.

### PM-038 — Checkout and Global Check-in

**Steps:**

1. Open the same Channel in two administrator sessions.
2. Observe checkout behavior.
3. Abandon one edit and recover with Joomla Global Check-in.

**Expected:** Conflicting edits are protected and Global Check-in restores abandoned locks safely.

---

## D. Subscribers and consent state

### PM-050 — Subscriber list and status columns

**Steps:**

1. Open **Audience → Subscribers**.
2. Inspect normal subscribed, unsubscribed/pending if available, and bounce-suppressed test records.
3. Sort/filter by supported fields.

**Expected:** Subscription permission and delivery health are not conflated. The list clearly distinguishes **Subscription** from **Delivery**. A hard-bounced address can correctly show subscribed/active permission while also showing **Delivery blocked — permanent failure**.

### PM-051 — Add an external subscriber in administration

**Steps:**

1. Add a controlled email-only subscriber.
2. Select one or more eligible Channels.
3. Save and reopen.

**Expected:** Subscriber persists, normalized email prevents accidental duplicates, and Channel selections persist. A “no Channels selected” note is shown only when no Channel is actually selected.

### PM-052 — Add a Joomla user and choose restricted Channels before saving

**Prerequisite:** Have at least one Channel for **Registered users** and, if possible, one Channel restricted to a Joomla group that the controlled account belongs to.

**Steps:**

1. Open **Audience → Subscribers → New**.
2. Set **Recipient Type** to **Joomla User**.
3. Before selecting an account, confirm restricted Channels are unavailable as appropriate.
4. Select a controlled registered Joomla user. **Do not save yet.**
5. Confirm Channels for registered users and matching Joomla groups become selectable immediately.
6. Change to a Joomla user that does not belong to the restricted group and confirm the Channel becomes unavailable again.
7. Re-select the eligible user, choose the Channels, and save.
8. Reopen the Joomla profile and Punga Mail subscriber editor.

**Expected:** Channel eligibility follows the selected Joomla account immediately, before the subscriber exists in the database. Saving preserves the selected eligible Channels, server-side validation still rejects ineligible memberships, and no duplicate subscriber row is created.

### PM-052A — Reject duplicate subscriber creation

**Steps:**

1. Ensure a controlled Joomla user already exists in **Audience → Subscribers**, with recognizable Channel selections.
2. Open **New Subscriber**, choose **Joomla User**, and select that same Joomla account.
3. Confirm the editor immediately reports that the recipient already exists and offers **Open existing subscriber**.
4. Attempt **Save** / **Save & Close** and confirm the New form does not overwrite the existing record.
5. Repeat with **Email address** using an address already present in Subscribers.
6. Reopen the original subscriber and inspect its subscription status and Channel memberships.

**Expected:** Duplicate Joomla-user and email identities are rejected in the New workflow. The existing subscriber is unchanged, no second row is created, and the server rejects duplicate creation even if client-side checks are bypassed.

### PM-053 — Administrator Channel membership changes

**Steps:**

1. Add/remove several eligible Channel memberships in Subscriber editor.
2. Save and reopen.
3. Include an unpublished Channel membership if the backend supports maintaining it.

**Expected:** Visible choices persist correctly; backend administration does not accidentally erase unrelated/unpublished memberships.

### PM-054 — Global unsubscribe

**Steps:**

1. Globally unsubscribe a controlled subscriber while leaving Channel memberships stored.
2. Resolve that address through Channel and Joomla-group audience sources.

**Expected:** Global unsubscribe always wins. Stored Channel choices do not cause delivery until newsletter reception is explicitly restored.

### PM-055 — Pending confirmation and resend

**Steps:**

1. Create a pending guest subscription.
2. Attempt resend before and after the configured resend delay.
3. Confirm via the newest valid link.

**Expected:** Resend timing is enforced, confirmation activates the correct address/Channel choices, and stale/altered tokens fail safely.

### PM-056 — Protected-address reactivation requires intent

**Steps:**

1. Use an address that has explicitly unsubscribed or is otherwise protected.
2. Attempt to reintroduce it indirectly through import, Channel selection, or Joomla group targeting.
3. Perform the explicit reactivation flow where available.

**Expected:** Alternate audience sources cannot silently override a protected consent state. Explicit reactivation is required.

### PM-057 — Allow delivery again after bounce suppression

**Prerequisite:** Subscriber suppressed due to hard bounce or temporary-failure threshold.

**Steps:**

1. Open **Audience → Subscribers** and click **Allow delivery again**.
2. Confirm the list returns to Audience with the sidebar still active.
3. Verify Delivery state becomes deliverable and temporary-bounce counter resets.
4. Confirm historic bounce entries remain.
5. Repeat from the Subscriber editor.
6. In a second stale tab, attempt the action again after the suppression has already been cleared.

**Expected:** A real bounce-origin suppression is removed. History is retained. The grouped route is preserved. A stale/no-op action reports that no block was cleared instead of showing false success.

### PM-058 — Subscriber identity and duplicate normalization

**Steps:**

1. Attempt to create/import the same email with different case/whitespace forms.
2. If one address corresponds to a Joomla user, exercise the relevant merge/update path.

**Expected:** Punga Mail treats normalized addresses consistently and does not create conflicting duplicate recipient identities.

### PM-059 — Delivery health visibility

**Steps:**

1. Open a subscriber with no bounce history or suppression.
2. Open one with bounce history but currently deliverable.
3. Open one with active suppression.

**Expected:** Delivery-health UI is hidden when there is nothing meaningful to report. When shown, wording is plain-language and distinguishes permanent from temporary failures.

### PM-060 — Permanently delete an obsolete/test subscriber

**Prerequisite:** Use a disposable subscriber. Ideally use the hard-bounced bogus address from the returned-mail tests so suppression preservation can also be verified.

**Steps:**

1. Open **Audience → Subscribers** and select the disposable subscriber.
2. Click **Delete** and confirm the permanent-delete warning.
3. Verify the subscriber disappears from the Subscribers list.
4. If it had Channel memberships or pending preference requests, confirm those live relationships no longer appear.
5. If it had a hard-bounce/soft-bounce suppression, verify the address remains blocked if you inspect/reintroduce it through a controlled administration flow.
6. Verify existing Delivery queue history and returned-mail/bounce history are still present.
7. If practical, queue a controlled pending message for another disposable subscriber and delete that subscriber; verify pending/failed rows are cancelled before deletion.

**Expected:** The live subscriber identity can be removed without erasing historical delivery/bounce evidence or accidentally removing an address-level suppression. Deletion is refused while a delivery for that subscriber is actively being processed.

---

- Change the selected Joomla user to another account with different group memberships and confirm the enabled/disabled Channel set updates immediately without saving.

## E. Frontend module, confirmation, unsubscribe, and Joomla profile

### PM-070 — Signup module with no configured Channels

**Steps:**

1. Configure the signup module with no explicit Channels selected.
2. View it logged out and logged in.

**Expected:** It offers all currently published Channels the visitor/account is eligible to join. It does not invent a hidden single-Channel choice.

### PM-071 — Signup module with exactly one Channel

**Steps:**

1. Configure exactly one Channel.
2. View the module.

**Expected:** The Channel selector is hidden and the module clearly refers to the configured Channel.

### PM-072 — Signup module with multiple Channels

**Steps:**

1. Configure multiple Channels.
2. Select different combinations as guest and logged-in user.

**Expected:** Only configured, published, eligible Channels are offered. Membership updates affect the intended visible choices.

### PM-073 — Module text and return-page options

**Steps:**

1. Configure intro text, button label, Joomla module assignment, and any return-page behavior.
2. Exercise a signup/update.

**Expected:** Custom text renders safely, Joomla module settings behave normally, and the user returns to the intended page.

### PM-074 — Guest confirmation flow

**Steps:**

1. Sign up a new external email address and selected Channels.
2. Inspect confirmation email.
3. Follow confirmation link.

**Expected:** No newsletter is sent before confirmation. Confirmation activates the correct subscription state and Channel choices.

### PM-075 — Existing subscriber adds/removes Channels

**Steps:**

1. Use an existing subscriber to add one Channel and remove another through frontend controls.
2. Reopen the subscriber in backend.

**Expected:** Only intended visible memberships change. Leaving a Channel does not automatically mean “stop all newsletters.”

### PM-076 — Global unsubscribe page

**Steps:**

1. Open a valid unsubscribe link from a controlled newsletter.
2. Complete global unsubscribe.
3. Attempt later delivery through a Channel and Joomla group.

**Expected:** Address is globally excluded regardless of alternate audience sources. Stored Channel choices may remain for future explicit reactivation.

### PM-077 — RFC 8058 one-click unsubscribe

**Steps:**

1. Inspect a sent message for `List-Unsubscribe` and `List-Unsubscribe-Post` headers.
2. Open the HTTPS URL with GET as a human.
3. Exercise a valid one-click POST in a controlled way.

**Expected:** GET reaches the human confirmation flow rather than HTTP 400. Token-authenticated one-click POST unsubscribes without unsafe redirects.

### PM-078 — Invalid/expired public tokens

**Steps:**

1. Alter confirmation/unsubscribe tokens.
2. Use an expired confirmation token.
3. Reuse completed one-time flows where appropriate.

**Expected:** Invalid requests fail safely without revealing subscriber data or changing unrelated records.

### PM-079 — Signup abuse controls and privacy

**Steps:**

1. Exercise the configured IP/rate limit using a controlled test environment.
2. Submit an already-known address and an unknown address through public forms.

**Expected:** Abuse limits work without exposing whether an arbitrary address belongs to a subscriber more than necessary.

### PM-080 — Joomla user-profile integration

**Steps:**

1. Open frontend profile and administrator user editor for a controlled Joomla user.
2. Toggle **Receive newsletters** and select Channels.
3. Save and compare Punga Mail subscriber state.

**Expected:** Master permission and Channel choices remain distinct. Channel choices may remain stored while Receive newsletters = No, but no newsletter is delivered until reception is re-enabled.

### PM-081 — Unpublished/restricted Channels in profile

**Steps:**

1. Give a user membership in a Channel, then unpublish/restrict that Channel.
2. Save the user profile without seeing that Channel.

**Expected:** Hidden/unavailable memberships are not accidentally deleted by the profile form. Current eligibility still controls delivery.

### PM-082 — Subscription menu item and SEF routes

**Steps:**

1. Open the published Newsletter subscription menu item logged out and logged in.
2. Exercise confirmation/unsubscribe/browser-view routes with SEF enabled.

**Expected:** Public routes resolve through Joomla routing, use the intended menu item, and do not expose administrator-only pages.

---

## F. Design: Templates, Content Layouts, Markdown, and rendering

### PM-100 — Create and edit a Template

**Steps:**

1. Open **Design → Templates** and create a Template.
2. Enter subject/body and message/design overrides.
3. Save, reopen, Save & Close, and Cancel.

**Expected:** Template persists, Design remains highlighted, and no fake always-Active status column is shown.

### PM-101 — Template inheritance and overrides

**Steps:**

1. Configure global design/Reply-To values.
2. Configure different Template overrides.
3. Apply the Template to a Newsletter and preview.

**Expected:** Template inherits or overrides each setting according to UI choices; resolved output is predictable.

### PM-102 — Template lifecycle, checkout, and list controls

**Steps:**

1. Exercise list filters/sorting/trash/restore where supported.
2. Open the same Template in two admin sessions.
3. Recover an abandoned checkout with Global Check-in.

**Expected:** Joomla-standard lifecycle/checkout works and Design routing is preserved.

### PM-103 — Apply Template in Newsletter editor

**Steps:**

1. Open a Newsletter on **Mail content**.
2. Choose a Template and click **Apply template**.
3. Inspect subject/body/design values.

**Expected:** Template values are copied into the Newsletter as designed, the user remains on **Mail content**, and the applied content change is immediately visible.

### PM-104 — Central Content Layouts overview

**Steps:**

1. Open **Design → Content layouts**.
2. Verify a **Default content layout** and all usable registered Joomla content types appear.
3. Confirm each type indicates whether it inherits Default or uses a custom layout.

**Expected:** Content Layouts are centralized; Newsletter and Template editors no longer expose competing Selected Content Layout overrides.

### PM-105 — Available placeholders for a content type

**Steps:**

1. Open a non-core registered content type in Content Layouts.
2. Compare **Available placeholders** with its real backing database table.
3. Inspect generic Punga Mail placeholders and database placeholders.
4. Look for obviously sensitive columns such as password, secret, token, credential, OTP, API key.

**Expected:** Administrator can see exactly which placeholders are available. Safe source-table fields are exposed automatically; obvious secret/security fields are not offered. No cooperation/plugin/provider code from the originating extension is required.

### PM-106 — Type-specific database placeholder rendering

**Steps:**

1. Enable a custom layout for the non-core content type.
2. Use a type-specific source field, e.g. `{start_at}`, `{venue}`, or another real column.
3. For a date/time column, test `|date`, `|time`, and/or `|datetime`.
4. Preview a Newsletter containing that content item.

**Expected:** The source-table value renders correctly. Date/time formatting uses Joomla/site conventions. An event layout can show the actual event date while Punga Mail’s new-content selection still uses its normal publication/creation recency logic.

### PM-107 — Mixed content types and fallback

**Steps:**

1. Create a Newsletter containing at least two content types, one with a custom layout and one using Default.
2. Preview and send a test mail.
3. Reset the custom type to Default and preview again.

**Expected:** Each item uses the layout for its own content type. Resetting restores Default behavior without changing the selected content record.

### PM-108 — Markdown editor controls

**Steps:**

1. Open a Markdown editor in Newsletter/Template/Options.
2. Verify line numbers are not shown.
3. Use Bold/Italic/link or other existing toolbar controls.
4. Use **Table** and confirm a sensible starter Markdown table is inserted.
5. Use **Image**, select media from Joomla Media Manager, click Select, and supply alt text if requested.

**Expected:** Table and Image buttons are visually distinct. Media selection inserts Markdown image syntax at the current cursor rather than doing nothing. Selecting the same image again later still works.

### PM-108A — Joomla User Custom Field placeholders

**Prerequisite:** Create a published Joomla User Custom Field whose **Name** is `mobile-phone`, give a controlled Joomla user a recognizable value, and have at least one external email-only subscriber.

**Steps:**

1. Open a Template or Newsletter and inspect the Markdown editor's **Insert placeholder** menu.
2. Confirm `{userfield|mobile-phone}` is listed.
3. Put `{userfield|mobile-phone}` in the Markdown body and, separately, in the email subject.
4. Preview/send a test while logged in as the controlled Joomla user, then send a real controlled newsletter to that user.
5. Send the same content to the external email-only subscriber.
6. Test an unknown field name such as `{userfield|does-not-exist}` and, if practical, unpublish the test field and repeat.

**Expected:** The linked Joomla recipient receives their Custom Field value in subject/body; HTML output escapes unsafe characters. External recipients, unknown field names, and unpublished fields receive an empty value rather than a raw placeholder or error. The editor only offers published Joomla User Custom Field names.

### PM-109 — Markdown preview and sanitization

**Steps:**

1. Test headings, paragraphs, lists, links, images, tables, horizontal rules, escaped punctuation, and Markdown hard line breaks.
2. Include harmless raw/unsafe HTML and unresolved Joomla-style content-plugin commands in selected-content excerpts where applicable.

**Expected:** Supported Markdown renders consistently. Unsafe output is sanitized. Joomla content-plugin commands are not executed unexpectedly inside newsletter rendering.

---

## G. Newsletter composition and selected content

### PM-119A — Newsletter editor layout

**Steps:**

1. Create/open a Draft Newsletter.
2. Inspect Settings, Mail content, Content selection, Design, and the right-hand lifecycle/sidebar controls.
3. Verify Template selector + Apply template are on **Mail content**.
4. Verify scheduling/status controls are in the Joomla-style sidebar.

**Expected:** Authoring controls are grouped logically. Template application is beside the content it changes; scheduling can be done directly without first opening Preflight.

### PM-120 — Create, save, and reopen a Draft

**Steps:**

1. Enter title, subject, body, audience settings, and design values.
2. Save, leave, and reopen.

**Expected:** Persisted values survive exactly; no unintended audience source is selected by default for a new Newsletter.

### PM-121 — Registered content-type discovery and filtering

**Steps:**

1. Open **Content selection**.
2. Inspect available registered content types.
3. Filter by type/date/search as available.

**Expected:** Usable registered Joomla content types appear without Punga Mail-specific provider plugins. Unpublished/ineligible content is not silently treated as sendable.

### PM-122 — Select, order, and override content

**Steps:**

1. Select several available content items.
2. Confirm they move into **Selected content**.
3. Search/sort Available content and use **Select visible**.
4. Drag selected items into a new order; watch the **Drop here** insertion marker.
5. Drop, save, reopen, and verify order.
6. Use title/excerpt overrides on selected items.
7. Use **Clear selected**.

**Expected:** Selected and Available content remain conceptually separate. Drop target is obvious before release. Order persists and drives `{new_content}` output. Overrides appear only where relevant.

### PM-123 — Missing/removed selected content

**Steps:**

1. Select a disposable content item and save the Newsletter.
2. Unpublish/delete/change access to that source item.
3. Reopen composition/Preflight.

**Expected:** Missing/inaccessible references are handled explicitly; Punga Mail does not crash or silently leak restricted content.

### PM-124 — `{new_content}` placement

**Steps:**

1. Put `{new_content}` in a distinctive position in the body.
2. Preview with multiple selected items/types.
3. Remove `{new_content}` and preview again.

**Expected:** Selected content renders exactly where the placeholder is placed and uses central per-content-type layouts. No obsolete per-Newsletter Selected Content Layout control appears.

### PM-125 — `{recipient}` personalization and fallback

**Steps:**

1. Use `{recipient}` in a controlled Newsletter.
2. Preview/test against a Joomla user with a name and an email-only subscriber.

**Expected:** Personalization resolves appropriately and has a sensible fallback without exposing another recipient’s data.

### PM-126 — Audience: all globally subscribed recipients

**Steps:**

1. Select **All globally subscribed recipients, regardless of Channel** only.
2. Run Preflight.

**Expected:** All eligible globally subscribed addresses are included subject to validity, consent, access, deduplication, and suppression rules.

### PM-127 — Audience: one or multiple Channels

**Steps:**

1. Clear the all-subscribers source.
2. Select one Channel, then multiple Channels with overlapping members.
3. Run Preflight.

**Expected:** Channel sources use OR semantics and duplicate normalized email addresses are removed.

### PM-128 — Audience: Joomla groups

**Steps:**

1. Target a Joomla user group independently of Channels.
2. Combine it with Channel sources.

**Expected:** Sources are combined as documented. Global opt-out/suppression still win. Restricted content access remains recipient-safe.

### PM-129 — Subscriber with no Channel choices

**Steps:**

1. Use a globally subscribed user with no Channel memberships.
2. Compare a Channel-only Newsletter with an All-subscribers/Joomla-group Newsletter.

**Expected:** No Channel selection does not mean “never receive newsletters.” The user receives only mailings whose selected audience sources actually include them.

### PM-130 — Newsletter message/design overrides

**Steps:**

1. Override Template/global Reply-To, heading, and several style fields at Newsletter level.
2. Preview/test send.

**Expected:** Newsletter-specific overrides win where selected; inherited values remain unchanged otherwise.

### PM-131 — Newsletter list statuses and archive

**Steps:**

1. Inspect Draft, Scheduled, Queued/Sending if available, Sent, Failed/Cancelled, Archived, and Trash filters/states.
2. Archive a safe Draft or Sent Newsletter.
3. Filter Archived, open it, then Unarchive it.
4. Attempt to archive an active Scheduled/Queued/Sending Newsletter.

**Expected:** Archived is separate from delivery status and Trash. Archived items are hidden from Current by default but preserve their real lifecycle status and history. Active delivery states cannot be hidden by archiving.

### PM-132 — Duplicate as new draft

**Steps:**

1. Open both an unsent and a sent Newsletter and use **Duplicate as new draft** from the top toolbar.
2. In the Newsletters list, select multiple items and bulk-duplicate.

**Expected:** Every duplicate is an independent Draft. Originals are unchanged. The action is available regardless of sent state.

### PM-133 — Unsaved-change protection

**Steps:**

1. Open a Newsletter and immediately click **Cancel** without changing anything.
2. Change a persisted field and click Cancel.
3. Change it back exactly and click Cancel.
4. Edit Markdown through typing and through a toolbar action.
5. Repeat basic pristine/changed checks in Template and Automatic Newsletter editors.

**Expected:** Untouched forms do not warn. Genuine unsaved edits warn. Reverting exactly to saved state avoids a false warning. Joomla/editor initialization alone never marks a form dirty.

### PM-134 — Newsletter trash, restore, and delete

**Steps:**

1. Trash a disposable non-active Newsletter.
2. Filter Trash, restore it, then permanently delete only where safe.

**Expected:** Trash remains distinct from Archive. Sent immutable history is protected according to the component’s lifecycle rules.

---

## H. Preview, test mail, Preflight, and recipient inspection

### PM-150 — Preview is non-destructive

**Steps:**

1. Preview a Draft with unsaved/saved content as supported.
2. Click unsubscribe/footer controls inside the backend preview.
3. Return to the editor.

**Expected:** Preview does not alter subscriber state, queue mail, or recursively load itself through the unsubscribe link.

### PM-151 — Newsletter test mail through active transport

**Steps:**

1. Configure Joomla transport and send a Newsletter test mail.
2. Configure Custom SMTP and repeat.
3. Inspect From, Reply-To, subject, HTML, and selected-content rendering.

**Expected:** Test mail uses the currently selected outgoing transport and renders the same content rules as real delivery without creating a real mailing to the audience.

### PM-152 — Preflight complete summary

**Steps:**

1. Open Preflight for a valid Newsletter.
2. Inspect audience, included/excluded recipient counts, content, configuration, and warnings.

**Expected:** Preflight gives a useful final validation summary and does not alter subscriptions or delivery state.

### PM-153 — Blocking Preflight errors

**Steps:**

1. Create controlled invalid states: no eligible recipients, invalid sender/Reply-To, unusable selected content, or another blocking condition.
2. Attempt Check & Send / Schedule.

**Expected:** Punga Mail refuses the action with clear reasons and does not create a partial mailing.

### PM-154 — Preflight warnings

**Steps:**

1. Create a non-blocking edge case, such as an audience combination that includes recipients outside selected Channels.
2. Open Preflight.

**Expected:** Warning explains the consequence without falsely blocking a valid send.

### PM-155 — Inspect included and excluded recipients

**Steps:**

1. Build an audience containing valid, duplicate, opted-out, suppressed, and access-ineligible recipients.
2. Inspect Preflight recipient details.

**Expected:** Included/excluded lists explain why addresses are or are not eligible. Consent and suppression reasons are understandable.

### PM-156 — Schedule field consistency between editor and Preflight

**Steps:**

1. Schedule a Newsletter from the editor sidebar.
2. Open Preflight.
3. Inspect the **Schedule send** date/time field.
4. Reschedule from either surface and revisit the other.

**Expected:** Both surfaces display the same scheduled date/time in Joomla’s site timezone. Preflight remains available as an alternate scheduling location.

---

## I. Queue, scheduled sending, snapshots, browser view, and statistics

### PM-170 — Queue a valid Newsletter while globally paused

**Steps:**

1. Keep global queue paused.
2. Approve/queue a controlled valid Newsletter.
3. Open Delivery → Mail queue.

**Expected:** Mailing and recipient rows are created but not transported. Queue entries show useful Newsletter, recipient, status, timing, attempt, and error information.

### PM-171 — Immutable queued/sent snapshot

**Steps:**

1. Queue a Newsletter.
2. Attempt to change source content/template after queue creation.
3. Process the mailing.
4. Compare sent/browser-view output with the frozen snapshot.

**Expected:** Queued/sent history is immutable and not rewritten by later source edits.

### PM-172 — Queue filtering and safe administrative actions

**Steps:**

1. Filter queue by state, Newsletter, and recipient.
2. Retry a controlled failed row.
3. Cancel/remove only a pending/failed unsent row where the UI permits.
4. Attempt the same on a sent/currently-processing row.

**Expected:** Filters work. Retry/cancel actions are constrained to safe states and never rewrite already-sent history.

### PM-173 — Batch processing and successful handoff

**Steps:**

1. Set a small batch size and resume global queue.
2. Process a mailing larger than one batch.
3. Observe queue/delivery statistics after each pass.

**Expected:** Only configured batch size is processed per run. Successful transport handoffs progress cleanly until completion.

### PM-174 — Retry temporary transport failures

**Steps:**

1. Create a controlled temporary SMTP/transport failure.
2. Process queue and inspect attempt count/next retry.
3. Restore transport before max attempts and process again.

**Expected:** Temporary send failure enters retry flow using configured attempts/delay. Later success does not leave the recipient falsely failed.

### PM-175 — Per-mailing pause/resume and cancellation

**Steps:**

1. With a queued mailing, pause and resume it if supported.
2. Cancel remaining deliveries on a disposable mailing.

**Expected:** Pause is reversible; cancellation affects only remaining unsent deliveries and is clearly irreversible. Already-sent messages remain historical facts.

### PM-176 — Global queue pause/resume

**Steps:**

1. Toggle global queue pause in Options.
2. Run queue processing both paused and unpaused.

**Expected:** Pause stops sending without marking recipients failed. Resume continues eligible queued work.

### PM-177 — Direct scheduling from Newsletter editor

**Steps:**

1. Set a future schedule in the editor sidebar and schedule the Newsletter.
2. Reopen it.
3. Reschedule to a different future time.
4. Cancel the schedule.

**Expected:** Status/time update correctly. Cancelling the schedule returns the Newsletter to **Draft**, not a terminal Cancelled state, so it can be edited/rescheduled.

### PM-178 — Scheduled Newsletter task

**Steps:**

1. Schedule a Newsletter shortly in the future.
2. Enable **Punga Mail — Prepare scheduled newsletters**.
3. Run the task before due time and after due time.

**Expected:** Nothing is queued early. Once due, the Newsletter is revalidated/prepared for delivery. The normal send-queue task remains responsible for transporting queued mail.

### PM-179 — Scheduled/automatic Dashboard distinction

**Steps:**

1. Keep one future manually Scheduled Newsletter and one enabled Automatic Newsletter.
2. Inspect Dashboard upcoming mail.

**Expected:** Both appear as separate concepts; ordinary scheduled mail is not hidden behind Automatic Newsletter information.

### PM-180 — Delivery statistics and recipient details

**Steps:**

1. Complete a controlled send with at least one success and, if possible, one controlled failure/exclusion.
2. Open the sent Newsletter detail/statistics.

**Expected:** Counts and recipient details match queue/history. “Sent/accepted” wording does not falsely claim human reading.

### PM-181 — Attributable unsubscribe statistic

**Steps:**

1. Unsubscribe a controlled recipient using a link from a specific sent Newsletter.
2. Inspect that Newsletter’s statistics/history.

**Expected:** Unsubscribe is attributed where the feature records it without changing unrelated historic recipient rows.

### PM-182 — Browser view integrity

**Steps:**

1. Open browser view from a sent Newsletter.
2. Modify the source Draft/template/content afterwards.
3. Reload browser view.

**Expected:** Public browser view remains tied to the immutable sent snapshot and does not expose draft changes.

---

## J. Automatic Newsletters

### PM-200 — Create a safe draft-only Automatic Newsletter

**Steps:**

1. Create an Automatic Newsletter targeting the test Channel.
2. Select content types and schedule.
3. Choose draft/review mode rather than automatic send.
4. Save and enable it.

**Expected:** Definition persists, shows a clear next run, and does not send unattended in draft mode.

### PM-201 — Automatic Newsletter recurrence and timezone

**Steps:**

1. Test daily, weekly, and monthly recurrence where practical.
2. Use a monthly date near the end of a month if possible.
3. Compare editor, list, Dashboard, and run-history times.

**Expected:** Times use the configured Joomla timezone in the UI. Monthly recurrence behaves as calendar months rather than fixed 30-day drift.

### PM-202 — Since-last cutoff avoids repeats

**Steps:**

1. Configure **Since last successful run**.
2. Run once with eligible content.
3. Run at the next due time without adding content.
4. Add a new eligible item and run again.

**Expected:** Already-consumed content is not repeatedly included. Cutoff advances only according to successful/defined behavior.

### PM-203 — Rolling-period cutoff

**Steps:**

1. Configure content from a recent time period.
2. Add items inside and outside the window.
3. Run at due time.

**Expected:** Only items in the configured rolling period are considered.

### PM-204 — Content type order, maximum, and minimum

**Steps:**

1. Make more eligible items than needed.
2. Test **Newest first** and **Oldest first**.
3. Set a Maximum items cap.
4. Set Minimum items to 0 with **If no new content is found → Do nothing** and run with zero content.
5. Set a positive Minimum above the currently available count in Since-last mode.

**Expected:** Sort/cap work. Minimum 0 does not force an empty Newsletter when empty behavior is Do nothing. A positive unmet minimum records a skip and does not advance the Since-last cutoff, allowing content to accumulate.

### PM-205 — Critical access-permission test: mixed recipients

**Prerequisite:** Audience contains recipients with different Joomla content access rights and selected content includes restricted items.

**Steps:**

1. Build an Automatic Newsletter whose resolved audience includes Test Registered and Test Special.
2. Include Public and Special-only content.
3. Generate/send in a controlled mode.
4. Inspect each recipient-specific output or generated selection behavior.

**Expected:** **Every resolved recipient** receives only content that recipient would normally be allowed to view on the website. Restricted content must never leak because another recipient in the same mailing can see it.

### PM-206 — Homogeneous privileged audience

**Steps:**

1. Target only recipients allowed to see the Special item.
2. Generate the Automatic Newsletter.

**Expected:** Eligible restricted content can be included when every resolved recipient is allowed to view it.

### PM-207 — Guest/external audience access

**Steps:**

1. Target an external email-only audience.
2. Include Public plus Registered/Special content candidates.

**Expected:** External recipients receive only content available to the equivalent public visitor.

### PM-208 — Empty-content behavior

**Steps:**

1. Configure **Do nothing** and run with no matching content.
2. Configure the explicit empty-draft behavior if available and repeat.

**Expected:** Empty behavior matches the selected policy and is recorded clearly in history.

### PM-209 — Explicit automatic-send activation

**Steps:**

1. Switch a controlled Automatic Newsletter from draft/review to unattended send.
2. Observe any warning/confirmation.

**Expected:** Automatic sending requires an explicit administrator decision and is not enabled accidentally by ordinary edits.

### PM-210 — Automatic Newsletter delivery path

**Steps:**

1. Run a due Automatic Newsletter in send mode while global queue is paused.
2. Inspect the generated Newsletter and queue.
3. Resume queue and process.

**Expected:** Automatic generation uses the normal immutable Newsletter/queue infrastructure rather than bypassing delivery safeguards.

### PM-211 — Draft notification

**Steps:**

1. Enable the Component Option for Automatic Newsletter draft notification and a controlled reviewer address.
2. Generate a draft with selected content.
3. Inspect notification.
4. Repeat in automatic-send mode.

**Expected:** Draft notification identifies the Automatic Newsletter and generated Draft, summarizes selected/excluded content, and links directly to the admin editor. It is not sent for automatic-send runs.

### PM-212 — Automatic Newsletter history

**Steps:**

1. Produce successful, skipped, and if possible failed runs.
2. Inspect the history for each definition.
3. Open a generated Newsletter through its history link.

**Expected:** History clearly shows result, generated Newsletter title/link and current lifecycle state, selected content count, duration, and useful details. Never-run state is understandable.

### PM-213 — Enable/disable behavior and list state icon

**Steps:**

1. Open the Automatic Newsletters list and verify each normal row shows an enabled/disabled icon directly after the selection checkbox; there is no redundant text Status column on the right.
2. Click the icon of an enabled Automatic Newsletter.
3. Verify it changes to disabled without opening the editor and that the current list/filter context remains usable.
4. Click the icon again and verify it changes back to enabled.
5. Repeat with an administrator who lacks `core.edit.state` permission if practical; the state indicator must not be actionable.
6. Disable an Automatic Newsletter before its due time.
7. Let the due time pass.
8. Re-enable it and run the Automatic Newsletter task.

**Expected:** The row icon accurately represents the stored state and toggles through the existing CSRF-protected enable/disable actions. Disabled definitions do not generate. On re-enable, overdue scheduling follows the documented catch-up behavior rather than silently discarding the due run. Trashed rows are not directly toggleable from this icon.

### PM-214 — Automatic Newsletter checkout and grouped navigation

**Steps:**

1. Open/edit/save/cancel an Automatic Newsletter.
2. Exercise checkout with a second admin session.

**Expected:** Joomla-standard checkout works and sidebar context remains stable.

---

## K. Delivery, bounce handling, and mail health

### PM-230 — Delivery page overview and diagnostics

**Steps:**

1. Open **Delivery**.
2. Inspect active outgoing transport, queue summary, returned-mail status, diagnostics, and available manual actions.

**Expected:** Page identifies Joomla transport vs Custom SMTP without exposing secrets. Missing PHP IMAP or other relevant capabilities are reported clearly.

### PM-231 — Backend outgoing-mail test

**Steps:**

1. Send a controlled test mail from Delivery using Joomla transport.
2. Repeat using Custom SMTP.

**Expected:** Test reaches controlled recipient and uses active transport/sender settings. Failures produce useful diagnostics.

### PM-232 — Manual and scheduled returned-mail processing

**Steps:**

1. Place a known DSN/bounce in the configured mailbox.
2. Click **Check returned mail now**.
3. Confirm there is no `imap_fetchheader()` flag TypeError.
4. Place another controlled DSN and run **Punga Mail — Check returned mail** Scheduled Task.
5. Inspect Delivery status after each run.

**Expected:** Manual and scheduled paths use the same processing logic. Delivery shows latest check time, processed count, permanent failures, temporary failures, newly suppressed addresses, or a sanitized failure message.

### PM-233 — Hard bounce classification and immediate suppression

**Steps:**

1. Process a controlled permanent failure such as unknown mailbox/domain (`5.x.x` or equivalent clear permanent diagnostic).
2. Inspect Delivery and Audience → Subscribers.

**Expected:** Address is blocked immediately regardless of temporary-failure threshold. UI says **Permanent failure — delivery stopped immediately** / **Delivery blocked — permanent failure**, not merely technical “hard-bounce” jargon.

### PM-234 — Soft bounce threshold

**Steps:**

1. Set **Temporary failures before blocking address** to 3.
2. Process one controlled temporary failure for an address.
3. Repeat until threshold is reached.

**Expected:** Before threshold, address remains deliverable and UI shows progress such as **Temporary failure — 1 of 3 before delivery is stopped**. At threshold, delivery becomes blocked. Temporary-failure count behaves predictably.

### PM-235 — Unknown/unmatched bounce

**Steps:**

1. Process a bounce that cannot be confidently classified or associated.

**Expected:** Event is retained/reported for diagnosis without suppressing an unrelated subscriber.

### PM-236 — Bounce association and duplicate handling

**Steps:**

1. Process the same DSN more than once if the mailbox/test setup allows.
2. Inspect bounce history and counters.

**Expected:** Duplicate processing does not repeatedly inflate suppression/counters or create misleading new suppression warnings.

### PM-237 — Address-level isolation

**Steps:**

1. Suppress one test address.
2. Ensure another subscriber with a similar name/Channel/Joomla group remains deliverable.

**Expected:** Suppression is tied to the normalized email address and does not leak to unrelated recipients.

### PM-238 — Bounce history privacy

**Steps:**

1. Inspect Delivery returned-mail details and Subscriber delivery history.
2. Search rendered pages/source for mailbox passwords, SMTP passwords, raw authentication tokens, or unnecessary full message content.

**Expected:** Administrators get enough diagnostic information without secrets or excessive private mail content being exposed.

### PM-239 — Allow delivery again and later bounce

**Steps:**

1. Clear a bounce suppression using **Allow delivery again**.
2. Confirm address is deliverable and old bounce history remains.
3. Generate/process another permanent bounce for the same address.

**Expected:** Re-enabled address can be suppressed again by a later real bounce. Recovery does not disable future health checks.

### PM-240 — Dashboard returned-mail warning lifecycle

**Steps:**

1. Cause a check to newly suppress an address.
2. Confirm Dashboard warning.
3. Mark it reviewed.
4. Run a successful check with no new suppressions.
5. Later create another new suppression.

**Expected:** Old reviewed warning stays cleared; ordinary successful checks do not resurrect it; a genuinely new suppression creates a new attention item.

---

## L. Subscriber CSV import and export

### PM-250 — Import preview and field mapping

**Steps:**

1. Open **Tools → Import / Export**.
2. Upload a controlled CSV containing name/email/subscription/Channel data as supported.
3. Map columns and preview before committing.

**Expected:** Preview clearly shows intended changes and does not modify data yet. Tools sidebar context remains active.

### PM-251 — Import new and existing subscribers

**Steps:**

1. Import one new address and one existing address.
2. Commit and inspect Audience → Subscribers.

**Expected:** New subscriber is created; existing subscriber is updated/merged according to documented rules without duplicate normalized addresses.

### PM-252 — Channel mapping and membership merge

**Steps:**

1. Import Channel memberships for controlled addresses.
2. Compare pre-existing memberships before/after.

**Expected:** Mapping/merge semantics match the import preview and do not silently erase unrelated membership data.

### PM-253 — Protected-state import safety

**Steps:**

1. Include a globally unsubscribed or suppressed address in an import that otherwise appears subscribed.
2. Import without choosing any explicit protected-address reactivation option.

**Expected:** Import does not silently bypass explicit opt-out/suppression protections. Protected states remain protected.

### PM-254 — Explicit import reactivation

**Steps:**

1. Use **Explicitly reactivate protected addresses** only on a controlled address where reactivation is intentional.
2. Commit and inspect state.

**Expected:** Reactivation happens only because the administrator explicitly requested it, and resulting state is clear.

### PM-255 — Import limits and malformed files

**Steps:**

1. Test missing required columns, invalid email values, duplicate rows, unusual quoting/Unicode, and an oversized file if practical.

**Expected:** Errors are reported safely; malformed input does not create partial/corrupt subscriber data.

### PM-256 — Export subsets and columns

**Steps:**

1. Filter Subscribers by Channel/state/search.
2. Export the subset.
3. Open CSV in a text editor and spreadsheet application.

**Expected:** Export contains the intended subset and columns with correct UTF-8/CSV escaping.

### PM-257 — CSV formula-injection protection

**Steps:**

1. Use a controlled name/value beginning with `=`, `+`, `-`, or `@` where CSV export permits.
2. Export and inspect raw CSV.

**Expected:** Spreadsheet-formula injection is neutralized according to Punga Mail’s export safety rules.

### PM-258 — Cancel/clear import preview

**Steps:**

1. Create an import preview.
2. Use Clear/Cancel.

**Expected:** Preview/session state is removed without committing subscriber changes and the user remains in Tools.

---

## M. Joomla Scheduled Tasks and reminders

### PM-270 — Task types are installed and translated

**Steps:**

1. Open **System → Scheduled Tasks → New**.
2. Inspect available Punga Mail task types.

**Expected:** At minimum the current task types appear with understandable names/descriptions: send pending newsletters, prepare scheduled newsletters, create Automatic Newsletters, check returned mail, and Newsletter reminder.

### PM-271 — Send-queue task

**Steps:**

1. Queue controlled mail.
2. Run **Punga Mail — Send pending newsletters** while global queue is paused, then unpaused.

**Expected:** Paused run sends nothing and does not fail recipients. Unpaused run processes according to batch/retry settings.

### PM-272 — Scheduled-newsletter task

**Steps:**

1. Schedule an ordinary Newsletter.
2. Run **Punga Mail — Prepare scheduled newsletters** before and after due time.

**Expected:** This task handles ordinary scheduled Newsletters only and does not replace Automatic Newsletter generation.

### PM-273 — Automatic Newsletter task

**Steps:**

1. Make an Automatic Newsletter due.
2. Run **Punga Mail — Create automatic newsletters**.

**Expected:** Due enabled definitions run once under overlap protection, create Draft/queued Newsletter as configured, and record history.

### PM-274 — Returned-mail task

**Steps:**

1. Configure mailbox and place a controlled bounce.
2. Run **Punga Mail — Check returned mail**.

**Expected:** Task processes the mailbox using the same logic as the manual Delivery action and updates the latest-check summary/Dashboard attention state.

### PM-275 — Newsletter reminder task

**Steps:**

1. Enable reminder with a short safe test age and controlled recipient.
2. Run the reminder task when conditions are not met, then when they are met.

**Expected:** Reminder sends only when due and renders configured subject/Markdown placeholders correctly.

### PM-276 — Task overlap and stale-worker recovery

**Steps:**

1. In a safe staging environment, attempt overlapping task execution or simulate stale processing state if supported by the test setup.

**Expected:** Duplicate workers do not send the same recipient twice. Stale recovery follows the component’s guarded retry/queue semantics.

---

## N. ACL, CSRF, privacy, language, responsive UI, and regression sweep

### PM-290 — Administrator ACL

**Steps:**

1. Compare Super User, Test Manager, and Test Restricted.
2. Attempt view/edit/delete/send/import/export/secure-mail-settings actions appropriate to their permissions.

**Expected:** Joomla ACL is enforced server-side, not just by hiding buttons. Secure SMTP/IMAP credential save/test actions require the stronger component-options/admin permission intended by Punga Mail.

### PM-291 — CSRF protection

**Steps:**

1. Inspect/attempt representative state-changing actions without a valid Joomla form token in a controlled environment: save, delete, Archive, queue action, Allow delivery again, Mark as reviewed, import commit.

**Expected:** State-changing requests without a valid token are rejected and no data changes.

### PM-292 — Secret and private-data exposure audit

**Steps:**

1. Search rendered administrator HTML, page source, logs, error messages, exported configuration, and normal component params for SMTP/IMAP passwords and confirmation/unsubscribe tokens.

**Expected:** Stored secrets are not exposed. Subscriber data appears only on authorized surfaces and to the minimum extent needed.

### PM-293 — Frontend authorization and draft enumeration

**Steps:**

1. Attempt to access administrator URLs logged out.
2. Guess browser-view/newsletter IDs without valid public keys.
3. Attempt to retrieve Draft content through public routes.

**Expected:** Unauthorized access fails safely and Draft/private mailing content cannot be enumerated.

### PM-294 — Content output safety

**Steps:**

1. Put controlled script-like HTML, dangerous URLs, malformed Markdown, and unusual Unicode into authoring/source fields.
2. Preview/test send/browser view.

**Expected:** Output is safely sanitized/escaped while legitimate Markdown and Unicode remain functional.

### PM-295 — Consent cannot be bypassed by alternate audience sources

**Steps:**

1. Globally unsubscribe a controlled subscriber who is still in a Channel and Joomla group.
2. Target that Channel/group.
3. Repeat with an actively suppressed address.

**Expected:** Global unsubscribe and suppression always win over alternate audience sources.

### PM-296 — English and German interface/mail strings

**Steps:**

1. Switch administrator/site language between English and German.
2. Visit every Punga Mail main page and major frontend flow.
3. Send confirmation/test/Newsletter messages in each relevant site language context.

**Expected:** No raw `COM_PUNGAMAIL_*`, `JTOOLBAR_*`, or other untranslated keys appear. Terminology is understandable and consistent.

### PM-297 — Website language overrides

**Steps:**

1. Create a Joomla language override for a visible Punga Mail frontend/mail string.
2. Trigger the surface.

**Expected:** Standard Joomla language overrides are respected.

### PM-298 — Light/dark administrator themes and responsive UI

**Steps:**

1. Test major admin pages in Joomla light and dark modes.
2. Narrow the browser viewport/tablet width.
3. Inspect Markdown toolbar, Dashboard cards, grouped tabs, filters, queue table, and Content Layout placeholder panel.

**Expected:** Text remains readable, controls do not become indistinguishable, tables/sidebars remain usable, and no important control is hidden off-screen without a usable responsive path.

### PM-299 — Error handling and grouped-route recovery

**Steps:**

1. Trigger safe validation errors in Audience and Design editors.
2. Use filters, Save, Save & Close, Cancel, inline actions, and toolbar actions in Subscribers, Channels, Templates, and Content Layouts.
3. Observe URL/sidebars after each action.

**Expected:** Error messages are useful and retain entered data where appropriate. Grouped routes preserve `Audience`/`Design` context instead of falling back to Dashboard or collapsing the sidebar.

### PM-300 — End-to-end manual Newsletter regression

**Steps:**

1. Create a Channel and controlled subscribers.
2. Create/configure a Template and central Content Layout.
3. Create a Newsletter, apply the Template, select/reorder mixed content, choose audience, preview, test, Preflight, schedule or queue, process delivery, inspect statistics/browser view, then archive the sent Newsletter.

**Expected:** Entire manual lifecycle works coherently with no stale navigation, unexpected data loss, duplicate delivery, or mismatch between preview and frozen sent output.

### PM-301 — End-to-end Joomla user/profile regression

**Steps:**

1. Create a Joomla user, choose newsletter reception/Channels in profile, update memberships from frontend module/backend, send targeted mail, globally unsubscribe, and verify later exclusion.

**Expected:** Joomla account integration, master permission, Channel choices, eligibility, and consent precedence remain coherent across all surfaces.

### PM-302 — End-to-end Automatic Newsletter regression with access levels

**Steps:**

1. Configure mixed-access content and recipients.
2. Generate a Draft Automatic Newsletter, inspect history/notification, then test automatic sending in a controlled second run.

**Expected:** Access-safe content selection, recurrence, min/max/order rules, normal queue transport, and history all work together.

### PM-303 — Existing feature regression checklist

Before declaring the release ready, confirm at least once that all of these still work:

- package install/update and database migration;
- compact grouped administrator navigation and **Options** access;
- Dashboard counts, upcoming ordinary/Automatic mail, Quick Actions, attention acknowledgement;
- Channels including eligibility restrictions and Joomla-group rechecks;
- Subscribers including explicit consent, separate Subscription/Delivery states, and Allow delivery again;
- frontend signup module and Newsletter subscription menu item;
- Joomla user-profile integration;
- Templates and central Content Layouts with database placeholders;
- Markdown editor including Table/Image insertion and no line numbers;
- manual content selection, drag/drop marker, order persistence, overrides;
- Newsletter Template application returning to Mail content;
- unsaved-change warning without false pristine warnings;
- preview, test mail, Preflight, direct scheduling, Preflight schedule synchronization;
- Duplicate as new draft from editor and bulk list;
- Archive/Unarchive separate from Trash and delivery status;
- queue inspection/filter/retry/cancel safeguards and immutable snapshots;
- Joomla transport and Punga Mail Custom SMTP;
- Automatic Newsletter recurrence, minimum/maximum/order, history, and draft notification;
- returned-mail manual/task processing, latest-check summary, hard/soft classification, suppression, recovery, and Dashboard warning lifecycle;
- CSV import/export and protected-state safeguards;
- Scheduled Tasks, ACL, CSRF, language strings, SEF/public routes, and dark/responsive admin UI.

**Expected:** No feature that worked in the previous accepted release regresses silently.

---

## Final live-site release gate

Do not approve the release for production until all applicable items below are true:

- no unresolved **Fail** remains in a critical consent, access-control, queue, SMTP, bounce, or security test;
- a real controlled test Newsletter was rendered, validated, queued, transported, and recorded successfully;
- at least one mixed-access content test proved restricted content is not leaked;
- Custom SMTP has been tested if the site intends to use it; otherwise Joomla transport has been tested;
- Scheduled Tasks required by the site’s enabled features exist, are enabled, and have a recent successful run;
- returned-mail processing has been tested if enabled, including at least one known classification;
- backup/restore procedure is current;
- administrator and frontend terminology contains no raw translation keys or obsolete “topic/list” wording where the current UI says **Channel**;
- grouped Audience/Design navigation remains stable after actions and validation errors;
- `docs/USER_GUIDE.md` and this test guide match the installed behavior.

## Related documentation

- [Administrator/User Guide](USER_GUIDE.md)
- [Newsletter tutorial](TUTORIAL_NEWSLETTER.md)
- [Automatic Newsletter tutorial](TUTORIAL_DIGEST.md)
- [Channels and signup tutorial](TUTORIAL_TOPICS_AND_SIGNUP.md)
- [Template tutorial](TUTORIAL_TEMPLATES.md)
- [Delivery health / returned-mail tutorial](TUTORIAL_DELIVERY_HEALTH.md)
- [Import / Export tutorial](TUTORIAL_IMPORT_EXPORT.md)
- [Concept and design notes](CONCEPT.md)
- [Database notes](DATABASE.md)

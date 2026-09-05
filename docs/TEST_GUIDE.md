# Punga Mail 0.3.6 Live Acceptance Test Guide

This guide is for a Joomla administrator testing Punga Mail on a real installation. It covers the extension's administrator screens, frontend features, mail generation, automation, delivery controls, consent safeguards, and important regressions.

Work through the tests in order on a staging copy first. A live-site test can send real mail, alter subscriptions, or expose test content if the preparation below is skipped.

## How to record a test

For every test, record:

- result: **Pass**, **Fail**, **Blocked**, or **Not applicable**;
- date, tester, Joomla version, PHP version, and Punga Mail version;
- the test account, topic, newsletter, or digest used;
- screenshots of unexpected results;
- the exact error message and relevant Joomla log entry for a failure;
- whether the cleanup step was completed.

A test passes only when every expected result is true. Do not mark a test as passed merely because no error page appeared.

## Safe test setup

1. Make a current database and file backup and confirm how it will be restored.
2. Prefer a staging clone with the same Joomla, PHP, database, mail, cron, and SEF configuration as the live site.
3. In **Components → Punga Mail → Options → Queue**, set **Pause queue processing** to **Yes** and save.
4. Temporarily disable the Punga Mail Scheduled Tasks until their individual tests.
5. Create a private topic named `PM Test <date>` and target only that topic during send tests.
6. Use mailboxes controlled by the tester. Never use invented addresses at somebody else's domain.
7. Create these Joomla users with different controlled mailboxes:

   - **Test Registered**: ordinary Registered user;
   - **Test Special**: member of a group allowed to see a test Special access level;
   - **Test Manager**: administrator allowed to manage Punga Mail but not Super User;
   - **Test Restricted**: administrator without Punga Mail management permission.

8. Prepare one controlled external subscriber address that is not a Joomla user.
9. Create and publish these harmless test content items, each with a distinctive title:

   - a Public item;
   - a Registered item;
   - a Special/custom-access item visible only to Test Special;
   - a Public unpublished item;
   - optionally, equivalent items from another extension that correctly registers a Joomla content type.

10. Create a hidden but published menu item of type **Punga Mail → Newsletter subscription** with **Public** access.
11. If bounce testing is planned, use a dedicated mailbox and a controlled mail system capable of producing known delivery-status notifications.
12. Write down the site's configured timezone from **System → Global Configuration**. Scheduled times in this guide refer to that displayed site timezone.

## Recommended execution order

Run the sections in this order:

1. installation, navigation, options, and non-sending administration;
2. topics, subscribers, module, profile, and consent flows;
3. templates, composition, preview, and preflight;
4. import/export and security checks;
5. test mail, queue, scheduling, digests, browser view, and statistics;
6. bounce processing last, because it intentionally changes delivery status.

Keep the global queue paused except where a test explicitly says to resume it.

---

## A. Installation, update, navigation, and dashboard

### PM-001 — Fresh package installation

**Prerequisite:** A clean Joomla test installation and the Punga Mail package ZIP.

**Steps:**

1. Open **System → Install → Extensions**.
2. Upload the Punga Mail package.
3. Wait for Joomla's success message.
4. Open **System → Manage → Extensions** and search for `Punga Mail`.
5. Open **Components → Punga Mail**.

**Expected:** The package installs without an SQL or prepared-statement error. The component, signup module, user plugin, and task plugin are installed. The user and task plugins are enabled. The dashboard opens without a database-column error.

### PM-002 — Update from an existing 0.2.x/0.3.x installation

**Prerequisite:** A restored copy of an older Punga Mail database containing at least one subscriber, topic/template if supported by that version, newsletter, and sent snapshot.

**Steps:**

1. Record the existing IDs and take screenshots of the records.
2. Install the current package as an update.
3. Open **System → Maintenance → Database** and select **Check Database** if offered.
4. Open every Punga Mail main page.
5. Recheck the recorded records and sent snapshot.

**Expected:** The update completes without SQL errors. No dashboard migration warning remains. Existing IDs and data are preserved. The subscriber page and Joomla user profile save without `recipient_name` or other missing-column errors. A historic sent newsletter remains unchanged.

### PM-003 — Reinstall the same package safely

**Steps:**

1. Back up the database.
2. Install the current package over the same version.
3. Reopen the dashboard and one record from each area.

**Expected:** Installation succeeds, no data is duplicated or deleted, and no migration warning or unknown-column error appears.

### PM-004 — Uninstall data policy

**Prerequisite:** A disposable clone only.

**Steps:**

1. Leave **Options → Maintenance / Data → Uninstall: Remove database tables** set to **No**.
2. Uninstall the Punga Mail package and reinstall it.
3. Confirm that the test data remains.
4. On a newly restored disposable clone, set the option to **Yes**, save, and uninstall again.

**Expected:** The default uninstall preserves data. The explicit destructive option removes Punga Mail data. The option is clearly labelled and does not default to deletion.

### PM-005 — Administrator navigation and page discovery

**Steps:**

1. Open **Components → Punga Mail**.
2. Visit Dashboard, Newsletters, Templates, Channels, Subscribers, Automatic Newsletters, Delivery, Import/Export, and Options.
3. From a list, open an editor, preview, preflight, or detail page.

**Expected:** Every area is reachable and uses the normal Joomla administrator style. The Punga Mail submenu stays expanded and highlights the owning section on secondary pages. No untranslated key, PHP warning, or 404 appears.

### PM-006 — Dashboard totals and links

**Steps:**

1. Note the dashboard counts for subscriber, newsletter, and queue states.
2. Compare them with filtered lists in the corresponding pages.
3. Follow every dashboard link and action.

**Expected:** Counts agree with the lists. Links open the intended Punga Mail or Joomla Scheduled Tasks page. **Process queue now** produces a useful result and, while globally paused, does not mark queued recipients failed.

### PM-007 — Dashboard task notices

**Steps:**

1. Ensure no Punga Mail tasks exist; enable one digest, schedule one newsletter, and configure a bounce mailbox.
2. Reopen the dashboard.
3. Create and enable each missing task type, returning to the dashboard after each one.

**Expected:** Contextual notices identify the missing digest, scheduled-newsletter, bounce, and normal queue tasks. Each notice disappears when the corresponding enabled Scheduled Task exists. Notices do not demand unused task types.

### PM-008 — Incomplete-database warning

**Prerequisite:** A disposable clone on which an administrator can deliberately restore an older Punga Mail schema.

**Steps:**

1. Restore an older component database schema while keeping the current files.
2. Open the dashboard.
3. Use Joomla's database/update repair or reinstall the package.
4. Reopen the dashboard.

**Expected:** The dashboard shows a clear migration warning rather than crashing. After repair, the warning disappears and all pages open normally.

---

## B. Component Options and diagnostics

### PM-010 — Save, reopen, and inline help

**Steps:**

1. Open **Punga Mail → Options**.
2. Toggle **Inline Help**.
3. Change one harmless field, save, leave the page, and reopen it.

**Expected:** Help text toggles normally, the localized title is **Punga Mail: Options**, and the saved value persists.

### PM-011 — Sender and Reply-To settings

**Steps:**

1. Under **Mail**, enter a valid From name and From email.
2. Test Reply-To modes **None** and **Custom**.
3. For Custom, enter a valid address/name, save, and later inspect a test message's headers.
4. Enter an invalid From or Reply-To address and run newsletter preflight.

**Expected:** Valid values persist and produce the expected `From` and `Reply-To` headers. None omits the custom Reply-To. Invalid addresses are rejected or become a blocking preflight error.

### PM-012 — Default subscription for new Joomla users

**Steps:**

1. Set **New Joomla users subscribed by default** to **No** and register a controlled user.
2. Confirm that the user is not globally subscribed.
3. Set it to **Yes** and register another controlled user.
4. Inspect both in Subscribers and their profiles.

**Expected:** Only the second new user receives the configured default. Existing users are not silently changed when the option changes.

### PM-013 — Global body heading modes

**Steps:**

1. Set the global heading to a literal test heading and preview a newsletter with no override.
2. Set it to `{site_name}` and preview again.
3. Set it to empty and preview again.
4. Repeat with a test mail and, later, a browser version.

**Expected:** The literal text, Joomla site name, and no-heading states render exactly as configured. Empty does not silently fall back to the site name. Preview, test mail, real mail, and browser snapshot use the same result.

### PM-014 — Global visual design fields

**Steps:**

1. Record the current values.
2. Set distinctive but readable values for content width, outer/content backgrounds, text/heading/link colours, font family, font size, padding, logo URL/width, footer colour, and footer reason Markdown.
3. Add harmless custom CSS such as a border on the content container.
4. Preview and send a test message to at least one desktop and one mobile mail client.

**Expected:** Every value is saved and visibly applied where supported. Width, font size, padding, and logo width respect their documented limits. The email remains readable in clients that ignore optional CSS. Footer Markdown renders and the standard unsubscribe facility remains present.

**Cleanup:** Restore the original design values.

### PM-015 — Subscription security/timing options

**Steps:**

1. Record the current confirmation lifetime, hourly IP limit, and resend interval.
2. Set short, test-friendly values within the displayed limits and save.
3. Request confirmation twice within the resend interval.
4. In a controlled staging environment, exceed the hourly IP limit.
5. Try an expired confirmation token after the configured lifetime.

**Expected:** Duplicate confirmation mail is throttled, excessive requests are limited without disclosing whether an address exists, and an expired token cannot confirm. Values outside the allowed ranges cannot be saved.

### PM-016 — Confirmation mail subject and Markdown

**Steps:**

1. Customize the confirmation subject and body with `{site_name}`, `{email}`, and `{confirmation_url}`.
2. Start a guest subscription.
3. Inspect both HTML/text content and follow the URL.

**Expected:** Placeholders are replaced correctly; no raw placeholder remains. The URL confirms only the intended request and is safe to use once.

### PM-017 — Queue and retry settings

**Steps:**

1. Record batch size, maximum attempts, and retry interval.
2. Set small test values, save, and reopen Options.
3. Run the later controlled queue-retry test.

**Expected:** Values persist and stay within displayed limits. These settings control the existing queue; no duplicate rate/batch/retry setting appears elsewhere.

### PM-018 — Bounce mailbox configuration and password secrecy

**Steps:**

1. Open **Options → Bounce / return mailbox**.
2. Enter server, port, security/protocol choice, folder, username, password, bounce address, certificate-validation choice, and soft-bounce threshold.
3. Save and reopen Options.
4. Inspect the page source and browser password-manager-visible value.
5. Save another unrelated option while leaving the password blank.

**Expected:** Mailbox settings live in Component Options, not on the Delivery page. The stored password is never returned to the browser or displayed. Leaving it blank preserves the existing secret. Other values persist.

### PM-019 — Bounce mailbox connection test

**Steps:**

1. In the bounce mailbox field, test intentionally invalid connection details.
2. Correct the details and test again.

**Expected:** Failure is reported inside the Joomla administrator UI with a useful, sanitized message. Correct settings succeed. Neither result exposes the password or produces an unstyled Joomla 500 page.

### PM-020 — Reminder options

**Steps:**

1. Open **Options → Newsletter reminder** and verify the explanatory note appears above the settings.
2. Use Joomla’s inline-help toggle and verify Enable reminder, days, recipient email, subject, and Markdown all have useful help text.
3. Enable the reminder and set days, recipient email, subject, and Markdown using `{days}`, `{last_newsletter}`, `{last_sent_date}`, and `{site_name}`.
4. Save and run PM-275 later.
5. Disable the reminder and run the task again.

**Expected:** The tab explains the feature before configuration, every relevant field participates in inline help, settings persist, placeholders resolve, and disabled means no reminder is sent.

### PM-021 — Mail diagnostics

**Steps:**

1. Open **Delivery / Bounces** and review diagnostics.
2. Compare Joomla mailer/sender values and Punga Mail batch/retry values with Global Configuration and Component Options.
3. Check IMAP availability and queue state.

**Expected:** Locally knowable values are accurate. Missing IMAP is clearly reported. SPF, DKIM, and DMARC appear only as guidance unless genuinely checked; the page does not claim to configure DNS.

---

## C. Channels

### PM-030 — Create a topic

**Steps:**

1. Open **Channels → New**.
2. Enter a title and description, leave Alias blank, and choose Published.
3. Click **Save** and then **Save & Close**.

**Expected:** Both toolbar buttons exist. Save remains in the editor; Save & Close returns to the list. A unique URL-safe alias is generated and the record appears with title, description, alias, member count, state, ordering, and ID.

### PM-031 — Validation and unique alias

**Steps:**

1. Try to save a topic without a title.
2. Try to create another topic with the same explicit alias.

**Expected:** Required-title validation is clear. A duplicate alias is prevented or made predictably unique; existing data is not overwritten.

### PM-032 — Channel list controls

**Steps:**

1. Create several topics with different titles/states.
2. Test search, published-state filter, every sortable column, ordering controls, and pagination at a small page size.

**Expected:** Filters, sorting, ordering, and pagination show the correct stable records. Clearing filters restores the full list.

### PM-032A — Drag Channel ordering

**Steps:**

1. Sort the Channels table by **Ordering**.
2. Drag several Channels into a different order using the Joomla drag handles.
3. Reload the page and open the public Newsletter page or a signup module that offers all Channels.
4. Sort the administrator table by Title and verify that dragging is disabled until Ordering is selected again.

**Expected:** No raw ordering number needs to be edited. The dragged order persists after reload and is used by public Channel choices. Handles behave like Joomla core list ordering and do not imply reordering while another sort column is active.

### PM-033 — Publish, unpublish, trash, restore, and delete

**Steps:**

1. Select a disposable topic and use Publish and Unpublish.
2. Trash it, filter for Trashed, restore it, then trash it again and delete it.

**Expected:** Every state action works through Joomla conventions, requires a selected record, and shows an appropriate message. Permanent deletion is limited to trashed records.

### PM-034 — Protect an in-use topic

**Steps:**

1. Assign a topic to a subscriber, newsletter, module, or digest.
2. Trash it and attempt permanent deletion.

**Expected:** Destructive deletion is blocked or safely handles all dependencies; no orphan relation or SQL error is produced.

### PM-035 — Topic member count

**Steps:**

1. Note a topic's count.
2. Add one controlled subscriber to it, refresh, then remove that membership and refresh again.

**Expected:** The count changes by one in each direction and does not double-count one email.

### PM-036 — Topic checkout and Global Check-in

**Steps:**

1. Open an existing topic for editing and close the browser tab without Save & Close or Cancel.
2. Open Joomla's **System → Maintenance → Global Check-in**.
3. Check in the Punga Mail topic table.
4. Repeat, this time using Cancel.

**Expected:** Abandoning the editor leaves a recoverable checkout shown in Global Check-in. Global Check-in releases it. Cancel releases it immediately. A second administrator sees the normal Joomla locked-record behaviour while it is checked out.

---

## D. Subscribers and consent state

### PM-050 — Subscriber list, filters, and statuses

**Steps:**

1. Open Subscribers with prepared active, pending, unsubscribed, suppressed, and bounced addresses.
2. Test search, subscription-status filter, suppression filter, sorting, and pagination.
3. Compare a normal active row with a globally subscribed but bounced row and a globally unsubscribed row.
4. Select each email address to open its editor.

**Expected:** The list has one Newsletter permission column rather than redundant Subscription/Suppressed yes-no columns. Its main badge shows the master permission state. A subscribed-but-bounced/suppressed row additionally shows the delivery block and reason in the same cell. Search, filters, links, and details are correct without exposing security tokens.

### PM-051 — Add an external subscriber in administration

**Steps:**

1. Click **Subscribers → New**.
2. Enter a controlled email address and optional name, choose two published topics, and save.
3. Search for it and open it.

**Expected:** The editor visibly separates identity/permission, Channels, and delivery health. The external subscriber is created once as globally subscribed with the chosen name and topics. Invalid email is rejected. Creating the same normalized email again does not create a duplicate.

### PM-052 — Enable an existing Joomla user in administration

**Steps:**

1. Click **New** and select or enter the controlled Joomla user.
2. Save and inspect the Subscriber row and Joomla user profile.

**Expected:** Punga Mail uses the existing user/email identity, displays the Joomla name, and enables the newsletter preference without creating a second address record.

### PM-053 — Administrator Channel membership changes

**Steps:**

1. Open a controlled subscriber.
2. Add two topics and save.
3. Remove one topic and save again.
4. Use Apply and then Save & Close, verifying both navigation behaviors.

**Expected:** The subscriber remains in the other topic. Removing one membership is not a global unsubscribe and does not clear an existing bounce/manual suppression. Topic counts update correctly. Apply keeps the editor open; Save & Close returns to the list.

### PM-053A — Administrator can manage unpublished Channels

**Steps:**

1. Give a controlled subscriber membership in a published Channel.
2. Unpublish that Channel and open the subscriber in Punga Mail.
3. Verify that the Channel is still shown, clearly marked unpublished, then remove and save the membership.
4. Re-add the membership while the Channel remains unpublished and save again.
5. Open the public Newsletter page and confirm that the unpublished Channel is not offered there.

**Expected:** Administrators can inspect and maintain every non-trashed Channel membership without republishing it. Public subscription surfaces continue to expose published Channels only.

### PM-054 — Bulk global unsubscribe

**Steps:**

1. Select only controlled active subscribers.
2. Choose the bulk unsubscribe action and confirm it.
3. Resolve recipients for a newsletter that otherwise includes them.

**Expected:** Addresses become globally unsubscribed and are excluded with that reason. Topic memberships may be retained as preferences, but do not reactivate delivery.

### PM-055 — Resend confirmation to pending subscribers

**Steps:**

1. Select one pending subscriber and one already active subscriber.
2. Use **Send confirmation**.
3. Check controlled inboxes and administrator messages.

**Expected:** A valid confirmation is sent only where appropriate, respecting resend throttling. The action does not change status by itself.

### PM-056 — Protected-address reactivation requires intent

**Steps:**

1. Use an unsubscribed address and try ordinary topic membership edits/import without explicit reactivation.
2. Repeat with a suppressed or hard-bounced address.
3. Use the clearly labelled administrator reactivation/clear-suppression action only for a controlled address.

**Expected:** Ordinary edits never silently reactivate protected addresses. Explicit reactivation is visibly destructive/important and affects only the selected address.

### PM-057 — Clear bounce suppression and retain history

**Steps:**

1. Open a controlled bounced/suppressed subscriber with history.
2. Record count, timestamps, and reasons.
3. Choose **Clear bounce suppression** and confirm.

**Expected:** Suppression is removed for that email address, but bounce history remains available. Unrelated subscribers or other addresses are untouched.

### PM-058 — Subscriber identity and duplicate email normalization

**Steps:**

1. Attempt to add the same controlled address with different letter case and surrounding spaces through admin, module, and CSV.
2. Search the subscriber list.

**Expected:** One canonical address exists. Membership/state updates apply to it; recipient resolution sends only once.

---

## E. Frontend module, confirmation, unsubscribe, and Joomla profile

### PM-070 — Module with no configured topics

**Prerequisite:** At least one published topic and one unpublished topic.

**Steps:**

1. Edit the Punga Mail signup module and select no topics in its configuration.
2. Publish it on a controlled page and open that page as a guest.
3. Repeat when exactly one public topic exists.

**Expected:** No configuration means **all currently published topics are available for selection**. Even with exactly one published topic, it remains a visitor choice; the module must not silently behave as a configured single-topic module. Unpublished topics do not appear.

### PM-071 — Module with exactly one configured topic

**Steps:**

1. Configure exactly one published topic.
2. Open the module as guest and logged-in user.

**Expected:** No topic selector is shown. The copy clearly names the configured topic and actions apply directly to it.

### PM-072 — Module with multiple configured topics

**Steps:**

1. Configure two published topics while other topics also exist.
2. Open the module and select one, then both.

**Expected:** Only the configured topics appear. The visitor can choose one or more. No unrelated or unpublished topic is offered.

### PM-073 — Module text and return page options

**Steps:**

1. Set the module's custom intro text and button label.
2. Publish the module on a nested frontend page and submit a valid request from that page.

**Expected:** The configured presentation appears and the logged-in preference actions return to the same local site page. A forged external return value cannot create an open redirect.

### PM-074 — New guest email confirmation

**Steps:**

1. As a logged-out visitor, enter a new controlled email and choose topics.
2. Submit and inspect the public response and Subscribers page.
3. Before confirmation, resolve a newsletter audience.
4. Open the confirmation link and resolve again.

**Expected:** The public response is generic. The address is Pending and excluded before confirmation. A valid one-time token activates the intended global subscription/topic request; the address becomes eligible afterward.

### PM-075 — Existing subscriber adds a topic

**Steps:**

1. Start with an active address in Topic A only.
2. Through the module, request Topic B while preserving A.
3. Complete any required confirmation.

**Expected:** Topic B is added and Topic A remains. No duplicate subscriber is created.

### PM-076 — Remove one topic without global unsubscribe

**Steps:**

1. Start with an active address in Topics A and B.
2. Use the frontend preference action to remove A only.
3. Resolve newsletters for A and B.

**Expected:** The address is absent from A and still eligible for B. Global status remains active.

### PM-077 — Global unsubscribe page

**Steps:**

1. Send a test newsletter to a controlled active subscriber.
2. Follow its unsubscribe link.
3. Confirm the complete unsubscribe action.
4. Reopen the same link and resolve future recipients.

**Expected:** The action is unambiguous and globally suppresses future delivery. Reuse is safe/idempotent. The token is not displayed beyond what is required in the URL and is not leaked into the browser newsletter.

### PM-078 — RFC 8058 one-click unsubscribe

**Prerequisite:** HTTPS public site and access to raw received-message headers.

**Steps:**

1. Send a real controlled newsletter.
2. Inspect `List-Unsubscribe` and `List-Unsubscribe-Post` headers.
3. POST `List-Unsubscribe=One-Click` to the header URL without an administrator session.
4. Resolve the address again.

**Expected:** Headers are standards-shaped, the endpoint accepts the one-click POST securely, the address becomes globally unsubscribed, and no GET-only crawler action unsubscribes unexpectedly.

### PM-079 — Invalid, expired, and altered public tokens

**Steps:**

1. Alter one character in a confirmation and unsubscribe token.
2. Try an expired confirmation link and a token for a different address/action.

**Expected:** No subscription state changes. The public response is safe and does not reveal subscriber details or token secrets.

### PM-080 — Signup abuse controls and privacy

**Steps:**

1. Submit the module with its hidden honeypot filled.
2. Submit repeated requests beyond the rate limit in staging.
3. Compare responses for an existing and unknown address.

**Expected:** Bot/rate-limited requests are rejected or safely ignored. Responses do not enable address enumeration. No entered email/token/password is written to public output or ordinary Joomla logs.

### PM-081 — Registered user profile topic selector

**Steps:**

1. Log in as Test Registered and edit the Joomla profile.
2. Set **Receive newsletters** to Yes and select two published topics.
3. Save, reopen, and resolve a topic-targeted newsletter.
4. Remove one topic and save again.

**Expected:** Profile saves without a database-column error. The global preference and selected topics persist. Removing one topic leaves the other. The address is resolved only for the retained topic.

### PM-082 — Profile global No retains preferences safely

**Steps:**

1. With two topic preferences selected, change **Receive newsletters** to No and save.
2. Reopen the profile and resolve both audiences.
3. Change it to Yes again deliberately.

**Expected:** No globally unsubscribes/excludes delivery but topic preferences remain visible/retained. Topic membership alone cannot override global No. Deliberately returning to Yes restores eligibility according to current topics.

### PM-083 — Unpublished profile topics

**Steps:**

1. Give a user a published topic membership.
2. Unpublish that topic, edit/save the user's profile, then republish it.

**Expected:** The unpublished topic is not offered publicly but is not accidentally deleted merely by saving unrelated profile fields. Republish restores its visibility/preference as designed.

### PM-084 — Profile surfaces and permissions

**Steps:**

1. Check registration, frontend profile edit, administrator user edit, and administrator profile edit.
2. Save the newsletter fields in each relevant surface.

**Expected:** Fields appear only where intended, use translated labels/help, save consistently, and cannot be used by one user to alter another user's subscription without Joomla permission.

### PM-085 — Frontend menu item and SEF routes

**Steps:**

1. Create a menu item of type **Punga Mail → Newsletter subscription**.
2. Confirm the type title/description are translated, publish it, and optionally hide it from the visible menu.
3. Enable Joomla SEF URLs and open the page as a guest. Verify that every published topic and no unpublished topic appears, select topics, submit, and complete email confirmation.
4. Log in as a controlled Joomla user. Verify that current Channel memberships are preselected, change only Channels, and save directly on the menu-item page (not through a signup module).
5. Disable the global subscription, change topics again, and confirm the global state remains disabled.
6. Open confirmation, status, unsubscribe, and browser-view links.

**Expected:** No `COM_PUNGAMAIL_...` language key is visible. The Newsletter URL renders inside the normal site template without a fatal 500 page, and saving Channel choices does not require or assume a module ID. Newsletter reception is shown as the master setting; the topic summary explains the selected-topic and no-topic consequences. The standalone page offers all published topics, guest choices activate only after confirmation, and logged-in topic changes never alter global consent. Clean routes work with the hidden published anchor; no route exposes a draft or backend-only data.

### PM-086 — Logged-in module global and topic controls

**Steps:**

1. Log in as Test Registered and open a page containing the module.
2. Change only the topic choices and save them.
3. Use the separate Receive newsletters master control to stop all newsletters.
4. Change topics again while globally unsubscribed, then deliberately subscribe globally again.

**Expected:** The module uses the logged-in Joomla account rather than asking for another email. Topic-only changes do not reactivate global delivery. Global and topic actions are labelled distinctly, topic choices can be retained while globally off, and deliberate global re-subscription restores eligibility without creating a duplicate identity.

---

## F. Templates and rendering

### PM-100 — Create and edit a template

**Steps:**

1. Open **Templates → New** and verify the editor uses a main content area plus a Joomla-style right sidebar.
2. Confirm title/subject/body/selected-content layout are in the main content area, visual overrides/custom CSS are under **Design**, and message options are in the right sidebar. Confirm there is no fake Published/Unpublished status.
3. Enter a title, default subject, and Markdown body containing headings, emphasis, a list, a link, an image, a pipe table, `{recipient}`, and `{new_content}`.
4. Click Save, Preview, and Save & Close.

**Expected:** The Template editor follows Joomla's main-content/sidebar editing pattern without inventing an active/inactive state. All toolbar actions work. Preview renders supported Markdown in HTML, produces readable plain text, replaces `{recipient}` with the administrator's display name, and places selected-content output only at `{new_content}`.

### PM-101 — Template placeholders and image URL forms

**Steps:**

1. In a template, add HTTP(S), root-relative, and site-relative Joomla image URLs.
2. Add `{recipient}` twice and place `{new_content}` between two unique paragraphs.
3. Apply the template to a draft with selected content and preview it.

**Expected:** Images resolve to usable mail URLs, recipient placeholders resolve consistently, and selected items appear exactly at the placeholder—not appended elsewhere and without an invented heading.

### PM-102 — Template style inheritance and overrides

**Steps:**

1. Give the global configuration a distinctive heading/design.
2. In a template, leave several fields at Inherit and override several others.
3. Test heading modes Inherit, custom literal, site name, and no heading.
4. Test browser-view and Reply-To modes Inherit, Enabled/Custom, Disabled/None as applicable.
5. Preview after each mode change.

**Expected:** Inherited values come from Component Options; explicit template values win. Custom, site-name, and empty heading are distinct. Browser and Reply-To modes follow the selected value.

### PM-103 — Apply template copies values into a newsletter

**Steps:**

1. Apply a template to a new newsletter and save it.
2. Record the newsletter's subject/body/style.
3. Change the original template substantially.
4. Reopen the newsletter.

**Expected:** Applying copies values; later template changes do not alter the existing draft. A newly created newsletter receives the newer template values.

### PM-104 — Template list lifecycle

**Steps:**

1. Create several templates.
2. Test search, sorting, pagination, trash, trashed filter, restore, and permanent delete.

**Expected:** Standard Joomla list behaviour works, trashed rows remain legible in light and dark administrator themes, and no dependent newsletter changes when a template is removed.

### PM-105 — Template checkout and Global Check-in

**Steps:**

1. Abandon an existing template editor by closing its tab.
2. Confirm the lock in Joomla Global Check-in and from a second administrator account.
3. Check it in; then repeat using Cancel.

**Expected:** The abandoned edit is recoverably locked. Global Check-in and Cancel release it. Concurrent editing follows Joomla conventions.

### PM-106 — Rendering sanitization

**Steps:**

1. In staging, add Markdown containing raw `<script>`, an event handler, and a `javascript:` link.
2. Add custom CSS containing the text `</style><script>alert(1)</script>`.
3. Preview and inspect the HTML source.

**Expected:** No executable script, event handler, unsafe URL, or style-breakout markup reaches the preview or mail. Ordinary Markdown and safe CSS still render.

---

## G. Newsletter composition and selected content

### PM-119a — Newsletter editor layout

1. Open a draft Newsletter in the administrator.
2. Switch through **Settings**, **Mail content**, **Content selection**, and **Design** in the main area.
3. Confirm the right sidebar shows the real Newsletter lifecycle status, Template selector/application, and Schedule controls.
4. Change at least one field on each tab and save.

**Expected:** The editor follows Joomla's main-content/sidebar pattern, all values save normally, toolbar actions remain available, and the Content selection list/search behavior still works.

### PM-119b — `{new_content}` item-template editor presentation

1. Open Component Options, a Template, and a draft Newsletter.
2. Locate the `{new_content}` item-template editor in each location.

**Expected:** All three editors use monospaced text and visibly list the supported placeholders `{title}`, `{title_link}`, `{publish_date}`, `{excerpt}`, `{read_more}`, `{url}`, and `{content_type}` beneath the editor.


### PM-120 — Create, save, and reopen a draft

**Steps:**

1. Open **Newsletters → New**.
2. Enter a backend title, mail subject, Markdown body, recipients, and message options.
3. Save, leave, and reopen it.

**Expected:** The draft saves with status Draft. Backend title and email subject remain independent. Every selection and override persists.

### PM-121 — Registered content-type discovery and filtering

**Steps:**

1. Set **Content published since** to include the prepared content.
2. Select each offered registered content type and apply filters.
3. Use the candidate search and inspect item metadata.
4. Switch the Joomla administrator language where a translation is installed and reopen the content-source controls.

**Expected:** Joomla registered content types with usable metadata are offered generically. Their display names follow the administrator language when the originating component provides a language string. Date, type, category/filter where supported, and search narrow the list correctly. No deprecated UCM-specific copy or third-party-specific coupling is visible.

### PM-122 — Select, order, inspect, and override content

**Steps:**

1. Click a candidate content title and verify the corresponding frontend page opens in a new browser tab while the Newsletter editor remains open.
2. Select three content items.
3. Reorder them and give one a newsletter-only title and excerpt override.
4. Save, close, reopen, and preview.
5. Open the original website content item again.

**Expected:** Candidate titles are safe frontend links with new-tab behavior. Selection, order, source identity, and overrides persist. Preview uses the overridden values. The source content is unchanged.

### PM-123 — Removed, missing, and legacy content references

**Steps:**

1. On a disposable clone, select content and then trash/delete the source item.
2. Reopen the newsletter and run preflight.
3. If upgrading old data, open a newsletter containing migrated article selections.

**Expected:** Missing content is reported rather than causing a crash or silently selecting another item. Old migrated selections still resolve or show a clear warning, and their identity is preserved.

### PM-124 — `{new_content}` placement

**Steps:**

1. Put text before and after `{new_content}` and select two items.
2. Preview HTML and plain text.
3. Remove the placeholder and preview again.

**Expected:** Selected content appears exactly at the placeholder and in selected order. Punga Mail does not add its own “new content” heading. Without the placeholder, content is not silently appended.

### PM-124A — Selected-content item layout

**Steps:**

1. In Punga Mail Options, set the selected-content layout to a Markdown pattern containing `{publish_date}`, `{title_link}`, `{excerpt}`, and `{read_more}`.
2. Preview a newsletter containing a selected website item and verify the date/title/excerpt/read-more output.
3. Add a different layout to the reusable Template and verify it overrides the global value.
4. Add another layout to the Newsletter and verify it overrides the Template; then clear it and verify inheritance returns.
5. Also test `{title}`, `{url}`, and `{content_type}`.

**Expected:** Each item follows the effective global → Template → Newsletter Markdown layout. Publication dates are human-readable, links remain valid, omitted/empty placeholders do not expose raw tokens, and HTML/plain-text output follow the same layout.

### PM-125 — `{recipient}` personalization and fallback

**Steps:**

1. Send controlled copies to a Joomla user with a display name and an external subscriber with a stored name.
2. If supported, test an external subscriber without a name.
3. Compare preview, queued snapshot, HTML, and text parts.

**Expected:** Joomla users use their display name, external stored names are used where available, and the documented email fallback is used when no name exists. The resolved name is frozen in the queued/sent snapshot.

### PM-126 — Target all confirmed subscribers

**Steps:**

1. Choose the all-confirmed audience.
2. Include controlled active, pending, unsubscribed, suppressed, and invalid addresses.
3. Run recipient inspection without sending.

**Expected:** The live audience summary says that all globally subscribed recipients are included regardless of topic. Only deliverable confirmed active addresses are final recipients. Every excluded address has an accurate reason.

### PM-127 — Target one or multiple topics and deduplicate

**Steps:**

1. Put one controlled address in Topics A and B.
2. Target A only, then B only, then both.
3. Inspect resolved recipients each time.

**Expected:** The address appears for either membership and exactly once when both topics are selected. The newsletter editor clearly distinguishes topics from Joomla groups.

### PM-128 — Joomla group targeting remains independent

**Steps:**

1. Target a controlled Joomla group without selecting a topic.
2. Target a topic without selecting the group.
3. Target both together.

**Expected:** Either source may include an eligible address; combined targeting is a deduplicated union. Global unsubscribe, pending, invalid, and suppression states still win. Group targeting does not create topic membership.

### PM-129 — Zero-topic subscriber semantics

**Steps:**

1. Keep an active controlled subscriber with no topic membership.
2. Resolve an all-confirmed newsletter, a topic-only newsletter, and a group-targeted newsletter containing that user.

**Expected:** The address is eligible for all-subscriber targeting or an applicable Joomla group but not for a topic it did not choose. An empty topic set is not misrepresented as a global opt-out. The frontend summary explains this consequence in plain language. When all-subscriber targeting and a topic are both selected, the editor states that the topic does not narrow the audience and Preflight warns if eligible recipients outside the topic are included. Clearing every audience source produces a blocking Preflight error.

### PM-130 — Newsletter-level message/design overrides

**Steps:**

1. Apply a template with known values.
2. Override heading, browser view, Reply-To, several styles, and custom CSS on the newsletter.
3. Preview and send a test copy.

**Expected:** Newsletter overrides win over template/global values; inherited fields still follow the lower level. Output is consistent across preview, test, real mail, and browser snapshot.

### PM-131 — Newsletter list controls and statuses

**Steps:**

1. Prepare newsletters in Draft, Scheduled, Queued/Sending, Sent, Failed/Cancelled, and Trashed states where practical.
2. Test search, filters, sorting, pagination, and displayed scheduled time.
3. Confirm the list does not contain a redundant record-state column that always says Active; delivery status remains visible.

**Expected:** Delivery status is distinct from Joomla trash/restore record state. Scheduled time is clearly shown in site timezone. Filters and list controls are accurate.

### PM-132 — Duplicate as new draft

**Steps:**

1. Open a draft Newsletter and a sent Newsletter separately and use **Duplicate as new draft** from the top toolbar.
2. In the Newsletters list, select two or more rows and use the bulk duplicate action.
3. Open the duplicates and compare them with their sources.

**Expected:** The toolbar action is available for every saved Newsletter. Each duplicate is a new editable Draft with a new ID. Bulk duplication creates one Draft per selected source. Original snapshots, statistics, and queue rows are unchanged; no recipient is queued automatically.

### PM-133 — Newsletter checkout and safe editing

**Steps:**

1. Repeat the abandoned-editor/Global Check-in test for a draft newsletter.
2. Schedule a newsletter and check the available edit/cancel behaviour.
3. Start actual queueing and attempt to edit the sending newsletter.

**Expected:** Draft checkout works. Scheduled editing is predictable and cannot leave an unseen stale snapshot. Once queueing/sending begins, immutable message and recipient data cannot be altered by ordinary editing.

### PM-134 — Newsletter trash, restore, and delete

**Steps:**

1. Trash and restore a disposable draft.
2. Trash it again, filter for Trashed, and permanently delete it.
3. Attempt the same lifecycle on a newsletter with immutable delivery history.

**Expected:** Standard Joomla state actions work for disposable drafts. Historic snapshots/statistics cannot be silently destroyed or orphaned; the UI blocks or safely handles an in-use sent record. Trashed rows remain legible.

---

## H. Preview, test mail, check before sending, and recipient inspection

### PM-150 — Preview is non-destructive

**Steps:**

1. Record subscriber states and newsletter modification data.
2. Open Preview several times.
3. Try both **Unsubscribe** and **View in browser** in the backend preview.
4. Compare the recorded data.

**Expected:** Preview uses the current administrator identity, renders both personal actions as non-navigable, creates no queue rows, and changes no subscriber/newsletter state.

### PM-151 — Test mail through Joomla transport

**Steps:**

1. Use a valid controlled address and click the newsletter test-mail action.
2. Inspect HTML, plain text, From, Reply-To, subject, links, and rendering.
3. Temporarily enter an unreachable/invalid transport on staging and repeat.

**Expected:** Test mail uses Joomla's configured mailer, not a parallel SMTP setup. Success/failure appears in the Joomla UI with a useful sanitized message. Failure does not produce an unstyled 500 page or queue a campaign.

### PM-152 — Preflight complete summary

**Steps:**

1. Build a valid newsletter with a known mixed recipient set.
2. Click **Check recipients & send**.
3. Compare displayed subject, sender, Reply-To, HTML/text status, unsubscribe, browser link, size, queue, schedule, intended/raw/final counts, and exclusions with the source data.

**Expected:** The summary is understandable and accurate. Warnings are visually distinct from blocking errors. Opening preflight does not alter subscription state or queue mail.

### PM-153 — Blocking preflight errors

**Steps:**

1. Test separately: missing subject, invalid/missing From, invalid Reply-To, zero valid recipients, unavailable HTML, unavailable text, missing unsubscribe mechanism, and a missing/restricted content reference where blocking.
2. Attempt to proceed after each case.

**Expected:** Genuine blocking problems prevent send/queue/schedule. The problem names the field or condition to fix. No accidental newsletter snapshot is created.

### PM-154 — Preflight warnings

**Steps:**

1. Add an obviously malformed HTTP link and an image without alt text.
2. Generate a message over the warning-size threshold (approximately 500 KiB) and, on staging, the blocking threshold (approximately 1 MiB).
3. Pause the queue and run preflight.

**Expected:** Detectable malformed links, missing alt text, large size, and paused queue appear as appropriate warnings. A truly oversized message is blocked where documented. A warning alone does not masquerade as proof that all links/images are valid.

### PM-155 — Inspect included and excluded recipients

**Steps:**

1. Prepare addresses excluded for invalid email, pending/unsubscribed, suppression, hard bounce, soft-bounce threshold, not-in-topic, and duplicate identity.
2. Include one valid address through multiple audience sources.
3. Open recipient inspection and search/view the underlying rows.

**Expected:** Raw and deduplicated final counts reconcile. Every expected exclusion has the correct reason; the duplicate is eliminated exactly once; the valid address appears once. Inspection changes no state.

### PM-156 — List-ID strategy

**Steps:**

1. Send controlled newsletters targeted to one topic, multiple topics, no topic/all-confirmed, and Joomla group only.
2. Inspect raw headers.

**Expected:** Each message has one stable standards-compatible `List-ID`. A single topic gets a stable topic-specific identity. Multiple-topic/non-topic strategies are predictable and do not generate a changing arbitrary ID per recipient or send attempt.

---

## I. Queue, scheduled sending, snapshots, browser view, and statistics

### PM-170 — Queue a valid newsletter while globally paused

**Steps:**

1. Keep global queue pause enabled.
2. Complete preflight and choose Send now/queue for a controlled audience.
3. Open the newsletter delivery detail and manually run queue processing.

**Expected:** An immutable message/recipient snapshot is created, status becomes Queued, and rows remain pending while paused. Pause is not counted as a failure or retry.

### PM-171 — Immutable queue snapshot

**Steps:**

1. Record frozen subject, HTML, plain text, recipient email/name/source, and intended count.
2. Change the original template, source article, subscriber name, topic membership, and global design.
3. Reopen delivery detail and then resume/send.

**Expected:** The queued/sent mailing retains the recorded snapshot. Current editable records do not rewrite history.

### PM-172 — Batch processing and successful handoff

**Steps:**

1. Set a small batch size and use more controlled recipients than fit in one batch.
2. Resume global queue processing and run one batch at a time.
3. Inspect statuses and controlled inboxes after each run.

**Expected:** No run exceeds the configured batch. Rows progress atomically without duplicates. Statistics distinguish queued/remaining from successfully handed to the configured mail transport; they do not claim a human read or guaranteed delivery.

### PM-173 — Retry temporary transport failures

**Prerequisite:** A controlled staging mail transport that can reject temporarily.

**Steps:**

1. Cause a temporary transport failure for a controlled recipient.
2. Run queue processing and record attempts, next retry, and status.
3. Run again before the retry time, then restore transport and run after it.
4. Separately allow failures to reach maximum attempts.

**Expected:** Retry uses the existing configured interval/count, does not duplicate successful rows, and distinguishes temporary from permanent/exhausted failure. Before due time it is not retried. Statistics reconcile.

### PM-174 — Per-mailing pause and resume

**Steps:**

1. Start a multi-batch controlled mailing.
2. Pause that mailing after one batch while leaving the global queue enabled.
3. Run processing, then resume it.

**Expected:** Already handed-off messages are not recalled or resent. Remaining rows stay queued without failure while paused and continue after resume. Other eligible mailings can continue.

### PM-175 — Global queue pause and resume

**Steps:**

1. With two queued controlled mailings, enable global pause.
2. Run both manual and Scheduled Task processing.
3. Disable pause and run again.

**Expected:** Nothing new is handed to transport while paused, no retries/failures accrue, and all eligible queues resume safely afterward.

### PM-176 — Cancel remaining mailing

**Steps:**

1. Start a multi-batch mailing and let one batch complete.
2. Choose **Cancel remaining mailing** and confirm the clearly worded warning.
3. Run queue processing again.

**Expected:** Sent messages are not recalled and are not reclassified. Unsent remainder becomes Cancelled, no more messages go out, newsletter status/statistics show partial completion and cancellation accurately.

### PM-177 — Schedule a newsletter

**Steps:**

1. In a valid Draft Newsletter, enter a future date/time directly in the right-hand Schedule sidebar without opening Preflight first.
2. Select **Schedule** and confirm the Newsletter becomes Scheduled. Change the date and use **Reschedule**.
3. Compare the editor, list, dashboard, database/task timing if available, and current UTC offset.
4. Repeat with a deliberately invalid Newsletter (for example, no effective audience) and confirm scheduling is rejected with the same blocking validation used by Preflight.

**Expected:** Valid mail becomes Scheduled; displayed time and timezone are unambiguous. Invalid mail cannot be scheduled. No queue snapshot starts before the due scheduling task.

### PM-178 — Cancel and edit a scheduled newsletter

**Steps:**

1. Cancel a scheduled newsletter before it is picked up.
2. Run the scheduling task after the original due time.
3. Separately edit a scheduled newsletter using the allowed workflow and confirm/reschedule it.

**Expected:** Removing the schedule returns the Newsletter to Draft and clears its scheduled time; the old due time never queues it. Editing cannot accidentally keep an obsolete hidden schedule or enable immediate sending.

### PM-179 — Scheduled newsletter task

**Steps:**

1. Create and enable **Punga Mail — Prepare scheduled newsletters** at a short interval.
2. Schedule one newsletter in the future and one due controlled newsletter.
3. Run the task manually or wait for cron, then inspect task history and newsletter states.

**Expected:** Only due newsletters move to the immutable send queue. Future/cancelled items remain untouched. Actual transport still depends on the normal send-queue task and pause state.

### PM-180 — Delivery statistics and recipient details

**Steps:**

1. Open a completed, partially failed, partially cancelled, and bounced controlled mailing.
2. Inspect intended, queued, transport-accepted, temporary/permanent failure, hard/soft bounce, suppressed, attributable unsubscribe, remaining/processing, and cancelled counts.
3. Drill into the underlying recipient/failure rows.

**Expected:** Counts reconcile to the immutable mailing population and rows. Reasons/timestamps are useful and sanitized. Later subscriber changes do not rewrite historic intended/delivery counts.

### PM-181 — Attributable unsubscribe statistic

**Steps:**

1. Send two distinct controlled mailings to a subscriber.
2. Use the unsubscribe link from the newer mailing.
3. Inspect both mailings' statistics.

**Expected:** Where attribution is available, the unsubscribe is credited to the correct immutable mailing only; the system does not invent attribution for unrelated/manual changes.

### PM-182 — Browser view enabled

**Steps:**

1. Enable browser view globally or via a valid template/newsletter override.
2. Send a controlled newsletter and follow its browser link in a logged-out/private window.
3. Change the draft/source/template/global styles after sending and reload the browser view.

**Expected:** The public page displays the immutable sent snapshot and stays unchanged. It contains no recipient-specific greeting/token, backend metadata, or private subscriber data. The route is SEF when the menu anchor exists.

### PM-183 — Browser view disabled and draft protection

**Steps:**

1. Disable browser view and send a controlled newsletter.
2. Attempt its would-be browser route.
3. Try guessed IDs/keys for a draft, scheduled, cancelled-before-send, and nonexistent newsletter.

**Expected:** Disabled views and all non-sent/private records are unavailable without revealing whether a private draft exists. Random/invalid keys fail safely. No sequential ID alone grants access.

---

## J. Automatic Newsletters

### PM-200 — Create a safe draft-only digest

**Steps:**

1. Open **Automatic Newsletters → New**, change several non-required settings, then press Save once with Title and Template still empty. Verify the editor remains open and the changed settings are still present.
2. Confirm recurrence is entered as a number plus **days**, **weeks**, or **months**. Verify a monthly choice remains a calendar-month schedule rather than becoming 30 days. Choose **Content since the previous automatic newsletter** and verify the look-back field is hidden; choose **Content from a recent time period** and verify **Look back … days** appears.
3. Verify registered content-source names follow the current administrator language where Joomla provides a translation; then enter a title, recurrence/next run, template, subject pattern, cutoff, content type/filter, target audience, **Create draft**, and empty-content behaviour.
4. Save and reopen it, then confirm or set its Enabled state in the Automatic Newsletters list.

**Expected:** Failed validation stays in the editor, shows the error, and preserves submitted values. Recurrence offers days/weeks/months, look-back remains day-based, and irrelevant fields stay hidden. After a valid save, all fields persist, next run is clear in site timezone, and Create draft is the safe/default generation mode. Editing does not run the automatic newsletter immediately.

### PM-201 — Digest subject placeholders and template placement

**Steps:**

1. Use `{date}` and `{site_name}` in the subject pattern and `{new_content}` in the chosen template.
2. Make one eligible public item new and run the digest task when due.
3. Open the generated newsletter.

**Expected:** A new Draft is created with resolved subject, template/style values, selected item, and content at the exact placeholder. It is not queued or sent.

### PM-202 — Since-last-successful cutoff avoids repeats

**Steps:**

1. Configure **content since last successfully generated/sent digest**.
2. Run it with Item A new and confirm a successful draft/run.
3. Run again with no new item, then publish Item B and run again.

**Expected:** Item A is not repeatedly included. The empty run follows configured empty behaviour and is recorded. The next content run includes Item B but not A. A failed run does not incorrectly advance the successful cutoff.

### PM-203 — Rolling-period cutoff

**Steps:**

1. Choose **Content from a recent time period** and enter a short **Look back … days** value.
2. Prepare one item inside and one outside the period.
3. Run the digest twice while both time positions remain valid.

**Expected:** Only in-period content is included. Repetition is possible only because rolling mode intentionally selects the period and is understandable from configuration/history.

### PM-204 — Content types and category/filter handling

**Steps:**

1. Select multiple registered content types and supported categories/filters.
2. Publish matching and nonmatching items after the cutoff.
3. Run the digest.

**Expected:** Only matching items are selected. Generic registered metadata drives selection; no Punga Mail-specific provider integration is required.

### PM-205 — Critical access-permission test: mixed recipients

**Prerequisite:** Public, Registered, and Special/custom-access test items plus recipients Test Registered and Test Special in the same intended digest audience.

**Steps:**

1. Confirm on the website that Test Registered cannot view the Special item and Test Special can.
2. Make the Public, Registered, and Special items new for the cutoff.
3. Configure one digest targeting both recipients and run it in Create draft mode.
4. Inspect selected content, preview, generated HTML/text, and digest history.

**Expected:** Every included item is something **every resolved recipient** could normally view. The Special item is excluded because one recipient lacks access. Missing/uncertain access metadata fails closed. There is no restricted title, excerpt, URL, or body leak.

### PM-206 — Access test: homogeneous privileged audience

**Steps:**

1. Create a separate digest audience containing only Test Special users who can view the Special item.
2. Run the digest in Create draft mode.

**Expected:** The Special item may now be included, while unpublished/trashed items remain excluded. Website view-level and applicable category access both take effect.

### PM-207 — Guest/external audience access

**Steps:**

1. Target an external email-only subscriber with Public, Registered, and Special items new.
2. Run a draft digest.

**Expected:** Only content available to a public/guest website visitor is included. External subscribers are never assumed to hold a Joomla authenticated access level.

### PM-208 — Empty digest behaviour: skip

**Steps:**

1. Configure no-content handling to Skip and make the digest due with no eligible content.
2. Run the task.

**Expected:** No newsletter or mail is created/sent. History clearly records no content/skip and the run time. No blank automatic newsletter reaches recipients.

### PM-209 — Empty digest behaviour: explicit draft

**Steps:**

1. Explicitly configure the available create-empty-draft behaviour.
2. Run with no eligible content, including while generation mode says automatic send.

**Expected:** At most a Draft is created; an empty digest is never automatically sent. History explains the outcome.

### PM-210 — Explicit automatic-send activation

**Steps:**

1. With **Create draft** selected, verify the unattended-send warning and confirmation checkbox are hidden.
2. Change it to **Create and send automatically** and verify both appear.
3. Save without accepting the explicit confirmation, then repeat with deliberate confirmation.
4. Edit an already configured Automatic Newsletter without changing generation mode.

**Expected:** Automatic send cannot become active accidentally. The warning is shown only when relevant, a clear confirmation is required when newly enabling it, and ordinary later edits do not silently switch modes or trigger an immediate run.

### PM-211 — Automatic digest delivery path

**Steps:**

1. Use only controlled recipients and keep global queue paused initially.
2. Make an automatic-send digest due and run the digest task.
3. Inspect generated newsletter/history/queue; then resume normal queue processing.

**Expected:** The digest creates a newsletter and normal immutable queue snapshot, then uses the existing queue/task/rate/retry path. Paused means queued, not failed. No parallel mail sender exists.

### PM-212 — Digest recipient targeting and suppression

**Steps:**

1. Target overlapping topics and a Joomla group containing duplicate, unsubscribed, suppressed, and active controlled addresses.
2. Run in draft mode and inspect recipient/preflight data.

**Expected:** Audience is a deduplicated union, while opt-out/suppression/bounce rules win. Digest targeting never creates memberships or reactivates protected addresses.

### PM-213 — Digest schedule and overlap protection

**Steps:**

1. Test a daily or weekly recurrence and set the next run in site timezone.
2. Test a monthly recurrence anchored on the 31st (or another end-of-month date), including a shorter following month.
3. Trigger two task executions as close together as the staging system allows.
4. Inspect newsletters and history.

**Expected:** One due occurrence produces at most one result. Overlapping workers do not duplicate an automatic newsletter/newsletter. Daily and weekly schedules advance predictably. A monthly schedule uses the last valid day in a shorter month and returns to its stored anchor day in later months instead of drifting.

### PM-214 — Digest history and failure recovery

**Steps:**

1. Produce successful draft, no-content, automatic-queue, and controlled failure runs.
2. Open history and follow the generated newsletter link where present.
3. Correct the failure and run again.

**Expected:** Each entry records run time, status, item count, whether/generated newsletter ID, sent/queued/draft/no-content outcome, and sanitized failure details. Passwords/tokens are absent. Recovery does not erase history.

### PM-215 — Digest list lifecycle and checkout

**Steps:**

1. Test search, state filter, sorting, pagination, enable/disable, trash, restore, and delete.
2. Abandon the editor and test Joomla Global Check-in as for topics/templates.

**Expected:** Normal Joomla list and checkout conventions work. Disabled/trashed digests never run. Deleting a definition retains reasonable run/newsletter history or blocks unsafe deletion.

---

## K. Delivery, bounce handling, and mail health

### PM-230 — Backend outgoing mail test

**Steps:**

1. Open **Delivery / Bounces** and send a mail test to a controlled address.
2. Inspect the received message.
3. On staging, cause a configured transport failure and repeat.

**Expected:** Valid mail succeeds through Joomla's transport. Failure is shown in the normal administrator template with a sanitized useful message—not an unstyled 500 page. No newsletter/queue/subscription record is created.

### PM-231 — Return/envelope behaviour inspection

**Prerequisite:** A mail transport/server where envelope sender can be inspected.

**Steps:**

1. Configure a bounce/return address.
2. Send a controlled newsletter and inspect SMTP transaction/server logs or provider metadata plus message headers.

**Expected:** Punga Mail uses Joomla mail-layer capabilities for envelope sender where technically available. It does not pretend that merely adding an ordinary `Return-Path` header controls SMTP routing. Behaviour/limitations are predictable.

### PM-232 — Manual and scheduled mailbox processing

**Steps:**

1. Put one controlled DSN in the bounce mailbox.
2. Click **Process bounces now**.
3. Put another DSN in the mailbox and run **Punga Mail — Check returned mail**.

**Expected:** Both paths use the same rules, report useful counts, and process each message at most once. Mailbox errors are sanitized. The recent-bounces list updates.

### PM-233 — Hard bounce classification and suppression

**Prerequisite:** A controlled delivery-status notification for the test address, for example status `5.1.1` with a permanent “user unknown” diagnostic. Do not send to a random real domain.

**Steps:**

1. Queue/send a controlled mailing so the bounce can identify subscriber and mailing.
2. Deliver the hard-bounce DSN to the configured mailbox and process it.
3. Inspect subscriber detail, bounce history, mailing statistics, and future preflight.

**Expected:** The event is Hard, with timestamp, address, subscriber, mailing if identifiable, status/SMTP code, and diagnostic. That email is immediately suppressed and excluded from future queues. Historical mailing hard-bounce count updates.

### PM-234 — Soft bounce threshold

**Prerequisite:** Controlled DSNs such as `4.2.2` mailbox full, and a low test threshold.

**Steps:**

1. Process one soft DSN and inspect the subscriber.
2. Process distinct subsequent soft DSNs until the threshold is reached.
3. Resolve a future newsletter before and after the threshold.

**Expected:** Each genuine event increments the soft count and records details. Before threshold, policy behaves as documented; at threshold, that address becomes suppressed and is excluded. A duplicate mailbox message does not increment twice.

### PM-235 — Unknown bounce

**Steps:**

1. Process a controlled nonstandard/ambiguous DSN that identifies the address but lacks a reliable permanent/temporary code.
2. Inspect subscriber and history.

**Expected:** It is retained as Unknown/Unclassified with diagnostic data where safe. It is not silently treated as a hard bounce or deleted.

### PM-236 — Bounce association and unmatched events

**Steps:**

1. Process one DSN identifying a known queue recipient/mailing and another controlled DSN for no known subscriber.
2. Inspect recent history and statistics.

**Expected:** Known events associate with the correct email/subscriber and mailing when identifiers permit. Unmatched events do not change an unrelated subscriber; they are reported/retained safely as designed.

### PM-237 — Address-level isolation

**Steps:**

1. Prepare one person represented by separate controlled address sources if the site supports them.
2. Hard-bounce one address only.
3. Resolve future recipients for both.

**Expected:** Suppression is attached to the failed normalized email. A separate valid address is not disabled merely because it belongs to the same person/user context.

### PM-238 — Bounce history privacy and duplicate handling

**Steps:**

1. Process the exact same DSN twice or leave it for a second task run.
2. Search administrator lists/logs and open a public/browser page.

**Expected:** The event is counted once. Necessary diagnostic text is administrator-only and sanitized; mailbox credentials, full raw private message, and subscriber tokens are not exposed publicly or in ordinary logs.

---

## L. Subscriber CSV import and export

Use only artificial test data. Spreadsheet programs can execute formula-like cells, so keep exported test files inside a safe test environment.

### PM-250 — Import preview and field mapping

**Steps:**

1. Prepare a UTF-8 CSV with columns in an unusual order: email, name, status, topics, and an ignored field.
2. Upload it using comma, semicolon, and tab delimiters in separate runs.
3. Map columns but stop at preview.

**Expected:** Delimiter/encoding are interpreted correctly, the first preview rows are legible, Email is required, ignored data is not imported, and preview makes no database changes.

### PM-251 — Import new and existing subscribers

**Steps:**

1. Import one new valid address, one existing address with a changed name, one unchanged row, one malformed address, and one duplicate row.
2. Commit and inspect the report and records.

**Expected:** Added, updated, unchanged, skipped/duplicate, invalid, conflicts, and errors are counted accurately. Normalized duplicates produce one subscriber.

### PM-252 — Topic mapping and membership merge

**Steps:**

1. Map topic/list values for a new and existing subscriber.
2. Give the existing subscriber another topic not present in the CSV.
3. Import using membership merge.

**Expected:** Mapped memberships are added without duplicates, existing unrelated memberships remain, unknown topics are clearly reported rather than silently invented, and topic counts update.

### PM-253 — Protected-state import safety

**Steps:**

1. Import rows marked Active for a globally unsubscribed, bounce-suppressed, hard-bounced, and ordinary active address.
2. Leave **Explicitly reactivate protected addresses** off.
3. Preview, commit, and resolve recipients.

**Expected:** Protected addresses remain protected and are reported as conflicts/skipped; importing topic/name data cannot reactivate them. The ordinary record may update normally.

### PM-254 — Explicit import reactivation

**Steps:**

1. On controlled addresses only, map an explicit Active state and enable the clearly labelled reactivation option.
2. Review preview carefully and commit.

**Expected:** Only rows meeting all explicit conditions are reactivated. The report names the action. It does not clear unrelated bounce history or affect unselected addresses.

### PM-255 — Import limits and malformed files

**Steps:**

1. Try an empty file, missing-email mapping, invalid UTF-8/broken CSV, oversized file, and more rows than the documented limit on staging.
2. Try an interrupted/failed commit if safely reproducible.

**Expected:** Each fails safely with an administrator-friendly message. No partial unknown state, PHP warning, memory dump, credential, or raw stack trace is exposed. Published limits (5 MiB/20,000 rows where shown) are enforced.

### PM-256 — Export subsets and columns

**Steps:**

1. Export All, Active, Unsubscribed, Suppressed/Bounced, and members of selected topic(s).
2. Open each as UTF-8 CSV and compare records with administrator filters.

**Expected:** Each subset is accurate and deduplicated. Useful name, email, topics, effective state, and relevant suppression/bounce information are present without secret tokens.

### PM-257 — CSV escaping and formula-injection protection

**Steps:**

1. On staging, use names/topic titles containing comma, semicolon, quotes, line breaks, non-ASCII German characters, and leading `=`, `+`, `-`, or `@`.
2. Export and open in a text editor first, then a safe spreadsheet environment.

**Expected:** UTF-8 and CSV quoting preserve the data. Formula-like cells are neutralized (for example with a leading apostrophe) and do not execute. Import/export does not corrupt umlauts.

### PM-258 — Cancel an import preview

**Steps:**

1. Upload a valid CSV and reach the mapping/preview step.
2. Click Cancel.
3. Reopen Import/Export and verify that the old preview cannot be committed accidentally.

**Expected:** The pending preview is cleared, no subscriber changes occur, and a later commit requires a newly uploaded preview.

---

## M. Joomla Scheduled Tasks and reminders

### PM-270 — Task types are installed and translated

**Steps:**

1. Open **System → Manage → Scheduled Tasks → New**.
2. Search for Punga Mail task types.

**Expected:** Exactly the expected types are discoverable and translated: Send pending newsletters, Newsletter reminder, Prepare scheduled newsletters, Create automatic newsletters, and Check returned mail. The description for **Prepare scheduled newsletters** explicitly says it handles ordinary newsletters manually scheduled for a specific date/time; **Create automatic newsletters** explicitly says it handles recurring Automatic Newsletters.

### PM-271 — Send-queue task

**Steps:**

1. Create/enable **Punga Mail — Send pending newsletters** at a safe interval.
2. Queue a controlled mailing, resume the queue, and run the task.
3. Inspect Joomla task history and Punga Mail statistics.

**Expected:** It processes only an allowed batch, reports success/failure accurately, uses configured retry logic, and does not overlap into duplicate delivery.

### PM-272 — Scheduled-newsletter task

Follow PM-179 and additionally verify Joomla task last-run/next-run/history values.

**Expected:** Task result agrees with Punga Mail status and handles “nothing due” as a normal successful no-op.

### PM-273 — Automatic-digest task

Follow PM-201, PM-208, and PM-213 using **Punga Mail — Create automatic newsletters**.

**Expected:** Task history distinguishes generated, no-content, and failed runs without exposing private data. A 5–15 minute task interval is accepted; the digest's own recurrence decides whether it is due.

### PM-274 — Bounce-mailbox task

Follow PM-232 using **Punga Mail — Check returned mail**.

**Expected:** “No new mail” is a clean no-op. Connection/parse errors are useful and sanitized. Repeated runs are idempotent.

### PM-275 — Newsletter reminder task

**Prerequisite:** Reminder settings from PM-020 and a staging database where dates can be prepared safely.

**Steps:**

1. Set the last successful newsletter inside the threshold and run the task.
2. Set it older than the threshold and run again.
3. Run repeatedly without a newer sent newsletter.
4. Mark/send a newer controlled newsletter and repeat after preparing its threshold.

**Expected:** No early reminder is sent. One reminder is sent after threshold with correct placeholders. Repeated task runs do not flood daily reminders for the same sent-newsletter cycle. A new sent cycle can generate a later reminder.

### PM-276 — Task overlap and stale-worker recovery

**Prerequisite:** Staging only; use controlled task concurrency or a deliberately interrupted worker.

**Steps:**

1. Trigger overlapping queue/digest task attempts.
2. Interrupt a queue worker after claiming work, then wait/configure the documented stale recovery interval and run again.

**Expected:** Database-level claiming/uniqueness prevents duplicate recipients or digests. Recoverable stale work eventually continues without resending rows already marked handed off.

---

### 0.4.0 focused acceptance

- Create Channels for **Everyone**, **Registered users**, and selected **Manager / Administrator** Joomla groups. Verify guests see only Everyone, ordinary registered users cannot save group-only memberships, eligible administrators can subscribe, and removing the Joomla group prevents that Channel from resolving at send time.
- Create a new Newsletter and verify no audience source is selected by default. Preflight must block until an audience is chosen. Existing newsletters must retain their stored audience.
- Exercise the Markdown toolbar, placeholder menu, collapsible help, CodeMirror source editor (when available), and Punga-renderer Preview in Newsletter, Template, confirmation/reminder and selected-content-layout fields.
- Generate an Automatic Newsletter in draft mode with review notification enabled. Verify the configured reviewer receives the draft link and counts; repeat in automatic-send mode and verify no review notification is sent.
- Export subscribers with zero, one, and at least two selected Channels. Each CSV download must complete without a prepared-statement binding error and the multi-Channel result must use OR membership semantics.
- Send/preview a newsletter with mail-heading background and confirm the background spans the full content container while the heading text remains aligned to the configured body padding.
- Review the Dashboard with healthy data and with a deliberately missing Scheduled Task/failed queue row; only actionable warnings should appear.

## N. ACL, CSRF, privacy, language, and regression sweep

### PM-290 — Administrator ACL

**Steps:**

1. Log in as Test Manager and verify only actions granted in Joomla permissions.
2. Log in as Test Restricted and try direct URLs for lists, edit/save, send, import, export, clear suppression, queue controls, bounce processing, and Options.

**Expected:** Joomla ACL is enforced both in the interface and controller. Hiding a button is not the only protection. Unauthorized direct requests make no changes and reveal no private data.

### PM-291 — CSRF protection

**Steps:**

1. In staging developer tools, repeat state-changing administrator and frontend requests with the Joomla form token removed or altered.
2. Include save, publish, delete, subscribe, unsubscribe POST, import commit, send, queue pause/cancel, and suppression clearing where safely possible.

**Expected:** Requests fail safely and make no change. Valid forms still work. GET requests do not perform administrator destructive actions.

### PM-292 — Secret and private-data exposure audit

**Steps:**

1. Search rendered page source, browser network responses, received mail, browser newsletter, Joomla messages/logs, and CSV exports for bounce passwords, mailbox usernames where inappropriate, confirmation/unsubscribe tokens, full subscriber tokens, and backend-only fields.
2. Trigger one controlled error in each mail/bounce/import area.

**Expected:** Passwords and credentials are absent; stored password is never echoed. Tokens appear only in the specific secure subscriber action URL that requires them, never in browser archive/log/error output. Errors are sanitized.

### PM-293 — Frontend authorization and draft enumeration

**Steps:**

1. Logged out, try direct component URLs for administrator actions, subscriber detail, newsletter IDs, digest IDs, queue rows, CSV, and draft browser views.
2. Vary numeric IDs and invalid random browser keys.

**Expected:** Private resources remain unavailable; responses do not reveal existence, email, status, or content of private records. Only intended public subscription/token/sent-browser endpoints work.

### PM-294 — Content output safety

**Steps:**

1. Repeat PM-106 in template, newsletter, imported text where applicable, selected-content title/excerpt override, email, and browser view.
2. Test malformed HTML and dangerous URL schemes.

**Expected:** No executable injection reaches administrator preview, outgoing HTML, plain text, or frontend browser page. Safe formatting remains intact.

### PM-295 — Consent cannot be bypassed by alternate audience sources

**Steps:**

1. Globally unsubscribe and separately suppress controlled Joomla users who belong to a targeted group and topic.
2. Target all-confirmed, their topic, their Joomla group, and a digest.

**Expected:** Every route excludes protected addresses with the correct reason. Group membership, topic membership, profile save, CSV, digest, and duplicate email cannot bypass consent/suppression.

### PM-296 — English and German interface/mail strings

**Steps:**

1. Switch administrator and site language between English and German.
2. Visit every Punga Mail page, module mode, profile field, menu-item type, task type, error/empty state, confirmation/unsubscribe page, and generated standard mail text.
3. Search visible output for `COM_PUNGAMAIL_`, `MOD_PUNGAMAIL_`, and `PLG_...` keys.

**Expected:** Natural English/German strings appear; no raw language key is visible. Subscriber-facing standard strings use the site/frontend language catalog even when mail is generated by administrator or Scheduled Tasks.

### PM-297 — Website language overrides

**Steps:**

1. Add a Joomla Website language override for a standard Punga Mail mail string such as footer reason, unsubscribe, or read-more.
2. Generate preview, test mail, queued mail via task, digest mail, and browser view.

**Expected:** The appropriate website-language override is honored consistently in every generation context.

### PM-298 — Light/dark administrator themes and responsive UI

**Steps:**

1. Use Atum light and dark modes where available.
2. Inspect all lists, forms, toolbars, notices, preflight tables, recipient detail, digest history, and trashed rows at desktop and narrow viewport widths.

**Expected:** Text/actions remain legible and reachable. Punga Mail follows Joomla styling without hard-coded colours breaking state rows. Routine composition is not obscured by advanced areas.

### PM-299 — Error handling and recovery

**Steps:**

1. Cause controlled invalid inputs, missing content, mail transport failure, mailbox failure, malformed CSV, and concurrent lock conditions.
2. Correct each issue and retry.

**Expected:** No raw exception, SQL, filesystem path, credential, or unstyled 500 page is exposed. Messages are actionable; recovery succeeds without reinstalling or duplicating data.

### PM-300 — End-to-end manual newsletter regression

**Steps:**

1. Subscribe a new guest through email confirmation and choose a Channel.
2. Compose from a template, select registered content, apply local overrides, preview, send test, inspect preflight/recipients, schedule or queue, process through Scheduled Tasks, inspect received HTML/text/headers/browser view/statistics, then unsubscribe.

**Expected:** The entire chain works once, respects access/consent, uses an immutable snapshot, and excludes the subscriber from a later mailing.

### PM-301 — End-to-end Joomla user/profile regression

**Steps:**

1. Register a Joomla user, set Receive newsletters and topics in profile, target by topic plus Joomla group, and send a controlled newsletter.
2. Change one topic, save profile, and resolve the next newsletter.

**Expected:** One canonical address/recipient is used, profile saves correctly, deduplication works, personalization uses Joomla display name, and topic preference changes affect only future audience resolution.

### PM-302 — End-to-end digest regression with access levels

**Steps:**

1. Run the mixed-access draft test, confirm restricted content is absent, then run an automatic public-only digest through queue delivery.
2. Inspect history, newsletter, recipient snapshot, received content, browser view, and statistics.

**Expected:** Generic content discovery works, access fails closed, no item repeats under since-last-success mode, and delivery uses the normal immutable queue.

### PM-303 — Existing feature regression checklist

Complete this final checklist after all tests:

- existing subscribers, IDs, global states, topic memberships, and bounce history remain intact;
- email confirmation and human/RFC 8058 unsubscribe work;
- signup module, Joomla-user integration, profile topic selector, and Joomla group targeting work;
- template apply/copy semantics and design hierarchy work;
- Markdown, HTML/plain text, images, tables, custom CSS, `{recipient}`, and `{new_content}` render correctly;
- registered content types, filters, selected-content persistence, ordering/overrides, and migrated article selections work;
- preview and test send are non-destructive;
- preflight blocks real errors and explains exclusions;
- queue batch/rate/retry settings, manual processing, Scheduled Tasks, pause/resume/cancel, and stale recovery work;
- scheduled sending, digest automation/history/access checks, and empty handling work;
- SEF routes and immutable sent browser snapshots work;
- statistics remain tied to the historic mailing;
- import/export preserves protected consent states;
- bounce handling suppresses at address level and retains history;
- English and German strings contain no visible untranslated keys.

**Expected:** Every applicable item has a corresponding passed test and recorded evidence. Any failure is filed before enabling unattended Scheduled Tasks or broad live audiences.

---


### 0.4.1 focused acceptance — restricted Channel persistence

1. Open **Punga Mail → Channels** and create a new Channel.
2. Set **Who can subscribe?** to **Selected Joomla user groups**.
3. Select at least **Manager**, **Administrator**, and **Super Users**.
4. Save the Channel.
5. Confirm Joomla reports a successful save with no database exception.
6. Reopen the Channel and confirm all selected user groups are still checked.
7. Change the selection (remove one group and add another), save, reopen, and verify the edited selection persists exactly.
8. Change **Who can subscribe?** to **Everyone**, save, reopen, and confirm the old group relation no longer affects eligibility.
9. Repeat with **Registered users**.

**Expected:** Channel metadata and group restrictions save as one atomic operation. No partially saved Channel is left behind if relation persistence fails, and selected groups survive create/edit/reload correctly.

## Final live-site release gate

Do not enable routine live sending until all of the following are true:

1. There are no open **Critical** failures involving consent, suppression, access permissions, tokens, credentials, recipient resolution, duplicate sending, or immutable snapshots.
2. Installation/update and Joomla Database checks are clean.
3. A controlled end-to-end manual mailing and digest run have passed.
4. The global and per-mailing pause/cancel controls have been demonstrated.
5. Required Scheduled Tasks exist, run under cron, and have clean history.
6. Bounce mailbox connection and a controlled DSN test have passed if bounce processing will be enabled.
7. Sender, Reply-To, List-ID, unsubscribe headers, SEF URLs, and browser view have been inspected from a real received message.
8. The queue is still limited to the private test topic until the administrator deliberately selects a production audience.

After approval, restore production-safe batch/rate/retry values, remove or unpublish test content/topics/module positions, delete only disposable test records, resume the queue deliberately, and monitor the first production task runs and delivery statistics.

## Related documentation

- [Administrator User Guide](USER_GUIDE.md)
- [Newsletter Tutorial](TUTORIAL_NEWSLETTER.md)
- [Digest Tutorial](TUTORIAL_DIGEST.md)
- [Topics and Signup Tutorial](TUTORIAL_TOPICS_AND_SIGNUP.md)
- [Templates Tutorial](TUTORIAL_TEMPLATES.md)
- [Delivery Health Tutorial](TUTORIAL_DELIVERY_HEALTH.md)
- [Import/Export Tutorial](TUTORIAL_IMPORT_EXPORT.md)


### 0.4.2 focused acceptance — Automatic Newsletter timezone and time picker

1. Set Joomla's site timezone to a zone that differs from UTC, for example **Europe/Berlin** during daylight-saving time.
2. Create or edit an Automatic Newsletter and open **Next run**. Confirm Joomla's calendar control offers both date selection and a **24-hour time picker**.
3. Choose a future date and set the time to **10:00**, then save.
4. Reopen the Automatic Newsletter. Confirm the editor still shows **10:00**.
5. Return to the **Automatic Newsletters** list. Confirm **Next run** shows **10:00**, not the corresponding UTC value (for example 08:00 during CEST).
6. Open the Punga Mail Dashboard. Confirm the same Automatic Newsletter also shows **10:00** there.
7. If a normal Newsletter is manually scheduled, confirm its scheduled time is likewise displayed in the Joomla site timezone in administrator views.
8. Confirm the Scheduled Task still evaluates due Automatic Newsletters correctly; the database representation remains UTC.


### 0.4.3 focused acceptance — Scheduled Tasks and unsubscribe

1. Open Joomla **System → Scheduled Tasks** and verify **Punga Mail — Send pending newsletters** exists and is enabled/configured.
2. Queue a small newsletter, wait for the task to run, and verify at least one pending recipient is delivered without using **Send pending mail now**.
3. Run the task manually from Joomla Scheduled Tasks and verify its execution status is successful and no plugin-construction/type error is logged.
4. Open the HTTPS URL from the newsletter's `List-Unsubscribe` header in a normal browser using GET. Verify it opens the normal unsubscribe confirmation page and does **not** unsubscribe immediately.
5. Submit that confirmation form and verify the recipient is unsubscribed.
6. With a standards-capable mail client/provider, trigger its one-click unsubscribe action and verify the RFC 8058 POST returns successfully without a redirect.
7. Verify an invalid unsubscribe token is rejected and cannot unsubscribe a recipient.

### 0.4.4 focused acceptance — editor workflow, queue visibility, and Markdown controls

1. Open the Dashboard with at least one manually Scheduled Newsletter and one enabled Automatic Newsletter. Confirm both appear distinctly in the upcoming summary with their correct site-timezone dates.
2. Open a Subscriber with one or more Channels selected. Confirm the “no Channels selected” note is hidden; clear all Channel checkboxes and confirm it appears immediately, then select one and confirm it disappears again.
3. Open **Delivery / Bounces** with queued, failed, and sent test rows. Filter by state, Newsletter, and recipient search; verify attempts/timestamps/errors match the underlying queue.
4. Select a failed row and use **Retry selected**. Confirm it returns to the normal pending/retry path. Select a pending/failed unsent row and use **Cancel selected**. Confirm processing/sent rows cannot be cancelled by that action.
5. Open a Template and Newsletter Markdown editor. Confirm no CodeMirror line-number gutter is shown. Use **Table** and confirm a starter Markdown pipe table is inserted. Use **Image**, select an image through Joomla's media picker, and confirm Markdown image syntax is inserted.
6. Open Newsletters and Templates lists and confirm neither displays a meaningless always-Active Status column.
7. Open a Template and confirm its message settings appear in the right sidebar without an artificial publish state. Open a Newsletter and confirm lifecycle status, Template selection, and scheduling controls appear in its right sidebar.
8. Schedule a valid Draft directly from the Newsletter sidebar, reschedule it, then cancel the schedule. Confirm the state returns to Draft. Try scheduling an invalid Newsletter and confirm blocking Preflight/sendability validation prevents it.
9. Duplicate a Draft and a sent Newsletter from the top toolbar, then bulk-duplicate multiple selected rows in the Newsletters list. Confirm all copies are independent Drafts and sources remain untouched.

**Expected:** The 0.4.4 administrator workflow behaves consistently with Joomla conventions, exposes the real queue safely, and preserves Punga Mail's existing validation and immutable-send semantics.

### 0.4.5 focused acceptance — Markdown media insertion and authoring layout

1. Open a Newsletter and confirm **Template** plus **Apply template** appear on **Mail content**, not in the right sidebar. Apply a Template while on that tab and verify subject/body changes are immediately visible.
2. In any Punga Mail Markdown editor, verify the **Insert table** and **Insert image** toolbar buttons are visually distinguishable by icon and label. Insert a table and confirm the starter Markdown table is written at the cursor.
3. Click **Insert image**, choose an image in Joomla Media Manager, click **Select**, provide or accept alt text, and verify `![alt](images/...)` is inserted at the current editor cursor/selection. Repeat with the same image to ensure the hidden media field is reset after insertion.
4. Schedule a Newsletter, open Preflight, and verify the **Schedule send** date/time field contains the existing scheduled time in the Joomla site timezone.
5. On the Dashboard, verify **Templates** appears next to **Channels** in Quick Actions and opens the Templates list.
6. Open a Subscriber with no bounce history or active delivery suppression and verify no delivery-health card is shown. Open a Subscriber with bounce history and verify the card is shown with plain-language permanent/temporary failure wording. For an active bounce-based suppression, verify **Allow delivery again** is available.

**Expected:** The 0.4.5 changes make Template application contextual, Media Manager image insertion reliable, scheduling state consistent between editor and Preflight, and Subscriber diagnostics visible only when useful.

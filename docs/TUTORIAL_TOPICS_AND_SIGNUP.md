# Tutorial: Set Up Topics and Frontend Signup

This tutorial creates public mailing choices and a signup experience that lets visitors subscribe globally, choose topics, adjust individual preferences, or unsubscribe completely.

## The behavior you are setting up

Punga Mail treats these as separate choices:

- **Receive newsletters globally** — the address is allowed to receive Punga Mail;
- **Topic preferences** — the kinds of newsletters the subscriber wants.

Removing one topic does not turn newsletter reception off. **Stop all newsletters** is the global action.

## 1. Plan a small topic set

Use topics for choices subscribers understand. Good examples are:

- News;
- Events;
- Development Updates.

Avoid creating many overlapping topics at first. Remember that a newsletter selecting several topics sends to members of **any** selected topic, with duplicate addresses removed.

Topics are not Joomla user groups. Joomla groups describe website accounts and access; Punga Mail topics describe mail preferences.

## 2. Create the topics

Go to **Components → Punga Mail → Channels** and select **New**.

For each topic, enter:

- **Title**: the visitor-facing name;
- **Alias**: a stable internal name, or leave blank to generate it;
- **Description**: a short explanation shown when the module offers multiple topics;
- **Ordering**: a number controlling display order.

Select **Save & Close**.

New topics are published by default. On the Topics list you can Publish, Unpublish, Trash, Restore, or Delete them using normal Joomla list controls.

An unpublished Channel is unavailable for new public choices but its stored relationships are retained. Trashing is reversible and also preserves those relationships. Permanently deleting a trashed Channel removes its subscriber memberships, pending preference actions, and Newsletter/Automatic Newsletter Channel assignments after an explicit warning; the related subscriber/campaign records themselves are not deleted.

## 3. Create the subscription menu item

Go to Joomla's Menu manager:

1. create a new menu item;
2. choose **Punga Mail → Newsletter subscription** as the menu-item type;
3. give it a title such as `Newsletter preferences`;
4. keep it Published;
5. save it.

It may be placed in a hidden menu if you do not want a visible navigation link.

This item provides a complete frontend subscription destination and gives Joomla a stable SEF route for subscription, confirmation, unsubscribe, status, and browser-view links. Give it Public access so ordinary email recipients can use those links.

The page always offers all published topics. Guests choose topics while requesting email confirmation; logged-in users see their current choices and save them independently of the separate global Subscribe/Unsubscribe action. A signup module is therefore optional when this standalone page is sufficient for your site.

## 4. Publish the signup module

Go to **Content → Site Modules** (or the module manager in your Joomla installation), create a **Punga Mail Signup** module, and configure the ordinary Joomla fields:

- Title and Show Title;
- Position;
- Status: Published;
- Menu Assignment;
- Access.

Then configure the Punga Mail fields:

| Field | Suggested use |
| --- | --- |
| Intro text | Explain the value and frequency of the messages. Example: `Choose the updates you would like to receive.` |
| Button label | Leave blank for the translated Subscribe label, or use a clear alternative such as `Request subscription`. |
| Topics | Choose how broad this particular module should be, as described below. |

## 5. Choose a module topic mode

### No topics selected in module settings

The module offers **all currently published topics**. Use this on a general newsletter-preferences page.

If topics are published later, they automatically appear in this module.

### Exactly one topic selected

The module hides the topic selector, names the configured topic, and provides a direct subscribe/unsubscribe-topic action. Use this beside content dedicated to one topic, such as an Events page.

### Multiple topics selected

The module shows only those configured topics. Use this when one part of the site should offer a curated subset.

For logged-in users, saving this module changes only the topics visible in that module. Other topic memberships remain intact.

## 6. Test as a logged-out visitor

Open the page in a private browser window.

You should see:

- the optional intro;
- an email field;
- one named topic, several checkboxes, or no choices if no topics currently exist;
- the Subscribe button.

Submit a test address and topic choices. Punga Mail sends a confirmation email; nothing becomes active until the recipient selects the confirmation link.

Confirm that:

- the message uses your chosen subject/body or translated default;
- the link reaches the SEF Punga Mail page;
- confirmation activates the global subscription and requested topics;
- the subscriber appears as Active in the backend.

The hidden anti-spam field, IP rate limit, confirmation expiry, and resend interval work without visitor configuration.

## 7. Test as a logged-in user

Sign in with a Joomla user and return to the module.

The module uses the account email and shows:

- the email address;
- newsletter choices for topics exposed by this module;
- the global subscription state;
- a clear **Start receiving newsletters** or **Stop all newsletters** master action.

With one visible topic, use the direct topic button. With several topics, check/uncheck choices and select **Save newsletter choices**.

Then test the global action separately:

- **Unsubscribe from topic** or unchecking one topic leaves other memberships and the global state intact.
- **Stop all newsletters** globally opts out the address and prevents all future newsletters, including Joomla-group targeting.

## 8. Test the Joomla profile fields

The Punga Mail user plugin adds a **Newsletter** fieldset to Joomla registration, frontend profile editing, administrator user editing, and the administrator's own profile.

It contains:

- **Receive newsletters: Yes/No** for the master permission;
- **Channels** for all currently published Channel choices.

Sign in as a test user, open the Joomla profile, enable Receive newsletters, select one or more topics, and save. Reopen the profile and confirm that the choices remain selected. The same memberships should also appear selected in a Punga Mail Signup module that exposes those topics.

Now set Receive newsletters to No and save. The topic choices remain stored, but the global unsubscribe prevents every delivery. Setting it to Yes later restores global eligibility without forcing the user to rebuild their topic choices.

Clearing a topic marks only that published topic unsubscribed. Unpublished topic relationships are not shown or changed by the form.

Do not present Receive newsletters as though Yes automatically chooses every topic. A user can have newsletter reception enabled and belong to zero topics; that user can still receive a newsletter deliberately sent to All globally subscribed recipients, regardless of topic, or an eligible Joomla group.

The signup module is therefore optional for registered-user topic management, but remains necessary for convenient inline preferences and email-only public signup.

## 9. Target a topic newsletter correctly

When composing a newsletter for Events only:

1. clear **All globally subscribed recipients, regardless of topic**;
2. select **Events** under Topics;
3. leave unrelated Joomla groups unselected;
4. run Preflight;
5. inspect included and excluded recipients.

If All globally subscribed recipients remains selected, the mailing is not restricted to Events.

## 10. Common layouts

### One general preferences page

- one Published subscription menu item;
- one module with no topics selected;
- intro explains that visitors can choose any topic.

### Topic signup beside content

- one module configured for exactly one topic;
- publish it only on the relevant pages;
- keep a separate general preferences module/page for managing everything.

### Different topic sets in different site areas

- configure multiple modules;
- each module selects its allowed topic subset;
- logged-in changes affect only the visible subset, preserving other memberships.

## Safety checks

- Keep the confirmation link available to Public users.
- Do not globally reactivate an unsubscribed or suppressed address without consent.
- Use topic unsubscribe for preference changes and global unsubscribe only when the person wants all Punga Mail stopped.
- Keep aliases stable for CSV import/export.
- Test both logged-out email confirmation and logged-in preference management.
- Use Preflight to verify real audience results before sending.

For the complete reference, see [Punga Mail Administrator Guide](USER_GUIDE.md).

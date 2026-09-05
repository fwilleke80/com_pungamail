# Punga Mail

Punga Mail is a self-hosted newsletter extension for **Joomla 6**.

It is designed for websites that want to manage newsletters directly inside Joomla, using the site's existing users, content, permissions and mail configuration instead of relying on an external newsletter service.

## What Punga Mail can do

- **Create and send newsletters** from the Joomla administrator.
- Write mail content in **Markdown** with a formatting toolbar, placeholders and preview.
- Use reusable **Templates** for newsletter content and design.
- Let subscribers choose **Channels** so they receive only the newsletters they are interested in.
- Restrict Channels to registered users or selected Joomla user groups when needed.
- Add new Joomla content to newsletters with **`{new_content}`**, including selected/available item management, drag-and-drop ordering, configurable item layouts, excerpts and publication dates.
- Create **Automatic Newsletters** on daily, weekly or monthly schedules, with content ordering/limits, run history, and either draft-for-review or automatic-send workflows.
- Schedule ordinary newsletters for delivery at a later date and time.
- Manage Joomla users and external email subscribers from one recipient list.
- Provide public subscribe, unsubscribe and preference-management pages.
- Use email confirmation for new external subscriptions.
- Handle undeliverable mail and stop repeatedly sending to addresses that can no longer be reached.
- Show delivery statistics, recent activity, upcoming mail and other useful status information on the Dashboard.
- Import and export recipient data as CSV.
- Generate browser-view and unsubscribe links for newsletters.
- Use Joomla's language system; Punga Mail currently includes **English and German**.

Punga Mail uses Joomla's configured mail transport, so SMTP and other mail settings remain in Joomla rather than being duplicated inside the extension.

## Installation

Install the Punga Mail package through:

**System → Install → Extensions**

The package installs the Punga Mail component together with its signup module and the Joomla plugins used for user-profile integration and Scheduled Tasks.

For unattended sending and Automatic Newsletters, enable the appropriate Punga Mail tasks under Joomla's **Scheduled Tasks** manager.

## Documentation

Detailed documentation lives in [`docs/`](docs/):

- [`USER_GUIDE.md`](docs/USER_GUIDE.md) — administrator guide
- [`TUTORIAL_NEWSLETTER.md`](docs/TUTORIAL_NEWSLETTER.md) — creating and sending newsletters
- [`TUTORIAL_DIGEST.md`](docs/TUTORIAL_DIGEST.md) — Automatic Newsletters
- [`TUTORIAL_TOPICS_AND_SIGNUP.md`](docs/TUTORIAL_TOPICS_AND_SIGNUP.md) — Channels and subscriptions
- [`TUTORIAL_TEMPLATES.md`](docs/TUTORIAL_TEMPLATES.md) — Templates and mail design
- [`TUTORIAL_DELIVERY_HEALTH.md`](docs/TUTORIAL_DELIVERY_HEALTH.md) — delivery and undeliverable mail
- [`TUTORIAL_IMPORT_EXPORT.md`](docs/TUTORIAL_IMPORT_EXPORT.md) — CSV import and export
- [`TEST_GUIDE.md`](docs/TEST_GUIDE.md) — live acceptance and regression testing
- [`CONCEPT.md`](docs/CONCEPT.md) — product architecture and design principles
- [`DATABASE.md`](docs/DATABASE.md) — database and migration details

## License

Punga Mail is released under the **MIT License**. See [`LICENSE.md`](LICENSE.md).

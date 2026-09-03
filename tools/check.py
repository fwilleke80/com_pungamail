#!/usr/bin/env python3
"""Static release checks for Punga Mail."""

from __future__ import annotations

import hashlib
import re
import shutil
import subprocess
import sys
import xml.etree.ElementTree as ET
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
VERSION = "0.3.4"

REQUIRED_FILES: tuple[str, ...] = (
    "README.md",
    "LICENSE.md",
    "CHANGELOG.md",
    "docs/DATABASE.md",
    "docs/CONCEPT.md",
    "docs/USER_GUIDE.md",
    "docs/TEST_GUIDE.md",
    "docs/TUTORIAL_NEWSLETTER.md",
    "docs/TUTORIAL_DIGEST.md",
    "docs/TUTORIAL_TOPICS_AND_SIGNUP.md",
    "docs/TUTORIAL_TEMPLATES.md",
    "docs/TUTORIAL_DELIVERY_HEALTH.md",
    "docs/TUTORIAL_IMPORT_EXPORT.md",
    "package/pkg_pungamail.xml",
    "package/script.php",
    "extensions/com_pungamail/pungamail.xml",
    "extensions/com_pungamail/components/com_pungamail/tmpl/subscription/default.xml",
    "extensions/com_pungamail/components/com_pungamail/src/Service/Router.php",
    "extensions/com_pungamail/administrator/components/com_pungamail/src/Service/ContentTypeService.php",
    "extensions/com_pungamail/administrator/components/com_pungamail/src/Service/AdministratorRoute.php",
    "extensions/com_pungamail/administrator/components/com_pungamail/src/Service/RecipientName.php",
    "extensions/com_pungamail/administrator/components/com_pungamail/src/Service/ErrorMessage.php",
    "extensions/com_pungamail/administrator/components/com_pungamail/src/Service/MailStyleService.php",
    "extensions/com_pungamail/administrator/components/com_pungamail/src/Service/MailTextService.php",
    "extensions/com_pungamail/administrator/components/com_pungamail/src/Field/MailfooterField.php",
    "extensions/com_pungamail/administrator/components/com_pungamail/src/Field/BouncemailboxField.php",
    "extensions/com_pungamail/administrator/components/com_pungamail/src/Service/ReminderService.php",
    "extensions/com_pungamail/administrator/components/com_pungamail/src/Service/TemplateRepository.php",
    "extensions/com_pungamail/administrator/components/com_pungamail/sql/install.mysql.sql",
    "extensions/com_pungamail/administrator/components/com_pungamail/sql/updates/mysql/0.1.0.sql",
    "extensions/com_pungamail/administrator/components/com_pungamail/sql/updates/mysql/0.1.1.sql",
    "extensions/com_pungamail/administrator/components/com_pungamail/sql/updates/mysql/0.2.0.sql",
    "extensions/com_pungamail/administrator/components/com_pungamail/sql/updates/mysql/0.2.1.sql",
    "extensions/com_pungamail/administrator/components/com_pungamail/sql/updates/mysql/0.2.2.sql",
    "extensions/com_pungamail/administrator/components/com_pungamail/sql/updates/mysql/0.2.3.sql",
    "extensions/com_pungamail/administrator/components/com_pungamail/sql/updates/mysql/0.2.4.sql",
    "extensions/com_pungamail/administrator/components/com_pungamail/sql/updates/mysql/0.2.5.sql",
    "extensions/com_pungamail/administrator/components/com_pungamail/sql/updates/mysql/0.2.6.sql",
    "extensions/com_pungamail/administrator/components/com_pungamail/sql/updates/mysql/0.3.0.sql",
    "extensions/com_pungamail/administrator/components/com_pungamail/sql/updates/mysql/0.3.1.sql",
    "extensions/com_pungamail/administrator/components/com_pungamail/sql/updates/mysql/0.3.2.sql",
    "extensions/com_pungamail/administrator/components/com_pungamail/sql/updates/mysql/0.3.3.sql",
    "extensions/com_pungamail/administrator/components/com_pungamail/sql/updates/mysql/0.3.4.sql",
    "extensions/com_pungamail/administrator/components/com_pungamail/src/Service/BounceService.php",
    "extensions/com_pungamail/administrator/components/com_pungamail/src/Service/DigestService.php",
    "extensions/com_pungamail/administrator/components/com_pungamail/src/Service/CheckoutService.php",
    "extensions/com_pungamail/administrator/components/com_pungamail/src/Service/PreflightService.php",
    "extensions/com_pungamail/administrator/components/com_pungamail/src/Service/TopicRepository.php",
    "extensions/com_pungamail/administrator/components/com_pungamail/src/Service/CsvService.php",
    "extensions/com_pungamail/administrator/components/com_pungamail/forms/subscriber.xml",
    "extensions/com_pungamail/administrator/components/com_pungamail/src/Model/SubscriberModel.php",
    "extensions/com_pungamail/administrator/components/com_pungamail/src/View/Subscriber/HtmlView.php",
    "extensions/com_pungamail/administrator/components/com_pungamail/tmpl/subscriber/default.php",
    "tools/test_markdown.php",
    "tools/test_newsletter_renderer.php",
    "tools/test_mail_language.php",
    "extensions/mod_pungamail_signup/mod_pungamail_signup.xml",
    "extensions/plg_user_pungamail/pungamail.xml",
    "extensions/plg_task_pungamail/pungamail.xml",
)


def fail(message: str) -> None:
    """Terminate the checker with a clear failure message.

    @param message Human-readable failure description.
    """

    raise RuntimeError(message)


def check_required_files() -> None:
    """Verify mandatory release files."""

    for relative in REQUIRED_FILES:
        if not (ROOT / relative).is_file():
            fail(f"Missing required file: {relative}")


def check_administrator_documentation() -> None:
    """Ensure administrator documentation covers the release's public workflows."""

    guide = (ROOT / "docs/USER_GUIDE.md").read_text(encoding="utf-8")
    readme = (ROOT / "README.md").read_text(encoding="utf-8")
    required_guide_sections = (
        "## Dashboard",
        "## Component Options",
        "## Topics / Lists",
        "## Subscribers",
        "## Templates",
        "## Newsletters",
        "## Automatic Digests",
        "## Delivery / Bounces",
        "## Subscriber Import / Export",
        "## Frontend signup module",
        "## Joomla user-profile integration",
        "## Newsletter subscription menu item",
        "## Scheduled Tasks",
        "## Editor locks and Global Check-in",
    )

    for section in required_guide_sections:
        if section not in guide:
            fail(f"Administrator guide is missing required section: {section}")

    for token in (
        "every resolved recipient would normally be allowed to view",
        "Newsletter topics | Multi-select containing all currently published topics.",
        "Password | Mailbox password. An existing password is never shown.",
        "Punga Mail — Generate automatic digests",
        "Explicitly reactivate protected addresses",
    ):
        if token not in guide:
            fail(f"Administrator guide is missing required safety guidance: {token!r}")

    test_guide = (ROOT / "docs/TEST_GUIDE.md").read_text(encoding="utf-8")
    required_test_sections = (
        "## A. Installation, update, navigation, and dashboard",
        "## B. Component Options and diagnostics",
        "## C. Topics / Lists",
        "## D. Subscribers and consent state",
        "## E. Frontend module, confirmation, unsubscribe, and Joomla profile",
        "## F. Templates and rendering",
        "## G. Newsletter composition and selected content",
        "## H. Preview, test mail, preflight, and recipient inspection",
        "## I. Queue, scheduled sending, snapshots, browser view, and statistics",
        "## J. Automatic digests",
        "## K. Delivery, bounce handling, and mail health",
        "## L. Subscriber CSV import and export",
        "## M. Joomla Scheduled Tasks and reminders",
        "## N. ACL, CSRF, privacy, language, and regression sweep",
    )

    for section in required_test_sections:
        if section not in test_guide:
            fail(f"Test guide is missing required section: {section}")

    for token in (
        "PM-205 — Critical access-permission test: mixed recipients",
        "every resolved recipient",
        "PM-253 — Protected-state import safety",
        "PM-291 — CSRF protection",
        "PM-303 — Existing feature regression checklist",
    ):
        if token not in test_guide:
            fail(f"Test guide is missing required coverage: {token!r}")

    if "[`docs/TEST_GUIDE.md`](docs/TEST_GUIDE.md)" not in readme:
        fail("README does not link the live acceptance test guide")

    if "](TEST_GUIDE.md)" not in guide:
        fail("Administrator guide does not link the live acceptance test guide")

    tutorial_names = (
        "TUTORIAL_NEWSLETTER.md",
        "TUTORIAL_DIGEST.md",
        "TUTORIAL_TOPICS_AND_SIGNUP.md",
        "TUTORIAL_TEMPLATES.md",
        "TUTORIAL_DELIVERY_HEALTH.md",
        "TUTORIAL_IMPORT_EXPORT.md",
    )

    for name in tutorial_names:
        if f"]({name})" not in guide:
            fail(f"Administrator guide does not link tutorial {name}")
        tutorial = (ROOT / "docs" / name).read_text(encoding="utf-8")
        if "](USER_GUIDE.md)" not in tutorial:
            fail(f"Tutorial {name} does not link back to the administrator guide")


def check_xml() -> None:
    """Parse every XML file and verify extension manifest versions."""

    manifests = {
        ROOT / "package/pkg_pungamail.xml",
        ROOT / "extensions/com_pungamail/pungamail.xml",
        ROOT / "extensions/mod_pungamail_signup/mod_pungamail_signup.xml",
        ROOT / "extensions/plg_user_pungamail/pungamail.xml",
        ROOT / "extensions/plg_task_pungamail/pungamail.xml",
    }

    for path in sorted(ROOT.rglob("*.xml")):
        try:
            tree = ET.parse(path)
        except ET.ParseError as exc:
            fail(f"Invalid XML in {path.relative_to(ROOT)}: {exc}")

        if path in manifests:
            version = (tree.getroot().findtext("version") or "").strip()
            if version != VERSION:
                fail(f"Manifest {path.relative_to(ROOT)} has version {version!r}, expected {VERSION!r}")


def check_php() -> None:
    """Run PHP syntax checking over all PHP files."""

    php = shutil.which("php")
    if php is None:
        print("[skip] php executable not found; PHP syntax lint not run")
        return

    for path in sorted(ROOT.rglob("*.php")):
        result = subprocess.run([php, "-l", str(path)], check=False, capture_output=True, text=True)
        if result.returncode != 0:
            fail(f"PHP lint failed for {path.relative_to(ROOT)}:\n{result.stdout}{result.stderr}")


def check_migration_chain() -> None:
    """Protect released migrations and validate the 0.2.0 transition shape."""

    sql_root = ROOT / "extensions/com_pungamail/administrator/components/com_pungamail/sql"
    install = (sql_root / "install.mysql.sql").read_text(encoding="utf-8")
    hashes = {
        "0.1.0.sql": "2653b5d3f4d2f491ee917dcef9ac60acf312be999ef9a4d8c4550054e19e43f6",
        "0.1.1.sql": "ce4408c079b3a81036f44ee934016280ace02a783def8ba294f010d7ce908918",
        "0.2.0.sql": "536115b118b8219d4f9ccc0ab31f4ee2f4b50052ae57814d8b72527ef23a30ef",
        "0.2.1.sql": "2524f58dbdb4f7f14d59977ca6f5657bf8f5464e2d8b2c4a434f176e2f2aa99e",
        "0.2.2.sql": "1f4031022c2ddab31ea473d82095bd67f62e04ea1233d486729ce3b4eb271de0",
        "0.2.3.sql": "f62d5749296803e023f850d0481ed85102923303ea7416ab27ae62a3f58fd09a",
        "0.2.4.sql": "670aba4b98ea019a208df7bc75dbb174c963a23fef8452158b12b73302e4d195",
        "0.2.5.sql": "e11e248ecc7c05ecffcab92f1eb20f666118746fa572f5426fa52e1c6156a190",
        "0.2.6.sql": "67f3ec2dc8b049ed4516c3003257e3176944b2f77eb983efd344da46ba790bd1",
        "0.3.0.sql": "8d6ea80bbc67f37384ff6bdae6d2aa453d25e2cbd8edbaa3f84495a811a3f1e1",
        "0.3.1.sql": "f53cdbbd84cdfe3f243273f05dd89decb5c8f7333fa3310e4e30b71920ab7f41",
        "0.3.2.sql": "5bc3e9c93bbc39a5797433dbcf8f616dfc3178f94a6db08c9c8b513ca7b0df74",
        "0.3.3.sql": "656415b4627ee9831d38c7597146124090844bfcfa6d9fe85e44e3948f352f75",
    }

    for filename, expected_hash in hashes.items():
        digest = hashlib.sha256((sql_root / "updates/mysql" / filename).read_bytes()).hexdigest()
        if digest != expected_hash:
            fail(f"Released migration {filename} was modified; migrations are immutable")

    required_install_fragments = (
        "#__pungamail_templates",
        "#__pungamail_newsletter_sources",
        "`template_id` BIGINT UNSIGNED NULL",
        "`style_overrides` MEDIUMTEXT NULL",
        "`custom_css` MEDIUMTEXT NULL",
        "`reminder_sent_at` DATETIME NULL",
        "`source_key` VARCHAR(191) NOT NULL",
        "`source_item_id` VARCHAR(191) NOT NULL",
        "PRIMARY KEY (`newsletter_id`, `source_key`, `source_item_id`)",
    )
    for fragment in required_install_fragments:
        if fragment not in install:
            fail(f"Fresh-install schema is missing 0.2.0 fragment: {fragment}")

    migration = (sql_root / "updates/mysql/0.2.0.sql").read_text(encoding="utf-8")
    required_migration_fragments = (
        "CREATE TABLE IF NOT EXISTS `#__pungamail_templates`",
        "CREATE TABLE IF NOT EXISTS `#__pungamail_newsletter_sources`",
        "ADD COLUMN `template_id`",
        "ADD COLUMN `reminder_sent_at`",
        "ADD COLUMN `source_key`",
        "ADD COLUMN `source_item_id`",
        "SET `source_item_id` = CAST(`content_id` AS CHAR)",
        "DROP COLUMN `content_id`",
        "ADD PRIMARY KEY (`newsletter_id`, `source_key`, `source_item_id`)",
    )
    for fragment in required_migration_fragments:
        if fragment not in migration:
            fail(f"0.2.0 migration is missing transition fragment: {fragment}")

    marker = (sql_root / "updates/mysql/0.2.1.sql").read_text(encoding="utf-8")
    if any(token in marker.upper() for token in ("ALTER TABLE", "CREATE TABLE", "DROP TABLE")):
        fail("0.2.1 is a renderer-only release; its version-marker migration must not change schema")

    migration_022 = (sql_root / "updates/mysql/0.2.2.sql").read_text(encoding="utf-8")
    if "ADD COLUMN `recipient_name` VARCHAR(255) NOT NULL DEFAULT '' AFTER `email`" not in migration_022:
        fail("0.2.2 migration is missing the recipient_name queue snapshot column")
    if "`recipient_name` VARCHAR(255) NOT NULL DEFAULT ''" not in install:
        fail("Fresh-install schema is missing the 0.2.2 recipient_name queue snapshot column")

    marker_025 = (sql_root / "updates/mysql/0.2.5.sql").read_text(encoding="utf-8")
    if any(token in marker_025.upper() for token in ("ALTER TABLE", "CREATE TABLE", "DROP TABLE")):
        fail("0.2.5 changes mail language/configuration only; its version-marker migration must not change schema")

    marker_026 = (sql_root / "updates/mysql/0.2.6.sql").read_text(encoding="utf-8")
    if any(token in marker_026.upper() for token in ("ALTER TABLE", "CREATE TABLE", "DROP TABLE")):
        fail("0.2.6 changes administrator UI/subscriber management only; its version-marker migration must not change schema")

    migration_030 = (sql_root / "updates/mysql/0.3.0.sql").read_text(encoding="utf-8")
    for fragment in (
        "#__pungamail_topics",
        "#__pungamail_subscriber_topics",
        "#__pungamail_newsletter_topics",
        "#__pungamail_bounces",
        "#__pungamail_digests",
        "#__pungamail_digest_runs",
    ):
        if fragment not in migration_030 or fragment not in install:
            fail(f"0.3.0 schema is missing normalized relation {fragment}")

    migration_031 = (sql_root / "updates/mysql/0.3.1.sql").read_text(encoding="utf-8")
    for table in ("newsletters", "templates", "topics", "digests"):
        table_name = f"#__pungamail_{table}"
        if table_name not in migration_031:
            fail(f"0.3.1 checkout migration is missing {table_name}")
    for fragment in ("`checked_out` INT UNSIGNED NOT NULL DEFAULT 0", "`checked_out_time` DATETIME NULL"):
        if migration_031.count(fragment) != 4 or install.count(fragment) != 4:
            fail(f"0.3.1 checkout schema has an unexpected count for {fragment!r}")

    marker_032 = (sql_root / "updates/mysql/0.3.2.sql").read_text(encoding="utf-8")
    if any(token in marker_032.upper() for token in ("ALTER TABLE", "CREATE TABLE", "DROP TABLE")):
        fail("0.3.2 uses existing topic relations; its version-marker migration must not change schema")

    marker_033 = (sql_root / "updates/mysql/0.3.3.sql").read_text(encoding="utf-8")
    if any(token in marker_033.upper() for token in ("ALTER TABLE", "CREATE TABLE", "DROP TABLE")):
        fail("0.3.3 is a stabilization release; its version-marker migration must not change schema")

    marker_034 = (sql_root / "updates/mysql/0.3.4.sql").read_text(encoding="utf-8")
    if any(token in marker_034.upper() for token in ("ALTER TABLE", "PREPARE ", "EXECUTE ")):
        fail("0.3.4 SQL must remain a prepared-statement-free version marker")

    package_script = (ROOT / "package/script.php").read_text(encoding="utf-8")
    for fragment in (
        "repairSubscriberRecipientName",
        "getTableColumns($table, true)",
        "isset($columns['recipient_name'])",
        "ADD COLUMN ' . $db->quoteName('recipient_name')",
    ):
        if fragment not in package_script:
            fail(f"0.3.4 conditional installer repair is missing {fragment!r}")


def schema_columns_from_create(sql: str) -> dict[str, set[str]]:
    """Extract table-column sets from MySQL CREATE TABLE statements.

    @param sql SQL source text.
    @return Columns keyed by Joomla table placeholder.
    """

    schema: dict[str, set[str]] = {}
    pattern = re.compile(
        r"CREATE TABLE(?: IF NOT EXISTS)?\s+`([^`]+)`\s*\((.*?)\)\s*ENGINE=",
        re.IGNORECASE | re.DOTALL,
    )

    for match in pattern.finditer(sql):
        columns = set(re.findall(r"(?:^|,)\s*`([^`]+)`\s+[A-Za-z]", match.group(2), re.MULTILINE))
        schema[match.group(1)] = columns

    return schema


def apply_schema_update(schema: dict[str, set[str]], sql: str) -> None:
    """Apply the column-level effect of one Punga Mail migration.

    @param schema Accumulated mutable schema.
    @param sql Migration SQL source text.
    """

    for table, columns in schema_columns_from_create(sql).items():
        schema[table] = set(columns)

    alter_pattern = re.compile(r"ALTER TABLE\s+`([^`]+)`\s+(.*?);", re.IGNORECASE | re.DOTALL)

    for match in alter_pattern.finditer(sql):
        columns = schema.setdefault(match.group(1), set())
        body = match.group(2)

        for column in re.findall(r"ADD COLUMN\s+`([^`]+)`", body, re.IGNORECASE):
            columns.add(column)

        for column in re.findall(r"DROP COLUMN\s+`([^`]+)`", body, re.IGNORECASE):
            columns.discard(column)

        for old_column, new_column in re.findall(
            r"CHANGE COLUMN\s+`([^`]+)`\s+`([^`]+)`",
            body,
            re.IGNORECASE,
        ):
            columns.discard(old_column)
            columns.add(new_column)


def check_schema_path_parity() -> None:
    """Require cumulative updates and fresh installation to expose equal columns."""

    sql_root = ROOT / "extensions/com_pungamail/administrator/components/com_pungamail/sql"
    install_schema = schema_columns_from_create((sql_root / "install.mysql.sql").read_text(encoding="utf-8"))
    update_schema: dict[str, set[str]] = {}
    migrations = sorted(
        (sql_root / "updates/mysql").glob("*.sql"),
        key=lambda path: tuple(int(part) for part in path.stem.split(".")),
    )

    for migration in migrations:
        apply_schema_update(update_schema, migration.read_text(encoding="utf-8"))

    # 0.3.4 repairs this one historical omission conditionally in the package
    # preflight, before Joomla applies the component's SQL version marker.
    update_schema.setdefault("#__pungamail_subscribers", set()).add("recipient_name")

    if install_schema.keys() != update_schema.keys():
        missing_tables = sorted(install_schema.keys() - update_schema.keys())
        extra_tables = sorted(update_schema.keys() - install_schema.keys())
        fail(f"Fresh/update table mismatch: missing={missing_tables}, extra={extra_tables}")

    for table, install_columns in install_schema.items():
        update_columns = update_schema[table]

        if install_columns != update_columns:
            missing = sorted(install_columns - update_columns)
            extra = sorted(update_columns - install_columns)
            fail(f"Fresh/update column mismatch for {table}: missing={missing}, extra={extra}")


def check_no_runtime_schema_mutation() -> None:
    """Reject schema mutation from normal extension runtime PHP."""

    forbidden = ("ALTER TABLE", "CREATE TABLE", "DROP TABLE")
    for path in sorted((ROOT / "extensions").rglob("*.php")):
        text = path.read_text(encoding="utf-8").upper()
        for phrase in forbidden:
            if phrase in text:
                fail(f"Runtime schema mutation {phrase!r} found in {path.relative_to(ROOT)}")


def check_bind_values_are_variables() -> None:
    """Require Joomla DatabaseQuery.bind() values to be local variables."""

    bind_pattern = re.compile(r"->bind\(\s*[^,]+,\s*([^,\)\n]+)")
    simple_variable = re.compile(r"^\$[A-Za-z_][A-Za-z0-9_]*$")

    for path in sorted((ROOT / "extensions").rglob("*.php")):
        for line_number, line in enumerate(path.read_text(encoding="utf-8").splitlines(), 1):
            if "->bind(" not in line:
                continue
            match = bind_pattern.search(line)
            if match is None:
                fail(f"Could not validate bind() at {path.relative_to(ROOT)}:{line_number}")
            value = match.group(1).strip()
            if simple_variable.fullmatch(value) is None:
                fail(f"Database bind value must be a local variable at {path.relative_to(ROOT)}:{line_number}; got {value!r}")


def check_renderer_regressions() -> None:
    """Run PHP renderer regression tests."""

    php = shutil.which("php")
    if php is None:
        print("[skip] php executable not found; renderer regression tests not run")
        return

    for script in ("test_markdown.php", "test_newsletter_renderer.php", "test_mail_language.php"):
        result = subprocess.run(
            [php, str(ROOT / "tools" / script)],
            check=False,
            capture_output=True,
            text=True,
            cwd=ROOT,
        )
        if result.returncode != 0:
            fail(f"Renderer test {script} failed:\n{result.stdout}{result.stderr}")


def check_new_content_pipeline() -> None:
    """Ensure new-content insertion cannot leak a Markdown sentinel."""

    path = ROOT / "extensions/com_pungamail/administrator/components/com_pungamail/src/Service/NewsletterRenderer.php"
    text = path.read_text(encoding="utf-8")
    if "NEW_CONTENT_MARKER" in text or "PUNGAMAIL_NEW_CONTENT" in text:
        fail("NewsletterRenderer still contains the old Markdown-mutating new-content sentinel")
    required = ("preg_split", "{new_content}", "implode($itemHtml", "implode($textSeparator")
    for token in required:
        if token not in text:
            fail(f"NewsletterRenderer new-content pipeline is missing {token!r}")



def check_administrator_sidebar_routes() -> None:
    """Require secondary administrator screens to preserve submenu URL context."""

    route_service = (ROOT / "extensions/com_pungamail/administrator/components/com_pungamail/src/Service/AdministratorRoute.php").read_text(encoding="utf-8")
    display_controller = (ROOT / "extensions/com_pungamail/administrator/components/com_pungamail/src/Controller/DisplayController.php").read_text(encoding="utf-8")
    for token in ("view=newsletters", "screen=newsletter", "screen=preview", "screen=preflight", "view=templates", "screen=template", "screen=templatepreview", "view=subscribers", "screen=subscriber"):
        if token not in route_service:
            fail(f"Administrator sidebar route contract is missing {token!r}")
    for screen in ("newsletter", "preview", "preflight", "template", "templatepreview", "subscriber"):
        if screen not in display_controller:
            fail(f"DisplayController does not map administrator screen {screen!r}")



def check_editor_toolbars() -> None:
    """Require Joomla-standard toolbar actions and adminForm editor submission."""

    newsletter_view = (ROOT / "extensions/com_pungamail/administrator/components/com_pungamail/src/View/Newsletter/HtmlView.php").read_text(encoding="utf-8")
    template_view = (ROOT / "extensions/com_pungamail/administrator/components/com_pungamail/src/View/Template/HtmlView.php").read_text(encoding="utf-8")
    newsletter_template = (ROOT / "extensions/com_pungamail/administrator/components/com_pungamail/tmpl/newsletter/default.php").read_text(encoding="utf-8")
    template_template = (ROOT / "extensions/com_pungamail/administrator/components/com_pungamail/tmpl/template/default.php").read_text(encoding="utf-8")

    for token in (
        "ToolbarHelper::apply('newsletter.save')",
        "ToolbarHelper::save('newsletter.save2close')",
        "newsletter.preview",
        "newsletter.sendTest",
        "newsletter.preflight",
        "ToolbarHelper::cancel('newsletter.cancel')",
    ):
        if token not in newsletter_view:
            fail(f"Newsletter editor toolbar is missing {token!r}")

    for token in (
        "ToolbarHelper::apply('template.save')",
        "ToolbarHelper::save('template.save2close')",
        "template.preview",
        "ToolbarHelper::cancel('template.cancel')",
    ):
        if token not in template_view:
            fail(f"Template editor toolbar is missing {token!r}")

    forbidden_helpers = re.compile(r"ToolbarHelper::([A-Za-z0-9_]+)\(")
    allowed_helpers = {"title", "apply", "save", "custom", "cancel"}
    for label, view in (("Newsletter", newsletter_view), ("Template", template_view)):
        helpers = set(forbidden_helpers.findall(view))
        unknown = helpers - allowed_helpers
        if unknown:
            fail(f"{label} editor uses unsupported ToolbarHelper methods: {sorted(unknown)}")

    for label, template in (("newsletter", newsletter_template), ("template", template_template)):
        if 'name="adminForm" id="adminForm"' not in template:
            fail(f"{label.capitalize()} editor does not use Joomla adminForm")
        if 'name="task" value=""' not in template:
            fail(f"{label.capitalize()} editor is missing the hidden Joomla task field")


    for task in ("newsletter.applyCutoff", "newsletter.applyTemplate"):
        expected = f"type=\"button\" onclick=\"Joomla.submitbutton('{task}');\""
        if expected not in newsletter_template:
            fail(f"Newsletter editor in-form action {task!r} does not use Joomla.submitbutton with a non-submit button")

    if re.search(r'type="submit"[^>]*name="task"|name="task"[^>]*type="submit"', newsletter_template):
        fail("Newsletter editor contains an in-form submit button that competes with Joomla's hidden task field")

    newsletter_controller = (ROOT / "extensions/com_pungamail/administrator/components/com_pungamail/src/Controller/NewsletterController.php").read_text(encoding="utf-8")
    if "persistAndRedirect('COM_PUNGAMAIL_CONTENT_DATE_APPLIED', false)" not in newsletter_controller:
        fail("Apply filters must preserve incomplete draft state before refreshing content candidates")

def check_queue_admin_workflow() -> None:
    """Keep manual queue processing on the dashboard, not the newsletter list."""

    list_view = (ROOT / "extensions/com_pungamail/administrator/components/com_pungamail/src/View/Newsletters/HtmlView.php").read_text(encoding="utf-8")
    dashboard = (ROOT / "extensions/com_pungamail/administrator/components/com_pungamail/tmpl/dashboard/default.php").read_text(encoding="utf-8")
    controller = (ROOT / "extensions/com_pungamail/administrator/components/com_pungamail/src/Controller/NewsletterController.php").read_text(encoding="utf-8")

    if "newsletter.processQueue" in list_view:
        fail("Newsletter list must not expose the manual Process queue action")
    for token in ('name="task" value="newsletter.processQueue"', 'name="return" value="dashboard"', "HTMLHelper::_('form.token')"):
        if token not in dashboard:
            fail(f"Dashboard manual queue action is missing {token!r}")
    if "post->getCmd('return') === 'dashboard'" not in controller:
        fail("Queue controller does not safely return dashboard-triggered processing to the dashboard")


def language_keys(path: Path) -> set[str]:
    """Return defined language constants from one INI file."""

    return set(re.findall(r"^([A-Z0-9_]+)=", path.read_text(encoding="utf-8"), re.MULTILINE))


def check_language_parity() -> None:
    """Require English/German catalogs to expose matching keys."""

    pairs = (
        (
            ROOT / "extensions/com_pungamail/administrator/components/com_pungamail/language/en-GB/com_pungamail.ini",
            ROOT / "extensions/com_pungamail/administrator/components/com_pungamail/language/de-DE/com_pungamail.ini",
        ),
        (
            ROOT / "extensions/com_pungamail/components/com_pungamail/language/en-GB/com_pungamail.ini",
            ROOT / "extensions/com_pungamail/components/com_pungamail/language/de-DE/com_pungamail.ini",
        ),
        (
            ROOT / "extensions/mod_pungamail_signup/language/en-GB/mod_pungamail_signup.ini",
            ROOT / "extensions/mod_pungamail_signup/language/de-DE/mod_pungamail_signup.ini",
        ),
        (
            ROOT / "extensions/plg_user_pungamail/language/en-GB/plg_user_pungamail.ini",
            ROOT / "extensions/plg_user_pungamail/language/de-DE/plg_user_pungamail.ini",
        ),
        (
            ROOT / "extensions/plg_task_pungamail/language/en-GB/plg_task_pungamail.ini",
            ROOT / "extensions/plg_task_pungamail/language/de-DE/plg_task_pungamail.ini",
        ),
        (
            ROOT / "extensions/com_pungamail/administrator/components/com_pungamail/language/en-GB/com_pungamail.sys.ini",
            ROOT / "extensions/com_pungamail/administrator/components/com_pungamail/language/de-DE/com_pungamail.sys.ini",
        ),
        (
            ROOT / "extensions/com_pungamail/components/com_pungamail/language/en-GB/com_pungamail.sys.ini",
            ROOT / "extensions/com_pungamail/components/com_pungamail/language/de-DE/com_pungamail.sys.ini",
        ),
        (
            ROOT / "extensions/mod_pungamail_signup/language/en-GB/mod_pungamail_signup.sys.ini",
            ROOT / "extensions/mod_pungamail_signup/language/de-DE/mod_pungamail_signup.sys.ini",
        ),
        (
            ROOT / "extensions/plg_user_pungamail/language/en-GB/plg_user_pungamail.sys.ini",
            ROOT / "extensions/plg_user_pungamail/language/de-DE/plg_user_pungamail.sys.ini",
        ),
        (
            ROOT / "extensions/plg_task_pungamail/language/en-GB/plg_task_pungamail.sys.ini",
            ROOT / "extensions/plg_task_pungamail/language/de-DE/plg_task_pungamail.sys.ini",
        ),
    )
    for english, german in pairs:
        en_keys = language_keys(english)
        de_keys = language_keys(german)
        if en_keys != de_keys:
            fail(f"Language key mismatch for {english.name}: EN-only={sorted(en_keys-de_keys)}, DE-only={sorted(de_keys-en_keys)}")


def check_language_usage() -> None:
    """Reject literal extension language keys that are not defined anywhere."""

    pattern = re.compile(r"(?:COM_PUNGAMAIL|MOD_PUNGAMAIL|PLG_USER_PUNGAMAIL|PLG_TASK_PUNGAMAIL)_[A-Z0-9_]+")
    defined: set[str] = set()
    used: set[str] = set()

    for path in sorted((ROOT / "extensions").rglob("*.ini")):
        defined.update(language_keys(path))

    for suffix in ("*.php", "*.xml"):
        for path in sorted((ROOT / "extensions").rglob(suffix)):
            used.update(pattern.findall(path.read_text(encoding="utf-8")))

    # Prefixes ending in an underscore are deliberately completed at runtime
    # for plural/status variants and cannot be checked as literal keys.
    missing = sorted(key for key in used - defined if not key.endswith("_"))
    if missing:
        fail(f"Undefined extension language keys: {missing}")


def check_mail_language_placement() -> None:
    """Keep subscriber-facing mail copy in the Website language catalog."""

    keys = {
        "COM_PUNGAMAIL_MAIL_FOOTER_REASON",
        "COM_PUNGAMAIL_MAIL_UNSUBSCRIBE",
        "COM_PUNGAMAIL_MAIL_READ_MORE",
    }
    admin_en = ROOT / "extensions/com_pungamail/administrator/components/com_pungamail/language/en-GB/com_pungamail.ini"
    site_en = ROOT / "extensions/com_pungamail/components/com_pungamail/language/en-GB/com_pungamail.ini"
    admin_keys = language_keys(admin_en)
    site_keys = language_keys(site_en)

    if keys & admin_keys:
        fail(f"Subscriber-facing mail strings remain in Administrator language catalog: {sorted(keys & admin_keys)}")
    if not keys <= site_keys:
        fail(f"Website mail catalog is missing subscriber-facing keys: {sorted(keys - site_keys)}")

    renderer = (ROOT / "extensions/com_pungamail/administrator/components/com_pungamail/src/Service/NewsletterRenderer.php").read_text(encoding="utf-8")
    if "mail_footer_reason" not in renderer or "MailTextService" not in renderer:
        fail("Newsletter renderer does not use configurable frontend-language footer copy")


def check_menu_metadata_language() -> None:
    """Require site menu metadata keys in Joomla's administrator system catalog."""

    admin_root = ROOT / "extensions/com_pungamail/administrator/components/com_pungamail/language"
    site_root = ROOT / "extensions/com_pungamail/components/com_pungamail/language"
    required = {
        "COM_PUNGAMAIL_SUBSCRIPTION_MENU_TITLE",
        "COM_PUNGAMAIL_SUBSCRIPTION_MENU_DESC",
    }

    for locale in ("en-GB", "de-DE"):
        for root in (admin_root, site_root):
            path = root / locale / "com_pungamail.sys.ini"
            missing = required - language_keys(path)
            if missing:
                fail(f"Menu metadata catalog {path.relative_to(ROOT)} is missing {sorted(missing)}")



def check_admin_polish_026() -> None:
    """Validate Joomla-native administrator UX and recipient creation in 0.2.6."""

    admin_root = ROOT / "extensions/com_pungamail/administrator/components/com_pungamail"
    newsletters = (admin_root / "tmpl/newsletters/default.php").read_text(encoding="utf-8")
    templates = (admin_root / "tmpl/templates/default.php").read_text(encoding="utf-8")
    newsletter_view = (admin_root / "src/View/Newsletter/HtmlView.php").read_text(encoding="utf-8")
    template_view = (admin_root / "src/View/Template/HtmlView.php").read_text(encoding="utf-8")
    newsletter_list_view = (admin_root / "src/View/Newsletters/HtmlView.php").read_text(encoding="utf-8")
    template_list_view = (admin_root / "src/View/Templates/HtmlView.php").read_text(encoding="utf-8")
    subscribers_view = (admin_root / "src/View/Subscribers/HtmlView.php").read_text(encoding="utf-8")
    subscriber_controller = (admin_root / "src/Controller/SubscriberController.php").read_text(encoding="utf-8")
    subscriber_repository = (admin_root / "src/Service/SubscriberRepository.php").read_text(encoding="utf-8")
    config = (admin_root / "config.xml").read_text(encoding="utf-8")
    admin_en = (admin_root / "language/en-GB/com_pungamail.ini").read_text(encoding="utf-8")
    admin_de = (admin_root / "language/de-DE/com_pungamail.ini").read_text(encoding="utf-8")

    if "table-secondary" in newsletters or "table-secondary" in templates:
        fail("Trashed newsletter/template rows still force light-mode table-secondary styling")
    if "COM_PUNGAMAIL_RESTORE" not in newsletter_list_view or "COM_PUNGAMAIL_RESTORE" not in template_list_view:
        fail("Trash views do not use the translated Punga Mail Restore label")
    if '<inlinehelp button="show"/>' not in config:
        fail("Component Options does not enable Joomla Toggle Inline Help")
    for text in (admin_en, admin_de):
        if "COM_PUNGAMAIL_CONFIGURATION=" not in text:
            fail("Component configuration page title language key is missing")
    if "#toolbar-eye { margin-inline-start: auto; }" not in newsletter_view:
        fail("Newsletter Preview/Test/Send toolbar group is not pushed to the right")
    if newsletter_view.find("ToolbarHelper::cancel('newsletter.cancel')") > newsletter_view.find("newsletter.preview"):
        fail("Newsletter Cancel must remain in the left toolbar group before Preview")
    if "#toolbar-eye { margin-inline-start: auto; }" not in template_view:
        fail("Template Preview toolbar action is not separated to the right")
    if "ToolbarHelper::addNew('subscriber.add')" not in subscribers_view:
        fail("Subscribers list does not expose the New recipient action")
    for token in ("addAdministratorExternal", "setUserPreference", "AdministratorRoute::subscriber"):
        if token not in subscriber_controller:
            fail(f"Subscriber add controller is missing {token!r}")
    if "addAdministratorExternal" not in subscriber_repository:
        fail("Subscriber repository lacks administrator external-subscription support")
    if "removeSuppression($normalized)" not in subscriber_repository:
        fail("Administrator subscription path does not explicitly clear suppressions")


def check_regressions_031() -> None:
    """Validate the 0.3.1 dashboard, editor-lock and action-controller repairs."""

    admin_root = ROOT / "extensions/com_pungamail/administrator/components/com_pungamail"
    dashboard = (admin_root / "src/Model/DashboardModel.php").read_text(encoding="utf-8")
    checkout = (admin_root / "src/Service/CheckoutService.php").read_text(encoding="utf-8")
    delivery = (admin_root / "src/Controller/DeliveryController.php").read_text(encoding="utf-8")
    mail = (admin_root / "src/Service/MailService.php").read_text(encoding="utf-8")
    delivery_template = (admin_root / "tmpl/delivery/default.php").read_text(encoding="utf-8")

    if "quoteName('enabled')" in dashboard or "digestEnabled" in dashboard:
        fail("Dashboard still queries the nonexistent digest enabled column")
    if "quoteName('state') . ' = :digestState'" not in dashboard:
        fail("Dashboard digest warning does not use Joomla's digest state field")

    for entity in ("newsletter", "template", "topic", "digest"):
        model = (admin_root / f"src/Model/{entity.capitalize()}Model.php").read_text(encoding="utf-8")
        controller = (admin_root / f"src/Controller/{entity.capitalize()}Controller.php").read_text(encoding="utf-8")
        if f"checkouts()->checkout('{entity}'" not in model:
            fail(f"{entity.capitalize()} editor model does not check out existing records")
        if f"checkouts()->checkin('{entity}'" not in controller:
            fail(f"{entity.capitalize()} editor controller does not check records in")
        if f"'{entity}' => '#__pungamail_" not in checkout:
            fail(f"Checkout service does not allow {entity} records")

    for entity in ("topic", "digest"):
        view = (admin_root / f"src/View/{entity.capitalize()}/HtmlView.php").read_text(encoding="utf-8")
        controller = (admin_root / f"src/Controller/{entity.capitalize()}Controller.php").read_text(encoding="utf-8")
        if f"ToolbarHelper::save('{entity}.save2close')" not in view or "function save2close()" not in controller:
            fail(f"{entity.capitalize()} editor is missing Save & Close")

    for path in sorted((admin_root / "src/Controller").glob("*Controller.php")):
        text = path.read_text(encoding="utf-8")
        if re.search(r"(?:private|protected) function redirect\s*\(", text):
            fail(f"{path.name} shadows Joomla BaseController::redirect() with incompatible visibility")

    if "redirectToDelivery" not in delivery:
        fail("Delivery actions do not use the non-conflicting redirect helper")
    if 'name="task" value="delivery.sendTest"' not in delivery_template:
        fail("Delivery mail-test form lacks an explicit task field")
    if "setSender([$fromEmail, $this->headerValue($fromName)])" not in mail:
        fail("Mail service does not use Joomla's sender tuple API")


def check_profile_topics_032() -> None:
    """Validate the shared profile/module topic-membership workflow."""

    plugin_root = ROOT / "extensions/plg_user_pungamail"
    form = (plugin_root / "forms/pungamail.xml").read_text(encoding="utf-8")
    extension = (plugin_root / "src/Extension/PungaMail.php").read_text(encoding="utf-8")

    for token in (
        'name="topic_ids"',
        'type="sql"',
        'multiple="true"',
        "FROM #__pungamail_topics WHERE state = 1",
        "PLG_USER_PUNGAMAIL_TOPICS_LABEL",
        "PLG_USER_PUNGAMAIL_TOPICS_DESC",
    ):
        if token not in form:
            fail(f"User profile topic selector is missing {token!r}")

    for token in (
        "getSubscriberTopicIds",
        "updateVisibleTopics",
        "$topics->active()",
        "'_topics_updated'",
        "'topic_ids' => $topicIds",
    ):
        if token not in extension:
            fail(f"User profile topic persistence is missing {token!r}")

    for locale in ("en-GB", "de-DE"):
        for suffix in (".ini", ".sys.ini"):
            language = (plugin_root / f"language/{locale}/plg_user_pungamail{suffix}").read_text(encoding="utf-8")
            for key in ("PLG_USER_PUNGAMAIL_TOPICS_LABEL=", "PLG_USER_PUNGAMAIL_TOPICS_DESC="):
                if key not in language:
                    fail(f"Profile topic catalog {locale}{suffix} is missing {key}")


def check_stabilization_033() -> None:
    """Protect the 0.3.3 consent, concurrency and security repairs."""

    module_root = ROOT / "extensions/mod_pungamail_signup"
    helper = (module_root / "src/Helper/PungaMailSignupHelper.php").read_text(encoding="utf-8")
    layout = (module_root / "tmpl/default.php").read_text(encoding="utf-8")
    subscription = (
        ROOT / "extensions/com_pungamail/components/com_pungamail/src/Controller/SubscriptionController.php"
    ).read_text(encoding="utf-8")

    for token in ("single_topic_mode", "count($configuredTopicIds) === 1", "array_unique"):
        if token not in helper:
            fail(f"Signup module configuration-mode contract is missing {token!r}")
    if "if (count($topics) === 1)" in layout:
        fail("Signup module still derives single-topic mode from the published result count")
    if layout.count("$singleTopicMode && count($topics) === 1") != 2:
        fail("Signup module does not apply explicit single-topic mode to both visitor states")
    if "elseif ($topics !== [])" not in layout:
        fail("An unconfigured module does not offer its single published topic as a choice")
    for token in (
        "Topic choices and the global newsletter preference are independent.",
        "$repo->isUserSubscribed(",
        "str_starts_with($return, $siteRoot . '/')",
    ):
        if token not in subscription:
            fail(f"Topic-preference consent/redirect hardening is missing {token!r}")

    admin_root = ROOT / "extensions/com_pungamail/administrator/components/com_pungamail"
    display = (admin_root / "src/Controller/DisplayController.php").read_text(encoding="utf-8")
    csv_controller = (admin_root / "src/Controller/ImportController.php").read_text(encoding="utf-8")
    csv_service = (admin_root / "src/Service/CsvService.php").read_text(encoding="utf-8")
    mail_settings = (admin_root / "src/Service/MailSettingsRepository.php").read_text(encoding="utf-8")
    mail_service = (admin_root / "src/Service/MailService.php").read_text(encoding="utf-8")
    errors = (admin_root / "src/Service/ErrorMessage.php").read_text(encoding="utf-8")
    dashboard_model = (admin_root / "src/Model/DashboardModel.php").read_text(encoding="utf-8")
    dashboard_layout = (admin_root / "tmpl/dashboard/default.php").read_text(encoding="utf-8")
    content_types = (admin_root / "src/Service/ContentTypeService.php").read_text(encoding="utf-8")
    digest_repository = (admin_root / "src/Service/DigestRepository.php").read_text(encoding="utf-8")
    digest_service = (admin_root / "src/Service/DigestService.php").read_text(encoding="utf-8")
    bounce_service = (admin_root / "src/Service/BounceService.php").read_text(encoding="utf-8")

    required_security_tokens = (
        (display, "authorise('core.manage', 'com_pungamail')", "administrator display ACL"),
        (csv_controller, "MAX_CSV_BYTES", "bounded CSV upload"),
        (csv_controller, "escapeSpreadsheetCell", "spreadsheet-formula-safe CSV export"),
        (csv_controller, "clear('pungamail.csv.contents')", "stale CSV preview clearing"),
        (csv_service, "COM_PUNGAMAIL_CSV_EMAIL_MAPPING_REQUIRED", "translated CSV validation"),
        (csv_service, "$result['unchanged']++", "accurate unchanged CSV result accounting"),
        (mail_settings, "isValidHost", "bounce mailbox host validation"),
        (mail_service, "headerValue", "mail header control-character filtering"),
        (errors, "[redacted]", "operational error redaction"),
        (dashboard_model, "safeCounts", "schema-safe dashboard aggregates"),
        (dashboard_layout, "COM_PUNGAMAIL_DATABASE_UPDATE_REQUIRED", "database repair notice"),
        (content_types, "access_metadata_unavailable", "fail-closed digest access metadata"),
        (digest_repository, "GET_LOCK(:lockName, 0)", "digest run lock"),
        (digest_service, "acquireRunLock", "digest concurrency guard"),
        (bounce_service, "acquireProcessLock", "bounce concurrency guard"),
    )

    for contents, token, label in required_security_tokens:
        if token not in contents:
            fail(f"0.3.3 is missing {label}: {token!r}")

    task_plugin = (ROOT / "extensions/plg_task_pungamail/src/Extension/PungaMail.php").read_text(encoding="utf-8")
    if "failed: ' . $e->getMessage()" in task_plugin:
        fail("Scheduled Tasks still write unsanitized exception details to Joomla logs")

    for controller in sorted((admin_root / "src/Controller").glob("*Controller.php")):
        if "->getMessage()" in controller.read_text(encoding="utf-8"):
            fail(f"Administrator controller exposes an unsanitized exception: {controller.name}")



def check_package_members() -> None:
    """Verify expected constituent extension ZIPs in package manifest."""

    tree = ET.parse(ROOT / "package/pkg_pungamail.xml")
    names = {node.text.strip() for node in tree.getroot().findall("./files/file") if node.text}
    expected = {"com_pungamail.zip", "mod_pungamail_signup.zip", "plg_user_pungamail.zip", "plg_task_pungamail.zip"}
    if names != expected:
        fail(f"Package constituents differ: got {sorted(names)}, expected {sorted(expected)}")


def check_feature_contracts() -> None:
    """Smoke-check release-defining integration points."""

    checks = {
        "registered content types": (ROOT / "extensions/com_pungamail/administrator/components/com_pungamail/src/Service/ContentTypeService.php", "#__content_types"),
        "content placeholder": (ROOT / "extensions/com_pungamail/administrator/components/com_pungamail/src/Service/NewsletterRenderer.php", "{new_content}"),
        "newsletter reminder task": (ROOT / "extensions/plg_task_pungamail/src/Extension/PungaMail.php", "pungamail.newsletter_reminder"),
        "SEF router": (ROOT / "extensions/com_pungamail/components/com_pungamail/src/Service/Router.php", "RouterViewConfiguration('subscription')"),
        "destructive uninstall opt-in": (ROOT / "package/script.php", "remove_tables_on_uninstall"),
        "preview sandbox": (ROOT / "extensions/com_pungamail/administrator/components/com_pungamail/tmpl/preview/default.php", 'sandbox=""'),
        "Markdown tables": (ROOT / "extensions/com_pungamail/administrator/components/com_pungamail/src/Service/MarkdownRenderer.php", "renderTable"),
        "recipient personalization": (ROOT / "extensions/com_pungamail/administrator/components/com_pungamail/src/Service/NewsletterRenderer.php", "RECIPIENT_PLACEHOLDER"),
        "Website mail language overrides": (ROOT / "extensions/com_pungamail/administrator/components/com_pungamail/src/Service/MailTextService.php", "/language/overrides/"),
        "configurable Markdown footer": (ROOT / "extensions/com_pungamail/administrator/components/com_pungamail/config.xml", "mail_footer_reason"),
        "sidebar context routes": (ROOT / "extensions/com_pungamail/administrator/components/com_pungamail/src/Service/AdministratorRoute.php", "screen=newsletter"),
        "administrator subscriber add": (ROOT / "extensions/com_pungamail/administrator/components/com_pungamail/src/Controller/SubscriberController.php", "addAdministratorExternal"),
        "component inline help": (ROOT / "extensions/com_pungamail/administrator/components/com_pungamail/config.xml", "inlinehelp"),
        "recipient-aware digest access": (ROOT / "extensions/com_pungamail/administrator/components/com_pungamail/src/Service/DigestService.php", "filterForRecipients"),
        "Joomla access levels": (ROOT / "extensions/com_pungamail/administrator/components/com_pungamail/src/Service/ContentTypeService.php", "getAuthorisedViewLevels"),
        "bounce processing task": (ROOT / "extensions/plg_task_pungamail/src/Extension/PungaMail.php", "pungamail.process_bounces"),
        "scheduled send task": (ROOT / "extensions/plg_task_pungamail/src/Extension/PungaMail.php", "pungamail.scheduled_sends"),
        "digest generation task": (ROOT / "extensions/plg_task_pungamail/src/Extension/PungaMail.php", "pungamail.generate_digests"),
        "immutable browser view": (ROOT / "extensions/com_pungamail/components/com_pungamail/src/Model/BrowserModel.php", "snapshot_html"),
        "suppression-safe CSV": (ROOT / "extensions/com_pungamail/administrator/components/com_pungamail/src/Service/CsvService.php", "isProtected"),
        "secure mailbox options field": (ROOT / "extensions/com_pungamail/administrator/components/com_pungamail/src/Field/BouncemailboxField.php", "password_configured"),
        "digest scheduler warning": (ROOT / "extensions/com_pungamail/administrator/components/com_pungamail/tmpl/dashboard/default.php", "COM_PUNGAMAIL_DIGEST_TASK_NOT_CONFIGURED"),
        "empty digest draft safety": (ROOT / "extensions/com_pungamail/administrator/components/com_pungamail/src/Service/DigestService.php", "&& !$forceDraftForEmptyDigest"),
    }
    for label, (path, token) in checks.items():
        if token not in path.read_text(encoding="utf-8"):
            fail(f"Missing feature contract for {label}: {path.relative_to(ROOT)}")


def main() -> int:
    """Run all release checks.

    @return Process exit status.
    """

    checks = (
        check_required_files,
        check_administrator_documentation,
        check_xml,
        check_php,
        check_migration_chain,
        check_schema_path_parity,
        check_no_runtime_schema_mutation,
        check_bind_values_are_variables,
        check_renderer_regressions,
        check_new_content_pipeline,
        check_administrator_sidebar_routes,
        check_editor_toolbars,
        check_queue_admin_workflow,
        check_language_parity,
        check_language_usage,
        check_mail_language_placement,
        check_menu_metadata_language,
        check_admin_polish_026,
        check_regressions_031,
        check_profile_topics_032,
        check_stabilization_033,
        check_package_members,
        check_feature_contracts,
    )
    try:
        for check in checks:
            check()
    except RuntimeError as exc:
        print(f"[FAIL] {exc}", file=sys.stderr)
        return 1
    print(f"[OK] Punga Mail {VERSION} release checks passed")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())

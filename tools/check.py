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
PACKAGE_MANIFEST = ROOT / "package/pkg_pungamail.xml"


def get_release_version() -> str:
    """Read and validate the release version from the package manifest.

    @return Canonical Punga Mail release version.
    """

    try:
        root = ET.parse(PACKAGE_MANIFEST).getroot()
    except (OSError, ET.ParseError) as exc:
        raise RuntimeError(f"Cannot read package manifest {PACKAGE_MANIFEST}: {exc}") from exc

    version = (root.findtext("version") or "").strip()

    if re.fullmatch(r"[0-9]+(?:\.[0-9]+){2}(?:[-+][0-9A-Za-z.-]+)?", version) is None:
        raise RuntimeError(f"Invalid release version in {PACKAGE_MANIFEST}: {version!r}")

    return version


VERSION = get_release_version()

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
    "extensions/com_pungamail/administrator/components/com_pungamail/sql/updates/mysql/0.3.5.sql",
    "extensions/com_pungamail/administrator/components/com_pungamail/sql/updates/mysql/0.3.6.sql",
    "extensions/com_pungamail/administrator/components/com_pungamail/sql/updates/mysql/0.3.7.sql",
    "extensions/com_pungamail/administrator/components/com_pungamail/sql/updates/mysql/0.3.8.sql",
    "extensions/com_pungamail/administrator/components/com_pungamail/sql/updates/mysql/0.3.9.sql",
    "extensions/com_pungamail/administrator/components/com_pungamail/sql/updates/mysql/0.3.10.sql",
    "extensions/com_pungamail/administrator/components/com_pungamail/sql/updates/mysql/0.3.11.sql",
    "extensions/com_pungamail/administrator/components/com_pungamail/src/Field/NewcontenttemplateField.php",
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
        "## Channels",
        "## Subscribers",
        "## Templates",
        "## Newsletters",
        "## Automatic Newsletters",
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
        "Channels | Multi-select containing all currently published Channels.",
        "Password | Mailbox password. An existing password is never shown.",
        "Punga Mail — Create automatic newsletters",
        "Explicitly reactivate protected addresses",
    ):
        if token not in guide:
            fail(f"Administrator guide is missing required safety guidance: {token!r}")

    test_guide = (ROOT / "docs/TEST_GUIDE.md").read_text(encoding="utf-8")
    required_test_sections = (
        "## A. Installation, update, navigation, and dashboard",
        "## B. Component Options and diagnostics",
        "## C. Channels",
        "## D. Subscribers and consent state",
        "## E. Frontend module, confirmation, unsubscribe, and Joomla profile",
        "## F. Templates and rendering",
        "## G. Newsletter composition and selected content",
        "## H. Preview, test mail, check before sending, and recipient inspection",
        "## I. Queue, scheduled sending, snapshots, browser view, and statistics",
        "## J. Automatic Newsletters",
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

    marker_035 = (sql_root / "updates/mysql/0.3.5.sql").read_text(encoding="utf-8")
    if any(token in marker_035.upper() for token in ("ALTER TABLE", "CREATE TABLE", "DROP TABLE")):
        fail("0.3.5 changes subscription management only; its version-marker migration must not change schema")

    marker_036 = (sql_root / "updates/mysql/0.3.6.sql").read_text(encoding="utf-8")
    if any(token in marker_036.upper() for token in ("ALTER TABLE", "CREATE TABLE", "DROP TABLE")):
        fail("0.3.6 changes terminology and explanations only; its version-marker migration must not change schema")

    marker_037 = (sql_root / "updates/mysql/0.3.7.sql").read_text(encoding="utf-8")
    if any(token in marker_037.upper() for token in ("ALTER TABLE", "CREATE TABLE", "DROP TABLE")):
        fail("0.3.7 changes UI and controller behavior only; its version-marker migration must not change schema")

    marker_038 = (sql_root / "updates/mysql/0.3.8.sql").read_text(encoding="utf-8")
    if any(token in marker_038.upper() for token in ("ALTER TABLE", "CREATE TABLE", "DROP TABLE")):
        fail("0.3.8 changes recipient-editor presentation only; its version-marker migration must not change schema")

    marker_039 = (sql_root / "updates/mysql/0.3.9.sql").read_text(encoding="utf-8")
    required_039 = (
        "ALTER TABLE `#__pungamail_templates`",
        "ADD COLUMN `new_content_item_template` MEDIUMTEXT NULL AFTER `body_markdown`",
        "ALTER TABLE `#__pungamail_newsletters`",
    )
    for fragment in required_039:
        if fragment not in marker_039:
            fail(f"0.3.9 new-content layout migration is missing {fragment!r}")

    if any(token in marker_039.upper() for token in ("CREATE TABLE", "DROP TABLE", "PREPARE ", "EXECUTE ")):
        fail("0.3.9 migration must remain limited to direct portable ALTER TABLE statements")

    marker_0310 = (sql_root / "updates/mysql/0.3.10.sql").read_text(encoding="utf-8")
    if any(token in marker_0310.upper() for token in ("ALTER TABLE", "CREATE TABLE", "DROP TABLE")):
        fail("0.3.10 changes administrator presentation only; its version-marker migration must not change schema")

    marker_0311 = (sql_root / "updates/mysql/0.3.11.sql").read_text(encoding="utf-8")
    if any(token in marker_0311.upper() for token in ("ALTER TABLE", "CREATE TABLE", "DROP TABLE")):
        fail("0.3.11 changes Markdown rendering and administrator presentation only; its version-marker migration must not change schema")


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


def check_subscription_management_035() -> None:
    """Protect the complete menu-page and administrator topic workflow."""

    site_root = ROOT / "extensions/com_pungamail/components/com_pungamail"
    model = (site_root / "src/Model/SubscriptionModel.php").read_text(encoding="utf-8")
    layout = (site_root / "tmpl/subscription/default.php").read_text(encoding="utf-8")
    controller = (site_root / "src/Controller/SubscriptionController.php").read_text(encoding="utf-8")

    for token in ("topics()->active()", "selected_topic_ids", "getSubscriberTopicIds"):
        if token not in model:
            fail(f"Subscription menu-page model is missing topic state: {token!r}")
    for token in ("topic_ids[]", "subscription.userTopics", "COM_PUNGAMAIL_SUBSCRIPTION_SAVE_TOPICS"):
        if token not in layout:
            fail(f"Subscription menu-page layout is missing topic controls: {token!r}")
    if "return ServiceFactory::topics()->active();" not in controller:
        fail("Standalone subscription requests do not expose all published topics")

    admin_root = ROOT / "extensions/com_pungamail/administrator/components/com_pungamail"
    topic_repository = (admin_root / "src/Service/TopicRepository.php").read_text(encoding="utf-8")
    route = (admin_root / "src/Service/AdministratorRoute.php").read_text(encoding="utf-8")
    form = (admin_root / "forms/subscriber.xml").read_text(encoding="utf-8")
    view = (admin_root / "src/View/Subscriber/HtmlView.php").read_text(encoding="utf-8")
    edit_layout = (admin_root / "tmpl/subscriber/default.php").read_text(encoding="utf-8")
    list_layout = (admin_root / "tmpl/subscribers/default.php").read_text(encoding="utf-8")
    edit_controller = (admin_root / "src/Controller/SubscriberController.php").read_text(encoding="utf-8")

    required = (
        (route, "subscriber(int $id = 0)", "subscriber edit route"),
        (form, 'name="status"', "raw global status field"),
        (view, "ToolbarHelper::apply('subscriber.save')", "subscriber Apply action"),
        (view, "ToolbarHelper::save('subscriber.save2close')", "subscriber Save & Close action"),
        (edit_layout, "jform[topic_ids][]", "administrator topic selector"),
        (list_layout, "AdministratorRoute::subscriber((int) $item->id)", "subscriber edit link"),
        (edit_controller, "function save2close()", "subscriber Save & Close controller"),
        (edit_controller, "updateAdministratorTopics", "administrator Channel persistence"),
        (edit_controller, "if ($newStatus !== $currentStatus)", "topic/consent state separation"),
        (edit_controller, "guardExistingMutation", "create/edit ACL separation"),
        (topic_repository, "isset($selected[$topicId]) ? self::MEMBERSHIP_PENDING : self::MEMBERSHIP_UNSUBSCRIBED", "complete double-opt-in topic staging"),
    )
    for contents, token, label in required:
        if token not in contents:
            fail(f"0.3.5 is missing {label}: {token!r}")
    if "Text::_('COM_PUNGAMAIL_SUPPRESSED'), 'x.reason'" in list_layout:
        fail("Subscribers list still renders the redundant suppression column")

    for locale in ("en-GB", "de-DE"):
        site_language = (site_root / f"language/{locale}/com_pungamail.ini").read_text(encoding="utf-8")
        admin_language = (admin_root / f"language/{locale}/com_pungamail.ini").read_text(encoding="utf-8")
        for key in ("COM_PUNGAMAIL_SUBSCRIPTION_TOPICS=", "COM_PUNGAMAIL_SUBSCRIPTION_SAVE_TOPICS="):
            if key not in site_language:
                fail(f"0.3.5 site catalog {locale} is missing {key}")
        for key in ("COM_PUNGAMAIL_EDIT_SUBSCRIBER=", "COM_PUNGAMAIL_SUBSCRIBER_TOPICS_HELP="):
            if key not in admin_language:
                fail(f"0.3.5 administrator catalog {locale} is missing {key}")


def check_audience_clarity_036() -> None:
    """Protect the explicit newsletter-permission and audience explanations."""

    admin_root = ROOT / "extensions/com_pungamail/administrator/components/com_pungamail"
    site_root = ROOT / "extensions/com_pungamail/components/com_pungamail"
    newsletter_layout = (admin_root / "tmpl/newsletter/default.php").read_text(encoding="utf-8")
    digest_layout = (admin_root / "tmpl/digest/default.php").read_text(encoding="utf-8")
    subscriber_layout = (admin_root / "tmpl/subscriber/default.php").read_text(encoding="utf-8")
    preflight = (admin_root / "src/Service/PreflightService.php").read_text(encoding="utf-8")
    subscription_layout = (site_root / "tmpl/subscription/default.php").read_text(encoding="utf-8")
    module_layout = (ROOT / "extensions/mod_pungamail_signup/tmpl/default.php").read_text(encoding="utf-8")

    required = (
        (newsletter_layout, 'id="pm-audience-summary"', "newsletter live audience summary"),
        (newsletter_layout, "COM_PUNGAMAIL_AUDIENCE_ALL_TOPICS_WARNING", "newsletter all/topic warning"),
        (digest_layout, 'id="pm-digest-audience-summary"', "digest live audience summary"),
        (subscriber_layout, "COM_PUNGAMAIL_SUBSCRIBER_DELIVERY_HEALTH", "subscriber delivery-health section"),
        (subscription_layout, "COM_PUNGAMAIL_SUBSCRIPTION_MASTER_HELP", "public master-permission explanation"),
        (subscription_layout, "pm-subscription-topic-summary", "public topic-consequence summary"),
        (module_layout, "MOD_PUNGAMAIL_SIGNUP_TOPIC_NONE_HELP", "module no-topic consequence"),
        (preflight, "COM_PUNGAMAIL_PREFLIGHT_AUDIENCE_MISSING", "missing-audience blocker"),
        (preflight, "COM_PUNGAMAIL_PREFLIGHT_ALL_TOPICS_OVERRIDE", "recipient-specific all/topic warning"),
    )

    for contents, token, label in required:
        if token not in contents:
            fail(f"0.3.6 is missing {label}: {token!r}")

    for locale in ("en-GB", "de-DE"):
        catalogs = (
            (admin_root / f"language/{locale}/com_pungamail.ini").read_text(encoding="utf-8"),
            (site_root / f"language/{locale}/com_pungamail.ini").read_text(encoding="utf-8"),
            (ROOT / f"extensions/mod_pungamail_signup/language/{locale}/mod_pungamail_signup.ini").read_text(encoding="utf-8"),
        )
        combined = "\n".join(catalogs)

        for obsolete in ("Lists / Topics", "Mailing lists / topics", "Verteiler / Themen"):
            if obsolete in combined:
                fail(f"0.3.6 catalog {locale} retains obsolete topic wording: {obsolete!r}")



def ini_values(path: Path) -> dict[str, str]:
    """Read simple Joomla INI key/value pairs for release-quality checks.

    @param path Joomla language file.
    @return Values keyed by language constant.
    """

    values: dict[str, str] = {}

    for line in path.read_text(encoding="utf-8").splitlines():
        stripped = line.strip()

        if stripped == "" or stripped.startswith(";") or "=" not in line:
            continue

        key, value = line.split("=", 1)
        values[key.strip()] = value.strip().strip('"')

    return values


def check_ux_and_fixes_037() -> None:
    """Protect the 0.3.7 Channel UX and form-state regressions."""

    component_manifest = (ROOT / "extensions/com_pungamail/pungamail.xml").read_text(encoding="utf-8")
    templates_pos = component_manifest.find('<menu view="templates">')
    channels_pos = component_manifest.find('<menu view="topics">')
    subscribers_pos = component_manifest.find('<menu view="subscribers">')

    if min(templates_pos, channels_pos, subscribers_pos) < 0 or not (templates_pos < channels_pos < subscribers_pos):
        fail("Channels are not positioned directly after Templates in the administrator submenu")

    admin_root = ROOT / "extensions/com_pungamail/administrator/components/com_pungamail"
    site_root = ROOT / "extensions/com_pungamail/components/com_pungamail"
    topic_layout = (admin_root / "tmpl/topics/default.php").read_text(encoding="utf-8")
    topic_editor = (admin_root / "tmpl/topic/default.php").read_text(encoding="utf-8")
    topics_controller = (admin_root / "src/Controller/TopicsController.php").read_text(encoding="utf-8")
    topic_repository = (admin_root / "src/Service/TopicRepository.php").read_text(encoding="utf-8")
    subscriber_model = (admin_root / "src/Model/SubscriberModel.php").read_text(encoding="utf-8")
    subscriber_controller = (admin_root / "src/Controller/SubscriberController.php").read_text(encoding="utf-8")
    subscriber_layout = (admin_root / "tmpl/subscriber/default.php").read_text(encoding="utf-8")
    subscription_controller = (site_root / "src/Controller/SubscriptionController.php").read_text(encoding="utf-8")
    digest_controller = (admin_root / "src/Controller/DigestController.php").read_text(encoding="utf-8")
    digest_model = (admin_root / "src/Model/DigestModel.php").read_text(encoding="utf-8")

    ordering_requirements = (
        (topic_layout, "HTMLHelper::_('draggablelist.draggable')", "Joomla draggable-list setup"),
        (topic_layout, 'class="js-draggable"', "Joomla draggable-list table body"),
        (topic_layout, "topics.saveOrderAjax", "Channel ordering AJAX endpoint"),
        (topic_layout, 'name="order[]"', "hidden ordering values"),
        (topics_controller, "function saveOrderAjax(): void", "ordering controller action"),
        (topic_repository, "function saveOrdering(array $ids, array $orderings): void", "ordering repository persistence"),
    )
    for contents, token, label in ordering_requirements:
        if token not in contents:
            fail(f"0.3.7 is missing {label}: {token!r}")

    if 'name="ordering"' in topic_editor:
        fail("Channel editor still exposes the raw numeric ordering field")

    subscriber_requirements = (
        (topic_repository, "availableForAdministration(): array", "administrator Channel list"),
        (topic_repository, "updateAdministratorTopics", "administrator Channel membership persistence"),
        (subscriber_model, "availableForAdministration()", "subscriber editor Channel loading"),
        (subscriber_model, "getSelectedTopicIds", "subscriber editor selected memberships"),
        (subscriber_controller, "updateAdministratorTopics", "subscriber Channel save path"),
        (subscriber_layout, "jform[topic_ids][]", "subscriber Channel checkboxes"),
        (subscriber_layout, "Text::_('JUNPUBLISHED')", "unpublished Channel marker"),
    )
    for contents, token, label in subscriber_requirements:
        if token not in contents:
            fail(f"0.3.7 is missing {label}: {token!r}")

    subscription_requirements = (
        "post->getInt('module_id', 0)",
        "private function moduleTopics(int $moduleId): array",
        "if ($moduleId <= 0)",
        "return ServiceFactory::topics()->active();",
    )
    for token in subscription_requirements:
        if token not in subscription_controller:
            fail(f"Standalone Newsletter page fix is missing {token!r}")

    digest_requirements = (
        (digest_controller, "com_pungamail.edit.digest.data", "failed-save form state"),
        (digest_controller, "AdministratorRoute::digest($id)", "failed-save editor redirect"),
        (digest_model, "getSubmittedData(): array", "submitted Digest data restoration"),
        (digest_model, "array_key_exists($key, $submitted)", "submitted scalar-field restoration"),
    )
    for contents, token, label in digest_requirements:
        if token not in contents:
            fail(f"Automatic Newsletter validation fix is missing {label}: {token!r}")

    language_files = sorted((ROOT / "extensions").rglob("*.ini"))
    forbidden_phrases = (
        "double opt-in",
        "double-opt-in",
        "newsletter topics",
        "newsletter-themen",
        "topic preferences",
        "themenauswahl",
        "subscriber suppressed",
        "bounce suppression",
        "process send queue",
        "generate automatic digests",
    )

    for language_file in language_files:
        values = ini_values(language_file)

        for key, value in values.items():
            if value.strip() == "":
                fail(f"Empty language value in {language_file.relative_to(ROOT)}: {key}")

            lowered = value.casefold()
            for phrase in forbidden_phrases:
                if phrase.casefold() in lowered:
                    fail(f"Technical/obsolete UI wording remains in {language_file.relative_to(ROOT)}: {key}={value!r}")

    for locale, expected in (("en-GB", "Channels"), ("de-DE", "Kanäle")):
        admin_main = admin_root / f"language/{locale}/com_pungamail.ini"
        admin_sys = admin_root / f"language/{locale}/com_pungamail.sys.ini"
        site_main = site_root / f"language/{locale}/com_pungamail.ini"
        site_sys = site_root / f"language/{locale}/com_pungamail.sys.ini"

        admin_values = ini_values(admin_main)
        if admin_values.get("COM_PUNGAMAIL_SUBMENU_TOPICS") != expected:
            fail(f"Administrator Channel label is wrong for {locale}")

        for main_path, sys_path in ((admin_main, admin_sys), (site_main, site_sys)):
            main_values = ini_values(main_path)
            sys_values = ini_values(sys_path)
            for key in main_values.keys() & sys_values.keys():
                if main_values[key] != sys_values[key]:
                    fail(f"System-language value differs from normal catalog for {locale}: {key}")



def check_recipient_identity_038() -> None:
    """Protect the 0.3.8 linked-user recipient-name behavior."""

    admin_root = ROOT / "extensions/com_pungamail/administrator/components/com_pungamail"
    model = (admin_root / "src/Model/SubscriberModel.php").read_text(encoding="utf-8")
    layout = (admin_root / "tmpl/subscriber/default.php").read_text(encoding="utf-8")
    form = (admin_root / "forms/subscriber.xml").read_text(encoding="utf-8")

    requirements = (
        (model, "UserFactoryInterface::class", "live Joomla user lookup"),
        (model, "$item->user_name", "resolved Joomla display name"),
        (layout, "$this->item->user_id !== null", "linked-user presentation branch"),
        (layout, "COM_PUNGAMAIL_JOOMLA_DISPLAY_NAME", "linked-user display-name field"),
        (layout, "$this->form?->renderField('recipient_name')", "external recipient-name editor"),
        (form, 'showon="recipient_type:email"', "new-recipient external-name visibility"),
    )

    for contents, token, label in requirements:
        if token not in contents:
            fail(f"0.3.8 recipient identity fix is missing {label}: {token!r}")

    linked_branch = layout.find("$this->item !== null && $this->item->user_id !== null")
    external_name = layout.find("$this->form?->renderField('recipient_name')")
    branch_else = layout.find("<?php else : ?>", linked_branch)

    if min(linked_branch, branch_else, external_name) < 0 or not (linked_branch < branch_else < external_name):
        fail("0.3.8 does not keep the editable recipient-name field out of the linked-user branch")

    for language in ("en-GB", "de-DE"):
        values = ini_values(admin_root / f"language/{language}/com_pungamail.ini")
        for key in ("COM_PUNGAMAIL_JOOMLA_DISPLAY_NAME", "COM_PUNGAMAIL_JOOMLA_DISPLAY_NAME_DESC"):
            if values.get(key, "").strip() == "":
                fail(f"0.3.8 is missing {language} recipient display-name copy: {key}")

def check_automatic_newsletter_ux_039() -> None:
    """Protect the 0.3.9 Automatic Newsletter and selected-content UX."""

    admin_root = ROOT / "extensions/com_pungamail/administrator/components/com_pungamail"
    digest_layout = (admin_root / "tmpl/digest/default.php").read_text(encoding="utf-8")
    digest_controller = (admin_root / "src/Controller/DigestController.php").read_text(encoding="utf-8")
    renderer = (admin_root / "src/Service/NewsletterRenderer.php").read_text(encoding="utf-8")
    mail_config = (admin_root / "src/Service/MailConfigurationService.php").read_text(encoding="utf-8")
    config = (admin_root / "config.xml").read_text(encoding="utf-8")
    migration = (admin_root / "sql/updates/mysql/0.3.9.sql").read_text(encoding="utf-8")
    task_en = (ROOT / "extensions/plg_task_pungamail/language/en-GB/plg_task_pungamail.ini").read_text(encoding="utf-8")
    task_de = (ROOT / "extensions/plg_task_pungamail/language/de-DE/plg_task_pungamail.ini").read_text(encoding="utf-8")

    required = (
        (digest_layout, 'name="recurrence_days"', "day-based recurrence input"),
        (digest_layout, 'name="rolling_days"', "day-based rolling input"),
        (digest_layout, "rollingPeriod.hidden", "conditional rolling-period field"),
        (digest_layout, "autoConfirm.hidden", "conditional unattended-send confirmation"),
        (digest_controller, "getInt('recurrence_days', 7)) * 1440", "day-to-minute persistence conversion"),
        (digest_controller, "getInt('rolling_days', 7)) * 24", "day-to-hour persistence conversion"),
        (renderer, "{publish_date}", "publish-date selected-content placeholder"),
        (renderer, "{title_link}", "linked-title selected-content placeholder"),
        (renderer, "newContentItemTemplate", "selected-content template resolution"),
        (mail_config, "DEFAULT_NEW_CONTENT_ITEM_TEMPLATE", "safe selected-content default"),
        (config, 'name="new_content_item_template"', "global selected-content layout setting"),
        (migration, "new_content_item_template", "selected-content layout schema update"),
        (task_en, "For ordinary newsletters that you created manually", "clear English scheduled-send task description"),
        (task_en, "For recurring Automatic Newsletters", "clear English automatic-newsletter task description"),
        (task_de, "Für normale Newsletter, die Sie manuell erstellt", "clear German scheduled-send task description"),
        (task_de, "Für wiederkehrende automatische Newsletter", "clear German automatic-newsletter task description"),
    )

    for contents, token, label in required:
        if token not in contents:
            fail(f"0.3.9 is missing {label}: {token!r}")

    if 'name="recurrence_minutes"' in digest_layout or 'name="rolling_hours"' in digest_layout:
        fail("0.3.9 still exposes minute/hour scheduling fields in the Automatic Newsletter editor")

    for rel in (
        "tmpl/preview/default.php",
        "tmpl/preflight/default.php",
        "tmpl/templatepreview/default.php",
        "tmpl/newsletter/default.php",
    ):
        contents = (admin_root / rel).read_text(encoding="utf-8")
        if "BROWSER_PLACEHOLDER" not in contents or "aria-disabled" not in contents:
            fail(f"0.3.9 does not disable View in browser in {rel}")

    for locale in ("en-GB", "de-DE"):
        values = ini_values(admin_root / f"language/{locale}/com_pungamail.ini")
        for key in (
            "COM_PUNGAMAIL_RECURRENCE_DAYS",
            "COM_PUNGAMAIL_ROLLING_DAYS",
            "COM_PUNGAMAIL_NEW_CONTENT_ITEM_TEMPLATE",
            "COM_PUNGAMAIL_PREVIEW_BROWSER_DISABLED",
        ):
            if values.get(key, "").strip() == "":
                fail(f"0.3.9 is missing {locale} UX copy: {key}")



def check_newsletter_editor_ux_0310() -> None:
    """Protect the 0.3.10 tabbed Newsletter and Markdown-template editor UX."""

    admin_root = ROOT / "extensions/com_pungamail/administrator/components/com_pungamail"
    newsletter = (admin_root / "tmpl/newsletter/default.php").read_text(encoding="utf-8")
    template = (admin_root / "tmpl/template/default.php").read_text(encoding="utf-8")
    config = (admin_root / "config.xml").read_text(encoding="utf-8")
    field = (admin_root / "src/Field/NewcontenttemplateField.php").read_text(encoding="utf-8")

    for token, label in (
        ("uitab.startTabSet", "Joomla tab set"),
        ("pm-settings", "Settings tab"),
        ("pm-mail-content", "Mail content tab"),
        ("pm-content-selection", "Content selection tab"),
        ("pm-design", "Design tab"),
        ("uitab.endTabSet", "closed Joomla tab set"),
    ):
        if token not in newsletter:
            fail(f"0.3.10 Newsletter editor is missing {label}: {token!r}")

    if newsletter.count("uitab.addTab") != 4 or newsletter.count("HTMLHelper::_('uitab.endTab')") != 4:
        fail("0.3.10 Newsletter editor must contain exactly four balanced Joomla tabs")

    for token in ("pm-content-search", "pm-content-list", "pm-audience-summary", "newsletter.applyTemplate"):
        if token not in newsletter:
            fail(f"0.3.10 tab refactor dropped existing Newsletter control: {token}")

    if 'type="newcontenttemplate"' not in config or "NewcontenttemplateField" not in field:
        fail("0.3.10 Component Options does not use the dedicated new-content Markdown editor field")

    if "font-monospace" not in field or "COM_PUNGAMAIL_NEW_CONTENT_ITEM_TEMPLATE_PLACEHOLDER_HELP" not in field:
        fail("0.3.10 Component Options new-content editor is missing monospaced styling or visible placeholder help")

    for contents, label in ((newsletter, "Newsletter"), (template, "Template")):
        if 'class="form-control font-monospace"' not in contents:
            fail(f"0.3.10 {label} new-content editor is not monospaced")
        if "COM_PUNGAMAIL_NEW_CONTENT_ITEM_TEMPLATE_PLACEHOLDER_HELP" not in contents:
            fail(f"0.3.10 {label} new-content editor is missing placeholder help")

    for locale in ("en-GB", "de-DE"):
        values = ini_values(admin_root / f"language/{locale}/com_pungamail.ini")
        for key in (
            "COM_PUNGAMAIL_TAB_SETTINGS",
            "COM_PUNGAMAIL_TAB_MAIL_CONTENT",
            "COM_PUNGAMAIL_TAB_CONTENT_SELECTION",
            "COM_PUNGAMAIL_TAB_DESIGN",
            "COM_PUNGAMAIL_NEW_CONTENT_ITEM_TEMPLATE_PLACEHOLDER_HELP",
        ):
            if values.get(key, "").strip() == "":
                fail(f"0.3.10 is missing {locale} Newsletter editor copy: {key}")



def check_markdown_and_override_ux_0311() -> None:
    """Protect the 0.3.11 Markdown fixes and collapsed new-content overrides."""

    admin_root = ROOT / "extensions/com_pungamail/administrator/components/com_pungamail"
    renderer = (admin_root / "src/Service/MarkdownRenderer.php").read_text(encoding="utf-8")
    newsletter = (admin_root / "tmpl/newsletter/default.php").read_text(encoding="utf-8")
    template = (admin_root / "tmpl/template/default.php").read_text(encoding="utf-8")

    for token in ("hard_break", "protectMarkdownEscapes", "unescapeMarkdown"):
        if token not in renderer:
            fail(f"0.3.11 Markdown renderer is missing regression fix: {token}")

    for contents, label in ((newsletter, "Newsletter"), (template, "Template")):
        if '<details class="card mb-3 pm-new-content-override">' not in contents:
            fail(f"0.3.11 {label} new-content override is not a native collapsed details panel")
        if '<details class="card mb-3 pm-new-content-override" open' in contents:
            fail(f"0.3.11 {label} new-content override must be collapsed by default")
        if "COM_PUNGAMAIL_CUSTOM_OVERRIDE_ACTIVE" not in contents:
            fail(f"0.3.11 {label} new-content override does not indicate an active custom override")

    for locale in ("en-GB", "de-DE"):
        values = ini_values(admin_root / f"language/{locale}/com_pungamail.ini")
        if values.get("COM_PUNGAMAIL_CUSTOM_OVERRIDE_ACTIVE", "").strip() == "":
            fail(f"0.3.11 is missing {locale} custom-override badge copy")


def check_release_ux_0312() -> None:
    """Protect the 0.3.12 editor, rendering, and excerpt-sanitization changes."""

    admin_root = ROOT / "extensions/com_pungamail/administrator/components/com_pungamail"
    newsletter = (admin_root / "tmpl/newsletter/default.php").read_text(encoding="utf-8")
    template = (admin_root / "tmpl/template/default.php").read_text(encoding="utf-8")
    renderer = (admin_root / "src/Service/NewsletterRenderer.php").read_text(encoding="utf-8")
    markdown = (admin_root / "src/Service/MarkdownRenderer.php").read_text(encoding="utf-8")
    styles = (admin_root / "src/Service/MailStyleService.php").read_text(encoding="utf-8")
    config = (admin_root / "config.xml").read_text(encoding="utf-8")
    marker = (admin_root / "sql/updates/mysql/0.3.12.sql").read_text(encoding="utf-8")

    settings_pos = newsletter.find("'pm-settings'")
    mail_pos = newsletter.find("'pm-mail-content'")
    template_pos = newsletter.find('id="pm-template"')
    if not (settings_pos >= 0 and mail_pos > settings_pos and template_pos > mail_pos):
        fail("0.3.12 Template selector is not located in the Newsletter Mail content tab")

    for contents, label in ((newsletter, "Newsletter"), (template, "Template")):
        for token in ('pm-collapse-indicator', '▶', '[open] .pm-collapse-indicator'):
            if token not in contents:
                fail(f"0.3.12 {label} selected-content override is missing disclosure-state indicator: {token}")

    for token, label in (
        ('name="design_heading_background"', "global heading-background option"),
        ("'heading_background'", "layered heading-background style"),
        ('width:100%', "full-width mail heading"),
        ('stripContentPluginTokens', "content-plugin token sanitization"),
        ("$trimmed === '---'", "Markdown horizontal rule"),
        ("'<hr>'", "horizontal-rule HTML output"),
        ("'<hr>' =>", "email-safe horizontal-rule styling"),
    ):
        haystack = config if token.startswith('name=') else (renderer + markdown + styles)
        if token not in haystack:
            fail(f"0.3.12 is missing {label}: {token!r}")

    if re.search(r"\b(?:ALTER|CREATE|DROP|RENAME)\b", marker, re.IGNORECASE):
        fail("0.3.12 must remain a no-schema-change migration marker")

    for locale in ("en-GB", "de-DE"):
        values = ini_values(admin_root / f"language/{locale}/com_pungamail.ini")
        for key in ("COM_PUNGAMAIL_STYLE_HEADING_BACKGROUND", "COM_PUNGAMAIL_STYLE_HEADING_BACKGROUND_DESC"):
            if values.get(key, "").strip() == "":
                fail(f"0.3.12 is missing {locale} heading-background copy: {key}")
        if "---" not in values.get("COM_PUNGAMAIL_MARKDOWN_HELP", ""):
            fail(f"0.3.12 {locale} Markdown help does not mention horizontal rules")

def check_package_members() -> None:
    """Verify expected constituent extension ZIPs in package manifest."""

    tree = ET.parse(PACKAGE_MANIFEST)
    names = {node.text.strip() for node in tree.getroot().findall("./files/file") if node.text}
    expected = {"com_pungamail.zip", "mod_pungamail_signup.zip", "plg_user_pungamail.zip", "plg_task_pungamail.zip"}
    if names != expected:
        fail(f"Package constituents differ: got {sorted(names)}, expected {sorted(expected)}")


def check_release_metadata_source() -> None:
    """Ensure release scripts continue to derive metadata from the manifest."""

    build = (ROOT / "build.py").read_text(encoding="utf-8")
    checker = (ROOT / "tools/check.py").read_text(encoding="utf-8")

    for path, contents in (("build.py", build), ("tools/check.py", checker)):
        if re.search(r'^VERSION\s*=\s*["\']', contents, re.MULTILINE) is not None:
            fail(f"{path} contains a hard-coded release VERSION")

        if "package/pkg_pungamail.xml" not in contents:
            fail(f"{path} does not use the canonical package manifest")

    for token in ('findtext("packagename")', 'findtext("version")', 'findall("./files/file")'):
        if token not in build:
            fail(f"build.py does not derive package metadata from the manifest: {token}")


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


def check_joomla_base_method_collisions() -> None:
    """Reject model methods that incompatibly override Joomla base methods."""

    model_roots = (
        ROOT / "extensions/com_pungamail/components/com_pungamail/src/Model",
        ROOT / "extensions/com_pungamail/administrator/components/com_pungamail/src/Model",
    )

    for model_root in model_roots:
        for path in sorted(model_root.glob("*Model.php")):
            contents = path.read_text(encoding="utf-8")

            if "extends BaseDatabaseModel" in contents and re.search(
                r"public\s+function\s+getState\s*\(\s*\)",
                contents,
            ) is not None:
                fail(f"Model incompatibly overrides Joomla getState(): {path.relative_to(ROOT)}")

    subscription_model = (
        ROOT / "extensions/com_pungamail/components/com_pungamail/src/Model/SubscriptionModel.php"
    ).read_text(encoding="utf-8")
    subscription_view = (
        ROOT / "extensions/com_pungamail/components/com_pungamail/src/View/Subscription/HtmlView.php"
    ).read_text(encoding="utf-8")

    if "function getSubscriptionState(): array" not in subscription_model:
        fail("Subscription model is missing its non-conflicting state accessor")

    if "->getSubscriptionState()" not in subscription_view:
        fail("Subscription view does not use the non-conflicting state accessor")


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
        check_subscription_management_035,
        check_audience_clarity_036,
        check_ux_and_fixes_037,
        check_recipient_identity_038,
        check_automatic_newsletter_ux_039,
        check_newsletter_editor_ux_0310,
        check_markdown_and_override_ux_0311,
        check_release_ux_0312,
        check_package_members,
        check_release_metadata_source,
        check_feature_contracts,
        check_joomla_base_method_collisions,
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

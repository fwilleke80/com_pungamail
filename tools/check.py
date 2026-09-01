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
VERSION = "0.2.5"

REQUIRED_FILES: tuple[str, ...] = (
    "README.md",
    "LICENSE.md",
    "CHANGELOG.md",
    "docs/DATABASE.md",
    "docs/CONCEPT.md",
    "package/pkg_pungamail.xml",
    "package/script.php",
    "extensions/com_pungamail/pungamail.xml",
    "extensions/com_pungamail/components/com_pungamail/tmpl/subscription/default.xml",
    "extensions/com_pungamail/components/com_pungamail/src/Service/Router.php",
    "extensions/com_pungamail/administrator/components/com_pungamail/src/Service/ContentTypeService.php",
    "extensions/com_pungamail/administrator/components/com_pungamail/src/Service/AdministratorRoute.php",
    "extensions/com_pungamail/administrator/components/com_pungamail/src/Service/RecipientName.php",
    "extensions/com_pungamail/administrator/components/com_pungamail/src/Service/MailStyleService.php",
    "extensions/com_pungamail/administrator/components/com_pungamail/src/Service/MailTextService.php",
    "extensions/com_pungamail/administrator/components/com_pungamail/src/Field/MailfooterField.php",
    "extensions/com_pungamail/administrator/components/com_pungamail/src/Service/ReminderService.php",
    "extensions/com_pungamail/administrator/components/com_pungamail/src/Service/TemplateRepository.php",
    "extensions/com_pungamail/administrator/components/com_pungamail/sql/install.mysql.sql",
    "extensions/com_pungamail/administrator/components/com_pungamail/sql/updates/mysql/0.1.0.sql",
    "extensions/com_pungamail/administrator/components/com_pungamail/sql/updates/mysql/0.1.1.sql",
    "extensions/com_pungamail/administrator/components/com_pungamail/sql/updates/mysql/0.2.0.sql",
    "extensions/com_pungamail/administrator/components/com_pungamail/sql/updates/mysql/0.2.1.sql",
    "extensions/com_pungamail/administrator/components/com_pungamail/sql/updates/mysql/0.2.2.sql",
    "extensions/com_pungamail/administrator/components/com_pungamail/sql/updates/mysql/0.2.4.sql",
    "extensions/com_pungamail/administrator/components/com_pungamail/sql/updates/mysql/0.2.5.sql",
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
    for token in ("view=newsletters", "screen=newsletter", "screen=preview", "screen=preflight", "view=templates", "screen=template", "screen=templatepreview"):
        if token not in route_service:
            fail(f"Administrator sidebar route contract is missing {token!r}")
    for screen in ("newsletter", "preview", "preflight", "template", "templatepreview"):
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
    )
    for english, german in pairs:
        en_keys = language_keys(english)
        de_keys = language_keys(german)
        if en_keys != de_keys:
            fail(f"Language key mismatch for {english.name}: EN-only={sorted(en_keys-de_keys)}, DE-only={sorted(de_keys-en_keys)}")


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


def check_package_members() -> None:
    """Verify expected constituent extension ZIPs in package manifest."""

    tree = ET.parse(ROOT / "package/pkg_pungamail.xml")
    names = {node.text.strip() for node in tree.getroot().findall("./files/file") if node.text}
    expected = {"com_pungamail.zip", "mod_pungamail_signup.zip", "plg_user_pungamail.zip", "plg_task_pungamail.zip"}
    if names != expected:
        fail(f"Package constituents differ: got {sorted(names)}, expected {sorted(expected)}")


def check_feature_contracts() -> None:
    """Smoke-check release-defining 0.2.x integration points."""

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
        check_xml,
        check_php,
        check_migration_chain,
        check_no_runtime_schema_mutation,
        check_bind_values_are_variables,
        check_renderer_regressions,
        check_new_content_pipeline,
        check_administrator_sidebar_routes,
        check_editor_toolbars,
        check_queue_admin_workflow,
        check_language_parity,
        check_mail_language_placement,
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

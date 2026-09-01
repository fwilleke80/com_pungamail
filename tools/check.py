#!/usr/bin/env python3
"""Static release checks for Punga Mail.

The checks intentionally use only Python's standard library so they can run on
any development machine capable of building the Joomla package.
"""

from __future__ import annotations

import hashlib
import re
import shutil
import subprocess
import sys
import xml.etree.ElementTree as ET
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
VERSION = "0.1.1"

REQUIRED_FILES: tuple[str, ...] = (
    "README.md",
    "LICENSE.md",
    "CHANGELOG.md",
    "docs/DATABASE.md",
    "docs/CONCEPT.md",
    "package/pkg_pungamail.xml",
    "package/script.php",
    "extensions/com_pungamail/pungamail.xml",
    "extensions/mod_pungamail_signup/mod_pungamail_signup.xml",
    "extensions/plg_user_pungamail/pungamail.xml",
    "extensions/plg_task_pungamail/pungamail.xml",
    "extensions/com_pungamail/administrator/components/com_pungamail/sql/install.mysql.sql",
    "extensions/com_pungamail/administrator/components/com_pungamail/sql/updates/mysql/0.1.1.sql",
    "extensions/com_pungamail/administrator/components/com_pungamail/language/de-DE/com_pungamail.ini",
    "extensions/com_pungamail/components/com_pungamail/language/de-DE/com_pungamail.ini",
    "extensions/mod_pungamail_signup/language/de-DE/mod_pungamail_signup.ini",
    "extensions/plg_user_pungamail/language/de-DE/plg_user_pungamail.ini",
    "extensions/plg_task_pungamail/language/de-DE/plg_task_pungamail.ini",
)


def fail(message: str) -> None:
    """Terminate the checker with a clear failure message.

    @param message Human-readable failure description.
    """

    raise RuntimeError(message)


def check_required_files() -> None:
    """Verify that the canonical release tree contains mandatory files."""

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
    """Run PHP's syntax checker over every PHP source file when available."""

    php = shutil.which("php")
    if php is None:
        print("[skip] php executable not found; PHP syntax lint not run")
        return

    for path in sorted(ROOT.rglob("*.php")):
        result = subprocess.run(
            [php, "-l", str(path)],
            check=False,
            capture_output=True,
            text=True,
        )
        if result.returncode != 0:
            fail(f"PHP lint failed for {path.relative_to(ROOT)}:\n{result.stdout}{result.stderr}")


def check_migration_chain() -> None:
    """Validate the immutable 0.1.0 baseline and the 0.1.1 transition."""

    sql_root = ROOT / "extensions/com_pungamail/administrator/components/com_pungamail/sql"
    install = (sql_root / "install.mysql.sql").read_text(encoding="utf-8")
    baseline_path = sql_root / "updates/mysql/0.1.0.sql"
    migration = (sql_root / "updates/mysql/0.1.1.sql").read_text(encoding="utf-8").strip()
    baseline_hash = hashlib.sha256(baseline_path.read_bytes()).hexdigest()

    if baseline_hash != "2653b5d3f4d2f491ee917dcef9ac60acf312be999ef9a4d8c4550054e19e43f6":
        fail("Released 0.1.0 migration was modified; migrations are immutable")

    if "`state` TINYINT NOT NULL DEFAULT 1" not in install:
        fail("Fresh-install schema is missing the 0.1.1 newsletter state column")

    if "idx_pungamail_newsletter_state" not in install:
        fail("Fresh-install schema is missing the 0.1.1 newsletter state index")

    expected = (
        "ALTER TABLE `#__pungamail_newsletters`\n"
        "  ADD COLUMN `state` TINYINT NOT NULL DEFAULT 1 AFTER `body_markdown`,\n"
        "  ADD KEY `idx_pungamail_newsletter_state` (`state`);"
    )

    if migration != expected:
        fail("0.1.1 migration must contain only the documented newsletter-state transition")


def check_no_runtime_schema_mutation() -> None:
    """Reject ad-hoc schema mutation from runtime PHP code."""

    forbidden = ("ALTER TABLE", "CREATE TABLE", "DROP TABLE")

    for path in sorted((ROOT / "extensions").rglob("*.php")):
        text = path.read_text(encoding="utf-8").upper()
        for phrase in forbidden:
            if phrase in text:
                fail(f"Runtime schema mutation {phrase!r} found in {path.relative_to(ROOT)}")



def check_bind_values_are_variables() -> None:
    """Reject Joomla query binds whose value is not a local variable.

    Joomla DatabaseQuery.bind() accepts the value by reference. Passing a
    class constant, function result, ternary expression, array access, or
    object property can therefore fail at runtime even when PHP syntax lint
    succeeds. Keeping every bound value in a local variable also makes types
    and ownership explicit at the persistence boundary.
    """

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
                fail(
                    "Database bind value must be a local variable at "
                    f"{path.relative_to(ROOT)}:{line_number}; got {value!r}"
                )

def check_package_members() -> None:
    """Verify that the package manifest names every expected constituent."""

    tree = ET.parse(ROOT / "package/pkg_pungamail.xml")
    names = {node.text.strip() for node in tree.getroot().findall("./files/file") if node.text}
    expected = {
        "com_pungamail.zip",
        "mod_pungamail_signup.zip",
        "plg_user_pungamail.zip",
        "plg_task_pungamail.zip",
    }

    if names != expected:
        fail(f"Package constituents differ: got {sorted(names)}, expected {sorted(expected)}")


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
        check_package_members,
    )

    try:
        for check in checks:
            check()
    except RuntimeError as exc:
        print(f"[FAIL] {exc}", file=sys.stderr)
        return 1

    print("[OK] Punga Mail release checks passed")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())

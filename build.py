#!/usr/bin/env python3
"""Build Joomla installer artifacts for Punga Mail."""

from __future__ import annotations

import shutil
import subprocess
import sys
import zipfile
from pathlib import Path

ROOT = Path(__file__).resolve().parent
DIST = ROOT / "dist"
VERSION = "0.2.5"
VERSION_FILE = VERSION.replace(".", "-")

EXTENSIONS: tuple[tuple[str, Path], ...] = (
    ("com_pungamail.zip", ROOT / "extensions/com_pungamail"),
    ("mod_pungamail_signup.zip", ROOT / "extensions/mod_pungamail_signup"),
    ("plg_user_pungamail.zip", ROOT / "extensions/plg_user_pungamail"),
    ("plg_task_pungamail.zip", ROOT / "extensions/plg_task_pungamail"),
)


def add_tree(archive: zipfile.ZipFile, source: Path, prefix: Path | None = None) -> None:
    """Add a directory tree to an archive in stable lexical order.

    @param archive Destination ZIP archive.
    @param source Directory whose contents are archived.
    @param prefix Optional path prepended inside the ZIP.
    """

    for path in sorted(source.rglob("*")):
        if not path.is_file():
            continue

        relative = path.relative_to(source)
        archive_name = relative if prefix is None else prefix / relative
        archive.write(path, archive_name.as_posix())


def make_extension_zip(filename: str, source: Path) -> Path:
    """Build one Joomla constituent extension ZIP.

    @param filename Output filename.
    @param source Extension source directory.
    @return Built ZIP path.
    """

    destination = DIST / filename
    with zipfile.ZipFile(destination, "w", compression=zipfile.ZIP_DEFLATED, compresslevel=9) as archive:
        add_tree(archive, source)
    return destination


def build_package(extension_archives: tuple[Path, ...]) -> Path:
    """Build the installable Joomla package ZIP.

    @param extension_archives Constituent extension ZIP files.
    @return Package ZIP path.
    """

    destination = DIST / f"pkg_pungamail_v{VERSION_FILE}.zip"
    with zipfile.ZipFile(destination, "w", compression=zipfile.ZIP_DEFLATED, compresslevel=9) as archive:
        archive.write(ROOT / "package/pkg_pungamail.xml", "pkg_pungamail.xml")
        archive.write(ROOT / "package/script.php", "script.php")
        archive.write(ROOT / "README.md", "README.md")
        archive.write(ROOT / "LICENSE.md", "LICENSE.md")
        for path in extension_archives:
            archive.write(path, path.name)
    return destination


def build_source_archive() -> Path:
    """Build the complete Git-oriented source archive without generated dist files.

    @return Source ZIP path.
    """

    destination = DIST / f"pungamail_v{VERSION_FILE}_source.zip"
    prefix = Path(f"pungamail-{VERSION}")
    excluded_roots = {"dist", ".git", "__pycache__"}

    with zipfile.ZipFile(destination, "w", compression=zipfile.ZIP_DEFLATED, compresslevel=9) as archive:
        for path in sorted(ROOT.rglob("*")):
            if not path.is_file():
                continue
            relative = path.relative_to(ROOT)
            if relative.parts and relative.parts[0] in excluded_roots:
                continue
            if "__pycache__" in relative.parts:
                continue
            archive.write(path, (prefix / relative).as_posix())

    return destination


def main() -> int:
    """Run checks and build all release artifacts.

    @return Process exit status.
    """

    check = subprocess.run([sys.executable, str(ROOT / "tools/check.py")], check=False)
    if check.returncode != 0:
        return check.returncode

    if DIST.exists():
        shutil.rmtree(DIST)
    DIST.mkdir(parents=True)

    extension_archives = tuple(make_extension_zip(name, source) for name, source in EXTENSIONS)
    package = build_package(extension_archives)
    source = build_source_archive()

    print(f"Built {package.relative_to(ROOT)}")
    print(f"Built {source.relative_to(ROOT)}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())

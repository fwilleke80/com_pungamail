#!/usr/bin/env python3
"""Build Joomla installer artifacts for Punga Mail."""

from __future__ import annotations

import re
import shutil
import subprocess
import sys
import zipfile
import xml.etree.ElementTree as ET
from dataclasses import dataclass
from pathlib import Path

ROOT = Path(__file__).resolve().parent
DIST = ROOT / "dist"
PACKAGE_MANIFEST = ROOT / "package/pkg_pungamail.xml"


@dataclass(frozen=True)
class PackageMetadata:
    """Release metadata read from the canonical Joomla package manifest."""

    package_name: str
    version: str
    extensions: tuple[tuple[str, Path], ...]

    @property
    def filename_version(self) -> str:
        """Return the release version formatted for archive filenames.

        @return Hyphen-separated version string.
        """

        return self.version.replace(".", "-")


def load_package_metadata() -> PackageMetadata:
    """Load and validate build metadata from the package manifest.

    @return Validated package name, version, and child-extension archives.
    """

    try:
        manifest = ET.parse(PACKAGE_MANIFEST).getroot()
    except (OSError, ET.ParseError) as exc:
        raise RuntimeError(f"Cannot read package manifest {PACKAGE_MANIFEST}: {exc}") from exc

    package_name = (manifest.findtext("packagename") or "").strip()
    version = (manifest.findtext("version") or "").strip()

    if re.fullmatch(r"[a-z][a-z0-9_]*", package_name) is None:
        raise RuntimeError(f"Invalid package name in {PACKAGE_MANIFEST}: {package_name!r}")

    if re.fullmatch(r"[0-9]+(?:\.[0-9]+){2}(?:[-+][0-9A-Za-z.-]+)?", version) is None:
        raise RuntimeError(f"Invalid release version in {PACKAGE_MANIFEST}: {version!r}")

    extensions: list[tuple[str, Path]] = []
    seen: set[str] = set()

    for entry in manifest.findall("./files/file"):
        filename = (entry.text or "").strip()
        path = Path(filename)

        if path.name != filename or re.fullmatch(r"[a-z0-9_]+\.zip", filename) is None:
            raise RuntimeError(f"Unsafe child-extension filename in {PACKAGE_MANIFEST}: {filename!r}")

        if filename in seen:
            raise RuntimeError(f"Duplicate child-extension filename in {PACKAGE_MANIFEST}: {filename!r}")

        source = ROOT / "extensions" / path.stem

        if not source.is_dir():
            raise RuntimeError(f"Missing source directory for {filename}: {source}")

        seen.add(filename)
        extensions.append((filename, source))

    if not extensions:
        raise RuntimeError(f"Package manifest contains no child extensions: {PACKAGE_MANIFEST}")

    return PackageMetadata(package_name, version, tuple(extensions))


METADATA = load_package_metadata()


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

    destination = DIST / f"pkg_{METADATA.package_name}_v{METADATA.filename_version}.zip"
    with zipfile.ZipFile(destination, "w", compression=zipfile.ZIP_DEFLATED, compresslevel=9) as archive:
        archive.write(PACKAGE_MANIFEST, PACKAGE_MANIFEST.name)
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

    destination = DIST / f"{METADATA.package_name}_v{METADATA.filename_version}_source.zip"
    prefix = Path(f"{METADATA.package_name}-{METADATA.version}")
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

    extension_archives = tuple(make_extension_zip(name, source) for name, source in METADATA.extensions)
    package = build_package(extension_archives)
    source = build_source_archive()

    print(f"Built {package.relative_to(ROOT)}")
    print(f"Built {source.relative_to(ROOT)}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())

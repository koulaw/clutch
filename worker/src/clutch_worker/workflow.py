"""End-to-end object-storage parsing workflow."""

import hashlib
from pathlib import Path
from tempfile import TemporaryDirectory
from typing import Any

from clutch_worker.errors import ChecksumMismatchError
from clutch_worker.parser import AwpyDemoParser
from clutch_worker.storage import DemoStorage
from clutch_worker.writer import ParsedDemoWriter


class ParseDemoWorkflow:
    """Download, validate, parse, and write one demo."""

    def __init__(
        self,
        storage: DemoStorage,
        parser: AwpyDemoParser,
        writer: ParsedDemoWriter,
    ) -> None:
        self._storage = storage
        self._parser = parser
        self._writer = writer

    def run(
        self,
        bucket: str,
        key: str,
        output_directory: Path,
        expected_sha256: str | None = None,
    ) -> dict[str, Any]:
        """Run the complete parsing workflow for one storage object."""
        with TemporaryDirectory(prefix="clutch-demo-") as temporary_directory:
            demo_path = Path(temporary_directory) / "input.dem"
            self._storage.download(bucket, key, demo_path)

            if expected_sha256 and self._sha256(demo_path) != expected_sha256.lower():
                raise ChecksumMismatchError

            parsed = self._parser.parse(demo_path)
            return self._writer.write(parsed, output_directory)

    @staticmethod
    def _sha256(path: Path) -> str:
        digest = hashlib.sha256()
        with path.open("rb") as file:
            for chunk in iter(lambda: file.read(1024 * 1024), b""):
                digest.update(chunk)
        return digest.hexdigest()

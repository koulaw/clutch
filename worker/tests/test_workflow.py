"""Tests for storage download and workflow error handling."""

import hashlib
from pathlib import Path

import pytest

from clutch_worker.errors import ChecksumMismatchError, StorageError
from clutch_worker.storage import S3DemoStorage
from clutch_worker.workflow import ParseDemoWorkflow


class FakeStorage:
    def __init__(self, content: bytes) -> None:
        self.content = content

    def download(self, bucket: str, key: str, destination: Path) -> None:
        assert bucket == "clutch"
        assert key == "demos/reference.dem"
        destination.write_bytes(self.content)


class RecordingParser:
    def __init__(self, result: object) -> None:
        self.result = result
        self.path: Path | None = None

    def parse(self, path: Path) -> object:
        self.path = path
        return self.result


class RecordingWriter:
    def write(self, parsed: object, output_directory: Path) -> dict[str, object]:
        return {"parsed": parsed, "output": str(output_directory)}


def test_downloads_checksums_and_parses_from_object_storage(tmp_path: Path) -> None:
    content = b"PBDEMS2\x00reference"
    parser = RecordingParser(result="parsed-demo")
    workflow = ParseDemoWorkflow(FakeStorage(content), parser, RecordingWriter())

    manifest = workflow.run(
        bucket="clutch",
        key="demos/reference.dem",
        output_directory=tmp_path,
        expected_sha256=hashlib.sha256(content).hexdigest(),
    )

    assert manifest == {"parsed": "parsed-demo", "output": str(tmp_path)}
    assert parser.path is not None
    assert not parser.path.exists()


def test_rejects_a_download_with_the_wrong_checksum(tmp_path: Path) -> None:
    workflow = ParseDemoWorkflow(FakeStorage(b"unexpected"), RecordingParser("unused"), RecordingWriter())

    with pytest.raises(ChecksumMismatchError):
        workflow.run("clutch", "demos/reference.dem", tmp_path, expected_sha256="0" * 64)


def test_normalizes_s3_download_failures(tmp_path: Path) -> None:
    class BrokenClient:
        def download_file(self, bucket: str, key: str, destination: str) -> None:
            raise ConnectionError("storage unavailable")

    with pytest.raises(StorageError) as error:
        S3DemoStorage(BrokenClient()).download("clutch", "demo.dem", tmp_path / "demo.dem")

    assert error.value.retryable is True
    assert error.value.details == {"exception_type": "ConnectionError"}

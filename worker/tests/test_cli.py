"""Tests for the worker's normalized command output."""

import json
from pathlib import Path

from clutch_worker.cli import run
from clutch_worker.errors import UnsupportedDemoError


class SuccessfulWorkflow:
    def run(
        self,
        bucket: str,
        key: str,
        output_directory: Path,
        expected_sha256: str | None,
    ) -> dict[str, object]:
        return {"schema_version": "1.0.0", "rounds": {"rows": 8}}


class FailedWorkflow:
    def run(
        self,
        bucket: str,
        key: str,
        output_directory: Path,
        expected_sha256: str | None,
    ) -> dict[str, object]:
        raise UnsupportedDemoError


def test_emits_a_success_payload(capsys, tmp_path: Path) -> None:
    exit_code = run(
        ["--bucket", "clutch", "--key", "demo.dem", "--output", str(tmp_path)],
        workflow=SuccessfulWorkflow(),
    )

    payload = json.loads(capsys.readouterr().out)
    assert exit_code == 0
    assert payload["ok"] is True
    assert payload["manifest"]["rounds"]["rows"] == 8


def test_emits_a_normalized_error_payload(capsys, tmp_path: Path) -> None:
    exit_code = run(
        ["--bucket", "clutch", "--key", "demo.dem", "--output", str(tmp_path)],
        workflow=FailedWorkflow(),
    )

    payload = json.loads(capsys.readouterr().out)
    assert exit_code == 2
    assert payload == {
        "ok": False,
        "error": {
            "code": "unsupported_demo",
            "message": "The file is not a supported Counter-Strike 2 demo.",
            "retryable": False,
            "details": {},
        },
    }

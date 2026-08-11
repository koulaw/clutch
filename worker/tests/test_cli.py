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
        return {
            "schema_version": "1.0.0",
            "parser_version": "2.0.2",
            "match": {"path": "match.json"},
            "rounds": {"path": "rounds.parquet", "rows": 8},
            "players": {"path": "players.parquet", "rows": 10},
            "events": {},
            "ticks": {"path": "ticks.parquet", "rows": 100},
        }


class FailedWorkflow:
    def run(
        self,
        bucket: str,
        key: str,
        output_directory: Path,
        expected_sha256: str | None,
    ) -> dict[str, object]:
        raise UnsupportedDemoError


class InvalidOutputWorkflow:
    def run(
        self,
        bucket: str,
        key: str,
        output_directory: Path,
        expected_sha256: str | None,
    ) -> dict[str, object]:
        return {"schema_version": "1.0.0"}


def test_emits_a_success_payload(capsys, tmp_path: Path) -> None:
    exit_code = run(
        ["--bucket", "clutch", "--key", "demo.dem", "--output", str(tmp_path)],
        workflow=SuccessfulWorkflow(),
    )

    payload = json.loads(capsys.readouterr().out)
    assert exit_code == 0
    assert payload["ok"] is True
    assert payload["schema_version"] == "1.0.0"
    assert payload["parser_version"] == "2.0.2"
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
        "schema_version": "1.0.0",
        "parser_version": "2.0.2",
        "error": {
            "code": "unsupported_demo",
            "message": "The file is not a supported Counter-Strike 2 demo.",
            "retryable": False,
            "details": {},
        },
    }


def test_rejects_an_invalid_input_payload(capsys, tmp_path: Path) -> None:
    exit_code = run(
        [
            "--bucket",
            "clutch",
            "--key",
            "demo.dem",
            "--output",
            str(tmp_path),
            "--expected-sha256",
            "invalid",
        ],
        workflow=SuccessfulWorkflow(),
    )

    payload = json.loads(capsys.readouterr().out)
    assert exit_code == 2
    assert payload["error"]["code"] == "invalid_input"


def test_normalizes_an_invalid_output_payload(capsys, tmp_path: Path) -> None:
    exit_code = run(
        ["--bucket", "clutch", "--key", "demo.dem", "--output", str(tmp_path)],
        workflow=InvalidOutputWorkflow(),
    )

    payload = json.loads(capsys.readouterr().out)
    assert exit_code == 1
    assert payload["error"]["code"] == "internal_error"

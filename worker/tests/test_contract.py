"""Shared Laravel-Awpy contract tests."""

import json
from pathlib import Path

import pytest

from clutch_worker.contract import SCHEMA_VERSION, validate_payload

CONTRACT_DIRECTORY = Path(__file__).parents[1] / "contracts"


def test_validates_every_shared_contract_fixture() -> None:
    cases = json.loads((CONTRACT_DIRECTORY / "fixtures/cases.json").read_text(encoding="utf-8"))

    for case in cases:
        assert validate_payload(case["kind"], case["payload"]) is case["valid"], case["name"]


@pytest.mark.parametrize("filename", ["input.schema.json", "output.schema.json", "error.schema.json"])
def test_json_schemas_use_the_supported_version(filename: str) -> None:
    schema = json.loads((CONTRACT_DIRECTORY / filename).read_text(encoding="utf-8"))

    assert schema["$schema"] == "https://json-schema.org/draft/2020-12/schema"
    assert schema["properties"]["schema_version"]["const"] == SCHEMA_VERSION

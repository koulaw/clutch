"""Versioned payload validation shared with the Laravel orchestrator contract."""

from collections.abc import Mapping
from typing import Any, Literal

from clutch_worker.errors import InvalidInputError

SCHEMA_VERSION = "1.0.0"
ERROR_CODES = {
    "storage_error",
    "checksum_mismatch",
    "unsupported_demo",
    "corrupt_demo",
    "invalid_input",
    "internal_error",
}

PayloadKind = Literal["input", "output", "error"]


def validate_payload(kind: PayloadKind, payload: object) -> bool:
    """Return whether a payload follows the supported contract version."""
    if not isinstance(payload, Mapping):
        return False

    validators = {"input": _valid_input, "output": _valid_output, "error": _valid_error}
    return validators[kind](payload)


def require_input(payload: dict[str, Any]) -> None:
    """Raise a normalized worker error when the invocation payload is invalid."""
    if not validate_payload("input", payload):
        raise InvalidInputError


def _valid_input(payload: Mapping[str, Any]) -> bool:
    if set(payload) != {"schema_version", "source", "output"} or payload.get("schema_version") != SCHEMA_VERSION:
        return False

    source = payload.get("source")
    output = payload.get("output")
    if not isinstance(source, Mapping) or not isinstance(output, Mapping):
        return False
    if not {"bucket", "object_key"} <= set(source) <= {"bucket", "object_key", "checksum_sha256"}:
        return False
    if set(output) != {"directory"}:
        return False

    checksum = source.get("checksum_sha256")
    return (
        _non_empty_string(source.get("bucket"))
        and _non_empty_string(source.get("object_key"))
        and (checksum is None or _sha256(checksum))
        and _non_empty_string(output.get("directory"))
    )


def _valid_output(payload: Mapping[str, Any]) -> bool:
    required = {"ok", "schema_version", "parser_version", "output_directory", "manifest"}
    if set(payload) != required or payload.get("ok") is not True or payload.get("schema_version") != SCHEMA_VERSION:
        return False
    if not _non_empty_string(payload.get("parser_version")) or not _non_empty_string(payload.get("output_directory")):
        return False

    manifest = payload.get("manifest")
    manifest_keys = {"schema_version", "parser_version", "match", "rounds", "players", "events", "ticks"}
    if not isinstance(manifest, Mapping) or set(manifest) != manifest_keys:
        return False
    if manifest.get("schema_version") != SCHEMA_VERSION or not _non_empty_string(manifest.get("parser_version")):
        return False
    if not _valid_json_artifact(manifest.get("match")):
        return False
    if not all(_valid_tabular_artifact(manifest.get(name)) for name in ("rounds", "players", "ticks")):
        return False

    events = manifest.get("events")
    return isinstance(events, Mapping) and all(
        _non_empty_string(name) and _valid_tabular_artifact(artifact) for name, artifact in events.items()
    )


def _valid_error(payload: Mapping[str, Any]) -> bool:
    if set(payload) != {"ok", "schema_version", "parser_version", "error"}:
        return False
    if payload.get("ok") is not False or payload.get("schema_version") != SCHEMA_VERSION:
        return False
    if not _non_empty_string(payload.get("parser_version")):
        return False

    error = payload.get("error")
    return (
        isinstance(error, Mapping)
        and set(error) == {"code", "message", "retryable", "details"}
        and error.get("code") in ERROR_CODES
        and _non_empty_string(error.get("message"))
        and isinstance(error.get("retryable"), bool)
        and isinstance(error.get("details"), Mapping)
    )


def _valid_json_artifact(value: object) -> bool:
    return isinstance(value, Mapping) and set(value) == {"path"} and _non_empty_string(value.get("path"))


def _valid_tabular_artifact(value: object) -> bool:
    return (
        isinstance(value, Mapping)
        and set(value) == {"path", "rows"}
        and _non_empty_string(value.get("path"))
        and isinstance(value.get("rows"), int)
        and not isinstance(value.get("rows"), bool)
        and value["rows"] >= 0
    )


def _non_empty_string(value: object) -> bool:
    return isinstance(value, str) and bool(value)


def _sha256(value: object) -> bool:
    return isinstance(value, str) and len(value) == 64 and all(character in "0123456789abcdef" for character in value)

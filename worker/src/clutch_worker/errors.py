"""Normalized worker errors."""

from dataclasses import dataclass, field
from typing import Any


@dataclass(slots=True)
class WorkerError(Exception):
    """An expected worker failure safe to return to the caller."""

    code: str
    message: str
    retryable: bool = False
    details: dict[str, Any] = field(default_factory=dict)

    def __str__(self) -> str:
        return self.message

    def as_dict(self) -> dict[str, Any]:
        """Return the stable error payload consumed by the orchestrator."""
        return {
            "code": self.code,
            "message": self.message,
            "retryable": self.retryable,
            "details": self.details,
        }


class UnsupportedDemoError(WorkerError):
    """The input does not use the CS2 Source 2 demo format."""

    def __init__(self) -> None:
        super().__init__(
            code="unsupported_demo",
            message="The file is not a supported Counter-Strike 2 demo.",
        )


class CorruptDemoError(WorkerError):
    """Awpy could not parse a file with a valid CS2 signature."""

    def __init__(self, exception: Exception) -> None:
        super().__init__(
            code="corrupt_demo",
            message="The Counter-Strike 2 demo is corrupt or incomplete.",
            details={"exception_type": type(exception).__name__},
        )


class ChecksumMismatchError(WorkerError):
    """The downloaded object does not match the expected digest."""

    def __init__(self) -> None:
        super().__init__(
            code="checksum_mismatch",
            message="The downloaded demo does not match the expected SHA-256 checksum.",
        )


class StorageError(WorkerError):
    """The demo could not be retrieved from object storage."""

    def __init__(self, exception: Exception) -> None:
        super().__init__(
            code="storage_error",
            message="The demo could not be downloaded from object storage.",
            retryable=True,
            details={"exception_type": type(exception).__name__},
        )


class InvalidInputError(WorkerError):
    """The orchestrator supplied a payload outside the supported contract."""

    def __init__(self) -> None:
        super().__init__(
            code="invalid_input",
            message="The worker input does not match schema version 1.0.0.",
        )

"""Command-line entry point for the Clutch demo worker."""

import argparse
import json
import sys
from importlib.metadata import version
from pathlib import Path
from typing import Any

from clutch_worker.contract import SCHEMA_VERSION, require_input, validate_payload
from clutch_worker.errors import WorkerError
from clutch_worker.parser import AwpyDemoParser
from clutch_worker.storage import S3DemoStorage
from clutch_worker.workflow import ParseDemoWorkflow
from clutch_worker.writer import ParsedDemoWriter


def build_parser() -> argparse.ArgumentParser:
    """Build the worker command-line parser."""
    parser = argparse.ArgumentParser(description="Parse a CS2 demo from S3-compatible storage.")
    parser.add_argument("--bucket", required=True)
    parser.add_argument("--key", required=True)
    parser.add_argument("--output", required=True, type=Path)
    parser.add_argument("--expected-sha256")
    return parser


def run(arguments: list[str] | None = None, workflow: ParseDemoWorkflow | None = None) -> int:
    """Execute the worker and emit exactly one JSON result to stdout."""
    options = build_parser().parse_args(arguments)
    active_workflow = workflow or ParseDemoWorkflow(S3DemoStorage(), AwpyDemoParser(), ParsedDemoWriter())

    try:
        input_payload = {
            "schema_version": SCHEMA_VERSION,
            "source": {
                "bucket": options.bucket,
                "object_key": options.key,
                "checksum_sha256": options.expected_sha256,
            },
            "output": {"directory": str(options.output)},
        }
        require_input(input_payload)
        manifest = active_workflow.run(
            bucket=options.bucket,
            key=options.key,
            output_directory=options.output,
            expected_sha256=options.expected_sha256,
        )
        payload: dict[str, Any] = {
            "ok": True,
            "schema_version": SCHEMA_VERSION,
            "parser_version": version("awpy"),
            "output_directory": str(options.output),
            "manifest": manifest,
        }
        if not validate_payload("output", payload):
            raise ValueError("The worker output does not match the contract.")
        exit_code = 0
    except WorkerError as error:
        payload = {
            "ok": False,
            "schema_version": SCHEMA_VERSION,
            "parser_version": version("awpy"),
            "error": error.as_dict(),
        }
        exit_code = 2
    except Exception as exception:
        payload = {
            "ok": False,
            "schema_version": SCHEMA_VERSION,
            "parser_version": version("awpy"),
            "error": {
                "code": "internal_error",
                "message": "The demo worker failed unexpectedly.",
                "retryable": True,
                "details": {"exception_type": type(exception).__name__},
            },
        }
        exit_code = 1

    print(json.dumps(payload, sort_keys=True))
    return exit_code


def main() -> None:
    """Console-script entry point."""
    sys.exit(run())


if __name__ == "__main__":
    main()

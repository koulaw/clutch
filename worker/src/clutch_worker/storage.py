"""Object-storage access for demo inputs."""

import os
from pathlib import Path
from typing import Protocol

import boto3

from clutch_worker.errors import StorageError


class DemoStorage(Protocol):
    """Storage boundary used by the parsing workflow."""

    def download(self, bucket: str, key: str, destination: Path) -> None:
        """Download one object to a local destination."""


class S3DemoStorage:
    """Download demos from S3-compatible object storage."""

    def __init__(self, client: object | None = None) -> None:
        self._client = client or boto3.client(
            "s3",
            endpoint_url=os.getenv("S3_ENDPOINT_URL"),
            region_name=os.getenv("AWS_DEFAULT_REGION", "us-east-1"),
        )

    def download(self, bucket: str, key: str, destination: Path) -> None:
        """Download an object while normalizing provider failures."""
        try:
            self._client.download_file(bucket, key, str(destination))
        except Exception as exception:
            raise StorageError(exception) from exception

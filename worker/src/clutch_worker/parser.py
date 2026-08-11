"""Awpy adapter and extracted demo datasets."""

from collections.abc import Callable
from dataclasses import dataclass
from pathlib import Path
from typing import Any

import polars as pl
from awpy import Demo

from clutch_worker.errors import CorruptDemoError, UnsupportedDemoError

CS2_DEMO_HEADER = b"PBDEMS2\x00"


@dataclass(frozen=True, slots=True)
class ParsedDemo:
    """Structured datasets extracted from one CS2 demo."""

    match: dict[str, Any]
    rounds: pl.DataFrame
    players: pl.DataFrame
    events: dict[str, pl.DataFrame]
    ticks: pl.DataFrame


class AwpyDemoParser:
    """Parse a local CS2 demo through Awpy."""

    def __init__(self, demo_factory: Callable[[Path], Any] = Demo) -> None:
        self._demo_factory = demo_factory

    def parse(self, path: Path) -> ParsedDemo:
        """Parse and normalize all datasets needed by Clutch."""
        if self._read_header(path) != CS2_DEMO_HEADER:
            raise UnsupportedDemoError

        try:
            demo = self._demo_factory(path)
            demo.parse()

            ticks = demo.ticks
            return ParsedDemo(
                match=dict(demo.header),
                rounds=demo.rounds,
                players=self._extract_players(ticks),
                events=dict(demo.events),
                ticks=ticks,
            )
        except Exception as exception:
            raise CorruptDemoError(exception) from exception

    @staticmethod
    def _read_header(path: Path) -> bytes:
        try:
            with path.open("rb") as demo_file:
                return demo_file.read(len(CS2_DEMO_HEADER))
        except OSError as exception:
            raise CorruptDemoError(exception) from exception

    @staticmethod
    def _extract_players(ticks: pl.DataFrame) -> pl.DataFrame:
        columns = [column for column in ("steamid", "name") if column in ticks.columns]
        if not columns:
            return pl.DataFrame(schema={"steamid": pl.UInt64, "name": pl.String})

        players = ticks.select(columns).unique(maintain_order=True)
        if "steamid" in players.columns:
            players = players.filter(pl.col("steamid").is_not_null())

        return players

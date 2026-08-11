"""Tests for the Awpy parsing adapter."""

from pathlib import Path

import polars as pl
import pytest

from clutch_worker.errors import CorruptDemoError, UnsupportedDemoError
from clutch_worker.parser import CS2_DEMO_HEADER, AwpyDemoParser


class FakeDemo:
    """Small Awpy-compatible demo double."""

    def __init__(self, path: Path) -> None:
        self.path = path
        self.header = {"map_name": "de_ancient", "demo_file_stamp": "PBDEMS2\x00"}
        self.rounds = pl.DataFrame({"round_num": [1], "start": [100], "end": [200]})
        self.ticks = pl.DataFrame(
            {
                "tick": [101, 102, 103],
                "steamid": [76561190000000001, 76561190000000001, 76561190000000002],
                "name": ["Alpha", "Alpha", "Bravo"],
            }
        )
        self.events = {"player_death": pl.DataFrame({"tick": [150], "attacker_name": ["Alpha"]})}

    def parse(self) -> None:
        """Match Awpy's parsing interface."""


def test_extracts_match_rounds_players_events_and_ticks(tmp_path: Path) -> None:
    demo_path = tmp_path / "reference.dem"
    demo_path.write_bytes(CS2_DEMO_HEADER + b"fixture")

    parsed = AwpyDemoParser(FakeDemo).parse(demo_path)

    assert parsed.match["map_name"] == "de_ancient"
    assert parsed.rounds.height == 1
    assert parsed.players.to_dicts() == [
        {"steamid": 76561190000000001, "name": "Alpha"},
        {"steamid": 76561190000000002, "name": "Bravo"},
    ]
    assert parsed.events["player_death"].height == 1
    assert parsed.ticks.height == 3


def test_rejects_a_non_cs2_demo_before_awpy_runs(tmp_path: Path) -> None:
    demo_path = tmp_path / "source1.dem"
    demo_path.write_bytes(b"HL2DEMO\x00fixture")

    with pytest.raises(UnsupportedDemoError) as error:
        AwpyDemoParser(lambda path: pytest.fail("Awpy must not run")).parse(demo_path)

    assert error.value.code == "unsupported_demo"
    assert error.value.retryable is False


def test_normalizes_awpy_parse_failures(tmp_path: Path) -> None:
    class BrokenDemo(FakeDemo):
        def parse(self) -> None:
            raise ValueError("invalid protobuf")

    demo_path = tmp_path / "corrupt.dem"
    demo_path.write_bytes(CS2_DEMO_HEADER + b"broken")

    with pytest.raises(CorruptDemoError) as error:
        AwpyDemoParser(BrokenDemo).parse(demo_path)

    assert error.value.as_dict() == {
        "code": "corrupt_demo",
        "message": "The Counter-Strike 2 demo is corrupt or incomplete.",
        "retryable": False,
        "details": {"exception_type": "ValueError"},
    }

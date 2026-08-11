"""Tests for parsed artifact writing."""

import json
from pathlib import Path

import polars as pl

from clutch_worker.parser import ParsedDemo
from clutch_worker.writer import ParsedDemoWriter


def test_writes_manifest_and_parquet_datasets(tmp_path: Path) -> None:
    parsed = ParsedDemo(
        match={"map_name": "de_mirage"},
        rounds=pl.DataFrame({"round_num": [1, 2]}),
        players=pl.DataFrame({"steamid": [1, 2], "name": ["Alpha", "Bravo"]}),
        events={
            "player_death": pl.DataFrame({"tick": [100]}),
            "../unsafe": pl.DataFrame({"tick": [200]}),
        },
        ticks=pl.DataFrame({"tick": [100, 101, 102]}),
    )

    manifest = ParsedDemoWriter().write(parsed, tmp_path)

    assert json.loads((tmp_path / "match.json").read_text())["map_name"] == "de_mirage"
    assert pl.read_parquet(tmp_path / "rounds.parquet").height == 2
    assert pl.read_parquet(tmp_path / "players.parquet").height == 2
    assert pl.read_parquet(tmp_path / "events/player_death.parquet").height == 1
    assert pl.read_parquet(tmp_path / "ticks.parquet").height == 3
    assert not (tmp_path / "unsafe.parquet").exists()
    assert manifest["events"] == {
        "player_death": {"path": "events/player_death.parquet", "rows": 1}
    }
    assert json.loads((tmp_path / "manifest.json").read_text())["schema_version"] == "1.0.0"

"""Tests for parsed artifact writing."""

import gzip
import json
from pathlib import Path

import polars as pl

from clutch_worker.parser import ParsedDemo
from clutch_worker.writer import ParsedDemoWriter


def test_writes_manifest_and_parquet_datasets(tmp_path: Path) -> None:
    parsed = ParsedDemo(
        match={"map_name": "de_mirage"},
        rounds=pl.DataFrame({
            "round_num": [1, 2],
            "start_tick": [100, 102],
            "freeze_end_tick": [100, 102],
            "end_tick": [101, 102],
        }),
        players=pl.DataFrame({"steamid": [1, 2], "name": ["Alpha", "Bravo"]}),
        events={
            "player_death": pl.DataFrame({"tick": [100]}),
            "../unsafe": pl.DataFrame({"tick": [200]}),
        },
        ticks=pl.DataFrame({"tick": [100, 101, 102], "round_num": [1, 1, 2]}),
        tick_rate=64,
    )

    manifest = ParsedDemoWriter().write(parsed, tmp_path)

    assert json.loads((tmp_path / "match.json").read_text())["map_name"] == "de_mirage"
    assert pl.read_parquet(tmp_path / "rounds.parquet").height == 2
    assert pl.read_parquet(tmp_path / "players.parquet").height == 2
    assert pl.read_parquet(tmp_path / "events/player_death.parquet").height == 1
    assert pl.read_parquet(tmp_path / "ticks.parquet").height == 3
    assert not (tmp_path / "unsafe.parquet").exists()
    assert manifest["parser_version"] == "2.0.2"
    assert manifest["events"] == {
        "player_death": {"path": "events/player_death.parquet", "rows": 1}
    }
    assert json.loads((tmp_path / "manifest.json").read_text())["schema_version"] == "1.0.0"


def test_writes_gzipped_round_replays_sampled_at_16_fps(tmp_path: Path) -> None:
    parsed = ParsedDemo(
        match={"map_name": "de_mirage"},
        rounds=pl.DataFrame({
            "round_num": [1],
            "start_tick": [100],
            "freeze_end_tick": [104],
            "end_tick": [108],
            "winner_side": ["ct"],
            "reason_name": ["t_killed"],
        }),
        players=pl.DataFrame({"steamid": [76561190000000001], "name": ["Alpha"]}),
        events={},
        ticks=pl.DataFrame({
            "tick": list(range(100, 109)),
            "round_num": [1] * 9,
            "steamid": [76561190000000001] * 9,
            "name": ["Alpha"] * 9,
            "X": list(range(9)),
            "Y": list(range(9)),
            "Z": [0] * 9,
            "health": [100] * 9,
        }),
        tick_rate=64,
    )

    manifest = ParsedDemoWriter().write(parsed, tmp_path)

    with gzip.open(tmp_path / "replays/round-1.json.gz", "rt", encoding="utf-8") as file:
        replay = json.load(file)

    assert [frame["tick"] for frame in replay["frames"]] == [100, 104, 108]
    assert replay["frames_per_second"] == 16
    assert replay["frames"][0]["players"][0]["steamid"] == "76561190000000001"
    assert manifest["replays"] == [{
        "path": "replays/round-1.json.gz",
        "round": 1,
        "start_tick": 100,
        "freeze_end_tick": 104,
        "end_tick": 108,
        "winner_side": "ct",
        "win_reason": "t_killed",
        "frames": 3,
        "frames_per_second": 16,
        "version": "1.0.0",
    }]

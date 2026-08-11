"""Write parsed demo datasets as compact artifacts."""

import gzip
import json
import re
from importlib.metadata import version
from pathlib import Path
from typing import Any

import polars as pl

from clutch_worker.contract import SCHEMA_VERSION
from clutch_worker.parser import ParsedDemo

SAFE_DATASET_NAME = re.compile(r"\A[a-zA-Z0-9_-]+\Z")
REPLAY_VERSION = "1.0.0"
REPLAY_FRAMES_PER_SECOND = 16


class ParsedDemoWriter:
    """Persist parsed datasets and return their manifest."""

    def write(self, parsed: ParsedDemo, output_directory: Path) -> dict[str, Any]:
        """Write match JSON plus Parquet datasets into the output directory."""
        output_directory.mkdir(parents=True, exist_ok=True)
        events_directory = output_directory / "events"
        events_directory.mkdir(exist_ok=True)

        self._write_json(output_directory / "match.json", parsed.match)
        parsed.rounds.write_parquet(output_directory / "rounds.parquet")
        parsed.players.write_parquet(output_directory / "players.parquet")
        parsed.ticks.write_parquet(output_directory / "ticks.parquet")

        replay_directory = output_directory / "replays"
        replay_directory.mkdir(exist_ok=True)
        replays = self._write_replays(parsed, replay_directory)

        event_datasets: dict[str, dict[str, Any]] = {}
        for event_name, events in sorted(parsed.events.items()):
            if not SAFE_DATASET_NAME.fullmatch(event_name):
                continue

            relative_path = f"events/{event_name}.parquet"
            events.write_parquet(output_directory / relative_path)
            event_datasets[event_name] = {"path": relative_path, "rows": events.height}

        manifest = {
            "schema_version": SCHEMA_VERSION,
            "parser_version": version("awpy"),
            "match": {"path": "match.json"},
            "rounds": {"path": "rounds.parquet", "rows": parsed.rounds.height},
            "players": {"path": "players.parquet", "rows": parsed.players.height},
            "events": event_datasets,
            "ticks": {"path": "ticks.parquet", "rows": parsed.ticks.height},
            "replays": replays,
        }
        self._write_json(output_directory / "manifest.json", manifest)

        return manifest

    @staticmethod
    def _write_json(path: Path, payload: dict[str, Any]) -> None:
        path.write_text(json.dumps(payload, default=str, ensure_ascii=False, sort_keys=True), encoding="utf-8")

    def _write_replays(self, parsed: ParsedDemo, directory: Path) -> list[dict[str, Any]]:
        replays: list[dict[str, Any]] = []

        for round_row in parsed.rounds.sort("round_num").iter_rows(named=True):
            round_number = int(round_row["round_num"])
            start_tick = self._round_tick(round_row, "start_tick", "start")
            freeze_end_tick = self._optional_round_tick(round_row, "freeze_end_tick", "freeze_end")
            end_tick = self._round_tick(round_row, "end_tick", "end")
            round_ticks = parsed.ticks.filter(
                (pl.col("round_num") == round_number)
                & pl.col("tick").is_between(start_tick, end_tick)
            )
            sampled_ticks = (
                round_ticks.select("tick")
                .unique()
                .sort("tick")
                .gather_every(max(1, round(parsed.tick_rate / REPLAY_FRAMES_PER_SECOND)))
                .get_column("tick")
                .to_list()
            )
            frames = self._frames(round_ticks.filter(pl.col("tick").is_in(sampled_ticks)))
            relative_path = f"replays/round-{round_number}.json.gz"
            payload = {
                "version": REPLAY_VERSION,
                "round": round_number,
                "tick_rate": parsed.tick_rate,
                "frames_per_second": REPLAY_FRAMES_PER_SECOND,
                "frames": frames,
            }

            with gzip.GzipFile(filename=str(directory / f"round-{round_number}.json.gz"), mode="wb", mtime=0) as file:
                file.write(json.dumps(payload, default=str, ensure_ascii=False, separators=(",", ":")).encode())

            replays.append({
                "path": relative_path,
                "round": round_number,
                "start_tick": start_tick,
                "freeze_end_tick": freeze_end_tick,
                "end_tick": end_tick,
                "winner_side": round_row.get("winner_side", round_row.get("winner")),
                "win_reason": round_row.get("reason_name", round_row.get("reason")),
                "frames": len(frames),
                "frames_per_second": REPLAY_FRAMES_PER_SECOND,
                "version": REPLAY_VERSION,
            })

        return replays

    @staticmethod
    def _frames(ticks: pl.DataFrame) -> list[dict[str, Any]]:
        player_columns = [
            column
            for column in (
                "steamid", "name", "X", "Y", "Z", "yaw", "pitch", "health",
                "armor_value", "has_helmet", "has_defuser", "team_name", "side",
            )
            if column in ticks.columns
        ]
        frames: list[dict[str, Any]] = []

        for tick in ticks.get_column("tick").unique(maintain_order=True).sort().to_list():
            players = ticks.filter(pl.col("tick") == tick).select(player_columns).to_dicts()
            for player in players:
                if "steamid" in player and player["steamid"] is not None:
                    player["steamid"] = str(player["steamid"])

            frames.append({"tick": int(tick), "players": players})

        return frames

    @staticmethod
    def _round_tick(round_row: dict[str, Any], preferred: str, legacy: str) -> int:
        value = round_row.get(preferred, round_row.get(legacy))
        if value is None:
            raise ValueError(f"Round data is missing {preferred}.")

        return int(value)

    @classmethod
    def _optional_round_tick(cls, round_row: dict[str, Any], preferred: str, legacy: str) -> int | None:
        value = round_row.get(preferred, round_row.get(legacy))

        return None if value is None else int(value)

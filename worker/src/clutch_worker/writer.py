"""Write parsed demo datasets as compact artifacts."""

import json
import re
from importlib.metadata import version
from pathlib import Path
from typing import Any

from clutch_worker.contract import SCHEMA_VERSION
from clutch_worker.parser import ParsedDemo

SAFE_DATASET_NAME = re.compile(r"\A[a-zA-Z0-9_-]+\Z")


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
        }
        self._write_json(output_directory / "manifest.json", manifest)

        return manifest

    @staticmethod
    def _write_json(path: Path, payload: dict[str, Any]) -> None:
        path.write_text(json.dumps(payload, default=str, ensure_ascii=False, sort_keys=True), encoding="utf-8")

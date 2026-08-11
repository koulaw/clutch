"""Integration test against Awpy's official matchmaking reference demo."""

import os
import urllib.request
from pathlib import Path

import pytest

from clutch_worker.parser import AwpyDemoParser

REFERENCE_DEMO_URL = "https://figshare.com/ndownloader/files/52456259"


@pytest.mark.reference
def test_parses_the_official_awpy_reference_demo(tmp_path: Path) -> None:
    if os.getenv("RUN_REFERENCE_DEMO") != "1":
        pytest.skip("Set RUN_REFERENCE_DEMO=1 to download and parse the official Awpy reference demo.")

    demo_path = tmp_path / "matchmaking-reference.dem"
    urllib.request.urlretrieve(REFERENCE_DEMO_URL, demo_path)

    parsed = AwpyDemoParser().parse(demo_path)

    assert parsed.match["map_name"] == "de_ancient"
    assert parsed.rounds.height == 8
    assert parsed.players.height > 0
    assert parsed.events["player_death"].height > 0
    assert parsed.ticks.height > 0

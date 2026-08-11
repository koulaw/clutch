---
paths:
  - config/map_radars.php
---

# Config

## Version radars by demo network protocol
Treat config/map_radars.php as the supported map/version registry. Each entry must map exact demo network protocols to a committed radar image, verified dimensions and SHA-256, plus the world-to-radar coordinate transform. Unsupported map/protocol pairs must fail explicitly as unsupported_demo instead of falling back to another radar.

## Version radars by demo patch version
Treat config/map_radars.php as the supported map/version registry. Match Awpy demo.header patch_version values against each radar's exact patch_versions list; do not use a network_protocol field because Awpy does not emit it. Every entry keeps a committed image, verified dimensions and SHA-256, and its coordinate transform. Reject unknown map/patch pairs as unsupported_demo rather than falling back.

## Patch version rule supersedes network protocol
The older network-protocol radar rule is obsolete. Awpy headers expose patch_version (for example 14174), not network_protocol; follow the patch_versions registry rule exclusively.

## Preserve versioned radar layers
Pin radar assets to the source commit recorded in map_radars.source. Multi-level maps store every altitude-bounded image in layers (currently Nuke, Train, and Vertigo); validate dimensions and SHA-256 for every layer and never collapse them to one image. Add patch versions only after verifying the map geometry still matches the pinned radar.

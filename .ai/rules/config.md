---
paths:
  - config/map_radars.php
---

# Config

## Version radars by demo network protocol
Treat config/map_radars.php as the supported map/version registry. Each entry must map exact demo network protocols to a committed radar image, verified dimensions and SHA-256, plus the world-to-radar coordinate transform. Unsupported map/protocol pairs must fail explicitly as unsupported_demo instead of falling back to another radar.

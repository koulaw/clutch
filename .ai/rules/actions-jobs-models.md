---
paths:
  - 'worker/**,app/{Actions,Jobs,Models}/**/*.php'
---

# Actions Jobs Models

## Keep replay and analytics artifacts paired
The worker retains complete analytics as Parquet and emits one gzip JSON replay per round sampled at 16 FPS. Laravel uploads every manifest artifact to private object storage, records size/SHA-256/version, attaches replay artifacts to GameRound records, and marks the Analysis ready only after the full set is stored.

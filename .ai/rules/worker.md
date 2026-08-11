---
paths:
  - 'worker/**'
---

# Worker

## Keep the demo worker isolated and artifact-based
The Python worker downloads demos through S3DemoStorage, verifies optional SHA-256, and parses only local temporary files with Awpy. Preserve normalized WorkerError codes and write match JSON plus Parquet datasets through ParsedDemoWriter; keep dependencies locked in uv.lock and the runtime image digest-pinned/non-root.

## Keep the Laravel-Awpy contract versioned
Treat `worker/contracts/` as the shared source of truth for worker input, success, and error payloads. Every payload includes `schema_version`; every result includes `parser_version`; Laravel and Python validators must both pass the fixtures in `worker/contracts/fixtures/cases.json` before changing the contract.

## Emit compact replay artifacts beside Parquet
Retain complete analytics as Parquet and emit one versioned gzip JSON replay per round, sampled at 16 frames per second. Keep replay entries in the shared worker manifest contract.

---
paths:
  - 'worker/**'
---

# Worker

## Keep the demo worker isolated and artifact-based
The Python worker downloads demos through S3DemoStorage, verifies optional SHA-256, and parses only local temporary files with Awpy. Preserve normalized WorkerError codes and write match JSON plus Parquet datasets through ParsedDemoWriter; keep dependencies locked in uv.lock and the runtime image digest-pinned/non-root.

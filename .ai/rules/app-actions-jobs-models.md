---
paths:
  - 'app/{Actions,Jobs,Models}/**/*.php'
---

# App Actions Jobs Models

## Register artifacts before marking analysis ready
Upload every worker manifest artifact to private object storage and record its size, SHA-256, and version. Attach replay artifacts to GameRound records and mark the Analysis ready only after the complete artifact set is stored.

---
paths:
  - 'app/{Actions,Http,Models}/**/*.php'
---

# Actions Http Models

## Keep user quota counters atomic
All demo imports must call ManageUserQuota::consumeImport before work starts. Store/release retained analyses through storeAnalysis and releaseAnalysis so the dashboard aggregate stays accurate; quota mutations lock the user row and throw QuotaExceededException at configured limits.

## Reuse unfinished demo upload reservations
Demo upload reservation is idempotent per user and SHA-256. Reuse an unfinished reservation and renew its signed URL (also correcting its generated `.dem`/`.dem.zst` suffix); return `demo_already_uploaded` for an already-confirmed duplicate instead of exposing the database uniqueness error.

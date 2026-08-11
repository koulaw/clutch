---
paths:
  - 'app/{Actions,Http,Models}/**/*.php'
---

# Actions Http Models

## Keep user quota counters atomic
All demo imports must call ManageUserQuota::consumeImport before work starts. Store/release retained analyses through storeAnalysis and releaseAnalysis so the dashboard aggregate stays accurate; quota mutations lock the user row and throw QuotaExceededException at configured limits.

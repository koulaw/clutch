---
paths:
  - 'app/{Events,Http,Models}/**/*.php'
---

# Events Http Models

## Keep analysis progress private and pollable
Expose analysis progress through AnalysisProgressResource and broadcast the same payload as analysis.progress.updated on private users.{userId}.analyses channels. Scope polling queries and channel authorization to the owning user, never expose raw worker error messages, and retain HTTP polling as the realtime fallback.

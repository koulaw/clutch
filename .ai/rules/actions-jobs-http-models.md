---
paths:
  - 'app/{Actions,Jobs,Http,Models}/**/*.php'
---

# Actions Jobs Http Models

## Queue demo parsing by demo checksum
After upload confirmation, create at most one active Analysis and dispatch ProcessDemoAnalysis after commit on the dedicated demo-analysis queue. Keep its unique key bound to demo ID plus SHA-256; only terminal failures may create a new numbered manual retry attempt. Persist structured worker failure context on the Analysis.

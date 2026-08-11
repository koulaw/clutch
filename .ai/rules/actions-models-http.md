---
paths:
  - 'app/{Actions,Models,Http}/**/*.php'
---

# Actions Models Http

## Beta invitations are email-bound and single-use
Store only SHA-256 hashes of invitation bearer tokens. Registration must match the normalized invited email and consume the invitation in the same database transaction as user creation using a row lock; expired, revoked, or used invitations remain invalid.

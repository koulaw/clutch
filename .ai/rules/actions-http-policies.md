---
paths:
  - 'app/{Actions,Http,Policies}/**/*.php'
---

# Actions Http Policies

## Keep demo uploads direct and checksum-bound
Reserve demo uploads with a generated storage path and a presigned PUT bound to content length and ChecksumSHA256; never persist the client filename. Confirmation must verify ownership, exact stored size, and the 8-byte PBDEMS2\0 header before atomically consuming the import quota and setting uploaded_at.

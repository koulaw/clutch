---
paths:
  - 'app/{Actions,Http,Policies}/**/*.php'
---

# Actions Http Policies

## Keep demo uploads direct and checksum-bound
Reserve demo uploads with a generated storage path and a presigned PUT bound to content length and ChecksumSHA256; never persist the client filename. Confirmation must verify ownership, exact stored size, and the 8-byte PBDEMS2\0 header before atomically consuming the import quota and setting uploaded_at.

## Keep demo uploads direct and format-aware
Reserve demo uploads with a generated storage path and a presigned PUT bound to content length and ChecksumSHA256; never persist the client filename. Raw `.dem` uploads must have the 8-byte `PBDEMS2\0` header, while `.dem.zst` uploads must have the 4-byte Zstandard frame magic; confirmation verifies ownership and exact stored size before atomically consuming quota.

## Keep demo uploads direct and checksum-bound
Reserve uploads with a generated `.dem` or `.dem.zst` storage path and a presigned PUT bound to content length and ChecksumSHA256; never persist the client filename. Confirmation verifies ownership, exact size, and either the raw `PBDEMS2\0` header or Zstandard frame magic before atomically consuming quota.

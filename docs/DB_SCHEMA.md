# DB Schema (Production Proposal)

## Core Identity & RBAC
- `teacher` (existing): `pID` PK, `roleID`, `positionID`, `oID`, `status`
- `position` (existing)
- `dh_roles` (existing)
- `dh_user_roles` (existing, many-to-many)

Indexes:
- `dh_user_roles`: (`pID`,`roleID`), `roleID`

## Documents & Workflow
- `dh_documents` (new)
  - `id` PK, `documentType`, `documentNumber`, `subject`, `content`, `status`
  - `createdByPID`, `updatedByPID`, `createdAt`, `updatedAt`
  - Index: (`documentType`,`status`), `documentNumber`, `createdAt`

- `dh_document_recipients` (new)
  - `documentID`, `recipientPID`, `inboxType`, `inboxStatus`, `readAt`
  - Unique: (`documentID`,`recipientPID`,`inboxType`)
  - Index: (`recipientPID`,`inboxStatus`), `documentID`

- `dh_document_routes` (new)
  - `documentID`, `fromStatus`, `toStatus`, `actorPID`, `requestID`, `createdAt`
  - Index: `documentID`, `actorPID`

- `dh_read_receipts` (new)
  - `documentID`, `recipientPID`, `readAt`, `requestID`, `ipAddress`, `userAgent`, `receiptHash`
  - Index: (`documentID`), (`recipientPID`,`readAt`)

## File Attachments
- `dh_files` (existing)
- `dh_file_refs` (existing)

Indexes:
- `dh_files`: (`checksumSHA256`), (`uploadedByPID`,`uploadedAt`)
- `dh_file_refs`: (`moduleName`,`entityName`,`entityID`), `fileID`

## Numbering / Concurrency
- `dh_sequences` (new)
  - `seqKey` PK, `currentValue`
  - Used for atomic numbering in transactions

## Audit & Security
- `dh_logs` (existing)
  - Ensure `requestID` populated per request
- `dh_login_attempts` (new)
  - `pID`, `ipAddress`, `attemptCount`, `lockedUntil`
  - Unique: (`pID`,`ipAddress`)

## Operational Modules (existing or planned)
- `dh_room_bookings`, `dh_vehicle_bookings`, `dh_repairs`, `dh_memos`
- `dh_outgoing_letters`, `dh_orders` (existing in migrations)

## Migration Tracking
- `dh_migrations` (new): store applied versions

# Memos (บันทึกข้อความ) Spec

This spec is the single source of truth for the "memos" module behavior (workflow, permissions, UX screens).

## Roles
- Creator (ผู้สร้าง): ครู/บุคลากรทุกคน
- Approver/Signer (ผู้พิจารณา/ผู้ลงนาม): ผอ./รอง/หัวหน้างาน/หัวหน้ากลุ่มสาระฯ (ถูกเลือกเป็นรายบุคคล หรือเลือก "ผอ./รักษาการ")
- Admin (แอดมิน): เข้าถึงได้เพื่อช่วยแก้ปัญหา/ตรวจสอบ

## Inboxes / Pages
- My Memos: สร้าง/แก้ไขร่าง, ส่งเสนอ, ติดตามสถานะ, ยกเลิก, จัดเก็บ
- Approver Inbox: รายการรอพิจารณา/พิจารณาแล้ว/ส่งกลับแก้ไข/ลงนามแล้ว
- View/Review: หน้าเดียวที่ดูรายละเอียด + ทำ action ตาม role และ state

## States
- `DRAFT`: ผู้สร้างแก้ไขได้
- `SUBMITTED`: ส่งเสนอแล้ว (ล็อคเนื้อหาหลัก ยกเว้นยกเลิก)
- `IN_REVIEW`: ผู้พิจารณาเปิดอ่านแล้ว (optional but enabled)
- `RETURNED`: ตีกลับแก้ไข
- `APPROVED_UNSIGNED`: อนุมัติแต่ยังรอแนบไฟล์ฉบับลงนาม (optional but enabled)
- `SIGNED`: ลงนามแล้ว (immutable)
- `REJECTED`: ไม่อนุมัติ (immutable)
- `CANCELLED`: ผู้สร้างยกเลิก (immutable)

## State Transitions (From -> Action -> To)
- `DRAFT` -> `submit` -> `SUBMITTED`
- `DRAFT` -> `cancel` -> `CANCELLED`
- `SUBMITTED` -> `open_by_approver` -> `IN_REVIEW`
- `SUBMITTED|IN_REVIEW` -> `return_with_note` -> `RETURNED`
- `SUBMITTED|IN_REVIEW` -> `reject_with_note` -> `REJECTED`
- `SUBMITTED|IN_REVIEW` -> `approve_unsigned_with_note` -> `APPROVED_UNSIGNED`
- `SUBMITTED|IN_REVIEW` -> `sign_upload` -> `SIGNED`
- `APPROVED_UNSIGNED` -> `upload_signed_file` -> `SIGNED`
- `RETURNED` -> `resubmit` -> `SUBMITTED`
- `SUBMITTED|IN_REVIEW|RETURNED|APPROVED_UNSIGNED|DRAFT` -> `cancel_by_creator` -> `CANCELLED` (blocked once `SIGNED`)

## Permission Summary
- Creator:
  - Create/edit in `DRAFT`
  - Edit + resubmit in `RETURNED`
  - Cancel anytime before `SIGNED`
  - Read-only in `SUBMITTED|IN_REVIEW|SIGNED|REJECTED|CANCELLED|APPROVED_UNSIGNED`
- Approver:
  - View when assigned (current `toPID`)
  - First open records `firstReadAt` and can set state to `IN_REVIEW`
  - Return/Reject/Approve unsigned/Sign
  - Cannot edit creator content fields
- Admin:
  - View all memos and attachments
  - No silent state changes (actions must still record routes/audit)

## Data Model (Minimum)
- `dh_memos` (document)
  - `memoNo`, `memoSeq`, `writeDate`
  - `subject`, `detail`
  - `status`
  - `createdByPID`
  - `toType` (`DIRECTOR|PERSON`) + `toPID` (resolved PID after submit)
  - `firstReadAt`
  - `reviewNote`, `reviewedAt`
  - `signedFileID` (file in `dh_files`)
  - `approvedByPID`, `approvedAt` (signer/decision actor)
  - `isArchived`, `archivedAt` (creator-only archive flag)
- `dh_memo_routes` (timeline)
  - action history with `actorPID`, `fromStatus`, `toStatus`, `note`, `requestID`, `createdAt`

## Mock Data
See `docs/memos/mock-role-data.json`.


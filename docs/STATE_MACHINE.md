# State Machine & Inbox Types

## Inbox Types (Standard)
- `normal_inbox`
- `special_principal_inbox`
- `saraban_return_inbox`
- `acting_principal_inbox`

## หนังสือเวียนภายใน (Internal Circular)
States:
- `INTERNAL_DRAFT`
- `INTERNAL_SENT`
- `INTERNAL_RECALLED`
- `INTERNAL_ARCHIVED`

Transitions:
- DRAFT -> SENT (ผู้ส่ง)
- SENT -> RECALLED (ผู้ส่ง)
- RECALLED -> SENT (ผู้ส่ง resend)
- SENT -> ARCHIVED (สารบรรณ/ผู้ดูแล)

Audit: ทุก transition ต้อง log + actor + timestamp + requestID

## หนังสือเวียนจากภายนอก (External Circular)
States:
- `EXTERNAL_SUBMITTED` (สารบรรณรับเข้า)
- `EXTERNAL_PENDING_REVIEW` (เสนอผอ./รอง/รักษาการ)
- `EXTERNAL_REVIEWED` (ผอ./รองให้ความเห็น)
- `EXTERNAL_FORWARDED` (สารบรรณส่งต่อรองฝ่าย/บุคลากร)

Transitions:
- SUBMITTED -> PENDING_REVIEW (สารบรรณ)
- PENDING_REVIEW -> REVIEWED (ผอ./รอง/รักษาการ)
- REVIEWED -> FORWARDED (สารบรรณ)
- PENDING_REVIEW -> FORWARDED (ผอ.อนุมัติส่งต่อทันที)

Inbox routing:
- ผอ./รอง/รักษาการ: `special_principal_inbox` หรือ `acting_principal_inbox`
- สารบรรณรอรับกลับ: `saraban_return_inbox`

## หนังสือออกภายนอก (Outgoing)
States:
- `OUTGOING_WAITING_ATTACHMENT`
- `OUTGOING_COMPLETE`

Transitions:
- WAITING_ATTACHMENT -> COMPLETE (สารบรรณแนบไฟล์ครบ)

## คำสั่งราชการ (Orders)
States:
- `ORDER_WAITING_ATTACHMENT`
- `ORDER_COMPLETE`
- `ORDER_SENT`

Transitions:
- WAITING_ATTACHMENT -> COMPLETE (ผู้สร้าง/สารบรรณ)
- COMPLETE -> SENT (ส่งให้บุคลากร)

## Read Receipt (หลักฐานการอ่าน)
- บันทึกใน `dh_read_receipts` ทุกครั้งที่ผู้รับเปิดอ่าน
- เก็บ `requestID`, `ip`, `userAgent`, `readAt`
- ผู้ส่งและผู้เกี่ยวข้องเท่านั้นที่เห็นข้อมูลนี้

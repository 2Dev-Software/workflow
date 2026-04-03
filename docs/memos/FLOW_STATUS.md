# Memo Flow Status

เอกสารนี้อธิบาย flow สถานะของตระกูล `memo` ตาม logic ที่ระบบใช้งานจริงในปัจจุบันจาก
- [app/controllers/memo-controller.php](/Users/pawaponthammalangka/Documents/projects/workflow/app/controllers/memo-controller.php)
- [app/controllers/memo-inbox-controller.php](/Users/pawaponthammalangka/Documents/projects/workflow/app/controllers/memo-inbox-controller.php)
- [app/controllers/memo-view-controller.php](/Users/pawaponthammalangka/Documents/projects/workflow/app/controllers/memo-view-controller.php)
- [app/controllers/memo-archive-controller.php](/Users/pawaponthammalangka/Documents/projects/workflow/app/controllers/memo-archive-controller.php)
- [app/modules/memos/service.php](/Users/pawaponthammalangka/Documents/projects/workflow/app/modules/memos/service.php)
- [app/modules/memos/repository.php](/Users/pawaponthammalangka/Documents/projects/workflow/app/modules/memos/repository.php)
- [app/config/state.php](/Users/pawaponthammalangka/Documents/projects/workflow/app/config/state.php)

## ภาพรวมสถานะ

| Status | ชื่อที่ผู้ใช้เห็น | ความหมาย |
| --- | --- | --- |
| `DRAFT` | รอการเสนอแฟ้ม | เจ้าของเรื่องยังแก้ไขได้ ยังไม่เข้ากล่องผู้พิจารณา |
| `SUBMITTED` | รอพิจารณา | ส่งเสนอแล้ว รอผู้พิจารณาคนปัจจุบันเปิดอ่านหรือดำเนินการ |
| `IN_REVIEW` | กำลังพิจารณา | ผู้พิจารณาคนปัจจุบันเปิดอ่านแล้ว |
| `RETURNED` | ตีกลับแก้ไข | ถูกส่งกลับให้เจ้าของเรื่องแก้ไขและเสนอใหม่ |
| `APPROVED_UNSIGNED` | อนุมัติ (รอแนบไฟล์) | ใช้กับ flow แบบ direct เท่านั้น เมื่ออนุมัติแล้วแต่ยังรอแนบไฟล์ฉบับลงนาม |
| `SIGNED` | ลงนามแล้ว | ปิดงานเรียบร้อยแล้ว |
| `REJECTED` | ไม่อนุมัติ | ปิดงานแบบไม่อนุมัติแล้ว |
| `CANCELLED` | ยกเลิก | เจ้าของเรื่องยกเลิกรายการ |

## ผู้มีบทบาท

- เจ้าของเรื่อง: ผู้สร้าง memo
- ผู้พิจารณาปัจจุบัน: คนที่อยู่ใน `toPID`
- หัวหน้ากลุ่ม/หัวหน้างาน: ขั้น `HEAD`
- รองผู้อำนวยการ: ขั้น `DEPUTY`
- ผู้อำนวยการ/รักษาการ: ขั้น `DIRECTOR`
- แอดมิน: ดูข้อมูลได้ แต่ไม่ได้มี flow เงียบพิเศษนอก service

## สองรูปแบบการไหลงาน

### 1. Direct

ใช้เมื่อกำหนดผู้พิจารณาเป็นรายบุคคลโดยตรง

Flow หลัก:
- `DRAFT -> SUBMITTED`
- `SUBMITTED -> IN_REVIEW`
- `SUBMITTED|IN_REVIEW -> RETURNED`
- `SUBMITTED|IN_REVIEW -> REJECTED`
- `SUBMITTED|IN_REVIEW -> APPROVED_UNSIGNED`
- `SUBMITTED|IN_REVIEW|APPROVED_UNSIGNED -> SIGNED`
- `RETURNED -> SUBMITTED`
- `SUBMITTED|IN_REVIEW|APPROVED_UNSIGNED -> DRAFT` ผ่าน action `recall`
- `DRAFT|SUBMITTED|IN_REVIEW|RETURNED|APPROVED_UNSIGNED -> CANCELLED`

จุดสำคัญ:
- `APPROVED_UNSIGNED` มีเฉพาะ direct flow
- direct flow ใช้ `approve_unsigned` และ `sign_upload`
- เมื่อ `sign_upload` สำเร็จ ระบบจะผูก `signedFileID`

### 2. Chain

ใช้เมื่อเสนอแฟ้มตามลำดับ `HEAD -> DEPUTY -> DIRECTOR`

Flow หลัก:
- `DRAFT -> SUBMITTED` ไปยังผู้พิจารณาคนแรก
- `SUBMITTED -> IN_REVIEW` เมื่อผู้พิจารณาปัจจุบันเปิดอ่าน
- `SUBMITTED|IN_REVIEW -> RETURNED` กลับหาเจ้าของเรื่อง
- `SUBMITTED|IN_REVIEW -> FORWARD -> SUBMITTED` ไปยังคนถัดไปใน chain
- `SUBMITTED|IN_REVIEW -> DIRECTOR_APPROVE -> SIGNED`
- `SUBMITTED|IN_REVIEW -> DIRECTOR_REJECT -> REJECTED`
- `RETURNED -> SUBMITTED`
- `SUBMITTED|IN_REVIEW -> DRAFT` ผ่าน action `recall`
- `DRAFT|SUBMITTED|IN_REVIEW|RETURNED -> CANCELLED`

จุดสำคัญ:
- chain flow ไม่ใช้ `APPROVED_UNSIGNED`
- chain flow ไม่ใช้ `sign_upload`
- การอนุมัติขั้นสุดท้ายของ chain จะจบเป็น `SIGNED` ทันทีจาก `director_approve`

## สิทธิ์ตามสถานะ

### เจ้าของเรื่อง

- `DRAFT`
  - แก้ไขได้
  - ส่งเสนอได้
  - ยกเลิกได้
- `RETURNED`
  - แก้ไขได้
  - ส่งเสนอใหม่ได้
  - ยกเลิกได้
- `SUBMITTED|IN_REVIEW|APPROVED_UNSIGNED`
  - ดูได้
  - ดึงกลับ (`recall`) ได้
  - ยกเลิกได้
- `SIGNED|REJECTED`
  - ดูได้
  - จัดเก็บได้
- `CANCELLED`
  - ดูได้
  - จัดเก็บได้

### ผู้พิจารณาปัจจุบัน

- เปิดอ่านครั้งแรกจะทำให้ `SUBMITTED -> IN_REVIEW`
- ถ้าเป็น direct flow:
  - `return`
  - `reject`
  - `approve_unsigned`
  - `sign_upload`
- ถ้าเป็น chain flow:
  - `HEAD/DEPUTY`: `forward` หรือ `return`
  - `DIRECTOR`: `director_approve`, `director_reject`, `return`

## พฤติกรรมของแต่ละหน้า

### [memo.php](/Users/pawaponthammalangka/Documents/projects/workflow/memo.php)

- ใช้สำหรับสร้าง draft และดูรายการของเจ้าของเรื่อง
- รายการของฉันจะแสดงเฉพาะ memo ที่ `createdByPID = current_user`
- รายการ draft/returned เปิดไปแก้ไขหรือเสนอใหม่ได้

### [memo-inbox.php](/Users/pawaponthammalangka/Documents/projects/workflow/memo-inbox.php)

- ใช้สำหรับผู้พิจารณา
- inbox จะไม่แสดง `DRAFT`
- inbox จะไม่แสดงรายการที่ยกเลิกก่อนเข้าสู่ flow พิจารณา
- query อนุญาตเฉพาะสถานะ:
  - `SUBMITTED`
  - `IN_REVIEW`
  - `RETURNED`
  - `APPROVED_UNSIGNED`
  - `SIGNED`
  - `REJECTED`
  - `CANCELLED`

### [memo-view.php](/Users/pawaponthammalangka/Documents/projects/workflow/memo-view.php)

- เป็นหน้ารายละเอียดกลางสำหรับ creator, approver, admin
- ใช้ตัดสินว่าใครทำ action อะไรได้จาก
  - owner rights
  - approver rights
  - `flowMode`
  - `flowStage`
  - สถานะปัจจุบัน

### [memo-archive.php](/Users/pawaponthammalangka/Documents/projects/workflow/memo-archive.php)

- จัดเก็บเป็นพฤติกรรมแยกจากสถานะหลัก
- ผู้สร้างเป็นเจ้าของ archive flag
- จัดเก็บได้เฉพาะเมื่อสถานะเป็น:
  - `SIGNED`
  - `REJECTED`
  - `CANCELLED`

## ข้อกำหนดสำคัญเชิงระบบ

- ทุก transition หลักต้องถูกบันทึกใน `dh_memo_routes`
- การเปิดอ่านของผู้พิจารณาจะเก็บ `firstReadAt`
- การอ่านในระดับเอกสาร sync ไปที่ `dh_documents` ด้วย
- `memoNo` จะถูกสร้างตอน submit ครั้งแรก ไม่ใช่ตอน create draft
- `signedFileID` มีเฉพาะ flow ที่ลงนามด้วยการอัปโหลดไฟล์

## สรุปสำหรับงานตรวจ logic

จาก code ปัจจุบัน logic หลักของ `memo` แยกเป็น 2 flow ชัดเจนคือ `DIRECT` และ `CHAIN`
- service เป็นแหล่ง truth ของ transition จริง
- state machine กลางต้องสะท้อน `APPROVED_UNSIGNED -> DRAFT` เพราะ service รองรับ `recall` จากสถานะนี้
- archive ไม่ใช่ primary status แต่เป็น storage flag แยกต่างหาก

ถ้าต้องอธิบายสั้นที่สุด:
- เจ้าของเรื่องเริ่มที่ `DRAFT`
- ส่งเสนอแล้วเป็น `SUBMITTED`
- ผู้พิจารณาเปิดอ่านแล้วเป็น `IN_REVIEW`
- จากนั้นจบได้ 4 ทางคือ `RETURNED`, `APPROVED_UNSIGNED`, `SIGNED`, `REJECTED`
- เจ้าของเรื่องสามารถ `recall` กลับเป็น `DRAFT` ได้ในช่วงที่ยังไม่ปิดงาน
- เมื่อปิดงานหรือยกเลิกแล้ว จึงเข้าสู่การ `archive`

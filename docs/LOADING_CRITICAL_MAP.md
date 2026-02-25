# Loading Critical Map (Production Enterprise)

## เป้าหมาย
- Preloader ใช้เฉพาะ `initial shell` เท่านั้น
- แต่ละ component ที่มี async fetch ต้องใช้ `component loading state`
- ปิด preloader เมื่อ `critical data ready` หรือ `timeout fallback` เท่านั้น

## Runtime Contract
- Runtime หลักอยู่ที่ `assets/js/main.js`
- Timeout fallback: `PRELOADER_TIMEOUT_MS = 6000`
- API กลาง:
  - `window.App.loading.markCriticalPending(key)`
  - `window.App.loading.markCriticalReady(key)`
  - `window.App.loading.startComponent(target)`
  - `window.App.loading.stopComponent(target)`
  - `window.App.loading.isPageCriticalKey(key)`
  - `window.App.loading.getCurrentPageName()`
  - `window.App.loading.getPageCriticalKeys()`

## Critical Key Registry
- `teacher-directory`: ข้อมูลรายชื่อสมุดโทรศัพท์ครู (ต้องมีข้อมูลก่อนปิด preloader)

## Page Critical Map (Current Baseline)

| Page | Critical Keys | Notes |
|---|---|---|
| `teacher-phone-directory.php` | `teacher-directory` | ปิด preloader หลัง fetch ครั้งแรกเสร็จ (success/error) |
| `dashboard.php` | `-` | ข้อมูลหลัก render จาก server, ใช้ shell-ready |
| `circular-notice.php` | `-` | มี fetch เฉพาะ action ระหว่างใช้งาน (ไม่ block preloader) |
| `room-booking.php` | `-` | มี fetch ตอนตรวจสอบ/ลบรายการ (ไม่ block preloader) |
| `room-booking-approval.php` | `-` | มี fetch ตอน filter/search (ไม่ block preloader) |
| `orders-*.php` | `-` | baseline ปัจจุบันไม่ต้องรอ critical async ก่อนเข้าใช้งาน |
| `memo-*.php` | `-` | baseline ปัจจุบันไม่ต้องรอ critical async ก่อนเข้าใช้งาน |
| `outgoing*.php` | `-` | baseline ปัจจุบันไม่ต้องรอ critical async ก่อนเข้าใช้งาน |
| `profile.php`, `setting.php`, `vehicle-*.php`, `repairs.php` | `-` | baseline ปัจจุบันไม่ต้องรอ critical async ก่อนเข้าใช้งาน |

## Per-Component Loading (Current Implemented)
- `teacher-phone-directory`: ตารางรายชื่อ (fetch รายการ)
- `room-booking-approval`: ตารางรายการอนุมัติ (ajax filter)
- `room-booking`: ฟอร์มตรวจสอบเวลาว่าง + ตารางรายการตอนลบ
- `circular-notice`: ตารางรายการตอน mark-as-read

## กติกาเพิ่ม Critical Key ใหม่
1. เพิ่ม key ใน `PAGE_CRITICAL_MAP` ของหน้าที่เกี่ยวข้อง (`assets/js/main.js`)
2. ครอบ fetch แรกด้วย `markCriticalPending/markCriticalReady` หรือ `withComponent(..., { critical: true, criticalKey })`
3. ใส่ `startComponent/stopComponent` กับ container ที่เหมาะสม
4. อัปเดตไฟล์นี้ให้ตรงกับของจริงทุกครั้ง

## Production Checklist
1. หน้าไม่ critical ต้องไม่โดน preloader block เกินจำเป็น
2. หน้า critical ต้องปิด preloader หลัง key พร้อม หรือ timeout fallback เท่านั้น
3. ทุก async ที่กระทบ UX ต้องมี `component-loading`
4. ตรวจ syntax (`node --check`) และ smoke (`make smoke`) ทุกครั้ง

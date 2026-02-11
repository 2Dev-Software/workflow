# Acceptance UI

## Definition of Done (UI)
- ทุกหน้าผ่าน layout เดียว
- ไม่มี inline style / inline JS
- ทุกข้อความถูก escape ด้วย `h()`
- ใช้ component ทุกจุดที่ซ้ำ
- มี empty state + error state

## Checklist รายหน้า (ตัวอย่าง)
### login
- ฟอร์มแสดงครบ (pid + password)
- กด submit ถ้าไม่กรอก ต้องขึ้นข้อความเตือน

### dashboard
- มีการ์ดสรุป + ปุ่มลัด
- sidebar + topbar แสดงครบ

### circular inbox
- มี search/filter/pagination
- รายการมีสถานะอ่านแล้ว/ยังไม่อ่าน

### circular view
- แสดงเนื้อหา, ผู้ส่ง, เวลา
- มีปุ่มดาวน์โหลดไฟล์แนบ

### circular compose
- มี form + validation
- มี upload placeholder พร้อม progress

## Smoke Test (UI)
1) เปิด `/dashboard.php` ต้องแสดง layout เดียว
2) เปิด `/circular-notice.php` ต้องเห็น list + pagination
3) เปิด `/profile.php` ต้องไม่มี inline JS/STYLE

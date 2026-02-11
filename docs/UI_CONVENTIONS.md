# UI Conventions (Single Source of Truth)

## เป้าหมาย
- ทุกหน้าต้องเหมือนกันทั้งโครงสร้างและพฤติกรรม
- แก้ดีไซน์ครั้งเดียวแล้วสะท้อนทั้งระบบ
- ห้ามทำให้ลิงก์เดิมพัง

## โครงสร้าง View (บังคับ)
- ทุกหน้า render ผ่าน `app/views/layout.php`
- เรียก view ด้วย `view_render('path', $data)`
- ห้าม echo HTML ก้อนใหญ่ใน controller

## Naming (บังคับ)
- Layout prefix: `l-`
- Component prefix: `c-`
- Utility prefix: `u-`

## ห้ามทำ
- ห้าม inline `<style>` หรือ inline JS ใน view
- ห้าม SQL ใน view
- ห้ามใช้ class ชั่วคราวตามหน้า

## ต้องทำ
- ทุก output ใช้ `h()`
- ทุก POST ต้องมี `csrf_field()`
- ใช้ `component_render()` สำหรับ UI ซ้ำๆ

## Layout มาตรฐาน
- sidebar + topbar + content
- อ้างอิง layout เดียวจาก `app/views/layout.php`

## JS Behavior (data-attributes เท่านั้น)
- modal: `data-modal-open="#id"` / `data-modal-close`
- toast: `data-toast="success|error"` + `data-toast-message`
- form validate: `data-validate`

## Legacy Bridge
- ไฟล์ `.php` เดิมต้องเป็น shim เท่านั้น
- ห้ามมี HTML หน้าเต็มในไฟล์เดิม

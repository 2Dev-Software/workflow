# Definition of Done (DoD) + Acceptance Criteria

## DoD (ทุกโมดูล)
- มีรายการ Route/API และ permission matrix
- มี UX behavior สรุป
- มี test checklist (manual) + smoke script
- Error cases ครบ (validation, authz, file upload, DB fail)
- มี audit log + requestID

## Acceptance Criteria (Skeleton v1)
### Routes + Permissions
| Route | Method | Permission | Description |
|---|---|---|---|
| `/login` | GET/POST | Public | Login page + submit |
| `/logout` | GET | Authenticated | Logout |
| `/dashboard` | GET | Authenticated | Dashboard summary |
| `/inbox` | GET | Authenticated | Inbox list + filters |
| `/health` | GET | ADMIN | Health check |

### UX Behavior
- Dashboard: มีการ์ดสรุป + quick actions
- Inbox: filter/search/pagination + unread highlight + preview mock
- Login: inline validation + error alert
- Health: แสดงผล DB, migrations, session path, uploads, PHP extensions

### Error Cases (handle แล้ว)
- CSRF invalid -> 403 + alert
- Invalid credentials -> 401 + audit log
- System closed -> 403 + alert
- Unauthorized access -> 403 page

### Smoke Test (manual)
1) Login ด้วยบัญชีที่ถูกต้อง
2) เปิด `/dashboard` และ `/inbox`
3) ลองเปิด `/health` ด้วย role ที่ไม่ใช่ ADMIN ต้องถูกปฏิเสธ
4) Logout แล้วกลับหน้า `/dashboard` ต้องถูก redirect

### Smoke Script (CLI)
ดู `scripts/smoke-test.php`

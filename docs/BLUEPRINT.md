# Blueprint (A-E)

## A) โครงสร้างโฟลเดอร์ (Target Architecture)
```
/app
  /config            # constants, state machine, roles
  /controllers       # request handlers
  /middleware        # auth, rbac, csrf
  /repositories      # db access only
  /services          # business logic
  /security          # session, csrf, uploads
  /views             # templates + components
  /modules           # legacy modules (keep)
/public
  index.php          # front controller (new)
  /api               # existing API
/assets
  /css               # tokens/base/components (new)
  /js                # app.js + modules
/storage/uploads     # secure upload storage
/migrations          # SQL migrations (source of truth)
/scripts/migrations  # migration mirror per request
```

## B) มาตรฐาน naming
- Files: lowercase kebab-case (เช่น `auth-controller.php`)
- Functions: lowerCamelCase
- Classes: PascalCase (ใช้เท่าที่จำเป็น เช่น Router)
- Constants: UPPER_SNAKE
- DB tables: `dh_` prefix
- DB columns: lowerCamelCase (ตามระบบเดิม)

## C) มาตรฐาน coding style
- PHP: `declare(strict_types=1);` เมื่อเหมาะสม
- DB: mysqli prepared statements เท่านั้น ผ่าน `/app/db/db.php`
- Error handling: ส่งข้อความไทยให้ผู้ใช้, log อังกฤษ
- Output: HTML ต้อง escape ด้วย `h()` ทุกจุด
- JS: โครงสร้าง module ผ่าน `window.App`, no framework
- CSS: design tokens + component classes, responsive first

## D) Core modules ที่สร้างก่อน
1) DB layer: `/app/db/db.php`
2) Auth + Session: `/app/services/auth-service.php`, `/app/security/session.php`
3) RBAC: `/app/rbac/roles.php` + role assignments (`dh_user_roles`)
4) CSRF: `/app/security/csrf.php`
5) Router: `/app/router.php`
6) View renderer: `/app/views/view.php`
7) File service: `/app/services/attachment-service.php`
8) Logger/Audit: `/app/modules/audit/logger.php`

## E) กติกา flow ของเอกสาร (state machine)
ดูรายละเอียดใน `docs/STATE_MACHINE.md`

## หมายเหตุสำคัญ (ข้อจำกัดจาก System Conventions)
- DB ต้องใช้ mysqli (ไม่ใช้ PDO ในตอนนี้)
- Password hashing ยัง “ห้ามเปลี่ยน” จนกว่าจะอนุมัติ (ยังใช้ plaintext แบบเดิม)

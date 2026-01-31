# System Conventions (Single Source of Truth)

## Purpose
This file defines the non-negotiable conventions for the workflow system. All new code and refactors must follow these rules while preserving backward compatibility.

## Backward Compatibility
- Root entry points (e.g. `index.php`, `dashboard.php`, `profile.php`, etc.) must remain working.
- If logic is moved, the old file becomes a thin shim that delegates to the new module.
- Database changes are additive only (no deletes or breaking column changes).

## Naming Conventions
- Files: lowercase kebab-case (e.g. `room-booking-save.php`).
- Functions: lowerCamelCase (e.g. `roomBookingSave`).
- Classes: PascalCase (minimal usage).
- Constants: UPPER_SNAKE_CASE.
- DB tables: use existing names and prefixes. New tables must use `dh_` prefix.
- DB columns: follow existing schema style (lowerCamelCase like `createdAt`, `updatedAt`).

## Folder Boundaries
- `/app` contains all new application logic and is the long-term target.
- `/src/Services` remains for legacy code; new code should avoid adding to it.
- Root `*.php` scripts are entry points only; they should call into `/app` modules.
- `/public/api` endpoints remain stable; internals may delegate to `/app`.
- Only `/app` may include `/app` and `/config/connection.php`.
- Do not include files from `/public` inside `/app` (views only).

## RBAC & Positions
- Position1 (organizational): Director, Deputy Director, Head of department/group, Head of unit, Teacher, Staff.
- Position2 (system roles): Admin, Registry Officer, Vehicle Officer, Leave Officer, Facility Officer.
- Primary sources:
  - `dh_positions` for Position1.
  - `dh_roles` for Position2.
- Many-to-many mapping tables will be added (`dh_user_positions`, `dh_user_roles`).
- Compatibility: `teacher.positionID` and `teacher.roleID` remain as defaults.
- Role keys used in code: `ADMIN`, `REGISTRY`, `VEHICLE`, `LEAVE`, `FACILITY`.

## Security Policy
- Use mysqli prepared statements only; no string interpolation.
- CSRF required on all POST/PUT/PATCH/DELETE actions (HTML and API).
- Session hardening: regenerate ID after login; set secure/httponly cookie flags.
- Output escaping in HTML: `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
- Input validation: filter + server-side checks only.
- Passwords: keep current plaintext behavior; do not introduce hashing yet.
- Password column compatibility: read/write both `password` and `passWord` safely.

## Error Handling Policy
- HTML pages: use `public/components/x-alert.php` for user alerts.
- API responses: JSON with consistent keys (`success`, `error`, `message`, `data`).
- User-facing messages in Thai; server logs in English.
- Never leak SQL errors to end users (log only).

## Upload Policy
- Allowlist: `application/pdf`, `image/jpeg`, `image/png`.
- Max files per action: 5.
- Use random filenames; store SHA-256 checksum.
- Store metadata in `dh_files`, references in `dh_file_refs`.
- Prefer `/storage/uploads` for new files. Legacy `/assets/uploads` remains supported.
- Add an antivirus hook placeholder (no external integration required).

## Environment Config (Optional)
- `ROLE_ADMIN_ID`, `ROLE_REGISTRY_ID`, `ROLE_VEHICLE_ID`, `ROLE_LEAVE_ID`, `ROLE_FACILITY_ID`
  map system role IDs when not resolvable by role names.
- `OUTGOING_PREFIX` and `OUTGOING_CODE` define outgoing number prefix (default `ศธ.` + `01234`).

## Logging & Audit
- Use `dh_logs` for audit and security-relevant actions.
- Log fields: actor_id, action_type, target_type, target_id, ip, user_agent, timestamp.
- Log login/logout, create/edit/send/forward/archive, acting director assignment,
  outgoing/order lifecycle, and director review steps.

## Do / Don't Examples
- Do: call `db_fetch_one()` with prepared statements.
- Do: return JSON consistently from API endpoints.
- Do: create migrations under `/migrations` for new tables/columns.
- Don't: change existing entry URLs or remove root scripts.
- Don't: delete columns or data in migrations.
- Don't: introduce new password hashing until explicitly approved.

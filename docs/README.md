# Workflow Documentation

Last updated: 25 March 2026

## Purpose
This directory is the current documentation set for the Workflow system used by Deebuk Phangnga Wittayayon School. It is intended to describe the system as it exists now, not as a future-only target.

## System Summary
Workflow is a pure PHP office workflow platform for internal documents and operational requests. The system currently covers:

- internal and external circulars
- internal memos with approval and signature flow
- government orders
- outgoing registration
- room booking and approval
- vehicle reservation and approval
- repair requests, approval, and management
- teacher phone directory
- profile, settings, audit, and role-aware dashboard

The application keeps legacy public entry points stable at the project root, while most domain logic has been moved into `app/controllers`, `app/modules`, and selected legacy services under `src/Services`.

## Technology Stack
- PHP 8.2+
- mysqli with prepared statements
- MariaDB / MySQL
- mPDF for PDF output
- Vanilla JavaScript modules under `assets/js/modules`
- CSS from the shared application stylesheet under `assets/css/main.css`
- Docker local stack for PHP + Apache + MariaDB

## Runtime Architecture
### Request flow
1. A root entry script such as `memo.php`, `circular-compose.php`, or `room-booking.php` is called.
2. The entry script delegates into a controller in `app/controllers`.
3. The controller loads data from repositories and services.
4. The controller renders a view under `app/views`.
5. Shared UI fragments are rendered from `public/components` or `app/views/components`.

### Active code layers
- `app/controllers`
  - request handling, guards, CSRF validation, page assembly, ajax partial responses
- `app/modules`
  - current domain repositories and services for circulars, memos, orders, outgoing, repairs, dashboard, audit, users, system
- `src/Services`
  - still active for room, vehicle, auth, system, and selected legacy features
- `public/api`
  - stable API and file download endpoints
- `app/views`
  - page templates
- `assets/js/modules`
  - page-specific client behavior

## Route and Module Map
| Domain | Main routes | Controller layer | Domain logic | Primary tables |
|---|---|---|---|---|
| Dashboard / Home | `index.php`, `dashboard.php` | `app/controllers/index-controller.php`, `app/controllers/dashboard-controller.php` | `app/modules/dashboard/metrics.php`, `app/modules/system/system.php` | `dh_year`, `dh_status`, workflow tables |
| Circulars | `circular-compose.php`, `circular-notice.php`, `circular-view.php`, `circular-archive.php` | `app/controllers/circular-*.php` | `app/modules/circulars/repository.php`, `app/modules/circulars/service.php` | `dh_circulars`, `dh_circular_recipients`, `dh_circular_inboxes`, `dh_circular_routes`, `dh_circular_announcements` |
| Memos | `memo.php`, `memo-inbox.php`, `memo-view.php`, `memo-archive.php` | `app/controllers/memo-*.php` | `app/modules/memos/repository.php`, `app/modules/memos/service.php` | `dh_memos`, `dh_memo_routes`, `dh_files`, `dh_file_refs` |
| Orders | `orders-create.php`, `orders-inbox.php`, `orders-view.php`, `orders-archive.php`, `orders-send.php` | `app/controllers/orders-*.php` | `app/modules/orders/repository.php`, `app/modules/orders/service.php` | `dh_orders`, `dh_order_recipients`, `dh_order_inboxes`, `dh_order_routes` |
| Outgoing | `outgoing.php`, `outgoing-create.php`, `outgoing-view.php`, `outgoing-notice.php`, `outgoing-receive.php` | `app/controllers/outgoing-*.php` | `app/modules/outgoing/repository.php`, `app/modules/outgoing/service.php`, `app/modules/outgoing/receive-service.php` | `dh_outgoing_letters`, `dh_files`, `dh_file_refs`, `dh_circulars` |
| Room Booking | `room-booking.php`, `room-booking-approval.php`, `room-management.php` | `app/controllers/room-*.php` | `src/Services/room/*` | `dh_rooms`, `dh_room_bookings` |
| Vehicle Reservation | `vehicle-reservation.php`, `vehicle-reservation-approval.php`, `vehicle-management.php` | `app/controllers/vehicle-*.php` | `src/Services/vehicle/*`, `app/modules/vehicle/*` | `dh_vehicle_bookings`, `dh_vehicles`, `dh_files`, `dh_file_refs` |
| Repairs | `repairs.php`, `repairs-approval.php`, `repairs-management.php` | `app/controllers/repairs-controller.php` | `app/modules/repairs/repository.php`, `app/modules/repairs/service.php` | `dh_repair_requests`, `dh_files`, `dh_file_refs` |
| Directory / Profile / Settings | `teacher-phone-directory.php`, `profile.php`, `setting.php` | dedicated controllers | phonebook, auth, system services | `teacher`, `position`, `dh_roles`, `dh_status`, `dh_year` |

## Role Model
The codebase currently uses two role dimensions.

### Organizational positions
Backed primarily by `teacher.oID` and `position`.
Examples: director, deputy director, head of department/group, teacher, staff.

### System roles
Backed by `dh_roles` and `teacher.roleID` compatibility mapping.

Current default mapping from `app/config/roles.php`:
- `ADMIN` = role ID 1
- `REGISTRY` = role ID 2
- `VEHICLE` = role ID 3
- `LEAVE` = role ID 4
- `FACILITY` = role ID 5
- `GENERAL` = role ID 6

## Runtime Data and Git Policy
The repository intentionally keeps runtime data outside tracked source wherever possible.

Should remain untracked in Git:
- `storage/uploads/**`
- `assets/img/profile/**`
- generated temp output under `tmp/` except `.gitkeep`

Project fixtures used for manual seeding or development should live under `scripts/fixtures/`, not under `tmp/`.

## Local Development and Docker
For the Docker handoff flow, see:
- [Docker Local Workflow](docker-local.md)

For quick local development outside Docker, the historical bootstrap remains available through `make dev` when the expected database dump exists.

## Documentation Map
### Current-state documentation
- [System Conventions](SYSTEM_CONVENTIONS.md)
- [Blueprint](BLUEPRINT.md)
- [DB Schema](DB_SCHEMA.md)
- [API Contract](API_CONTRACT.md)
- [State Machine](STATE_MACHINE.md)
- [Refactor Plan / Hardening Map](REFACTOR_PLAN.md)

### UI and acceptance standards
- [UI Conventions](UI_CONVENTIONS.md)
- [UI Components](UI_COMPONENTS.md)
- [Style Guide](STYLE_GUIDE.md)
- [Acceptance Criteria](ACCEPTANCE_CRITERIA.md)
- [Acceptance UI](ACCEPTANCE_UI.md)
- [Loading Critical Map](LOADING_CRITICAL_MAP.md)

### Module-specific notes
- [Memo Spec](memos/SPEC.md)

## Current Hardening Priorities
As of this documentation snapshot, the highest-value hardening work that does not require UX redesign is:

1. normalize DB collation handling at the connection/schema boundary
2. expand regression coverage beyond smoke testing
3. continue migrating room and vehicle logic from `src/Services` into `app/modules`
4. keep root entry points stable while reducing inline page logic

## Documentation Maintenance Rule
When a route, workflow state, role rule, or storage rule changes, update the relevant file in `docs/` in the same change set.

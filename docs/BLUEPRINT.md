# Blueprint

Last updated: 25 March 2026

## 1. Current Runtime Architecture
This project is not a front-controller-only application. It is a route-stable PHP system where root scripts remain public entry points and delegate into application code.

### Request path
`root route` -> `controller` -> `repository/service` -> `view` -> `shared components/assets`

## 2. Current Directory Blueprint
```text
/app
  /auth              auth helpers and csrf bridge
  /config            role and workflow state constants
  /controllers       page controllers and ajax partial handlers
  /db                mysqli helper layer
  /middleware        shared request guards
  /modules           current domain repositories/services
  /rbac              current user and role helpers
  /security          security and session helpers
  /services          shared document/upload/auth services
  /views             page templates and app-side components
/assets
  /css               shared styles
  /js                global runtime + page modules
/public
  /api               stable API and download endpoints
  /components        shared UI partials used across pages
/src/Services        legacy but active service layer for room/vehicle/auth/system
/scripts             CLI scripts, docker helpers, fixtures, smoke checks
/storage/uploads     runtime uploads (not tracked in git)
/tmp                 generated temp output only
/docs                project-authored markdown documentation
```

## 3. Layer Responsibilities
### Root routes
Examples:
- `memo.php`
- `circular-compose.php`
- `outgoing.php`
- `room-booking.php`
- `vehicle-reservation.php`

Responsibility:
- include bootstrap/config
- call the matching controller function
- stay thin

### Controllers
Controllers own:
- session/auth guard
- role checks
- query/form normalization
- CSRF enforcement
- selection of full page or ajax partial output

### Modules
`app/modules` is the preferred place for:
- repositories
- domain services
- workflow transitions
- document numbering
- audit-aware mutations

### Legacy services
`src/Services` remains active for:
- room booking and room approval
- vehicle reservation and approval
- selected auth/system utilities

This is a real part of the runtime today. Documentation and refactors must treat it as live code, not dead code.

### Views and components
- `app/views` contains page templates
- `app/views/components` contains page-scoped reusable fragments
- `public/components` contains shared global partials and assets bootstrap

## 4. Module Ownership Map
| Module | Current home | Notes |
|---|---|---|
| Circulars | `app/controllers/circular-*.php`, `app/modules/circulars/*` | Internal and external flows are already in the app module layer |
| Memos | `app/controllers/memo-*.php`, `app/modules/memos/*` | Full workflow state machine is in app module layer |
| Orders | `app/controllers/orders-*.php`, `app/modules/orders/*` | Owner/inbox/archive split is active |
| Outgoing | `app/controllers/outgoing-*.php`, `app/modules/outgoing/*` | Includes receive flow for external circular intake |
| Repairs | `app/controllers/repairs-controller.php`, `app/modules/repairs/*` | Report, approval, and management flows share one controller |
| Room | `app/controllers/room-*.php`, `src/Services/room/*` | Still legacy-heavy but production-active |
| Vehicle | `app/controllers/vehicle-*.php`, `src/Services/vehicle/*`, `app/modules/vehicle/*` | Hybrid structure; approval and PDF flows remain important |
| Dashboard / System | `app/controllers/*`, `app/modules/dashboard/*`, `app/modules/system/*`, `src/Services/system/*` | Shared config and duty logic |

## 5. Architectural Guardrails
- Do not break root URLs.
- Do not duplicate the same workflow in both `app/modules` and `src/Services`.
- Prefer extracting legacy logic behind stable controller contracts.
- Keep business rules out of views.
- Keep runtime file storage and source-controlled fixtures separate.

## 6. Current Migration Direction
The safe modernization path is:
1. keep route contracts stable
2. move business logic into module/service layers
3. leave views visually unchanged unless explicitly requested
4. add regression coverage around critical flows before deeper rewrites

# System Conventions

Last updated: 25 March 2026

## Purpose
This file is the operational source of truth for code, folder, workflow, and runtime conventions. New code and refactors must follow these rules while keeping the current application stable.

## Backward Compatibility Rules
- Root entry points such as `index.php`, `dashboard.php`, `memo.php`, `circular-compose.php`, `outgoing.php`, `room-booking.php`, and `vehicle-reservation.php` must keep working.
- If logic is moved, the root file remains a thin shim that delegates to controllers or services.
- Database changes must be additive unless a specific cleanup migration is explicitly approved.
- Existing response formats for stable APIs and ajax partials must not change without updating all consumers.

## Naming Rules
- Files: lowercase kebab-case where new files are introduced
- Functions: lowerCamelCase
- Constants: UPPER_SNAKE_CASE
- Tables: preserve current names; new workflow tables use `dh_` prefix
- Columns: preserve current lowerCamelCase style used in legacy schema

## Folder Boundaries
### Root
- Root `*.php` files are public entry points only.
- They should not become the place for new business logic.

### `app/controllers`
- request parsing
- auth and role guard
- CSRF validation
- page assembly
- ajax partial response switching

### `app/modules`
- domain repositories
- domain services
- workflow transitions
- current preferred location for new business logic

### `src/Services`
- legacy but still active for room, vehicle, auth, and parts of system behavior
- do not remove active code from here without replacing it safely
- new room and vehicle work should prefer gradual extraction instead of parallel duplicate logic

### `app/views` and `public/components`
- HTML templates only
- no SQL in views
- avoid business rule branching that belongs in controllers/services

### `storage/uploads`
- runtime storage only
- keep `.gitkeep` and `.htaccess`
- user uploads must not be reintroduced into Git tracking

### `tmp`
- temporary generated output only
- long-lived mock or manual seed fixtures must live under `scripts/fixtures`

## Security Policy
- Use mysqli prepared statements as the default DB access pattern.
- Escape all HTML output with `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` or helper `h()`.
- All state-changing forms and endpoints must validate CSRF.
- Session-required routes must guard access before loading sensitive records.
- Never expose raw SQL errors to end users.
- User-facing error text stays in Thai; server-side logs and audit payloads may stay in English.

## Upload Policy
- Store file metadata in `dh_files`
- Store ownership/reference rows in `dh_file_refs`
- Use randomized stored filenames
- Preserve original filename in metadata
- Keep upload validation on the server side even if the UI already limits file types
- Module-specific policies may be stricter than the global allowlist

## Workflow Policy
### State constants
- Workflow state constants live in `app/config/state.php`
- Controllers and views must use those constants instead of hardcoding strings where practical

### Audit
Use `app/modules/audit/logger.php` for create, update, submit, approve, reject, archive, download, read, and security-relevant events.

### Read receipts and inboxes
- Inbox rows represent recipient-facing delivery state
- Route rows represent workflow or forwarding history
- Archive flags must not silently delete primary records

## Role Policy
Current default system role IDs from `app/config/roles.php`:
- 1: `ADMIN`
- 2: `REGISTRY`
- 3: `VEHICLE`
- 4: `LEAVE`
- 5: `FACILITY`
- 6: `GENERAL`

Organizational authority still also depends on position data from `teacher`, `position`, and current acting-duty records.

## Runtime Data Policy
Do not commit runtime artifacts back into source history.

Examples:
- `storage/uploads/**`
- `assets/img/profile/**`
- generated files under `tmp/pdfs`

Development fixtures that need to stay in the repo must be stored under `scripts/fixtures` with a narrow, explicit purpose.

## Documentation Policy
- Project-authored markdown documentation belongs under `docs/`
- Runtime notes or one-off exports do not belong in the root
- When architecture changes, update `docs/README.md`, `docs/BLUEPRINT.md`, and the module-specific source of truth in the same change

## Testing Baseline
Minimum baseline before shipping risky backend changes:
- PHP syntax checks on edited files
- smoke test via `scripts/smoke-test.php`
- manual route verification for affected module

The repository still lacks a full regression test suite. Treat that as an active hardening gap, not as proof that the module is complete.

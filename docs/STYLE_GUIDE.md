# PHP Style Guide

## Goals
- Maintainability and safety with mysqli prepared statements.
- Backward compatibility with existing entry points.
- Consistent module structure for new features.

## File Layout
- Each PHP file should have a single responsibility.
- Prefer pure functions in `/app/services` and DB access in `/app/repositories`.
- Keep HTML templates in `/app/views` and reuse existing `/public/components`.

## PHP Conventions
- Use `declare(strict_types=1);` in new service/repository files when possible.
- Always validate input from `$_GET`, `$_POST`, and `php://input`.
- Escape output for HTML using `htmlspecialchars`.
- Prefer early returns for error handling.

## DB Access (mysqli)
- Use helper functions from `/app/db/db.php`:
  - `db_query()` for prepared statements.
  - `db_fetch_one()` / `db_fetch_all()` for results.
  - `db_execute()` for write operations.
- Avoid `mysqli_query()` unless a query is guaranteed to be static.
- Use transactions for multi-step state changes.

## CSRF
- All state-changing actions must call `csrf_validate()`.
- Use `csrf_field()` for HTML forms.

## Error Messages
- UI messages should be in Thai, concise, and consistent.
- Log details server-side in English (no SQL output to users).

## Module Pattern
- Entry script (root or `/public/api`) delegates to:
  - `/app/modules/<module>/controller.php`
  - Services and repositories as needed.
- Controller should handle:
  - Auth/role guard
  - CSRF
  - Input validation
  - Response rendering

## Adding a New Module
1) Create repository functions for DB access.
2) Create service functions for business rules.
3) Create controller for input/output.
4) Add view templates if needed.
5) Add routes (shim in root or `/public/api`).

## Backward Compatibility Rules
- Do not remove or rename existing scripts.
- Keep response formats stable for existing APIs.
- New modules can be added without touching legacy logic, then wired via shims.

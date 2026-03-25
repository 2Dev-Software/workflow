# API and Ajax Contract

Last updated: 25 March 2026

This file documents the stable request contracts used by the current application. It covers both `public/api` endpoints and controller-driven ajax endpoints that the UI depends on.

## 1. Common Rules
- Session authentication is required unless a route is explicitly public.
- State-changing requests require CSRF.
- HTML fragment responses must preserve current markup contracts expected by client-side modules.
- File downloads must authorize access against the module/entity being requested.

## 2. Stable `public/api` Endpoints
### `GET /public/api/teacher-directory-api.php`
Purpose:
- fetch teacher directory results with pagination and search

Expected query params:
- `page`
- `per_page`
- `q`

Response:
- JSON list plus pagination metadata

### `POST /public/api/room-booking-check.php`
Purpose:
- validate room availability against room status and approved booking conflicts

Expected payload:
- `csrf_token`
- `roomID`
- `startDate`
- `endDate`
- `startTime`
- `endTime`
- `attendeeCount`

Response:
- JSON with success flag and rendered alert HTML

### `POST /public/api/room-booking-delete.php`
Purpose:
- soft-delete a room booking owned by the current user

Expected payload:
- `booking_id`
- `csrf_token`

Response:
- JSON

### `GET /public/api/file-download.php`
Purpose:
- secure download gateway for files stored in `dh_files`/`dh_file_refs`

Supported module values currently used in the app:
- `circulars`
- `orders`
- `outgoing`
- `memos`
- `repairs`

Required query params:
- `module`
- `entity_id`
- `file_id`
- optional `download=1`

### `GET /public/api/vehicle-booking-file.php`
Purpose:
- download vehicle booking attachments tied to a booking record

Required query params:
- `booking_id`
- `file_id`
- optional `download`

### `GET /public/api/vehicle-booking-pdf.php`
Purpose:
- generate the approved vehicle booking PDF form

Required query params:
- `booking_id`
- optional cache-busting query values

Behavior:
- denies access if the booking is not in an approved/completed state

## 3. Controller-Driven Ajax Endpoints
These are not under `public/api`, but they are still API-like contracts because the frontend depends on partial responses.

### `GET /circular-compose.php?tab=track&ajax_filter=1`
Returns:
- HTML fragment for the tracking table in the internal circular compose page

Client:
- `assets/js/modules/circular-compose.js`

### `GET /circular-notice.php?ajax_filter=1`
Returns:
- HTML fragment for circular inbox table rows/sections

Client:
- `assets/js/modules/circular-notice.js`

### `GET /memo-inbox.php?ajax_filter=1`
Returns:
- HTML fragment for memo inbox table content

Client:
- shared memo/circular filter behavior

### `GET /memo-archive.php?ajax_filter=1`
Returns:
- HTML fragment for memo archive table content

### `GET /room-booking-approval.php?ajax_filter=1`
Returns:
- HTML partial rows for the room approval table

### `GET /vehicle-reservation-approval.php?ajax_filter=1`
Returns:
- JSON with `rows_html`, `pagination_html`, and paging metadata

## 4. File Download Authorization Contract
The following must stay true when touching download logic.

- access is evaluated by module and entity ownership or role
- a matching `dh_file_refs` row is required
- missing file rows must resolve as 404 or safe failure, never raw warnings
- the UI may request view mode or forced download mode without changing the route itself

## 5. Backward Compatibility Requirements
- do not rename these endpoints without providing a compatibility layer
- do not change response shape for active JS modules without updating both sides together
- when adding ajax filtering, keep the non-ajax full page render working as the fallback path

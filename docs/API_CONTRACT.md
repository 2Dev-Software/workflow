# API Contract (public/api)

This file documents existing public API endpoints. These endpoints must remain stable.

## Common Notes
- All endpoints require an active session (`$_SESSION['pID']`).
- All responses use UTF-8.
- CSRF is required for state-changing requests.

---

## GET /public/api/teacher-directory-api.php
Fetch teacher directory list with pagination/search.

### Query Params
- `page` (int, default 1)
- `per_page` (10|20|50|all, default 10)
- `q` (string, optional search)

### Response 200 (JSON)
```json
{
  "data": [
    {"fName":"...","department_name":"...","telephone":"..."}
  ],
  "meta": {
    "page": 1,
    "per_page": 10,
    "total": 100,
    "total_pages": 10,
    "query": "..."
  }
}
```

### Errors
- 401 unauthorized
- 405 method_not_allowed

---

## POST /public/api/room-booking-check.php
Validate room availability and booking time window.

### Body (form-encoded)
- `csrf_token` (string)
- `dh_year` (int, optional)
- `roomID` (int)
- `startDate` (Y-m-d)
- `endDate` (Y-m-d, optional)
- `startTime` (H:i)
- `endTime` (H:i)
- `attendeeCount` (int, optional)

### Response (JSON)
```json
{
  "ok": true,
  "html": "<div>...</div>"
}
```
`html` is rendered from `public/components/x-alert.php`.

### Errors
- 405 method_not_allowed
- 200 with ok=false for validation errors

---

## POST /public/api/room-booking-delete.php
Soft-delete a room booking owned by the current user.

### Body (JSON or form-encoded)
- `booking_id` (int)
- `csrf_token` (string)

### Response 200 (JSON)
```json
{"success": true, "reload": true}
```

### Errors (JSON)
```json
{"message":"...","html":"<div>...</div>","error":"..."}
```
Status codes: 400, 401, 403, 404, 405, 500.

---

## GET /public/api/vehicle-booking-file.php
Stream an attachment file for a vehicle booking.

### Query Params
- `booking_id` (int)
- `file_id` (int)
- `download` (0|1, optional)

### Response
- 200 file stream with `Content-Type` and `Content-Disposition` headers.

### Errors
- 400, 401, 403, 404, 405

---

## GET /public/api/file-download.php
Secure download for files stored in `dh_files`/`dh_file_refs`.

### Query Params
- `module` (circulars|orders|outgoing|memos|repairs)
- `entity_id` (string/int)
- `file_id` (int)
- `download` (0|1, optional)

### Response
- 200 file stream when authorized.

### Errors
- 400 invalid params
- 401 unauthorized (not logged in)
- 403 forbidden (no access to entity)
- 404 not found

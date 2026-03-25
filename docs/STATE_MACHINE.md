# State Machine and Workflow Routes

Last updated: 25 March 2026

## 1. Inbox Types
Used primarily by the circular system.

- `normal_inbox`
- `special_principal_inbox`
- `saraban_return_inbox`
- `acting_principal_inbox`

## 2. Internal Circulars
### States
- `INTERNAL_DRAFT`
- `INTERNAL_SENT`
- `INTERNAL_RECALLED`
- `INTERNAL_ARCHIVED`

### Main transitions
- draft -> sent
- sent -> recalled
- recalled -> sent
- sent -> archived

### Operational notes
- creator owns draft/edit/resend/recall behaviors
- forward behavior keeps the same circular and expands recipients/inboxes
- read tracking is handled per recipient inbox row

## 3. External Circulars
### States
- `EXTERNAL_SUBMITTED`
- `EXTERNAL_PENDING_REVIEW`
- `EXTERNAL_REVIEWED`
- `EXTERNAL_FORWARDED`

### Main transitions
- submitted -> pending review
- pending review -> reviewed
- reviewed -> forwarded
- pending review -> submitted (return to registry)
- pending review -> forwarded (direct forward path when workflow allows)

### Operational notes
- registry receives and proposes
- director or acting director reviews
- registry forwards after review
- deputy/final recipients consume the forwarded item through inbox flow

## 4. Memos
### States
- `DRAFT`
- `SUBMITTED`
- `IN_REVIEW`
- `RETURNED`
- `APPROVED_UNSIGNED`
- `SIGNED`
- `REJECTED`
- `CANCELLED`

### Main transitions
- draft -> submitted
- draft -> cancelled
- submitted -> in review
- submitted/in review -> returned
- submitted/in review -> rejected
- submitted/in review -> approved unsigned
- submitted/in review/approved unsigned -> signed
- returned -> submitted
- submitted/in review/approved unsigned -> draft (recall)
- most non-terminal states -> cancelled where allowed by service rules

### Operational notes
- memo routes are recorded in `dh_memo_routes`
- archive behavior is separate from primary status and is user-facing storage behavior

## 5. Orders
### States
- `WAITING_ATTACHMENT`
- `COMPLETE`
- `SENT`

### Main transitions
- waiting attachment -> complete
- complete -> sent

### Operational notes
- owner can add attachments while still in owner-managed state
- recipient inboxes are created when the order is sent

## 6. Outgoing Registration
### States
- `WAITING_ATTACHMENT`
- `COMPLETE`

### Main transitions
- waiting attachment -> complete

### Operational notes
- outgoing numbering and attachment completion are the primary lifecycle
- external circular intake through `outgoing-receive.php` follows the external circular state machine instead of the outgoing status machine above

## 7. Repairs
### States
- `PENDING`
- `IN_PROGRESS`
- `COMPLETED`
- `REJECTED`
- `CANCELLED`

### Main transitions
- pending -> in progress
- pending -> rejected
- pending -> cancelled
- in progress -> completed
- in progress -> cancelled

### Operational notes
- requester can edit/delete only while pending
- approval page moves pending items into in-progress or rejected
- management page continues the lifecycle to completed/cancelled

## 8. Room Booking
### Effective status groups
The room module uses a compatibility helper because the stored DB type may differ between environments.

Logical values used by the application:
- `0` = pending
- `1` = approved
- `2` = rejected

### Main transitions
- create -> pending
- pending -> approved
- pending -> rejected

### Operational notes
- approved bookings are the only ones used for availability conflicts and calendar occupancy
- pending items do not block availability checks
- owner-side delete is soft-delete and limited to allowed states

## 9. Vehicle Reservation
### States
- `DRAFT`
- `PENDING`
- `ASSIGNED`
- `APPROVED`
- `REJECTED`
- `CANCELLED`
- `COMPLETED`

### Main transitions seen in the current flow
- create/submit -> pending
- pending -> assigned (vehicle officer assigns vehicle and driver)
- assigned -> approved (final approval)
- assigned -> rejected
- approved -> completed
- various active states -> cancelled where permitted by workflow handlers

### Operational notes
- vehicle officer assignment is a distinct step before final approval
- approved/completed bookings can produce the PDF form
- final decision notes and assignment details are stored on the booking record

## 10. Read Tracking and Route History
- circulars and documents rely on recipient/inbox rows for delivery visibility
- route history records workflow transitions and forwards
- read state must never be inferred only from page views; it is stored explicitly when the module supports it

# Refactor Sequence (Module Order)
1) Auth/RBAC/Security (session, CSRF, permissions, audit)
2) Documents + Inbox (internal/external/outgoing/orders)
3) Attachments (upload/download, file refs, storage)
4) Booking/Vehicles/Repairs
5) Memo + Signature workflows
6) Admin/Users + Settings

Each module must ship with:
- Route list + permission matrix
- UX behavior summary
- Manual checklist + smoke script
- Error cases handled

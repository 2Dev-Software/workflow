START TRANSACTION;

-- Expand status enum to support new canonical states
ALTER TABLE dh_circulars
  MODIFY status enum(
    'DRAFT','SENT','RECALLED','RETURNED','FORWARDED','APPROVED','ARCHIVED','CANCELLED',
    'INTERNAL_DRAFT','INTERNAL_SENT','INTERNAL_RECALLED','INTERNAL_ARCHIVED',
    'EXTERNAL_SUBMITTED','EXTERNAL_PENDING_REVIEW','EXTERNAL_REVIEWED','EXTERNAL_FORWARDED'
  ) NOT NULL DEFAULT 'INTERNAL_DRAFT';

-- Map legacy statuses to new canonical values
UPDATE dh_circulars
SET status = CASE
  WHEN circularType = 'INTERNAL' THEN
    CASE status
      WHEN 'DRAFT' THEN 'INTERNAL_DRAFT'
      WHEN 'SENT' THEN 'INTERNAL_SENT'
      WHEN 'RECALLED' THEN 'INTERNAL_RECALLED'
      WHEN 'ARCHIVED' THEN 'INTERNAL_ARCHIVED'
      WHEN 'CANCELLED' THEN 'INTERNAL_RECALLED'
      WHEN 'RETURNED' THEN 'INTERNAL_SENT'
      WHEN 'FORWARDED' THEN 'INTERNAL_SENT'
      WHEN 'APPROVED' THEN 'INTERNAL_SENT'
      ELSE 'INTERNAL_SENT'
    END
  ELSE
    CASE status
      WHEN 'DRAFT' THEN 'EXTERNAL_SUBMITTED'
      WHEN 'SENT' THEN 'EXTERNAL_PENDING_REVIEW'
      WHEN 'RETURNED' THEN 'EXTERNAL_REVIEWED'
      WHEN 'APPROVED' THEN 'EXTERNAL_REVIEWED'
      WHEN 'FORWARDED' THEN 'EXTERNAL_FORWARDED'
      WHEN 'ARCHIVED' THEN 'EXTERNAL_FORWARDED'
      WHEN 'RECALLED' THEN 'EXTERNAL_SUBMITTED'
      WHEN 'CANCELLED' THEN 'EXTERNAL_SUBMITTED'
      ELSE 'EXTERNAL_SUBMITTED'
    END
END;

-- Restrict to new canonical enum values only
ALTER TABLE dh_circulars
  MODIFY status enum(
    'INTERNAL_DRAFT','INTERNAL_SENT','INTERNAL_RECALLED','INTERNAL_ARCHIVED',
    'EXTERNAL_SUBMITTED','EXTERNAL_PENDING_REVIEW','EXTERNAL_REVIEWED','EXTERNAL_FORWARDED'
  ) NOT NULL DEFAULT 'INTERNAL_DRAFT';

-- Expand and migrate inbox types
ALTER TABLE dh_circular_inboxes
  MODIFY inboxType enum(
    'NORMAL','DIRECTOR_BOX','CLERK_BOX','CLERK_RETURN_BOX',
    'normal_inbox','special_principal_inbox','saraban_return_inbox','acting_principal_inbox'
  ) NOT NULL DEFAULT 'normal_inbox';

UPDATE dh_circular_inboxes
SET inboxType = CASE inboxType
  WHEN 'NORMAL' THEN 'normal_inbox'
  WHEN 'DIRECTOR_BOX' THEN 'special_principal_inbox'
  WHEN 'CLERK_BOX' THEN 'normal_inbox'
  WHEN 'CLERK_RETURN_BOX' THEN 'saraban_return_inbox'
  ELSE inboxType
END;

-- Restrict to new canonical inbox types
ALTER TABLE dh_circular_inboxes
  MODIFY inboxType enum(
    'normal_inbox','special_principal_inbox','saraban_return_inbox','acting_principal_inbox'
  ) NOT NULL DEFAULT 'normal_inbox';

COMMIT;

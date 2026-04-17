INSERT INTO `dh_logs` (
    `pID`,
    `actorPID`,
    `logLevel`,
    `moduleName`,
    `actionName`,
    `actionStatus`,
    `entityName`,
    `entityID`,
    `logMessage`,
    `payloadData`,
    `httpMethod`,
    `requestURL`,
    `httpStatus`,
    `serverName`,
    `created_at`
)
SELECT
    NULL,
    r.`requesterPID`,
    'AUDIT',
    'repairs',
    'TIMELINE',
    'SUCCESS',
    'dh_repair_requests',
    r.`repairID`,
    'รับเรื่องคำร้องแล้ว',
    JSON_OBJECT(
        'actorPID', r.`requesterPID`,
        'event', 'CREATE',
        'toStatus', 'PENDING',
        'toLabel', 'ส่งคำร้องสำเร็จ',
        'timelineTitle', 'รับเรื่องคำร้องแล้ว',
        'subject', r.`subject`,
        'location', r.`location`,
        'equipment', r.`equipment`
    ),
    'CLI',
    'migration:021_backfill_repair_timeline_logs',
    200,
    'migration',
    r.`createdAt`
FROM `dh_repair_requests` AS r
WHERE NOT EXISTS (
    SELECT 1
    FROM `dh_logs` AS l
    WHERE l.`moduleName` = 'repairs'
      AND l.`actionName` = 'TIMELINE'
      AND l.`actionStatus` = 'SUCCESS'
      AND l.`entityName` = 'dh_repair_requests'
      AND l.`entityID` = r.`repairID`
      AND l.`logMessage` = 'รับเรื่องคำร้องแล้ว'
);

INSERT INTO `dh_logs` (
    `pID`,
    `actorPID`,
    `logLevel`,
    `moduleName`,
    `actionName`,
    `actionStatus`,
    `entityName`,
    `entityID`,
    `logMessage`,
    `payloadData`,
    `httpMethod`,
    `requestURL`,
    `httpStatus`,
    `serverName`,
    `created_at`
)
SELECT
    NULL,
    COALESCE(NULLIF(r.`assignedToPID`, ''), r.`requesterPID`),
    'AUDIT',
    'repairs',
    'TIMELINE',
    'SUCCESS',
    'dh_repair_requests',
    r.`repairID`,
    CASE r.`status`
        WHEN 'IN_PROGRESS' THEN 'กำลังดำเนินการ'
        WHEN 'COMPLETED' THEN 'เสร็จสิ้น'
        WHEN 'CANCELLED' THEN 'ยกเลิกคำร้อง'
        WHEN 'REJECTED' THEN 'ยกเลิกคำร้อง'
        ELSE r.`status`
    END,
    JSON_OBJECT(
        'actorPID', COALESCE(NULLIF(r.`assignedToPID`, ''), r.`requesterPID`),
        'event', 'STATUS_BACKFILL',
        'fromStatus', 'PENDING',
        'fromLabel', 'ส่งคำร้องสำเร็จ',
        'toStatus', r.`status`,
        'toLabel', CASE r.`status`
            WHEN 'IN_PROGRESS' THEN 'กำลังดำเนินการ'
            WHEN 'COMPLETED' THEN 'เสร็จสิ้น'
            WHEN 'CANCELLED' THEN 'ยกเลิกคำร้อง'
            WHEN 'REJECTED' THEN 'ยกเลิกคำร้อง'
            ELSE r.`status`
        END,
        'timelineTitle', CASE r.`status`
            WHEN 'IN_PROGRESS' THEN 'กำลังดำเนินการ'
            WHEN 'COMPLETED' THEN 'เสร็จสิ้น'
            WHEN 'CANCELLED' THEN 'ยกเลิกคำร้อง'
            WHEN 'REJECTED' THEN 'ยกเลิกคำร้อง'
            ELSE r.`status`
        END
    ),
    'CLI',
    'migration:021_backfill_repair_timeline_logs',
    200,
    'migration',
    COALESCE(
        r.`resolvedAt`,
        NULLIF(r.`updatedAt`, '0000-00-00 00:00:00'),
        r.`createdAt`
    )
FROM `dh_repair_requests` AS r
WHERE r.`status` <> 'PENDING'
  AND NOT EXISTS (
      SELECT 1
      FROM `dh_logs` AS l
      WHERE l.`moduleName` = 'repairs'
        AND l.`actionName` = 'TIMELINE'
        AND l.`actionStatus` = 'SUCCESS'
        AND l.`entityName` = 'dh_repair_requests'
        AND l.`entityID` = r.`repairID`
        AND l.`logMessage` = CASE r.`status`
            WHEN 'IN_PROGRESS' THEN 'กำลังดำเนินการ'
            WHEN 'COMPLETED' THEN 'เสร็จสิ้น'
            WHEN 'CANCELLED' THEN 'ยกเลิกคำร้อง'
            WHEN 'REJECTED' THEN 'ยกเลิกคำร้อง'
            ELSE r.`status`
        END
  );

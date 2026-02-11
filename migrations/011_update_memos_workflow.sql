START TRANSACTION;

-- Allow MEMO document type in workflow core (used by document-service inbox/read-receipts).
ALTER TABLE `dh_documents`
  MODIFY `documentType` enum('INTERNAL','EXTERNAL','OUTGOING','ORDER','MEMO') NOT NULL;

-- Expand memo status enum and add workflow columns (enterprise memo workflow).
ALTER TABLE `dh_memos`
  ADD COLUMN `memoNo` varchar(20) DEFAULT NULL AFTER `dh_year`,
  ADD COLUMN `memoSeq` int(11) DEFAULT NULL AFTER `memoNo`,
  ADD COLUMN `writeDate` date DEFAULT NULL AFTER `memoSeq`,
  ADD COLUMN `toType` enum('DIRECTOR','PERSON') DEFAULT NULL AFTER `writeDate`,
  ADD COLUMN `toPID` varchar(13) DEFAULT NULL AFTER `toType`,
  ADD COLUMN `submittedAt` datetime DEFAULT NULL AFTER `toPID`,
  ADD COLUMN `firstReadAt` datetime DEFAULT NULL AFTER `submittedAt`,
  ADD COLUMN `reviewNote` text DEFAULT NULL AFTER `detail`,
  ADD COLUMN `reviewedAt` datetime DEFAULT NULL AFTER `reviewNote`,
  ADD COLUMN `signedFileID` bigint(20) DEFAULT NULL AFTER `reviewedAt`,
  ADD COLUMN `isArchived` tinyint(1) NOT NULL DEFAULT 0 AFTER `signedFileID`,
  ADD COLUMN `archivedAt` datetime DEFAULT NULL AFTER `isArchived`,
  ADD COLUMN `updatedByPID` varchar(13) DEFAULT NULL AFTER `createdByPID`,
  ADD COLUMN `cancelledByPID` varchar(13) DEFAULT NULL AFTER `updatedByPID`,
  ADD COLUMN `cancelledAt` datetime DEFAULT NULL AFTER `cancelledByPID`;

-- Expand status enum to include new workflow states (temporary includes legacy APPROVED).
ALTER TABLE `dh_memos`
  MODIFY `status` enum(
    'DRAFT','SUBMITTED','APPROVED','REJECTED',
    'IN_REVIEW','RETURNED','APPROVED_UNSIGNED','SIGNED','CANCELLED'
  ) NOT NULL DEFAULT 'DRAFT';

-- Map legacy values to canonical values.
UPDATE `dh_memos` SET `status` = 'SIGNED' WHERE `status` = 'APPROVED';

-- Restrict to canonical enum values only.
ALTER TABLE `dh_memos`
  MODIFY `status` enum(
    'DRAFT','SUBMITTED','IN_REVIEW','RETURNED',
    'APPROVED_UNSIGNED','SIGNED','REJECTED','CANCELLED'
  ) NOT NULL DEFAULT 'DRAFT';

-- Indexes (optimize my-memos + approver inbox + numbering lookups).
ALTER TABLE `dh_memos`
  ADD UNIQUE KEY `uq_memo_year_seq` (`dh_year`,`memoSeq`),
  ADD KEY `idx_memo_no` (`memoNo`),
  ADD KEY `idx_memo_creator` (`createdByPID`,`isArchived`,`status`,`createdAt`),
  ADD KEY `idx_memo_to` (`toPID`,`status`,`createdAt`),
  ADD KEY `fk_memo_to` (`toPID`),
  ADD KEY `fk_memo_updated` (`updatedByPID`),
  ADD KEY `fk_memo_cancelled` (`cancelledByPID`),
  ADD KEY `fk_memo_signed_file` (`signedFileID`);

-- Foreign keys (best-effort referential integrity).
ALTER TABLE `dh_memos`
  ADD CONSTRAINT `fk_memo_to` FOREIGN KEY (`toPID`) REFERENCES `teacher` (`pID`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_memo_updated` FOREIGN KEY (`updatedByPID`) REFERENCES `teacher` (`pID`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_memo_cancelled` FOREIGN KEY (`cancelledByPID`) REFERENCES `teacher` (`pID`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_memo_signed_file` FOREIGN KEY (`signedFileID`) REFERENCES `dh_files` (`fileID`) ON DELETE SET NULL ON UPDATE CASCADE;

CREATE TABLE IF NOT EXISTS `dh_memo_routes` (
  `routeID` bigint(20) NOT NULL AUTO_INCREMENT,
  `memoID` bigint(20) NOT NULL,
  `action` enum('CREATE','UPDATE','SUBMIT','OPEN','RETURN','RESUBMIT','APPROVE_UNSIGNED','SIGN','REJECT','CANCEL','ARCHIVE') NOT NULL,
  `fromStatus` varchar(30) DEFAULT NULL,
  `toStatus` varchar(30) DEFAULT NULL,
  `actorPID` varchar(13) NOT NULL,
  `note` text DEFAULT NULL,
  `requestID` char(26) DEFAULT NULL,
  `createdAt` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`routeID`),
  KEY `idx_memoroute_memo` (`memoID`,`createdAt`),
  KEY `idx_memoroute_actor` (`actorPID`,`createdAt`),
  CONSTRAINT `fk_memoroute_memo` FOREIGN KEY (`memoID`) REFERENCES `dh_memos` (`memoID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_memoroute_actor` FOREIGN KEY (`actorPID`) REFERENCES `teacher` (`pID`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

COMMIT;

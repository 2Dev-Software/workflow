CREATE TABLE IF NOT EXISTS `dh_repair_requests` (
  `repairID` bigint(20) NOT NULL AUTO_INCREMENT,
  `dh_year` int(4) NOT NULL,
  `requesterPID` varchar(13) NOT NULL,
  `subject` varchar(300) NOT NULL,
  `detail` text DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `status` enum('PENDING','IN_PROGRESS','COMPLETED','REJECTED','CANCELLED') NOT NULL DEFAULT 'PENDING',
  `assignedToPID` varchar(13) DEFAULT NULL,
  `resolvedAt` datetime DEFAULT NULL,
  `deletedAt` datetime DEFAULT NULL,
  `createdAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `updatedAt` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
  PRIMARY KEY (`repairID`),
  KEY `idx_repair_year` (`dh_year`,`createdAt`),
  KEY `idx_repair_requester` (`requesterPID`),
  KEY `idx_repair_status` (`status`),
  CONSTRAINT `fk_repair_requester` FOREIGN KEY (`requesterPID`) REFERENCES `teacher` (`pID`) ON UPDATE CASCADE,
  CONSTRAINT `fk_repair_assigned` FOREIGN KEY (`assignedToPID`) REFERENCES `teacher` (`pID`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `dh_memos` (
  `memoID` bigint(20) NOT NULL AUTO_INCREMENT,
  `dh_year` int(4) NOT NULL,
  `subject` varchar(300) NOT NULL,
  `detail` text DEFAULT NULL,
  `status` enum('DRAFT','SUBMITTED','APPROVED','REJECTED') NOT NULL DEFAULT 'DRAFT',
  `createdByPID` varchar(13) NOT NULL,
  `approvedByPID` varchar(13) DEFAULT NULL,
  `approvedAt` datetime DEFAULT NULL,
  `deletedAt` datetime DEFAULT NULL,
  `createdAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `updatedAt` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
  PRIMARY KEY (`memoID`),
  KEY `idx_memo_year` (`dh_year`,`createdAt`),
  KEY `idx_memo_status` (`status`),
  CONSTRAINT `fk_memo_created` FOREIGN KEY (`createdByPID`) REFERENCES `teacher` (`pID`) ON UPDATE CASCADE,
  CONSTRAINT `fk_memo_approved` FOREIGN KEY (`approvedByPID`) REFERENCES `teacher` (`pID`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

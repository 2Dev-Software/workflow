CREATE TABLE IF NOT EXISTS `dh_migrations` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `version` int(11) unsigned NOT NULL,
  `name` varchar(150) NOT NULL,
  `appliedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_migrations_version` (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `dh_permissions` (
  `permissionID` int(11) NOT NULL AUTO_INCREMENT,
  `permissionKey` varchar(100) NOT NULL,
  `permissionName` varchar(150) NOT NULL,
  `permissionDescription` text DEFAULT NULL,
  PRIMARY KEY (`permissionID`),
  UNIQUE KEY `uq_permissions_key` (`permissionKey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `dh_role_permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `roleID` int(11) NOT NULL,
  `permissionID` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_role_perm` (`roleID`,`permissionID`),
  KEY `idx_role_perm_role` (`roleID`),
  KEY `idx_role_perm_perm` (`permissionID`),
  CONSTRAINT `fk_role_perm_role` FOREIGN KEY (`roleID`) REFERENCES `dh_roles` (`roleID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_role_perm_perm` FOREIGN KEY (`permissionID`) REFERENCES `dh_permissions` (`permissionID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `dh_documents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `documentType` enum('INTERNAL','EXTERNAL','OUTGOING','ORDER') NOT NULL,
  `documentNumber` varchar(50) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `content` longtext DEFAULT NULL,
  `status` varchar(50) NOT NULL,
  `senderName` varchar(255) DEFAULT NULL,
  `createdByPID` varchar(13) NOT NULL,
  `updatedByPID` varchar(13) DEFAULT NULL,
  `createdAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `updatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_documents_type_status` (`documentType`,`status`),
  KEY `idx_documents_number` (`documentNumber`),
  KEY `idx_documents_created` (`createdAt`),
  CONSTRAINT `fk_documents_creator` FOREIGN KEY (`createdByPID`) REFERENCES `teacher` (`pID`) ON UPDATE CASCADE,
  CONSTRAINT `fk_documents_updater` FOREIGN KEY (`updatedByPID`) REFERENCES `teacher` (`pID`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `dh_document_recipients` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `documentID` bigint(20) unsigned NOT NULL,
  `recipientPID` varchar(13) NOT NULL,
  `inboxType` varchar(50) NOT NULL,
  `inboxStatus` enum('UNREAD','READ','ARCHIVED') NOT NULL DEFAULT 'UNREAD',
  `readAt` datetime DEFAULT NULL,
  `receivedAt` datetime DEFAULT NULL,
  `createdAt` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_recipient` (`documentID`,`recipientPID`,`inboxType`),
  KEY `idx_recipient_pid` (`recipientPID`,`inboxStatus`),
  KEY `idx_recipient_document` (`documentID`),
  CONSTRAINT `fk_recipients_document` FOREIGN KEY (`documentID`) REFERENCES `dh_documents` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_recipients_user` FOREIGN KEY (`recipientPID`) REFERENCES `teacher` (`pID`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `dh_document_routes` (
  `routeID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `documentID` bigint(20) unsigned NOT NULL,
  `fromStatus` varchar(50) DEFAULT NULL,
  `toStatus` varchar(50) NOT NULL,
  `actorPID` varchar(13) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `requestID` char(26) DEFAULT NULL,
  `createdAt` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`routeID`),
  KEY `idx_routes_document` (`documentID`),
  KEY `idx_routes_actor` (`actorPID`),
  CONSTRAINT `fk_routes_document` FOREIGN KEY (`documentID`) REFERENCES `dh_documents` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_routes_actor` FOREIGN KEY (`actorPID`) REFERENCES `teacher` (`pID`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `dh_read_receipts` (
  `receiptID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `documentID` bigint(20) unsigned NOT NULL,
  `recipientPID` varchar(13) NOT NULL,
  `readAt` datetime NOT NULL,
  `requestID` char(26) DEFAULT NULL,
  `ipAddress` varchar(45) DEFAULT NULL,
  `userAgent` varchar(255) DEFAULT NULL,
  `receiptHash` char(64) DEFAULT NULL,
  PRIMARY KEY (`receiptID`),
  KEY `idx_receipts_document` (`documentID`),
  KEY `idx_receipts_recipient` (`recipientPID`,`readAt`),
  CONSTRAINT `fk_receipts_document` FOREIGN KEY (`documentID`) REFERENCES `dh_documents` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_receipts_recipient` FOREIGN KEY (`recipientPID`) REFERENCES `teacher` (`pID`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `dh_sequences` (
  `seqKey` varchar(50) NOT NULL,
  `currentValue` bigint(20) unsigned NOT NULL DEFAULT 0,
  `updatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`seqKey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `dh_login_attempts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `pID` varchar(13) NOT NULL,
  `ipAddress` varchar(45) NOT NULL,
  `attemptCount` int(11) unsigned NOT NULL DEFAULT 0,
  `lastAttemptAt` datetime DEFAULT NULL,
  `lockedUntil` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_login_attempt` (`pID`,`ipAddress`),
  KEY `idx_login_pid` (`pID`),
  KEY `idx_login_ip` (`ipAddress`),
  CONSTRAINT `fk_login_attempt_user` FOREIGN KEY (`pID`) REFERENCES `teacher` (`pID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

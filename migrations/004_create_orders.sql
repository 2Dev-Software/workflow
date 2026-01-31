CREATE TABLE IF NOT EXISTS `dh_orders` (
  `orderID` bigint(20) NOT NULL AUTO_INCREMENT,
  `dh_year` int(4) NOT NULL,
  `orderNo` varchar(20) NOT NULL,
  `orderSeq` int(11) NOT NULL,
  `subject` varchar(300) NOT NULL,
  `detail` text DEFAULT NULL,
  `status` enum('WAITING_ATTACHMENT','COMPLETE','SENT') NOT NULL DEFAULT 'WAITING_ATTACHMENT',
  `createdByPID` varchar(13) NOT NULL,
  `updatedByPID` varchar(13) DEFAULT NULL,
  `deletedAt` datetime DEFAULT NULL,
  `createdAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `updatedAt` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
  PRIMARY KEY (`orderID`),
  UNIQUE KEY `uq_order_no` (`dh_year`,`orderSeq`),
  KEY `idx_order_year` (`dh_year`,`createdAt`),
  KEY `fk_order_created` (`createdByPID`),
  KEY `fk_order_updated` (`updatedByPID`),
  CONSTRAINT `fk_order_created` FOREIGN KEY (`createdByPID`) REFERENCES `teacher` (`pID`) ON UPDATE CASCADE,
  CONSTRAINT `fk_order_updated` FOREIGN KEY (`updatedByPID`) REFERENCES `teacher` (`pID`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `dh_order_recipients` (
  `recipientID` bigint(20) NOT NULL AUTO_INCREMENT,
  `orderID` bigint(20) NOT NULL,
  `targetType` enum('UNIT','ROLE','PERSON') NOT NULL,
  `fID` int(3) DEFAULT NULL,
  `roleID` int(2) DEFAULT NULL,
  `pID` varchar(13) DEFAULT NULL,
  `isCc` tinyint(1) NOT NULL DEFAULT 0,
  `createdAt` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`recipientID`),
  KEY `idx_orderrec_target` (`targetType`,`fID`,`roleID`,`pID`),
  KEY `idx_orderrec_order` (`orderID`),
  KEY `fk_orderrec_person` (`pID`),
  CONSTRAINT `fk_orderrec_order` FOREIGN KEY (`orderID`) REFERENCES `dh_orders` (`orderID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_orderrec_person` FOREIGN KEY (`pID`) REFERENCES `teacher` (`pID`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `dh_order_inboxes` (
  `inboxID` bigint(20) NOT NULL AUTO_INCREMENT,
  `orderID` bigint(20) NOT NULL,
  `pID` varchar(13) NOT NULL,
  `isRead` tinyint(1) NOT NULL DEFAULT 0,
  `readAt` datetime DEFAULT NULL,
  `isArchived` tinyint(1) NOT NULL DEFAULT 0,
  `archivedAt` datetime DEFAULT NULL,
  `deliveredByPID` varchar(13) DEFAULT NULL,
  `deliveredAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `updatedAt` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
  PRIMARY KEY (`inboxID`),
  KEY `idx_orderinbox_user` (`pID`,`isRead`,`isArchived`,`deliveredAt`),
  KEY `idx_orderinbox_order` (`orderID`),
  KEY `fk_orderinbox_delivered` (`deliveredByPID`),
  CONSTRAINT `fk_orderinbox_order` FOREIGN KEY (`orderID`) REFERENCES `dh_orders` (`orderID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_orderinbox_user` FOREIGN KEY (`pID`) REFERENCES `teacher` (`pID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_orderinbox_delivered` FOREIGN KEY (`deliveredByPID`) REFERENCES `teacher` (`pID`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `dh_order_routes` (
  `routeID` bigint(20) NOT NULL AUTO_INCREMENT,
  `orderID` bigint(20) NOT NULL,
  `action` enum('CREATE','SEND','RECALL','FORWARD','ARCHIVE','CANCEL') NOT NULL,
  `fromPID` varchar(13) DEFAULT NULL,
  `toPID` varchar(13) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `actionAt` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`routeID`),
  KEY `idx_orderroute_action` (`action`,`actionAt`),
  KEY `idx_orderroute_to` (`toPID`),
  KEY `fk_orderroute_order` (`orderID`),
  CONSTRAINT `fk_orderroute_order` FOREIGN KEY (`orderID`) REFERENCES `dh_orders` (`orderID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_orderroute_from` FOREIGN KEY (`fromPID`) REFERENCES `teacher` (`pID`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_orderroute_to` FOREIGN KEY (`toPID`) REFERENCES `teacher` (`pID`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

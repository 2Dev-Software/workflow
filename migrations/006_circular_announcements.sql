CREATE TABLE IF NOT EXISTS `dh_circular_announcements` (
  `announcementID` bigint(20) NOT NULL AUTO_INCREMENT,
  `circularID` bigint(20) NOT NULL,
  `selectedByPID` varchar(13) NOT NULL,
  `selectedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `isActive` tinyint(1) NOT NULL DEFAULT 1,
  `note` text DEFAULT NULL,
  PRIMARY KEY (`announcementID`),
  KEY `idx_cir_announce_active` (`isActive`,`selectedAt`),
  KEY `fk_cir_announce_circular` (`circularID`),
  KEY `fk_cir_announce_user` (`selectedByPID`),
  CONSTRAINT `fk_cir_announce_circular` FOREIGN KEY (`circularID`) REFERENCES `dh_circulars` (`circularID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_cir_announce_user` FOREIGN KEY (`selectedByPID`) REFERENCES `teacher` (`pID`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

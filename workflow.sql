-- -------------------------------------------------------------
-- TablePlus 6.8.0(654)
--
-- https://tableplus.com/
--
-- Database: deebuk_platform
-- Generation Time: 2569-01-24 21:47:26.7720
-- -------------------------------------------------------------


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


CREATE TABLE `department` (
  `dID` int(3) NOT NULL,
  `dName` text NOT NULL,
  PRIMARY KEY (`dID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `dh_circular_inboxes` (
  `inboxID` bigint(20) NOT NULL AUTO_INCREMENT,
  `circularID` bigint(20) NOT NULL,
  `pID` varchar(13) NOT NULL,
  `inboxType` enum('NORMAL','DIRECTOR_BOX','CLERK_BOX','CLERK_RETURN_BOX') NOT NULL DEFAULT 'NORMAL',
  `isRead` tinyint(1) NOT NULL DEFAULT 0,
  `readAt` datetime DEFAULT NULL,
  `isArchived` tinyint(1) NOT NULL DEFAULT 0,
  `archivedAt` datetime DEFAULT NULL,
  `deliveredByPID` varchar(13) DEFAULT NULL,
  `deliveredAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `updatedAt` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
  PRIMARY KEY (`inboxID`),
  KEY `fk_cirinbox_delivered_by` (`deliveredByPID`),
  KEY `idx_cirinbox_user` (`pID`,`inboxType`,`isRead`,`isArchived`,`deliveredAt`),
  KEY `idx_cirinbox_circular` (`circularID`),
  CONSTRAINT `fk_cirinbox_circular` FOREIGN KEY (`circularID`) REFERENCES `dh_circulars` (`circularID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_cirinbox_delivered_by` FOREIGN KEY (`deliveredByPID`) REFERENCES `teacher` (`pID`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_cirinbox_user` FOREIGN KEY (`pID`) REFERENCES `teacher` (`pID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `dh_circular_recipients` (
  `recipientID` bigint(20) NOT NULL AUTO_INCREMENT,
  `circularID` bigint(20) NOT NULL,
  `targetType` enum('UNIT','ROLE','PERSON') NOT NULL,
  `fID` int(3) DEFAULT NULL,
  `roleID` int(2) DEFAULT NULL,
  `pID` varchar(13) DEFAULT NULL,
  `isCc` tinyint(1) NOT NULL DEFAULT 0,
  `createdAt` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`recipientID`),
  KEY `fk_cirrec_person` (`pID`),
  KEY `idx_cirrec_target` (`targetType`,`fID`,`roleID`,`pID`),
  KEY `idx_cirrec_circular` (`circularID`),
  CONSTRAINT `fk_cirrec_circular` FOREIGN KEY (`circularID`) REFERENCES `dh_circulars` (`circularID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_cirrec_person` FOREIGN KEY (`pID`) REFERENCES `teacher` (`pID`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `dh_circular_routes` (
  `routeID` bigint(20) NOT NULL AUTO_INCREMENT,
  `circularID` bigint(20) NOT NULL,
  `action` enum('CREATE','SEND','RECALL','RETURN','FORWARD','APPROVE','ARCHIVE','CANCEL') NOT NULL,
  `fromPID` varchar(13) DEFAULT NULL,
  `toPID` varchar(13) DEFAULT NULL,
  `toFID` int(3) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `actionAt` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`routeID`),
  KEY `fk_cirroute_circular` (`circularID`),
  KEY `fk_cirroute_from` (`fromPID`),
  KEY `idx_cirroute_action` (`action`,`actionAt`),
  KEY `idx_cirroute_to` (`toPID`,`toFID`),
  CONSTRAINT `fk_cirroute_circular` FOREIGN KEY (`circularID`) REFERENCES `dh_circulars` (`circularID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_cirroute_from` FOREIGN KEY (`fromPID`) REFERENCES `teacher` (`pID`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_cirroute_to` FOREIGN KEY (`toPID`) REFERENCES `teacher` (`pID`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `dh_circulars` (
  `circularID` bigint(20) NOT NULL AUTO_INCREMENT,
  `dh_year` int(4) NOT NULL,
  `circularType` enum('INTERNAL','EXTERNAL') NOT NULL DEFAULT 'INTERNAL',
  `subject` varchar(300) NOT NULL,
  `detail` text DEFAULT NULL,
  `linkURL` varchar(500) DEFAULT NULL,
  `fromFID` int(3) DEFAULT NULL,
  `extPriority` enum('ปกติ','ด่วน','ด่วนมาก','ด่วนที่สุด') DEFAULT NULL,
  `extBookNo` varchar(80) DEFAULT NULL,
  `extIssuedDate` date DEFAULT NULL,
  `extFromText` varchar(255) DEFAULT NULL,
  `extGroupFID` int(3) DEFAULT NULL,
  `status` enum('DRAFT','SENT','RECALLED','RETURNED','FORWARDED','APPROVED','ARCHIVED','CANCELLED') NOT NULL DEFAULT 'DRAFT',
  `createdByPID` varchar(13) NOT NULL,
  `updatedByPID` varchar(13) DEFAULT NULL,
  `deletedAt` datetime DEFAULT NULL,
  `createdAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `updatedAt` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
  PRIMARY KEY (`circularID`),
  UNIQUE KEY `uq_cir_ext_book` (`dh_year`,`extBookNo`),
  KEY `fk_cir_created_by` (`createdByPID`),
  KEY `fk_cir_updated_by` (`updatedByPID`),
  KEY `idx_cir_type_status` (`circularType`,`status`),
  KEY `idx_cir_year` (`dh_year`,`createdAt`),
  KEY `idx_cir_fromFID` (`fromFID`),
  CONSTRAINT `fk_cir_created_by` FOREIGN KEY (`createdByPID`) REFERENCES `teacher` (`pID`) ON UPDATE CASCADE,
  CONSTRAINT `fk_cir_updated_by` FOREIGN KEY (`updatedByPID`) REFERENCES `teacher` (`pID`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `dh_exec_duty_logs` (
  `dutyLogID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `pID` varchar(13) DEFAULT NULL,
  `dutyStatus` tinyint(3) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `end_at` datetime DEFAULT NULL,
  PRIMARY KEY (`dutyLogID`),
  KEY `idx_dh_exec_duty_logs_pid` (`pID`),
  KEY `idx_dh_exec_duty_logs_status` (`dutyStatus`),
  CONSTRAINT `chk_dh_exec_duty_logs_status` CHECK (`dutyStatus` in (0,1,2))
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `dh_file_refs` (
  `refID` bigint(20) NOT NULL AUTO_INCREMENT,
  `fileID` bigint(20) NOT NULL,
  `moduleName` varchar(50) NOT NULL,
  `entityName` varchar(100) NOT NULL,
  `entityID` varchar(64) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `attachedByPID` varchar(13) NOT NULL,
  `attachedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`refID`),
  KEY `fk_frefs_user` (`attachedByPID`),
  KEY `idx_frefs_entity` (`moduleName`,`entityName`,`entityID`),
  KEY `idx_frefs_file` (`fileID`),
  CONSTRAINT `fk_frefs_file` FOREIGN KEY (`fileID`) REFERENCES `dh_files` (`fileID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_frefs_user` FOREIGN KEY (`attachedByPID`) REFERENCES `teacher` (`pID`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `dh_files` (
  `fileID` bigint(20) NOT NULL AUTO_INCREMENT,
  `fileName` varchar(255) NOT NULL,
  `filePath` varchar(500) NOT NULL,
  `mimeType` varchar(100) DEFAULT NULL,
  `fileSize` bigint(20) unsigned DEFAULT NULL,
  `checksumSHA256` char(64) DEFAULT NULL,
  `storageProvider` varchar(50) DEFAULT NULL,
  `version` int(11) NOT NULL DEFAULT 1,
  `uploadedByPID` varchar(13) NOT NULL,
  `uploadedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `deletedAt` datetime DEFAULT NULL,
  PRIMARY KEY (`fileID`),
  KEY `idx_files_checksum` (`checksumSHA256`),
  KEY `idx_files_uploader` (`uploadedByPID`,`uploadedAt`),
  CONSTRAINT `fk_files_uploader` FOREIGN KEY (`uploadedByPID`) REFERENCES `teacher` (`pID`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `dh_logs` (
  `logID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `pID` int(10) unsigned DEFAULT NULL,
  `sessionID` varchar(64) DEFAULT NULL,
  `requestID` char(26) DEFAULT NULL,
  `traceID` char(36) DEFAULT NULL,
  `logLevel` enum('INFO','WARN','ERROR','SECURITY','AUDIT') NOT NULL DEFAULT 'INFO',
  `moduleName` varchar(50) NOT NULL,
  `actionName` varchar(50) NOT NULL,
  `actionStatus` enum('SUCCESS','FAIL','DENY') NOT NULL DEFAULT 'SUCCESS',
  `entityName` varchar(100) DEFAULT NULL,
  `entityID` bigint(20) unsigned DEFAULT NULL,
  `logMessage` varchar(500) DEFAULT NULL,
  `diffData` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`diffData`)),
  `payloadData` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payloadData`)),
  `httpMethod` enum('GET','POST','PUT','PATCH','DELETE','CLI') DEFAULT NULL,
  `requestURL` varchar(255) DEFAULT NULL,
  `httpStatus` smallint(5) unsigned DEFAULT NULL,
  `latencyMS` int(10) unsigned DEFAULT NULL,
  `ipAddress` varchar(45) DEFAULT NULL,
  `userAgent` varchar(255) DEFAULT NULL,
  `serverName` varchar(50) DEFAULT NULL,
  `created_at` timestamp(6) NOT NULL DEFAULT current_timestamp(6),
  PRIMARY KEY (`logID`),
  KEY `idx_person_time` (`pID`,`created_at`),
  KEY `idx_module_action` (`moduleName`,`actionName`,`created_at`),
  KEY `idx_entity_time` (`entityName`,`entityID`,`created_at`),
  KEY `idx_level_time` (`logLevel`,`created_at`),
  KEY `idx_request` (`requestID`),
  FULLTEXT KEY `ftx_message` (`logMessage`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `dh_positions` (
  `positionID` int(11) NOT NULL AUTO_INCREMENT,
  `positionName` varchar(100) NOT NULL,
  `positionDescription` text DEFAULT NULL,
  PRIMARY KEY (`positionID`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `dh_roles` (
  `roleID` int(11) NOT NULL AUTO_INCREMENT,
  `roleName` varchar(100) NOT NULL,
  `roleDescription` text NOT NULL,
  PRIMARY KEY (`roleID`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `dh_room_bookings` (
  `roomBookingID` bigint(20) NOT NULL AUTO_INCREMENT,
  `dh_year` int(4) NOT NULL,
  `requesterPID` varchar(13) NOT NULL,
  `roomID` int(11) NOT NULL,
  `startDate` date NOT NULL,
  `endDate` date NOT NULL,
  `startTime` time NOT NULL,
  `endTime` time NOT NULL,
  `attendeeCount` smallint(5) unsigned NOT NULL DEFAULT 1,
  `status` enum('DRAFT','PENDING','APPROVED','REJECTED','CANCELLED','COMPLETED') NOT NULL DEFAULT 'PENDING',
  `statusReason` text DEFAULT NULL,
  `bookingTopic` varchar(255) DEFAULT NULL,
  `bookingDetail` text DEFAULT NULL,
  `equipmentDetail` text DEFAULT NULL,
  `requesterDisplayName` varchar(255) DEFAULT NULL,
  `approvedByPID` varchar(13) DEFAULT NULL,
  `approvedAt` datetime DEFAULT NULL,
  `deletedAt` datetime DEFAULT NULL,
  `createdAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `updatedAt` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
  PRIMARY KEY (`roomBookingID`),
  KEY `fk_room_approved` (`approvedByPID`),
  KEY `idx_room_booking_range` (`roomID`,`startDate`,`endDate`),
  KEY `idx_room_booking_time` (`roomID`,`startTime`,`endTime`),
  KEY `idx_room_status` (`status`),
  KEY `idx_room_requester` (`requesterPID`,`createdAt`),
  CONSTRAINT `fk_room_approved` FOREIGN KEY (`approvedByPID`) REFERENCES `teacher` (`pID`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_room_req` FOREIGN KEY (`requesterPID`) REFERENCES `teacher` (`pID`) ON UPDATE CASCADE,
  CONSTRAINT `fk_room_room` FOREIGN KEY (`roomID`) REFERENCES `dh_rooms` (`roomID`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `dh_rooms` (
  `roomID` int(11) NOT NULL AUTO_INCREMENT,
  `roomName` varchar(150) NOT NULL,
  `roomStatus` enum('พร้อมใช้งาน','ระงับชั่วคราว','กำลังซ่อม','ไม่พร้อมใช้งาน') NOT NULL DEFAULT 'พร้อมใช้งาน',
  `roomNote` text NOT NULL,
  `createdAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updatedAt` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deletedAt` datetime DEFAULT NULL,
  PRIMARY KEY (`roomID`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `dh_vehicle_bookings` (
  `bookingID` bigint(20) NOT NULL AUTO_INCREMENT,
  `dh_year` int(4) NOT NULL,
  `requesterPID` varchar(13) NOT NULL,
  `vehicleID` int(11) DEFAULT NULL,
  `driverPID` varchar(13) DEFAULT NULL,
  `driverName` varchar(150) DEFAULT NULL,
  `driverTel` varchar(10) DEFAULT NULL,
  `startAt` datetime NOT NULL,
  `endAt` datetime NOT NULL,
  `status` enum('DRAFT','PENDING','ASSIGNED','APPROVED','REJECTED','CANCELLED','COMPLETED') NOT NULL DEFAULT 'PENDING',
  `statusReason` text DEFAULT NULL,
  `approvedByPID` varchar(13) DEFAULT NULL,
  `approvedAt` datetime DEFAULT NULL,
  `deletedAt` timestamp NULL DEFAULT NULL,
  `createdAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `updatedAt` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
  PRIMARY KEY (`bookingID`),
  KEY `fk_booking_requester` (`requesterPID`),
  KEY `fk_booking_approvedby` (`approvedByPID`),
  KEY `idx_booking_time` (`startAt`,`endAt`),
  KEY `idx_booking_vehicle_time` (`vehicleID`,`startAt`),
  KEY `idx_booking_driver_time` (`driverPID`,`startAt`),
  KEY `idx_booking_status` (`status`),
  CONSTRAINT `fk_booking_approvedby` FOREIGN KEY (`approvedByPID`) REFERENCES `teacher` (`pID`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_booking_driverpid` FOREIGN KEY (`driverPID`) REFERENCES `teacher` (`pID`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_booking_requester` FOREIGN KEY (`requesterPID`) REFERENCES `teacher` (`pID`) ON UPDATE CASCADE,
  CONSTRAINT `fk_booking_vehicle` FOREIGN KEY (`vehicleID`) REFERENCES `dh_vehicles` (`vehicleID`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `dh_vehicles` (
  `vehicleID` int(11) NOT NULL AUTO_INCREMENT,
  `vehicleType` varchar(50) NOT NULL,
  `vehicleBrand` varchar(100) DEFAULT NULL,
  `vehicleModel` varchar(100) DEFAULT NULL,
  `vehiclePlate` varchar(50) NOT NULL,
  `vehicleColor` varchar(50) DEFAULT NULL,
  `vehicleCapacity` int(2) DEFAULT 4,
  `vehicleStatus` enum('พร้อมใช้งาน','อยู่ระหว่างใช้งาน','ส่งซ่อม','ไม่พร้อมใช้งาน') DEFAULT 'พร้อมใช้งาน',
  `createdAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `updatedAt` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`vehicleID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `faction` (
  `fID` int(3) NOT NULL,
  `fName` text NOT NULL,
  PRIMARY KEY (`fID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `level` (
  `lID` int(2) NOT NULL,
  `lName` text NOT NULL,
  PRIMARY KEY (`lID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `position` (
  `oID` int(2) NOT NULL,
  `oName` text NOT NULL,
  PRIMARY KEY (`oID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE `teacher` (
  `pID` varchar(20) NOT NULL,
  `fName` text NOT NULL,
  `fID` int(3) NOT NULL,
  `dID` int(3) NOT NULL,
  `lID` int(2) NOT NULL,
  `oID` int(3) NOT NULL,
  `positionID` int(2) NOT NULL,
  `roleID` int(2) NOT NULL,
  `telephone` varchar(10) NOT NULL,
  `picture` text NOT NULL,
  `signature` text DEFAULT NULL,
  `passWord` text NOT NULL,
  `LineID` text NOT NULL,
  `status` int(1) NOT NULL,
  PRIMARY KEY (`pID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `thesystem` (
  `ID` int(1) NOT NULL,
  `pa_year` int(4) NOT NULL,
  `st_year` int(4) NOT NULL,
  `st_semester` int(1) NOT NULL,
  `dh_year` int(4) NOT NULL,
  `dh_status` tinyint(1) NOT NULL DEFAULT 1,
  `hb_year` int(4) NOT NULL,
  `hb_statusT` int(1) NOT NULL,
  `hb_statusS` int(1) NOT NULL,
  `sj_Status` int(1) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
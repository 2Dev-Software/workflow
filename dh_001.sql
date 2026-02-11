/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-12.1.2-MariaDB, for osx10.21 (arm64)
--
-- Host: 127.0.0.1    Database: deebuk_platform
-- ------------------------------------------------------
-- Server version	12.1.2-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `dh_circular_announcements`
--

DROP TABLE IF EXISTS `dh_circular_announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dh_circular_announcements` (
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dh_circular_announcements`
--

LOCK TABLES `dh_circular_announcements` WRITE;
/*!40000 ALTER TABLE `dh_circular_announcements` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `dh_circular_announcements` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `dh_circular_inboxes`
--

DROP TABLE IF EXISTS `dh_circular_inboxes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dh_circular_inboxes` (
  `inboxID` bigint(20) NOT NULL AUTO_INCREMENT,
  `circularID` bigint(20) NOT NULL,
  `pID` varchar(13) NOT NULL,
  `inboxType` enum('normal_inbox','special_principal_inbox','saraban_return_inbox','acting_principal_inbox') NOT NULL DEFAULT 'normal_inbox',
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
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dh_circular_inboxes`
--

LOCK TABLES `dh_circular_inboxes` WRITE;
/*!40000 ALTER TABLE `dh_circular_inboxes` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `dh_circular_inboxes` VALUES
(5,1,'3810100580006','normal_inbox',0,NULL,0,NULL,'1829900159722','2026-02-01 16:05:53','0000-00-00 00:00:00'),
(6,1,'5800900028151','normal_inbox',0,NULL,0,NULL,'1829900159722','2026-02-01 16:05:53','0000-00-00 00:00:00'),
(7,1,'3820100025592','normal_inbox',0,NULL,0,NULL,'1829900159722','2026-02-01 16:05:53','0000-00-00 00:00:00'),
(8,1,'1829900012446','normal_inbox',0,NULL,0,NULL,'1829900159722','2026-02-01 16:05:53','0000-00-00 00:00:00'),
(10,3,'1829900159722','normal_inbox',1,'2026-02-02 00:18:19',0,NULL,'1810500062871','2026-02-01 17:18:01','2026-02-01 17:18:19'),
(12,2,'1829900159722','normal_inbox',1,'2026-02-02 00:43:16',1,'2026-02-02 00:45:33','1810500062871','2026-02-01 17:22:17','2026-02-01 17:45:33');
/*!40000 ALTER TABLE `dh_circular_inboxes` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `dh_circular_recipients`
--

DROP TABLE IF EXISTS `dh_circular_recipients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dh_circular_recipients`
--

LOCK TABLES `dh_circular_recipients` WRITE;
/*!40000 ALTER TABLE `dh_circular_recipients` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `dh_circular_recipients` VALUES
(1,1,'PERSON',NULL,NULL,'3810100580006',0,'2026-02-01 16:05:41'),
(2,1,'PERSON',NULL,NULL,'5800900028151',0,'2026-02-01 16:05:41'),
(3,1,'PERSON',NULL,NULL,'3820100025592',0,'2026-02-01 16:05:41'),
(4,1,'PERSON',NULL,NULL,'1829900012446',0,'2026-02-01 16:05:41'),
(5,2,'PERSON',NULL,NULL,'1829900159722',0,'2026-02-01 17:06:14'),
(6,3,'PERSON',NULL,NULL,'1829900159722',0,'2026-02-01 17:18:01');
/*!40000 ALTER TABLE `dh_circular_recipients` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `dh_circular_routes`
--

DROP TABLE IF EXISTS `dh_circular_routes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dh_circular_routes`
--

LOCK TABLES `dh_circular_routes` WRITE;
/*!40000 ALTER TABLE `dh_circular_routes` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `dh_circular_routes` VALUES
(1,1,'CREATE','1829900159722',NULL,NULL,NULL,'2026-02-01 16:05:41'),
(2,1,'SEND','1829900159722',NULL,NULL,NULL,'2026-02-01 16:05:41'),
(3,1,'RECALL','1829900159722',NULL,NULL,NULL,'2026-02-01 16:05:49'),
(4,1,'RECALL','1829900159722',NULL,NULL,NULL,'2026-02-01 16:05:52'),
(5,1,'SEND','1829900159722',NULL,NULL,'RESEND','2026-02-01 16:05:53'),
(6,2,'CREATE','1810500062871',NULL,NULL,NULL,'2026-02-01 17:06:14'),
(7,2,'SEND','1810500062871',NULL,NULL,NULL,'2026-02-01 17:06:14'),
(8,3,'CREATE','1810500062871',NULL,NULL,NULL,'2026-02-01 17:18:01'),
(9,3,'SEND','1810500062871',NULL,NULL,NULL,'2026-02-01 17:18:01'),
(10,2,'RECALL','1810500062871',NULL,NULL,NULL,'2026-02-01 17:22:07'),
(11,2,'SEND','1810500062871',NULL,NULL,'RESEND','2026-02-01 17:22:12'),
(12,2,'RECALL','1810500062871',NULL,NULL,NULL,'2026-02-01 17:22:14'),
(13,2,'RECALL','1810500062871',NULL,NULL,NULL,'2026-02-01 17:22:16'),
(14,2,'SEND','1810500062871',NULL,NULL,'RESEND','2026-02-01 17:22:17');
/*!40000 ALTER TABLE `dh_circular_routes` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `dh_circulars`
--

DROP TABLE IF EXISTS `dh_circulars`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
  `status` enum('INTERNAL_DRAFT','INTERNAL_SENT','INTERNAL_RECALLED','INTERNAL_ARCHIVED','EXTERNAL_SUBMITTED','EXTERNAL_PENDING_REVIEW','EXTERNAL_REVIEWED','EXTERNAL_FORWARDED') NOT NULL DEFAULT 'INTERNAL_DRAFT',
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dh_circulars`
--

LOCK TABLES `dh_circulars` WRITE;
/*!40000 ALTER TABLE `dh_circulars` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `dh_circulars` VALUES
(1,2568,'INTERNAL','กหดหกดหกดกหด','หกดกหดหกดหกดหกดหก',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'INTERNAL_SENT','1829900159722','1829900159722',NULL,'2026-02-01 16:05:41','2026-02-01 16:05:53'),
(2,2568,'INTERNAL','1212','121212',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'INTERNAL_SENT','1810500062871','1810500062871',NULL,'2026-02-01 17:06:14','2026-02-01 17:22:17'),
(3,2568,'INTERNAL','เอกสารราชการ','121212',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'INTERNAL_SENT','1810500062871','1810500062871',NULL,'2026-02-01 17:18:01','2026-02-01 17:18:01');
/*!40000 ALTER TABLE `dh_circulars` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `dh_document_recipients`
--

DROP TABLE IF EXISTS `dh_document_recipients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dh_document_recipients` (
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dh_document_recipients`
--

LOCK TABLES `dh_document_recipients` WRITE;
/*!40000 ALTER TABLE `dh_document_recipients` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `dh_document_recipients` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `dh_document_routes`
--

DROP TABLE IF EXISTS `dh_document_routes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dh_document_routes` (
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
  CONSTRAINT `fk_routes_actor` FOREIGN KEY (`actorPID`) REFERENCES `teacher` (`pID`) ON UPDATE CASCADE,
  CONSTRAINT `fk_routes_document` FOREIGN KEY (`documentID`) REFERENCES `dh_documents` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dh_document_routes`
--

LOCK TABLES `dh_document_routes` WRITE;
/*!40000 ALTER TABLE `dh_document_routes` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `dh_document_routes` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `dh_documents`
--

DROP TABLE IF EXISTS `dh_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dh_documents` (
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
  KEY `fk_documents_creator` (`createdByPID`),
  KEY `fk_documents_updater` (`updatedByPID`),
  CONSTRAINT `fk_documents_creator` FOREIGN KEY (`createdByPID`) REFERENCES `teacher` (`pID`) ON UPDATE CASCADE,
  CONSTRAINT `fk_documents_updater` FOREIGN KEY (`updatedByPID`) REFERENCES `teacher` (`pID`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dh_documents`
--

LOCK TABLES `dh_documents` WRITE;
/*!40000 ALTER TABLE `dh_documents` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `dh_documents` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `dh_exec_duty_logs`
--

DROP TABLE IF EXISTS `dh_exec_duty_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dh_exec_duty_logs`
--

LOCK TABLES `dh_exec_duty_logs` WRITE;
/*!40000 ALTER TABLE `dh_exec_duty_logs` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `dh_exec_duty_logs` VALUES
(1,'3810500334835',0,'2026-01-14 06:49:20',NULL),
(2,'1820500004103',0,'2026-01-14 06:50:03',NULL),
(3,'1820500005169',0,'2026-01-14 06:53:13',NULL),
(4,'3810500334835',0,'2026-01-14 06:53:21',NULL),
(5,'3810500334835',0,'2026-01-14 06:53:35',NULL),
(6,'1820500004103',0,'2026-01-14 06:54:26',NULL),
(7,'3810500334835',0,'2026-01-14 06:54:30','2026-01-14 14:01:21'),
(8,'1820500004103',0,'2026-01-14 07:01:21','2026-01-14 16:24:18'),
(9,'1820500005169',0,'2026-01-14 09:24:18','2026-01-14 16:25:55'),
(10,'3810500334835',0,'2026-01-14 09:25:55','2026-01-14 16:32:39'),
(11,'1820500004103',0,'2026-01-14 09:32:39','2026-01-14 16:33:22'),
(12,'3430200354125',0,'2026-01-14 09:33:22','2026-01-14 16:35:58'),
(13,'1820500005169',0,'2026-01-14 09:35:58','2026-01-14 17:30:31'),
(14,'3810500334835',0,'2026-01-14 10:30:31','2026-01-14 19:06:27'),
(15,'1820500004103',0,'2026-01-14 12:06:27','2026-01-14 23:02:05'),
(16,'3810500334835',0,'2026-01-14 16:02:05','2026-01-14 23:02:10'),
(17,'1820500005169',0,'2026-01-14 16:02:10','2026-01-16 00:34:44'),
(18,'3810500334835',0,'2026-01-15 17:34:44','2026-01-16 00:41:38'),
(19,'1820500004103',0,'2026-01-15 17:41:38','2026-01-16 00:41:40'),
(20,'3810500334835',0,'2026-01-15 17:41:40','2026-01-16 00:41:52'),
(21,'1820500004103',0,'2026-01-15 17:41:52','2026-01-16 00:41:55'),
(22,'3810500334835',0,'2026-01-15 17:41:55','2026-01-29 11:11:02'),
(23,'1820500004103',0,'2026-01-29 04:11:02','2026-01-29 11:11:15'),
(24,'3810500334835',0,'2026-01-29 04:11:15','2026-01-29 14:48:07'),
(25,'1820500004103',0,'2026-01-29 07:48:07','2026-01-29 14:48:11'),
(26,'3810500334835',0,'2026-01-29 07:48:11','2026-01-29 14:55:47'),
(27,'3430200354125',0,'2026-01-29 07:55:47','2026-01-29 14:55:50'),
(28,'3810500334835',1,'2026-01-29 07:55:50',NULL);
/*!40000 ALTER TABLE `dh_exec_duty_logs` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `dh_file_refs`
--

DROP TABLE IF EXISTS `dh_file_refs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dh_file_refs`
--

LOCK TABLES `dh_file_refs` WRITE;
/*!40000 ALTER TABLE `dh_file_refs` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `dh_file_refs` VALUES
(1,1,'vehicle','dh_vehicle_bookings','1','vehicle_reservation_attachment','1829900159722','2026-01-20 13:14:14'),
(2,2,'repairs','dh_repair_requests','2',NULL,'1829900159722','2026-01-29 06:17:56'),
(3,3,'repairs','dh_repair_requests','2',NULL,'1829900159722','2026-01-29 06:17:56'),
(4,4,'repairs','dh_repair_requests','2',NULL,'1829900159722','2026-01-29 06:17:56'),
(5,5,'circulars','dh_circulars','2',NULL,'1810500062871','2026-02-01 17:06:14'),
(6,6,'circulars','dh_circulars','2',NULL,'1810500062871','2026-02-01 17:06:14'),
(7,7,'circulars','dh_circulars','2',NULL,'1810500062871','2026-02-01 17:06:14'),
(8,8,'circulars','dh_circulars','2',NULL,'1810500062871','2026-02-01 17:06:14'),
(9,9,'circulars','dh_circulars','3',NULL,'1810500062871','2026-02-01 17:18:01'),
(10,10,'circulars','dh_circulars','3',NULL,'1810500062871','2026-02-01 17:18:01'),
(11,11,'circulars','dh_circulars','3',NULL,'1810500062871','2026-02-01 17:18:01'),
(12,12,'circulars','dh_circulars','3',NULL,'1810500062871','2026-02-01 17:18:01');
/*!40000 ALTER TABLE `dh_file_refs` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `dh_files`
--

DROP TABLE IF EXISTS `dh_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dh_files`
--

LOCK TABLES `dh_files` WRITE;
/*!40000 ALTER TABLE `dh_files` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `dh_files` VALUES
(1,'PV1160633_20251216153717.pdf','assets/uploads/vehicle-bookings/vehicle_booking_20260120_201414_5be3e8fccb1e.pdf','application/pdf',176335,'663e209c95c5fd224fa34b102d12292eb7b98d2a97b7dec8f88120a0d4bef1ea','local',1,'1829900159722','2026-01-20 13:14:14',NULL),
(2,'ch01-updated-260127.pdf','storage/uploads/repairs/2026/01/7c01dea2ad5c8f7544ac2b89b6a1bc48.pdf','application/pdf',1298819,'d0c4fe1c735804a8eb693603a0d9e2d682a7d5a2351a3fc632954abc4c1fadb2','local',1,'1829900159722','2026-01-29 06:17:56',NULL),
(3,'DS and Algo-Algorithm Analysis 2025.pdf','storage/uploads/repairs/2026/01/c0c069febb310e07d430fc5de73da2a1.pdf','application/pdf',2010170,'c3d12f2935a53f1ce991393a36a07bf2bd6ce5e7cf9fd3af1556ded011d16209','local',1,'1829900159722','2026-01-29 06:17:56',NULL),
(4,'DS and Algo-Stack-Queue 2023.pdf','storage/uploads/repairs/2026/01/1e4865b0dc88228275be37e09235e8de.pdf','application/pdf',1777539,'435e1d2a08da437c8b55f09458bb5ef2dcce9d9c54bf4946c383a60215a4db81','local',1,'1829900159722','2026-01-29 06:17:56',NULL),
(5,'Screenshot 2569-01-31 at 23.12.55.png','storage/uploads/circulars/2026/02/b7a75efb47a71a616dc55f52c8a54004.png','image/png',1606052,'b5a51d15e07e8df721d6d889d1724db7e48e781610d08183a32c86dd128124bf','local',1,'1810500062871','2026-02-01 17:06:14',NULL),
(6,'Screenshot 2569-01-31 at 23.12.51.png','storage/uploads/circulars/2026/02/732a15ff6ecedfd27ebbba54a80c3906.png','image/png',483601,'8c6147b7583ede7ba6bcfe79834f77bc71bd2168c287d3022f34245fb82eb765','local',1,'1810500062871','2026-02-01 17:06:14',NULL),
(7,'Screenshot 2569-01-31 at 23.12.26.png','storage/uploads/circulars/2026/02/1e3f4790931b2405fd4e226e3702b657.png','image/png',489944,'bd573716d7a8daf98740f9e5642053ce13e46936154185833307b464ac37b324','local',1,'1810500062871','2026-02-01 17:06:14',NULL),
(8,'22-1.jpg','storage/uploads/circulars/2026/02/a65d11796f5bf49189661e2a90b199e7.jpg','image/jpeg',986419,'1376543a63ece3a6d67ea4c3435f89eaf969e7bba776f8da38f48f8ef109ea9d','local',1,'1810500062871','2026-02-01 17:06:14',NULL),
(9,'Screenshot 2569-01-31 at 23.12.55.png','storage/uploads/circulars/2026/02/aa0684647b3771a3ae3638b49c9a2694.png','image/png',1606052,'b5a51d15e07e8df721d6d889d1724db7e48e781610d08183a32c86dd128124bf','local',1,'1810500062871','2026-02-01 17:18:01',NULL),
(10,'Screenshot 2569-01-31 at 23.12.51.png','storage/uploads/circulars/2026/02/acffd5794e5a51f210c5cbf1ecaf7dc4.png','image/png',483601,'8c6147b7583ede7ba6bcfe79834f77bc71bd2168c287d3022f34245fb82eb765','local',1,'1810500062871','2026-02-01 17:18:01',NULL),
(11,'Screenshot 2569-01-31 at 23.12.26.png','storage/uploads/circulars/2026/02/7b24c892cbbbf3eb368e1d6f26bbeb30.png','image/png',489944,'bd573716d7a8daf98740f9e5642053ce13e46936154185833307b464ac37b324','local',1,'1810500062871','2026-02-01 17:18:01',NULL),
(12,'22-1.jpg','storage/uploads/circulars/2026/02/7c488c5ab583b8fcc29fbd536c9f5405.jpg','image/jpeg',986419,'1376543a63ece3a6d67ea4c3435f89eaf969e7bba776f8da38f48f8ef109ea9d','local',1,'1810500062871','2026-02-01 17:18:01',NULL);
/*!40000 ALTER TABLE `dh_files` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `dh_login_attempts`
--

DROP TABLE IF EXISTS `dh_login_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dh_login_attempts` (
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dh_login_attempts`
--

LOCK TABLES `dh_login_attempts` WRITE;
/*!40000 ALTER TABLE `dh_login_attempts` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `dh_login_attempts` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `dh_logs`
--

DROP TABLE IF EXISTS `dh_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dh_logs` (
  `logID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `pID` int(10) unsigned DEFAULT NULL,
  `actorPID` varchar(13) DEFAULT NULL,
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
  KEY `idx_logs_actor_pid` (`actorPID`),
  FULLTEXT KEY `ftx_message` (`logMessage`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dh_logs`
--

LOCK TABLES `dh_logs` WRITE;
/*!40000 ALTER TABLE `dh_logs` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `dh_logs` VALUES
(1,NULL,NULL,NULL,NULL,NULL,'ERROR','auth','LOGIN','FAIL','teacher',NULL,'Invalid credentials',NULL,'{\"pID\":\"1829900159722\"}','POST','/index.php',NULL,NULL,'::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','localhost','2026-01-25 08:06:10.752799');
/*!40000 ALTER TABLE `dh_logs` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `dh_memos`
--

DROP TABLE IF EXISTS `dh_memos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dh_memos` (
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
  KEY `fk_memo_created` (`createdByPID`),
  KEY `fk_memo_approved` (`approvedByPID`),
  CONSTRAINT `fk_memo_approved` FOREIGN KEY (`approvedByPID`) REFERENCES `teacher` (`pID`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_memo_created` FOREIGN KEY (`createdByPID`) REFERENCES `teacher` (`pID`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dh_memos`
--

LOCK TABLES `dh_memos` WRITE;
/*!40000 ALTER TABLE `dh_memos` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `dh_memos` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `dh_migrations`
--

DROP TABLE IF EXISTS `dh_migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dh_migrations` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `version` int(11) unsigned NOT NULL,
  `name` varchar(150) NOT NULL,
  `appliedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_migrations_version` (`version`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dh_migrations`
--

LOCK TABLES `dh_migrations` WRITE;
/*!40000 ALTER TABLE `dh_migrations` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `dh_migrations` VALUES
(1,1,'001_add_rbac_tables.sql','2026-01-29 05:39:53'),
(2,2,'002_add_audit_actor_pid.sql','2026-01-29 05:39:53'),
(3,3,'003_create_outgoing_letters.sql','2026-01-29 05:39:53'),
(4,4,'004_create_orders.sql','2026-01-29 05:39:53'),
(5,5,'005_create_repairs_memos.sql','2026-01-29 05:39:53'),
(6,6,'006_circular_announcements.sql','2026-01-29 05:39:53'),
(7,7,'007_create_workflow_core.sql','2026-01-29 05:39:53');
/*!40000 ALTER TABLE `dh_migrations` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `dh_order_inboxes`
--

DROP TABLE IF EXISTS `dh_order_inboxes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dh_order_inboxes` (
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
  CONSTRAINT `fk_orderinbox_delivered` FOREIGN KEY (`deliveredByPID`) REFERENCES `teacher` (`pID`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_orderinbox_order` FOREIGN KEY (`orderID`) REFERENCES `dh_orders` (`orderID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_orderinbox_user` FOREIGN KEY (`pID`) REFERENCES `teacher` (`pID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dh_order_inboxes`
--

LOCK TABLES `dh_order_inboxes` WRITE;
/*!40000 ALTER TABLE `dh_order_inboxes` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `dh_order_inboxes` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `dh_order_recipients`
--

DROP TABLE IF EXISTS `dh_order_recipients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dh_order_recipients` (
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dh_order_recipients`
--

LOCK TABLES `dh_order_recipients` WRITE;
/*!40000 ALTER TABLE `dh_order_recipients` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `dh_order_recipients` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `dh_order_routes`
--

DROP TABLE IF EXISTS `dh_order_routes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dh_order_routes` (
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
  KEY `fk_orderroute_from` (`fromPID`),
  CONSTRAINT `fk_orderroute_from` FOREIGN KEY (`fromPID`) REFERENCES `teacher` (`pID`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_orderroute_order` FOREIGN KEY (`orderID`) REFERENCES `dh_orders` (`orderID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_orderroute_to` FOREIGN KEY (`toPID`) REFERENCES `teacher` (`pID`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dh_order_routes`
--

LOCK TABLES `dh_order_routes` WRITE;
/*!40000 ALTER TABLE `dh_order_routes` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `dh_order_routes` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `dh_orders`
--

DROP TABLE IF EXISTS `dh_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dh_orders` (
  `orderID` bigint(20) NOT NULL AUTO_INCREMENT,
  `dh_year` int(4) NOT NULL,
  `orderNo` varchar(20) NOT NULL,
  `orderSeq` int(11) NOT NULL,
  `subject` varchar(300) NOT NULL,
  `detail` text DEFAULT NULL,
  `status` enum('ORDER_WAITING_ATTACHMENT','ORDER_COMPLETE','ORDER_SENT') NOT NULL DEFAULT 'OUTGOING_WAITING_ATTACHMENT',
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dh_orders`
--

LOCK TABLES `dh_orders` WRITE;
/*!40000 ALTER TABLE `dh_orders` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `dh_orders` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `dh_outgoing_letters`
--

DROP TABLE IF EXISTS `dh_outgoing_letters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dh_outgoing_letters` (
  `outgoingID` bigint(20) NOT NULL AUTO_INCREMENT,
  `dh_year` int(4) NOT NULL,
  `outgoingNo` varchar(50) NOT NULL,
  `outgoingSeq` int(11) NOT NULL,
  `subject` varchar(300) NOT NULL,
  `detail` text DEFAULT NULL,
  `status` enum('OUTGOING_WAITING_ATTACHMENT','OUTGOING_COMPLETE') NOT NULL DEFAULT 'OUTGOING_WAITING_ATTACHMENT',
  `createdByPID` varchar(13) NOT NULL,
  `updatedByPID` varchar(13) DEFAULT NULL,
  `deletedAt` datetime DEFAULT NULL,
  `createdAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `updatedAt` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
  PRIMARY KEY (`outgoingID`),
  UNIQUE KEY `uq_outgoing_no` (`dh_year`,`outgoingSeq`),
  KEY `idx_outgoing_year` (`dh_year`,`createdAt`),
  KEY `fk_outgoing_created` (`createdByPID`),
  KEY `fk_outgoing_updated` (`updatedByPID`),
  CONSTRAINT `fk_outgoing_created` FOREIGN KEY (`createdByPID`) REFERENCES `teacher` (`pID`) ON UPDATE CASCADE,
  CONSTRAINT `fk_outgoing_updated` FOREIGN KEY (`updatedByPID`) REFERENCES `teacher` (`pID`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dh_outgoing_letters`
--

LOCK TABLES `dh_outgoing_letters` WRITE;
/*!40000 ALTER TABLE `dh_outgoing_letters` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `dh_outgoing_letters` VALUES
(1,2568,'ศธ.01234/001',1,'ำะั้เ้','พะ้พะ้ะ้','OUTGOING_COMPLETE','1829900159722','1829900159722',NULL,'2026-01-29 06:49:35','2026-01-29 06:49:35');
/*!40000 ALTER TABLE `dh_outgoing_letters` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `dh_permissions`
--

DROP TABLE IF EXISTS `dh_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dh_permissions` (
  `permissionID` int(11) NOT NULL AUTO_INCREMENT,
  `permissionKey` varchar(100) NOT NULL,
  `permissionName` varchar(150) NOT NULL,
  `permissionDescription` text DEFAULT NULL,
  PRIMARY KEY (`permissionID`),
  UNIQUE KEY `uq_permissions_key` (`permissionKey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dh_permissions`
--

LOCK TABLES `dh_permissions` WRITE;
/*!40000 ALTER TABLE `dh_permissions` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `dh_permissions` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `dh_positions`
--

DROP TABLE IF EXISTS `dh_positions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dh_positions` (
  `positionID` int(11) NOT NULL AUTO_INCREMENT,
  `positionName` varchar(100) NOT NULL,
  `positionDescription` text DEFAULT NULL,
  PRIMARY KEY (`positionID`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dh_positions`
--

LOCK TABLES `dh_positions` WRITE;
/*!40000 ALTER TABLE `dh_positions` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `dh_positions` VALUES
(1,'ผู้อำนวยการโรงเรียน','ผู้บริหารสูงสุดของสถานศึกษา ดูแลและกำกับงานทั้งหมด'),
(2,'รองผู้อำนวยการกลุ่มบริหารงานวิชาการ','กำกับดูแลงานวิชาการและคุณภาพการเรียนการสอน วางแผนหลักสูตร ติดตามผลสัมฤทธิ์ และพัฒนาครู/การสอน'),
(3,'รองผู้อำนวยการกลุ่มบริหารงานบุคคลและงบประมาณ','ดูแลงานบุคคลและงบประมาณ บริหารอัตรากำลัง วางแผนการเงิน/พัสดุ ควบคุมการใช้จ่ายให้โปร่งใสและเป็นระบบ'),
(4,'รองผู้อำนวยการกลุ่มบริหารกิจการนักเรียน','รับผิดชอบงานกิจการนักเรียน วินัย ความปลอดภัย สวัสดิการ แนะแนว และกิจกรรม เพื่อให้นักเรียนมีคุณภาพและอยู่ร่วมกันได้ดี'),
(5,'ครู (หัวหน้ากลุ่มสาระ)','ดูแลการจัดการเรียนการสอนในกลุ่มสาระที่รับผิดชอบ วางแผนงานกลุ่มสาระ ประสานงานครู และติดตามผลการสอน'),
(6,'หัวหน้างาน','บริหารและควบคุมการทำงานของฝ่าย/งานที่ได้รับมอบหมาย จัดสรรงาน ติดตามความคืบหน้า และรายงานผลให้ผู้บริหาร'),
(7,'ครู','จัดการเรียนการสอนและดูแลนักเรียนตามหลักสูตร วัดผลประเมินผล และส่งเสริมพัฒนาการของผู้เรียนทั้งด้านวิชาการและพฤติกรรม'),
(8,'เจ้าหน้าที่','สนับสนุนงานเอกสาร ธุรการ และงานประสานงานภายใน ช่วยให้การดำเนินงานของโรงเรียนเป็นไปอย่างเรียบร้อย');
/*!40000 ALTER TABLE `dh_positions` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `dh_read_receipts`
--

DROP TABLE IF EXISTS `dh_read_receipts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dh_read_receipts` (
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dh_read_receipts`
--

LOCK TABLES `dh_read_receipts` WRITE;
/*!40000 ALTER TABLE `dh_read_receipts` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `dh_read_receipts` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `dh_repair_requests`
--

DROP TABLE IF EXISTS `dh_repair_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dh_repair_requests` (
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
  KEY `fk_repair_assigned` (`assignedToPID`),
  CONSTRAINT `fk_repair_assigned` FOREIGN KEY (`assignedToPID`) REFERENCES `teacher` (`pID`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_repair_requester` FOREIGN KEY (`requesterPID`) REFERENCES `teacher` (`pID`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dh_repair_requests`
--

LOCK TABLES `dh_repair_requests` WRITE;
/*!40000 ALTER TABLE `dh_repair_requests` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `dh_repair_requests` VALUES
(1,2568,'1829900159722','กฟหกฟหก','ฟหกฟหก','ฟกฟหกฟห','PENDING',NULL,NULL,'2026-01-29 13:17:34','2026-01-29 05:53:52','2026-01-29 06:17:34'),
(2,2568,'1829900159722','หกดหกดหกดกห','หกดกหดหกดกหด','หกดหกดหกดหกด','PENDING',NULL,NULL,NULL,'2026-01-29 06:17:56','0000-00-00 00:00:00');
/*!40000 ALTER TABLE `dh_repair_requests` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `dh_role_permissions`
--

DROP TABLE IF EXISTS `dh_role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dh_role_permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `roleID` int(11) NOT NULL,
  `permissionID` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_role_perm` (`roleID`,`permissionID`),
  KEY `idx_role_perm_role` (`roleID`),
  KEY `idx_role_perm_perm` (`permissionID`),
  CONSTRAINT `fk_role_perm_perm` FOREIGN KEY (`permissionID`) REFERENCES `dh_permissions` (`permissionID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_role_perm_role` FOREIGN KEY (`roleID`) REFERENCES `dh_roles` (`roleID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dh_role_permissions`
--

LOCK TABLES `dh_role_permissions` WRITE;
/*!40000 ALTER TABLE `dh_role_permissions` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `dh_role_permissions` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `dh_roles`
--

DROP TABLE IF EXISTS `dh_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dh_roles` (
  `roleID` int(11) NOT NULL AUTO_INCREMENT,
  `roleName` varchar(100) NOT NULL,
  `roleDescription` text NOT NULL,
  PRIMARY KEY (`roleID`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dh_roles`
--

LOCK TABLES `dh_roles` WRITE;
/*!40000 ALTER TABLE `dh_roles` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `dh_roles` VALUES
(1,'ผู้ดูแลระบบ','มีสิทธิ์สูงสุดในการเข้าถึงและจัดการระบบสำนักงานอิเล็กทรอนิกส์ทั้งหมด\nสามารถเพิ่ม แก้ไข หรือลบบัญชีผู้ใช้งาน กำหนดสิทธิ์การเข้าถึงเมนูต่าง ๆ\nตรวจสอบและบริหารจัดการข้อมูลภายในระบบ เช่น หนังสือเวียน คำสั่งราชการ การจองยานพาหนะ\nรวมถึงดูแลการตั้งค่าระบบทั่วไป เช่น การสำรองข้อมูล การตั้งค่าไฟล์แนบ และการจัดการลายเซ็นดิจิทัลของผู้บริหาร'),
(2,'เจ้าหน้าที่สารบัญ','รับผิดชอบการรับ–ส่งหนังสือราชการภายในและภายนอกหน่วยงาน\r\nบันทึก ลงทะเบียน และจัดเก็บเอกสารในระบบสำนักงานอิเล็กทรอนิกส์ให้เป็นระเบียบ\r\nตรวจสอบสถานะหนังสือเวียน คำสั่งราชการ และบันทึกข้อความ\r\nรวมถึงติดตามว่าผู้รับเปิดอ่านเอกสารแล้วหรือไม่\r\nสามารถเผยแพร่หนังสือเวียนที่สำคัญให้แสดงในหน้า “ข่าวประชาสัมพันธ์” ของระบบได้'),
(3,'เจ้าหน้าที่ยานพาหนะ ','รับผิดชอบการบริหารจัดการและดูแลการจองยานพาหนะของหน่วยงาน\r\nตรวจสอบคำขอใช้รถ บันทึกข้อมูลการจอง อนุมัติหรือปฏิเสธตามความเหมาะสม\r\nดูแลตารางการใช้รถ สถานะการใช้งาน และบันทึกข้อมูลผู้ขับขี่\r\nรวมถึงจัดเก็บเอกสารแนบ เช่น ใบขออนุญาตเดินทาง ใบสั่งงาน หรือเอกสารประกอบการเดินทาง\r\nเพื่อให้การใช้ยานพาหนะเป็นไปตามระเบียบและตรวจสอบย้อนหลังได้'),
(4,'เจ้าหน้าที่วันลา','รับผิดชอบการจัดการข้อมูลการลาของบุคลากรในหน่วยงาน ตรวจสอบคำขอลาแต่ละประเภท เช่น ลาป่วย ลากิจ ลาพักผ่อน ตรวจสอบความถูกต้องของข้อมูล วันลา และเอกสารแนบ บันทึกสถานะการอนุมัติหรือไม่อนุมัติให้เป็นปัจจุบันในระบบ พร้อมทั้งจัดทำรายงานสรุปสถิติการลาเพื่อเสนอผู้บริหาร และช่วยตรวจสอบสิทธิ์การลาประจำปีของแต่ละบุคลากร'),
(5,'เจ้าหน้าที่สถานที่','รับผิดชอบการดูแลและบริหารจัดการการจองห้องประชุม อาคาร และสถานที่ต่าง ๆ ภายในหน่วยงาน\r\nตรวจสอบคำขอจองห้อง บันทึกข้อมูลการจอง อนุมัติหรือปฏิเสธตามความเหมาะสม\r\nจัดทำตารางการใช้สถานที่ให้ไม่ซ้ำซ้อน พร้อมอัปเดตสถานะให้ผู้ใช้สามารถตรวจสอบได้แบบเรียลไทม์\r\nรวมถึงบันทึกหมายเหตุหรือเอกสารแนบที่เกี่ยวข้อง เพื่อให้การใช้สถานที่มีประสิทธิภาพและตรวจสอบย้อนหลังได้อย่างโปร่งใส'),
(6,'บุคลากรทั่วไป','บุคลากรที่ปฏิบัติงานทั่วไปภายในหน่วยงาน สนับสนุนการดำเนินงานประจำวัน ประสานงานภายใน และปฏิบัติงานตามที่ได้รับมอบหมายจากผู้บังคับบัญชา');
/*!40000 ALTER TABLE `dh_roles` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `dh_room_bookings`
--

DROP TABLE IF EXISTS `dh_room_bookings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dh_room_bookings`
--

LOCK TABLES `dh_room_bookings` WRITE;
/*!40000 ALTER TABLE `dh_room_bookings` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `dh_room_bookings` VALUES
(1,2568,'1829900159722',2,'2026-01-16','2026-01-17','00:52:00','17:53:00',243,'PENDING','','23423423','234234','234234','นางสาวทิพยรัตน์ บุญมณี',NULL,NULL,'2026-01-16 01:09:59','2026-01-15 17:47:27','2026-01-15 18:09:59'),
(2,2568,'1829900159722',8,'2026-01-30','2026-02-01','01:04:00','02:04:00',2,'PENDING','','23123123','12312312','3123123','นางสาวทิพยรัตน์ บุญมณี',NULL,NULL,'2026-01-16 01:05:09','2026-01-15 18:04:48','2026-01-15 18:05:09'),
(3,2568,'1829900159722',5,'2026-01-01','2026-01-02','13:15:00','15:14:00',12,'PENDING','','12','12','12','นางสาวทิพยรัตน์ บุญมณี',NULL,NULL,'2026-01-16 13:15:02','2026-01-16 06:14:57','2026-01-16 06:15:02'),
(4,2568,'1819900163142',4,'2026-01-01','2026-01-02','13:22:00','15:22:00',12,'REJECTED','asdasdasdsd','12','12','12','นายบพิธ มังคะลา','1829900159722','2026-01-19 17:35:23',NULL,'2026-01-16 06:22:55','2026-01-19 10:35:23'),
(5,2568,'1819900163142',8,'2026-01-31','2026-02-01','19:39:00','21:30:00',12,'APPROVED',NULL,'2','3','77','นายบพิธ มังคะลา','1829900159722','2026-01-20 18:15:25',NULL,'2026-01-16 06:29:19','2026-01-20 11:15:25'),
(6,2568,'1829900159722',7,'2026-01-16','2026-01-17','13:55:00','14:55:00',12,'APPROVED',NULL,'12121','12121212','465','นางสาวทิพยรัตน์ บุญมณี','1829900159722','2026-01-17 00:04:30','2026-01-19 12:53:34','2026-01-16 06:55:43','2026-01-19 05:53:34');
/*!40000 ALTER TABLE `dh_room_bookings` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `dh_rooms`
--

DROP TABLE IF EXISTS `dh_rooms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dh_rooms`
--

LOCK TABLES `dh_rooms` WRITE;
/*!40000 ALTER TABLE `dh_rooms` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `dh_rooms` VALUES
(1,'ห้องประชุมก้ามงิ้นเชี้ยว','พร้อมใช้งาน','','2026-01-16 11:56:39','2026-01-15 16:55:26',NULL),
(2,'ห้องประชุมเพชรดีบุก','พร้อมใช้งาน','','2026-01-15 16:55:26','2026-01-15 16:55:26',NULL),
(3,'ห้องสื่อมัลติมีเดีย','พร้อมใช้งาน','','2026-01-15 16:55:26','2026-01-15 16:55:26',NULL),
(4,'หอประชุม(หลังเก่า)','พร้อมใช้งาน','','2026-01-15 16:55:26','2026-01-15 16:55:26',NULL),
(5,'หอประชุมชั้นบนอาคารอเนกประสงค์','พร้อมใช้งาน','','2026-01-16 12:31:49','2026-01-16 12:31:49',NULL),
(6,'โรงอาหาร','พร้อมใช้งาน','','2026-01-15 16:55:26','2026-01-15 16:55:26',NULL),
(7,'ใต้ถุนอาคาร 5','พร้อมใช้งาน','','2026-01-19 05:02:46','2026-01-19 05:02:46',NULL),
(8,'ห้องประชุมเพชรประดู่','พร้อมใช้งาน','','2026-01-15 16:55:26','2026-01-15 16:55:26',NULL),
(9,'ห้องประชุมเกียรติยศ','พร้อมใช้งาน','','2026-01-15 16:55:26','2026-01-15 16:55:26',NULL),
(12,'กหดหกดหก','พร้อมใช้งาน','หกดกหดกหดกห','2026-01-16 12:42:37','2026-01-16 12:42:37','2026-01-16 19:42:37'),
(13,'ดเ้ด้ดเ','พร้อมใช้งาน','ดเ้ดเ้ดเ้ดเ','2026-01-16 12:44:52','2026-01-16 12:44:52','2026-01-16 19:44:52');
/*!40000 ALTER TABLE `dh_rooms` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `dh_sequences`
--

DROP TABLE IF EXISTS `dh_sequences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dh_sequences` (
  `seqKey` varchar(50) NOT NULL,
  `currentValue` bigint(20) unsigned NOT NULL DEFAULT 0,
  `updatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`seqKey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dh_sequences`
--

LOCK TABLES `dh_sequences` WRITE;
/*!40000 ALTER TABLE `dh_sequences` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `dh_sequences` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `dh_vehicle_bookings`
--

DROP TABLE IF EXISTS `dh_vehicle_bookings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dh_vehicle_bookings`
--

LOCK TABLES `dh_vehicle_bookings` WRITE;
/*!40000 ALTER TABLE `dh_vehicle_bookings` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `dh_vehicle_bookings` VALUES
(1,2568,'1829900159722',NULL,NULL,NULL,NULL,'2026-01-20 20:14:00','2026-01-21 22:14:00','APPROVED','','1829900159722','2026-01-25 15:28:00',NULL,'2026-01-20 13:14:14','2026-01-25 08:28:00'),
(2,2568,'1829900159722',NULL,NULL,NULL,NULL,'2026-01-25 15:49:00','2026-01-26 16:50:00','PENDING',NULL,NULL,NULL,NULL,'2026-01-25 08:49:46','0000-00-00 00:00:00');
/*!40000 ALTER TABLE `dh_vehicle_bookings` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `dh_vehicles`
--

DROP TABLE IF EXISTS `dh_vehicles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dh_vehicles`
--

LOCK TABLES `dh_vehicles` WRITE;
/*!40000 ALTER TABLE `dh_vehicles` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `dh_vehicles` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `position`
--

DROP TABLE IF EXISTS `position`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `position` (
  `oID` int(2) NOT NULL,
  `oName` text NOT NULL,
  PRIMARY KEY (`oID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `position`
--

LOCK TABLES `position` WRITE;
/*!40000 ALTER TABLE `position` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `position` VALUES
(1,'เจ้าหน้าที่'),
(2,'ครูอัตรจ้าง'),
(3,'พนักงานราชการ'),
(4,'ครูผู้ช่วย'),
(5,'ครู/ข้าราชการ'),
(6,'ผู้บริหาร');
/*!40000 ALTER TABLE `position` ENABLE KEYS */;
UNLOCK TABLES;
commit;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-02-02  2:16:16

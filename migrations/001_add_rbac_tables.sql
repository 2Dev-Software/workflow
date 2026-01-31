CREATE TABLE IF NOT EXISTS `dh_user_positions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `pID` varchar(13) NOT NULL,
  `positionID` int(11) NOT NULL,
  `createdAt` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_position` (`pID`,`positionID`),
  KEY `idx_user_position_pid` (`pID`),
  KEY `idx_user_position_pos` (`positionID`),
  CONSTRAINT `fk_user_positions_teacher` FOREIGN KEY (`pID`) REFERENCES `teacher` (`pID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_user_positions_position` FOREIGN KEY (`positionID`) REFERENCES `dh_positions` (`positionID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
-- roleID ใช้จากตาราง teacher โดยตรง จึงไม่ต้องมีตาราง dh_user_roles

START TRANSACTION;

ALTER TABLE `dh_memos`
  ADD COLUMN `flowMode` enum('CHAIN','DIRECT') NOT NULL DEFAULT 'CHAIN' AFTER `toPID`,
  ADD COLUMN `flowStage` enum('OWNER','HEAD','DEPUTY','DIRECTOR') NOT NULL DEFAULT 'OWNER' AFTER `flowMode`,
  ADD COLUMN `headPID` varchar(13) DEFAULT NULL AFTER `flowStage`,
  ADD COLUMN `deputyPID` varchar(13) DEFAULT NULL AFTER `headPID`,
  ADD COLUMN `directorPID` varchar(13) DEFAULT NULL AFTER `deputyPID`;

ALTER TABLE `dh_memos`
  ADD KEY `idx_memo_flow_stage` (`flowStage`,`status`),
  ADD KEY `idx_memo_head` (`headPID`),
  ADD KEY `idx_memo_deputy` (`deputyPID`),
  ADD KEY `idx_memo_director` (`directorPID`);

ALTER TABLE `dh_memos`
  ADD CONSTRAINT `fk_memo_head` FOREIGN KEY (`headPID`) REFERENCES `teacher` (`pID`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_memo_deputy` FOREIGN KEY (`deputyPID`) REFERENCES `teacher` (`pID`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_memo_director` FOREIGN KEY (`directorPID`) REFERENCES `teacher` (`pID`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `dh_memo_routes`
  MODIFY `action` enum(
    'CREATE','UPDATE','SUBMIT','OPEN','RETURN','RESUBMIT',
    'APPROVE_UNSIGNED','SIGN','REJECT','CANCEL','ARCHIVE',
    'RECALL','FORWARD','DIRECTOR_APPROVE','DIRECTOR_REJECT'
  ) NOT NULL;

COMMIT;

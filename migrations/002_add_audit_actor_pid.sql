ALTER TABLE `dh_logs`
  ADD COLUMN `actorPID` varchar(13) DEFAULT NULL AFTER `pID`,
  ADD KEY `idx_logs_actor_pid` (`actorPID`);

ALTER TABLE `dh_outgoing_letters`
  ADD COLUMN IF NOT EXISTS `destinationName` varchar(255) DEFAULT NULL AFTER `detail`;

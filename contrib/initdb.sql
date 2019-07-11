CREATE TABLE `games` (
  `appId` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  PRIMARY KEY (`appId`)
) ENGINE=InnoDB DEFAULT;

CREATE TABLE `reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `appId` int(11) NOT NULL,
  `timestamp` int(11) NOT NULL,
  `rating` varchar(16) DEFAULT NULL,
  `notes` text,
  `os` varchar(255) DEFAULT NULL,
  `gpuDriver` varchar(255) DEFAULT NULL,
  `specs` varchar(255) DEFAULT NULL,
  `protonVersion` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `appId` (`appId`),
  CONSTRAINT `game` FOREIGN KEY (`appId`) REFERENCES `games` (`appId`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT;	

CREATE TABLE `meta` (
  `key` varchar(32) NOT NULL,
  `value` varchar(255) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT;


INSERT INTO `meta` (`key`, `value`) VALUES('last_update', '0');

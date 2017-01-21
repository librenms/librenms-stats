CREATE TABLE `data` (
  `data_id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` varchar(64) NOT NULL,
  `run_id` int(11) NOT NULL,
  `total` int(11) NOT NULL,
  `group` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL,
  PRIMARY KEY (`data_id`),
  KEY `data_uuid_index` (`uuid`),
  KEY `data_run_id_index` (`run_id`),
  KEY `data_group_index` (`group`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

CREATE TABLE `devinfo` (
  `uuid` char(64) NOT NULL,
  `count` int(11) NOT NULL,
  `os` char(64) NOT NULL,
  `sysDescr` varchar(255),
  `sysObjectID` varchar(255),
  KEY `devices_uuid_index` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `hosts` (
  `hosts_id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` char(64) NOT NULL,
  `first_added` datetime NOT NULL,
  `last_updated` datetime NOT NULL,
  PRIMARY KEY (`hosts_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

CREATE TABLE `run` (
  `run_id` int(11) NOT NULL AUTO_INCREMENT,
  `hosts_id` int(11) NOT NULL,
  `datetime` datetime NOT NULL,
  PRIMARY KEY (`run_id`),
  KEY `run_hosts_id_index` (`hosts_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

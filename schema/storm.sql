CREATE TABLE `hive_audits` (
  `audit_id` int(11) NOT NULL,
  `object_class` varchar(255) NOT NULL DEFAULT '',
  `object_id` char(32) NOT NULL DEFAULT '',
  `user_id` int(11) NOT NULL,
  `ref_id` varchar(100) DEFAULT NULL,
  `type` int(11) NOT NULL,
  `message` varchar(255) NOT NULL,
  `datetime` datetime NOT NULL,
  `ip_address` varchar(16) NOT NULL DEFAULT '',
  PRIMARY KEY (`audit_id`),
  KEY `object_idx` (`object_class`,`object_id`),
  KEY `other_idx` (`user_id`,`ref_id`,`type`,`datetime`,`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `hive_audit_values` (
  `audit_value_id` int(11) NOT NULL,
  `audit_id` int(11) NOT NULL,
  `key` varchar(255) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`audit_value_id`),
  KEY `idx` (`audit_id`,`key`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

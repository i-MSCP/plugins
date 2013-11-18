DROP TABLE IF EXISTS `#REPLACE_WITH_YOUR_DB_NAME#`.`pop3fetcher_accounts`;
CREATE TABLE  `#REPLACE_WITH_YOUR_DB_NAME#`.`pop3fetcher_accounts` (
  `pop3fetcher_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pop3fetcher_email` varchar(128) NOT NULL,
  `pop3fetcher_username` varchar(128) NOT NULL,
  `pop3fetcher_password` varchar(128) NOT NULL,
  `pop3fetcher_serveraddress` varchar(128) NOT NULL,
  `pop3fetcher_serverport` varchar(128) NOT NULL,
  `pop3fetcher_ssl` varchar(10) DEFAULT '0',
  `pop3fetcher_leaveacopyonserver` tinyint(1) DEFAULT '0',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `last_check` int(10) unsigned NOT NULL DEFAULT '0',
  `last_uidl` varchar(70) DEFAULT NULL,
  `update_lock` tinyint(1) NOT NULL DEFAULT '0',
  `pop3fetcher_provider` varchar(128) DEFAULT NULL,
  `default_folder` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`pop3fetcher_id`),
  KEY `user_id_fk_accounts` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=23 DEFAULT CHARSET=utf8;
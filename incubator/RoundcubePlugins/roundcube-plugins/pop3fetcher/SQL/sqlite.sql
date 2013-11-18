DROP TABLE IF EXISTS 'pop3fetcher_accounts';

CREATE TABLE  'pop3fetcher_accounts' (
	  `pop3fetcher_id` INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
	  `pop3fetcher_email` VARCHAR(128) NOT NULL,
	  `pop3fetcher_username` VARCHAR(128) NOT NULL,
	  `pop3fetcher_password` VARCHAR(128) NOT NULL,
	  `pop3fetcher_serveraddress` VARCHAR(128) NOT NULL,
	  `pop3fetcher_serverport` VARCHAR(128) NOT NULL,
	  `pop3fetcher_ssl` VARCHAR(10) DEFAULT '0',
	  `pop3fetcher_leaveacopyonserver` TINYINT(1) DEFAULT '0',
	  `user_id` INT(10) NOT NULL DEFAULT '0',
	  `last_check` INT(10) NOT NULL DEFAULT '0',
	  `last_uidl` VARCHAR(70) DEFAULT NULL,
	  `update_lock` TINYINT(1) NOT NULL DEFAULT '0',
	  `pop3fetcher_provider` VARCHAR(128) DEFAULT NULL,
	  `default_folder` VARCHAR(128) DEFAULT NULL
);

CREATE INDEX user_id_fk_accounts ON pop3fetcher_accounts (user_id);

<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2013-2016 Rene Schuster <mail@reneschuster.de>
 * Copyright (C) 2013-2016 Sascha Bay <info@space2place.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

$roundcubeDbName = iMSCP_Registry::get('config')->DATABASE_NAME . '_roundcube';

return array(
	'up' => "
		CREATE TABLE IF NOT EXISTS " . $roundcubeDbName . ".`pop3fetcher_accounts` (
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
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
	",
	'down' => "
		DROP TABLE IF EXISTS " . $roundcubeDbName . ".`pop3fetcher_accounts`;
	"
);

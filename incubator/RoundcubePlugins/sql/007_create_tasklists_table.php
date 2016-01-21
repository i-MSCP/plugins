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
		CREATE TABLE IF NOT EXISTS " . $roundcubeDbName . ".`tasklists` (
			`tasklist_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`user_id` int(10) unsigned NOT NULL,
			`name` varchar(255) NOT NULL,
			`color` varchar(8) NOT NULL,
			`showalarms` tinyint(2) unsigned NOT NULL DEFAULT '0',
			PRIMARY KEY (`tasklist_id`),
			KEY `user_id` (`user_id`),
			CONSTRAINT `fk_tasklist_user_id` FOREIGN KEY (`user_id`)
				REFERENCES `users`(`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
		) /*!40000 ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci */;
	",
	'down' => "
		DROP TABLE IF EXISTS " . $roundcubeDbName . ".`tasklists`;
	"
);

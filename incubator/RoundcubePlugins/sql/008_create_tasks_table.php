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
		CREATE TABLE IF NOT EXISTS " . $roundcubeDbName . ".`tasks` (
			`task_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`tasklist_id` int(10) unsigned NOT NULL,
			`parent_id` int(10) unsigned DEFAULT NULL,
			`uid` varchar(255) NOT NULL,
			`created` datetime NOT NULL,
			`changed` datetime NOT NULL,
			`del` tinyint(1) unsigned NOT NULL DEFAULT '0',
			`title` varchar(255) NOT NULL,
			`description` text,
			`tags` text,
			`date` varchar(10) DEFAULT NULL,
			`time` varchar(5) DEFAULT NULL,
			`startdate` varchar(10) DEFAULT NULL,
			`starttime` varchar(5) DEFAULT NULL,
			`flagged` tinyint(4) NOT NULL DEFAULT '0',
			`complete` float NOT NULL DEFAULT '0',
			`alarms` varchar(255) DEFAULT NULL,
			`recurrence` varchar(255) DEFAULT NULL,
			`organizer` varchar(255) DEFAULT NULL,
			`attendees` text,
			`notify` datetime DEFAULT NULL,
			PRIMARY KEY (`task_id`),
			KEY `tasklisting` (`tasklist_id`,`del`,`date`),
			KEY `uid` (`uid`),
			CONSTRAINT `fk_tasks_tasklist_id` FOREIGN KEY (`tasklist_id`)
				REFERENCES `tasklists`(`tasklist_id`) ON DELETE CASCADE ON UPDATE CASCADE
		) /*!40000 ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci */;
	",
	'down' => "
		DROP TABLE IF EXISTS " . $roundcubeDbName . ".`tasks`;
	"
);

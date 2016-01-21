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
		CREATE TABLE IF NOT EXISTS " . $roundcubeDbName . ".`events` (
			`event_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
			`calendar_id` int(11) UNSIGNED NOT NULL DEFAULT '0',
			`recurrence_id` int(11) UNSIGNED NOT NULL DEFAULT '0',
			`uid` varchar(255) NOT NULL DEFAULT '',
			`created` datetime NOT NULL DEFAULT '1000-01-01 00:00:00',
			`changed` datetime NOT NULL DEFAULT '1000-01-01 00:00:00',
			`sequence` int(1) UNSIGNED NOT NULL DEFAULT '0',
			`start` datetime NOT NULL DEFAULT '1000-01-01 00:00:00',
			`end` datetime NOT NULL DEFAULT '1000-01-01 00:00:00',
			`recurrence` varchar(255) DEFAULT NULL,
			`title` varchar(255) NOT NULL,
			`description` text NOT NULL,
			`location` varchar(255) NOT NULL DEFAULT '',
			`categories` varchar(255) NOT NULL DEFAULT '',
			`all_day` tinyint(1) NOT NULL DEFAULT '0',
			`free_busy` tinyint(1) NOT NULL DEFAULT '0',
			`priority` tinyint(1) NOT NULL DEFAULT '0',
			`sensitivity` tinyint(1) NOT NULL DEFAULT '0',
			`alarms` varchar(255) DEFAULT NULL,
			`attendees` text DEFAULT NULL,
			`notifyat` datetime DEFAULT NULL,
			PRIMARY KEY(`event_id`),
			INDEX `uid_idx` (`uid`),
			INDEX `recurrence_idx` (`recurrence_id`),
			INDEX `calendar_notify_idx` (`calendar_id`,`notifyat`),
			CONSTRAINT `fk_events_calendar_id` FOREIGN KEY (`calendar_id`)
				REFERENCES `calendars`(`calendar_id`) ON DELETE CASCADE ON UPDATE CASCADE
		) /*!40000 ENGINE=INNODB */ /*!40101 CHARACTER SET utf8 COLLATE utf8_general_ci */;
	",
	'down' => "
		DROP TABLE IF EXISTS " . $roundcubeDbName . ".`events`;
	"
);

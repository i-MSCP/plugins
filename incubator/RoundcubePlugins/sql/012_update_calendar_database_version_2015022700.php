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
		ALTER TABLE " . $roundcubeDbName . ".`events` ADD `instance` varchar(16) NOT NULL DEFAULT '' AFTER `uid`;
		ALTER TABLE " . $roundcubeDbName . ".`events` ADD `isexception` tinyint(1) NOT NULL DEFAULT '0' AFTER `instance`;

		UPDATE " . $roundcubeDbName . ".`events` SET `instance` = DATE_FORMAT(`start`, '%Y%m%d')
			WHERE `recurrence_id` != 0 AND `instance` = '' AND `all_day` = 1;

		UPDATE " . $roundcubeDbName . ".`events` SET `instance` = DATE_FORMAT(`start`, '%Y%m%dT%k%i%s')
			WHERE `recurrence_id` != 0 AND `instance` = '' AND `all_day` = 0;

		ALTER TABLE " . $roundcubeDbName . ".`events` CHANGE `alarms` `alarms` TEXT NULL DEFAULT NULL;

		REPLACE INTO " . $roundcubeDbName . ".`system` (`name`, `value`) VALUES ('calendar-database-version', '2015022700');
	"
);

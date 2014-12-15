<?php
/**
 * i-MSCP CronJobs plugin
 * Copyright (C) 2014 Laurent Declercq <l.declercq@nuxwin.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 */

return array(
	'up' => "
		CREATE TABLE IF NOT EXISTS cron_permissions (
			cron_permission_id INT(10) unsigned AUTO_INCREMENT NOT NULL,
			cron_permission_admin_id INT(10) unsigned NOT NULL,
			cron_permission_type ENUM('url', 'jailed', 'full') NOT NULL DEFAULT 'url',
			cron_permission_max INT(10) NOT NULL default '0',
			cron_permission_frequency INT(10) NOT NULL DEFAULT '5',
			cron_permission_status VARCHAR(255) NOT NULL,
			PRIMARY KEY cron_permission_id (cron_permission_id),
			UNIQUE KEY cron_permission_admin_id (cron_permission_admin_id),
			KEY cron_permission_status (cron_permission_status)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;
	",
	'down' => '
		DROP TABLE IF EXISTS cron_permissions
	'
);

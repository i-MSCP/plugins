<?php
/**
 * i-MSCP InstantSSH plugin
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
	'up' => '
		CREATE TABLE IF NOT EXISTS instant_ssh_permissions (
			ssh_permission_id int(10) unsigned AUTO_INCREMENT NOT NULL,
			ssh_permission_admin_id int(10) unsigned NOT NULL,
			ssh_permission_max_keys int(10) NOT NULL,
			ssh_permission_key_options tinyint(1) NOT NULL,
			PRIMARY KEY ssh_permission_id (ssh_permission_id),
			UNIQUE KEY ssh_permission_admin_id (ssh_permission_admin_id),
			CONSTRAINT ssh_permission_admin_id FOREIGN KEY (ssh_permission_admin_id)
  				REFERENCES admin (admin_id) ON DELETE CASCADE
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;
	',
	'down' => '
		DROP TABLE IF EXISTS instant_ssh_permissions
	'
);

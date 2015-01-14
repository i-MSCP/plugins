<?php
/**
 * i-MSCP InstantSSH plugin
 * Copyright (C) 2014-2015 Laurent Declercq <l.declercq@nuxwin.com>
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

# Rename instant_ssh_permissions.ssh_permission_max_keys column to instant_ssh_permissions.ssh_permission_max_users

return array(
	'up' => '
		ALTER TABLE
			instant_ssh_permissions
		CHANGE
			ssh_permission_max_keys ssh_permission_max_users INT(10) NOT NULL
	',
	'down' => '
		ALTER TABLE
			instant_ssh_permissions
		CHANGE
			ssh_permission_max_users ssh_permission_max_keys INT(10) NOT NULL
	'
);

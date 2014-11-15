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

# Update ssh_permission_id constraint (ON DELETE SET NULL to ON DELETE CASCADE)

return array(
	'up' => '
		ALTER TABLE instant_ssh_keys DROP FOREIGN KEY ssh_permission_id, DROP INDEX ssh_permission_id;

		ALTER TABLE instant_ssh_keys ADD CONSTRAINT ssh_permission_id FOREIGN KEY (ssh_permission_id)
			REFERENCES instant_ssh_permissions (ssh_permission_id) ON DELETE CASCADE
	',
	'down' => '
		ALTER TABLE instant_ssh_keys DROP FOREIGN KEY ssh_permission_id, DROP INDEX ssh_permission_id;

		ALTER TABLE instant_ssh_keys ADD CONSTRAINT ssh_permission_id FOREIGN KEY (ssh_permission_id)
			REFERENCES instant_ssh_permissions (ssh_permission_id) ON DELETE SET NULL
	'
);

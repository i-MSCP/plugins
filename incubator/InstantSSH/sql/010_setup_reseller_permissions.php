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

// Setup SSH permissions for resellers

return array(
	'up' => "
		INSERT INTO instant_ssh_permissions (
			ssh_permission_admin_id, ssh_permission_max_users, ssh_permission_auth_options, ssh_permission_jailed_shell,
			ssh_permission_status
		) SELECT
			t2.created_by, '0', '1', '0', 'ok'
		FROM
			instant_ssh_permissions AS t1
		INNER JOIN
			admin AS t2 ON (t2.admin_id = t1.ssh_permission_admin_id)
		WHERE
			t2.admin_type = 'user'
		GROUP BY
			t2.created_by
	",
	'down' => "
		DELETE instant_ssh_permissions FROM
			instant_ssh_permissions
		INNER JOIN
		 	admin ON(admin_id = ssh_permission_admin_id)
		WHERE
			admin_type = 'reseller'
	"
);

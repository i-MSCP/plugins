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

// Create instant_ssh_users table
// Move data from the instant_ssh_keys table to the instant_ssh_users table

return array(
	'up' => "
		CREATE TABLE IF NOT EXISTS instant_ssh_users (
			ssh_user_id int(10) unsigned AUTO_INCREMENT NOT NULL,
			ssh_user_permission_id int(10) unsigned DEFAULT NULL,
			ssh_user_admin_id int(10) unsigned NOT NULL,
			ssh_user_name varchar(255) COLLATE utf8_unicode_ci NOT NULL,
			ssh_user_password varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
			ssh_user_key text NULL DEFAULT NULL,
			ssh_user_key_fingerprint varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
			ssh_user_auth_options text DEFAULT NULL,
			ssh_user_status varchar(255) NOT NULL,
			PRIMARY KEY ssh_user_id (ssh_user_id),
			KEY ssh_user_admin_id (ssh_user_admin_id),
			UNIQUE KEY ssh_user_name (ssh_user_name),
			UNIQUE KEY ssh_user_key_fingerprint (ssh_user_admin_id, ssh_user_key_fingerprint),
			KEY ssh_user_status (ssh_user_status),
			CONSTRAINT ssh_user_permission_id FOREIGN KEY (ssh_user_permission_id)
  				REFERENCES instant_ssh_permissions (ssh_permission_id) ON DELETE SET NULL
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;

		INSERT IGNORE instant_ssh_users SELECT
			ssh_key_id, ssh_permission_id, ssh_key_admin_id, CONCAT('imscp_', ssh_key_id), null, ssh_key,
			ssh_key_fingerprint, ssh_auth_options, ssh_key_status
		FROM
			instant_ssh_keys;

		DROP TABLE IF EXISTS instant_ssh_keys
	",
	'down' => "
		CREATE TABLE IF NOT EXISTS instant_ssh_keys (
			ssh_key_id int(10) unsigned AUTO_INCREMENT NOT NULL,
			ssh_permission_id int(10) unsigned DEFAULT NULL,
			ssh_key_admin_id int(10) unsigned NOT NULL,
			ssh_key_name varchar(255) COLLATE utf8_unicode_ci NOT NULL,
			ssh_key text NOT NULL,
			ssh_key_fingerprint varchar(255) COLLATE utf8_unicode_ci NOT NULL,
			ssh_auth_options text NOT NULL,
			ssh_key_status varchar(255) NOT NULL,
			PRIMARY KEY ssh_key_id (ssh_key_id),
			KEY ssh_key_admin_id (ssh_key_admin_id),
			UNIQUE KEY ssh_key_name (ssh_key_admin_id, ssh_key_name),
			UNIQUE KEY ssh_key_fingerprint (ssh_key_admin_id, ssh_key_fingerprint),
			KEY ssh_key_status (ssh_key_status),
			CONSTRAINT ssh_permission_id FOREIGN KEY (ssh_permission_id)
  				REFERENCES instant_ssh_permissions (ssh_permission_id) ON DELETE SET NULL,
			CONSTRAINT ssh_key_admin_id FOREIGN KEY (ssh_key_admin_id)
  				REFERENCES admin (admin_id) ON DELETE CASCADE
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;

		INSERT IGNORE instant_ssh_keys SELECT
			ssh_user_id, ssh_user_permission_id, ssh_user_admin_id, CONCAT('key_', ssh_user_id), ssh_user_key,
			ssh_user_key_fingerprint, ssh_user_auth_options, ssh_user_status
		FROM
			instant_ssh_users;

		DROP TABLE IF EXISTS instant_ssh_users
	"
);

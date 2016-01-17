<?php
/**
 * i-MSCP RawPasswd plugin
 * Copyright (C) 2015-2016 Laurent Declercq <l.declercq@nuxwin.com>
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

// Add admin.admin_rawpasswd column in i-MSCP database

return array(
	'up' => '
		ALTER TABLE
			`admin`
		ADD
			`admin_rawpasswd` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL
		AFTER
			`admin_pass`
	',
	'down' => '
		ALTER TABLE `admin` DROP `admin_rawpasswd`
	'
);

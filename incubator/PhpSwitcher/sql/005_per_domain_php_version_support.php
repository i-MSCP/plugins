<?php
/**
 * i-MSCP PhpSwitcher plugin
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

return array(
	'up' => "
		ALTER TABLE
			php_switcher_version_admin
		ADD
			domain_name varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;

		ALTER TABLE
			php_switcher_version_admin
		ADD
			domain_type ENUM('dmn','sub','als','subals') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;

		UPDATE
			php_switcher_version_admin AS t1
		INNER JOIN
			domain AS t2
		SET
			t1.domain_name = t2.domain_name, t1.domain_type = 'dmn'
		WHERE
			t1.admin_id = t2.domain_admin_id;

		ALTER TABLE
			php_switcher_version_admin
		DROP INDEX
			admin_id_version_admin,
		ADD INDEX
			version_admin_admin_id (admin_id);

		ALTER TABLE php_switcher_version_admin ADD UNIQUE version_admin_domain_name (domain_name);

		ALTER TABLE
			php_switcher_version_admin
		ADD INDEX
			version_admin_domain_name_domain_type (domain_name, domain_type);
	",
	'down' => "
		DELETE FROM php_switcher_version_admin WHERE domain_type <> 'dmn';

		ALTER TABLE
			php_switcher_version_admin
		DROP INDEX
			version_admin_admin_id,
		ADD
			UNIQUE admin_id_version_admin (admin_id);

		ALTER TABLE php_switcher_version_admin DROP domain_type;
		ALTER TABLE php_switcher_version_admin DROP domain_name;
	"
);

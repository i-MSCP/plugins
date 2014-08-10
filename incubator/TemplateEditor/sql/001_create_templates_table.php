<?php
/**
 * i-MSCP TemplateEditor plugin
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
		CREATE TABLE IF NOT EXISTS template_editor_templates (
			template_id int(10) unsigned AUTO_INCREMENT NOT NULL,
			temmplate_parent_id int(10) unsigned DEFAULT NULL,
			template_name = varchar(50) COLLATE utf8_unicode_ci NOT NULL,
			template_pretty_name varchar(50) COLLATE utf8_unicode_ci NOT NULL,
			template_content text COLLATE utf8_unicode_ci NOT NULL,
			template_service_name varchar(50) COLLATE utf8_unicode_ci NOT NULL,
			template_scope varchar(15) NOT NULL,
			template_status varchar(15) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'ok',
			PRIMARY KEY template_id (template_id),
			KEY template_name (template_name)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;
	",
	'down' => '
		DROP TABLE IF EXISTS template_editor_templates
	'
);

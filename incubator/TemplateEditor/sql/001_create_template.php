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
 			id int(10) unsigned AUTO_INCREMENT NOT NULL,
			parent_id int(10) unsigned DEFAULT NULL,
 			name varchar(50) NOT NULL,
 			service_name varchar(50) NOT NULL,
 			scope varchar(15) NOT NULL,
 			is_default tinyint NOT NULL,
 			status varchar(15) NOT NULL DEFAULT 'ok',
 			PRIMARY KEY id (id),
 			KEY parent_id (parent_id),
 			UNIQUE KEY name_service_name (name,service_name),
 			KEY service_name (service_name),
 			CONSTRAINT templates_parent_id FOREIGN KEY (parent_id)
 				REFERENCES template_editor_templates (id) ON DELETE CASCADE
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;
	",
	'down' => '
		DROP TABLE IF EXISTS template_editor_templates
	'
);

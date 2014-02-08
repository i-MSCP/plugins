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
	'up' =>
		'
			CREATE TABLE IF NOT EXISTS `template_editor_group` (
				`group_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				`group_parent_id` int(10) unsigned DEFAULT NULL,
				`group_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
				`group_service_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
				`group_scope` varchar(25) COLLATE utf8_unicode_ci NOT NULL,
				PRIMARY KEY (`group_id`),
				UNIQUE KEY `group_name` (`group_name`),
				KEY `group_parent_id` (`group_parent_id`),
				KEY `group_service_name` (`group_service_name`),
				KEY `group_scope` (`group_scope`),
				CONSTRAINT `group_parent_id` FOREIGN KEY (`group_parent_id`)
					REFERENCES `template_editor_group` (`group_id`) ON DELETE CASCADE
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;
		',
	'down' => 'DROP TABLE IF EXISTS template_editor_group'
);

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
		CREATE TABLE IF NOT EXISTS tple_templates (
			tid int(10) unsigned AUTO_INCREMENT NOT NULL,
			tpid int(10) unsigned DEFAULT NULL,
			tgid int(10) unsigned NOT NULL,
			tname varchar(50) COLLATE utf8_unicode_ci NOT NULL,
			tcontent mediumtext COLLATE utf8_unicode_ci NOT NULL,
			tsname varchar(50) COLLATE utf8_unicode_ci NOT NULL,
			ttype varchar(50) COLLATE utf8_unicode_ci NOT NULL,
			tscope varchar(15) NOT NULL,
			tdefault tinyint(1) NOT NULL DEFAULT '0',
			PRIMARY KEY (tid),
			UNIQUE KEY tplu (tgid, tname, tsname, tscope),
			INDEX tpl_tsname_tscope (tsname, tscope),
			CONSTRAINT tpid FOREIGN KEY (tpid) REFERENCES tple_templates (tid) ON DELETE CASCADE,
  			CONSTRAINT tgid FOREIGN KEY (tgid) REFERENCES tple_tgroups (tgid) ON DELETE CASCADE
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;
	",
	'down' => '
		DROP TABLE IF EXISTS tple_templates
	'
);

<?php
/**
 * i-MSCP Mailgraph plugin
 * Copyright (C) 2013-2016 Laurent Declercq <l.declercq@nuxwin.com>
 * Copyright (C) 2010-2016 Sascha Bay <info@space2place.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

return array(
	'cronjob_enabled' => true, // TRUE to enable Mailgraph plugin cronjob (default), FALSE to disable it

	// See man CRONTAB(5) for allowed values
	'cronjob_config' => array(
		'minute' => '*/5',
		'hour' => '*',
		'day' => '*',
		'month' => '*',
		'dweek' => '*'
	)
);

<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2013 by i-MSCP Team
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
 *
 * @category    iMSCP
 * @package     iMSCP_Plugin
 * @subpackage  Mailgraph
 * @copyright   2010-2013 by i-MSCP Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

return array(
	'cronjob_enabled' => true, // TRUE to enable Mailgraph plugin cronjob (default), FALSE to disable it
	'cronjob_config' => array(
		'minute' => '05', // minute ( value from 0 to 59 or * for every minutes)
		'hour' => '*', // hour (value from 0 to 23 or * for every hours)
		'day' => '*', // day ( value from 1 to 31 or * for every days)
		'month' => '*', // month ( value from 1 to 12 or * for every months )
		'dweek' => '*' // day of the week (value from 0 to 6 or * for every day of the week)
	)
);

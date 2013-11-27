<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) Laurent Declercq <l.declercq@nuxwin.com>
 * Copyright (C) Sascha Bay <info@space2place.de>
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
 * @subpackage  JailKit
 * @copyright   Laurent Declercq <l.declercq@nuxwin.com>
 * @copyright   Sascha Bay <info@space2place.de>
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @author      Sascha Bay <info@space2place.de>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

return array(
	// Jailkit installation directory.
	// This path is used as value of the --prefix option (JailKit configure script).
	// IMPORTANT: You must never change this parameter while updating the plugin to a new version.
	'install_path' => '/usr/local',

	// Path to the root jail directory which holds all customer jails.
	// IMPORTANT: You must never change this parameter while updating the plugin to a new version.
	'root_jail_path' => '/home/imscp-jails',

	// See man shells
	'shell' => '/bin/bash',

	// See man jk_init
	'jail_app_sections' => array(
		'basicshell',
		'editors',
		'sftp',
		'mysql-client'
	),

	// See man jk_cp
	'jail_additional_apps' => array(
		'/bin/hostname',
		'/usr/bin/basename',
		'/usr/bin/dircolors',
		'/usr/bin/dirname',
		'/usr/bin/env',
		'/usr/bin/id',
		'/usr/bin/groups',
		'/usr/bin/tput',
		'/usr/bin/which'
	),

	// See man jk_socketd
	'jail_socketd_base' => '512',
	'jail_socketd_peak' => '2048',
	'jail_socketd_interval' => '10',

	// Max SSH user per customer
	// This is only a default value which can be modified through the reseller interface
	'max_allowed_ssh_user' => '1'
);

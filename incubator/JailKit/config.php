<?php
/**
 * i-MSCP JailKit plugin
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
	// Jailkit installation directory
    //
	// This path is used as value of the --prefix option (JailKit configure script).
	// IMPORTANT: You must never change this parameter while updating the plugin to a new version.
	'install_path' => '/usr/local',

	// Full path to the root jail directory which holds all jails. Be sure that the partition in which this directory is
	// living has enough space to host the jails.
	// IMPORTANT: You must never change this parameter while updating the plugin to a new version.
	'root_jail_dir' => '/home/imscp-jails',

	// See man shells
	// Don't change this value if you do not know what you are doing
	'shell' => '/bin/bash',

	// See man jk_init
	'jail_app_sections' => array(
		'imscp-base', // Include Pre-selected sections, users and groups
		'mysql-client'
	),

	// See man jk_cp
	// Any file which is not installed on your system will be ignored
	'jail_additional_apps' => array(
		'/bin/hostname',
		'/usr/bin/basename',
		'/usr/bin/dircolors',
		'/usr/bin/dirname',
		'/usr/bin/clear_console',
		'/usr/bin/env',
		'/usr/bin/id',
		'/usr/bin/groups',
		'/usr/bin/lesspipe',
		'/usr/bin/tput',
		'/usr/bin/which'
	),

	// See man jk_socketd
	'jail_socketd_base' => '512',
	'jail_socketd_peak' => '2048',
	'jail_socketd_interval' => '5.0',

	// List of additional host directories to mount in jails
	// See man mount (bind mounts section)
	'jail_host_directories' => array(
		// oldir => newdir_in_jail
		'/var/run/mysqld' => '/var/run/mysqld'
	)
);

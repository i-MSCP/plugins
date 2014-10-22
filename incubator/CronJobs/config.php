<?php
/**
 * i-MSCP CronJobs plugin
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
	// Shell for cronjob (default: /bin/sh for full cronjobs ; /bin/ash for jailed cronjobs)
	//
	// See man shells for further details. Do not change the default values if you do not know what you are doing.
	'shells' => array(
		// Shell for full cronjob
		'normal' => '/bin/sh',

		// Shell for jailed cronjobs (only relevant if you installed the InstantSSH plugin >= 2.0.4)
		// BusyBox built-in shell ; Don't forget to set it back to something else if you do not use the BusyBox built-in
		// shell. /bin/ash is a link that point to the /bin/busybox executable.
		'jailed' => '/bin/ash'
	),

	//
	// Jailed cronjobs environment configuration parameters (only relevant if you installed InstantSSH plugin >= 2.0.4)
	//

	// Root jail directory (default: /var/chroot/CronJobs)
	//
	// Full path to the root jail directory. Be sure that the partition in which this directory is living has enough
	// space to host the jails.
	//
	// Warning: If you are changing this path, don't forget to move the jails in the new location.
	'root_jail_dir' => '/var/chroot/CronJobs',

	// Makejail script path
	// Don't change this parameter if you don't know what you are doing
	'makejail_path' => realpath('../InstantSSH/bin/makejail'),

	// Makejail configuration directory (default: <CONF_DIR>/CronJobs)
	// Don't change this parameter if you don't know what you are doing
	'makejail_confdir_path' => iMSCP_Registry::get('config')->get('CONF_DIR') . '/CronJobs',

	// Preserved files (default: <USER_WEB_DIR>)
	//
	// The plugin won't try to remove files or directories inside jails if their path begins with one of the strings
	// in this list.
	//
	// This option can be also defined in the application sections (see below).
	//
	// WARNING: Do not remove the default entry if you don't know what you are doing.
	'preserve_files' => array(
		iMSCP_Registry::get('config')->get('USER_WEB_DIR')
	),

	// Selected application sections for jailed cronjob environment (default: default)
	//
	// This is the list of application sections which are used to create/update the jailed cronjob environement.
	//
	// By default only the default application section is added, which allows to build very restricted jailed  cronjob
	// environments using BusyBox.
	'app_sections' => array(
		'default'
	),

	// Predefined application sections for jailed cronjob environment
	// See InstanSSH configuration file for more details about how to define your own application sections

	// uidbasics section
	// Provide common files for jails that need user/group information
	'uidbasics' => array(
		'paths' => array(
			'/etc/ld.so.conf', '/etc/passwd', '/etc/group', '/etc/nsswitch.conf', '/lib/libnsl.so.1',
			'/lib64/libnsl.so.1', '/lib/libnss*.so.2', '/lib64/libnss*.so.2', '/lib/i386-linux-gnu/libnsl.so.1',
			'/lib/i386-linux-gnu/libnss*.so.2', '/lib/x86_64-linux-gnu/libnsl.so.1',
			'/lib/x86_64-linux-gnu/libnss*.so.2'
		)
	),

	// default section
	// Provide pre-selected application sections, users and groups for Cron jailed shell environments
	'default' => array(
		'paths' => array(
			'/bin/ash', '/bin/busybox', '/tmp'
		),
		'include_app_sections' => array(
			'uidbasics'
		),
		'users' => array(
			'root', 'www-data'
		),
		'groups' => array(
			'root', 'www-data'
		),
		'devices' => array(
			'/dev/null', '/dev/urandom', '/dev/zero'
		)
	)
);

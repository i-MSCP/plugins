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

/** @var iMSCP_Plugin_Manager $pluginManager */
$pluginManager = iMSCP_Registry::get('pluginManager');

if($pluginManager->isPluginKnown('InstantSSH')) {
	if(
		isset($instantConfig['makejail_path']) && isset($instantConfig['preserve_files']) &&
		isset($instantConfig['bashshell']) && isset($instantConfig['uidbasics']) &&
		isset($instantConfig['netbasics']) && isset($instantConfig['netutils']) &&
		isset($instantConfig['rsync']) && isset($instantConfig['mysqltools']) &&
		isset($instantConfig['php'])
	) {
		$instantConfig = $pluginManager->getPlugin('InstantSSH')->getConfig();

		$config = array(
			// Root jail directory (default: /var/chroot/CronJobs)
			//
			// Full path to the root jail directory. Be sure that the partition in which this directory is living has
			// enough space to host the jails.
			//
			// Warning: If you are changing this path, don't forget to move the jail in the new location.
			'root_jail_dir' => '/var/chroot/CronJobs',

			// Se InstantSSH/config.php
			'makejail_path' => $instantConfig['makejail_path'],

			// Makejail configuration directory (default: <CONF_DIR>/CronJobs)
			// Don't change this parameter unless you know what you are doing
			'makejail_confdir_path' => iMSCP_Registry::get('config')->get('CONF_DIR') . '/CronJobs',

			// See InstantSSH/config.php
			'preserve_files' => $instantConfig['preserve_files'],

			// Selected application sections for jailed cron environment (default: default)
			//
			// This is the list of application sections which are used to create/update the jailed cron environement.
			//
			// By default only the default application section is added, which allows to build very restricted jailed
			// cron environment using BusyBox.
			'app_sections' => array(
				'cronjobs_base',
			),

			// Predefined application sections for jailed cron environment
			// See InstanSSH configuration file for more details about how to define your own application sections

			// See InstantSSH/config.php
			'bashshell' => $instantConfig['bashshell'],
			'uidbasics', $instantConfig['uidbasics'],
			'netbasics' => $instantConfig['netbasics'],
			'netutils' => $instantConfig['netutils'],
			'rsync' => $instantConfig['rsync'],
			'mysqltools' => $instantConfig['mysqltools'],
			'php' => $instantConfig['php'],

			# cron section
			'cron' => array(
				'paths' => array(
					'/usr/sbin/cron', '/dev'
				),
				'preserve_files' => array(
					'/dev/log'
				)
			),

			// cronjobs base section
			// Provide pre-selected application sections, users and groups for Cron jailed shell environment
			'cronjobs_base' => array(
				'include_app_sections' => array(
					'bashshell', 'cron', 'php'
				),
				'users' => array(
					'root'
				),
				'groups' => array(
					'root'
				)
			),
		);
	}

	unset($pluginManager, $instantConfig);
} else {
	$config = array();
}

return array_merge($config, array(
	// Path to the crontab command ( default: /usr/bin/crontab )
	'crontab_path' => '/usr/bin/crontab'
));

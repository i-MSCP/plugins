<?php
/**
 * i-MSCP InstantSSH plugin
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

require_once 'InstantSSH/Validate/SshAuthOptions.php';

return array(
	// Default SSH authentication options added for any new SSH key
	//
	// See man authorized_keys for list of allowed authentication options.
	// eg. command="dump /home",no-pty,no-port-forwarding
	//
	// WARNING: Any option defined here must be specified in the allowed_ssh_auth_options configuration option.
	'default_ssh_auth_options' => 'no-agent-forwarding,no-port-forwarding,no-X11-forwarding',

	//
	// SSH authentication options that customers are allowed to define when they are allowed to override default.
	//
	// Valid options are:
	//
	// \InstantSSH\Validate\SshAuthOptions::ALL (for all options)
	// \InstantSSH\Validate\SshAuthOptions::CERT_AUTHORITY (for the cert-authority option)
	// \InstantSSH\Validate\SshAuthOptions::COMMAND (for the 'command' option)
	// \InstantSSH\Validate\SshAuthOptions::ENVIRONMENT (for the 'environment' option)
	// \InstantSSH\Validate\SshAuthOptions::FROM (for the 'from' option)
	// \InstantSSH\Validate\SshAuthOptions::NO_AGENT_FORWARDING (for the 'no-agent-forwarding' option)
	// \InstantSSH\Validate\SshAuthOptions::NO_PORT_FORWARDING (for the 'no-port-forwarding' option)
	// \InstantSSH\Validate\SshAuthOptions::NO_PTY (for the 'no-pty' option)
	// \InstantSSH\Validate\SshAuthOptions::NO_USER_RC (for the 'no-user-rc' option)
	// \InstantSSH\Validate\SshAuthOptions::NO_X11_FORWARDING (for the 'no-x11-forwarding' option)
	// \InstantSSH\Validate\SshAuthOptions::PERMITOPEN (for the 'permitopen' option)
	// \InstantSSH\Validate\SshAuthOptions::PRINCIPALS (for the 'principals' option)
	// \InstantSSH\Validate\SshAuthOptions::TUNNEL (for the 'tunnel' option)
	'allowed_ssh_auth_options' => array(
		\InstantSSH\Validate\SshAuthOptions::ALL
	),

	// Shell for SSH users (default: /bin/bash for normal shell ; /bin/ash for jailed shell)
	//
	// See man shells for further details. Please, do not change the default values if you do not know what you are doing.
	'shells' => array(
		// Shell for normal ssh users
		'normal' => '/bin/bash',

		// Shell for jailed ssh users
		// BusyBox built-in shell ; Don't forget to set it back to /bin/bash if you do not use BusyBox built-in shell
		// Note: /bin/ash is a link that point to the /bin/busybox executable. This link is automatically created by the
		// plugin. The /bin/ash shell is also automatically added in the /etc/shells file.
		'jailed' => '/bin/ash'
	),

	// Root jail directory (default: /var/chroot/InstantSSH)
	//
	// Full path to the root jail directory. Be sure that the partition in which this directory is living has enough
	// space to host the jails.
	'root_jail_dir' => '/var/chroot/InstantSSH',

	// Shared jail (default: true)
	//
	// When the value is true, only one jail is created for all customers. A shared jail doesn't mean that customers
	// will be able to read, modify or delete files of other customers. This simply mean that the jail will be shared
	// between customers. The primary purpose of a jailed environment is to protect the main system. Having a jail for
	// each customer is interesting only when you want provide a different set of commands for each of them.
	//
	// Note: The creation of a jail per customer is currently useless because the per customer application feature is
	// not implemented yet. This will be implemented in near future.
	'shared_jail' => true,

	// Preserved files (default: USER_WEB_DIR parameter value from the imscp.conf file)
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

	// Whether or not files from packages required by packages listed in packages options must be copied whthin the jails
	'include_pkg_deps' => false,

	// Selected application sections for jailed shell environments (default: imscpbase)
	//
	// This is the list of application sections which are used to create/update the jails (see below).
	//
	// By default only the imscpbase application section is added, which allows to build a very restricted jailed shell
	// environments using BusyBox.
	'app_sections' => array(
		'imscpbase'
	),

	// Predefined application sections for jailed shell environments
	//
	// Below, you can find the predefined application sections. Those sections are used to create and update the jails.
	// You can select as many sections as you want by adding them into the app_sections configuration option above.
	//
	// It's not recommended to change a section without understanding its meaning and how it is working. Once you know
	// how the sections are defined, you can define your own sections.
	//
	// Application section options
	//
	// The following options can be defined in application sections
	//
	// paths: List of paths which have to be copied inside the jail. Be aware that copy is not recursive.
	// packages: List of debian packages. Files from those packages will be copied inside the jail
	// include_apps_sections: List of applications sections that have to be included
	// users: List of users that have to be added inside the jail (eg. in passwd/shadow files)
	// groups: List of groups that have to be added inside the jail (eg. in group/gshadow files)
	// preserve_files: Files that have to be preserved when a jail is being updated
	// devices: List of devices that have to be copied inside the jail
	//
	// Notes:
	//  - All options which contain paths (paths, devices) support the glob patterns.
	//  - Any path which doesn't exists on the system is ignored
	//  - Any package listed in a package option must be already installed on the system, else an error is thrown
	//  - Any device must exists on the system, else an error is thrown. You must use glob patterns to avoid any error
	//
	// Other application sections will be added soon.

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

	// busyboxshell section
	// Provide restricted shell using BusyBox (built-in shell and common UNIX utilities)
	'busyboxshell' => array(
		'paths' => array(
			'/bin/ash',
			'/proc'
		),
		'include_app_sections' => array(
			'uidbasics',
			'busybox'
		),
		'preserve_files' => array(
			'/dev'
		),
		'devices' => array(
			'/dev/ptmx',
			'/dev/pty*',
			'/dev/pts/*',
			'/dev/tty*',
			'/dev/urandom',
			'/dev/zero',
			'/dev/null'
		)
	),

	// busybox section
	// Provide BusyBox which combines tiny versions of many common UNIX utilities into a single small executable
	'busybox' => array(
		'packages' => array(
			'busybox'
		)
	),

	// imscpbase section
	// Provide pre-selected path, application sections, users and groups for i-MSCP jailed shell
	'imscpbase' => array(
		'users' => array(
			'root', 'www-data'
		),
		'groups' => array(
			'root', 'www-data'
		),
		'include_app_sections' => array(
			'busyboxshell'
		)
	)
);

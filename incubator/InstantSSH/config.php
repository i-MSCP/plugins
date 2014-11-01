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
	// \InstantSSH\Validate\SshAuthOptions::ALL (all options)
	// \InstantSSH\Validate\SshAuthOptions::CERT_AUTHORITY ( 'cert-authority' option )
	// \InstantSSH\Validate\SshAuthOptions::COMMAND ( 'command' option )
	// \InstantSSH\Validate\SshAuthOptions::ENVIRONMENT ( 'environment' option )
	// \InstantSSH\Validate\SshAuthOptions::FROM ( 'from' option )
	// \InstantSSH\Validate\SshAuthOptions::NO_AGENT_FORWARDING ( 'no-agent-forwarding' option )
	// \InstantSSH\Validate\SshAuthOptions::NO_PORT_FORWARDING ( 'no-port-forwarding' option )
	// \InstantSSH\Validate\SshAuthOptions::NO_PTY ( 'no-pty' option )
	// \InstantSSH\Validate\SshAuthOptions::NO_USER_RC ( 'no-user-rc' option )
	// \InstantSSH\Validate\SshAuthOptions::NO_X11_FORWARDING ( 'no-x11-forwarding' option )
	// \InstantSSH\Validate\SshAuthOptions::PERMITOPEN ( 'permitopen' option )
	// \InstantSSH\Validate\SshAuthOptions::PRINCIPALS ( 'principals' option )
	// \InstantSSH\Validate\SshAuthOptions::TUNNEL ( 'tunnel' option )
	'allowed_ssh_auth_options' => array(
		\InstantSSH\Validate\SshAuthOptions::ALL
	),

	// Shell for SSH users (default: /bin/bash for full SSH access ; /bin/ash for restricted SSH access)
	//
	// See man shells for further details. Do not change the default values if you do not know what you are doing.
	'shells' => array(
		// Shell for full SSH access
		'full' => '/bin/bash',

		// Shell for restricted SSH access
		// BusyBox built-in shell ; Don't forget to set it back to something else if you do not use the BusyBox built-in
		// shell. /bin/ash is a link that point to the /bin/busybox executable. This link is automatically created by the
		// plugin. The /bin/ash shell is also automatically added in the /etc/shells file.
		'jailed' => '/bin/ash'
	),

	// Root jail directory (default: /var/chroot/InstantSSH)
	//
	// Full path to the root jail directory. Be sure that the partition in which this directory is living has enough
	// space to host the jails.
	//
	// Warning: If you are changing this path, don't forget to move the jails in the new location.
	'root_jail_dir' => '/var/chroot/InstantSSH',

	// Makejail script path
	// Don't change this parameter if you don't know what you are doing
	'makejail_path' => __DIR__ . '/bin/makejail',

	// Makejail configuration directory (default: <CONF_DIR>/InstantSSH)
	// Don't change this parameter if you don't know what you are doing
	'makejail_confdir_path' => iMSCP_Registry::get('config')->get('CONF_DIR') . '/InstantSSH',

	// Shared jail (default: true)
	//
	// When set to true, only one jail is created for all customers. A shared jail doesn't mean that customers will be
	// able to read, modify or delete files of other customers. This simply mean that the jail will be shared between
	// customers. The primary purpose of a jailed environment is to protect the main system. Having a jail for each
	// customer is interesting only when you want provide a different set of commands for each of them.
	//
	// Note: The creation of a jail per customer is currently useless because the per customer application feature is
	// not implemented yet. This will be implemented in near future.
	'shared_jail' => true,

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

	// Whether or not files from packages required by packages listed in packages option must be copied within the jails
	'include_pkg_deps' => false,

	// Selected application sections for jailed shell environments (default: imscpbase)
	//
	// This is the list of application sections which are used to create/update the jails (see below).
	//
	// By default only the imscpbase application section is added, which allows to build very restricted jailed shell
	// environments using BusyBox. The imscpbase application section also include the editors and the mysqltools sections
	'app_sections' => array(
		'imscpbase'
	),

	// Predefined application sections for jailed shell environments
	//
	// Below, you can find the predefined application sections. Those sections are used to create and update the jails.
	// You can select as many sections as you want by adding them into the app_sections configuration option above.
	//
	// It is not recommended to change a section without understanding its meaning and how it is working. Once you know
	// how the sections are defined, you can define your own sections.
	//
	// Application section options
	//
	// The following options can be defined in application sections
	//
	// paths: List of paths which have to be copied inside the jail. Be aware that copy is not recursive
	// packages: List of debian packages. Files from those packages will be copied inside the jail
	// copy_file_to: List of files to copy within the jail, each of them specified as a key/value pairs where the key
	//               is the source file path and the value, the destination path
	// include_apps_sections: List of applications sections that have to be included
	// users: List of users that have to be added inside the jail (eg. in passwd/shadow files)
	// groups: List of groups that have to be added inside the jail (eg. in group/gshadow files)
	// preserve_files: Files that have to be preserved when a jail is being updated
	// devices: List of devices that have to be copied inside the jail
	// mount: List of directory/file to mount within the jail, each of them specified as a key/value pair where the key
	//        corresponds to 'oldir/oldfile' on the system or the fstype (devpts, proc) and the value to 'newdir/newfile'
	// within the jail.
	//
	// Notes:
	//  - The paths and devices options both support the glob patterns.
	//  - Any path which doesn't exists on the system is ignored
	//  - Any package listed in a package option must be already installed on the system, else an error is thrown
	//  - Any device must exists on the system, else an error is thrown. You must use glob patterns to avoid any error
	//
	// Other application sections will be added in time. Feel free to provide us your own section for integration.

	// ashshell section
	// Provide restricted shell using BusyBox (built-in ash shell and common UNIX utilities)
	// Warning: Don't forget to set the shells => jailed configuration option to /bin/ash
	'ashshell' => array(
		'paths' => array(
			'/bin/ash', '/bin/false', '/tmp', '/usr/bin/dircolors', '/usr/bin/tput', '/var/log'
		),
		'copy_file_to' => array(
			dirname(__FILE__) . '/config/etc/motd' => '/etc/motd',
			dirname(__FILE__) . '/config/etc/profile' => '/etc/profile'
		),
		'include_app_sections' => array(
			'busybox', 'uidbasics', 'editors'
		),
		'devices' => array(
			'/dev/null', '/dev/ptmx', '/dev/urandom', '/dev/zero'
		),
		'mount' => array(
			'devpts' => '/dev/pts',
			'proc' => '/proc',
			'/var/log/lastlog' => '/var/log/lastlog' # Needed for the last login message
		)
	),

	// bashshell
	// Provide restricted shell using bash
	// Warning: Don't forget to set the shells => jailed configuration option to /bin/bash
	'bashshell' => array(
		'paths' => array(
			'/bin/sh', '/bin/bash', '/bin/ls', '/bin/cat', '/bin/chmod', '/bin/mkdir', '/bin/cp', '/bin/cpio',
			'/bin/date', '/bin/dd', '/bin/echo', '/bin/egrep', '/bin/false', '/bin/fgrep', '/bin/grep', '/bin/gunzip',
			'/bin/gzip', '/bin/ln', '/bin/mktemp', '/bin/more', '/bin/mv', '/bin/pwd', '/bin/rm', '/bin/rmdir',
			'/bin/sed',  '/bin/sleep', '/bin/sync', '/bin/tar', '/usr/bin/touch', '/bin/true', '/bin/uncompress',
			'/bin/zcat', '/etc/issue', '/etc/bash.bashrc', '/usr/bin/dircolors', '/usr/bin/tput', '/tmp', '/var/log',
			'/usr/bin/awk', '/bin/bzip2', '/bin/bunzip2', '/usr/bin/ldd', '/usr/bin/less', '/usr/bin/clear',
			'/usr/bin/cut', '/usr/bin/du', '/usr/bin/find', '/usr/bin/head', '/usr/bin/md5sum', '/usr/bin/nice',
			'/usr/bin/sort', '/usr/bin/tac', '/usr/bin/tail', '/usr/bin/tr', '/usr/bin/wc', '/usr/bin/watch',
			'/usr/bin/whoami', '/usr/bin/id', '/bin/hostname', '/usr/bin/lzma', '/usr/bin/xz', '/usr/bin/pbzip2',
			'/usr/bin/curl', '/usr/bin/env', '/bin/readlink'
		),
		'copy_file_to' => array(
			dirname(__FILE__) . '/config/etc/motd' => '/etc/motd',
			dirname(__FILE__) . '/config/etc/profile' => '/etc/profile'
		),
		'include_app_sections' => array(
			'uidbasics', 'editors'
		),
		'devices' => array(
			'/dev/null', '/dev/ptmx', '/dev/urandom', '/dev/zero'
		),
		'mount' => array(
			'devpts' => '/dev/pts',
			'proc' => '/proc',
			'/var/log/lastlog' => '/var/log/lastlog' # Needed for the last login message
		)
	),

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

	// netbasics section
	// Provide common files for jails that need any internet connectivity
	'netbasics' => array(
		'paths' => array(
			'/lib/libnss_dns.so.2', '/lib64/libnss_dns.so.2', '/etc/resolv.conf', '/etc/host.conf', '/etc/hosts',
			'/etc/protocols', '/etc/services'
		),
	),

	// busybox section
	// Provide BusyBox which combines tiny versions of many common UNIX utilities into a single small executable
	'busybox' => array(
		'paths' => array(
			'/bin/busybox'
		)
	),

	// netutils section
	// Provide wget, lynx, ftp, ssh, sftp, scp, rsync
	'netutils' => array(
		'paths' => array(
			'/usr/bin/ftp', '/usr/bin/lynx', '/usr/bin/wget', '/etc/lynx-cur/*'
		),
		'include_app_sections' => array(
			'netbasics', 'scp', 'sftp', 'ssh', 'rsync'
		)
	),

	# scp section
	# Provide ssh secure copy
	'scp' => array(
		'paths' => array(
			'/usr/bin/scp'
		),
		'include_app_sections' => array(
			'netbasics', 'uidbasics'
		),
		'devices' => array(
			'/dev/urandom'
		)
	),

	# sftp section
	# Provide ssh secure ftp
	'sftp' => array(
		'paths' => array(
			'/usr/lib/sftp-server', '/usr/lib/openssh/sftp-server', '/usr/bin/sftp'
		),
		'include_app_sections' => array(
			'netbasics', 'uidbasics'
		),
		'devices' => array(
			'/dev/urandom', '/dev/null'
		)
	),

	# ssh section
	# Provide ssh secure shell
	'ssh' => array(
		'paths' => array(
			'/usr/bin/ssh'
		),
		'include_app_sections' => array(
			'netbasics', 'uidbasics'
		),
		'devices' => array(
			'/dev/urandom', '/dev/tty', '/dev/null'
		)
	),

	# rsync section
	# Provide rsync
	'rsync' => array(
		'paths' => array(
			'/usr/bin/rsync'
		),
		'include_app_sections' => array(
			'netbasics', 'uidbasics'
		),
	),

	# mysqltools section
	# Provide the MySQL command-line tool and the mysqldump program
	'mysqltools' => array(
		'paths' => array(
			'/etc/mysql', '/usr/bin/mysql', '/usr/bin/mysqldump'
		),
		'copy_file_to' => array(
			dirname(__FILE__) . '/config/etc/mysql/my.cnf' => '/etc/mysql/my.cnf'
		)
	),

	// editors section
	// Provide common editors
	'editors' => array(
		'paths' => array(
			'/usr/bin/nano', '/usr/bin/vi', '/usr/bin/vim'
		),
		'include_app_sections' => array(
			'terminfo'
		)
	),

	// terminfo section
	// Provide terminfo databases
	'terminfo' => array(
		'packages' => array(
			'ncurses-base' # Package which provide terminfo data files to support the most common types of terminal
		)
	),

	// imscpbase section
	// Provide pre-selected application sections, users and groups for i-MSCP jailed shell environments
	'imscpbase' => array(
		'include_app_sections' => array(
			'ashshell', 'mysqltools'
		),
		'users' => array(
			'root', 'www-data'
		),
		'groups' => array(
			'root', 'www-data'
		)
	)
);

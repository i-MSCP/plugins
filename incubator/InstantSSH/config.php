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
	// Default SSH authentication options added for any new customer key
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

	// Shell for SSH users (default: /bin/bash)
	//
	// See man shells for further details. Please, do not change this value if you do not know what you are doing.
	'shell' => '/bin/bash',

	##
	### Jailed shell configuration options
	##

	// Root jail directory (default: /var/chroot/InstantSSH)
	//
	// Full path to the root jail directory. Be sure that the partition in which this directory is living has enough
	// space to host the jails.
	//
	// WARNING: You must never change this parameter while updating the plugin to a new version.
	'root_jail_dir' => '/var/chroot/InstantSSH',

	// Shared jail (default: true)
	//
	// When the value is true, only one jail is created for all customers. A shared jail doesn't mean that customers
	// will be able to read, modify or delete files of other customers. This simply mean that the jail will be shared
	// between customers. The primary purpose of a jailed shell environment is to protect the main system. Having a jail
	// for each customer is interesting only when you want provide a different set of commands for each of them.
	//
	// Note: The creation of a jail per customer is currently useless because the per customer application feature is
	// not implemented yet. This will be implemented in near future.
	'shared_jail' => true,

	// Preserved files (default: /home)
	//
	// The plugin won't try to remove files or directories inside jails if their path begins with one of the strings
	// in this list.
	//
	// This option can be also defined in the application sections (see below).
	//
	// WARNING: Do not remove the default /home entry if you don't know what you are doing.
	'preserve_files' => array(
		'/home'
	),

	# Application section (default: imscpbase)
	#
	# This is the list of application sections which are used to create/update the jails (see below).
	#
	# By default only the imscpbase application section is added, which allows to build very restricted jailed shell
	# environments (with limited set of commands).
	'app_sections' => array(
		'imscpbase'
	),

	# Predefined application sections
	#
	# Below, you can find the predefined application sections. Those sections are used to create and update jails.
	# You can select as many sections as you want by adding them into the app_sections configuration option.
	#
	# It's not recommended to change a section without understanding its meaning and how it is working. Once you
	# know how the sections are defined, you can define your own sections.
	#
	# Application section options
	#
	# The following options can be defined in application sections
	#
	# path: List of paths which have to be copied inside the jail
	# packages: List of debian packages. Files from those packages will be copied inside the jail
	# include_apps_sections: List of applications sections that have to be included
	# users: List of users that have to be added inside the jail (eg. in passwd/shadow files)
	# groups: List of groups that have to be added inside the jail (eg. in group/gshadow files)
	# preserve_files: Files that have to be preserved when the jail is being updated
    # devices: List of devices that have to be created inside the jail
	# mount: List of keys/values where a key correspond to oldir (system) and the value to newdir (jail) (see man mount)
	#
	# WARNING: Any of the paths and packages which are listed in the application sections must be already present on
	# your system, else they will be ignored.
	#
	# WARNING: Some applications sections are still experimental and can require few adjustements.

	// uidbasics section
	// Provide common files for all jails that need user/group information
	'uidbasics' => array(
		'paths' => array(
			'/lib/libnsl.so.1', '/lib64/libnsl.so.1', '/lib/libnss*.so.2', '/lib64/libnss*.so.2',
			'/lib/i386-linux-gnu/libnsl.so.1', '/lib/i386-linux-gnu/libnss*.so.2', '/lib/x86_64-linux-gnu/libnsl.so.1',
			'/lib/x86_64-linux-gnu/libnss*.so.2', '/etc/nsswitch.conf', '/etc/ld.so.conf', '/etc/passwd', '/etc/group'
		)
	),

	// netbasics section
	// Provide common files for all jails that need any internet connectivity
	'netbasics' => array(
		'paths' => array(
			'/lib/libnss_dns.so.2', '/lib64/libnss_dns.so.2', '/etc/resolv.conf', '/etc/host.conf', '/etc/hosts',
			'/etc/protocols', '/etc/services'
		)
	),

	// logbasics section
	// Provide timezone information and log sockets
	'logbasics' => array(
		'paths' => '/etc/localtime',
		'need_logsocket' => true
	),

	// basicshell section
	// Provide bash based shell with several basic utilities
	'basicshell' => array(
		'paths' => array(
			'/bin/sh', '/bin/bash', '/bin/ls', '/bin/cpio', '/bin/egrep', '/bin/fgrep', '/bin/grep', '/bin/gunzip',
			'/bin/gzip', '/bin/more', '/usr/bin/rgrep', '/bin/sed', '/bin/tar', '/bin/uncompress', '/bin/zcat',
			'/etc/motd', '/etc/issue', '/etc/bash.bashrc', '/etc/bashrc', '/etc/profile', '/usr/lib/locale/C.UTF-8',
			'/dev/pt*',
			#'/dev/ttyp[0-9]*',
			'/dev/tty*'
		),
		'users' => array(
			'root'
		),
		'groups' => array(
			'root'
		),
		'include_app_sections' => array(
			'uidbasics'
		),
		'packages' => array(
			'coreutils' # Package which provide basic file, shell and text manipulation utilities
		),
		'preserve_files' => array(
			'/dev/'
		)
	),

	// extendedshell section
	// Provide bash shell including things like awk, bzip, tail, less
	'extendedshell' => array(
		'paths' => array(
			'/usr/bin/awk', '/bin/bzip2', '/bin/bunzip2', '/usr/bin/ldd', '/usr/bin/less', '/usr/bin/clear',
			'/usr/bin/cut', '/usr/bin/find', '/usr/bin/less', '/usr/bin/watch',
		),
		'include_app_sections' => array(
			'basicshell', 'midnightcommander', 'editors'
		)
	),

	// editors section
	// Provide vim, joe, nano and pico
	'editors' => array(
		'paths' => array(
			'/usr/bin/joe', '/usr/bin/nano', '/usr/bin/vi', '/usr/bin/vim', '/usr/bin/pico', '/etc/vimrc', '/etc/joe',
			'/usr/share/vim', '/usr/bin/pico'
		)
	),

	// netutils section
	// Provide several internet utilities like wget, ftp, rsync, scp, ssh
	'netutils' => array(
		'paths' => array(
			'/usr/bin/wget', '/usr/bin/lynx', '/usr/bin/ftp', '/usr/bin/host', '/usr/bin/rsync', '/usr/bin/smbclient',
			'/usr/bin/dig'
		),
		'include_app_sections' => array(
			'netbasics', 'ssh', 'sftp', 'scp'
		)
	),

	// section extshellplusnet
	// alias for extendedshell + netutils + apacheutils
	'extshellplusnet' => array(
		'include_app_sections' => array(
			'extendedshell', 'netutils', 'apacheutils'
		)
	),

	// midnightcommander section
	// Midnight Commander
	'midnightcommander' => array(
		'paths' => array(
			'/usr/bin/mc', '/usr/bin/mcedit', '/usr/bin/mcview', '/usr/share/mc'
		),
		'include_app_sections' => array(
			'basicshell', 'terminfo'
		)
	),

	// scp section
	// Provide ssh secure copy
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

	// sftp section
	// Provide ssh secure ftp
	'sftp' => array(
		'paths' => array(
			'/usr/lib/sftp-server', '/usr/libexec/openssh/sftp-server', '/usr/lib/misc/sftp-server',
			'/usr/libexec/sftp-server', '/usr/lib/openssh/sftp-server'
		),
		'include_app_sections' => array(
			'netbasics', 'uidbasics'
		),
		'devices' => array(
			'/dev/urandom', '/dev/null'
		)
	),

	// ssh section
	// Provide ssh secure shell
	'ssh' => array(
		'paths' => array(
			'/usr/bin/ssh'
		),
		'include_app_sections' => array(
			'netbasics', 'uidbasics'
		),
		'devices' => array( # TODO review
			'/dev/urandom', '/dev/tty', '/dev/null'
		),
		'preserve_files' => array(
			'/dev'
		)
	),

	// rsync section
	// Provide rsync command
	'rsync' => array(
		'path' => array(
			'/usr/bin/rsync'
		),
		'include_app_sections' => array(
			'netbasics', 'uidbasics'
		)
	),

	// cvs section
	// Provide concurrent Versions System
	'cvs' => array(
		'paths' => array(
			'/usr/bin/cvs'
		),
		'devices' => array(
			'/dev/null'
		)
	),

	// git section
	// Provide Git - Distributed revision control and source code management (SCM) system
	'git' => array(
		'paths' => array(
			'/usr/bin/git*', '/usr/lib/git-core'
		),
		'commands' => array(
			'basename', 'uname'
		),
		'include_app_sections' => array(
			'editors', 'perl'
		)
	),

	// procmail section
	// Provide procmail mail delivery agent
	'procmail' => array(
		'paths' => array(
			'/usr/bin/procmail', '/bin/sh'
		),
		'devices' => array(
			'/dev/null'
		)
	),

	// terminfo section
	// Provide terminfo databases, required for example for ncurses or vim
	'terminfo' => array(
		'paths' => array(
			'/etc/terminfo', '/usr/share/terminfo', '/lib/terminfo'
		)
	),

	// apacheutils section
	// Provide htpasswd utility
	'apacheutils' => array(
		'paths' => array(
			'/usr/bin/htpasswd'
		)
	),

	// perl section
	// Provide the perl interpreter and libraries
	'perl' => array(
		'paths' => array(
			'/usr/bin/perl', '/usr/lib/perl', '/usr/lib/perl5', '/usr/share/perl', '/usr/share/perl5'
		)
	),

	// ping section
	// Provide the ping command
	'ping' => array(
		'paths' => array(
			'/bin/ping'
		)
	),

	// xterm section
	// Provide xterm
	'xterm' => array(
		'paths' => array(
			'/usr/bin/X11/xterm', '/usr/share/terminfo', '/etc/terminfo'
		),
		'devices' => array( # TODO REVIEW
			'/dev/pts/0', '/dev/pts/1', '/dev/pts/2', '/dev/pts/3', '/dev/pts/4', '/dev/ptyb4', '/dev/ptya4',
			'/dev/tty', '/dev/tty0', '/dev/tty4'
		)
	),

	// mysqlclient section
	// Provide MySQL client
	'mysqlclient' => array(
		'paths' => array(
			'/usr/bin/mysql'
		),
		'users' => array(
			'mysql'
		),
		'groups' => array(
			'mysql'
		),
		'include_app_sections' => array(
			'netbasics', 'uidbasics'
		),
		'mount' => array(
			'/var/run/mysqld' => '/var/run/mysqld'
		)
	),

	// imscpbase section
	// Provide pre-selected commands and application sections, users and groups for i-MSCP jailed shell
	'imscpbase' => array(
		'paths' => array(
			'/bin/hostname',
			'/usr/bin/clear_console',
			'/usr/bin/id',
			'/usr/bin/groups',
			'/usr/bin/lesspipe',
			'/usr/bin/tput',
			'/usr/bin/which'
		),
		'users' => array(
			'root', 'www-data'
		),
		'groups' => array(
			'root', 'www-data'
		),
		'include_app_sections' => array(
			'extshellplusnet'
		)
	)
);

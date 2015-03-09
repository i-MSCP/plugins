<?php
/**
 * i-MSCP InstantSSH plugin
 * Copyright (C) 2014-2015 Laurent Declercq <l.declercq@nuxwin.com>
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

$config = iMSCP_Registry::get('config');

return array(
	// SSH user name prefix ( default: imscp_ )
	'ssh_user_name_prefix' => 'imscp_',

	// Passwordless authentication ( default: false )
	//
	// When the value is set to TRUE, passwordless authentication is enforced, meaning that the customers cannot set
	// password for their SSH users. This implies necessarily that the customers have to provide an SSH key. When the
	// value is set to FALSE, both authentication methods (password and key) are available. In such a case, customers
	// can provide either a password, either a key or both.
	//
	// Note: This applies only to newly created or updated SSH users
	'passwordless_authentication' => false,

	// Default SSH authentication options added for any new SSH key
	//
	// See man authorized_keys for list of allowed authentication options.
	// eg. command="dump /home",no-pty,no-port-forwarding
	//
	// WARNING: Any option defined here must be specified in the allowed_ssh_auth_options configuration option.
	'default_ssh_auth_options' => 'no-agent-forwarding,no-port-forwarding,no-X11-forwarding',

	// SSH authentication options that customers can define when they are allowed to override default.
	//
	// Supported options are:
	//
	// \InstantSSH\Validate\SshAuthOptions::ALL (all options)
	// \InstantSSH\Validate\SshAuthOptions::CERT_AUTHORITY ( cert-authority option )
	// \InstantSSH\Validate\SshAuthOptions::COMMAND ( command option )
	// \InstantSSH\Validate\SshAuthOptions::ENVIRONMENT ( environment option )
	// \InstantSSH\Validate\SshAuthOptions::FROM ( from option )
	// \InstantSSH\Validate\SshAuthOptions::NO_AGENT_FORWARDING ( no-agent-forwarding option )
	// \InstantSSH\Validate\SshAuthOptions::NO_PORT_FORWARDING ( no-port-forwarding option )
	// \InstantSSH\Validate\SshAuthOptions::NO_PTY ( no-pty option )
	// \InstantSSH\Validate\SshAuthOptions::NO_USER_RC ( no-user-rc option )
	// \InstantSSH\Validate\SshAuthOptions::NO_X11_FORWARDING ( no-x11-forwarding option )
	// \InstantSSH\Validate\SshAuthOptions::PERMITOPEN ( permitopen option )
	// \InstantSSH\Validate\SshAuthOptions::PRINCIPALS ( principals option )
	// \InstantSSH\Validate\SshAuthOptions::TUNNEL ( tunnel option )
	'allowed_ssh_auth_options' => array(
		\InstantSSH\Validate\SshAuthOptions::ALL
	),

	// Shell for SSH users ( default: /bin/bash for full SSH access ; /bin/ash for restricted SSH access )
	//
	// See man shells for further details.
	'shells' => array(
		// Shell for full SSH access
		'full' => '/bin/bash',

		// Shell for restricted SSH access
		'jailed' => '/bin/bash'
	),

	// Root jail directory ( default: /var/chroot/InstantSSH )
	//
	// Full path to the root jail directory. Be sure that the partition in which this directory is living has enough
	// space to host the jails.
	'root_jail_dir' => '/var/chroot/InstantSSH',

	// Makejail script path
	'makejail_path' => __DIR__ . '/bin/makejail',

	// Makejail configuration directory ( default: <CONF_DIR>/InstantSSH )
	'makejail_confdir_path' => $config['CONF_DIR'] . '/InstantSSH',

	// Shared jail ( default: true )
	//
	// When set to true, only one jail is created for all customers. A shared jail doesn't mean that customers will be
	// able to read, modify or delete files of other customers. This simply mean that the jail will be shared between
	// customers. The primary purpose of a jailed environment is to protect the main system. Having a jail for each
	// customer is interesting only when you want provide a different set of commands for each of them.
	//
	// Note: The creation of a jail per customer is currently useless because the per customer application feature is
	// not implemented yet. This will be implemented in near future.
	'shared_jail' => true,

	// Preserved files ( default: <USER_WEB_DIR> )
	//
	// The plugin won't try to remove files or directories inside jails if their path begins with one of the strings
	// in this list.
	//
	// This option can be also defined in the application sections ( see below ).
	//
	// WARNING: Do not remove the default entry if you don't know what you are doing.
	'preserve_files' => array(
		$config['USER_WEB_DIR']
	),

	// Whether or not files from packages listed in the 'packages' option of the application sections must be copied
	// within the jails
	'include_pkg_deps' => false,

	// Application sections ( default: 'bashshell', 'netutils', 'editors', 'mysqltools' )
	//
	// This is the list of application sections which are used to create/update the jails ( see below ).
	'app_sections' => array(
		'bashshell', 'netutils', 'editors', 'mysqltools'
	),

	// Application sections definitions
	//
	// Below, you can find the application sections for jailed shell environments. Those sections are used to create and
	// update the jails. You can select as many sections as you want by adding them into the app_sections configuration
	// option above.
	//
	// It is not recommended to change a section without understanding its meaning and how it is working. Once you know
	// how the sections are defined, you can define your own sections.
	//
	// Application section options
	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~
	//
	// The following options can be defined in application sections:
	//
	// paths: List of paths to create inside the jails.
	// create_dirs: List of directories to create inside jail where each key is a directory path and the value, an
	//              an associative array describing directory permissions ( user, group and mode ).
	// packages: List of debian packages. Files from those packages will be copied inside jail.
	// discard_packages: List of debian packages to discard. ( Only relevant if the global include_pkg_deps
	//                   configuration option is set to true ).
	// sys_copy_file_to: List of files to copy outside the jail, each of them specified as a key/value pair where the
	//                   key is the source file path and the value, the destination path.
	// jail_copy_file_to: List of files to copy inside jail, each of them specified as a key/value pair where the key is
	//                    the source file path and the value, the destination path.
	// include_apps_sections: List of applications sections to include.
	// users: List of users to add inside the jail ( eg. in passwd/shadow files ).
	// groups: List of groups to add inside the jail ( eg. in group/gshadow files ).
	// preserve_files: List of files to preserve when the jails are updated.
	// devices: List of devices to copy inside jail.
	// fstab: List of fstab entries to add where each value is an array describing an fstab entry ( see man fstab ).
	// create_sys_commands: List of commands to execute outside the jail once built or updated.
	// create_sys_commands_args: List of commands to execute outside the jail once built or updated. Any listed command
	//                           will receive the full jail path as argument.
	// destroy_sys_commands: List of command to execute outside jail before it get destroyed.
	// destroy_sys_commands_args: List of command to execute outside jail before it get destroyed. Any listed command
	//                            will receive the full jail path as argument.
	// create_jail_commands: List of commands to execute inside jail once built or updated.
	// create_jail_commands_args: List of commands to execute inside jail once built or updated. Any listed command will
	//                            receive the full jail path as argument.
	// destroy_jail_commands: List of commands to execute inside jail before it get destroyed.
	// destroy_jail_commands_args: List of commands to execute inside jail before it get destroyed. Any listed command
	//                             will receive the full jail path as argument.
	//
	// Notes:
	//  - The paths and devices options both support the glob patterns.
	//  - Directories specified in paths option are copied recursively.
	//  - Any path which doesn't exists on the system is ignored.
	//  - Any package listed in a package option must be already installed on the system, else an error is raised.
	//  - Any device must exists on the system, else an error is raised. You can use glob patterns to avoid error.
	//  - filesystems specified in the fstab option are mounted automatically inside jail.
	//  - Mount points defined in the fstab option must be specified without the jail root path.

	// common files for jails that need user/group information
	'uidbasics' => array(
		'paths' => array(
			'/etc/ld.so.conf', '/etc/passwd', '/etc/group', '/etc/nsswitch.conf',
			'/lib/libnsl.so.1', '/lib64/libnsl.so.1', '/lib/i386-linux-gnu/libnsl.so.1',
			'/lib/x86_64-linux-gnu/libnsl.so.1', '/lib/libnss*.so.2', '/lib64/libnss*.so.2',
			'/lib/i386-linux-gnu/libnss*.so.2', '/lib/x86_64-linux-gnu/libnss*.so.2',
		)
	),

	// common files for jails that need internet connectivity
	'netbasics' => array(
		'paths' => array(
			'/etc/resolv.conf', '/etc/host.conf', '/etc/hosts', '/etc/protocols', '/etc/services', '/etc/ssl/certs',
			'/lib/libnss_dns.so.2', '/lib64/libnss_dns.so.2', '/lib/i386-linux-gnu/libnss_dns.so.2',
			'/lib/x86_64-linux-gnu/libnss_dns.so.2'
		)
	),

	// timezone information and log sockets
	'logbasics' => array(
		'paths' => array(
			'/etc/localtime'
		),
		'create_dir' => array(
			'/dev/log' => array(
				'user' => 'root',
				'group' => 'root',
				'mode' => 0755
			)
		),
		'preserve_files' => array(
			'/dev/log'
		),
		'create_sys_commands_args' => array(
			$config['CMD_PERL'] . ' ' . __DIR__ . '/bin/syslogproxyd add'
		),
		'destroy_sys_commands_args' => array(
			$config['CMD_PERL'] . ' ' . __DIR__ . '/bin/syslogproxyd remove'
		)
	),

	// restricted ash shell ( BusyBox built-in shell and common UNIX utilities )
	// Warning: Don't forget to set the shells => jailed configuration option to /bin/ash
	'ashshell' => array(
		'users' => array(
			'root', 'www-data'
		),
		'groups' => array(
			'root', 'www-data'
		),
		'paths' => array(
			'ash', 'busybox', 'dircolors', 'false', 'tput', '/etc/localtime', '/etc/timezone', '/usr/lib/locale/C.UTF-8'
		),
		'create_dirs' => array(
			'/tmp' => array(
				'user' => 'root',
				'group' => 'root',
				'mode' => 01777
			),
			'/var/log' => array(
				'user' => 'root',
				'group' => 'root',
				'mode' => 0755
			)
		),
		'jail_copy_file_to' => array(
			__DIR__ . '/config/etc/motd' => '/etc/motd',
			__DIR__ . '/config/etc/profile' => '/etc/profile'
		),
		'include_app_sections' => array(
			'busybox', 'uidbasics', 'logbasics', 'terminfo'
		),
		'devices' => array(
			'/dev/null', '/dev/ptmx', '/dev/random', '/dev/urandom', '/dev/zero'
		),
		'fstab' => array(
			array(
				'file_system' => '/proc',
				'mount_point' => '/proc',
				'type' => 'proc',
				'options' => 'bind',
				'dump' => '0',
				'pass' => '0'
			),
			array(
				'file_system' => '/dev/pts',
				'mount_point' => '/dev/pts',
				'type' => 'devpts',
				'options' => 'bind',
				'dump' => '0',
				'pass' => '0'
			),
			array(
				'file_system' => '/var/log/lastlog',
				'mount_point' => '/var/log/lastlog',
				'type' => 'auto',
				'options' => 'bind',
				'dump' => '0',
				'pass' => '0'
			)
		)
	),

	// Provide restricted GNU bash shell
	// Warning: Don't forget to set the shells => jailed configuration option to /bin/bash
	'bashshell' => array(
		'users' => array(
			'root', 'www-data'
		),
		'groups' => array(
			'root', 'www-data'
		),
		'paths' => array(
			'sh', 'bash', 'ls', 'cat', 'chmod', 'mkdir', 'cp', 'cpio', 'date', 'dd', 'echo', 'egrep', 'false', 'fgrep',
			'grep', 'gunzip', 'gzip', 'ln', 'mktemp', 'more', 'mv', 'pwd', 'rm', 'rmdir', 'sed', 'sleep', 'sync', 'tar',
			'basename', 'touch', 'true', 'uncompress', 'zcat', '/etc/issue', '/etc/bash.bashrc', 'dircolors', 'tput',
			'awk', 'bzip2', 'bunzip2', 'ldd', 'less', 'clear', 'cut', 'du', 'find', 'head', 'md5sum', 'nice', 'sort',
			'tac', 'tail', 'tr', 'wc', 'watch', 'whoami', 'id', 'hostname', 'lzma', 'xz', 'pbzip2', 'env', 'readlink',
			'groups', '/etc/localtime', '/etc/timezone', '/usr/lib/locale/C.UTF-8'
		),
		'create_dirs' => array(
			'/tmp' => array(
				'user' => 'root',
				'group' => 'root',
				'mode' => 01777
			),
			'/var/log' => array(
				'user' => 'root',
				'group' => 'root',
				'mode' => 0755
			)
		),
		'jail_copy_file_to' => array(
			__DIR__ . '/config/etc/motd' => '/etc/motd',
			__DIR__ . '/config/etc/profile' => '/etc/profile'
		),
		'include_app_sections' => array(
			'uidbasics', 'logbasics', 'terminfo'
		),
		'devices' => array(
			'/dev/null', '/dev/ptmx', '/dev/random', '/dev/urandom', '/dev/zero'
		),
		'fstab' => array(
			array(
				'file_system' => '/proc',
				'mount_point' => '/proc',
				'type' => 'proc',
				'options' => 'bind',
				'dump' => '0',
				'pass' => '0'
			),
			array(
				'file_system' => '/dev/pts',
				'mount_point' => '/dev/pts',
				'type' => 'devpts',
				'options' => 'bind',
				'dump' => '0',
				'pass' => '0'
			),
			array(
				'file_system' => '/var/log/lastlog',
				'mount_point' => '/var/log/lastlog',
				'type' => 'auto',
				'options' => 'bind',
				'dump' => '0',
				'pass' => '0'
			)
		)
	),

	// Provide curl, wget, lynx, ftp, ssh, sftp, scp, rsync,
	'netutils' => array(
		'paths' => array(
			'curl', 'ftp', 'lynx', 'wget', '/etc/lynx-cur'
		),
		'include_app_sections' => array(
			'netbasics', 'scp', 'sftp', 'ssh', 'rsync'
		),
		'devices' => array(
			'/dev/random', '/dev/urandom'
		)
	),

	# ssh secure copy
	'scp' => array(
		'paths' => array(
			'scp'
		),
		'include_app_sections' => array(
			'netbasics'
		),
		'devices' => array(
			'/dev/urandom'
		)
	),

	# ssh secure ftp
	'sftp' => array(
		'paths' => array(
			'sftp', '/usr/lib/sftp-server', '/usr/lib/openssh/sftp-server'
		),
		'include_app_sections' => array(
			'netbasics'
		),
		'devices' => array(
			'/dev/urandom', '/dev/null'
		)
	),

	# ssh secure shell
	'ssh' => array(
		'paths' => array(
			'ssh'
		),
		'include_app_sections' => array(
			'netbasics'
		),
		'devices' => array(
			'/dev/urandom', '/dev/tty', '/dev/null'
		)
	),

	# rsync
	'rsync' => array(
		'paths' => array(
			'rsync'
		),
		'include_app_sections' => array(
			'uidbasics', 'netbasics'
		)
	),

	# MySQL command-line tools ( mysql, mysqldump )
	'mysqltools' => array(
		'paths' => array(
			'/etc/mysql', '/usr/bin/mysql', '/usr/bin/mysqldump', '/lib/libgcc_s.so.1',
			'/lib/i386-linux-gnu/libgcc_s.so.1', '/lib64/libgcc_s.so.1', '/lib/x86_64-linux-gnu/libgcc_s.so.1'
		),
		'jail_copy_file_to' => array(
			__DIR__ . '/config/etc/mysql/my.cnf' => '/etc/mysql/my.cnf'
		)
	),

	// common editors
	'editors' => array(
		'paths' => array(
			'joe', 'nano', 'vi', 'vim', '/etc/vim', '/etc/vimrc', '/etc/joe', '/usr/share/vim',
			'/etc/nanorc', '/usr/share/nano'
		),
		'include_app_sections' => array(
			'terminfo'
		)
	),

	// terminfo databases
	'terminfo' => array(
		'paths' => array(
			'/etc/terminfo', '/lib/terminfo', '/usr/share/terminfo'
		)
	),

	# PHP (CLI)
	'php' => array(
		'paths' => array(
			'php', '/etc/php5/cli/php.ini', '/etc/php5/cli/conf.d/*', PHP_EXTENSION_DIR, '/usr/share/zoneinfo'
		),
		'include_app_sections' => array(
			'netutils'
		)
	),

	# composer ( see https://getcomposer.org )
	'composer' => array(
		'paths' => array(
			'/usr/local/bin'
		),
		'include_app_sections' => array(
			'php'
		),
		'create_jail_commands' => array(
			"curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin",
			'mv /usr/local/bin/composer.phar /usr/local/bin/composer'
		)
	)
);

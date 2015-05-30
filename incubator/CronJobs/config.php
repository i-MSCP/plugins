<?php
/**
 * i-MSCP CronJobs plugin
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
$pluginDirectory = iMSCP_Registry::get('pluginManager')->pluginGetDirectory();

return array(
	// Path to the crontab command (default: /usr/bin/crontab)
	//
	// WARNING: Don't change this parameter unless you know what you are doing.
	'crontab_cmd_path' => '/usr/bin/crontab',

	// Path to crontab directory (default: /var/spool/cron/crontabs)
	//
	// WARNING: Don't change this parameter unless you know what you are doing.
	'crontab_dir' => '/var/spool/cron/crontabs',

	// Root jail directory (default: /var/chroot/CronJobs)
	//
	// Full path to the root jail directory. Be sure that the partition in which this directory is living has enough
	// space to host the jail.
	//
	// WARNING: Don't change this parameter unless you know what you are doing.
	'root_jail_dir' => '/var/chroot/CronJobs',

	// Makejail configuration directory (default: <CONF_DIR>/CronJobs)
	//
	// WARNING: Don't change this parameter unless you know what you are doing.
	'makejail_confdir_path' => $config['CONF_DIR'] . '/CronJobs',

	// Makejail script path
	//
	// WARNING: Don't change this parameter unless you know what you are doing.
	'makejail_path' => $pluginDirectory . '/InstantSSH/bin/makejail',

	// Preserved files (default: <USER_WEB_DIR>)
	//
	// The plugin won't try to remove files or directories inside jail if their path begins with one of the strings in
	// this list.
	//
	// This option can be also defined in the application sections (see below).
	//
	// WARNING: Do not remove the default entry unless you know what you are doing.
	'preserve_files' => array(
		$config['USER_WEB_DIR']
	),

	// Whether or not files from packages required by packages listed in the packages option must be copied inside the
	// jail
	'include_pkg_deps' => false,

	// Application sections (default: 'bashshell', 'cron', 'netutils', 'mysqltools', 'php')
	//
	// This is the list of application sections which are used to create/update the jail (see below).
	'app_sections' => array(
		'bashshell', 'cron', 'netutils', 'mysqltools', 'php'
	),

	// Application sections definitions
	// See the InstantSSH plugin configuration file for more details.

	// common files for jails that need user/group information
	'uidbasics' => array(
		'paths' => array(
			'/etc/ld.so.conf', '/etc/passwd', '/etc/group', '/etc/nsswitch.conf', '/lib/libnsl.so.1',
			'/lib64/libnsl.so.1', '/lib/i386-linux-gnu/libnsl.so.1', '/lib/x86_64-linux-gnu/libnsl.so.1',
			'/lib/libnss*.so.2', '/lib64/libnss*.so.2', '/lib/i386-linux-gnu/libnss*.so.2',
			'/lib/x86_64-linux-gnu/libnss*.so.2',
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
			'/etc/localtime', '/etc/timezone'
		),
		'create_dirs' => array(
			'/dev' => array(
				'user' => 'root',
				'group' => 'root',
				'mode' => 0755
			)
		),
		'preserve_files' => array(
			'/dev/log'
		),
		'create_sys_commands_args' => array(
			'perl ' . $pluginDirectory . '/InstantSSH/bin/syslogproxyd add'
		),
		'destroy_sys_commands_args' => array(
			'perl ' . $pluginDirectory . '/InstantSSH/bin/syslogproxyd remove'
		)
	),

	// restricted GNU bash shell
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
			'groups', '/usr/lib/locale/C.UTF-8'
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
			$pluginDirectory . '/InstantSSH/config/etc/profile' => '/etc/profile'
		),
		'include_app_sections' => array(
			'uidbasics', 'logbasics', 'terminfo'
		),
		'devices' => array(
			'/dev/null', '/dev/ptmx', '/dev/random', '/dev/urandom', '/dev/zero'
		),
		'fstab' => array(
			array(
				'file_system' => 'devpts',
				'mount_point' => '/dev/pts',
				'type' => 'devpts',
				'options' => 'gid=5,mode=620',
				'dump' => '0',
				'pass' => '0'
			),
			array(
				'file_system' => 'proc',
				'mount_point' => '/proc',
				'type' => 'proc',
				'options' => 'defaults',
				'dump' => '0',
				'pass' => '0'
			),
			array(
				'file_system' => 'sysfs',
				'mount_point' => '/sys',
				'type' => 'sysfs',
				'options' => 'defaults',
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
		),
		'destroy_sys_commands_args' => array(
			'perl ' . $pluginDirectory . '/InstantSSH/bin/dovecot_rm_mount /var/log/lastlog',
			'perl ' . $pluginDirectory . '/InstantSSH/bin/dovecot_rm_mount ' . $config['USER_WEB_DIR'] . '/*'
		)
	),

	// Provide curl, wget, lynx, ftp, ssh, sftp, scp, rsync
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

	// ssh secure copy
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

	// ssh secure ftp
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

	// ssh secure shell
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

	// rsync
	'rsync' => array(
		'paths' => array(
			'rsync'
		),
		'include_app_sections' => array(
			'uidbasics', 'netbasics'
		)
	),

	// MySQL command-line tools (mysql, mysqldump)
	'mysqltools' => array(
		'paths' => array(
			'mysql', 'mysqldump', '/lib/libgcc_s.so.1', '/lib/i386-linux-gnu/libgcc_s.so.1', '/lib64/libgcc_s.so.1',
			'/lib/x86_64-linux-gnu/libgcc_s.so.1'
		),
		'create_dirs' => array(
			'/etc/mysql' => array(
				'user' => 'root',
				'group' => 'root',
				'mode' => 0755
			)
		),
		'jail_copy_file_to' => array(
			$pluginDirectory . '/InstantSSH/config/etc/mysql/my.cnf' => '/etc/mysql/my.cnf'
		)
	),

	// terminfo databases
	'terminfo' => array(
		'paths' => array(
			'/etc/terminfo', '/lib/terminfo', '/usr/share/terminfo'
		)
	),

	// PHP (CLI) and extensions
	'php' => array(
		'paths' => array(
			'php', '/etc/php5/cli', PHP_EXTENSION_DIR, '/usr/share/zoneinfo'
		),
		'include_app_sections' => array(
			'netutils'
		)
	),

	// msmtp (light SMTP client with support for server profiles)
	'msmtp' => array(
		'paths' => array(
			'/etc/aliases', 'msmtp'
		),
		'jail_copy_file_to' => array(
			__DIR__ . '/config/etc/msmtprc' => '/etc/msmtprc'
		),
		'create_jail_commands' => array(
			'sed -i\'\' -e \'s/{HOSTNAME}/\'$(hostname -f)\'/\' /etc/msmtprc', // Setup maildomain in msmtp conffile
			'ln -s /usr/bin/msmtp /usr/sbin/sendmail' // Use the msmtp SMTP client as sendmail interface inside jail
		)
	),

	// cron
	'cron' => array(
		'paths' => array(
			'cron'
		),
		'include_app_sections' => array(
			'msmtp'
		)
	)
);

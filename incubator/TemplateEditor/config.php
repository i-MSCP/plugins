<?php
/**
 * i-MSCP TemplateEditor plugin
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
	// Service template definitions
	// Warning: imscp unix group must have read access to all file paths defined below.
	// Do not change anything if you don't know what you are doing.
	'service_templates' => array(
		'apache_fcgid' => array(
			'system' => array(
				'00_master.conf' => '/etc/imscp/apache/00_master.conf',
				'00_master_ssl.conf' => '/etc/imscp/apache/00_master_ssl.conf',
				'00_nameserver_conf' => '/etc/imscp/apache/00_master_ssl.conf',
				'fcgid_imscp.conf' => '/etc/imscp/apache/fcgid_imscp.conf',
				'logrotate.conf' => '/etc/imscp/apache/logrotate.conf',
				'vlogger.conf.tpl' => '/etc/imscp/apache/vlogger.conf.tpl',
				'vlogger.sql' => '/etc/imscp/apache/vlogger.sql',
				'php.ini' => '/etc/imscp/fcgi/parts/master/php5/php.ini',
				'php5-fcgid-starter.tpl' => '/etc/imscp/fcgi/parts/master/php5-fcgid-starter.tpl',
			),
			'site' => array(
				'custom.conf.tpl' => '/etc/imscp/apache/parts/custom.conf.tpl',
				'domain.tpl' => '/etc/imscp/apache/parts/domain.tpl',
				'domain_disabled.tpl' => '/etc/imscp/apache/parts/domain_disabled.tpl',
				'domain_disabled_ssl.tpl' => '/etc/imscp/apache/parts/domain_disabled_ssl.tpl',
				'domain_redirect.tpl' => '/etc/imscp/apache/parts/domain_redirect.tpl',
				'domain_redirect_ssl.tpl' => '/etc/imscp/apache/parts/domain_redirect_ssl.tpl',
				'domain_ssl.tpl' => '/etc/imscp/apache/parts/domain_ssl.tpl',
				'php.ini' => '/etc/imscp/fcgi/parts/php5/php.ini',
				'php5-fcgid-starter.tpl' => '/etc/imscp/fcgi/parts/php5-fcgid-starter.tpl',
			)
		),
		'apache_itk' => array(
			'system' => array(
				'00_master.conf' => '/etc/imscp/apache/00_master.conf',
				'00_master_ssl.conf' => '/etc/imscp/apache/00_master_ssl.conf',
				'00_nameserver_conf' => '/etc/imscp/apache/00_master_ssl.conf',
				'logrotate.conf' => '/etc/imscp/apache/logrotate.conf',
				'vlogger.conf.tpl' => '/etc/imscp/apache/vlogger.conf.tpl',
				'vlogger.sql' => '/etc/imscp/apache/vlogger.sql',
			),
			'site' => array(
				'custom.conf.tpl' => '/etc/imscp/apache/parts/custom.conf.tpl',
				'domain.tpl' => '/etc/imscp/apache/parts/domain.tpl',
				'domain_disabled.tpl' => '/etc/imscp/apache/parts/domain_disabled.tpl',
				'domain_disabled_ssl.tpl' => '/etc/imscp/apache/parts/domain_disabled_ssl.tpl',
				'domain_redirect.tpl' => '/etc/imscp/apache/parts/domain_redirect.tpl',
				'domain_redirect_ssl.tpl' => '/etc/imscp/apache/parts/domain_redirect_ssl.tpl',
				'domain_ssl.tpl' => '/etc/imscp/apache/parts/domain_ssl.tpl',
			)
		),
		'apache_php_fpm' => array(
			'system' => array(
				'00_master.conf' => '/etc/imscp/apache/00_master.conf',
				'00_master_ssl.conf' => '/etc/imscp/apache/00_master_ssl.conf',
				'00_nameserver_conf' => '/etc/imscp/apache/00_master_ssl.conf',
				'fcgid_imscp.conf' => '/etc/imscp/apache/fcgid_imscp.conf',
				'logrotate.conf' => '/etc/imscp/apache/logrotate.conf',
				'vlogger.conf.tpl' => '/etc/imscp/apache/vlogger.conf.tpl',
				'vlogger.sql' => '/etc/imscp/apache/vlogger.sql',
				'php-fpm.conf' => '/etc/imscp/php-fpm/php-fpm.conf',
				'php_fpm_imscp.conf' => '/etc/imscp/php-fpm/php_fpm_imscp.conf',
				'php_fpm_imscp.load' => '/etc/imscp/php-fpm/php_fpm_imscp.load',
				'php5.ini' => '/etc/imscp/php-fpm/parts/php5.ini',
				'pool.conf' => '/etc/imscp/php-fpm/parts/master/pool.conf',
			),
			'site' => array(
				'custom.conf.tpl' => '/etc/imscp/apache/parts/custom.conf.tpl',
				'domain.tpl' => '/etc/imscp/apache/parts/domain.tpl',
				'domain_disabled.tpl' => '/etc/imscp/apache/parts/domain_disabled.tpl',
				'domain_disabled_ssl.tpl' => '/etc/imscp/apache/parts/domain_disabled_ssl.tpl',
				'domain_redirect.tpl' => '/etc/imscp/apache/parts/domain_redirect.tpl',
				'domain_redirect_ssl.tpl' => '/etc/imscp/apache/parts/domain_redirect_ssl.tpl',
				'domain_ssl.tpl' => '/etc/imscp/apache/parts/domain_ssl.tpl',
				'pool.conf' => '/etc/imscp/php-fpm/parts/pool.conf'
			)
		),
		'bind' => array(
			'system' => array(
				'named.conf' => '/etc/imscp/bind/named.conf',
				'named.conf.local' => '/etc/imscp/bind/named.conf.local',
				'named.conf.options' => '/etc/imscp/bind/named.conf.options'
			),
			'site' => array(
				'cfg_master.tpl' => '/etc/imscp/bind/parts/cfg_master.tpl',
				'cfg_slave.tpl' => '/etc/imscp/bind/parts/cfg_slave.tpl',
				'db.tpl' => '/etc/imscp/bind/parts/db.tpl',
				'db_sub.tpl' => '/etc/imscp/bind/parts/db_sub.tpl',
			)
		),
		'courier' => array(
			'system' => array(
				'authmysqlrc' => '/etc/imscp/courier/authmysqlrc',
				'quota-warning' => '/etc/imscp/courier/quota-warning'
			)
		),
		'dovecot' => array(
			'system' => array(
				'dovecot.conf.1' => '/etc/imscp/dovecot/dovecot.conf.1',
				'dovecot.conf.2' => '/etc/imscp/dovecot/dovecot.conf.2',
				'dovecot-sql.conf' => '/etc/imscp/dovecot/dovecot-sql.conf',
				'quota-warning.1' => '/etc/imscp/dovecot/quota-warning.1',
				'quota-warning.2' => '/etc/imscp/dovecot/quota-warning.2'
			)
		),
		# Only relevant for i-MSCP version >= 1.2.x
		'frontend' => array(
			'system' => array(
				'00_master.conf' => '/etc/imscp/nginx/00_master.conf',
				'00_master_ssl.conf' => '/etc/imscp/nginx/00_master_ssl.conf',
				'imscp_fastcgi.conf' => '/etc/imscp/nginx/imscp_fastcgi.conf',
				'imscp_php.conf' => '/etc/imscp/nginx/imscp_php.conf',
				'nginx.conf' => '/etc/imscp/nginx/nginx.conf',
				'php.ini' => '/etc/imscp/nginx/parts/master/php5/php.ini',
				'php5-fcgi-starter.tpl' => '/etc/imscp/nginx/parts/master/php5-fcgi-starter.tpl'
			)
		),
		'mysql' => array(
			'system' => array(
				'imscp.cnf' => '/etc/imscp/mysql/imscp.cnf'
			)
		),
		'postfix' => array(
			'system' => array(
				'main.cf' => '/etc/imscp/postfix/main.cf',
				'master.cf' => '/etc/imscp/postfix/master.cf'
			)
		),
		'proftpd' => array(
			'system' => array(
				'proftpd.conf' => '/etc/imscp/proftpd/proftpd.conf'
			)
		)
	),

	// Whether or not default templates must be synced with last available versions on update
	'sync_default_templates' => true,

	// Allow to use memcached server for better performances
	// Default is disabled. See the README.md file for instructions.
	//'memcached' => array(
	//	'enabled' => false,
	//	'hostname' => 'localhost',
	//	'port' => '11211'
	//),
);

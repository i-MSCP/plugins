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

$confDir = iMSCP_Registry::get('config')->CONF_DIR;

return array(
	// Default template group definitions
	//
	// Warning: imscp unix group must have read access to all file paths defined below.
	//
	// Do not change anything if you don't know what you are doing.
	//
	// Structure is as follow:
	//
	// default_template_groups => array(
	//     '<service_name (as provided by the onLoadTemplate event)>' => array(
	//         '<scope (either system or site)>' => array(
	//             '<template name (as provided by the onLoadTemplate event)>' => array(
	//                 'template_ path => '<template file_path>',
	//                 'template_type => '<codemirror mode>'
	//             )
	//         ),
	//         ...
	//     ),
	//     ...
	//  ):
 	//
	// Note: The 'template_type' parameter map to the 'mode' parameter from CodeMirror. This is used to tell CodeMirror
	// which mode to use for Syntax highlighting. The 'none' value mean that no mode is available.
	'default_template_groups' => array(
		'apache_fcgid' => array(
			'system' => array(
				'00_master.conf' => array(
					'template_path' => "$confDir/apache/00_master.conf",
					'template_type' => 'none'
				),
				'00_master_ssl.conf' => array(
					'template_path' => "$confDir/apache/00_master_ssl.conf",
					'template_type' => 'none'
				),
				'00_nameserver.conf' => array(
					'template_path' => "$confDir/apache/00_nameserver.conf",
					'template_type' => 'none'
				),
				'fcgid_imscp.conf' => array(
					'template_path' => "$confDir/apache/fcgid_imscp.conf",
					'template_type' => 'none'
				),
				'logrotate.conf' => array(
					'template_path' => "$confDir/apache/logrotate.conf",
					'template_type' => 'none'
				),
				'vlogger.conf.tpl' => array(
					'template_path' => "$confDir/apache/vlogger.conf.tpl",
					'template_type' => 'none'
				),
				'php.ini' => array(
					'template_path' => "$confDir/fcgi/parts/master/php5/php.ini",
					'template_type' => 'properties'
				),
				'php5-fcgid-starter.tpl' => array(
					'template_path' => "$confDir/fcgi/parts/master/php5-fcgid-starter.tpl",
					'template_type' => 'shell'
				)
			),
			'site' => array(
				'custom.conf.tpl' => array(
					'template_path' => "$confDir/apache/parts/custom.conf.tpl",
					'template_type' => 'none'
				),
				'domain.tpl' => array(
					'template_path' => "$confDir/apache/parts/domain.tpl",
					'template_type' => 'none'
				),
				'domain_disabled.tpl' => array(
					'template_path' => "$confDir/apache/parts/domain_disabled.tpl",
					'template_type' => 'none'
				),
				'domain_disabled_ssl.tpl' => array(
					'template_path' => "$confDir/apache/parts/domain_disabled_ssl.tpl",
					'template_type' => 'none'
				),
				'domain_redirect.tpl' => array(
					'template_path' => "$confDir/apache/parts/domain_redirect.tpl",
					'template_type' => 'none'
				),
				'domain_redirect_ssl.tpl' => array(
					'template_path' => "$confDir/apache/parts/domain_redirect_ssl.tpl",
					'template_type' => 'none'
				),
				'domain_ssl.tpl' => array(
					'template_path' => "$confDir/apache/parts/domain_ssl.tpl",
					'template_type' => 'none'
				),
				'php.ini' => array(
					'template_path' => "$confDir/fcgi/parts/php5/php.ini",
					'template_type' => 'properties'
				),
				'php5-fcgid-starter.tpl' => array(
					'template_path' => "$confDir/fcgi/parts/php5-fcgid-starter.tpl",
					'template_type' => 'shell'
				)
			)
		),
		'apache_itk' => array(
			'system' => array(
				'00_master.conf' => array(
					'template_path' => "$confDir/apache/00_master.conf",
					'template_type' => 'none'
				),
				'00_master_ssl.conf' => array(
					'template_path' => "$confDir/apache/00_master_ssl.conf",
					'template_type' => 'none'
				),
				'00_nameserver.conf' => array(
					'template_path' => "$confDir/apache/00_nameserver.conf",
					'template_type' => 'none'
				),
				'logrotate.conf' => array(
					'template_path' => "$confDir/apache/logrotate.conf",
					'template_type' => 'none'
				),
				'vlogger.conf.tpl' => array(
					'template_path' => "$confDir/apache/vlogger.conf.tpl",
					'template_type' => 'none'
				)
			),
			'site' => array(
				'custom.conf.tpl' => array(
					'template_path' => "$confDir/apache/parts/custom.conf.tpl",
					'template_type' => 'none'
				),
				'domain.tpl' => array(
					'template_path' => "$confDir/apache/parts/domain.tpl",
					'template_type' => 'none'
				),
				'domain_disabled.tpl' => array(
					'template_path' => "$confDir/apache/parts/domain_disabled.tpl",
					'template_type' => 'none'
				),
				'domain_disabled_ssl.tpl' => array(
					'template_path' => "$confDir/apache/parts/domain_disabled_ssl.tpl",
					'template_type' => 'none'
				),
				'domain_redirect.tpl' => array(
					'template_path' => "$confDir/apache/parts/domain_redirect.tpl",
					'template_type' => 'none'
				),
				'domain_redirect_ssl.tpl' => array(
					'template_path' => "$confDir/apache/parts/domain_redirect_ssl.tpl",
					'template_type' => 'none'
				),
				'domain_ssl.tpl' => array(
					'template_path' => "$confDir/apache/parts/domain_ssl.tpl",
					'template_type' => 'none'
				)
			)
		),
		'apache_php_fpm' => array(
			'system' => array(
				'00_master.conf' => array(
					'template_path' => "$confDir/apache/00_master.conf",
					'template_type' => 'none'
				),
				'00_master_ssl.conf' => array(
					'template_path' => "$confDir/apache/00_master_ssl.conf",
					'template_type' => 'none'
				),
				'00_nameserver.conf' => array(
					'template_path' => "$confDir/apache/00_nameserver.conf",
					'template_type' => 'none'
				),
				'fcgid_imscp.conf' => array(
					'template_path' => "$confDir/apache/fcgid_imscp.conf",
					'template_type' => 'none'
				),
				'logrotate.conf' => array(
					'template_path' => "$confDir/apache/logrotate.conf",
					'template_type' => 'none'
				),
				'vlogger.conf.tpl' => array(
					'template_path' => "$confDir/apache/vlogger.conf.tpl",
					'template_type' => 'none'
				),
				'php-fpm.conf' => array(
					'template_path' => "$confDir/php-fpm/php-fpm.conf",
					'template_type' => 'properties'
				),
				'php_fpm_imscp.conf' => array(
					'template_path' => "$confDir/php-fpm/php_fpm_imscp.conf",
					'template_type' => 'none'
				),
				'php_fpm_imscp.load' => array(
					'template_path' => "$confDir/php-fpm/php_fpm_imscp.load",
					'template_type' => 'none'
				),
				'php5.ini' => array(
					'template_path' => "$confDir/php-fpm/parts/php5.ini",
					'template_type' => 'properties'
				),
				'pool.conf' => array(
					'template_path' => "$confDir/php-fpm/parts/master/pool.conf",
					'template_type' => 'properties'
				)
			),
			'site' => array(
				'custom.conf.tpl' => array(
					'template_path' => "$confDir/apache/parts/custom.conf.tpl",
					'template_type' => 'none'
				),
				'domain.tpl' => array(
					'template_path' => "$confDir/apache/parts/domain.tpl",
					'template_type' => 'none'
				),
				'domain_disabled.tpl' => array(
					'template_path' => "$confDir/apache/parts/domain_disabled.tpl",
					'template_type' => 'none'
				),
				'domain_disabled_ssl.tpl' => array(
					'template_path' => "$confDir/apache/parts/domain_disabled_ssl.tpl",
					'template_type' => 'none'
				),
				'domain_redirect.tpl' => array(
					'template_path' => "$confDir/apache/parts/domain_redirect.tpl",
					'template_type' => 'none'
				),
				'domain_redirect_ssl.tpl' => array(
					'template_path' => "$confDir/apache/parts/domain_redirect_ssl.tpl",
					'template_type' => 'none'
				),
				'domain_ssl.tpl' => array(
					'template_path' => "$confDir/apache/parts/domain_ssl.tpl",
					'template_type' => 'none'
				),
				'pool.conf' => array(
					'template_path' => "$confDir/php-fpm/parts/pool.conf",
					'template_type' => 'properties'
				)
			)
		),
		'bind' => array(
			'system' => array(
				'named.conf' => array(
					'template_path' => "$confDir/bind/named.conf",
					'template_type' => 'none'
				),
				'named.conf.local' => array(
					'template_path' => "$confDir/bind/named.conf.local",
					'template_type' => 'none'
				),
				'named.conf.options' => array(
					'template_path' => "$confDir/bind/named.conf.options",
					'template_type' => 'none'
				)
			),
			'site' => array(
				'cfg_master.tpl' => array(
					'template_path' => "$confDir/bind/parts/cfg_master.tpl",
					'template_type' => 'none'
				),
				'cfg_slave.tpl' => array(
					'template_path' => "$confDir/bind/parts/cfg_slave.tpl",
					'template_type' => 'none'
				),
				'db.tpl' => array(
					'template_path' => "$confDir/bind/parts/db.tpl",
					'template_type' => 'none'
				),
				'db_sub.tpl' => array(
					'template_path' => "$confDir/bind/parts/db_sub.tpl",
					'template_type' => 'none'
				)
			)
		),
		'courier' => array(
			'system' => array(
				'authmysqlrc' => array(
					'template_path' => "$confDir/courier/authmysqlrc",
					'template_type' => 'none'
				),
				'quota-warning' => array(
					'template_path' => "$confDir/courier/quota-warning",
					'template_type' => 'properties'
				)
			)
		),
		'dovecot' => array(
			'system' => array(
				'dovecot.conf.1' => array(
					'template_path' => "$confDir/dovecot/dovecot.conf.1",
					'template_type' => 'none'
				),
				'dovecot.conf.2' => array(
					'template_path' => "$confDir/dovecot/dovecot.conf.2",
					'template_type' => 'none'
				),
				'dovecot-sql.conf' => array(
					'template_path' => "$confDir/dovecot/dovecot-sql.conf",
					'template_type' => 'properties'
				),
				'quota-warning.1' => array(
					'template_path' => "$confDir/dovecot/quota-warning.1",
					'template_type' => 'shell'
				),
				'quota-warning.2' => array(
					'template_path' => "$confDir/dovecot/quota-warning.2",
					'template_type' => 'shell'
				),
			)
		),
		# Only relevant for i-MSCP version >= 1.2.x
		'frontend' => array(
			'system' => array(
				'00_master.conf' => array(
					'template_path' => "$confDir/nginx/00_master.conf",
					'template_type' => 'nginx'
				),
				'00_master_ssl.conf' => array(
					'template_path' => "$confDir/nginx/00_master_ssl.conf",
					'template_type' => 'nginx'
				),
				'imscp_fastcgi.conf' => array(
					'template_path' => "$confDir/nginx/imscp_fastcgi.conf",
					'template_type' => 'nginx'
				),
				'imscp_php.conf' => array(
					'template_path' => "$confDir/nginx/imscp_php.conf",
					'template_type' => 'nginx'
				),
				'nginx.conf' => array(
					'template_path' => "$confDir/nginx/nginx.conf",
					'template_type' => 'nginx'
				),
				'php.ini' => array(
					'template_path' => "$confDir/nginx/parts/master/php5/php.ini",
					'template_type' => 'properties'
				),
				'php5-fcgi-starter.tpl' => array(
					'template_path' => "$confDir/nginx/parts/master/php5-fcgi-starter.tpl",
					'template_type' => 'shell'
				),
			)
		),
		'mysql' => array(
			'system' => array(
				'.my.cnf' => array(
					'template_path' => "$confDir/mysql/.my.cnf",
					'template_type' => 'properties'
				),
				'imscp.cnf' => array(
					'template_path' => "$confDir/mysql/imscp.cnf",
					'template_type' => 'properties'
				)
			)
		),
		'postfix' => array(
			'system' => array(
				'main.cf' => array(
					'template_path' => "$confDir/postfix/main.cf",
					'template_type' => 'properties'
				),
				'master.cf' => array(
					'template_path' => "$confDir/postfix/master.cf",
					'template_type' => 'properties'
				)
			)
		),
		'proftpd' => array(
			'system' => array(
				'proftpd.conf' => array(
					'template_path' => "$confDir/proftpd/proftpd.conf",
					'template_type' => 'none'
				)
			)
		)
	),

	// Whether or not default template groups must be synced
	// Default template groups are those defined in the default_template_groups configuration parameter (see above).
	// When set to false, the templates which belong to the default template groups will not be updated (not recommended)
	// Default: true
	'sync_default_template_groups' => true,

	// Whether or not configuration files which belong to inactive services must be hidden
	// Default: true
	'hide_inactive_service_conffiles' => true,

	// Allow to use memcached server for better performances
	// Default is disabled. See the README.md file for instructions.
	//'memcached' => array(
	//	'enabled' => false,
	//	'hostname' => 'localhost',
	//	'port' => '11211'
	//),
);

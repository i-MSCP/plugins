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
	// Service template definitions.
	'templates' => array(
		// Bind9 Templates
		'bind9' => array(
			'global' => array(
				'named.conf' => array(
					'path' => '/etc/imscp/bind/named.conf',
					'scope' => 'system'
				),
				'named.conf.local' => array(
					'path' => '/etc/imscp/bind/named.conf.local',
					'scope' => 'system'
				),
				'named.conf.options' => array(
					'path' => '/etc/imscp/bind/named.conf.options',
					'scope' => 'system'
				)
			),
			'master_zone' => array(
				'cfg_master.tpl' => array(
					'path' => '/etc/imscp/bind/parts/cfg_master.tpl',
					'scope' => 'site'
				),
				'db.tpl' => array(
					'path' => '/etc/imscp/bind/parts/db.tpl',
					'scope' => 'site'
				),
				'db_sub.tpl' => array(
					'path' => '/etc/imscp/bind/parts/db_sub.tpl',
					'scope' => 'site'
				)
			),
			'slave_zone' => array(
				'cfg_slave.tpl' => array(
					'path' => '/etc/imscp/bind/parts/cfg_slave.tpl',
					'scope' => 'site'
				)
			)
		),

		/*
		 // Apache2 Templates (adaoter not implemented yet)
		'apache2' => array(
			'global' => array(
				'00_nameserver.conf' => array(
					'path' => '/etc/imscp/apache/00_nameserver.conf',
					'scope' => 'system'
				),
			),
			'panel' => array(
				'00_master.conf' => array(
					'path' => '/etc/imscp/apache/00_master.conf',
					'scope' => 'system'
				),
				'00_master_ssl.conf' => array(
					'path' => '/etc/imscp/apache/00_master_ssl.conf',
					'scope' => 'system'
				)
			),
			'vhost' => array(
				'domain.tpl' => array(
					'path' => '/etc/imscp/apache/parts/domain.tpl',
					'scope' => 'site'
				),
				'domain_ssl.tpl' => array(
					'path' => '/etc/imscp/apache/parts/domain_ssl.tpl',
					'scope' => 'site'
				),
				'domain_disabled.tpl' => array(
					'path' => '/etc/imscp/apache/parts/domain_disabled.tpl',
					'scope' => 'site'
				),
				'domain_disabled_ssl.tpl' => array(
					'path' => '/etc/imscp/apache/parts/domain_disabled_ssl.tpl',
					'scope' => 'site'
				),
				'domain_redirect.tpl' => array(
					'path' => '/etc/imscp/apache/parts/domain_redirect.tpl',
					'scope' => 'site'
				),
				'domain_redirect_ssl.tpl' => array(
					'path' => '/etc/imscp/apache/parts/domain_redirect_ssl.tpl',
					'scope' => 'site'
				)
			)
		),
		*/

		/*
		// Courier Templates (adapter not implemented yet)
		'courier' => array(
			'global' => array(
				'authmysqlrc' => array(
					'path' => '/etc/imscp/courier/authmysqlrc',
					'scope' => 'system'
				),
				'quota-warning' => array(
					'path' => '/etc/imscp/courier/quota-warning',
					'scope' => 'system'
				)
			)
		),
		*/

		/*
		 // Dovecot Templates (adapter not implemented yet)
		'dovecot' => array(
			'global' => array(
				'dovecot.conf.1' => array(
					'path' => '/etc/imscp/dovecot/dovecot.conf.1',
					'scope' => 'system'
				),
				'dovecot.conf.2' => array(
					'path' => '/etc/imscp/dovecot/dovecot.conf.2',
					'scope' => 'system'
				),
				'dovecot-sql.conf' => array(
					'path' => '/etc/imscp/dovecot/dovecot-sql.conf',
					'scope' => 'system'
				),
				'quota-warning.1' => array(
					'path' => '/etc/imscp/dovecot/quota-warning.1',
					'scope' => 'system'
				),
				'quota-warning.2' => array(
					'path' => '/etc/imscp/dovecot/quota-warning.1',
					'scope' => 'system'
				)
			)
		),
		*/

		/*
		// Postfix Templates (adapter not implemented yet)
		'postfix' => array(
			'system' => array(
				'main.cf' => array(
					'path' => '/etc/imscp/postfix/main.cf',
					'scope' => 'system'
				),
				'master.cf' => array(
					'path' => '/etc/imscp/postfix/master.cf',
					'scope' => 'system'
				)
			)
		),
		*/

		/*
		// Proftpd Templates (adapter not implemented yet)
		'proftpd' => array(
			'system' => array(
				'proftpd.conf' => array(
					'path' => '/etc/imscp/postfix/proftpd.conf',
					'scope' => 'system'
				)
			)
		),
		*/

		// TODO PHP templates

		// Level for template assigment (only reseller level is currently supported)
		// Note: Only site-wide templates are assignable
		'assigmnent_level' => 'reseller',

		// Whether or not default template must be synced with last available versions on update
		'sync_templates' => true,

		// Whether or not obsolete templates (including childs) must be purged on update
		'purge_obsolete' => true
	)
);

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
		// Bind9 service templates
		'bind' => array(
			# Templates which operates at system-wide
			'system' => array(
					'named.conf' => '/etc/imscp/bind/named.conf',
					'named.conf.local' => '/etc/imscp/bind/named.conf.local',
					'named.conf.options' => '/etc/imscp/bind/named.conf.options'
			),
			# Template which operate at site-wide
			'site' => array(
				'cfg_master.tpl' => '/etc/imscp/bind/parts/cfg_master.tpl',
				'db.tpl' => '/etc/imscp/bind/parts/db.tpl',
				'db_sub.tpl' => '/etc/imscp/bind/parts/db_sub.tpl',
				'cfg_slave.tpl' => '/etc/imscp/bind/parts/cfg_slave.tpl'
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

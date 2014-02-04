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
	'service_templates' => array(
		'bind9' => array(
			'named.conf' => array(
				'path' => '/etc/imscp/bind/named.conf',
				'scope' => 'system'
			),
			'named.conf.local' => array(
				'path' => '/etc/imscp/bind/named.conf.local',
				'scope' => 'system'
			),
			'named.conf.options' => array(
				'path' =>  '/etc/imscp/bind/named.conf.options',
				'scope' => 'system'
			),
			'cfg_master.tpl' => array(
				'path' => '/etc/imscp/bind/parts/cfg_master.tpl',
				'scope' => 'site'
			),
			'cfg_slave.tpl' => array(
				'path' => '/etc/imscp/bind/parts/cfg_slave.tpl',
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
		)
		// TODO other services
	)
);

<?php
/**
 * i-MSCP MyDNS Plugin
 * Copyright (C) 2010-2013 by Laurent Declercq
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @category    iMSCP
 * @package     iMSCP_Plugin
 * @subpackage  MyDNS
 * @copyright   2010-2013 by Laurent Declercq
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

return array(
	'author' => 'Laurent Declercq',
	'email' => 'l.declercq@nuxwin.com',
	'version' => '0.0.1',
	'date' => '2013-10-16',
	'name' => 'MyDNS',
	'desc' => "MyDNS plugin for i-MSCP is designed as a full DNS management system. It's a drop-in replacement for the built-in i-MSCP DNS server implementation.",
	'url' => 'http://i-mscp.net',
	'require' => array(
		'imscp' => '20131010',
		'servers' => array(
			'apache2',
			'bind9',
			'sql' => array('mariadb', 'mysql')
		)
	)
);
